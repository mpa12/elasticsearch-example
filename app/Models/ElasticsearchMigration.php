<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $migration
 */
class ElasticsearchMigration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'migration',
    ];
}
