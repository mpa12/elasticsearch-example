<?php

namespace App\Observers;

use App\Services\Elasticsearch\ElasticsearchService;

class ElasticsearchObserver
{
    public function __construct(
        private readonly ElasticsearchService $elasticsearchService,
    )
    {
        // ...
    }

    public function saved($model): void
    {
        $models = collect([$model]);
        $this->elasticsearchService->bulkIndexing($models);
    }

    public function deleted($model): void
    {
        $models = collect([$model]);
        $this->elasticsearchService->bulkIndexing($models);
    }
}
