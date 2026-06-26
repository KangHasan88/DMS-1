<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderApprovalService
{
    public function approve(PurchaseOrder $purchaseOrder, ?string $note = null): PurchaseOrder
    {
        if (!$purchaseOrder->isApprovalPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Purchase Order ini tidak sedang menunggu approval.',
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $note) {
            $purchaseOrder->loadMissing('approvalRequest');

            if ($purchaseOrder->approvalRequest?->isPending()) {
                app(ApprovalWorkflowService::class)->approve($purchaseOrder->approvalRequest, $note);
            }

            $purchaseOrder->approve();
            $purchaseOrder->forceFill([
                'approval_status' => PurchaseOrder::APPROVAL_APPROVED,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_note' => null,
            ])->save();

            return $purchaseOrder->refresh();
        });
    }

    public function reject(PurchaseOrder $purchaseOrder, string $note): PurchaseOrder
    {
        if (!$purchaseOrder->isApprovalPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Purchase Order ini tidak sedang menunggu approval.',
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $note) {
            $purchaseOrder->loadMissing('approvalRequest');

            if ($purchaseOrder->approvalRequest?->isPending()) {
                app(ApprovalWorkflowService::class)->reject($purchaseOrder->approvalRequest, $note);
            }

            $purchaseOrder->forceFill([
                'approval_status' => PurchaseOrder::APPROVAL_REJECTED,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_note' => $note,
            ])->save();

            return $purchaseOrder->refresh();
        });
    }
}
