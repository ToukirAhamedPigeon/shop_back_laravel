<?php

namespace Modules\Shared\Application\Resources\UserLog;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class UserLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'detail'           => $this->detail,
            'changes'          => $this->changes ? json_decode($this->changes, true) : null,
            'actionType'       => $this->action_type,
            'modelName'        => $this->model_name,
            'modelId'          => $this->model_id,
            'createdBy'        => $this->created_by,
            'createdByName'    => $this->created_by_name ?? null,
            'createdAt'        => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
            'createdAtId'      => $this->created_at ? Carbon::parse($this->created_at)->timestamp : null,
            'ipAddress'        => $this->ip_address ?? null,
            'browser'          => $this->browser ?? null,
            'device'           => $this->device ?? null,
            'operatingSystem'  => $this->os ?? null,
            'userAgent'        => $this->user_agent ?? null,
        ];
    }
}
