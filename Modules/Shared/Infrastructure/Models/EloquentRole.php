<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EloquentRole extends Model
{
    protected $table = 'roles';
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
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
        )->withTimestamps();
    }

    public function createdByUser()
    {
        return $this->belongsTo(EloquentUser::class, 'created_by', 'id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(EloquentUser::class, 'updated_by', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeOnlyDeleted($query)
    {
        return $query->where('is_deleted', true);
    }
}
