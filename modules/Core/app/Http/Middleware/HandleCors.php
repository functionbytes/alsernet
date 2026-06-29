<?php

namespace Modules\Core\Http\Middleware;

use Fruitcake\Cors\CorsService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Middleware\HandleCors as Middleware;

class HandleCors extends Middleware
{
    public function __construct(Container $container, CorsService $cors)
    {
        parent::__construct($container, $cors);
    }
}
