<?php

namespace Modules\Theme\Helpers;

/**
 * Set de iconos SVG propios para el riel de navegación (app-navbar-tabs).
 *
 * Estilo "trazo cercano": contorno redondeado de 1.9px (linecap/linejoin
 * round), esquinas generosas y relleno plano — elegido entre 3 propuestas
 * (ver artefacto de comparación) frente a un estilo técnico de línea recta
 * y uno de sello/silueta sólida.
 *
 * No dependen de Font Awesome ni de ningún icon font. Los dos tonos salen
 * de las variables CSS `--icon-stroke` (contorno) e `--icon-fill`
 * (relleno) — así el mismo SVG sirve tanto para el estado inactivo (negro
 * + gris claro) como para el activo (blanco sólido sobre el verde de
 * marca), cambiando solo esas dos variables.
 */
class NavIconHelper
{
    public static function render(string $key): string
    {
        $inner = self::paths()[$key] ?? self::paths()['dot'];

        return '<svg viewBox="0 0 24 24" fill="none" stroke="var(--icon-stroke, #000000)" stroke-width="1.9" '
            .'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
    }

    /**
     * @return array<string, string>
     */
    private static function paths(): array
    {
        return [
            'gauge' => '
                <path d="M4.5 16.8a7.5 7.5 0 1 1 15 0Z" fill="var(--icon-fill, #e4e4e7)" stroke="none" />
                <path d="M4.7 16.8a7.3 7.3 0 1 1 14.6 0" fill="none" />
                <path d="M12 16.6 14.7 12.1" />
                <circle cx="12" cy="16.8" r="1.5" fill="var(--icon-stroke, #000000)" stroke="none" />
            ',

            'wallet' => '
                <rect x="3" y="6.5" width="14" height="12" rx="3" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M3 10.5h14" />
                <circle cx="18" cy="7" r="3.6" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M18 5.3v3.4M16.3 7h3.4" />
            ',

            'mail' => '
                <rect x="3" y="5.8" width="18" height="12.4" rx="4" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M4.2 7.3 12 13 19.8 7.3" fill="none" />
            ',

            'users' => '
                <circle cx="9.2" cy="8.3" r="3" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M3.8 19c.3-3.4 2.6-5.6 5.4-5.6s5.1 2.2 5.4 5.6" fill="none" />
                <circle cx="16.8" cy="9.4" r="2.4" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M14.8 19c.2-2.6 1.8-4.3 4-4.3" fill="none" />
            ',

            'media' => '
                <rect x="7" y="3.5" width="14" height="11" rx="3" fill="var(--icon-fill, #e4e4e7)" />
                <rect x="3" y="7.5" width="14" height="13" rx="3" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="7.6" cy="12" r="1.4" fill="var(--icon-stroke, #000000)" stroke="none" />
                <path d="M4.4 18 8 13.7l2.6 3 2-2.4L17 18" />
            ',

            'truck' => '
                <path d="M2.5 8.5h10.5v8.2H2.5Z" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M13 11.3h3.6l3.4 3v2.4H13Z" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="6.4" cy="18.3" r="1.6" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="16.4" cy="18.3" r="1.6" fill="var(--icon-fill, #e4e4e7)" />
            ',

            'tag' => '
                <path d="M11.3 3.2H4.2v7.1l9 9 7.1-7.1-9-9Z" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="7.4" cy="7.4" r="1.4" fill="var(--icon-stroke, #000000)" stroke="none" />
            ',

            'gift' => '
                <rect x="3.5" y="10.5" width="17" height="9.5" rx="2" fill="var(--icon-fill, #e4e4e7)" />
                <rect x="2.5" y="7" width="19" height="4" rx="1.5" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M12 7v13.5" />
                <path d="M12 7c-1-2.6-5-2.6-5-.4 0 1 1 1.4 2.3 1.4M12 7c1-2.6 5-2.6 5-.4 0 1-1 1.4-2.3 1.4" />
            ',

            'inbox' => '
                <path d="M4.5 12.6 6.1 6.4A2.2 2.2 0 0 1 8.2 4.8h7.6a2.2 2.2 0 0 1 2.1 1.6l1.6 6.2" fill="none" />
                <path d="M4.5 12.6h4.9l1.4 2.2h2.4l1.4-2.2h4.9v4.6a2.3 2.3 0 0 1-2.3 2.3H6.8a2.3 2.3 0 0 1-2.3-2.3v-4.6Z" fill="var(--icon-fill, #e4e4e7)" />
            ',

            'bell' => '
                <path d="M12 3.6a4.9 4.9 0 0 0-4.9 4.9v2.9c0 1-.4 1.8-1 2.5l-.6.7h13l-.6-.7a3.5 3.5 0 0 1-1-2.5V8.5A4.9 4.9 0 0 0 12 3.6Z" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M9.8 17.6a2.2 2.2 0 0 0 4.4 0" fill="none" />
            ',

            'id-card' => '
                <rect x="2.5" y="5.2" width="19" height="13.6" rx="4" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="8.2" cy="10.6" r="2.2" fill="var(--icon-fill, #e4e4e7)" />
                <path d="M4.8 15.6c.3-1.9 1.7-3 3.4-3s3.1 1.1 3.4 3" fill="none" />
                <path d="M14 9.6h5M14 12.6h5" />
            ',

            'chart' => '
                <path d="M3.5 20h17" />
                <rect x="4.3" y="12.3" width="3.2" height="7.2" rx=".8" fill="var(--icon-fill, #e4e4e7)" />
                <rect x="10.4" y="7" width="3.2" height="12.5" rx=".8" fill="var(--icon-fill, #e4e4e7)" />
                <rect x="16.5" y="10" width="3.2" height="9.5" rx=".8" fill="var(--icon-fill, #e4e4e7)" />
            ',

            'sliders' => '
                <path d="M4.5 7h9.5M17.5 7h2M4.5 12h4.5M12 12h7.5M4.5 17h7.5M15 17h4.5" />
                <circle cx="16" cy="7" r="2.3" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="10.5" cy="12" r="2.3" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="13" cy="17" r="2.3" fill="var(--icon-fill, #e4e4e7)" />
            ',

            'chat' => '
                <path d="M7.5 6.5h9a3 3 0 0 1 3 3v3.5a3 3 0 0 1-3 3h-4l-3.2 2.8v-2.8h-1.8a3 3 0 0 1-3-3V9.5a3 3 0 0 1 3-3Z" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="9.5" cy="12" r="1" fill="var(--icon-stroke, #000000)" stroke="none" />
                <circle cx="13" cy="12" r="1" fill="var(--icon-stroke, #000000)" stroke="none" />
                <circle cx="16.5" cy="12" r="1" fill="var(--icon-stroke, #000000)" stroke="none" />
            ',

            'dot' => '
                <circle cx="12" cy="12" r="7" fill="var(--icon-fill, #e4e4e7)" />
                <circle cx="12" cy="12" r="2.4" fill="var(--icon-stroke, #000000)" stroke="none" />
            ',
        ];
    }
}
