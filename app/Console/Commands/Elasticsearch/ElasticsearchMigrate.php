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
    protected $description = 'Launching elasticsearch migrations';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        // Получение миграций, которые надо запустить
        $migrations = $this->migrationsToUp();

        if (!$migrations->count()) {
            (new Info($this->output))->render('Nothing to migrate.');
            return;
        }

        (new Info($this->output))->render('Running migrations.');

        // Запуск миграций
        $migrations->map(function (string $path) {
            $filename = basename($path);
            $this->components->twoColumnDetail($filename, '<fg=yellow>RUNNING</>');

            try {
                // Запуск миграции
                $this->upMigration($path);

                // Сохранение информации об успешном запуске миграции
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
        // Получение всех файлов с миграциями
        $files = collect(File::glob(base_path('elasticsearch/migrations/*.php')));

        // Получение завершенных миграций
        $completedMigrations = app(ElasticsearchMigrationRepository::class)->completedMigrations();

        // Получение миграций, которые еще не выполнялись
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
        // Получение объекта миграции
        $migration = include $file;

        if (!$migration instanceof ElasticsearchMigration) {
            throw new Exception("Migration file '$file' must extend " . ElasticsearchMigration::class);
        }

        // Запуск миграции
        $migration->up();
    }
}
