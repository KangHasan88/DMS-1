<?php

namespace App\Http\Controllers;

use App\Models\ApDebitNote;
use App\Models\ApInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApDebitNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = ApDebitNote::with(['apInvoice', 'supplier', 'postedBy'])
            ->forUserBranch()
            ->latest('note_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('note_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('apInvoice', fn ($invoice) => $invoice->where('invoice_number', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            });
        }

        $debitNotes = $query->paginate($request->get('per_page', 10))->withQueryString();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $statuses = ApDebitNote::STATUS_LIST;

        return view('ap-debit-notes.index', compact('debitNotes', 'suppliers', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ap_invoice_id' => ['required', 'exists:ap_invoices,id'],
            'note_date' => ['required', 'date'],
            'reason_type' => ['required', 'in:' . implode(',', array_keys(ApDebitNote::REASON_LIST))],
            'amount' => ['required', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice = ApInvoice::with(['supplier', 'companyBranch'])
            ->forUserBranch()
            ->findOrFail($validated['ap_invoice_id']);

        try {
            $debitNote = ApDebitNote::postForInvoice($invoice, $validated, Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('ap-invoices.show', $invoice->fresh())
            ->with('success', 'Debit note ' . $debitNote->note_number . ' berhasil diposting.');
    }

    public function show(ApDebitNote $apDebitNote)
    {
        $this->authorizeBranchAccess($apDebitNote);

        $apDebitNote->load(['apInvoice.purchaseOrder', 'supplier', 'companyBranch', 'postedBy', 'voidedBy']);

        return view('ap-debit-notes.show', compact('apDebitNote'));
    }

    public function void(Request $request, ApDebitNote $apDebitNote)
    {
        $this->authorizeBranchAccess($apDebitNote);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $apDebitNote->voidNote($validated['void_reason'], Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ap-invoices.show', $apDebitNote->apInvoice)
            ->with('success', 'Debit note AP berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function authorizeBranchAccess(ApDebitNote $debitNote): void
    {
        $branchScopeId = Auth::user()?->scopedCompanyBranchId();

        abort_if(
            $branchScopeId && (int) $debitNote->company_branch_id !== (int) $branchScopeId,
            403
        );
    }
}
