<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\IUserTableCombinationRepository;
use Modules\Shared\Domain\Entities\UserTableCombination;
use Modules\Shared\Infrastructure\Models\EloquentUserTableCombination;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;



class EloquentUserTableCombinationRepository implements IUserTableCombinationRepository
{
    public function findByTableIdAndUserId(string $tableId, string $userId): ?UserTableCombination
    {
        $model = EloquentUserTableCombination::where('table_id', $tableId)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function create(UserTableCombination $entity): void
    {
        EloquentUserTableCombination::create([
            'id' => $entity->id ?: (string) Str::uuid(),
            'table_id' => $entity->tableId,
            'show_column_combinations' => $entity->showColumnCombinations,
            'user_id' => $entity->userId,
            'updated_by' => $entity->updatedBy,
            'updated_at' => $entity->updatedAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(UserTableCombination $entity): void
    {
        $model = EloquentUserTableCombination::find($entity->id);

        if (!$model) {
            throw new \Exception("UserTableCombination with ID {$entity->id} not found.");
        }

        // Convert PHP array to proper Postgres text[] literal
        $pgArray = '{' . implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $entity->showColumnCombinations)) . '}';

        // Use query builder raw expression to prevent double quoting
        DB::table('user_table_combinations')
            ->where('id', $entity->id)
            ->update([
                'show_column_combinations' => DB::raw("'" . $pgArray . "'::text[]"),
                'updated_by' => $entity->updatedBy,
                'updated_at' => $entity->updatedAt->format('Y-m-d H:i:s'),
            ]);
    }


    private function mapToEntity(EloquentUserTableCombination $model): UserTableCombination
    {
        return new UserTableCombination(
            id: $model->id,
            tableId: $model->table_id,
            showColumnCombinations: $model->show_column_combinations ?? [],
            userId: $model->user_id,
            updatedBy: $model->updated_by,
            updatedAt: new DateTimeImmutable($model->updated_at)
        );
    }
}
