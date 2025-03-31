<?php

namespace App\Services\Elastic;

use App\Parents\Services\Service as ParentService;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Support\Collection;

class ElasticService extends ParentService
{
    public function __construct(
        protected Client $elasticsearchClient,
    )
    {
        parent::__construct();
    }

    /**
     * Создание mappings и settings для индекса
     *
     * @param string $modelClass
     *
     * @return void
     *
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index_management.html#_get_settings_api
     */
    public function createIndices(string $modelClass): void
    {
        $model = new $modelClass();

        $params = [
            'index' => $model->getTable(),
            'body' => [
                'settings' => $model::getElasticsearchIndexSettings(),
                'mappings' => $model::getElasticsearchIndexMappings(),
            ],
        ];

        $this->elasticsearchClient->indices()->create($params);
    }

    /**
     * Обновление mappings и settings для индекса, либо создание при отсутствии
     *
     * @param string $modelClass
     *
     * @return void
     *
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function updateOrCreateIndices(string $modelClass): void
    {
        $settingsExists = !empty($this->getIndexSettings($modelClass));
        $mappingsExists = !empty($this->getIndexMappings($modelClass));

        if (!$settingsExists && !$mappingsExists) {
            $this->createIndices($modelClass);
        } else {
            $this->updateIndexSettings($modelClass);
            $this->updateIndexMappings($modelClass);
        }
    }

    /**
     * Обновление settings
     *
     * @param string $modelClass
     *
     * @return void
     *
     * @throws ClientResponseException
     * @throws ServerResponseException
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index_management.html#_get_settings_api
     */
    public function updateIndexSettings(string $modelClass): void
    {
        $model = new $modelClass();

        $params = [
            'index' => $model->getTable(),
            'body' => [
                'settings' => $model::getElasticsearchIndexSettings(),
            ],
        ];

        $this->elasticsearchClient->indices()->putSettings($params);
    }

    /**
     * Получение settings
     *
     * @param string $modelClass
     *
     * @return array
     *
     * @throws ServerResponseException
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index_management.html#_get_settings_api
     */
    public function getIndexSettings(string $modelClass): array
    {
        $model = new $modelClass();

        $params = ['index' => $model->getTable()];

        try {
            return $this->elasticsearchClient
                ->indices()
                ->getSettings($params)
                ->asArray();
        } catch (ClientResponseException) {
            return [];
        }
    }

    /**
     * Обновление mappings
     *
     * @param string $modelClass
     *
     * @return void
     *
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index_management.html#_put_mappings_api
     */
    public function updateIndexMappings(string $modelClass): void
    {
        $model = new $modelClass();

        $params = [
            'index' => $model->getTable(),
            'body' => $model::getElasticsearchIndexMappings(),
        ];

        $this->elasticsearchClient->indices()->putMapping($params);
    }

    /**
     * Получение mappings
     *
     * @param string $modelClass
     *
     * @return array
     *
     * @throws ServerResponseException
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index_management.html#_get_mappings_api
     */
    public function getIndexMappings(string $modelClass): array
    {
        $model = new $modelClass();

        $params = ['index' => $model->getTable()];

        try {
            return $this->elasticsearchClient
                ->indices()
                ->getMapping($params)
                ->asArray();
        } catch (ClientResponseException) {
            return [];
        }
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
        app(ElasticBulkService::class)->bulkIndexing($bulkCollection);
    }

    /**
     * Закрытие индекса
     *
     * @param string $modelClass
     *
     * @return void
     *
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function closeIndex(string $modelClass): void
    {
        $model = new $modelClass();

        $params = ['index' => $model->getTable()];

        $this->elasticsearchClient->indices()->close($params);
    }

    /**
     * Открытие индекса
     *
     * @param string $modelClass
     *
     * @return void
     *
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function openIndex(string $modelClass): void
    {
        $model = new $modelClass();

        $params = ['index' => $model->getTable()];

        $this->elasticsearchClient->indices()->open($params);
    }
}
