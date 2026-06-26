<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductPriceChangeApprovalService
{
    public function approve(ApprovalRequest $approvalRequest, ?string $note = null): ApprovalRequest
    {
        if ($approvalRequest->approval_type !== ApprovalRequest::TYPE_PRICE_CHANGE) {
            throw ValidationException::withMessages([
                'approval' => 'Tipe approval bukan perubahan harga.',
            ]);
        }

        if (!$approvalRequest->isPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Persetujuan ini sudah diproses.',
            ]);
        }

        $product = $approvalRequest->approvable;

        if (!$product instanceof Product) {
            throw ValidationException::withMessages([
                'approval' => 'Produk untuk approval perubahan harga tidak ditemukan.',
            ]);
        }

        $payload = $approvalRequest->payload ?? [];
        $newBasePrice = (int) ($payload['new_base_price'] ?? 0);
        $newPrice = (int) ($payload['new_price'] ?? 0);

        return DB::transaction(function () use ($approvalRequest, $product, $payload, $newBasePrice, $newPrice, $note) {
            $oldBasePrice = (int) $product->base_price;
            $oldPrice = (int) $product->price;

            $product->update([
                'base_price' => $newBasePrice,
                'price' => $newPrice,
            ]);

            ProductPriceHistory::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'old_base_price' => $oldBasePrice,
                'new_base_price' => $newBasePrice,
                'reason' => $payload['reason'] ?? 'Perubahan harga disetujui melalui approval.',
            ]);

            return app(ApprovalWorkflowService::class)->approve($approvalRequest, $note);
        });
    }
}
