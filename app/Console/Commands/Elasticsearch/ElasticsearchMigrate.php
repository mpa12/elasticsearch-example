<?php

namespace App\Console\Commands\Elasticsearch;

use App\Parents\Elasticsearch\ElasticsearchMigration;
use App\Models\ElasticsearchMigration as ElasticsearchMigrationModel;
use App\Repositories\ElasticsearchMigrationRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use Illuminate\Support\Collection;
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
        $migrations = $this->migrationsToUp();
        // TODO: Сделать команду для отката миграций

        if (!$migrations->count()) {
            (new Info($this->output))->render('Nothing to migrate.');
            return;
        }

        (new Info($this->output))->render('Running migrations.');

        $migrations->map(function (string $path) {
            $filename = basename($path);
            $this->components->twoColumnDetail($filename, '<fg=yellow>RUNNING</>');
            try {
                $this->upMigration($path);
                ElasticsearchMigrationModel::create(['migration' => $filename]);
            } catch (Exception $exception) {
                $this->components->twoColumnDetail($filename, '<fg=red>FAIL</>');
                throw $exception;
            }
            $this->components->twoColumnDetail($filename, '<fg=green>DONE</>');
        });
    }

    private function migrationsToUp(): Collection
    {
        $files = collect(File::glob(base_path('elasticsearch/migrations/*.php')));

        $completedMigrations = app(ElasticsearchMigrationRepository::class)->completedMigrations();

        $migrationsToUp = $files->filter(function (string $path) use ($completedMigrations) {
            $filename = basename($path);
            return !$completedMigrations->contains($filename);
        });

        return $migrationsToUp;
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
