<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentOtp extends Model
{
    protected $table = 'otp';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'email',
        'code_hash',
        'purpose',
        'expires_at',
        'used',
        'attempts',
        'user_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * OTP belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id', 'id');
    }

    /**
     * Mark OTP as used.
     */
    public function markUsed(): void
    {
        $this->used = true;
        $this->save();
    }

    /**
     * Increment OTP attempt count.
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
        $this->save();
    }

    /**
     * Check if the OTP is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
