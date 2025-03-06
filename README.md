## 1. Установка Laravel

```shell
curl -s "https://laravel.build/elasticsearch-example?with=pgsql" | bash
```

## 2. Добавляем ElasticSearch в Docker

В файле `docker-compose.yml` добавляем контейнер с ElasticSearch:

```yaml
services:
    # ...
    elasticsearch:
        image: elasticsearch:8.17.0
        ports:
            - "9200:9200"
            - "9300:9300"
        environment:
            - discovery.type=single-node
            - xpack.security.enabled=false
            - ES_JAVA_OPTS=-Xms512m -Xmx512m
        volumes:
            - elasticsearch:/usr/share/elasticsearch/data
        networks:
            - sail
    # ...
volumes:
    # ...
    elasticsearch:
        driver: local
    # ...
```

Теперь можем запустить наш Docker через команду:

```shell
./vendor/bin/sail up -d
```

## 3. Настройка .env

В файлы `.env` и `.env.example` добавляем переменные для ElasticSearch:

```
ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_HOSTS="elasticsearch:9200"
```

## 4. Настройки в config/services.php

Добавляем конфигурацию ElasticSearch в `config/services.php`:

```php
<?php

return [
    // ...

    'search' => [
        'enabled' => env('ELASTICSEARCH_ENABLED', false),
        'hosts' => explode(',', env('ELASTICSEARCH_HOSTS', 'elasticsearch:9200')),
    ],

    // ...
];
```

## 5. Создание модели Post, по который будем делать поиск

Запустим команду для создания модели сразу с миграцией и фабрикой:

```shell
./vendor/bin/sail artisan make:model Post -mf
```

В модели будет два поля `name` и `content`.

Перейдем в модель `Post` и добавим `$fillable`:

```php
# app/Models/Post.php

<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $content
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'content',
    ];
}
```

Сделаем изменения в миграции:

```php
# database/migrations/..._create_posts_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

Перейдем в `PostFactory` для настройки создания фейковых данных:

```php
# database/factories/PostFactory.php

<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(5, true),
            'content' => $this->faker->text(),
        ];
    }
}
```

В `DatabaseSeeder` сделаем запуск создания `Post`:

```php
# database/seeders/DatabaseSeeder.php

<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Post::factory(50)->create();
    }
}
```

Теперь можно запустить миграции вместе с нашим сидером:

```shell
./vendor/bin/sail artisan migrate --seed
```

Если все сделано правильно,
то в результате в БД должна появиться табличка `posts`,
внутри которой должно быть 50 записей:

![img.png](/docs/images/img.png)

## 6. Установка библиотеки ElasticSearch

Установим пакет ElasticSearch через Composer:

```shell
./vendor/bin/sail composer require elasticsearch/elasticsearch
```

## 7. Регистрация ElasticSearch в AppServiceProvider

Добавим регистрацию клиента ElasticSearch в `AppServiceProvider`:

```php
# app/Providers/AppServiceProvider.php

<?php

namespace App\Providers;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerSearchClient();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    private function registerSearchClient(): void
    {
        $this->app->bind(Client::class, function ($app) {
            return ClientBuilder::create()
                ->setHosts($app['config']->get('services.search.hosts'))
                ->build();
        });
    }
}
```

## 8. Создание трейта Searchable

Добавим в проект трей `Searchable`, который будет использоваться в моделях.
Он позволит автоматически индексировать данные в ElasticSearch:

```php
# app/Traits/Searchable.php

<?php

namespace App\Traits;

use Elastic\Elasticsearch\Client;

trait Searchable
{
    public function elasticsearchIndex(Client $elasticsearchClient): void
    {
        $elasticsearchClient->index([
            'index' => $this->getTable(),
            'type' => '_doc',
            'id' => $this->getKey(),
            'body' => $this->toElasticsearchDocumentArray(),
        ]);
    }

    public function elasticsearchDelete(Client $elasticsearchClient): void
    {
        $elasticsearchClient->delete([
            'index' => $this->getTable(),
            'type' => '_doc',
            'id' => $this->getKey(),
        ]);
    }

