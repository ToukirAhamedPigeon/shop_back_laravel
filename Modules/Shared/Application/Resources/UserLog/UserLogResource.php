<?php

namespace Modules\Shared\Application\Resources\UserLog;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class UserLogResource extends JsonResource
{
    public function toArray($request): array
    {
        // Handle changes - check if it's already an array or needs decoding
        $changes = $this->changes;

        if (is_string($changes)) {
            // If it's a JSON string, decode it
            $changes = json_decode($changes, true) ?: null;
        } elseif (is_array($changes)) {
            // If it's already an array, keep it as is
            $changes = $changes;
        } else {
            $changes = null;
        }

        return [
            'id'               => $this->id,
            'detail'           => $this->detail,
            'changes'          => $changes,
            'actionType'       => $this->action_type,
            'modelName'        => $this->model_name,
            'modelId'          => $this->model_id,
            'createdBy'        => $this->created_by,
            'createdByName'    => $this->created_by_name ?? null,
            'createdAt'        => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
            'createdAtId'      => $this->created_at_id ?? ($this->created_at ? Carbon::parse($this->created_at)->timestamp : null),
            'ipAddress'        => $this->ip_address ?? null,
            'browser'          => $this->browser ?? null,
            'device'           => $this->device ?? null,
            'operatingSystem'  => $this->os ?? $this->operating_system ?? null,
            'userAgent'        => $this->user_agent ?? null,
        ];
    }
}
