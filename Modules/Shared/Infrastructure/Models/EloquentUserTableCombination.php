<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentUserTableCombination extends Model
{
    protected $table = 'user_table_combinations';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'table_id', 'show_column_combinations', 'user_id', 'updated_by', 'updated_at'
    ];

    protected $casts = [
        'show_column_combinations' => 'array',
        'updated_at' => 'datetime',
    ];
}
