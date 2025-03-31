<?php

namespace App\Jobs\Elastic;

use App\Dto\Elastic\BulkItemDto;
use App\Enums\Elastic\BukItemTypeEnum;
use App\Services\Elastic\ElasticService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class ElasticReindexJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly array  $modelIds,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Model $model */
        $model = new $this->modelClass;
        $keyName = $model->getKeyName();

        $query = $this->modelClass::query()
            ->whereIn($keyName, $this->modelIds)
            ->needsReindex();

        $bulkCollection = (clone $query)->get()->map(function (Model $model) {
            $type = empty($model->deleted_at) ? BukItemTypeEnum::INDEX : BukItemTypeEnum::DELETE;

            return new BulkItemDto($type, $model);
        });

        app(ElasticService::class)->bulkIndexing($bulkCollection);

        $query->update(['needs_reindex' => false]);
    }
}
