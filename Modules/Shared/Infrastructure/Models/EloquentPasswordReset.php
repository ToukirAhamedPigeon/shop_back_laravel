<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentPasswordReset extends Model
{
    protected $table = 'password_reset';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // No updated_at column

    protected $fillable = [
        'token',
        'user_id',
        'expires_at',
        'used',
        'created_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * PasswordReset belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id', 'id');
    }

    /**
     * Mark reset token as used.
     */
    public function markUsed(): void
    {
        $this->used = true;
        $this->save();
    }

    /**
     * Check if reset token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
