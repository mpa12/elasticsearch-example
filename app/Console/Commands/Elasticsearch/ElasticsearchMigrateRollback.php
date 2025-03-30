<?php

namespace App\Console\Commands\Elasticsearch;

use App\Models\ElasticsearchMigration as ElasticsearchMigrationModel;
use App\Parents\Elasticsearch\ElasticsearchMigration;
use App\Repositories\ElasticsearchMigrationRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use Illuminate\Support\Collection;

class ElasticsearchMigrateRollback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:migrate:rollback {--step=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rolling back elasticsearch migrations';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Получение миграций для отката
        $migrationsToRollback = $this->migrationsToRollback();

        if (!$migrationsToRollback->count()) {
            (new Info($this->output))->render('Nothing to rollback.');
            return;
        }

        (new Info($this->output))->render('Rolling back migrations.');

        // Откат миграций
        $migrationsToRollback->map(function (ElasticsearchMigrationModel $migration) {
            $filename = $migration->migration;
            $this->components->twoColumnDetail($filename, '<fg=yellow>RUNNING</>');

            try {
                // Откат миграции
                $this->downMigration($migration);

                // Удаление миграции из списка запущенных
                $migration->delete();
            } catch (Exception $exception) {
                $this->components->twoColumnDetail($filename, '<fg=red>FAIL</>');
                throw $exception;
            }

            $this->components->twoColumnDetail($filename, '<fg=green>DONE</>');
        });
    }

    /**
     * @return Collection
     */
    private function migrationsToRollback(): Collection
    {
        $step = $this->option('step');

        $migrationsToRollback = app(ElasticsearchMigrationRepository::class)->migrationsToRollback($step);

        return $migrationsToRollback;
    }

    /**
     * @param ElasticsearchMigrationModel $migration
     *
     * @return void
     *
     * @throws Exception
     */
    private function downMigration(ElasticsearchMigrationModel $migration): void
    {
        // Получение пути до миграции
        $file = base_path("elasticsearch/migrations/$migration->migration");

        // Получение объекта миграции
        $migration = include $file;

        if (!$migration instanceof ElasticsearchMigration) {
            throw new Exception("Migration file '$file' must extend " . ElasticsearchMigration::class);
        }

        // Откат миграции
        $migration->down();
    }
}
