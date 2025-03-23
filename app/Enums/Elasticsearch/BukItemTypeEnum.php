<?php

namespace App\Enums\Elasticsearch;

enum BukItemTypeEnum
{
    case INDEX;
    case DELETE;
    case CREATE;
    case UPDATE;
}
