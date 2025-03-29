<?php

use App\Parents\Elasticsearch\ElasticsearchMigration;

return new class extends ElasticsearchMigration
{
    public function up(): void
    {
        // Здесь размещается код, который будет выполняться при запуске команды elasticsearch:migrate
    }

    public function down(): void
    {
        // Здесь размещается код, который будет выполняться при запуске команды elasticsearch:migrate:rollback
    }
};
