<?php

namespace App\Models;

use App\Traits\Searchable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $content
 * @property boolean $needs_reindex
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'content',
        'needs_reindex',
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

    public static function getElasticsearchIndexSettings(): array
    {
        return [
            'analysis' => [
                'analyzer' => [
                    'custom_analyzer' => [
                        'type' => 'standard'
                    ]
                ]
            ]
        ];
    }

    public static function getElasticsearchIndexMappings(): array
    {
        return [
            'properties' => [
                'name' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 255
                        ]
                    ]
                ],
                'content' => [
                    'type' => 'text',
                    'analyzer' => 'standard'
                ]
            ]
        ];
    }
}
