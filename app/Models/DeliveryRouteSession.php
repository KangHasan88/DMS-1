<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DeliveryRouteSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_profile_id',
        'company_branch_id',
        'sales_territory_id',
        'salesperson_id',
        'driver_id',
        'delivery_vehicle_id',
        'route_code',
        'route_date',
        'selling_mode',
        'status',
        'opening_qty',
        'sold_qty',
        'returned_qty',
        'damaged_qty',
        'started_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'route_date' => 'date',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_qty' => 'integer',
        'sold_qty' => 'integer',
        'returned_qty' => 'integer',
        'damaged_qty' => 'integer',
    ];

    public const MODE_FULL_CANVAS = 'full_canvas';
    public const MODE_SEMI_CANVAS = 'semi_canvas';

    public const MODE_LIST = [
        self::MODE_FULL_CANVAS => 'Full Canvas',
        self::MODE_SEMI_CANVAS => 'Semi Canvas',
    ];

    public const STATUS_PLANNED = 'planned';
    public const STATUS_LOADING = 'loading';
    public const STATUS_ON_ROUTE = 'on_route';
    public const STATUS_SETTLING = 'settling';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LIST = [
        self::STATUS_PLANNED => 'Direncanakan',
        self::STATUS_LOADING => 'Loading',
        self::STATUS_ON_ROUTE => 'Dalam Perjalanan',
        self::STATUS_SETTLING => 'Rekap',
        self::STATUS_CLOSED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PLANNED => 'secondary',
        self::STATUS_LOADING => 'info',
        self::STATUS_ON_ROUTE => 'primary',
        self::STATUS_SETTLING => 'warning',
        self::STATUS_CLOSED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    protected $appends = [
        'status_label',
        'status_color',
        'selling_mode_label',
        'remaining_qty',
    ];

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function salesTerritory(): BelongsTo
    {
        return $this->belongsTo(SalesTerritory::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(DeliveryVehicle::class, 'delivery_vehicle_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'route_session_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getSellingModeLabelAttribute(): string
    {
        return self::MODE_LIST[$this->selling_mode] ?? ucfirst(str_replace('_', ' ', (string) $this->selling_mode));
    }

    public function getRemainingQtyAttribute(): int
    {
        return max(0, (int) $this->opening_qty - (int) $this->sold_qty - (int) $this->returned_qty - (int) $this->damaged_qty);
    }

    public function canEdit(): bool
    {
        return !in_array($this->status, [self::STATUS_CLOSED, self::STATUS_CANCELLED], true);
    }

    public static function generateRouteCode(?CompanyBranch $branch = null, ?string $routeDate = null): string
    {
        $companyCode = CompanyProfile::normalizeCodePart(CompanyProfile::defaultProfile()->code ?: 'KMG', 'KMG');
        $branchCode = CompanyProfile::normalizeCodePart($branch?->code ?: 'GLB', 'GLB');
        $datePart = $routeDate ? Str::of($routeDate)->replace('-', '')->toString() : now()->format('Ymd');

        return 'RTS-' . $companyCode . $branchCode . $datePart;
    }
}
