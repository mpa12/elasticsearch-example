<?php

namespace App\Observers;

use App\Dto\Elasticsearch\BulkItemDto;
use App\Enums\Elasticsearch\BukItemTypeEnum;
use App\Services\Elasticsearch\ElasticsearchService;

readonly class ElasticsearchObserver
{
    public function __construct(
        private ElasticsearchService $elasticsearchService,
    )
    {
        // ...
    }

    public function saved($model): void
    {
        $bulkItem = new BulkItemDto(BukItemTypeEnum::INDEX, $model);
        $bulkCollection = collect([$bulkItem]);
        $this->elasticsearchService->bulkIndexing($bulkCollection);
    }

    public function deleted($model): void
    {
        $bulkItem = new BulkItemDto(BukItemTypeEnum::DELETE, $model);
        $bulkCollection = collect([$bulkItem]);
        $this->elasticsearchService->bulkIndexing($bulkCollection);
    }
}
