<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IUserService;
use Modules\Shared\Application\Services\IMailVerificationService;
use Modules\Shared\Application\Services\IChangePasswordService;
use Modules\Shared\Application\Repositories\IUserRepository;
use Modules\Shared\Application\Repositories\IRolePermissionRepository;
use Modules\Shared\Application\Requests\User\UserFilterRequest;
use Modules\Shared\Application\Requests\User\CreateUserRequest;
use Modules\Shared\Application\Requests\User\UpdateUserRequest;
use Modules\Shared\Application\Requests\User\UpdateProfileRequest;
use Modules\Shared\Application\Requests\User\ChangePasswordRequest;
use Modules\Shared\Application\Requests\User\VerifyPasswordChangeRequest;
use Modules\Shared\Application\Resources\User\UserResource;
use Modules\Shared\Domain\Entities\User;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use Modules\Shared\Infrastructure\Helpers\FileHelper;
use Modules\Shared\Infrastructure\Helpers\QRHelper;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class UserService implements IUserService
{
    private IUserRepository $repo;
    private IRolePermissionRepository $rolePermissionRepo;
    private UserLogHelper $userLogHelper;
    private IMailVerificationService $mailVerificationService;
    private IChangePasswordService $changePasswordService;
    private EloquentUser $userModel;

    public function __construct(
        IUserRepository $repo,
        IRolePermissionRepository $rolePermissionRepo,
        UserLogHelper $userLogHelper,
        IMailVerificationService $mailVerificationService,
        IChangePasswordService $changePasswordService,
        EloquentUser $userModel
    ) {
        $this->repo = $repo;
        $this->rolePermissionRepo = $rolePermissionRepo;
        $this->userLogHelper = $userLogHelper;
        $this->mailVerificationService = $mailVerificationService;
        $this->changePasswordService = $changePasswordService;
        $this->userModel = $userModel;
    }

    /**
     * Get paginated list of users
     */
    public function getUsers(UserFilterRequest $request): array
    {
        return $this->repo->getFiltered($request);
    }

    public function getUsersAsync(UserFilterRequest $request): array
    {
        return $this->getUsers($request);
    }

    /**
     * Get user by ID with roles and permissions
     */
    public function getUser(string $id): ?array
    {
        $user = $this->repo->getById($id);
        if (!$user) return null;

        // Fetch roles & permissions
        $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);
        $permissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);

        $user->roles = $roles;
        $user->permissions = $permissions;

        return (new UserResource($user))->toArray();
    }

    public function getUserAsync(string $id): ?array
    {
        return $this->getUser($id);
    }

    /**
     * Get user for edit (separates direct permissions from role permissions)
     */
    public function getUserForEdit(string $id): ?array
    {
        $user = $this->repo->getById($id);
        if (!$user) return null;

        $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);
        $allPermissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);
        $rolePermissions = $this->rolePermissionRepo->getRolePermissionsByUserId($user->id);

        // Only direct permissions for Edit form
        $directPermissions = array_diff($allPermissions, $rolePermissions);

        // Get creator and updater names
        $createdByName = null;
        $updatedByName = null;

        if ($user->createdBy) {
            $creator = $this->userModel->withoutGlobalScopes()->find($user->createdBy);
            $createdByName = $creator?->name;
        }

        if ($user->updatedBy) {
            $updater = $this->userModel->withoutGlobalScopes()->find($user->updatedBy);
            $updatedByName = $updater?->name;
        }

        $user->roles = $roles;
        $user->permissions = array_values($directPermissions);

        $resource = new UserResource($user, $createdByName, $updatedByName);
        return $resource->toArray();
    }

    public function getUserForEditAsync(string $id): ?array
    {
        return $this->getUserForEdit($id);
    }

    /**
     * Create new user
     */
    public function createUser(CreateUserRequest $request, ?string $createdBy): array
    {
        // Log the request data for debugging
        Log::info('CreateUser received:', [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'mobileNo' => $request->mobileNo,
            'nid' => $request->nid,
            'roles' => $request->roles,
            'has_file' => $request->hasFile('profileImage'),
            'has_file_capital' => $request->hasFile('ProfileImage'),
            'all_input' => $request->all()
        ]);

        // 1️⃣ Validation
        if (empty($request->name)) {
            return ['success' => false, 'message' => 'Name is required'];
        }
        if (empty($request->username) || strlen($request->username) < 4) {
            return ['success' => false, 'message' => 'Username must be at least 4 characters'];
        }
        if (empty($request->email)) {
            return ['success' => false, 'message' => 'Email is required'];
        }

        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/';
        if (!preg_match($passwordRegex, $request->password)) {
            return ['success' => false, 'message' => 'Password must contain uppercase, lowercase, number and special character.'];
        }

        if ($request->password !== $request->confirmedPassword) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }

        if (empty($request->roles)) {
            return ['success' => false, 'message' => 'At least one role must be selected'];
        }

        // 2️⃣ Parse createdBy
        $createdByGuid = null;
        if (!empty($createdBy) && Str::isUuid($createdBy)) {
            $createdByGuid = $createdBy;
        }

        // 3️⃣ Check uniqueness
        if ($this->repo->getByIdentifier($request->username)) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        if ($this->repo->getByEmail($request->email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        if (!empty($request->nid) && $this->repo->existsByNID($request->nid)) {
            return ['success' => false, 'message' => 'NID already exists'];
        }

        // 4️⃣ Parse IsActive
        $isActive = filter_var($request->isActive ?? 'true', FILTER_VALIDATE_BOOLEAN);

        // 5️⃣ Start transaction
        DB::beginTransaction();

        try {
            // 6️⃣ Hash password
            $hashedPassword = Hash::make($request->password);

            // 7️⃣ Create user entity
            $user = new User(
                id: (string) Str::uuid(),
                name: trim($request->name),
                username: trim($request->username),
                email: strtolower(trim($request->email)),
                password: $hashedPassword,
                mobileNo: $request->mobileNo,
                nid: $request->nid,
                bio: $request->bio,
                address: $request->address,
                gender: $request->gender,
                dateOfBirth: $request->dateOfBirth ? Carbon::parse($request->dateOfBirth)->toDateTimeImmutable() : null,
                isActive: $isActive,
                isDeleted: false,
                createdAt: Carbon::now()->toDateTimeImmutable(),
                updatedAt: Carbon::now()->toDateTimeImmutable(),
                createdBy: $createdByGuid,
                updatedBy: $createdByGuid
            );

            // 8️⃣ Handle profile image using FileHelper - FIX: Check both field names
            $file = null;
            if ($request->hasFile('profileImage')) {
                $file = $request->file('profileImage');
                Log::info('Found profileImage field');
            } elseif ($request->hasFile('ProfileImage')) {
                $file = $request->file('ProfileImage');
                Log::info('Found ProfileImage field');
            }

            if ($file) {
                try {
                    Log::info('Processing profile image', [
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType()
                    ]);

                    $imagePath = FileHelper::saveFile($file, 'users');
                    if ($imagePath) {
                        $this->resizeImage($imagePath, 1000, 1000);
                        $user->profileImage = $imagePath;
                        Log::info('Image saved successfully', ['path' => $imagePath]);
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error saving profile image', ['error' => $e->getMessage()]);
                    return ['success' => false, 'message' => 'Error saving profile image: ' . $e->getMessage()];
                }
            } else {
                Log::info('No profile image file found in request');
            }

            // 9️⃣ Generate QR code
            $user->qrCode = $this->generateQRCodeString($user->id);

            // 🔟 Save user
            $this->repo->add($user);
            $this->repo->saveChanges();

            // 1️⃣1️⃣ Assign Roles & Permissions
            $this->rolePermissionRepo->assignRolesToUser($user->id, $request->roles);

            if (!empty($request->permissions)) {
                $this->rolePermissionRepo->assignPermissionsToUser($user->id, $request->permissions);
            }

            // 1️⃣2️⃣ Log snapshot
            $afterSnapshot = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'mobileNo' => $user->mobileNo,
                'nid' => $user->nid,
                'gender' => $user->gender,
                'dateOfBirth' => $user->dateOfBirth?->format('Y-m-d'),
                'bio' => $user->bio,
                'address' => $user->address,
                'profileImage' => $user->profileImage,
                'qrCode' => $user->qrCode,
                'isActive' => $user->isActive,
                'roles' => $this->rolePermissionRepo->getRoleNamesByUserId($user->id),
                'permissions' => $this->rolePermissionRepo->getAllPermissionsByUserId($user->id)
            ];

            $changesJson = json_encode(['before' => null, 'after' => $afterSnapshot]);

            $this->userLogHelper->log(
                actionType: 'Create',
                detail: "User '{$user->username}' was created",
                changes: $changesJson,
                modelName: 'User',
                modelId: $user->id,
                userId: $createdByGuid ?? $user->id
            );

            // 1️⃣3️⃣ Send verification email
            $this->mailVerificationService->sendVerificationEmail($user);

            // 1️⃣4️⃣ Commit transaction
            DB::commit();

            Log::info('User created successfully', ['username' => $user->username]);

            return ['success' => true, 'message' => 'User created successfully. Verification email sent.'];
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Error creating user: ' . $ex->getMessage(), [
                'trace' => $ex->getTraceAsString()
            ]);
            return ['success' => false, 'message' => "Error: {$ex->getMessage()}"];
        }
    }

    public function createUserAsync(CreateUserRequest $request, ?string $createdBy): array
    {
        return $this->createUser($request, $createdBy);
    }

    /**
     * Regenerate QR code for user
     */
    public function regenerateQr(string $id, ?string $currentUserId): ?array
    {
        $user = $this->repo->getById($id);
        if (!$user) return null;

        $user->qrCode = $this->generateQRCodeString($user->id);
        $user->updatedAt = Carbon::now()->toDateTimeImmutable();

        if (!empty($currentUserId) && Str::isUuid($currentUserId)) {
            $user->updatedBy = $currentUserId;
        }

        $this->repo->update($user);
        $this->repo->saveChanges();

        return $this->getUser($id);
    }

    public function regenerateQrAsync(string $id, ?string $currentUserId): ?array
    {
        return $this->regenerateQr($id, $currentUserId);
    }

    /**
     * Update user
     */
    public function updateUser(string $id, UpdateUserRequest $request, ?string $currentUserId): array
    {
        // Debug: Log the request data
        Log::info('UserService updateUser received:', [
            'id' => $id,
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'roles' => $request->roles,
            'has_file' => $request->hasFile('profile_image'),
            'remove_profile_image' => $request->remove_profile_image,
            'all_input' => $request->all()
        ]);

        // 1️⃣ Fetch user
        $user = $this->repo->getById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // 2️⃣ Validate uniqueness
        if ($this->repo->existsByUsername($request->username, $id)) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        if ($this->repo->existsByEmail($request->email, $id)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        // 3️⃣ Validate roles exist
        $validRoles = $this->rolePermissionRepo->validateRolesExist($request->roles ?? []);
        if (count($validRoles) !== count($request->roles ?? [])) {
            return ['success' => false, 'message' => 'One or more roles are invalid'];
        }

        // 4️⃣ Check if email changed
        $emailChanged = strtolower($user->email) !== strtolower($request->email);

        // 5️⃣ Update basic fields
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->isActive = filter_var($request->isActive ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $user->mobileNo = $request->mobile_no;
        $user->nid = $request->nid;
        $user->address = $request->address;

        // 6️⃣ Handle password update if provided
        if (!empty($request->password)) {
            $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/';
            if (!preg_match($passwordRegex, $request->password)) {
                return ['success' => false, 'message' => 'Password must contain uppercase, lowercase, number and special character.'];
            }
            if ($request->password !== $request->confirmedPassword) {
                return ['success' => false, 'message' => 'Passwords do not match'];
            }
            $user->password = Hash::make($request->password);
        }

        // 7️⃣ Handle ProfileImage using FileHelper
        if ($request->remove_profile_image) {
            if (!empty($user->profileImage)) {
                FileHelper::deleteFile($user->profileImage);
                $user->profileImage = null;
            }
        } elseif ($request->hasFile('profile_image')) {
            if (!empty($user->profileImage)) {
                FileHelper::deleteFile($user->profileImage);
            }

            try {
                $imagePath = FileHelper::saveFile($request->file('profile_image'), 'users');
                if ($imagePath) {
                    $this->resizeImage($imagePath, 1000, 1000);
                    $user->profileImage = $imagePath;
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // 8️⃣ Update roles and permissions
        $this->rolePermissionRepo->setRolesForUser($user->id, $request->roles ?? []);

        $rolePermissions = $this->rolePermissionRepo->getPermissionsByRoleNames($request->roles ?? []);
        $filteredPermissions = array_diff($request->permissions ?? [], $rolePermissions);

        $this->rolePermissionRepo->setPermissionsForUser($user->id, $filteredPermissions);

        // 9️⃣ Handle email verification if email changed
        if ($emailChanged) {
            $user->emailVerifiedAt = null;
            $this->mailVerificationService->sendVerificationEmail($user);
        }

        // 🔟 Audit fields
        if (!empty($currentUserId) && Str::isUuid($currentUserId)) {
            $user->updatedBy = $currentUserId;
        }
        $user->updatedAt = Carbon::now()->toDateTimeImmutable();

        // 1️⃣1️⃣ Save changes
        $this->repo->update($user);
        $this->repo->saveChanges();

        Log::info('User updated successfully', ['id' => $id]);

        return ['success' => true, 'message' => 'User updated successfully'];
    }

    public function updateUserAsync(string $id, UpdateUserRequest $request, ?string $currentUserId): array
    {
        return $this->updateUser($id, $request, $currentUserId);
    }

    /**
     * Get user profile
     */
    public function getProfile(string $userId): ?array
    {
        $user = $this->repo->getById($userId);
        if (!$user) return null;

        $roles = $this->rolePermissionRepo->getRoleNamesByUserId($user->id);
        $permissions = $this->rolePermissionRepo->getAllPermissionsByUserId($user->id);

        $user->roles = $roles;
        $user->permissions = $permissions;

        return (new UserResource($user))->toArray();
    }

    public function getProfileAsync(string $userId): ?array
    {
        return $this->getProfile($userId);
    }

    /**
     * Update user profile
     */
    public function updateProfile(string $userId, UpdateProfileRequest $request): array
    {
        // 1️⃣ Fetch user
        $user = $this->repo->getById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Log the request data for debugging
        Log::info('Updating profile with data:', [
            'name' => $request->name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'nid' => $request->nid,
            'address' => $request->address,
            'bio' => $request->bio,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'remove_profile_image' => $request->remove_profile_image,
        ]);

        // 2️⃣ Validate email uniqueness if changed
        if (!empty($request->email) && strtolower($user->email) !== strtolower($request->email) &&
            $this->repo->existsByEmail($request->email, $userId)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        // 3️⃣ Validate mobile number uniqueness if changed
        if (!empty($request->mobile_no) &&
            $request->mobile_no !== $user->mobileNo &&
            $this->repo->existsByMobileNo($request->mobile_no, $userId)) {
            return ['success' => false, 'message' => 'Mobile number already exists'];
        }

        // 4️⃣ Validate NID uniqueness if changed
        if (!empty($request->nid) &&
            $request->nid !== $user->nid &&
            $this->repo->existsByNID($request->nid, $userId)) {
            return ['success' => false, 'message' => 'NID already exists'];
        }

        $emailChanged = !empty($request->email) && strtolower($user->email) !== strtolower($request->email);

        // 5️⃣ Update profile fields (only if provided)
        if (!empty($request->name)) {
            $user->name = $request->name;
        }

        if (!empty($request->email)) {
            $user->email = $request->email;
        }

        // Handle mobile_no - ALWAYS keep the existing value if not provided
        // The field is required in the database, so we must never set it to null
        if (isset($request->mobile_no)) {
            // Only update if a value is provided (even empty string)
            $user->mobileNo = $request->mobile_no;
        }
        // If mobile_no is not in the request, keep the existing value

        if (!empty($request->nid)) {
            $user->nid = $request->nid;
        }

        if (!empty($request->address)) {
            $user->address = $request->address;
        }

        if (!empty($request->bio)) {
            $user->bio = $request->bio;
        }

        if (!empty($request->gender)) {
            $user->gender = $request->gender;
        }

        if (!empty($request->date_of_birth)) {
            try {
                $user->dateOfBirth = Carbon::parse($request->date_of_birth)->toDateTimeImmutable();
            } catch (\Exception $e) {
                Log::warning('Invalid date format', ['date' => $request->date_of_birth]);
            }
        }

        // 6️⃣ Handle Profile Image using FileHelper
        if ($request->remove_profile_image) {
            if (!empty($user->profileImage)) {
                FileHelper::deleteFile($user->profileImage);
                $user->profileImage = null;
            }
        } elseif ($request->hasFile('profile_image')) {
            if (!empty($user->profileImage)) {
                FileHelper::deleteFile($user->profileImage);
            }

            try {
                $imagePath = FileHelper::saveFile($request->file('profile_image'), 'users');
                if ($imagePath) {
                    $this->resizeImage($imagePath, 1000, 1000);
                    $user->profileImage = $imagePath;
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // 7️⃣ Handle email verification if email changed
        if ($emailChanged) {
            $user->emailVerifiedAt = null;
            $this->mailVerificationService->sendVerificationEmail($user);
        }

        // 8️⃣ Audit fields
        $user->updatedAt = Carbon::now()->toDateTimeImmutable();

        // 9️⃣ Save changes
        $this->repo->update($user);
        $this->repo->saveChanges();

        Log::info('Profile updated successfully', ['user_id' => $userId]);

        return ['success' => true, 'message' => 'Profile updated successfully'];
    }

    public function updateProfileAsync(string $userId, UpdateProfileRequest $request): array
    {
        return $this->updateProfile($userId, $request);
    }

    /**
     * Request password change
     */
    public function requestPasswordChange(string $userId, ChangePasswordRequest $request): array
    {
        try {
            $result = $this->changePasswordService->requestChangePassword($userId, $request);
            return ['success' => true, 'message' => $result->message];
        } catch (Exception $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        }
    }

    public function requestPasswordChangeAsync(string $userId, ChangePasswordRequest $request): array
    {
        return $this->requestPasswordChange($userId, $request);
    }

    /**
     * Verify password change token
     */
    public function verifyPasswordChange(string $token): array
    {
        try {
            $this->changePasswordService->completeChangePassword(
                new VerifyPasswordChangeRequest(['token' => $token])
            );
            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (Exception $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        }
    }

    public function verifyPasswordChangeAsync(string $token): array
    {
        return $this->verifyPasswordChange($token);
    }

    /**
     * Delete user (soft or hard)
     */
    public function deleteUser(string $id, bool $permanent, ?string $currentUserId): array
    {
        // 1️⃣ Fetch user
        $user = $this->repo->getById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'deleteType' => 'none'];
        }

        // 2️⃣ Parse current user ID
        $deletedBy = null;
        if (!empty($currentUserId) && Str::isUuid($currentUserId)) {
            $deletedBy = $currentUserId;
        }

        // 3️⃣ Check if user is already deleted
        if ($user->isDeleted) {
            return ['success' => false, 'message' => 'User is already deleted', 'deleteType' => 'none'];
        }

        // 4️⃣ Determine delete type based on conditions
        $deleteType = 'soft'; // Default to soft delete

        if ($permanent) {
            // Check if permanent deletion is possible
            $hasRelatedRecords = $this->repo->hasRelatedRecords($id);
            $hasVerifiedEmail = $this->repo->hasVerifiedEmail($id);

            Log::info('Checking delete conditions', [
                'hasRelatedRecords' => $hasRelatedRecords,
                'hasVerifiedEmail' => $hasVerifiedEmail,
                'permanent' => $permanent
            ]);
            // If no related records AND email not verified, allow permanent delete
            if (!$hasRelatedRecords && !$hasVerifiedEmail) {
                $deleteType = 'permanent';
            } else {
                // Force soft delete if conditions not met
                $deleteType = 'soft';
            }
        }

        // 5️⃣ Perform the deletion
        DB::beginTransaction();

        try {
            if ($deleteType === 'permanent') {
                // Delete profile image first if it exists
                if (!empty($user->profileImage)) {
                    FileHelper::deleteFile($user->profileImage);
                }

                // Permanent delete - remove user and all related records
                $this->repo->hardDelete($id);

                // Log the action
                $this->userLogHelper->log(
                    actionType: 'Delete',
                    detail: "User '{$user->username}' was permanently deleted",
                    changes: json_encode([
                        'before' => ['id' => $user->id, 'username' => $user->username, 'email' => $user->email]
                    ]),
                    modelName: 'User',
                    modelId: $id,
                    userId: $deletedBy ?? $id
                );
            } else {
                // Soft delete - just mark as deleted
                $this->repo->softDelete($id, $deletedBy);

                // Log the action
                $this->userLogHelper->log(
                    actionType: 'Delete',
                    detail: "User '{$user->username}' was soft deleted",
                    changes: json_encode([
                        'before' => ['id' => $user->id, 'username' => $user->username, 'email' => $user->email, 'isDeleted' => false],
                        'after' => ['isDeleted' => true, 'deletedAt' => Carbon::now()->toISOString()]
                    ]),
                    modelName: 'User',
                    modelId: $id,
                    userId: $deletedBy ?? $id
                );
            }

            $this->repo->saveChanges();
            DB::commit();

            return [
                'success' => true,
                'message' => "User " . ($deleteType === 'permanent' ? 'permanently' : 'soft') . " deleted successfully",
                'deleteType' => $deleteType
            ];
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Error deleting user: ' . $ex->getMessage());
            return ['success' => false, 'message' => "Error deleting user: {$ex->getMessage()}", 'deleteType' => 'none'];
        }
    }

    public function deleteUserAsync(string $id, bool $permanent, ?string $currentUserId): array
    {
        return $this->deleteUser($id, $permanent, $currentUserId);
    }

    /**
     * Restore soft-deleted user
     */
    public function restoreUser(string $id, ?string $currentUserId): array
    {
        // 1️⃣ Fetch user
        $user = $this->repo->getById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // 2️⃣ Check if user is actually deleted
        if (!$user->isDeleted) {
            return ['success' => false, 'message' => 'User is not deleted'];
        }

        // 3️⃣ Parse restored by
        $restoredBy = null;
        if (!empty($currentUserId) && Str::isUuid($currentUserId)) {
            $restoredBy = $currentUserId;
        }

        // 4️⃣ Restore the user
        $this->repo->restore($id, $restoredBy);
        $this->repo->saveChanges();

        // 5️⃣ Log the action
        $this->userLogHelper->log(
            actionType: 'Restore',
            detail: "User '{$user->username}' was restored",
            changes: json_encode([
                'before' => ['isDeleted' => true, 'deletedAt' => $user->deletedAt?->format('Y-m-d H:i:s')],
                'after' => ['isDeleted' => false]
            ]),
            modelName: 'User',
            modelId: $id,
            userId: $restoredBy ?? $id
        );

        return ['success' => true, 'message' => 'User restored successfully'];
    }

    public function restoreUserAsync(string $id, ?string $currentUserId): array
    {
        return $this->restoreUser($id, $currentUserId);
    }

    /**
     * Check if user can be permanently deleted
     */
    public function checkDeleteEligibility(string $id): array
    {
        $user = $this->repo->getById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'canBePermanent' => false];
        }

        if ($user->isDeleted) {
            return ['success' => false, 'message' => 'User is already deleted', 'canBePermanent' => false];
        }

        $hasRelatedRecords = $this->repo->hasRelatedRecords($id);
        $hasVerifiedEmail = $this->repo->hasVerifiedEmail($id);

        $canBePermanent = !$hasRelatedRecords && !$hasVerifiedEmail;
        $message = $canBePermanent
            ? 'User can be permanently deleted'
            : 'User must be soft deleted due to existing related records';

        return ['success' => true, 'message' => $message, 'canBePermanent' => $canBePermanent];
    }

    public function checkDeleteEligibilityAsync(string $id): array
    {
        return $this->checkDeleteEligibility($id);
    }

    /**
     * Private helper methods
     */
    private function resizeImage(string $imagePath, int $width, int $height): void
    {
        $relativePath = str_replace('/storage/', '', $imagePath);
        $fullPath = storage_path("app/public/{$relativePath}");

        if (file_exists($fullPath)) {
            try {
                // V3 syntax uses read() instead of make()
                $img = Image::read($fullPath);
                $img->scale($width, $height);
                $img->save($fullPath);
            } catch (\Exception $e) {
                Log::warning('Failed to resize image: ' . $e->getMessage());
            }
        }
    }

    private function generateQRCodeString(string $userId): string
    {
        // Combine User ID, UTC timestamp, and a random 4-character string
        $timestamp = Carbon::now()->format('YmdHisu'); // precise milliseconds
        $random = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        return str_replace('-', '', $userId) . "-{$timestamp}-{$random}";
    }
}
