<?php

namespace App\Parents\Elasticsearch;

use App\Services\Elasticsearch\ElasticsearchService;
use Exception;

abstract class ElasticsearchMigration
{
    protected ElasticsearchService $service;

    public function __construct()
    {
        $this->service = app(ElasticsearchService::class);
    }

    /**
     * Метод для применения миграции Elasticsearch.
     *
     * В этом методе выполняется код, который изменяет индексы, их настройки или данные внутри индексов в Elasticsearch.
     * Для работы с Elasticsearch используется свойство $service, которое является экземпляром
     * класса \App\Services\Elasticsearch\ElasticsearchService.
     *
     * Миграции Elasticsearch работают аналогично миграциям базы данных, но с определёнными
     * особенностями и ограничениями по функционалу. Например, вы можете изменять как структуру
     * и настройки индексов, так и данные, хранящиеся в этих индексах.
     *
     * Основные команды для работы с миграциями Elasticsearch:
     * - `artisan elasticsearch:migrate` — Применяет все миграции.
     * - `artisan elasticsearch:migrate:rollback` — Откатывает последнюю миграцию.
     *
     * @throws Exception
     * @return void
     */
    abstract public function up(): void;

    /**
     * Метод для отката миграции Elasticsearch.
     *
     * В этом методе выполняется код, который отменяет изменения, сделанные в методе up().
     * Это важно для того, чтобы можно было вернуть систему к предыдущему состоянию, если
     * миграция не прошла успешно или если требуется откат изменений.
     *
     * @return void
     * @throws Exception
     */
    abstract public function down(): void;
}
