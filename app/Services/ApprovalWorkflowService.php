<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflowService
{
    public function request(array $data, ?Model $approvable = null): ApprovalRequest
    {
        return DB::transaction(function () use ($data, $approvable) {
            if ($approvable) {
                $data['approvable_type'] = $approvable::class;
                $data['approvable_id'] = $approvable->getKey();
            }

            $approvalRequest = ApprovalRequest::create($data);

            ActivityLog::record('approval', 'requested', 'Approval request dibuat', $approvalRequest, [
                'approval_type' => $approvalRequest->approval_type,
                'status' => $approvalRequest->status,
                'approvable_type' => $approvalRequest->approvable_type,
                'approvable_id' => $approvalRequest->approvable_id,
            ]);

            return $approvalRequest;
        });
    }

    public function approve(ApprovalRequest $approvalRequest, ?string $note = null): ApprovalRequest
    {
        return $this->decide($approvalRequest, ApprovalRequest::STATUS_APPROVED, $note);
    }

    public function reject(ApprovalRequest $approvalRequest, string $note): ApprovalRequest
    {
        return $this->decide($approvalRequest, ApprovalRequest::STATUS_REJECTED, $note);
    }

    private function decide(ApprovalRequest $approvalRequest, string $status, ?string $note): ApprovalRequest
    {
        if (!$approvalRequest->isPending()) {
            throw ValidationException::withMessages([
                'approval' => 'Persetujuan ini sudah diproses.',
            ]);
        }

        return DB::transaction(function () use ($approvalRequest, $status, $note) {
            $approvalRequest->forceFill([
                'status' => $status,
                'decision_note' => $note,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
            ])->save();

            ActivityLog::record('approval', $status, 'Approval request ' . $approvalRequest->status_label, $approvalRequest, [
                'approval_type' => $approvalRequest->approval_type,
                'decision_note' => $approvalRequest->decision_note,
                'decided_by' => $approvalRequest->decided_by,
            ]);

            return $approvalRequest->refresh();
        });
    }
}
