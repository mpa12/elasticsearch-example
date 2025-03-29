<?php

namespace App\Console\Commands\Elasticsearch;

use App\Parents\Elasticsearch\ElasticsearchMigration;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ElasticsearchMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        $files = File::glob(base_path('elasticsearch/migrations/*.php'));
        // TODO: Сделать сохранение выполненных миграций в БД
        // TODO: Сделать команду для отката миграций

        foreach ($files as $file) {
            $this->components->twoColumnDetail("<fg=yellow>$file</>", '<fg=yellow>RUNNING</>');
            try {
                $this->upMigration($file);
            } catch (Exception $exception) {
                $this->components->twoColumnDetail("<fg=red>$file</>", '<fg=red>FAIL</>');
                throw $exception;
            }
            $this->components->twoColumnDetail("<fg=green>$file</>", '<fg=green>DONE</>');
        }
    }

    /**
     * @throws Exception
     */
    private function upMigration(string $file): void
    {
        $migration = include $file;

        if (!$migration instanceof ElasticsearchMigration) {
            throw new Exception("Migration file '$file' must extend " . ElasticsearchMigration::class);
        }

        $migration->up();
    }
}
