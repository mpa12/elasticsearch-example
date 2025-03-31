<?php

namespace App\Observers\Elastic;

readonly class ElasticObserver
{
    public function saving($model): void
    {
        $model->needs_reindex = true;
    }

    public function deleting($model): void
    {
        $model->needs_reindex = true;
    }
}
