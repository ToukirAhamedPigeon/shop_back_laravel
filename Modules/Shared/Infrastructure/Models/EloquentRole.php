<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EloquentRole extends Model
{
    protected $table = 'roles';
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
        return $this->hasMany(EloquentRolePermission::class, 'role_id', 'id');
    }

    public function modelRoles(): HasMany
    {
        return $this->hasMany(EloquentModelRole::class, 'role_id', 'id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentPermission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }
}
