<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class SeedErrorPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pages:seed-error-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update default administrable error pages (404, 403, 419, 500, 503)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $errors = $this->errorDefinitions();

        foreach ($errors as $code => $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'page_type' => "error_{$code}",
                    'status' => PageStatus::Published->value,
                    'published_at' => now(),
                    'description' => $data['description'],
                ]
            );

            $this->info("Error page {$code} created/updated.");
        }

        $this->info('Done. All error pages seeded successfully.');

        return Command::SUCCESS;
    }

    /**
     * Return the definitions for each error page.
     *
     * @return array<string, array{slug: string, title: string, description: string, content: string}>
     */
    private function errorDefinitions(): array
    {
        return [
            '400' => [
                'slug' => 'error-400',
                'title' => 'Solicitud incorrecta',
                'description' => 'La solicitud enviada no pudo ser procesada.',
                'content' => $this->card('400', 'fa-ban', 'Solicitud incorrecta', 'La solicitud enviada no pudo ser procesada por el servidor.'),
            ],
            '401' => [
                'slug' => 'error-401',
                'title' => 'No autenticado',
                'description' => 'Debes iniciar sesión para acceder a este recurso.',
                'content' => $this->card('401', 'fa-lock', 'No autenticado', 'Debes iniciar sesión para acceder a este recurso.', '<a href="/login" class="btn btn-primary me-2"><i class="fas fa-sign-in-alt me-1"></i> Iniciar sesión</a>'),
            ],
            '403' => [
                'slug' => 'error-403',
                'title' => 'Acceso denegado',
                'description' => 'No tienes permisos para acceder a esta sección.',
                'content' => $this->card('403', 'fa-shield-alt', 'Acceso denegado', 'No tienes permisos para acceder a esta sección.'),
            ],
            '404' => [
                'slug' => 'error-404',
                'title' => 'Página no encontrada',
                'description' => 'La página que buscas no existe o ha sido movida.',
                'content' => $this->card('404', 'fa-search', 'Página no encontrada', 'La página que buscas no existe o ha sido movida.'),
            ],
            '405' => [
                'slug' => 'error-405',
                'title' => 'Método no permitido',
                'description' => 'El método HTTP utilizado no está permitido para esta URL.',
                'content' => $this->card('405', 'fa-minus-circle', 'Método no permitido', 'El método HTTP utilizado no está permitido para esta URL.'),
            ],
            '408' => [
                'slug' => 'error-408',
                'title' => 'Tiempo de espera agotado',
                'description' => 'La solicitud tardó demasiado en procesarse.',
                'content' => $this->card('408', 'fa-hourglass-end', 'Tiempo de espera agotado', 'La solicitud tardó demasiado en procesarse. Por favor, inténtalo de nuevo.'),
            ],
            '410' => [
                'slug' => 'error-410',
                'title' => 'Recurso eliminado',
                'description' => 'Este contenido fue eliminado permanentemente.',
                'content' => $this->card('410', 'fa-trash-alt', 'Recurso eliminado', 'Este contenido fue eliminado permanentemente y ya no está disponible.'),
            ],
            '419' => [
                'slug' => 'error-419',
                'title' => 'Sesión expirada',
                'description' => 'Tu sesión ha expirado. Por favor recarga la página e inténtalo de nuevo.',
                'content' => $this->card('419', 'fa-clock', 'Sesión expirada', 'Tu sesión ha expirado. Por favor recarga la página e inténtalo de nuevo.', '<button onclick="location.reload()" class="btn btn-primary me-2"><i class="fas fa-sync-alt me-1"></i> Recargar página</button>'),
            ],
            '422' => [
                'slug' => 'error-422',
                'title' => 'Datos inválidos',
                'description' => 'Los datos enviados no pudieron ser procesados.',
                'content' => $this->card('422', 'fa-exclamation-circle', 'Datos inválidos', 'Los datos enviados no pudieron ser procesados. Verifica la información e inténtalo de nuevo.'),
            ],
            '429' => [
                'slug' => 'error-429',
                'title' => 'Demasiadas solicitudes',
                'description' => 'Has realizado demasiadas solicitudes en poco tiempo.',
                'content' => $this->card('429', 'fa-tachometer-alt', 'Demasiadas solicitudes', 'Has realizado demasiadas solicitudes en poco tiempo. Por favor, espera unos minutos e inténtalo de nuevo.'),
            ],
            '500' => [
                'slug' => 'error-500',
                'title' => 'Error interno del servidor',
                'description' => 'Ha ocurrido un error inesperado. Nuestro equipo ha sido notificado.',
                'content' => $this->card('500', 'fa-exclamation-triangle', 'Error interno del servidor', 'Ha ocurrido un error inesperado. Nuestro equipo ha sido notificado.'),
            ],
            '502' => [
                'slug' => 'error-502',
                'title' => 'Puerta de enlace fallida',
                'description' => 'El servidor recibió una respuesta inválida.',
                'content' => $this->card('502', 'fa-plug', 'Puerta de enlace fallida', 'El servidor recibió una respuesta inválida. Por favor, inténtalo de nuevo en unos momentos.'),
            ],
            '503' => [
                'slug' => 'error-503',
                'title' => 'Servicio no disponible',
                'description' => 'Estamos realizando mejoras en el sistema. Por favor, vuelve en unos minutos.',
                'content' => $this->card('503', 'fa-wrench', 'Servicio no disponible', 'Estamos realizando mejoras en el sistema. Por favor, vuelve en unos minutos.', '<a href="/" class="btn btn-primary"><i class="fas fa-home me-1"></i> Inicio</a>'),
            ],
            '504' => [
                'slug' => 'error-504',
                'title' => 'Tiempo de espera del gateway',
                'description' => 'El servidor no respondió a tiempo.',
                'content' => $this->card('504', 'fa-stopwatch', 'Tiempo de espera del gateway', 'El servidor no respondió a tiempo. Por favor, inténtalo de nuevo en unos momentos.'),
            ],
        ];
    }

    /**
     * Build default card HTML for an error page.
     */
    private function card(string $code, string $icon, string $title, string $description, string $extraButtons = ''): string
    {
        $buttons = $extraButtons
            .'<a href="/" class="btn btn-primary me-2"><i class="fas fa-home me-1"></i> Inicio</a>'
            .'<button onclick="history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Volver</button>';

        return '<div class="error-icon"><i class="fas '.$icon.'"></i></div>'
            .'<div class="error-code">'.$code.'</div>'
            .'<h4 class="mt-3 mb-2 fw-bold">'.$title.'</h4>'
            .'<p class="text-muted mb-4">'.$description.'</p>'
            .$buttons;
    }
}
