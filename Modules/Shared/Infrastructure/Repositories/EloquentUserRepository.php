<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Domain\Entities\User as UserEntity;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use DateTimeImmutable;

class EloquentUserRepository implements IUserRepository
{
    public function getByIdentifier(string $identifier): ?UserEntity
    {
        $model = EloquentUser::with([
            'roles.permissions',
            'permissions',
        ])
        ->where('is_deleted', false)
        ->where(function($q) use ($identifier) {
            $q->where('username', $identifier)
              ->orWhere('email', $identifier)
              ->orWhere('mobile_no', $identifier);
        })
        ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function findById(string $id): ?UserEntity
    {
        $model = EloquentUser::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $model = EloquentUser::where('email', $email)->first();
        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByUsername(string $username): ?UserEntity
    {
        $model = EloquentUser::where('username', $username)->first();
        return $model ? $this->mapToEntity($model) : null;
    }

    public function create(UserEntity $user): UserEntity
    {
        $model = new EloquentUser();
        $model->id = $user->id;
        $model->name = $user->name;
        $model->username = $user->username;
        $model->email = $user->email;
        $model->password = $user->password;
        $model->mobile_no = $user->mobileNo;
        $model->is_active = $user->isActive;
        $model->is_deleted = $user->isDeleted;
        $model->created_at = $user->createdAt?->format('Y-m-d H:i:s');
        $model->updated_at = $user->updatedAt?->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapToEntity($model);
    }

    public function update(UserEntity $user): UserEntity
    {
        $model = EloquentUser::findOrFail($user->id);
        $model->name = $user->name;
        $model->username = $user->username;
        $model->email = $user->email;
        $model->password = $user->password;
        $model->mobile_no = $user->mobileNo;
        $model->is_active = $user->isActive;
        $model->is_deleted = $user->isDeleted;
        $model->updated_at = $user->updatedAt?->format('Y-m-d H:i:s');
        $model->save();

        return $this->mapToEntity($model);
    }

    public function delete(UserEntity $user): void
    {
        $model = EloquentUser::findOrFail($user->id);
        $model->delete();
    }

    /**
     * Create Passport access token (short-lived)
     */
    public function createAccessToken(UserEntity $user, string $tokenName = 'API Token'): string
    {
        $model = EloquentUser::findOrFail($user->id);
        $token = $model->createToken($tokenName)->accessToken; // Passport access token
        return $token;
    }

    public function revokeAllAccessTokens(string $userId): void
    {
        $model = EloquentUser::findOrFail($userId);
        $model->tokens()->delete();
    }

    public function revokeOtherAccessTokens(string $userId, string $exceptTokenId): void
    {
        $model = EloquentUser::findOrFail($userId);
        $model->tokens()->where('id', '!=', $exceptTokenId)->delete();
    }

    private function mapToEntity(EloquentUser $model): UserEntity
    {
        $roles = $model->roles->pluck('name')->toArray();

        $permissions = [];
        foreach ($model->roles as $role) {
            foreach ($role->permissions as $perm) {
                $permissions[$perm->name] = $perm->name;
            }
        }

        if ($model->relationLoaded('permissions')) {
            foreach ($model->permissions as $perm) {
                $permissions[$perm->name] = $perm->name;
            }
        }

        return new UserEntity(
            $model->id,
            $model->name,
            $model->username,
            $model->email,
            $model->password,
            $model->mobile_no,
            $model->is_active,
            $model->is_deleted,
            $model->created_at ? new DateTimeImmutable($model->created_at) : null,
            $model->updated_at ? new DateTimeImmutable($model->updated_at) : null,
            [], // refreshTokens
            $roles,
            array_values($permissions)
        );
    }
}
