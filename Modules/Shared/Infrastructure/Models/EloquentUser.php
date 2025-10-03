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
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id', 'name', 'username', 'email', 'password', 'mobile_no', 'is_active', 'is_deleted','remember_token','email_verified_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * User has many refresh tokens
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(EloquentRefreshToken::class, 'user_id', 'id');
    }

   public function roles(string $modelName = 'User'): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentRole::class,
            'model_roles',
            'model_id',
            'role_id'
        )
        ->with('permissions')
        ->wherePivot('model_name', $modelName);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentPermission::class,
            'model_permissions',
            'model_id',
            'permission_id'
        )
        ->wherePivot('model_name', 'User'); // model_name filter
    }

    /**
     * Helper to get all user permissions from roles
     */
    public function getAllPermissionsAttribute(): array
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            foreach ($role->permissions as $perm) {
                $permissions[$perm->name] = $perm->name;
            }
        }

        return array_values($permissions);
    }
}
