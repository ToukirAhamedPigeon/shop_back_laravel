<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Services\IUniqueCheckService;
use Modules\Shared\Application\Requests\Common\CheckUniqueRequest;
use Modules\Shared\Infrastructure\Models\EloquentUser;
use Modules\Shared\Infrastructure\Models\EloquentRole;
use Modules\Shared\Infrastructure\Models\EloquentPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class UniqueCheckService implements IUniqueCheckService
{
    /**
     * Allowed models mapping
     */
    private const ALLOWED_MODELS = [
        'User' => EloquentUser::class,
        'Role' => EloquentRole::class,
        'Permission' => EloquentPermission::class,
    ];

    /**
     * Check if a value exists in a specific model field
     */
    public function exists(CheckUniqueRequest $request): bool
    {
        // Validate model
        if (!isset(self::ALLOWED_MODELS[$request->model])) {
            throw new \InvalidArgumentException("Invalid model: {$request->model}");
        }

        $modelClass = self::ALLOWED_MODELS[$request->model];

        // Validate field exists in model
        if (!$this->fieldExistsInModel($modelClass, $request->fieldName)) {
            throw new \InvalidArgumentException("Invalid field name: {$request->fieldName}");
        }

        // Convert value to proper type based on model field type
        $convertedValue = $this->convertToType($request->fieldValue, $modelClass, $request->fieldName);

        // Build query
        $query = $modelClass::query();

        // Add main condition
        $query->where($request->fieldName, $convertedValue);

        // Add except condition if provided
        if (!empty($request->exceptFieldName) && !empty($request->exceptFieldValue)) {
            // Validate except field exists
            if (!$this->fieldExistsInModel($modelClass, $request->exceptFieldName)) {
                throw new \InvalidArgumentException("Invalid except field name: {$request->exceptFieldName}");
            }

            $convertedExceptValue = $this->convertToType(
                $request->exceptFieldValue,
                $modelClass,
                $request->exceptFieldName
            );

            $query->where($request->exceptFieldName, '!=', $convertedExceptValue);
        }

        return $query->exists();
    }

    public function existsAsync(CheckUniqueRequest $request): bool
    {
        return $this->exists($request);
    }

    /**
     * Check if a field exists in the model's table
     */
    private function fieldExistsInModel(string $modelClass, string $fieldName): bool
    {
        /** @var Model $model */
        $model = new $modelClass();
        $table = $model->getTable();

        return Schema::hasColumn($table, $fieldName);
    }

    /**
     * Get the PHP type of a model field
     */
    private function getFieldType(string $modelClass, string $fieldName): string
    {
        /** @var Model $model */
        $model = new $modelClass();
        $casts = $model->getCasts();

        // Check if field is cast to a specific type
        if (isset($casts[$fieldName])) {
            return $casts[$fieldName];
        }

        // Check migration/table column type
        $table = $model->getTable();
        $columnType = DB::connection()->getDoctrineColumn($table, $fieldName)->getType()->getName();

        return match ($columnType) {
            'integer', 'bigint', 'smallint' => 'int',
            'decimal', 'float' => 'float',
            'boolean' => 'bool',
            'datetime', 'date', 'timestamp' => 'datetime',
            'guid', 'uuid' => 'string', // UUIDs are stored as strings in Laravel
            default => 'string',
        };
    }

    /**
     * Convert string value to the appropriate type based on model field
     */
    private function convertToType(string $value, string $modelClass, string $fieldName): mixed
    {
        if (empty($value)) {
            return null;
        }

        $type = $this->getFieldType($modelClass, $fieldName);

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'decimal' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'datetime', 'date', 'timestamp' => $value, // Leave as string for DB comparison
            'json', 'array' => json_decode($value, true),
            default => (string) $value,
        };
    }

    /**
     * Get the model class from model name
     */
    public static function getModelClass(string $modelName): ?string
    {
        return self::ALLOWED_MODELS[$modelName] ?? null;
    }

    /**
     * Check if a model is allowed
     */
    public static function isModelAllowed(string $modelName): bool
    {
        return isset(self::ALLOWED_MODELS[$modelName]);
    }
}
