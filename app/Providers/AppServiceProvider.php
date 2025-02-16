<?php

namespace App\Providers;

use App\Library\Facades\Hook;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Compartir configuración en todas las vistas sin usar view composer globalmente
        $this->app->singleton('app.settings', fn () => $this->getSettings());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $this->changeDefaultSettings();

        // Configuración de WebSockets y Pusher
        $settings = $this->getSettings();
        if ($settings) {
            config([
                'websockets.dashboard.port' => $settings->liveChatPort ?? env('LIVE_CHAT_PORT', 6001),
                'broadcasting.connections.pusher.options.port' => $settings->liveChatPort ?? env('LIVE_CHAT_PORT', 6001),
                'broadcasting.connections.pusher.options.host' => parse_url(url('/'))["host"] ?? env('PUSHER_HOST', 'localhost'),
            ]);
        }

        // Registrar archivos de traducción desde los plugins
        foreach (Hook::execute('add_translation_file') as $source) {
            if (!empty($source['translation_prefix']) && !empty($source['translation_folder'])) {
                $this->loadTranslationsFrom($source['translation_folder'], $source['translation_prefix']);
            }
        }

        // Manejo de colas con Laravel 11
        Queue::before(fn (JobProcessing $event) => $this->handleQueueEvent($event, 'before'));
        Queue::after(fn (JobProcessed $event) => $this->handleQueueEvent($event, 'after'));

        // Usar Queue::failing() en lugar de catching() para compatibilidad con DatabaseQueue
        Queue::failing(fn (JobFailed $event) => $this->handleQueueEvent($event, 'failed'));
    }

    /**
     * Obtener configuración de la base de datos.
     */
    private function getSettings(): ?Setting
    {
        return cache()->remember('app_settings', now()->addMinutes(10), fn () => Setting::first());
    }

    /**
     * Configuración global del sistema.
     */
    private function changeDefaultSettings(): void
    {
        ini_set('memory_limit', '-1');
        ini_set('pcre.backtrack_limit', '1000000000');

        // Configuración de HTTPS si es necesario
        if (request()->isSecure() || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0)) {
            URL::forceScheme('https');
        }
    }

    /**
     * Manejar eventos de la cola.
     */
    private function handleQueueEvent($event, string $type): void
    {
        // Aquí puedes agregar logs o lógica adicional para manejar eventos de la cola
    }
}
