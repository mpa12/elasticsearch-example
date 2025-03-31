<?php

use App\Parents\Elastic\ElasticMigration;

return new class extends ElasticMigration
{
    public function up(): void
    {
        // TODO: Сделать создание индекса
        // Здесь размещается код, который будет выполняться при запуске команды elastic:migrate
    }

    public function down(): void
    {
        // TODO: Сделать удаление индекса
        // Здесь размещается код, который будет выполняться при запуске команды elastic:migrate:rollback
    }
};
