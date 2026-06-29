<?php

namespace App\Http\Api\V1;

use App\Http\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class BaseApiController extends Controller
{
    use ApiResponses;
    use AuthorizesRequests;
    use ValidatesRequests;
}
