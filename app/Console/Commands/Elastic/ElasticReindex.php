<?php

namespace App\Console\Commands\Elastic;

use App\Jobs\Elastic\ElasticReindexJob;
use App\Models\Post;
use App\Services\Elastic\ElasticService;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\TwoColumnDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ElasticReindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for indexing data for ElasticSearch';

    public function __construct(
        protected readonly ElasticService $elasticsearchService,
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

        // Модели для индексации
        $models = collect([
            Post::class,
        ]);

        // Получение записей моделей для индексации
        $models->map(fn(string $className) => $this->reindex($className));
    }

    private function reindex(string $className): void
    {
        /** @var Model $model */
        $model = new $className;
        $keyName = $model->getKeyName();

        /** @var Builder $query */
        $query = $className::query()->select([$keyName, 'needs_reindex'])->needsReindex();

        (clone $query)->chunk(20_000, function (Collection $models) use ($className, $keyName) {
            // Получение id объектов индексации
            $chunkModelIds = $models->pluck($keyName);

            // Отправка объектов на индексацию
            ElasticReindexJob::dispatch($className, $chunkModelIds->toArray());
        });

        $count = $query->count();

        (new TwoColumnDetail($this->output))->render($className, "<fg=gray>$count entries</> <fg=green>DONE</>");
    }
}
