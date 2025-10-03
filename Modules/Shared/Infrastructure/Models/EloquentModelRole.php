<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentModelRole extends Model
{
    protected $table = 'model_roles';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id', 'model_id', 'role_id', 'model_name'
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(EloquentRole::class, 'role_id', 'id');
    }
}
