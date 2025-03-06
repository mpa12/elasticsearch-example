<?php

namespace App\Console\Commands;

use App\Models\Post;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class ReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for indexing data for ElasticSearch';

    public function __construct(
        protected readonly Client $elasticsearch,
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Indexation has start');

        collect([
            Post::class,
        ])->map(fn(string $className) => $this->reindex($className));

        $this->info("\n\nDone");
    }

    private function reindex(string $className): void
    {
        $this->info("\nIndexing for $className");

        $this->withProgressBar($className::all(), function (Model $model) {
            $model->elasticsearchIndex($this->elasticsearch);
        });
    }
}
