<?php

namespace App\Services;

use App\Models\AccountingPeriodLock;
use Carbon\CarbonInterface;

class AccountingPeriodLockService
{
    public function assertOpen(CarbonInterface|string $date, ?int $companyBranchId = null): void
    {
        $postingDate = is_string($date) ? \Carbon\Carbon::parse($date)->toDateString() : $date->toDateString();

        $lock = AccountingPeriodLock::query()
            ->locked()
            ->whereDate('date_from', '<=', $postingDate)
            ->whereDate('date_to', '>=', $postingDate)
            ->where(function ($query) use ($companyBranchId) {
                $query->whereNull('company_branch_id');

                if ($companyBranchId) {
                    $query->orWhere('company_branch_id', $companyBranchId);
                }
            })
            ->with('companyBranch')
            ->first();

        if ($lock) {
            $scope = $lock->companyBranch?->name ?? 'Global';

            throw new \InvalidArgumentException("Periode akuntansi {$postingDate} sudah dikunci ({$scope}).");
        }
    }
}
