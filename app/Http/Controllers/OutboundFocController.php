<?php

namespace App\Http\Controllers;

use App\Models\OutboundFoc;
use App\Models\OutboundFocItem;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Order;
use App\Models\Product;
use App\Services\ApprovalWorkflowService;
use App\Services\ProductBonusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OutboundFocController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;
        $query = OutboundFoc::with('createdBy', 'companyBranch', 'approvalRequest')->forCompanyBranch($branchScopeId);

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

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }
        
        $perPage = $request->get('per_page', 10);
        $focs = $query->orderBy('foc_date', 'desc')->paginate($perPage);
        
        $reasons = OutboundFoc::REASONS;
        $companyBranches = $this->availableCompanyBranches();
        
        $approvalStatuses = OutboundFoc::APPROVAL_STATUSES;

        return view('outbound-focs.index', compact('focs', 'reasons', 'companyBranches', 'canFilterBranches', 'approvalStatuses'));
    }

    public function create(Request $request)
    {
        $products = Product::active()->orderBy('name')->get();
        $reasons = OutboundFoc::REASONS;
        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();
        $defaultBranchId = $this->defaultCompanyBranchId();
        $prefill = [
            'company_branch_id' => $defaultBranchId,
            'customer_name' => '',
            'customer_phone' => '',
            'address' => '',
            'reason' => '',
            'reason_detail' => '',
            'reference_order' => '',
            'notes' => '',
            'items' => [['product_id' => '', 'quantity' => 1]],
        ];

        if ($request->filled('order_id')) {
            $order = Order::with(['user.customer', 'items.product'])->findOrFail($request->order_id);

            if (($branchScopeId = $this->currentBranchScopeId()) && (int) $order->company_branch_id !== $branchScopeId) {
                abort(403);
            }

            $bonusItems = $order->items
                ->map(function ($item) use ($order) {
                    $rule = $item->product
                        ? app(ProductBonusService::class)->resolveBonus($item->product, $item->quantity, $order->user?->customer, $order->company_branch_id)
                        : null;

                    return $rule ? [
                        'product_id' => $rule->bonus_product_id,
                        'quantity' => $rule->bonus_quantity,
                    ] : null;
                })
                ->filter()
                ->values()
                ->all();

            if (!empty($bonusItems)) {
                $prefill = [
                    'company_branch_id' => $order->company_branch_id ?: $defaultBranchId,
                    'customer_name' => $order->user?->customer?->name ?? $order->user?->name ?? '',
                    'customer_phone' => $order->user?->customer?->phone ?? $order->user?->phone ?? '',
                    'address' => $order->shipping_address_snapshot ?? $order->address ?? '',
                    'reason' => OutboundFoc::REASON_PROMOTION,
                    'reason_detail' => 'Bonus promo dari order ' . $order->order_number,
                    'reference_order' => $order->order_number,
                    'notes' => 'Dibuat dari rekomendasi Aturan Bonus.',
                    'items' => $bonusItems,
                ];
            }
        }
        
        return view('outbound-focs.create', compact('products', 'reasons', 'companyBranches', 'branchLocked', 'defaultBranchId', 'prefill'));
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
                'customer_phone' => $validated['customer_phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'foc_date' => $validated['foc_date'],
                'reason' => $validated['reason'],
                'reason_detail' => $validated['reason_detail'] ?? null,
                'reference_order' => $validated['reference_order'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'approval_status' => OutboundFoc::APPROVAL_PENDING,
            ]);
            
            foreach ($items as $item) {
                $item['outbound_foc_id'] = $foc->id;
                OutboundFocItem::create($item);
            }

            $approvalRequest = app(ApprovalWorkflowService::class)->request([
                'approval_type' => \App\Models\ApprovalRequest::TYPE_OUTBOUND_FOC,
                'company_branch_id' => $companyBranchId,
                'title' => 'Approval Bonus / FOC ' . $foc->foc_number,
                'description' => 'Pengeluaran barang gratis untuk ' . $foc->customer_name,
                'request_note' => $foc->reason_detail,
                'payload' => [
                    'foc_number' => $foc->foc_number,
                    'customer' => $foc->customer_name,
                    'reason' => $foc->reason_label,
                    'total_items' => collect($items)->sum('quantity'),
                    'total_value' => $subtotal,
                    'reference_order' => $foc->reference_order,
                ],
            ], $foc);

            $foc->forceFill(['approval_request_id' => $approvalRequest->id])->save();
            
            DB::commit();
            
            return redirect()->route('outbound-focs.show', $foc)
                ->with('success', 'Bonus / FOC berhasil dibuat dan menunggu approval. Stok belum berkurang.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat barang bonus: ' . $e->getMessage());
        }
    }

    public function show(OutboundFoc $outboundFoc)
    {
        $this->authorizeBranch($outboundFoc);

        $outboundFoc->load('items.product', 'createdBy', 'companyBranch', 'approvalRequest', 'approvedBy', 'rejectedBy');
        
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
