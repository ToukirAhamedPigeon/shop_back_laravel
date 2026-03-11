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
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'token',
        'expires_at',
        'is_revoked',
        'user_id',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean',
        'user_id' => 'string',
        'updated_by' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id', 'id');
    }
}
