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
