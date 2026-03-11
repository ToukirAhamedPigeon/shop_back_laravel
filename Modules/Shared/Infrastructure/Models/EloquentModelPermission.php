<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentModelPermission extends Model
{
    protected $table = 'model_permissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'model_id',
        'permission_id',
        'model_name',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'model_id' => 'string',
        'permission_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['permission'];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(EloquentPermission::class, 'permission_id', 'id');
    }
}
