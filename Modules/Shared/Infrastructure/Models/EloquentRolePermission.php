<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentRolePermission extends Model
{
    protected $table = 'role_permissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'permission_id',
        'role_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'permission_id' => 'string',
        'role_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['permission', 'role'];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(EloquentPermission::class, 'permission_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(EloquentRole::class, 'role_id', 'id');
    }
}
