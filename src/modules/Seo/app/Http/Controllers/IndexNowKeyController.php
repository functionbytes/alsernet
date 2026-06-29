<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Seo\Services\IndexNowService;

class IndexNowKeyController extends Controller
{
    /**
     * Sirve `/{key}.txt` con el contenido exacto de la key.
     * IndexNow lo usa para verificar que el dueño del dominio autorizó
     * el envío de URLs.
     */
    public function __invoke(string $key, IndexNowService $service): Response
    {
        $expected = $service->key();

        if ($expected === '' || ! hash_equals($expected, $key)) {
            abort(404);
        }

        return response($expected, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
