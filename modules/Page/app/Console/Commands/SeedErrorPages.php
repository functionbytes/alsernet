<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Locales\Models\Locale;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTranslation;

class SeedErrorPages extends Command
{
    protected $signature = 'pages:seed-error-pages {--force : Overwrite existing content even if edited}';

    protected $description = 'Create or update administrable error pages (400-504) with translations for every active locale';

    public function handle(): int
    {
        $locales = Locale::query()->where('is_active', 1)->orderBy('is_default', 'desc')->get();

        if ($locales->isEmpty()) {
            $this->error('No active locales found. Seed locales first.');

            return Command::FAILURE;
        }

        $definitions = $this->definitions();
        $defaultLocale = $locales->firstWhere('is_default', 1) ?? $locales->first();

        foreach ($definitions as $code => $def) {
            $defaultTrans = $def['translations'][$defaultLocale->code] ?? reset($def['translations']);

            $page = Page::updateOrCreate(
                ['page_type' => "error_{$code}"],
                [
                    'slug' => "error-{$code}",
                    'title' => $defaultTrans['title'],
                    'description' => $defaultTrans['description'],
                    'content' => $this->buildContent($code, $def, $defaultTrans),
                    'status' => PageStatus::Published->value,
                    'published_at' => now(),
                    'seo_noindex' => true,
                ]
            );

            foreach ($locales as $locale) {
                $trans = $def['translations'][$locale->code] ?? $defaultTrans;

                PageTranslation::updateOrCreate(
                    ['page_id' => $page->id, 'locale_id' => $locale->id],
                    [
                        'title' => $trans['title'],
                        'slug' => "error-{$code}",
                        'description' => $trans['description'],
                        'content' => $this->buildContent($code, $def, $trans),
                        'seo_title' => $trans['title'],
                        'seo_description' => $trans['description'],
                        'seo_noindex' => true,
                        'status' => PageStatus::Published->value,
                        'published_at' => now(),
                    ]
                );
            }

            $this->info("✓ Error {$code} — ".count($def['translations']).' translations');
        }

        $this->newLine();
        $this->info('All error pages seeded with translations ('.$locales->pluck('code')->implode(', ').').');

        return Command::SUCCESS;
    }

