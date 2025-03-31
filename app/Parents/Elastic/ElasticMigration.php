<?php

namespace App\Parents\Elastic;

use App\Services\Elastic\ElasticService;
use Exception;

abstract class ElasticMigration
{
    protected ElasticService $service;

    public function __construct()
    {
        $this->service = app(ElasticService::class);
    }

    /**
     * Метод для применения миграции Elastic.
     *
     * В этом методе выполняется код, который изменяет индексы, их настройки или данные внутри индексов в Elastic.
     * Для работы с Elasticsearch используется свойство $service, которое является экземпляром
     * класса \App\Services\Elastic\ElasticService.
     *
     * Миграции Elastic работают аналогично миграциям базы данных, но с определёнными
     * особенностями и ограничениями по функционалу. Например, вы можете изменять как структуру
     * и настройки индексов, так и данные, хранящиеся в этих индексах.
     *
     * Основные команды для работы с миграциями Elastic:
     * - `artisan elastic:migrate` — Применяет все миграции.
     * - `artisan elastic:migrate:rollback` — Откатывает последнюю миграцию.
     *
     * @throws Exception
     * @return void
     */
    abstract public function up(): void;

    /**
     * Метод для отката миграции Elastic.
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
