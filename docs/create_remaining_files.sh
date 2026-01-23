#!/bin/bash

# WebhookDeliveryController
cat > /Users/functionbytes/Function/Coding/manager/app/Http/Controllers/Managers/Settings/Webhooks/WebhookDeliveryController.php << 'PHP1'
<?php

namespace App\Http\Controllers\Managers\Settings\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\Webhook\DeliverWebhookJob;
use App\Models\Webhook\WebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebhookDeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $pageTitle = 'Entregas Webhook';
        $breadcrumb = 'Configuración / Webhooks / Entregas';

        $query = WebhookDelivery::with(['integration', 'subscription', 'event'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        $deliveries = $query->paginate(25);

        return view('managers.views.settings.webhooks.deliveries.index', compact(
            'deliveries',
            'pageTitle',
            'breadcrumb'
        ));
    }

    public function show(WebhookDelivery $delivery): View
    {
        $pageTitle = 'Detalle de Entrega - ' . $delivery->uid;
        $breadcrumb = 'Configuración / Webhooks / Entregas / Detalle';

        $delivery->load(['integration', 'subscription', 'event', 'logs']);

        return view('managers.views.settings.webhooks.deliveries.show', compact(
            'delivery',
            'pageTitle',
            'breadcrumb'
        ));
    }

    public function retry(WebhookDelivery $delivery): JsonResponse
    {
        if ($delivery->status !== 'failed' && $delivery->status !== 'dead') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden reintentar entregas fallidas o muertas',
            ], 400);
        }

        $delivery->update([
            'status' => 'pending',
            'attempt_count' => 0,
            'next_retry_at' => null,
        ]);

        DeliverWebhookJob::dispatch($delivery->id, $delivery->event->payload)
            ->onQueue('deliveries');

        return response()->json([
            'success' => true,
            'message' => 'Reintento programado correctamente',
        ]);
    }

    public function bulkRetry(Request $request): JsonResponse
    {
        $ids = $request->input('delivery_ids', []);

        $count = WebhookDelivery::whereIn('id', $ids)
            ->whereIn('status', ['failed', 'dead'])
            ->update([
                'status' => 'pending',
                'attempt_count' => 0,
                'next_retry_at' => null,
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} entregas programadas para reintento",
        ]);
    }
}
PHP1

# WebhookEventController
cat > /Users/functionbytes/Function/Coding/manager/app/Http/Controllers/Managers/Settings/Webhooks/WebhookEventController.php << 'PHP2'
<?php

namespace App\Http\Controllers\Managers\Settings\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\Webhook\ProcessWebhookEventJob;
use App\Models\Webhook\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebhookEventController extends Controller
{
    public function index(Request $request): View
    {
        $pageTitle = 'Eventos Webhook';
        $breadcrumb = 'Configuración / Webhooks / Eventos';

        $query = WebhookEvent::with('integration')
            ->orderByDesc('received_at');

        if ($request->filled('event_key')) {
            $query->where('event_key', $request->event_key);
        }

        if ($request->filled('integration_id')) {
            $query->where('integration_id', $request->integration_id);
        }

        $events = $query->paginate(25);

        return view('managers.views.settings.webhooks.events.index', compact(
            'events',
            'pageTitle',
            'breadcrumb'
        ));
    }

    public function show(WebhookEvent $event): View
    {
        $pageTitle = 'Detalle de Evento - ' . $event->uid;
        $breadcrumb = 'Configuración / Webhooks / Eventos / Detalle';

        $event->load(['integration', 'deliveries']);

        return view('managers.views.settings.webhooks.events.show', compact(
            'event',
            'pageTitle',
            'breadcrumb'
        ));
    }

    public function replay(WebhookEvent $event): JsonResponse
    {
        ProcessWebhookEventJob::dispatch($event->id)
            ->onQueue('webhooks');

        return response()->json([
            'success' => true,
            'message' => 'Evento programado para reprocesamiento',
        ]);
    }
}
PHP2

echo "Controllers created successfully!"
