<?php

namespace App\Parents\Repositories;

use Elastic\Elasticsearch\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

abstract class ElasticsearchRepository extends Repository
{
    private readonly Client $elasticsearch;

    public function __construct()
    {
        parent::__construct();

        $this->elasticsearch = app(Client::class);
    }

    public function search(string $searchText, Builder $query = null): Builder
    {
        $items = $this->searchOnElasticsearch($searchText);

        $collection = $this->buildCollection($items, $query);

        return $collection;
    }

    private function searchOnElasticsearch(string $searchText): array
    {
        $items = $this->elasticsearch->search([
            'index' => $this->model->getTable(),
            'type' => '_doc',
            'body' => [
                'query' => [
                    'multi_match' => [
                        'fields' => $this->model->getSearchableFields(),
                        'query' => $searchText,
                    ],
                ]
            ],
        ])->asArray();

        return $items;
    }

    private function buildCollection(array $items, Builder $query = null): Builder
    {
        $ids = Arr::pluck($items['hits']['hits'], '_id');

        $query = $query ?? $this->startConditions();
        $query = $query->whereIn($this->model->getKeyName(), $ids);

        return $query;
    }
}
