<?php

namespace App\Models;

use App\Traits\Searchable;
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
    use Searchable;

    protected $fillable = [
        'name',
        'content',
    ];

    public function toElasticsearchDocumentArray(): array
    {
        return $this->toArray();
    }

    public function getSearchableFields(): array
    {
        return [
            'name',
            'content',
        ];
    }
}
