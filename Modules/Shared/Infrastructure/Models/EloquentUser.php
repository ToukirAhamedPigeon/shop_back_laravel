<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Passport\HasApiTokens;

class EloquentUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'username',
        'email',
        'password',
        'profile_image',
        'bio',
        'date_of_birth',
        'gender',
        'address',
        'mobile_no',
        'email_verified_at',
        'qr_code',
        'remember_token',
        'last_login_at',
        'last_login_ip',
        'timezone',
        'language',
        'nid',
        'is_active',
        'is_deleted',
        'deleted_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'date_of_birth' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(EloquentRefreshToken::class, 'user_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentRole::class,
            'model_roles',
            'model_id',
            'role_id'
        )->withPivot('model_name')
         ->withTimestamps()
         ->wherePivot('model_name', 'User');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentPermission::class,
            'model_permissions',
            'model_id',
            'permission_id'
        )->withPivot('model_name')
         ->withTimestamps()
         ->wherePivot('model_name', 'User');
    }
}
