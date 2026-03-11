<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentModelRole extends Model
{
    protected $table = 'model_roles';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'model_id',
        'role_id',
        'model_name',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'model_id' => 'string',
        'role_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['role'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(EloquentRole::class, 'role_id', 'id');
    }
}
