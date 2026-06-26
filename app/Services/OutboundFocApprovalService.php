<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\OutboundFoc;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutboundFocApprovalService
{
    public function approve(OutboundFoc $foc, ?string $note = null): OutboundFoc
    {
        if (!$foc->isApprovalPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Bonus / FOC ini sudah diproses.',
            ]);
        }

        return DB::transaction(function () use ($foc, $note) {
            $foc->loadMissing(['items.product.stock', 'approvalRequest']);

            foreach ($foc->items as $item) {
                $product = $item->product;

                if (!$product || !$product->stock || !$product->stock->hasStock((int) $item->quantity)) {
                    throw ValidationException::withMessages([
                        'approval' => "Stok {$item->product?->name} tidak mencukupi untuk Bonus / FOC.",
                    ]);
                }
            }

            foreach ($foc->items as $item) {
                $stockReduced = $item->product->reduceForFocOut(
                    (int) $item->quantity,
                    $foc->id,
                    'Bonus / FOC approved: ' . $foc->reason_label . ' - ' . ($foc->reason_detail ?? '')
                );

                if (!$stockReduced) {
                    throw ValidationException::withMessages([
                        'approval' => "Stok {$item->product->name} tidak mencukupi untuk Bonus / FOC.",
                    ]);
                }
            }

            if ($foc->approvalRequest?->isPending()) {
                app(ApprovalWorkflowService::class)->approve($foc->approvalRequest, $note);
            }

            $foc->forceFill([
                'approval_status' => OutboundFoc::APPROVAL_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])->save();

            return $foc->refresh();
        });
    }

    public function reject(OutboundFoc $foc, string $note): OutboundFoc
    {
        if (!$foc->isApprovalPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Bonus / FOC ini sudah diproses.',
            ]);
        }

        return DB::transaction(function () use ($foc, $note) {
            $foc->loadMissing('approvalRequest');

            if ($foc->approvalRequest?->isPending()) {
                app(ApprovalWorkflowService::class)->reject($foc->approvalRequest, $note);
            }

            $foc->forceFill([
                'approval_status' => OutboundFoc::APPROVAL_REJECTED,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_note' => $note,
            ])->save();

            return $foc->refresh();
        });
    }
}
