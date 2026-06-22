<?php

namespace App\Http\Controllers;

use App\Models\OutboundFoc;
use App\Models\OutboundFocItem;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OutboundFocController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;
        $query = OutboundFoc::with('createdBy', 'companyBranch')->forCompanyBranch($branchScopeId);

        if ($canFilterBranches && $request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }
        
        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('foc_number', 'like', "%{$request->search}%")
                    ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }
        
        $perPage = $request->get('per_page', 10);
        $focs = $query->orderBy('foc_date', 'desc')->paginate($perPage);
        
        $reasons = OutboundFoc::REASONS;
        $companyBranches = $this->availableCompanyBranches();
        
        return view('outbound-focs.index', compact('focs', 'reasons', 'companyBranches', 'canFilterBranches'));
    }

    public function create()
    {
        $products = Product::active()->orderBy('name')->get();
        $reasons = OutboundFoc::REASONS;
        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();
        $defaultBranchId = $this->defaultCompanyBranchId();
        
        return view('outbound-focs.create', compact('products', 'reasons', 'companyBranches', 'branchLocked', 'defaultBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'customer_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'foc_date' => 'required|date',
            'reason' => 'required|in:' . implode(',', array_keys(OutboundFoc::REASONS)),
            'reason_detail' => 'nullable|string',
            'reference_order' => 'nullable|string',
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
            $focNumber = OutboundFoc::generateFocNumber();
            
            $foc = OutboundFoc::create([
                'foc_number' => $focNumber,
                'company_branch_id' => $companyBranchId,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'address' => $validated['address'],
                'foc_date' => $validated['foc_date'],
                'reason' => $validated['reason'],
                'reason_detail' => $validated['reason_detail'],
                'reference_order' => $validated['reference_order'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);
            
            foreach ($items as $item) {
                $item['outbound_foc_id'] = $foc->id;
                OutboundFocItem::create($item);
                
                $product = Product::find($item['product_id']);
                $stockReduced = $product->reduceForFocOut(
                    $item['quantity'],
                    $foc->id,
                    'Barang bonus: ' . $validated['reason'] . ' - ' . ($validated['reason_detail'] ?? '')
                );

                if (!$stockReduced) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi untuk barang bonus. Tersedia: {$product->current_stock}");
                }
            }
            
            DB::commit();
            
            return redirect()->route('outbound-focs.show', $foc)
                ->with('success', 'Barang bonus berhasil dicatat. Stok berkurang.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat barang bonus: ' . $e->getMessage());
        }
    }

    public function show(OutboundFoc $outboundFoc)
    {
        $this->authorizeBranch($outboundFoc);

        $outboundFoc->load('items.product', 'createdBy', 'companyBranch');
        
        return view('outbound-focs.show', compact('outboundFoc'));
    }

    public function destroy(OutboundFoc $outboundFoc)
    {
        $this->authorizeBranch($outboundFoc);

        $outboundFoc->delete();
        
        return redirect()->route('outbound-focs.index')
            ->with('success', 'Barang bonus berhasil dihapus');
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

    private function authorizeBranch(OutboundFoc $foc): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $foc->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
