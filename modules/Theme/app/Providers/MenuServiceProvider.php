<?php

namespace Modules\Theme\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;

/**
 * MenuServiceProvider - Registra todos los menús del panel administrativo
 *
 * Este provider se encarga de coordinar el registro de items de menú
 * desde todos los módulos y hacerlos disponibles en las vistas.
 */
class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Se ejecuta después de que todos los providers hayan sido registrados,
     * permitiendo que cada módulo haya tenido oportunidad de registrar sus menús.
     */
    public function boot(): void
    {
        $this->shareWithViews();
    }

    /**
     * Compartir el servicio con las vistas
     */
    private function shareWithViews(): void
    {
        \View::share('navService', NavService::class);
    }
}
