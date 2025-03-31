<?php

namespace App\Repositories;

use App\Models\ElasticMigration;
use App\Models\ElasticMigration as ElasticsearchMigrationModel;
use App\Parents\Repositories\Repository as ParentRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ElasticMigrationRepository extends ParentRepository
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
        return ElasticMigration::class;
    }
}
