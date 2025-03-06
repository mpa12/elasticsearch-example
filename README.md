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