    /**
     * Build the themed HTML content for an error page.
     * This is embedded into the Page content and rendered through the active theme layout.
     */
    private function buildContent(string $code, array $def, array $trans): string
    {
        $icon = $def['icon'];
        $accent = $def['accent'];
        $labels = $trans['labels'];
        $extra = $trans['extra_button'] ?? '';

        return <<<HTML
<style>
.err-page{min-height:60vh;display:flex;align-items:center;justify-content:center;padding:48px 16px;font-family:'Poppins',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}.err-card{position:relative;background:#fff;border-radius:20px;padding:56px 44px;max-width:560px;width:100%;text-align:center;overflow:hidden}.err-card::before{content:'';position:absolute;top:0;left:0;right:0;height:5px;}.err-icon-wrap{width:104px;height:104px;margin:0 auto 24px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#C90F1618;color:#C90F16;font-size:46px;animation:errPulse 2.4s ease-in-out infinite}@keyframes errPulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 #C90F1635}50%{transform:scale(1.04);box-shadow:0 0 0 16px #C90F1600}}.err-code{font-size:clamp(64px,12vw,108px);font-weight:800;line-height:1;background: #b10100;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:-3px;margin:0 0 4px}.err-title{font-size:22px;font-weight:700;color:#060404;margin:0 0 10px;line-height:1.3}.err-message{font-size:15px;color:#5A5454;line-height:1.65;margin:0 auto 32px;max-width:440px}.err-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}.err-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius: 8px;font-size:14px;font-weight:600;text-decoration:none;border:2px solid transparent;cursor:pointer;transition:transform .15s,box-shadow .15s,background .15s;font-family:inherit}.err-btn-primary{background: #b10100;color:#fff;box-shadow:0 6px 18px rgba(201,15,22,.28)}.err-btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(201,15,22,.38);background:#A30B11;color:#fff}.err-btn-ghost{background:transparent;color:#142637;border-color:#D9DDE3}.err-btn-ghost:hover{background:#F3F5F7;border-color:#142637;color:#142637}@media (max-width:480px){.err-card{padding:40px 22px}.err-icon-wrap{width:88px;height:88px;font-size:38px}.err-title{font-size:19px}}
</style>

<div class="err-page">
    <div class="err-card">
        <div class="err-icon-wrap"><i class="fas {$icon}"></i></div>
        <div class="err-code">{$code}</div>
        <h1 class="err-title">{$trans['title']}</h1>
        <p class="err-message">{$trans['description']}</p>
        <div class="err-actions">
            {$extra}
            <a href="/" class="err-btn err-btn-primary"><i class="fas fa-house"></i> {$labels['home']}</a>
            <button type="button" onclick="history.back()" class="err-btn err-btn-ghost"><i class="fas fa-arrow-left"></i> {$labels['back']}</button>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Definitions for every error page + icon + accent color + per-locale copy.
     */
    private function definitions(): array
    {
        $labels = [
            'es' => ['home' => 'Inicio', 'back' => 'Volver', 'reload' => 'Recargar', 'login' => 'Iniciar sesión'],
            'pt' => ['home' => 'Início', 'back' => 'Voltar', 'reload' => 'Recarregar', 'login' => 'Iniciar sessão'],
            'en' => ['home' => 'Home',   'back' => 'Back',   'reload' => 'Reload',     'login' => 'Sign in'],
        ];

        $reload = fn (string $locale) => '<button type="button" onclick="location.reload()" class="err-btn err-btn-ghost"><i class="fas fa-rotate-right"></i> '.$labels[$locale]['reload'].'</button>';
        $login = fn (string $locale) => '<a href="/login" class="err-btn err-btn-ghost"><i class="fas fa-right-to-bracket"></i> '.$labels[$locale]['login'].'</a>';

        return [
            '400' => [
                'icon' => 'fa-circle-exclamation', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'Solicitud incorrecta', 'description' => 'El servidor no pudo procesar tu solicitud por un error en los datos enviados.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Pedido incorreto',     'description' => 'O servidor não conseguiu processar o seu pedido devido a um erro nos dados enviados.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Bad request',          'description' => 'The server could not process your request due to an error in the submitted data.', 'labels' => $labels['en']],
                ],
            ],
            '401' => [
                'icon' => 'fa-user-lock', 'accent' => '#C90F16',
                'translations' => [
                    'es' => ['title' => 'No autenticado', 'description' => 'Necesitas iniciar sesión para acceder a este recurso.', 'labels' => $labels['es'], 'extra_button' => $login('es')],
                    'pt' => ['title' => 'Não autenticado', 'description' => 'Precisa de iniciar sessão para aceder a este recurso.', 'labels' => $labels['pt'], 'extra_button' => $login('pt')],
                    'en' => ['title' => 'Not authenticated', 'description' => 'You need to sign in to access this resource.', 'labels' => $labels['en'], 'extra_button' => $login('en')],
                ],
            ],
            '403' => [
                'icon' => 'fa-shield-halved', 'accent' => '#C90F16',
                'translations' => [
                    'es' => ['title' => 'Acceso denegado', 'description' => 'No tienes permisos suficientes para ver o realizar esta acción.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Acesso negado',   'description' => 'Não tem permissões suficientes para ver ou realizar esta ação.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Access denied',   'description' => 'You do not have sufficient permissions to view or perform this action.', 'labels' => $labels['en']],
                ],
            ],
            '404' => [
                'icon' => 'fa-compass', 'accent' => '#C90F16',
                'translations' => [
                    'es' => ['title' => 'Página no encontrada', 'description' => 'La página que buscas no existe, fue movida o ya no está disponible.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Página não encontrada', 'description' => 'A página que procura não existe, foi movida ou já não está disponível.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Page not found',        'description' => 'The page you are looking for does not exist, was moved, or is no longer available.', 'labels' => $labels['en']],
                ],
            ],
            '405' => [
                'icon' => 'fa-ban', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'Método no permitido', 'description' => 'El método HTTP utilizado no está permitido para esta URL.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Método não permitido', 'description' => 'O método HTTP utilizado não é permitido para este URL.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Method not allowed',   'description' => 'The HTTP method used is not allowed for this URL.', 'labels' => $labels['en']],
                ],
            ],
            '408' => [
                'icon' => 'fa-hourglass-end', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'Tiempo de espera agotado', 'description' => 'La solicitud tardó demasiado en procesarse. Por favor, inténtalo de nuevo.', 'labels' => $labels['es'], 'extra_button' => $reload('es')],
                    'pt' => ['title' => 'Tempo esgotado', 'description' => 'O pedido demorou demasiado a processar. Por favor, tente novamente.', 'labels' => $labels['pt'], 'extra_button' => $reload('pt')],
                    'en' => ['title' => 'Request timeout',           'description' => 'The request took too long to process. Please try again.', 'labels' => $labels['en'], 'extra_button' => $reload('en')],
                ],
            ],
            '410' => [
                'icon' => 'fa-trash-can', 'accent' => '#2C3C4B',
                'translations' => [
                    'es' => ['title' => 'Recurso eliminado', 'description' => 'Este contenido fue eliminado permanentemente y ya no está disponible.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Recurso removido',  'description' => 'Este conteúdo foi removido permanentemente e já não está disponível.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Gone',              'description' => 'This content has been permanently removed and is no longer available.', 'labels' => $labels['en']],
                ],
            ],
            '419' => [
                'icon' => 'fa-clock-rotate-left', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'Sesión expirada', 'description' => 'Tu sesión ha expirado. Recarga la página e inténtalo de nuevo.', 'labels' => $labels['es'], 'extra_button' => $reload('es')],
                    'pt' => ['title' => 'Sessão expirada', 'description' => 'A sua sessão expirou. Recarregue a página e tente novamente.', 'labels' => $labels['pt'], 'extra_button' => $reload('pt')],
                    'en' => ['title' => 'Session expired',  'description' => 'Your session has expired. Reload the page and try again.', 'labels' => $labels['en'], 'extra_button' => $reload('en')],
                ],
            ],
            '422' => [
                'icon' => 'fa-triangle-exclamation', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'Datos inválidos', 'description' => 'Los datos enviados no pudieron ser procesados. Verifica la información e inténtalo de nuevo.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Dados inválidos', 'description' => 'Os dados enviados não puderam ser processados. Verifique a informação e tente novamente.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Invalid data',    'description' => 'The submitted data could not be processed. Please check the information and try again.', 'labels' => $labels['en']],
                ],
            ],
            '429' => [
                'icon' => 'fa-gauge-high', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'Demasiadas solicitudes', 'description' => 'Has realizado demasiadas solicitudes en poco tiempo. Por favor, espera unos minutos.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Muitos pedidos',         'description' => 'Fez demasiados pedidos em pouco tempo. Por favor, aguarde alguns minutos.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Too many requests',      'description' => 'You have made too many requests in a short time. Please wait a few minutes.', 'labels' => $labels['en']],
                ],
            ],
            '500' => [
                'icon' => 'fa-server', 'accent' => '#C90F16',
                'translations' => [
                    'es' => ['title' => 'Error del servidor', 'description' => 'Ha ocurrido un error inesperado. Nuestro equipo ha sido notificado.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Erro do servidor',   'description' => 'Ocorreu um erro inesperado. A nossa equipa foi notificada.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Server error',       'description' => 'An unexpected error occurred. Our team has been notified.', 'labels' => $labels['en']],
                ],
            ],
            '502' => [
                'icon' => 'fa-plug-circle-xmark', 'accent' => '#C90F16',
                'translations' => [
                    'es' => ['title' => 'Puerta de enlace fallida', 'description' => 'El servidor recibió una respuesta inválida. Por favor, inténtalo en unos momentos.', 'labels' => $labels['es'], 'extra_button' => $reload('es')],
                    'pt' => ['title' => 'Gateway inválido',         'description' => 'O servidor recebeu uma resposta inválida. Por favor, tente novamente em breve.', 'labels' => $labels['pt'], 'extra_button' => $reload('pt')],
                    'en' => ['title' => 'Bad gateway',               'description' => 'The server received an invalid response. Please try again in a few moments.', 'labels' => $labels['en'], 'extra_button' => $reload('en')],
                ],
            ],
            '503' => [
                'icon' => 'fa-screwdriver-wrench', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'En mantenimiento',     'description' => 'Estamos realizando mejoras en el sistema. Vuelve en unos minutos.', 'labels' => $labels['es']],
                    'pt' => ['title' => 'Em manutenção',         'description' => 'Estamos a efetuar melhorias no sistema. Volte dentro de alguns minutos.', 'labels' => $labels['pt']],
                    'en' => ['title' => 'Under maintenance',    'description' => 'We are making improvements to the system. Please come back in a few minutes.', 'labels' => $labels['en']],
                ],
            ],
            '504' => [
                'icon' => 'fa-hourglass-half', 'accent' => '#EE6400',
                'translations' => [
                    'es' => ['title' => 'Tiempo de gateway agotado', 'description' => 'El servidor no respondió a tiempo. Por favor, inténtalo en unos momentos.', 'labels' => $labels['es'], 'extra_button' => $reload('es')],
                    'pt' => ['title' => 'Tempo de gateway esgotado', 'description' => 'O servidor não respondeu a tempo. Por favor, tente novamente em breve.', 'labels' => $labels['pt'], 'extra_button' => $reload('pt')],
                    'en' => ['title' => 'Gateway timeout',            'description' => 'The server did not respond in time. Please try again in a few moments.', 'labels' => $labels['en'], 'extra_button' => $reload('en')],
                ],
            ],
        ];
    }
}
