<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentOtp extends Model
{
    protected $table = 'otp';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int'; // BIGINT UNSIGNED in DB
    public $timestamps = true;

    protected $fillable = [
        'email',
        'code_hash',
        'purpose',
        'expires_at',
        'used',
        'attempts',
        'user_id',
    ];

    protected $casts = [
        'used' => 'boolean',
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'user_id', 'id');
    }
}
