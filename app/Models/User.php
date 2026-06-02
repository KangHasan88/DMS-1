<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Lab404\Impersonate\Models\Impersonate;
use Illuminate\Support\Facades\Hash;
use App\Traits\HasLastLogin;

class User extends Authenticatable
{
    use HasApiTokens, 
        HasFactory,
        HasRoles, 
        LogsActivity,
        Notifiable, 
        SoftDeletes, 
        Impersonate,
        HasLastLogin;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'phone',
        'photo',
        'gender',
        'birth_date',
        'address',
        'is_active',
        'locale',
        'last_login_at',
        'last_login_ip',
        'employee_id',
        'position',
        'department',
        'join_date',
        'supervisor_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'locale' => 'string',
        'birth_date' => 'date',
        'join_date' => 'date',
        'last_login_at' => 'datetime'
    ];

    protected $appends = ['photo_url', 'full_name', 'is_online', 'last_login_formatted'];

    // ===================== CONFIGURATION ACTIVITY LOG =====================
    
    /**
     * Konfigurasi Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'username', 'is_active', 'phone', 'position', 'department'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "User {$this->name} telah {$eventName}");
    }

    // ===================== RELATIONSHIPS =====================
    
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'causer');
    }

    // ===================== KURMIGO RELATIONSHIPS =====================
    
    /**
     * Relasi ke Wallet (satu user punya satu wallet)
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Relasi ke profil pelanggan.
     */
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Relasi ke Orders (satu user bisa punya banyak order)
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relasi ke Deliveries sebagai kurir (satu kurir bisa handle banyak delivery)
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'kurir_id');
    }

    /**
     * Relasi ke Orders yang sedang aktif (belum delivered/cancelled)
     */
    public function activeOrders()
    {
        return $this->orders()->whereNotIn('status', ['delivered', 'cancelled']);
    }

    // ===================== KURMIGO HELPER METHODS =====================
    
    /**
     * Inisialisasi wallet untuk user baru
     */
    public function initWallet()
    {
        if (!$this->wallet) {
            return $this->wallet()->create(['balance' => 0]);
        }
        return $this->wallet;
    }

    /**
     * Cek apakah user adalah customer
     */
    public function isCustomer()
    {
        return $this->hasRole('customer');
    }

    /**
     * Cek apakah user adalah kurir
     */
    public function isKurir()
    {
        return $this->hasRole('kurir');
    }

    /**
     * Cek apakah user adalah operator (tim belanja & repack)
     */
    public function isOperator()
    {
        return $this->hasRole('operator');
    }

    /**
     * Get total belanja customer (total dari semua order yang sudah delivered)
     */
    public function getTotalSpentAttribute()
    {
        return $this->orders()
            ->where('status', 'delivered')
            ->sum('total');
    }

    /**
     * Get jumlah order customer
     */
    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    /**
     * Get saldo wallet customer
     */
    public function getWalletBalanceAttribute()
    {
        return $this->wallet ? $this->wallet->balance : 0;
    }

    // ===================== ACCESSORS =====================
    
    public function getPhotoUrlAttribute()
    {
        if ($this->photo && file_exists(public_path('storage/' . $this->photo))) {
            return asset('storage/' . $this->photo);
        }
        
        // Default avatar based on gender
        if ($this->gender == 'female') {
            return asset('images/default-avatar-female.png');
        }
        
        return asset('images/default-avatar.png');
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getIsOnlineAttribute()
    {
        return $this->isOnline();
    }

    public function getRoleNamesAttribute()
    {
        return $this->roles->pluck('name')->toArray();
    }

    public function getRoleLabelsAttribute()
    {
        return $this->roles->pluck('name')->map(function($role) {
            return ucwords(str_replace('-', ' ', $role));
        })->implode(', ');
    }

    // ===================== MUTATORS =====================
    
    /**
     * Set password attribute - otomatis hash sesuai konfigurasi
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    // ===================== SCOPES =====================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByRole($query, $role)
    {
        return $query->whereHas('roles', function($q) use ($role) {
            $q->where('name', $role);
        });
    }

    public function scopeSales($query)
    {
        return $query->byRole('sales');
    }

    public function scopeOnline($query)
    {
        return $query->where('last_login_at', '>=', now()->subMinutes(5));
    }

    // ===================== KURMIGO SCOPES =====================
    
    /**
     * Scope untuk customer saja
     */
    public function scopeCustomers($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'customer');
        });
    }

    /**
     * Scope untuk kurir aktif
     */
    public function scopeActiveKurir($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'kurir');
        })->active();
    }

    // ===================== METHODS =====================
    
    public function isAdmin()
    {
        return $this->hasRole('admin') || $this->hasRole('super-admin');
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('super-admin');
    }

    public function isSales()
    {
        return $this->hasRole('sales');
    }

    public function isManager()
    {
        return $this->hasRole('manager');
    }

    public function isWarehouse()
    {
        return $this->hasRole('warehouse');
    }

    public function isFinance()
    {
        return $this->hasRole('finance');
    }

    public function canImpersonate()
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function canBeImpersonated()
    {
        return !$this->isSuperAdmin(); // Super admin tidak bisa di-impersonate
    }

    public function recordLogin($request)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        return $this->loginHistories()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->getDeviceType($request->userAgent()),
            'platform' => $this->getPlatform($request->userAgent()),
            'browser' => $this->getBrowser($request->userAgent()),
            'login_at' => now()
        ]);
    }

    public function recordLogout()
    {
        $lastLogin = $this->loginHistories()
            ->whereNull('logout_at')
            ->latest()
            ->first();

        if ($lastLogin) {
            $lastLogin->update(['logout_at' => now()]);
        }
    }

    protected function getDeviceType($userAgent)
    {
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/(mobile|iphone|ipod|android|blackberry|windows phone)/i', $userAgent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }

    protected function getPlatform($userAgent)
    {
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'MacOS';
        if (strpos($userAgent, 'Linux') !== false) return 'Linux';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false) return 'iOS';
        
        return 'Unknown';
    }

    protected function getBrowser($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) return 'Safari';
        if (strpos($userAgent, 'Edg') !== false) return 'Edge';
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'Internet Explorer';
        
        return 'Unknown';
    }
}
