<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'company_branch_id',
        'order_date',
        'expected_delivery_date',
        'received_date',
        'status',
        'subtotal',
        'total',
        'notes',
        'internal_notes',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_request_id',
        'approval_status',
        'rejected_by',
        'rejected_at',
        'rejection_note',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'integer',
        'total' => 'integer',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LIST = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_PARTIALLY_RECEIVED => 'Partially Received',
        self::STATUS_RECEIVED => 'Received',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const STATUS_COLORS = [
        self::STATUS_DRAFT => 'secondary',
        self::STATUS_PENDING => 'warning',
        self::STATUS_PARTIALLY_RECEIVED => 'info',
        self::STATUS_RECEIVED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    const APPROVAL_NOT_REQUESTED = 'not_requested';
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    const APPROVAL_STATUSES = [
        self::APPROVAL_NOT_REQUESTED => 'Belum Diajukan',
        self::APPROVAL_PENDING => 'Menunggu Approval',
        self::APPROVAL_APPROVED => 'Disetujui',
        self::APPROVAL_REJECTED => 'Ditolak',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function apInvoice()
    {
        return $this->hasOne(ApInvoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    // ===================== HELPER METHODS =====================
    
    public static function generatePONumber(): string
    {
        $date = now()->format('Ymd');
        $lastPO = self::whereDate('created_at', today())->orderBy('id', 'desc')->first();
        $sequence = $lastPO ? intval(substr($lastPO->po_number, -4)) + 1 : 1;
        
        return 'PO' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = $subtotal;
        $this->save();
    }

    public function canReceive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIALLY_RECEIVED], true)
            && $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isInvoiceableForAp(): bool
    {
        return !$this->apInvoice && $this->status === self::STATUS_RECEIVED;
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && !in_array($this->approval_status, [self::APPROVAL_PENDING, self::APPROVAL_APPROVED], true);
    }

    public function isApprovalPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function approve(): void
    {
        $this->status = self::STATUS_PENDING;
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->approval_status = self::APPROVAL_APPROVED;
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return self::APPROVAL_STATUSES[$this->approval_status] ?? ucfirst((string) $this->approval_status);
    }

    // ===================== SCOPES =====================
    
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeForUserBranch($query, ?User $user = null)
    {
        $branchScopeId = ($user ?? auth()->user())?->scopedCompanyBranchId();

        return $branchScopeId
            ? $query->where('company_branch_id', $branchScopeId)
            : $query;
    }
}
