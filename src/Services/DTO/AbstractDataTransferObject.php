<?php

namespace App\Services\DTO;

use App\Contracts\Arrayable;
use Spatie\DataTransferObject\DataTransferObject;

class AbstractDataTransferObject extends DataTransferObject implements Arrayable {}
