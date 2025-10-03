<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentRolePermission extends Model
{
    protected $table = 'role_permissions';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id', 'permission_id', 'role_id'
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(EloquentPermission::class, 'permission_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(EloquentRole::class, 'role_id', 'id');
    }
}
