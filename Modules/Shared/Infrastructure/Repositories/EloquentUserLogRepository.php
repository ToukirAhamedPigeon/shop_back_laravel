<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Carbon\Carbon;
use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Domain\Entities\UserLog;
use Modules\Shared\Infrastructure\Models\EloquentUserLog;
use Modules\Shared\Application\Requests\UserLog\UserLogFilterRequest;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Modules\Shared\Application\Resources\UserLog\UserLogResource;
use Modules\Shared\Application\Resources\Common\SelectOptionResource;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class EloquentUserLogRepository implements IUserLogRepository
{
    /**
     * Create a new user log
     */
    public function create(UserLog $log): UserLog
    {
        $model = new EloquentUserLog();
        $this->mapToModel($log, $model);
        $model->save();

        return $this->mapToEntity($model);
    }

    /**
     * Async version for interface compatibility
     */
    public function createAsync(UserLog $log): UserLog
    {
        return $this->create($log);
    }

    /**
     * Find user log by ID
     */
    public function getById(string $id): ?UserLog
    {
        $model = EloquentUserLog::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * Async version for interface compatibility
     */
    public function getByIdAsync(string $id): ?UserLog
    {
        return $this->getById($id);
    }

    /**
     * Get all user logs by user ID (created_by)
     */
    public function getByUserId(string $createdBy): array
    {
        return EloquentUserLog::where('created_by', $createdBy)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->toArray();
    }

    /**
     * Async version for interface compatibility
     */
    public function getByUserIdAsync(string $createdBy): array
    {
        return $this->getByUserId($createdBy);
    }

    /**
     * Get all user logs
     */
    public function getAll(): array
    {
        return EloquentUserLog::orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->toArray();
    }

    /**
     * Async version for interface compatibility
     */
    public function getAllAsync(): array
    {
        return $this->getAll();
    }

    /**
     * Save changes - in Laravel, saves are auto, kept for interface compatibility
     */
    public function saveChanges(): void
    {
        return;
    }

    /**
     * Async version for interface compatibility
     */
    public function saveChangesAsync(): void
    {
        $this->saveChanges();
    }

    /**
     * Get filtered user logs with pagination
     */
    public function getFiltered(UserLogFilterRequest $req): array
    {
        $query = DB::table('user_logs')
            ->join('users', 'user_logs.created_by', '=', 'users.id');

        // Search filter
        if (!empty($req->q)) {
            $query->where(function ($q) use ($req) {
                $q->where('user_logs.detail', 'like', "%{$req->q}%")
                ->orWhere('user_logs.action_type', 'like', "%{$req->q}%")
                ->orWhere('user_logs.model_name', 'like', "%{$req->q}%");
            });
        }

        // FIX: Date filters - use proper date formatting
        if ($req->createdAtFrom) {
            $fromDate = Carbon::parse($req->createdAtFrom)->startOfDay()->format('Y-m-d H:i:s');
            $query->where('user_logs.created_at', '>=', $fromDate);
        }

        if ($req->createdAtTo) {
            $toDate = Carbon::parse($req->createdAtTo)->endOfDay()->format('Y-m-d H:i:s');
            $query->where('user_logs.created_at', '<=', $toDate);
        }

        // Collection filter
        if (!empty($req->collectionName)) {
            $query->whereIn('user_logs.model_name', $req->collectionName);
        }

        // Action Type filter
        if (!empty($req->actionType)) {
            $query->whereIn('user_logs.action_type', $req->actionType);
        }

        // Created By filter
        if (!empty($req->createdBy)) {
            $query->whereIn('user_logs.created_by', $req->createdBy);
        }

        // Get total counts
        $totalCount = $query->count();
        $grandTotalCount = DB::table('user_logs')->count();

        // Sorting
        $allowedSortColumns = [
            'detail' => 'user_logs.detail',
            'actionType' => 'user_logs.action_type',
            'modelName' => 'user_logs.model_name',
            'createdAt' => 'user_logs.created_at',
            'createdBy' => 'users.name',
            'ipAddress' => 'user_logs.ip_address',
            'browser' => 'user_logs.browser',
            'device' => 'user_logs.device',
            'operatingSystem' => 'user_logs.os',
        ];

        $sortColumn = $allowedSortColumns[$req->sortBy] ?? 'user_logs.created_at';
        $sortOrder = strtolower($req->sortOrder) === 'asc' ? 'asc' : 'desc';

        // Pagination
        $logs = $query
            ->select(
                'user_logs.*',
                'users.name as created_by_name'
            )
            ->orderBy($sortColumn, $sortOrder)
            ->offset(($req->page - 1) * $req->limit)
            ->limit($req->limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'detail' => $log->detail,
                    'changes' => $log->changes ? json_decode($log->changes, true) : null,
                    'actionType' => $log->action_type,
                    'modelName' => $log->model_name,
                    'modelId' => $log->model_id,
                    'createdBy' => $log->created_by,
                    'createdByName' => $log->created_by_name,
                    'createdAt' => $log->created_at,
                    'createdAtId' => $log->created_at_id,
                    'ipAddress' => $log->ip_address,
                    'browser' => $log->browser,
                    'device' => $log->device,
                    'operatingSystem' => $log->os,
                    'userAgent' => $log->user_agent,
                ];
            })
            ->toArray();

        return [
            'logs' => $logs,
            'totalCount' => $totalCount,
            'grandTotalCount' => $grandTotalCount,
            'pageIndex' => $req->page - 1,
            'pageSize' => $req->limit,
        ];
    }

    /**
     * Async version for interface compatibility
     */
    public function getFilteredAsync(UserLogFilterRequest $req): array
    {
        return $this->getFiltered($req);
    }

    /**
     * Get distinct model names for dropdown
     */
    public function getDistinctModelNames(SelectOptionRequest $req): array
    {
        $query = DB::table('user_logs');

        // Apply where filter for ModelName
        if (!empty($req->where) && isset($req->where['ModelName'])) {
            $modelName = $req->where['ModelName'];
            if (!empty($modelName)) {
                $query->where('model_name', 'like', "%{$modelName}%");
            }
        }

        // Apply search
        if (!empty($req->search)) {
            $query->where('model_name', 'like', "%{$req->search}%");
        }

        return $query->select('model_name as value', 'model_name as label')
            ->distinct()
            ->orderBy('model_name')
            ->skip($req->skip)
            ->take($req->limit)
            ->get()
            ->map(fn ($item) => new SelectOptionResource($item->value, $item->label))
            ->toArray();
    }

    /**
     * Async version for interface compatibility
     */
    public function getDistinctModelNamesAsync(SelectOptionRequest $req): array
    {
        return $this->getDistinctModelNames($req);
    }

    /**
     * Get distinct action types for dropdown
     */
    public function getDistinctActionTypes(SelectOptionRequest $req): array
    {
        $query = DB::table('user_logs');

        // Apply where filter for ActionType
        if (!empty($req->where) && isset($req->where['ActionType'])) {
            $actionType = $req->where['ActionType'];
            if (!empty($actionType)) {
                $query->where('action_type', 'like', "%{$actionType}%");
            }
        }

        // Apply search
        if (!empty($req->search)) {
            $query->where('action_type', 'like', "%{$req->search}%");
        }

        return $query->select('action_type as value', 'action_type as label')
            ->distinct()
            ->orderBy('action_type')
            ->skip($req->skip)
            ->take($req->limit)
            ->get()
            ->map(fn ($item) => new SelectOptionResource($item->value, $item->label))
            ->toArray();
    }

    /**
     * Async version for interface compatibility
     */
    public function getDistinctActionTypesAsync(SelectOptionRequest $req): array
    {
        return $this->getDistinctActionTypes($req);
    }

    /**
     * Get distinct creators (users) for dropdown
     */
    public function getDistinctCreators(SelectOptionRequest $req): array
    {
        $query = DB::table('user_logs')
            ->join('users', 'user_logs.created_by', '=', 'users.id');

        // Apply where filter for CreatedByName
        if (!empty($req->where) && isset($req->where['CreatedByName'])) {
            $createdByName = $req->where['CreatedByName'];
            if (!empty($createdByName)) {
                $query->where('users.name', 'like', "%{$createdByName}%");
            }
        }

        // Apply search
        if (!empty($req->search)) {
            $query->where('users.name', 'like', "%{$req->search}%");
        }

        return $query->select('users.id as value', 'users.name as label')
            ->distinct()
            ->orderBy('users.name')
            ->skip($req->skip)
            ->take($req->limit)
            ->get()
            ->map(fn ($item) => new SelectOptionResource($item->value, $item->label))
            ->toArray();
    }

    /**
     * Async version for interface compatibility
     */
    public function getDistinctCreatorsAsync(SelectOptionRequest $req): array
    {
        return $this->getDistinctCreators($req);
    }

    /**
     * Map entity to model
     */
    private function mapToModel(UserLog $entity, EloquentUserLog $model): void
    {
        $model->id = $entity->id;
        $model->detail = $entity->detail;

        // Handle changes - encode array to JSON for storage
        $model->changes = $entity->changes ? json_encode($entity->changes) : null;

        $model->action_type = $entity->actionType;
        $model->model_name = $entity->modelName;
        $model->model_id = $entity->modelId;
        $model->created_by = $entity->createdBy;
        $model->created_at = $entity->createdAt->format('Y-m-d H:i:s');
        $model->created_at_id = $entity->createdAtId;
        $model->ip_address = $entity->ipAddress;
        $model->browser = $entity->browser;
        $model->device = $entity->device;
        $model->os = $entity->operatingSystem;
        $model->user_agent = $entity->userAgent;
    }

    /**
     * Decode changes field safely
     */
    private function decodeChanges($changes): ?array
    {
        if (is_array($changes)) {
            return $changes;
        }

        if (is_string($changes)) {
            return json_decode($changes, true) ?: null;
        }

        return null;
    }

    /**
     * Map model to entity
     */
    private function mapToEntity(EloquentUserLog $model): UserLog
    {
        return new UserLog(
            id: $model->id,
            actionType: $model->action_type,
            modelName: $model->model_name,
            createdBy: $model->created_by,
            createdAt: new DateTimeImmutable($model->created_at),
            createdAtId: (int) $model->created_at_id,
            detail: $model->detail,
            changes: $this->decodeChanges($model->changes),
            modelId: $model->model_id,
            ipAddress: $model->ip_address,
            browser: $model->browser,
            device: $model->device,
            operatingSystem: $model->os,
            userAgent: $model->user_agent
        );
    }

    /**
     * Map database row to DTO
     */
    private function mapToDto($row): UserLogResource
    {
        // Handle changes - check type before decoding
        $changes = $row->changes;

        if (is_string($changes)) {
            // If it's a JSON string, decode it
            $changes = json_decode($changes, true) ?: null;
        } elseif (is_array($changes)) {
            // If it's already an array, keep it as is
            $changes = $changes;
        } else {
            $changes = null;
        }

        return new UserLogResource((object) [
            'id' => $row->id,
            'detail' => $row->detail,
            'changes' => $changes,
            'action_type' => $row->action_type,
            'model_name' => $row->model_name,
            'model_id' => $row->model_id,
            'created_by' => $row->created_by,
            'created_by_name' => $row->created_by_name,
            'created_at' => $row->created_at,
            'created_at_id' => $row->created_at_id,
            'ip_address' => $row->ip_address,
            'browser' => $row->browser,
            'device' => $row->device,
            'os' => $row->os ?? $row->operating_system ?? null,
            'user_agent' => $row->user_agent,
        ]);
    }
}
