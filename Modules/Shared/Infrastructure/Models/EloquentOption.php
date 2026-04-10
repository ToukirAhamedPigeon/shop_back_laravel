<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentOption extends Model
{
    use SoftDeletes;

    protected $table = 'options';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'parent_id',
        'has_child',
        'is_active',
        'is_deleted',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'id' => 'string',
        'parent_id' => 'string',
        'has_child' => 'boolean',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(EloquentOption::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EloquentOption::class, 'parent_id', 'id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'created_by', 'id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'updated_by', 'id');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'deleted_by', 'id');
    }
}
