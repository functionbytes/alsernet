<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Events
use App\Events\Return\ReturnCreated;
use App\Events\Return\ReturnStatusChanged;
use App\Events\Return\ReturnCompleted;
use App\Events\Return\ReturnPaymentProcessed;

// Listeners
use App\Listeners\Return\GeneratePDFListener;
use App\Listeners\Return\SendConfirmationListener;
use App\Listeners\Return\UpdateHistoryListener;
use App\Listeners\Return\NotifyCustomerListener;
use App\Listeners\Return\LogReturnActivityListener;

class ReturnEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Evento: Devolución Creada
        ReturnCreated::class => [
            GeneratePDFListener::class,
            SendConfirmationListener::class,
            LogReturnActivityListener::class . '@handleReturnCreated',
        ],

        // Evento: Estado de Devolución Cambiado
        ReturnStatusChanged::class => [
            UpdateHistoryListener::class,
            NotifyCustomerListener::class,
            LogReturnActivityListener::class . '@handleReturnStatusChanged',
        ],

        // Evento: Devolución Completada
        ReturnCompleted::class => [
            LogReturnActivityListener::class . '@handleReturnCompleted',
        ],

        // Evento: Pago Procesado
        ReturnPaymentProcessed::class => [
            LogReturnActivityListener::class . '@handleReturnPaymentProcessed',
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        LogReturnActivityListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // Registrar eventos adicionales programáticamente si es necesario
        $this->registerDynamicEventListeners();
    }

    /**
     * Registrar event listeners dinámicamente
     */
    private function registerDynamicEventListeners(): void
    {
        // Listener condicional para notificaciones push (si está habilitado)
        if (config('returns.notifications.push_notifications_enabled', false)) {
            $this->app['events']->listen(ReturnStatusChanged::class, function ($event) {
                // Aquí se podría agregar lógica para push notifications
                // dispatch(new SendPushNotificationJob($event->return));
            });
        }

        // Listener condicional para integración con ERP (si está configurado)
        if (config('returns.integrations.erp_enabled', false)) {
            $this->app['events']->listen(ReturnCompleted::class, function ($event) {
                // Aquí se podría sincronizar con sistema ERP
                // dispatch(new SyncWithERPJob($event->return));
            });
        }

        // Listener condicional para webhook notifications
        if (config('returns.webhooks.enabled', false)) {
            $this->app['events']->listen([
                ReturnCreated::class,
                ReturnStatusChanged::class,
                ReturnCompleted::class,
                ReturnPaymentProcessed::class
            ], function ($event) {
                // Enviar webhook notification
                // dispatch(new SendWebhookNotificationJob($event));
            });
        }

        // Listener para métricas en tiempo real
        if (config('returns.metrics.real_time_enabled', false)) {
            $this->app['events']->listen([
                ReturnCreated::class,
                ReturnCompleted::class,
                ReturnPaymentProcessed::class
            ], function ($event) {
                // Actualizar métricas en tiempo real
                // $this->updateRealTimeMetrics($event);
            });
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Deshabilitado para control explícito
    }

    /**
     * Get the listener directories that should be used to discover events.
     */
    protected function discoverEventsWithin(): array
    {
        return [
            $this->app->path('Listeners/Return'),
        ];
    }
}
