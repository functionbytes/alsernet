<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Carrier extends Facade
{
protected static function getFacadeAccessor()
{
return 'carrier.service';
}
}
