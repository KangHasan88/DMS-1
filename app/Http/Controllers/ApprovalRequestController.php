<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\OutboundFoc;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustmentRequest;
use App\Services\OutboundFocApprovalService;
use App\Services\ApprovalWorkflowService;
use App\Services\PurchaseOrderApprovalService;
use App\Services\StockAdjustmentApprovalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $type = $request->input('approval_type');
        $search = trim((string) $request->input('search'));

        $approvalRequests = ApprovalRequest::query()
            ->with(['requester', 'decider', 'companyBranch'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($type, fn ($query) => $query->where('approval_type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('request_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return view('approval-requests.index', [
            'approvalRequests' => $approvalRequests,
            'statuses' => ApprovalRequest::STATUSES,
            'types' => ApprovalRequest::TYPES,
        ]);
    }

    public function show(ApprovalRequest $approvalRequest)
    {
        $approvalRequest->load(['requester', 'decider', 'companyBranch']);

        return view('approval-requests.show', compact('approvalRequest'));
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest, ApprovalWorkflowService $service)
    {
        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($approvalRequest->approvable_type === OutboundFoc::class && $approvalRequest->approvable) {
            app(OutboundFocApprovalService::class)->approve($approvalRequest->approvable, $validated['decision_note'] ?? null);
        } elseif ($approvalRequest->approvable_type === PurchaseOrder::class && $approvalRequest->approvable) {
            app(PurchaseOrderApprovalService::class)->approve($approvalRequest->approvable, $validated['decision_note'] ?? null);
        } elseif ($approvalRequest->approvable_type === StockAdjustmentRequest::class && $approvalRequest->approvable) {
            app(StockAdjustmentApprovalService::class)->approve($approvalRequest->approvable, $validated['decision_note'] ?? null);
        } else {
            $service->approve($approvalRequest, $validated['decision_note'] ?? null);
        }

        return redirect()
            ->route('approval-requests.show', $approvalRequest)
            ->with('success', 'Approval berhasil disetujui.');
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest, ApprovalWorkflowService $service)
    {
        $validated = $request->validate([
            'decision_note' => ['required', 'string', 'max:1000'],
        ]);

        if ($approvalRequest->approvable_type === OutboundFoc::class && $approvalRequest->approvable) {
            app(OutboundFocApprovalService::class)->reject($approvalRequest->approvable, $validated['decision_note']);
        } elseif ($approvalRequest->approvable_type === PurchaseOrder::class && $approvalRequest->approvable) {
            app(PurchaseOrderApprovalService::class)->reject($approvalRequest->approvable, $validated['decision_note']);
        } elseif ($approvalRequest->approvable_type === StockAdjustmentRequest::class && $approvalRequest->approvable) {
            app(StockAdjustmentApprovalService::class)->reject($approvalRequest->approvable, $validated['decision_note']);
        } else {
            $service->reject($approvalRequest, $validated['decision_note']);
        }

        return redirect()
            ->route('approval-requests.show', $approvalRequest)
            ->with('success', 'Approval berhasil ditolak.');
    }
}
