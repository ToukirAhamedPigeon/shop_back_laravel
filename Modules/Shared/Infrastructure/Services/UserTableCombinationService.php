<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Repositories\IUserTableCombinationRepository;
use Modules\Shared\Application\Requests\UserTableCombinationRequest;
use Modules\Shared\Domain\Entities\UserTableCombination;
use Modules\Shared\Infrastructure\Helpers\UserLogHelper;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Application\Services\IUserTableCombinationService;

class UserTableCombinationService implements IUserTableCombinationService
{
    private IUserTableCombinationRepository $repository;
    private UserLogHelper $userLogHelper;
    private int $cacheTtl = 18000; // 5 hours

    public function __construct(
        IUserTableCombinationRepository $repository,
        UserLogHelper $userLogHelper
    ) {
        $this->repository = $repository;
        $this->userLogHelper = $userLogHelper;
    }

    private function cacheKey(string $tableId, string $userId): string
    {
        return "user_table:{$userId}:{$tableId}";
    }

    public function get(string $tableId, string $userId): array
    {
        $key = $this->cacheKey($tableId, $userId);

        return Cache::remember($key, $this->cacheTtl, function () use ($tableId, $userId) {
            $entity = $this->repository->findByTableIdAndUserId($tableId, $userId);

            return $entity
                ? $entity->showColumnCombinations
                : [];
        });
    }

    public function saveOrUpdate(UserTableCombinationRequest $request, string $authUserId): void
    {
        $dto = $request->validated();
        $cacheKey = $this->cacheKey($dto['tableId'], $dto['userId']);

        $entity = $this->repository->findByTableIdAndUserId(
            $dto['tableId'],
            $dto['userId']
        );

        $actionType = '';
        $beforeSnapshot = null;

        if ($entity) {
            // Keep snapshot of before values
            $beforeSnapshot = ['showColumnCombinations' => $entity->showColumnCombinations];

            $entity->showColumnCombinations = $dto['showColumnCombinations'];
            $entity->updatedBy = $authUserId;
            $entity->updatedAt = new DateTimeImmutable();

            $this->repository->update($entity);

            $actionType = 'Update';
        } else {
            $entity = new UserTableCombination(
                id: (string) Str::uuid(),
                tableId: $dto['tableId'],
                showColumnCombinations: $dto['showColumnCombinations'],
                userId: $dto['userId'],
                updatedBy: $authUserId,
                updatedAt: new DateTimeImmutable()
            );

            $this->repository->create($entity);

            $actionType = 'Create';
        }

        // Update cache
        Cache::put($cacheKey, $entity->showColumnCombinations, $this->cacheTtl);

        // Prepare after snapshot
        $afterSnapshot = ['showColumnCombinations' => $entity->showColumnCombinations];

        // Log user action
        try {
            $detailMessage = $actionType === 'Update'
                ? "Updated column combination for table: {$dto['tableId']}"
                : "Created new column combination for table: {$dto['tableId']}";

            $this->userLogHelper->log(
                actionType: $actionType,
                detail: $detailMessage,
                changes: [
                    'before' => $beforeSnapshot,
                    'after'  => $afterSnapshot
                ],
                modelName: 'UserTableCombination',
                modelId: $entity->id
            );
        } catch (Exception $ex) {
            // Fail silently but log to Laravel log
            Log::error("UserLog Error (UserTableCombination): {$ex->getMessage()}");
        }
    }
}
