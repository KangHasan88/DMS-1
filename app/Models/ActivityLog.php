<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'batch_uuid',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    protected $casts = [
        'properties' => 'collection',
        'created_at' => 'datetime',
    ];

    public static function record(
        string $logName,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = []
    ): self {
        $user = auth()->user();
        $request = request();

        return self::create([
            'log_name' => $logName,
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->getKey(),
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Get the subject of the activity (polymorphic).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who caused the activity.
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Scope untuk filter berdasarkan log name
     */
    public function scopeInLog($query, ...$logNames)
    {
        return $query->whereIn('log_name', $logNames);
    }

    /**
     * Scope untuk filter berdasarkan causer (user)
     */
    public function scopeCausedBy($query, $causer)
    {
        return $query->where('causer_id', $causer->id)
                     ->where('causer_type', get_class($causer));
    }

    /**
     * Scope untuk filter berdasarkan subject
     */
    public function scopeForSubject($query, $subject)
    {
        return $query->where('subject_id', $subject->id)
                     ->where('subject_type', get_class($subject));
    }

    /**
     * Get formatted created at
     */
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('d M Y H:i:s');
    }

    /**
     * Get properties as array
     */
    public function getPropertiesArrayAttribute()
    {
        return $this->properties ? $this->properties->toArray() : [];
    }
}
