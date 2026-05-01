<?php

namespace Modules\Shared\Application\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class BulkOperationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'success' => $this->resource['success'] ?? false,
            'message' => $this->resource['message'] ?? '',
            'totalCount' => $this->resource['totalCount'] ?? 0,
            'successCount' => $this->resource['successCount'] ?? 0,
            'failedCount' => $this->resource['failedCount'] ?? 0,
            'errors' => $this->when(isset($this->resource['errors']), function () {
                return BulkOperationErrorResource::collection($this->resource['errors'] ?? []);
            }, []),
        ];
    }
}

class BulkOperationErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['id'] ?? '',
            'error' => $this->resource['error'] ?? '',
        ];
    }
}
