<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentTranslationKey extends Model
{
    protected $table = 'translation_keys';
    public $timestamps = false;

    protected $fillable = [
        'id', 'key', 'module', 'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function values(): HasMany
    {
        return $this->hasMany(EloquentTranslationValue::class, 'key_id', 'id');
    }
}
