<?php

namespace App\Services\Elasticsearch;

use App\Dto\Elasticsearch\BulkItemDto;
use App\Enums\Elasticsearch\BukItemTypeEnum;
use App\Parents\Services\Service as ParentService;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Collection;

class ElasticsearchBulkService extends ParentService
{
    public function __construct(
        protected Client $elasticsearchClient,
    )
    {
        parent::__construct();
    }

    /**
     * Массовое индексирование
     *
     * @param Collection $bulkCollection
     *
     * @return void
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/indexing_documents.html#_bulk_indexing
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     */
    public function bulkIndexing(Collection $bulkCollection): void
    {
        $bulkCollection->chunk(1000)->map(function (Collection $chunk) {
            $params = ['body' => []];

            /** @var BulkItemDto $bulkItem */
            foreach ($chunk as $bulkItem) {
                $this->addBulkItem($params, $bulkItem);
            }

            $this->elasticsearchClient->bulk($params);
        });
    }

    private function addBulkItem(array &$params, BulkItemDto $bulkItem): void
    {
        switch ($bulkItem->type) {
            case BukItemTypeEnum::INDEX:
                $this->itemIndexType($params, $bulkItem);
                break;
            case BukItemTypeEnum::DELETE:
                $this->itemDeleteType($params, $bulkItem);
                break;
            case BukItemTypeEnum::CREATE:
                $this->itemCreateType($params, $bulkItem);
                break;
            case BukItemTypeEnum::UPDATE:
                $this->itemUpdateType($params, $bulkItem);
                break;
        }
    }

    private function itemIndexType(array &$params, BulkItemDto $bulkItem): void
    {
        $params['body'][] = [
            'index' => [
                '_index' => $bulkItem->model->getTable(),
                '_id' => $bulkItem->model->getKey()
            ]
        ];

        $params['body'][] = $bulkItem->model->toElasticsearchDocumentArray();
    }

    private function itemDeleteType(array &$params, BulkItemDto $bulkItem): void
    {
        $params['body'][] = [
            'delete' => [
                '_index' => $bulkItem->model->getTable(),
                '_id' => $bulkItem->model->getKey()
            ]
        ];
    }

    private function itemCreateType(array &$params, BulkItemDto $bulkItem): void
    {
        $params['body'][] = [
            'create' => [
                '_index' => $bulkItem->model->getTable(),
                '_id' => $bulkItem->model->getKey()
            ]
        ];

        $params['body'][] = $bulkItem->model->toElasticsearchDocumentArray();
    }

    private function itemUpdateType(array &$params, BulkItemDto $bulkItem): void
    {
        $params['body'][] = [
            'update' => [
                '_index' => $bulkItem->model->getTable(),
                '_id' => $bulkItem->model->getKey()
            ]
        ];

        $params['body'][] = $bulkItem->model->toElasticsearchDocumentArray();
    }
}
