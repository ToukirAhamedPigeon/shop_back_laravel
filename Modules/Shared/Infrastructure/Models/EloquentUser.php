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

    /**
     * Get the refresh tokens for the user.
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(EloquentRefreshToken::class, 'user_id', 'id');
    }

    /**
     * Get the roles for the user through model_roles table.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentRole::class,
            'model_roles',
            'model_id',
            'role_id'
        )
        ->withPivot('model_name')
        ->withTimestamps()
        ->wherePivot('model_name', 'User');
    }

    /**
     * Get the direct permissions for the user through model_permissions table.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentPermission::class,
            'model_permissions',
            'model_id',
            'permission_id'
        )
        ->withPivot('model_name')
        ->withTimestamps()
        ->wherePivot('model_name', 'User');
    }

    /**
     * Get all permissions for the user (from roles + direct).
     */
    public function getAllPermissionsAttribute(): array
    {
        $permissions = [];

        // Get permissions from roles
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[$permission->name] = $permission->name;
            }
        }

        // Get direct permissions
        foreach ($this->permissions as $permission) {
            $permissions[$permission->name] = $permission->name;
        }

        return array_values($permissions);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermissionTo(string $permission): bool
    {
        // Check direct permissions
        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Check permissions through roles
        foreach ($this->roles as $role) {
            if ($role->permissions()->where('name', $permission)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermissionTo($permission)) {
                return false;
            }
        }
        return true;
    }
}
