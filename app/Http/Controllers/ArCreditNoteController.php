<?php

namespace App\Http\Controllers;

use App\Models\ArCreditNote;
use App\Models\ArInvoice;
use App\Models\CompanyBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArCreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = ArCreditNote::with(['arInvoice', 'customer', 'customerUser', 'companyBranch', 'postedBy'])
            ->forUserBranch()
            ->latest('note_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_branch_id') && !$this->currentBranchScopeId()) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('note_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('arInvoice', fn ($invoice) => $invoice->where('invoice_number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customerUser', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        $creditNotes = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ArCreditNote::STATUS_LIST;
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();

        return view('ar-credit-notes.index', compact('creditNotes', 'statuses', 'companyBranches', 'canFilterBranches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ar_invoice_id' => ['required', 'exists:ar_invoices,id'],
            'note_date' => ['required', 'date'],
            'reason_type' => ['required', 'in:' . implode(',', array_keys(ArCreditNote::REASON_LIST))],
            'amount' => ['required', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice = ArInvoice::with(['customer', 'customerUser', 'companyBranch'])
            ->when($branchScopeId = $this->currentBranchScopeId(), fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->findOrFail($validated['ar_invoice_id']);

        try {
            $creditNote = ArCreditNote::postForInvoice($invoice, $validated, Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('ar-invoices.show', $invoice->fresh())
            ->with('success', 'Credit note ' . $creditNote->note_number . ' berhasil diposting.');
    }

    public function show(ArCreditNote $arCreditNote)
    {
        $this->authorizeBranchAccess($arCreditNote);

        $arCreditNote->load(['arInvoice.order', 'customer', 'customerUser', 'companyBranch', 'postedBy', 'voidedBy']);

        return view('ar-credit-notes.show', compact('arCreditNote'));
    }

    public function void(Request $request, ArCreditNote $arCreditNote)
    {
        $this->authorizeBranchAccess($arCreditNote);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $arCreditNote->voidNote($validated['void_reason'], Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ar-invoices.show', $arCreditNote->arInvoice)
            ->with('success', 'Credit note AR berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function authorizeBranchAccess(ArCreditNote $creditNote): void
    {
        $branchScopeId = $this->currentBranchScopeId();

        abort_if(
            $branchScopeId && (int) $creditNote->company_branch_id !== (int) $branchScopeId,
            403
        );
    }
}
