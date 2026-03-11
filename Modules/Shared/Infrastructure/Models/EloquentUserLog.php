<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentUserLog extends Model
{
    protected $table = 'user_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'detail',
        'changes',
        'action_type',
        'model_name',
        'model_id',
        'created_by',
        'created_at',
        'created_at_id',
        'ip_address',
        'browser',
        'device',
        'os',
        'user_agent',
    ];

    protected $casts = [
        'id' => 'string',
        'changes' => 'array',
        'model_id' => 'string',
        'created_by' => 'string',
        'created_at_id' => 'integer',
        'created_at' => 'datetime',
    ];
}
