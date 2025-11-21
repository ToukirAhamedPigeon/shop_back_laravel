<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentPasswordReset extends Model
{
    protected $table = 'password_reset';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int'; // BIGINT UNSIGNED
    public $timestamps = false;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id', 'id');
    }
}
