<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentUserLog extends Model
{
    protected $table = 'user_logs';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'detail', 'changes', 'action_type', 'model_name', 'model_id', 'created_by', 'created_at', 'created_at_id'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];
}
