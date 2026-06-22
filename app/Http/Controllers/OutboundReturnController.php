<?php

namespace App\Http\Controllers;

use App\Models\OutboundReturn;
use App\Models\OutboundReturnItem;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OutboundReturnController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;
        $query = OutboundReturn::with('createdBy', 'companyBranch')->forCompanyBranch($branchScopeId);

        if ($canFilterBranches && $request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }
        
        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('return_number', 'like', "%{$request->search}%")
                    ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->filled('return_type')) {
            $query->where('return_type', $request->return_type);
        }
        
        $perPage = $request->get('per_page', 10);
        $returns = $query->orderBy('return_date', 'desc')->paginate($perPage);
        
        $types = OutboundReturn::TYPES;
        $actions = OutboundReturn::ACTIONS;
        $companyBranches = $this->availableCompanyBranches();
        
        return view('outbound-returns.index', compact('returns', 'types', 'actions', 'companyBranches', 'canFilterBranches'));
    }

    public function create()
    {
        $products = Product::active()->orderBy('name')->get();
        $types = OutboundReturn::TYPES;
        $actions = OutboundReturn::ACTIONS;
        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();
        $defaultBranchId = $this->defaultCompanyBranchId();
        
        return view('outbound-returns.create', compact('products', 'types', 'actions', 'companyBranches', 'branchLocked', 'defaultBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'customer_phone' => 'nullable|string|max:20',
            'reference_order' => 'nullable|string',
            'return_type' => 'required|in:' . implode(',', array_keys(OutboundReturn::TYPES)),
            'reason_detail' => 'nullable|string',
            'action' => 'required|in:' . implode(',', array_keys(OutboundReturn::ACTIONS)),
            'replacement_order' => 'nullable|string',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $items = [];
            
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $price = $product->price;
                $subtotalItem = $item['quantity'] * $price;
                $subtotal += $subtotalItem;
                
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $subtotalItem,
                    'notes' => $item['notes'] ?? null,
                ];
            }
            
            $companyBranchId = $this->resolveCompanyBranchId($validated['company_branch_id'] ?? null);
            $returnNumber = OutboundReturn::generateReturnNumber();
            
            $return = OutboundReturn::create([
                'return_number' => $returnNumber,
                'company_branch_id' => $companyBranchId,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'reference_order' => $validated['reference_order'],
                'return_type' => $validated['return_type'],
                'reason_detail' => $validated['reason_detail'],
                'action' => $validated['action'],
                'replacement_order' => $validated['replacement_order'],
                'return_date' => $validated['return_date'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);
            
            foreach ($items as $item) {
                $item['outbound_return_id'] = $return->id;
                OutboundReturnItem::create($item);
                
                $product = Product::find($item['product_id']);
                $stockReduced = $product->reduceForReturnOut(
                    $item['quantity'],
                    $return->id,
                    'Retur penjualan: ' . $validated['return_type'] . ' - ' . ($validated['reason_detail'] ?? '')
                );

                if (!$stockReduced) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi untuk retur. Tersedia: {$product->current_stock}");
                }
            }
            
            DB::commit();
            
            return redirect()->route('outbound-returns.show', $return)
                ->with('success', 'Retur berhasil dicatat. Stok berkurang.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat retur: ' . $e->getMessage());
        }
    }

    public function show(OutboundReturn $outboundReturn)
    {
        $this->authorizeBranch($outboundReturn);

        $outboundReturn->load('items.product', 'createdBy', 'companyBranch');
        
        return view('outbound-returns.show', compact('outboundReturn'));
    }

    public function destroy(OutboundReturn $outboundReturn)
    {
        $this->authorizeBranch($outboundReturn);

        $outboundReturn->delete();
        
        return redirect()->route('outbound-returns.index')
            ->with('success', 'Retur berhasil dihapus');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function availableCompanyBranches()
    {
        $query = CompanyProfile::defaultProfile()->activeBranches();

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->whereKey($branchScopeId);
        }

        return $query->get();
    }

    private function defaultCompanyBranchId(): ?int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        $defaultBranch = CompanyProfile::defaultProfile()->defaultInvoiceBranch();

        return $defaultBranch ? $defaultBranch->id : null;
    }

    private function resolveCompanyBranchId($requestedBranchId): ?int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        if ($requestedBranchId && CompanyBranch::whereKey($requestedBranchId)->where('is_active', true)->exists()) {
            return (int) $requestedBranchId;
        }

        return $this->defaultCompanyBranchId();
    }

    private function authorizeBranch(OutboundReturn $return): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $return->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
