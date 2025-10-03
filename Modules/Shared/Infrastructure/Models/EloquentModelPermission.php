<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentModelPermission extends Model
{
    protected $table = 'model_permissions';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id', 'model_id', 'permission_id', 'model_name'
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(EloquentPermission::class, 'permission_id', 'id');
    }
}
