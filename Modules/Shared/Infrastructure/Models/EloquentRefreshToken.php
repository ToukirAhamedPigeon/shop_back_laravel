<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentRefreshToken extends Model
{
    protected $table = 'refresh_tokens';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


    protected $fillable = [
        'id',
        'token',
        'expires_at',
        'is_revoked',
        'user_id',
        'updated_by',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean',
        'user_id' => 'string',
        'updated_by' => 'string',
        'updated_at' => 'datetime',
    ];

    protected $with = ['user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id', 'id');
    }
}
