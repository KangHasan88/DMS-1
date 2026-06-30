<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ArInvoice::with(['order', 'customer', 'customerUser', 'companyBranch', 'issuedBy'])
            ->latest('invoice_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('exchange_status')) {
            $request->exchange_status === 'needs_exchange'
                ? $query->where('outstanding_amount', '>', 0)->where('exchange_status', '!=', ArInvoice::EXCHANGE_ACCEPTED)
                : $query->where('exchange_status', $request->exchange_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customerUser', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ArInvoice::STATUS_LIST;
        $exchangeStatuses = ArInvoice::EXCHANGE_STATUS_LIST;
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;

        $invoiceableOrders = Order::query()
            ->with(['user.customer', 'companyBranch'])
            ->whereDoesntHave('arInvoice')
            ->where(function ($orderQuery) {
                $orderQuery->where('status', Order::STATUS_DELIVERED)
                    ->orWhereHas('delivery', fn ($delivery) => $delivery->where('status', \App\Models\Delivery::STATUS_COMPLETED));
            })
            ->when($branchScopeId, fn ($orderQuery) => $orderQuery->where('company_branch_id', $branchScopeId))
            ->latest()
            ->limit(8)
            ->get()
            ->filter(fn (Order $order) => $order->isInvoiceableForAr());

        return view('ar-invoices.index', compact(
            'invoices',
            'statuses',
            'exchangeStatuses',
            'companyBranches',
            'branchScopeId',
            'canFilterBranches',
            'invoiceableOrders'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with(['arInvoice', 'delivery', 'items.product', 'companyBranch', 'user.customer'])
            ->findOrFail($validated['order_id']);

        $this->authorizeBranch($order);

        try {
            $invoice = ArInvoice::issueFromOrder($order, Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ar-invoices.show', $invoice)
            ->with('success', 'AR Invoice berhasil diterbitkan.');
    }

    public function show(ArInvoice $arInvoice)
    {
        $this->authorizeInvoiceBranch($arInvoice);

        $arInvoice->load([
            'items.product',
            'order.items',
            'customer',
            'customerUser',
            'companyBranch',
            'issuedBy',
            'paymentAllocations.customerPayment.chartAccount',
            'paymentAllocations.customerPayment.receivedBy',
            'creditNotes.postedBy',
            'creditNotes.voidedBy',
            'exchangeCollector',
        ]);

        $cashAccounts = $this->cashAccountsForBranch($arInvoice->company_branch_id);
        $collectors = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($arInvoice) {
                $query->whereNull('company_branch_id')
                    ->when($arInvoice->company_branch_id, fn ($branchQuery) => $branchQuery->orWhere('company_branch_id', $arInvoice->company_branch_id));
            })
            ->orderBy('name')
            ->get();

        return view('ar-invoices.show', compact('arInvoice', 'cashAccounts', 'collectors'));
    }

    public function updateExchange(Request $request, ArInvoice $arInvoice)
    {
        $this->authorizeInvoiceBranch($arInvoice);

        if ($arInvoice->status === ArInvoice::STATUS_VOID) {
            return back()->with('error', 'Invoice void tidak bisa diproses tukar faktur.');
        }

        $validated = $request->validate([
            'exchange_status' => ['required', 'in:' . implode(',', array_keys(ArInvoice::EXCHANGE_STATUS_LIST))],
            'exchange_scheduled_date' => ['nullable', 'date'],
            'exchange_next_action_date' => ['nullable', 'date'],
            'exchange_collector_id' => ['nullable', 'exists:users,id'],
            'exchange_receipt_number' => ['nullable', 'string', 'max:100'],
            'exchange_rejection_reason' => ['nullable', 'string', 'max:500'],
            'exchange_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['exchange_status'] === ArInvoice::EXCHANGE_REJECTED && empty($validated['exchange_rejection_reason'])) {
            return back()->withInput()->with('error', 'Alasan penolakan/revisi wajib diisi.');
        }

        if ($validated['exchange_status'] === ArInvoice::EXCHANGE_ACCEPTED && empty($validated['exchange_receipt_number'])) {
            return back()->withInput()->with('error', 'Nomor tanda terima wajib diisi saat tukar faktur diterima customer.');
        }

        DB::transaction(function () use ($arInvoice, $validated) {
            $timestampFields = [
                'exchange_submitted_at' => $arInvoice->exchange_submitted_at,
                'exchange_accepted_at' => $arInvoice->exchange_accepted_at,
                'exchange_rejected_at' => $arInvoice->exchange_rejected_at,
            ];

            if ($validated['exchange_status'] === ArInvoice::EXCHANGE_SUBMITTED) {
                $timestampFields['exchange_submitted_at'] = now();
            }

            if ($validated['exchange_status'] === ArInvoice::EXCHANGE_ACCEPTED) {
                $timestampFields['exchange_accepted_at'] = now();
                $timestampFields['exchange_rejected_at'] = null;
            }

            if ($validated['exchange_status'] === ArInvoice::EXCHANGE_REJECTED) {
                $timestampFields['exchange_rejected_at'] = now();
            }

            $arInvoice->forceFill(array_merge($timestampFields, [
                'exchange_status' => $validated['exchange_status'],
                'exchange_scheduled_date' => $validated['exchange_scheduled_date'] ?? null,
                'exchange_next_action_date' => $validated['exchange_next_action_date'] ?? null,
                'exchange_collector_id' => $validated['exchange_collector_id'] ?? null,
                'exchange_receipt_number' => $validated['exchange_receipt_number'] ?? null,
                'exchange_rejection_reason' => $validated['exchange_status'] === ArInvoice::EXCHANGE_REJECTED ? $validated['exchange_rejection_reason'] : null,
                'exchange_notes' => $validated['exchange_notes'] ?? null,
            ]))->save();

            \App\Models\ActivityLog::record('ar_invoices', 'exchange_status_updated', 'Status tukar faktur AR Invoice diupdate', $arInvoice, [
                'invoice_number' => $arInvoice->invoice_number,
                'exchange_status' => $arInvoice->exchange_status,
                'collector_id' => $arInvoice->exchange_collector_id,
            ]);
        });

        return redirect()->route('ar-invoices.show', $arInvoice->fresh())
            ->with('success', 'Status tukar faktur berhasil diupdate.');
    }

    public function void(Request $request, ArInvoice $arInvoice)
    {
        $this->authorizeInvoiceBranch($arInvoice);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $arInvoice->voidInvoice($validated['void_reason'], Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ar-invoices.show', $arInvoice->fresh())
            ->with('success', 'AR Invoice berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function authorizeBranch(Order $order): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $order->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }

    private function authorizeInvoiceBranch(ArInvoice $invoice): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $invoice->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }

    private function cashAccountsForBranch(?int $branchId)
    {
        return ChartAccount::query()
            ->where('is_active', true)
            ->where('is_cash_account', true)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('company_branch_id')
                    ->when($branchId, fn ($branchQuery) => $branchQuery->orWhere('company_branch_id', $branchId));
            })
            ->orderBy('code')
            ->get();
    }
}
