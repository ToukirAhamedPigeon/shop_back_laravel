<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentPermission extends Model
{
    protected $table = 'permissions';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id', 'name', 'guard_name', 'is_active', 'is_deleted'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(EloquentRolePermission::class, 'permission_id', 'id');
    }

    public function modelPermissions(): HasMany
    {
        return $this->hasMany(EloquentModelPermission::class, 'permission_id', 'id');
    }
}
