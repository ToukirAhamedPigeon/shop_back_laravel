<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentRefreshToken extends Model
{
    protected $table = 'refresh_tokens';
    protected $keyType = 'string';
    public $incrementing = false;

    // Disable default timestamps since we don't want created_at
    public $timestamps = true;
    const CREATED_AT = null; // Disable created_at
    const UPDATED_AT = 'updated_at'; // Keep updated_at

    protected $fillable = [
        'id', 'token', 'expires_at', 'is_revoked', 'user_id', 'updated_by', 'updated_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id', 'id');
    }
}
