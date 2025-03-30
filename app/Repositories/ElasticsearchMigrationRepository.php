<?php

namespace App\Repositories;

use App\Models\ElasticsearchMigration;
use App\Models\ElasticsearchMigration as ElasticsearchMigrationModel;
use App\Parents\Repositories\Repository as ParentRepository;
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

    /**
     * @inheritDoc
     */
    protected function getModelClass(): string
    {
        return ElasticsearchMigration::class;
    }
}
