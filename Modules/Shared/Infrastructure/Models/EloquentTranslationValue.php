<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentTranslationValue extends Model
{
    protected $table = 'translation_values';
    public $timestamps = false;

    protected $fillable = [
        'id', 'key_id', 'lang', 'value', 'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function key(): BelongsTo
    {
        return $this->belongsTo(EloquentTranslationKey::class, 'key_id', 'id');
    }
}
