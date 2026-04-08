<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentTranslationKey extends Model
{
    protected $table = 'translation_keys';
    public $timestamps = false;

    protected $fillable = [
        'id', 'key', 'module', 'created_at', 'updated_at', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(EloquentTranslationValue::class, 'key_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'updated_by', 'id');
    }
}
