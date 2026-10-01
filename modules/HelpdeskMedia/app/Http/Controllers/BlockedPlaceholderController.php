<?php

namespace Modules\HelpdeskMedia\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Imagen que sustituye a un adjunto bloqueado por el antivirus. Un SVG servido
 * en <img> no ejecuta scripts; además va con nosniff y CSP sandbox.
 */
class BlockedPlaceholderController extends Controller
{
    public function __invoke(): Response
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="120" viewBox="0 0 240 120">'
            .'<rect width="240" height="120" rx="8" fill="#f8d7da" stroke="#dc3545"/>'
            .'<text x="120" y="55" font-family="sans-serif" font-size="14" fill="#842029" text-anchor="middle">Adjunto bloqueado</text>'
            .'<text x="120" y="75" font-family="sans-serif" font-size="12" fill="#842029" text-anchor="middle">por el antivirus</text>'
            .'</svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
