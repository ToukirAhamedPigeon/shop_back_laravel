<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Domain\Entities\UserLog;
use Modules\Shared\Infrastructure\Models\EloquentUserLog;
use Modules\Shared\Infrastructure\Mappers\UserLogMapper;
use Modules\Shared\Application\Requests\UserLog\UserLogFilterRequest;
use Modules\Shared\Application\Resources\UserLog\UserLogResource;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Illuminate\Support\Facades\DB;

final class EloquentUserLogRepository implements IUserLogRepository
{
    public function create(UserLog $log): UserLog
    {
        EloquentUserLog::create(UserLogMapper::toModel($log));
        return $log;
    }

    public function findById(string $id): ?UserLog
    {
        $m = EloquentUserLog::find($id);
        return $m ? UserLogMapper::toEntity($m) : null;
    }

    public function getFiltered(UserLogFilterRequest $req): array
    {
        $query = DB::table('user_logs')
            ->join('users', 'user_logs.created_by', '=', 'users.id');

        // Search query
        if ($req->q) {
            $query->where(function ($q) use ($req) {
                $q->where('user_logs.detail', 'like', "%{$req->q}%")
                  ->orWhere('user_logs.action_type', 'like', "%{$req->q}%")
                  ->orWhere('user_logs.model_name', 'like', "%{$req->q}%");
            });
        }

        // Date filters
        if ($req->createdAtFrom) {
            $query->where('user_logs.created_at', '>=', $req->createdAtFrom);
        }

        if ($req->createdAtTo) {
            $query->where('user_logs.created_at', '<=', $req->createdAtTo);
        }

        // Model and action filters
        if ($req->collectionName) {
            $query->whereIn('user_logs.model_name', $req->collectionName);
        }

        if ($req->actionType) {
            $query->whereIn('user_logs.action_type', $req->actionType);
        }

        if ($req->createdBy) {
            $query->whereIn('user_logs.created_by', $req->createdBy);
        }

        // Total counts
        $total = $query->count();
        $grandTotal = DB::table('user_logs')->count();

        // Safe dynamic sorting
        $allowedSortColumns = [
            'detail' => 'user_logs.detail',
            'actionType' => 'user_logs.action_type',
            'changes' => 'user_logs.changes',
            'modelName' => 'user_logs.model_name',
            'createdAt' => 'user_logs.created_at',
            'createdBy' => 'users.name',
            'operatingSystem' => 'user_logs.os',
            'browser' => 'user_logs.browser',
            'device' => 'user_logs.device',
            'ipAddress' => 'user_logs.ip_address',
            'userAgent' => 'user_logs.user_agent',
        ];

        $sortColumn = $allowedSortColumns[$req->sortBy] ?? 'user_logs.created_at';
        $sortOrder = strtolower($req->sortOrder) === 'asc' ? 'asc' : 'desc';

        $logs = $query
            ->select('user_logs.*', 'users.name as created_by_name')
            ->orderBy($sortColumn, $sortOrder)
            ->offset(($req->page - 1) * $req->limit)
            ->limit($req->limit)
            ->get();
        return [
            'logs' => UserLogResource::collection($logs),
            'totalCount' => $total,
            'grandTotalCount' => $grandTotal,
            'pageIndex' => $req->page - 1,
            'pageSize' => $req->limit,
        ];
    }

    public function getCollections(SelectOptionRequest $req): array
    {
        return DB::table('user_logs')
            ->select('model_name as value', 'model_name as label')
            ->distinct()
            ->limit($req->limit)
            ->offset($req->skip)
            ->get()
            ->toArray();
    }

    public function getActionTypes(SelectOptionRequest $req): array
    {
        return DB::table('user_logs')
            ->select('action_type as value', 'action_type as label')
            ->distinct()
            ->limit($req->limit)
            ->offset($req->skip)
            ->get()
            ->toArray();
    }

    public function getCreators(SelectOptionRequest $req): array
    {
        return DB::table('users')
            ->select('id as value', 'name as label')
            ->limit($req->limit)
            ->offset($req->skip)
            ->get()
            ->toArray();
    }
}
