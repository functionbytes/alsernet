<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CountryApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->countries()]);
    }

    private function countries(): array
    {
        return [
            ['code' => 'AR', 'name' => 'Argentina'],
            ['code' => 'BO', 'name' => 'Bolivia'],
            ['code' => 'BR', 'name' => 'Brasil'],
            ['code' => 'CL', 'name' => 'Chile'],
            ['code' => 'CO', 'name' => 'Colombia'],
            ['code' => 'CR', 'name' => 'Costa Rica'],
            ['code' => 'CU', 'name' => 'Cuba'],
            ['code' => 'DO', 'name' => 'República Dominicana'],
            ['code' => 'EC', 'name' => 'Ecuador'],
            ['code' => 'SV', 'name' => 'El Salvador'],
            ['code' => 'GT', 'name' => 'Guatemala'],
            ['code' => 'HN', 'name' => 'Honduras'],
            ['code' => 'MX', 'name' => 'México'],
            ['code' => 'NI', 'name' => 'Nicaragua'],
            ['code' => 'PA', 'name' => 'Panamá'],
            ['code' => 'PY', 'name' => 'Paraguay'],
            ['code' => 'PE', 'name' => 'Perú'],
            ['code' => 'ES', 'name' => 'España'],
            ['code' => 'UY', 'name' => 'Uruguay'],
            ['code' => 'VE', 'name' => 'Venezuela'],
        ];
    }
}
