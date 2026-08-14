<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'roles';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'department_id',
        'label',
        'rank',
        'is_department_admin',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'is_department_admin' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
