<?php

namespace App\Services;

use App\Models\StockAdjustmentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentApprovalService
{
    public function approve(StockAdjustmentRequest $stockAdjustmentRequest, ?string $note = null): StockAdjustmentRequest
    {
        if (!$stockAdjustmentRequest->isApprovalPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Request penyesuaian stok ini sudah diproses.',
            ]);
        }

        return DB::transaction(function () use ($stockAdjustmentRequest, $note) {
            $stockAdjustmentRequest->loadMissing(['product.stock', 'approvalRequest']);

            $reason = trim(sprintf(
                'Approval penyesuaian stok %s: %s',
                $stockAdjustmentRequest->request_number,
                $stockAdjustmentRequest->reason
            ));

            $stockAdjustmentRequest->product->adjustStock(
                (int) $stockAdjustmentRequest->new_quantity,
                $reason
            );

            if ($stockAdjustmentRequest->approvalRequest?->isPending()) {
                app(ApprovalWorkflowService::class)->approve($stockAdjustmentRequest->approvalRequest, $note);
            }

            $stockAdjustmentRequest->forceFill([
                'approval_status' => StockAdjustmentRequest::APPROVAL_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])->save();

            return $stockAdjustmentRequest->refresh();
        });
    }

    public function reject(StockAdjustmentRequest $stockAdjustmentRequest, string $note): StockAdjustmentRequest
    {
        if (!$stockAdjustmentRequest->isApprovalPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Request penyesuaian stok ini sudah diproses.',
            ]);
        }

        return DB::transaction(function () use ($stockAdjustmentRequest, $note) {
            $stockAdjustmentRequest->loadMissing('approvalRequest');

            if ($stockAdjustmentRequest->approvalRequest?->isPending()) {
                app(ApprovalWorkflowService::class)->reject($stockAdjustmentRequest->approvalRequest, $note);
            }

            $stockAdjustmentRequest->forceFill([
                'approval_status' => StockAdjustmentRequest::APPROVAL_REJECTED,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_note' => $note,
            ])->save();

            return $stockAdjustmentRequest->refresh();
        });
    }
}
