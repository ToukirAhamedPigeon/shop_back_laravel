<?php

namespace Modules\Shared\Infrastructure\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class EloquentUser extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $table = 'users';
    protected $keyType = 'string';
    public $incrementing = false;
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

        'is_active',
        'is_deleted',
        'deleted_at',

        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',

        'date_of_birth' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'deleted_at' => 'datetime',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
    /**
     * Check if user has ANY of the given permissions
    */
    public function hasAnyPermission(array $permissions): bool
    {
        // Direct permissions
        if ($this->permissions()
            ->whereIn('name', $permissions)
            ->exists()
        ) {
            return true;
        }

        // Role-based permissions
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if (in_array($permission->name, $permissions, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has ALL of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = [];

        // Direct permissions
        foreach ($this->permissions as $permission) {
            $userPermissions[$permission->name] = true;
        }

        // Role permissions
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $userPermissions[$permission->name] = true;
            }
        }

        foreach ($permissions as $required) {
            if (!isset($userPermissions[$required])) {
                return false;
            }
        }
        return true;
    }

}