    abstract public function toElasticsearchDocumentArray(): array;
    abstract public function getSearchableFields(): array;
}
```

Сделаем использование `Searchable` в модели `Post`:

```php
# app/Models/Post.php

<?php

namespace App\Models;

use App\Traits\Searchable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $content
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'content',
    ];

    public function toElasticsearchDocumentArray(): array
    {
        return $this->toArray();
    }

    public function getSearchableFields(): array
    {
        return [
            'name',
            'content',
        ];
    }
}
```

## 9. Создание ElasticsearchObserver

Запустим команду для создания `ElasticsearchObserver`:

```shell
./vendor/bin/sail artisan make:observer ElasticsearchObserver
```

Перейдем в созданный файл и внесем изменения:

```php
# app/Observers/ElasticsearchObserver.php

<?php

namespace App\Observers;

use Elastic\Elasticsearch\Client;

class ElasticsearchObserver
{
    public function __construct(private Client $elasticsearchClient)
    {
        // ...
    }

    public function saved($model): void
    {
        $model->elasticSearchIndex($this->elasticsearchClient);
    }

    public function deleted($model): void
    {
        $model->elasticSearchDelete($this->elasticsearchClient);
    }
}
```

Теперь, когда в моделях используется этот наблюдатель,
данные будут индексироваться в ElasticSearch при их создании или обновлении.
При удалении индексация будет очищена.

Сделаем использование `ElasticsearchObserver` в модели `Post` через трейт `Searchable`.
Для этого в `Searchable` нужно добавить новый метод `bootSearchable`:

```php
# app/Traits/Searchable.php

<?php

namespace App\Traits;

use App\Observers\ElasticsearchObserver;
use Elastic\Elasticsearch\Client;

trait Searchable
{
    // ...

    public static function bootSearchable(): void
    {
        if (config('services.search.enabled')) {
            static::observe(ElasticsearchObserver::class);
        }
    }

    // ...
}
```

Так же понадобится вызов `bootSearchable`, его сделаем в `AppServiceProvider`:

```php
# app/Providers/AppServiceProvider.php

<?php

namespace App\Providers;

use App\Models\Post;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // ...

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootSearchable();
    }

    private function bootSearchable(): void
    {
        Post::bootSearchable();
    }
    
    // ...
}
```

## 10. Создание репозиториев для поиска

Создадим два базовых репозитория `Repository` и `ElasticsearchRepository`,
и 1 репозиторий для модели `Post`:

```php
# app/Parents/Repositories/Repository.php

<?php

namespace App\Parents\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

abstract class Repository
{
    /**
     * @var Model $model
     */
    protected Model $model;

    public function __construct()
    {
        $this->model = app($this->getModelClass());
    }

    /**
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * @return Model|Application|mixed
     */
    protected function startConditions(): mixed
    {
        return clone $this->model;
    }
}
```

```php
# app/Parents/Repositories/ElasticsearchRepository.php

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
```

Репозиторий для модели `Post`:

```php
# app/Repositories/PostRepository.php

<?php

namespace App\Repositories;

use App\Models\Post;
use App\Parents\Repositories\ElasticsearchRepository;

class PostRepository extends ElasticsearchRepository
{
    /**
     * @inheritDoc
     */
    protected function getModelClass(): string
    {
        return Post::class;
    }
}
```

## 11. Команда для запуска индексации данных

Через artisan создадим команду для индексации данных для ElasticSearch:

```shell
./vendor/bin/sail artisan make:command ReindexCommand --command=search:reindex
```

Перейдем в файл команды `ReindexCommand` и внесем правки:

```php
# app/Console/Commands/ReindexCommand.php

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
```

Запустим индексацию:

```shell
./vendor/bin/sail artisan search:reindex
```

Если индексация прошла без ошибок, то можно приступать к следующему пункту.

Проверить, есть ли данные в ElasticSearch можно через приложение Elasticvue.

![img.png](/docs/images/img2.png)

## 12. Пример поиска

```php
# routes/web.php

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $data = app(\App\Repositories\PostRepository::class)
        ->search('vero')
        ->get();
    dd($data->toArray());
});
```

Результат поиска:

![img.png](/docs/images/img3.png)
