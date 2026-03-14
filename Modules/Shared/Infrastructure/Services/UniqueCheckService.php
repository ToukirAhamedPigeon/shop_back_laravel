<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IUniqueCheckService;
use Modules\Shared\Application\Requests\Common\CheckUniqueRequest;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Modules\Shared\Infrastructure\Models\EloquentRole;
use Modules\Shared\Infrastructure\Models\EloquentPermission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UniqueCheckService implements IUniqueCheckService
{
    private const ALLOWED_MODELS = [
        'User' => EloquentUser::class,
        'Role' => EloquentRole::class,
        'Permission' => EloquentPermission::class,
    ];

    private array $columnCache = [];

    public function exists(CheckUniqueRequest $request): bool
    {
        try {
            // Validate model
            if (!isset(self::ALLOWED_MODELS[$request->model])) {
                throw new \InvalidArgumentException("Invalid model: {$request->model}");
            }

            $modelClass = self::ALLOWED_MODELS[$request->model];
            $model = new $modelClass();
            $table = $model->getTable();

            // Map field names to database column names
            $fieldName = $this->mapFieldName($request->fieldName);

            // Validate field exists
            if (!$this->fieldExistsInTable($table, $fieldName)) {
                throw new \InvalidArgumentException("Invalid field name: {$request->fieldName}");
            }

            // Build query
            $query = $modelClass::query()->where($fieldName, $request->fieldValue);

            // Handle except condition
            if (!empty($request->exceptFieldName) && !empty($request->exceptFieldValue)) {
                $exceptField = $this->mapFieldName($request->exceptFieldName);

                if (!$this->fieldExistsInTable($table, $exceptField)) {
                    throw new \InvalidArgumentException("Invalid except field name: {$request->exceptFieldName}");
                }

                $query->where($exceptField, '!=', $request->exceptFieldValue);
            }

            return $query->exists();

        } catch (\Exception $e) {
            Log::error('Unique check failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function existsAsync(CheckUniqueRequest $request): bool
    {
        return $this->exists($request);
    }

    private function fieldExistsInTable(string $table, string $fieldName): bool
    {
        $cacheKey = $table . '.' . $fieldName;

        if (isset($this->columnCache[$cacheKey])) {
            return $this->columnCache[$cacheKey];
        }

        $columns = Schema::getColumnListing($table);
        $exists = in_array(strtolower($fieldName), array_map('strtolower', $columns));

        $this->columnCache[$cacheKey] = $exists;
        return $exists;
    }

    private function mapFieldName(string $fieldName): string
    {
        return match($fieldName) {
            'Username' => 'username',
            'Email' => 'email',
            'MobileNo' => 'mobile_no',
            'NID' => 'nid',
            'Id' => 'id',
            default => strtolower($fieldName)
        };
    }
}
