<?php

namespace App\Repositories;

use App\Models\Post;
use App\Parents\Repositories\ElasticRepository;

class PostRepository extends ElasticRepository
{
    /**
     * @inheritDoc
     */
    protected function getModelClass(): string
    {
        return Post::class;
    }
}
