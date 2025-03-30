<?php

namespace App\Console\Commands\Elasticsearch;

use App\Dto\Elasticsearch\BulkItemDto;
use App\Enums\Elasticsearch\BukItemTypeEnum;
use App\Models\Post;
use App\Services\Elasticsearch\ElasticsearchService;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\TwoColumnDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ElasticsearchReindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for indexing data for ElasticSearch';

    public function __construct(
        protected readonly ElasticsearchService $elasticsearchService,
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        (new Info($this->output))->render('Indexation has start');

        $bulkItems = collect();

        // Модели для индексации
        $models = collect([
            Post::class,
        ]);

        // Получение записей моделей для индексации
        $models->map(fn(string $className) => $this->reindex($className, $bulkItems));

        // Индексация через elasticsearch
        $this->elasticsearchService->bulkIndexing($bulkItems);
    }

    private function reindex(string $className, Collection $bulkItems): void
    {
        (new TwoColumnDetail($this->output))->render($className, '<fg=yellow>RUNNING</>');

        /** @var Builder $query */
        $query = $className::query()->needsReindex();

        // Количество записей на индексацию
        $count = $query->count();

        // Получение объектов индексации
        (clone $query)->chunk(1_000, function (Collection $models) use ($bulkItems) {
            $bulkChunkItems = $models->map(function (Model $model) {
                $type = empty($model->deleted_at) ? BukItemTypeEnum::INDEX : BukItemTypeEnum::DELETE;

                return new BulkItemDto($type, $model);
            });

            $bulkItems->merge($bulkChunkItems);
        });

        // Обновление поля needs_reindex
        $query->update(['needs_reindex' => false]);

        (new TwoColumnDetail($this->output))->render($className, "<fg=gray>$count entries</> <fg=green>DONE</>");
    }
}
