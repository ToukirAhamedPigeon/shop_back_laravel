<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentMail extends Model
{
    protected $table = 'mail';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false; // only created_at, no updated_at

    protected $fillable = [
        'from_mail',
        'to_mail',
        'subject',
        'body',
        'module_name',
        'purpose',
        'attachments',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'attachments' => 'array', // JSONB → array
        'created_at' => 'datetime',
    ];

    /**
     * Created By User (UUID)
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(EloquentUser::class, 'created_by', 'id');
    }
}
