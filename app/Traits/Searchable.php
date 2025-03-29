<?php

namespace App\Traits;

use App\Observers\ElasticsearchObserver;
use Elastic\Elasticsearch\Client;

trait Searchable
{
    public static function bootSearchable(): void
    {
        if (config('services.search.enabled')) {
            static::observe(ElasticsearchObserver::class);
        }
    }

    // TODO: Сделать создание индексов через миграции
    public function elasticsearchIndexCreate(Client $elasticsearchClient): void
    {
        $elasticsearchClient->indices()->create([
            'index' => $this->getTable(),
            'body' => [
                'settings' => $this::getElasticsearchIndexSettings(),
                'mappings' => $this::getElasticsearchIndexMappings(),
            ],
        ]);
    }

    // TODO: Сделать индексацию через ElasticsearchService
    public function elasticsearchIndex(Client $elasticsearchClient): void
    {
        $elasticsearchClient->index([
            'index' => $this->getTable(),
            'type' => '_doc',
            'id' => $this->getKey(),
            'body' => $this->toElasticsearchDocumentArray(),
        ]);
    }

    public function elasticsearchDelete(Client $elasticsearchClient): void
    {
        $elasticsearchClient->delete([
            'index' => $this->getTable(),
            'type' => '_doc',
            'id' => $this->getKey(),
        ]);
    }

    abstract public function toElasticsearchDocumentArray(): array;
    abstract public function getSearchableFields(): array;

    // TODO: Вынести в миграции
    abstract public static function getElasticsearchIndexSettings(): array;
    // TODO: Вынести в миграции
    abstract public static function getElasticsearchIndexMappings(): array;
}
