<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponseTrait;
use App\Http\Controllers\Concerns\ResolvesCurrentActor;

abstract class Controller
{
    use ApiResponseTrait, ResolvesCurrentActor;
}

