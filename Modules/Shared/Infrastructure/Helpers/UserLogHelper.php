<?php

namespace Modules\Shared\Infrastructure\Helpers;

use Illuminate\Support\Facades\Auth;
use Modules\Shared\Domain\Entities\UserLog;
use Modules\Shared\Infrastructure\Models\EloquentUserLog;
use DateTimeImmutable;

class UserLogHelper
{
    /**
     * Log a user action.
     *
     * @param string $actionType
     * @param string|null $detail
     * @param mixed $changes
     * @param string|null $modelName
     * @param string|null $modelId
     * @return UserLog
     */
    public function log(
        string $actionType,
        ?string $detail = null,
        mixed $changes = null,
        ?string $modelName = null,
        ?string $modelId = null,
        ?string $userId = null   // <-- add this
    ): UserLog {
        $request = request();
        $user = Auth::user();

        [$browser, $os, $device] = $this->parseUserAgent($request->userAgent());

        $eloquentLog = new EloquentUserLog([
            'id'               => uuid_create(UUID_TYPE_RANDOM),
            'created_by'       => $userId ?? $user?->id, // <-- use passed userId if available
            'action_type'      => $actionType,
            'detail'           => $detail,
            'changes'          => $this->normalizeChanges($changes),
            'model_name'       => $modelName,
            'model_id'         => $modelId,
            'ip_address'       => $request->ip(),
            'browser'          => $browser,
            'device'           => $device,
            'operating_system' => $os,
            'user_agent'       => $request->userAgent(),
            'created_at'       => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'created_at_id'    => (int) (microtime(true) * 1000),
        ]);

        $eloquentLog->save();

        return new UserLog(
            id: $eloquentLog->id,
            actionType: $eloquentLog->action_type,
            modelName: $eloquentLog->model_name,
            createdBy: $eloquentLog->created_by,
            createdAt: new DateTimeImmutable($eloquentLog->created_at),
            createdAtId: $eloquentLog->created_at_id,
            detail: $eloquentLog->detail,
            changes: is_array($eloquentLog->changes)
                ? json_encode($eloquentLog->changes, JSON_UNESCAPED_UNICODE)
                : $eloquentLog->changes,
            modelId: $eloquentLog->model_id,
            ipAddress: $eloquentLog->ip_address,
            browser: $eloquentLog->browser,
            device: $eloquentLog->device,
            operatingSystem: $eloquentLog->operating_system,
            userAgent: $eloquentLog->user_agent
        );
    }

    /**
     * Normalize changes to JSON string with 'before' and 'after'.
     *
     * @param mixed $changes
     * @return string|null
     */
    private function normalizeChanges(mixed $changes): ?string
    {
        if (!$changes) {
            return null;
        }

        if (is_string($changes)) {
            return $changes;
        }

        $before = $changes['before'] ?? [];
        $after  = $changes['after'] ?? $changes;

        $filteredAfter = [];
        foreach ($after as $key => $value) {
            if (!array_key_exists($key, $before) || $before[$key] !== $value) {
                $filteredAfter[$key] = $value;
            }
        }

        return json_encode([
            'before' => $before,
            'after'  => $filteredAfter,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse user agent string into [browser, OS, device].
     *
     * @param string|null $ua
     * @return array
     */
    private function parseUserAgent(?string $ua): array
    {
        if (!$ua) {
            return ['Unknown', 'Unknown', 'Unknown'];
        }

        // Browser detection
        if (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edge')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Edge')) {
            $browser = 'Edge';
        } else {
            $browser = 'Unknown';
        }

        // OS detection
        if (str_contains($ua, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($ua, 'Mac')) {
            $os = 'MacOS';
        } elseif (str_contains($ua, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($ua, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $os = 'iOS';
        } else {
            $os = 'Unknown';
        }

        // Device type
        $device = str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone') ? 'Mobile' : 'Desktop';

        return [$browser, $os, $device];
    }

    /**
     * Get client IP address.
     *
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        return request()->ip();
    }
}
