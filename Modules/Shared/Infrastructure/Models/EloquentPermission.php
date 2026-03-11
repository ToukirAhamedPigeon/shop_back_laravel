<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentPermission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'guard_name',
        'is_active',
        'is_deleted',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
