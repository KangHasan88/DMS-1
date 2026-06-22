<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasModuleTenant extends Model
{
    protected $fillable = [
        'tenant_id',
        'tenant_module_id',
        'module_key',
        'operation_id',
        'status',
        'metadata',
        'provisioned_at',
        'last_launch_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'provisioned_at' => 'datetime',
        'last_launch_at' => 'datetime',
    ];
}
