<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Application\Requests\User\UserFilterRequest;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Modules\Shared\Domain\Entities\User as UserEntity;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Modules\Shared\Infrastructure\Models\EloquentUserLog;
use Modules\Shared\Infrastructure\Models\EloquentRefreshToken;
use Modules\Shared\Infrastructure\Models\EloquentPasswordReset;
use Modules\Shared\Application\Resources\User\UserResource;
use Modules\Shared\Application\Resources\Common\SelectOptionResource;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class EloquentUserRepository implements IUserRepository
{
    private IRolePermissionRepository $rolePermissionRepo;

    public function __construct(IRolePermissionRepository $rolePermissionRepo)
    {
        $this->rolePermissionRepo = $rolePermissionRepo;
    }

    // ==================== EXISTENCE CHECKS ====================

    public function existsByUsername(string $username, ?string $ignoreId = null): bool
    {
        $query = EloquentUser::where('username', $username)
            ->where('is_deleted', false); // This is boolean false, not string

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function existsByEmail(string $email, ?string $ignoreId = null): bool
    {
        $query = EloquentUser::where('email', $email)
            ->where('is_deleted', false); // This is boolean false, not string

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function existsByMobileNo(string $mobileNo, ?string $ignoreId = null): bool
    {
        if (empty($mobileNo)) return false;

        $query = EloquentUser::where('mobile_no', $mobileNo)
            ->where('is_deleted', false); // This is boolean false, not string

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function existsByNID(string $nid, ?string $ignoreId = null): bool
    {
        if (empty($nid)) return false;

        $query = EloquentUser::where('nid', $nid)
            ->where('is_deleted', false); // This is boolean false, not string

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    // ==================== FIND METHODS ====================

    public function getByIdentifier(string $identifier): ?UserEntity
    {
        // Add debug logging
        Log::info('Searching for user with identifier: ' . $identifier);

        $model = EloquentUser::with(['roles.permissions', 'permissions'])
            ->where('is_deleted', false)
            ->where(function($q) use ($identifier) {
                $q->where('username', $identifier)
                ->orWhere('email', $identifier)
                ->orWhere('mobile_no', $identifier);
            })
            ->first();

        if ($model) {
            Log::info('User found: ' . $model->username);
        } else {
            Log::info('User NOT found with identifier: ' . $identifier);
        }

        return $model ? $this->mapToEntity($model) : null;
    }

    public function getByIdentifierAsync(string $identifier): ?UserEntity
    {
        return $this->getByIdentifier($identifier);
    }

    public function getByEmail(string $email): ?UserEntity
    {
        $model = EloquentUser::where('email', $email)
            ->where('is_deleted', false)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function getByEmailAsync(string $email): ?UserEntity
    {
        return $this->getByEmail($email);
    }

    public function getById(string $id): ?UserEntity
    {
        $model = EloquentUser::with(['roles.permissions', 'permissions'])->find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function getByIdAsync(string $id): ?UserEntity
    {
        return $this->getById($id);
    }

    public function getByMobileNo(string $mobileNo): ?UserEntity
    {
        $model = EloquentUser::where('mobile_no', $mobileNo)
            ->where('is_deleted', false)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function getByMobileNoAsync(string $mobileNo): ?UserEntity
    {
        return $this->getByMobileNo($mobileNo);
    }

    // ==================== FILTERED LIST ====================

    public function getFiltered(UserFilterRequest $req): array
    {
        // Start with base query based on IsDeleted filter
        $baseQuery = $this->buildBaseQuery($req->getIsDeleted());

        // Apply IsActive filter
        if ($req->getIsActive() !== null) {
            $baseQuery->where('is_active', $req->getIsActive());
        }

        // Apply search (Q)
        if (!empty($req->q)) {
            $this->applySearchFilter($baseQuery, $req->q);
        }

        // Apply gender filter
        if (!empty($req->gender)) {
            $baseQuery->whereIn('gender', $req->gender);
        }

        // Apply created by filter
        if (!empty($req->createdBy)) {
            $baseQuery->whereIn('created_by', $req->createdBy);
        }

        // Apply updated by filter
        if (!empty($req->updatedBy)) {
            $baseQuery->whereIn('updated_by', $req->updatedBy);
        }

        // Apply roles filter
        if (!empty($req->roles)) {
            $this->applyRolesFilter($baseQuery, $req->roles);
        }

        // Apply permissions filter
        if (!empty($req->permissions)) {
            $this->applyPermissionsFilter($baseQuery, $req->permissions);
        }

        // Apply date range filters
        if (!empty($req->dateType) && ($req->from || $req->to)) {
            $this->applyDateFilters($baseQuery, $req);
        }

        // Get counts
        $totalCount = $baseQuery->count();
        $grandTotalCount = EloquentUser::withoutGlobalScopes()->count();

        // Apply sorting
        $query = $this->applySorting($baseQuery, $req->sortBy, $req->sortOrder);

        // Apply pagination
        $users = $query
            ->skip(($req->page - 1) * $req->limit)
            ->take($req->limit)
            ->get();

        // Get creator and updater names
        $creatorIds = $users->pluck('created_by')->filter()->unique()->toArray();
        $updaterIds = $users->pluck('updated_by')->filter()->unique()->toArray();
        $userIds = array_unique(array_merge($creatorIds, $updaterIds));

        $userNames = [];
        if (!empty($userIds)) {
            $userNames = EloquentUser::withoutGlobalScopes()
                ->whereIn('id', $userIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        // Build result with roles and permissions
        $result = [];
        foreach ($users as $user) {
            $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);
            $permissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);

            // Create entity with roles and permissions
            $entity = new UserEntity(
                id: $user->id,
                name: $user->name,
                username: $user->username,
                email: $user->email,
                password: $user->password,
                profileImage: $user->profile_image,
                bio: $user->bio,
                dateOfBirth: $user->date_of_birth ? new DateTimeImmutable($user->date_of_birth) : null,
                gender: $user->gender,
                address: $user->address,
                mobileNo: $user->mobile_no,
                emailVerifiedAt: $user->email_verified_at ? new DateTimeImmutable($user->email_verified_at) : null,
                qrCode: $user->qr_code,
                rememberToken: $user->remember_token,
                lastLoginAt: $user->last_login_at ? new DateTimeImmutable($user->last_login_at) : null,
                lastLoginIp: $user->last_login_ip,
                timezone: $user->timezone,
                language: $user->language,
                nid: $user->nid,
                isActive: $user->is_active,
                isDeleted: $user->is_deleted,
                deletedAt: $user->deleted_at ? new DateTimeImmutable($user->deleted_at) : null,
                createdAt: new DateTimeImmutable($user->created_at),
                updatedAt: new DateTimeImmutable($user->updated_at),
                createdBy: $user->created_by,
                updatedBy: $user->updated_by,
                refreshTokens: [],
                roles: $roles,
                permissions: $permissions
            );

            $result[] = new UserResource(
                $entity,
                $userNames[$user->created_by] ?? null,
                $userNames[$user->updated_by] ?? null
            );
        }

        return [
            'users' => array_map(fn($r) => $r->toArray(), $result),
            'totalCount' => $totalCount,
            'grandTotalCount' => $grandTotalCount,
            'pageIndex' => $req->page - 1,
            'pageSize' => $req->limit,
        ];
    }

    public function getFilteredAsync(UserFilterRequest $req): array
    {
        return $this->getFiltered($req);
    }

    // ==================== CRUD OPERATIONS ====================

    public function add(UserEntity $user): UserEntity
    {
        $model = new EloquentUser();
        $this->mapToModel($user, $model);
        $model->save();

        // Reload with relationships
        $model->load(['roles.permissions', 'permissions']);
        return $this->mapToEntity($model);
    }

    public function addAsync(UserEntity $user): UserEntity
    {
        return $this->add($user);
    }

    public function update(UserEntity $user): UserEntity
    {
        $model = EloquentUser::findOrFail($user->id);
        $this->mapToModel($user, $model);
        $model->save();

        // Reload with relationships
        $model->load(['roles.permissions', 'permissions']);
        return $this->mapToEntity($model);
    }

    public function updateAsync(UserEntity $user): UserEntity
    {
        return $this->update($user);
    }

    public function saveChanges(): void
    {
        // In Laravel, saves are auto, kept for interface compatibility
        return;
    }

    public function saveChangesAsync(): void
    {
        $this->saveChanges();
    }

    // ==================== ACCESS TOKEN MANAGEMENT ====================

    public function createAccessToken(UserEntity $user, string $tokenName = 'API Token'): string
    {
        $model = EloquentUser::findOrFail($user->id);
        return $model->createToken($tokenName)->accessToken;
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

    // ==================== RELATED RECORDS CHECK ====================

    public function hasRelatedRecords(string $userId): bool
    {
        $hasUserLogs = EloquentUserLog::where('created_by', $userId)->exists();
        $hasRefreshTokens = EloquentRefreshToken::where('user_id', $userId)->exists();
        $hasPasswordResets = EloquentPasswordReset::where('user_id', $userId)->exists();
        // $hasMail = DB::table('mail')->where('created_by', $userId)->exists();
        // $hasMailVerifications = DB::table('mail_verifications')->where('user_id', $userId)->exists();

        Log::info('Checking related records for user', [
            'user_id' => $userId,
            'hasUserLogs' => $hasUserLogs,
            'hasRefreshTokens' => $hasRefreshTokens,
            'hasPasswordResets' => $hasPasswordResets,
            // 'hasMail' => $hasMail,
            // 'hasMailVerifications' => $hasMailVerifications
        ]);

        return $hasUserLogs || $hasRefreshTokens || $hasPasswordResets;
    }

    public function hasVerifiedEmail(string $userId): bool
    {
        return EloquentUser::where('id', $userId)
            ->whereNotNull('email_verified_at')
            ->exists();
    }

    // ==================== DELETE OPERATIONS ====================

    private function deleteUserProfileImage(?string $profileImage): void
    {
        if (empty($profileImage)) return;

        try {
            $relativePath = ltrim($profileImage, '/');
            $possiblePaths = [
                public_path($relativePath),
                public_path('uploads/users/' . basename($profileImage)),
                public_path(str_replace('/', DIRECTORY_SEPARATOR, $relativePath))
            ];

            foreach ($possiblePaths as $path) {
                if (File::exists($path)) {
                    File::delete($path);
                    return;
                }
            }
        } catch (\Exception $e) {
            // Log error but don't throw
        }
    }

    public function hardDelete(string $userId): void
    {
        $user = EloquentUser::withoutGlobalScopes()->find($userId);
        if (!$user) return;

        // Delete profile image
        $this->deleteUserProfileImage($user->profile_image);

        // Start a transaction to ensure all deletions succeed or fail together
        DB::beginTransaction();

        try {
            // Delete related records in the correct order to avoid foreign key constraints

            // 1. Delete mail records where user is the creator
            DB::table('mail')
                ->where('created_by', $userId)
                ->delete();

            // 2. Delete mail_verifications
            DB::table('mail_verifications')
                ->where('user_id', $userId)
                ->delete();

            // 3. Delete model_roles
            DB::table('model_roles')
                ->where('model_id', $userId)
                ->where('model_name', 'User')
                ->delete();

            // 4. Delete model_permissions
            DB::table('model_permissions')
                ->where('model_id', $userId)
                ->where('model_name', 'User')
                ->delete();

            // 5. Delete password_reset records
            DB::table('password_reset')
                ->where('user_id', $userId)
                ->delete();

            // 6. Delete refresh_tokens
            DB::table('refresh_tokens')
                ->where('user_id', $userId)
                ->delete();

            // 7. Delete user_logs
            DB::table('user_logs')
                ->where('created_by', $userId)
                ->delete();

            // 8. Finally delete the user
            $user->forceDelete();

            DB::commit();

            Log::info('User permanently deleted with all related records', ['user_id' => $userId]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during hard delete', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function softDelete(string $userId, ?string $deletedBy = null): void
    {
        $user = EloquentUser::find($userId);
        if ($user) {
            $user->is_deleted = true;
            $user->deleted_at = now();
            $user->updated_by = $deletedBy;
            $user->updated_at = now();
            $user->save();
        }
    }

    public function restore(string $userId, ?string $restoredBy = null): void
    {
        $user = EloquentUser::withoutGlobalScopes()->find($userId);
        if ($user) {
            $user->is_deleted = false;
            $user->deleted_at = null;
            $user->updated_by = $restoredBy;
            $user->updated_at = now();
            $user->save();
        }
    }

    // ==================== SELECT OPTIONS ====================

    public function getDistinctCreators(SelectOptionRequest $req): array
    {
        $creatorIds = EloquentUser::withoutGlobalScopes()
            ->whereNotNull('created_by')
            ->pluck('created_by')
            ->unique()
            ->toArray();

        if (empty($creatorIds)) {
            return [];
        }

        $query = EloquentUser::withoutGlobalScopes()
            ->whereIn('id', $creatorIds)
            ->select('id as value', 'name as label');

        // Apply search
        if (!empty($req->search)) {
            $query->where('name', 'like', "%{$req->search}%");
        }

        // Add deleted marker
        $users = $query->get()->map(function($user) {
            $label = $user->label;
            if (EloquentUser::withoutGlobalScopes()->where('id', $user->value)->value('is_deleted')) {
                $label .= ' (Deleted)';
            }
            return new SelectOptionResource($user->value, $label);
        });

        // Apply sorting
        $users = $users->sortBy('label', SORT_NATURAL, $req->sortOrder === 'desc');

        // Apply pagination
        $users = $users->slice($req->skip, $req->limit)->values();

        return array_map(fn($item) => $item->toArray(), $users->toArray());
    }

    public function getDistinctCreatorsAsync(SelectOptionRequest $req): array
    {
        return $this->getDistinctCreators($req);
    }

    public function getDistinctUpdaters(SelectOptionRequest $req): array
    {
        $query = DB::table('users as u1')
            ->join('users as u2', 'u1.updated_by', '=', 'u2.id')
            ->select('u2.id as value', 'u2.name as label')
            ->distinct();

        // Apply search
        if (!empty($req->search)) {
            $query->where('u2.name', 'like', "%{$req->search}%");
        }

        // Apply where filter
        if (!empty($req->where) && isset($req->where['UpdatedByName'])) {
            $query->where('u2.name', 'like', "%{$req->where['UpdatedByName']}%");
        }

        $users = $query
            ->orderBy('label', $req->sortOrder)
            ->skip($req->skip)
            ->take($req->limit)
            ->get()
            ->map(fn($item) => new SelectOptionResource($item->value, $item->label));

        return array_map(fn($item) => $item->toArray(), $users->toArray());
    }

    public function getDistinctUpdatersAsync(SelectOptionRequest $req): array
    {
        return $this->getDistinctUpdaters($req);
    }

    public function getDistinctDateTypes(SelectOptionRequest $req): array
    {
        $reflection = new ReflectionClass(EloquentUser::class);
        $properties = $reflection->getProperties();

        $dateTypes = [];
        foreach ($properties as $prop) {
            $type = $prop->getType();
            if ($type && in_array($type->getName(), ['datetime', 'date', 'carbon', 'DateTime'])) {
                $dateTypes[] = new SelectOptionResource($prop->getName(), $prop->getName());
            }
        }

        // Also check casts
        $model = new EloquentUser();
        foreach ($model->getCasts() as $field => $cast) {
            if (in_array($cast, ['datetime', 'date'])) {
                $dateTypes[] = new SelectOptionResource($field, $field);
            }
        }

        // Remove duplicates
        $unique = [];
        foreach ($dateTypes as $type) {
            $unique[$type->value] = $type;
        }
        $dateTypes = array_values($unique);

        // Apply sorting
        usort($dateTypes, fn($a, $b) => strcmp($a->label, $b->label));
        if ($req->sortOrder === 'desc') {
            $dateTypes = array_reverse($dateTypes);
        }

        // Apply pagination
        $dateTypes = array_slice($dateTypes, $req->skip, $req->limit);

        return array_map(fn($item) => $item->toArray(), $dateTypes);
    }

    public function getDistinctDateTypesAsync(SelectOptionRequest $req): array
    {
        return $this->getDistinctDateTypes($req);
    }

    // ==================== PRIVATE HELPER METHODS ====================

    private function buildBaseQuery(?bool $isDeleted)
    {
        if ($isDeleted === true) {
            // Show ONLY deleted users
            return EloquentUser::withoutGlobalScopes()
                ->where('is_deleted', true);
        } else {
            // Show ONLY non-deleted users (default)
            return EloquentUser::where('is_deleted', false);
        }
    }

    private function applySearchFilter($query, string $search): void
    {
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('mobile_no', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%")

              // Role search
              ->orWhereExists(function($sub) use ($search) {
                  $sub->select(DB::raw(1))
                      ->from('model_roles')
                      ->join('roles', 'model_roles.role_id', '=', 'roles.id')
                      ->whereColumn('model_roles.model_id', 'users.id')
                      ->where('model_roles.model_name', 'User')
                      ->where('roles.name', 'like', "%{$search}%");
              })

              // Permission search (direct or via roles)
              ->orWhereExists(function($sub) use ($search) {
                  $sub->select(DB::raw(1))
                      ->from('model_permissions')
                      ->join('permissions', 'model_permissions.permission_id', '=', 'permissions.id')
                      ->whereColumn('model_permissions.model_id', 'users.id')
                      ->where('model_permissions.model_name', 'User')
                      ->where('permissions.name', 'like', "%{$search}%");
              })
              ->orWhereExists(function($sub) use ($search) {
                  $sub->select(DB::raw(1))
                      ->from('role_permissions')
                      ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                      ->join('model_roles', function($join) {
                          $join->on('model_roles.role_id', '=', 'role_permissions.role_id')
                               ->where('model_roles.model_name', 'User');
                      })
                      ->whereColumn('model_roles.model_id', 'users.id')
                      ->where('permissions.name', 'like', "%{$search}%");
              });
        });
    }

    private function applyRolesFilter($query, array $roles): void
    {
        $query->whereExists(function($sub) use ($roles) {
            $sub->select(DB::raw(1))
                ->from('model_roles')
                ->join('roles', 'model_roles.role_id', '=', 'roles.id')
                ->whereColumn('model_roles.model_id', 'users.id')
                ->where('model_roles.model_name', 'User')
                ->whereIn('roles.name', $roles);
        });
    }

    private function applyPermissionsFilter($query, array $permissions): void
    {
        $query->where(function($q) use ($permissions) {
            // Direct user permissions
            $q->whereExists(function($sub) use ($permissions) {
                $sub->select(DB::raw(1))
                    ->from('model_permissions')
                    ->join('permissions', 'model_permissions.permission_id', '=', 'permissions.id')
                    ->whereColumn('model_permissions.model_id', 'users.id')
                    ->where('model_permissions.model_name', 'User')
                    ->whereIn('permissions.name', $permissions);
            })
            // Role based permissions
            ->orWhereExists(function($sub) use ($permissions) {
                $sub->select(DB::raw(1))
                    ->from('role_permissions')
                    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                    ->join('model_roles', function($join) {
                        $join->on('model_roles.role_id', '=', 'role_permissions.role_id')
                             ->where('model_roles.model_name', 'User');
                    })
                    ->whereColumn('model_roles.model_id', 'users.id')
                    ->whereIn('permissions.name', $permissions);
            });
        });
    }

    private function applyDateFilters($query, UserFilterRequest $req): void
    {
        $from = $req->from ? date('Y-m-d 00:00:00', strtotime($req->from)) : '1970-01-01 00:00:00';
        $to = $req->to ? date('Y-m-d 23:59:59', strtotime($req->to)) : '9999-12-31 23:59:59';

        $query->where(function($q) use ($req, $from, $to) {
            foreach ($req->dateType as $dateType) {
                switch (strtolower($dateType)) {
                    case 'createdat':
                    case 'created_at':
                        $q->orWhereBetween('created_at', [$from, $to]);
                        break;
                    case 'updatedat':
                    case 'updated_at':
                        $q->orWhereBetween('updated_at', [$from, $to]);
                        break;
                    case 'dateofbirth':
                    case 'date_of_birth':
                        $q->orWhereBetween('date_of_birth', [$from, $to]);
                        break;
                    case 'emailverifiedat':
                    case 'email_verified_at':
                        $q->orWhereBetween('email_verified_at', [$from, $to]);
                        break;
                    case 'lastloginat':
                    case 'last_login_at':
                        $q->orWhereBetween('last_login_at', [$from, $to]);
                        break;
                    case 'deletedat':
                    case 'deleted_at':
                        $q->orWhereBetween('deleted_at', [$from, $to]);
                        break;
                }
            }
        });
    }

    private function applySorting($query, ?string $sortBy, ?string $sortOrder)
    {
        $desc = strtolower($sortOrder ?? 'desc') === 'desc';
        $sortBy = strtolower($sortBy ?? 'createdat');

        switch ($sortBy) {
            case 'name':
                return $desc ? $query->orderByDesc('name') : $query->orderBy('name');
            case 'username':
                return $desc ? $query->orderByDesc('username') : $query->orderBy('username');
            case 'email':
                return $desc ? $query->orderByDesc('email') : $query->orderBy('email');
            case 'mobileno':
            case 'mobile_no':
                return $desc ? $query->orderByDesc('mobile_no') : $query->orderBy('mobile_no');
            case 'gender':
                return $desc ? $query->orderByDesc('gender') : $query->orderBy('gender');
            case 'address':
                return $desc ? $query->orderByDesc('address') : $query->orderBy('address');
            case 'timezone':
                return $desc ? $query->orderByDesc('timezone') : $query->orderBy('timezone');
            case 'nid':
                return $desc ? $query->orderByDesc('nid') : $query->orderBy('nid');
            case 'language':
                return $desc ? $query->orderByDesc('language') : $query->orderBy('language');
            case 'isactive':
            case 'is_active':
                return $desc ? $query->orderByDesc('is_active') : $query->orderBy('is_active');
            case 'createdat':
            case 'created_at':
                return $desc ? $query->orderByDesc('created_at') : $query->orderBy('created_at');
            case 'updatedat':
            case 'updated_at':
                return $desc ? $query->orderByDesc('updated_at') : $query->orderBy('updated_at');
            case 'dateofbirth':
            case 'date_of_birth':
                return $desc ? $query->orderByDesc('date_of_birth') : $query->orderBy('date_of_birth');
            case 'lastloginat':
            case 'last_login_at':
                return $desc ? $query->orderByDesc('last_login_at') : $query->orderBy('last_login_at');
            default:
                return $desc ? $query->orderByDesc('created_at') : $query->orderBy('created_at');
        }
    }

    private function mapToModel(UserEntity $entity, EloquentUser $model): void
    {
        $model->id = $entity->id;
        $model->name = $entity->name;
        $model->username = $entity->username;
        $model->email = $entity->email;
        $model->password = $entity->password;
        $model->profile_image = $entity->profileImage;
        $model->bio = $entity->bio;
        $model->date_of_birth = $entity->dateOfBirth?->format('Y-m-d');
        $model->gender = $entity->gender;
        $model->address = $entity->address;
        $model->mobile_no = $entity->mobileNo;
        $model->email_verified_at = $entity->emailVerifiedAt?->format('Y-m-d H:i:s');
        $model->qr_code = $entity->qrCode;
        $model->remember_token = $entity->rememberToken;
        $model->last_login_at = $entity->lastLoginAt?->format('Y-m-d H:i:s');
        $model->last_login_ip = $entity->lastLoginIp;
        $model->timezone = $entity->timezone;
        $model->language = $entity->language;
        $model->nid = $entity->nid;
        $model->is_active = $entity->isActive;
        $model->is_deleted = $entity->isDeleted;
        $model->deleted_at = $entity->deletedAt?->format('Y-m-d H:i:s');
        $model->created_by = $entity->createdBy;
        $model->updated_by = $entity->updatedBy;
        $model->created_at = $entity->createdAt->format('Y-m-d H:i:s');
        $model->updated_at = $entity->updatedAt->format('Y-m-d H:i:s');
    }

    private function mapToEntity(EloquentUser $model): UserEntity
    {
        $roles = $model->relationLoaded('roles')
            ? $model->roles->pluck('name')->toArray()
            : [];

        $permissions = [];
        if ($model->relationLoaded('roles')) {
            foreach ($model->roles as $role) {
                if ($role->relationLoaded('permissions')) {
                    foreach ($role->permissions as $perm) {
                        $permissions[$perm->name] = $perm->name;
                    }
                }
            }
        }

        if ($model->relationLoaded('permissions')) {
            foreach ($model->permissions as $perm) {
                $permissions[$perm->name] = $perm->name;
            }
        }

        return new UserEntity(
            id: $model->id,
            name: $model->name,
            username: $model->username,
            email: $model->email,
            password: $model->password,
            profileImage: $model->profile_image,
            bio: $model->bio,
            dateOfBirth: $model->date_of_birth ? new DateTimeImmutable($model->date_of_birth) : null,
            gender: $model->gender,
            address: $model->address,
            mobileNo: $model->mobile_no,
            emailVerifiedAt: $model->email_verified_at ? new DateTimeImmutable($model->email_verified_at) : null,
            qrCode: $model->qr_code,
            rememberToken: $model->remember_token,
            lastLoginAt: $model->last_login_at ? new DateTimeImmutable($model->last_login_at) : null,
            lastLoginIp: $model->last_login_ip,
            timezone: $model->timezone,
            language: $model->language,
            nid: $model->nid,
            isActive: $model->is_active,
            isDeleted: $model->is_deleted,
            deletedAt: $model->deleted_at ? new DateTimeImmutable($model->deleted_at) : null,
            createdAt: new DateTimeImmutable($model->created_at),
            updatedAt: new DateTimeImmutable($model->updated_at),
            createdBy: $model->created_by,
            updatedBy: $model->updated_by,
            refreshTokens: [],
            roles: $roles,
            permissions: array_values($permissions)
        );
    }
}
