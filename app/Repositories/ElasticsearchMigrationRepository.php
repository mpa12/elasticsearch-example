<?php

namespace App\Repositories;

use App\Models\ElasticsearchMigration;
use App\Models\ElasticsearchMigration as ElasticsearchMigrationModel;
use App\Parents\Repositories\Repository as ParentRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ElasticsearchMigrationRepository extends ParentRepository
{
    public function completedMigrations(): Collection
    {
        $completedMigrations = ElasticsearchMigrationModel::query()
            ->select(['migration'])
            ->get()
            ->pluck('migration');

        return $completedMigrations;
    }

    public function migrationsToRollback(int $step = null): \Illuminate\Database\Eloquent\Collection
    {
        $migrationsToRollback = ElasticsearchMigrationModel::query()
            ->select(['id', 'migration'])
            ->orderBy('id', 'desc')
            ->when($step, fn(Builder $query) => $query->limit($step))
            ->get();

        return $migrationsToRollback;
    }

    /**
     * @inheritDoc
     */
    protected function getModelClass(): string
    {
        return ElasticsearchMigration::class;
    }
}
