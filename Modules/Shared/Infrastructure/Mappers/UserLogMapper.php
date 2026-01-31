<?php

namespace Modules\Shared\Infrastructure\Mappers;

use Modules\Shared\Domain\Entities\UserLog;
use Modules\Shared\Infrastructure\Models\EloquentUserLog;
use DateTimeImmutable;

final class UserLogMapper
{
    public static function toEntity(EloquentUserLog $m): UserLog
    {
        return new UserLog(
            id: $m->id,
            actionType: $m->action_type,
            modelName: $m->model_name,
            createdBy: $m->created_by,
            createdAt: new DateTimeImmutable($m->created_at),
            createdAtId: $m->created_at_id,
            detail: $m->detail,
            changes: is_array($m->changes) ? json_encode($m->changes, JSON_UNESCAPED_UNICODE) : $m->changes,
            modelId: $m->model_id,
            ipAddress: $m->ip_address,
            browser: $m->browser,
            device: $m->device,
            operatingSystem: $m->os,
            userAgent: $m->user_agent
        );
    }

    public static function toModel(UserLog $log): array
    {
        return [
            'id' => $log->id,
            'detail' => $log->detail,
            'changes' => is_array($log->changes) ? json_encode($log->changes, JSON_UNESCAPED_UNICODE) : $log->changes,
            'action_type' => $log->actionType,
            'model_name' => $log->modelName,
            'model_id' => $log->modelId,
            'created_by' => $log->createdBy,
            'created_at' => $log->createdAt->format('Y-m-d H:i:s'),
            'created_at_id' => $log->createdAtId,
            'ip_address' => $log->ipAddress,
            'browser' => $log->browser,
            'device' => $log->device,
            'os' => $log->operatingSystem,
            'user_agent' => $log->userAgent,
        ];
    }
}
