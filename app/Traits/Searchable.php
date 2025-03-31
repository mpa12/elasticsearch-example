<?php

namespace App\Traits;

use App\Observers\Elastic\ElasticObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Searchable
{
    use SoftDeletes;

    public static function bootSearchable(): void
    {
        if (config('services.search.enabled')) {
            static::observe(ElasticObserver::class);
        }
    }

    public function scopeNeedsReindex(Builder $query): void
    {
        $query->where('needs_reindex', true);
    }

    abstract public function toElasticsearchDocumentArray(): array;
    abstract public function getSearchableFields(): array;
}
