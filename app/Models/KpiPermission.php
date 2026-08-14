<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiPermission extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'kpi_permissions';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'role',
        'can_create',
        'can_view',
        'can_retrieve',
        'can_update',
        'can_delete',
        'scope',
    ];

    protected function casts(): array
    {
        return [
            'can_create' => 'boolean',
            'can_view' => 'boolean',
            'can_retrieve' => 'boolean',
            'can_update' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }
}
