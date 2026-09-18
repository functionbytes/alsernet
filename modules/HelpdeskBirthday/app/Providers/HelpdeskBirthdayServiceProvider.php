<?php

namespace Modules\HelpdeskBirthday\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\HelpdeskBirthday\Console\Commands\CheckUnmarkedBirthdayCoupons;
use Modules\HelpdeskBirthday\Console\Commands\DispatchDueBirthdayEmails;
use Modules\HelpdeskBirthday\Console\Commands\FinalizeBirthdayCampaigns;
use Modules\HelpdeskBirthday\Console\Commands\PrepareBirthdayCampaign;
use Modules\HelpdeskBirthday\Console\Commands\SendBirthdayTestEmail;
use Modules\HelpdeskBirthday\Console\Commands\SyncBirthdayRedemptions;
use Modules\HelpdeskBirthday\Listeners\AnonymizeBirthdayRecipients;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Policies\BirthdayCampaignPolicy;
use Modules\HelpdeskBirthday\Services\Redemption\BirthdayRedemptionReader;
use Modules\HelpdeskBirthday\Services\Redemption\BridgeRedemptionReader;
use Modules\HelpdeskBirthday\Services\Redemption\SqlRedemptionReader;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskBirthdayServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskBirthday';

    protected string $nameLower = 'helpdeskbirthday';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );

        $this->registerRedemptionReader();
    }

    /**
     * De dónde se leen los canjes de la tienda.
     *
     * Por defecto el bridge, que es la vía que funciona aunque PrestaShop viva
     * en otra máquina. Si no está configurado —o si se pide explícitamente— se
     * cae a la lectura SQL directa, que sirve mientras webadmin y la tienda
     * compartan MariaDB y es lo que permite trabajar sin haber desplegado nada
     * en PrestaShop.
     *
     * Se decide por configuración y NO por entorno: que en desarrollo funcione
     * una vía distinta de la de producción es justo lo que hace que un fallo
     * aparezca al desplegar y no antes.
     */
    protected function registerRedemptionReader(): void
    {
        $this->app->bind(BirthdayRedemptionReader::class, function (): BirthdayRedemptionReader {
            $source = (string) config('helpdeskbirthday.redemption_source', 'auto');

            if ($source === 'sql') {
                return new SqlRedemptionReader;
            }

            $bridge = new BridgeRedemptionReader;

            if ($source === 'bridge' || $bridge->isAvailable()) {
                return $bridge;
            }

            return new SqlRedemptionReader;
        });
    }

    public function boot(): void
    {
        // Antes del early-return a propósito: borrar dato personal es una
        // obligación legal y debe cumplirse aunque el módulo esté apagado —
        // los datos siguen en la tabla igualmente.
        Event::listen(CustomerGdprDeleted::class, AnonymizeBirthdayRecipients::class);

        if (Module::find($this->name)?->isDisabled()) {
            return;
        }

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Gate::policy(BirthdayCampaign::class, BirthdayCampaignPolicy::class);

        $this->registerRoutes();
        $this->registerNav();
        $this->registerCommands();
    }

    protected function registerRoutes(): void
    {
        $web = module_path($this->name, 'routes/web.php');

        if (file_exists($web)) {
            Route::middleware(['web', 'auth'])
                ->prefix('panel/helpdeskbirthday')
                ->group($web);
        }

        // La baja va aparte: es pública y firmada, no puede exigir sesión.
        $public = module_path($this->name, 'routes/public.php');

        if (file_exists($public)) {
            Route::middleware('web')->group($public);
        }
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class) || ! helpdesk_birthday_enabled()) {
            return;
        }

        // En el sidebar del Helpdesk, que es desde donde se trabaja el día a
        // día: registrarlo solo en 'settings' lo dejaba escondido en el panel
        // de configuración y no había forma de llegar desde la bandeja.
        NavService::registerSidebar('helpdesk', [
            'title' => 'Cumpleaños',
            'items' => [
                ['label' => 'Campañas de cumpleaños', 'route' => 'helpdeskbirthday.campaigns.index', 'permission' => 'helpdeskbirthday.view'],
                ['label' => 'Ajustes de cumpleaños', 'route' => 'helpdeskbirthday.settings.index', 'permission' => 'helpdeskbirthday.settings.view'],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk · Cumpleaños',
            'order' => 265,
            'items' => [
                ['label' => 'Campañas de cumpleaños', 'route' => 'helpdeskbirthday.campaigns.index', 'permission' => 'helpdeskbirthday.view'],
                ['label' => 'Ajustes de cumpleaños', 'route' => 'helpdeskbirthday.settings.index', 'permission' => 'helpdeskbirthday.settings.view'],
            ],
        ]);
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PrepareBirthdayCampaign::class,
            DispatchDueBirthdayEmails::class,
            FinalizeBirthdayCampaigns::class,
            SendBirthdayTestEmail::class,
            CheckUnmarkedBirthdayCoupons::class,
            SyncBirthdayRedemptions::class,
        ]);

        $prepareAt = (string) config('helpdeskbirthday.prepare_at', '06:00');
        // La app corre en UTC pero prepare_at es hora de oficina: sin esto,
        // "06:00" se ejecutaría a las 08:00 en España durante el verano.
        $timezone = (string) config('helpdeskbirthday.timezone', config('app.timezone', 'UTC'));

        $windowEnd = (string) config('helpdeskbirthday.window_end', '14:00');

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) use ($prepareAt, $windowEnd, $timezone): void {
            // withoutOverlapping()+onOneServer() en los tres: preparar dos veces
            // duplicaría destinatarios y despachar en paralelo desde dos nodos
            // podría enviar el mismo correo dos veces.
            //
            // El minutaje de withoutOverlapping() NO es decorativo: por defecto
            // el candado dura 24 h, así que un proceso muerto de golpe (OOM, un
            // contenedor reiniciado a mitad) lo deja puesto y la tarea no
            // vuelve a correr en todo el día, en silencio. Con un TTL corto el
            // candado se suelta solo.
            $schedule->command('helpdeskbirthday:prepare')
                ->dailyAt($prepareAt)
                ->timezone($timezone)
                ->withoutOverlapping(30)
                ->onOneServer()
                ->when(fn (): bool => helpdesk_birthday_enabled());

            // Reintento durante la mañana: si a las 6 el ERP no respondía, a
            // las 7 puede que sí. El comando no hace nada cuando la campaña del
            // día ya está preparada, así que correrlo de más es barato — y
            // correrlo de menos cuesta un día entero de cumpleaños sin felicitar.
            $schedule->command('helpdeskbirthday:prepare')
                ->hourly()
                ->between($prepareAt, $windowEnd)
                ->timezone($timezone)
                ->withoutOverlapping(30)
                ->onOneServer()
                ->when(fn (): bool => helpdesk_birthday_enabled());

            // Cada minuto: es la resolución del escalonado y lo que hace que una
            // pausa desde el panel surta efecto casi al instante.
            $schedule->command('helpdeskbirthday:dispatch-due')
                ->everyMinute()
                ->withoutOverlapping(5)
                ->onOneServer()
                ->when(fn (): bool => helpdesk_birthday_enabled())
                ->runInBackground();

            // Una vez al día basta: un cupón sin marcar no se arregla solo,
            // pero tampoco urge al minuto.
            $schedule->command('helpdeskbirthday:check-unmarked')
                ->dailyAt('07:30')
                ->timezone($timezone)
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_birthday_enabled());

            $schedule->command('helpdeskbirthday:finalize')
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_birthday_enabled());

            // Los canjes de los últimos días, que es donde puede cambiar algo:
            // un pedido pasa a válido, Gestión registra el consumo. El panel lee
            // de la copia local, así que sin esto las cifras de dinero se quedan
            // congeladas en la última sincronización.
            $schedule->command('helpdeskbirthday:sync-redemptions --days=45')
                ->hourly()
                ->withoutOverlapping(30)
                ->onOneServer()
                ->when(fn (): bool => helpdesk_birthday_enabled());

            // Y una pasada larga de madrugada: recoge lo que se haya movido más
            // atrás y mantiene vivo el histórico, que es la línea base con la
            // que se compara una campaña. De noche porque son ~1.100 filas y
            // varias páginas contra la tienda.
            $schedule->command('helpdeskbirthday:sync-redemptions --days=400')
                ->dailyAt('04:30')
                ->timezone($timezone)
                ->withoutOverlapping(60)
                ->onOneServer()
                ->when(fn (): bool => helpdesk_birthday_enabled());
        });
    }
}
