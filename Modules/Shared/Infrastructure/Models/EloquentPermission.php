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
        return $this->hasMany(EloquentRolePermission::class, 'permission_id', 'id');
    }

    public function modelPermissions(): HasMany
    {
        return $this->hasMany(EloquentModelPermission::class, 'permission_id', 'id');
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
