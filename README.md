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
        volumes:
            - elasticsearch:/usr/share/elasticsearch/data
    # ...
volumes:
    # ...
    elasticsearch:
        driver: local
    # ...
```

По итогу файл `docker-compose.yml` будет выглядеть следующим образом:

```yaml
services:
    laravel.test:
        build:
            context: './vendor/laravel/sail/runtimes/8.4'
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP}'
        image: 'sail-8.4/app'
        extra_hosts:
            - 'host.docker.internal:host-gateway'
        ports:
            - '${APP_PORT:-80}:80'
            - '${VITE_PORT:-5173}:${VITE_PORT:-5173}'
        environment:
            WWWUSER: '${WWWUSER}'
            LARAVEL_SAIL: 1
            XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
            XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
            IGNITION_LOCAL_SITES_PATH: '${PWD}'
        volumes:
            - '.:/var/www/html'
        networks:
            - sail
        depends_on:
            - pgsql
    pgsql:
        image: 'postgres:17'
        ports:
            - '${FORWARD_DB_PORT:-5432}:5432'
        environment:
            PGPASSWORD: '${DB_PASSWORD:-secret}'
            POSTGRES_DB: '${DB_DATABASE}'
            POSTGRES_USER: '${DB_USERNAME}'
            POSTGRES_PASSWORD: '${DB_PASSWORD:-secret}'
        volumes:
            - 'sail-pgsql:/var/lib/postgresql/data'
            - './vendor/laravel/sail/database/pgsql/create-testing-database.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql'
        networks:
            - sail
        healthcheck:
            test:
                - CMD
                - pg_isready
                - '-q'
                - '-d'
                - '${DB_DATABASE}'
                - '-U'
                - '${DB_USERNAME}'
            retries: 3
            timeout: 5s
    elasticsearch:
        image: elasticsearch:8.17.0
        ports:
            - "9200:9200"
            - "9300:9300"
        environment:
            - discovery.type=single-node
            - xpack.security.enabled=false
        volumes:
            - elasticsearch:/usr/share/elasticsearch/data
networks:
    sail:
        driver: bridge
volumes:
    sail-pgsql:
        driver: local
    elasticsearch:
        driver: local
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

Если все сделано правильно, то по итогу в БД должна появиться табличка `posts`, внутри которой должно быть 50 записей:

![img.png](/docs/images/img.png)

## 6. Установка библиотеки ElasticSearch

Установим пакет ElasticSearch через Composer:

```shell
./vendor/bin/sail composer require elasticsearch/elasticsearch
```
