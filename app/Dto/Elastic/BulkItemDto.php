<?php

namespace App\Dto\Elastic;

use App\Enums\Elastic\BukItemTypeEnum;
use App\Parents\Dto\Dto as ParentDto;
use Illuminate\Database\Eloquent\Model;

class BulkItemDto extends ParentDto
{
    public function __construct(
        public BukItemTypeEnum $type,
        public Model|null $model = null,
    )
    {
        parent::__construct();
    }
}
