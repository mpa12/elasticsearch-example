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
