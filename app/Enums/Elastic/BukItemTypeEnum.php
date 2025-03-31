<?php

namespace App\Enums\Elastic;

enum BukItemTypeEnum
{
    case INDEX;
    case DELETE;
    case CREATE;
    case UPDATE;
}
