# WebhookHub - Panel de Administración con Blade

> **Patrón:** Blade + Bootstrap 5.3 + Font Awesome 6
> **Fecha:** 2025-12-23
> **Basado en:** Estructura existente de `Managers/Settings`

---

## 📋 Tabla de Contenidos

1. [Estructura de Rutas](#estructura-de-rutas)
2. [Controladores](#controladores)
3. [Vistas Blade](#vistas-blade)
4. [JavaScript y AJAX](#javascript-y-ajax)
5. [Form Requests](#form-requests)

---

## 1. Estructura de Rutas

### 1.1 Archivo `routes/managers.php`

```php
<?php

use App\Http\Controllers\Managers\Settings\Webhooks\WebhookIntegrationController;
use App\Http\Controllers\Managers\Settings\Webhooks\WebhookSubscriptionController;
use App\Http\Controllers\Managers\Settings\Webhooks\WebhookDeliveryController;
use App\Http\Controllers\Managers\Settings\Webhooks\WebhookEventController;
use Illuminate\Support\Facades\Route;

// Webhooks Management
Route::prefix('backups/webhooks')->name('manager.backups.webhooks.')->group(function () {

    // Integrations
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [WebhookIntegrationController::class, 'index'])->name('index');
        Route::get('/create', [WebhookIntegrationController::class, 'create'])->name('create');
        Route::post('/', [WebhookIntegrationController::class, 'store'])->name('store');
        Route::get('/{integration}/edit', [WebhookIntegrationController::class, 'edit'])->name('edit');
        Route::put('/{integration}', [WebhookIntegrationController::class, 'update'])->name('update');
        Route::delete('/{integration}', [WebhookIntegrationController::class, 'destroy'])->name('destroy');
        Route::post('/{integration}/toggle', [WebhookIntegrationController::class, 'toggle'])->name('toggle');

        // API Keys sub-routes
        Route::get('/{integration}/api-keys', [WebhookIntegrationController::class, 'apiKeys'])->name('api-keys');
        Route::post('/{integration}/api-keys', [WebhookIntegrationController::class, 'storeApiKey'])->name('api-keys.store');
        Route::delete('/api-keys/{apiKey}', [WebhookIntegrationController::class, 'destroyApiKey'])->name('api-keys.destroy');
        Route::post('/api-keys/{apiKey}/revoke', [WebhookIntegrationController::class, 'revokeApiKey'])->name('api-keys.revoke');
    });

    // Subscriptions
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [WebhookSubscriptionController::class, 'index'])->name('index');
        Route::get('/create', [WebhookSubscriptionController::class, 'create'])->name('create');
        Route::post('/', [WebhookSubscriptionController::class, 'store'])->name('store');
        Route::get('/{subscription}/edit', [WebhookSubscriptionController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [WebhookSubscriptionController::class, 'update'])->name('update');
        Route::delete('/{subscription}', [WebhookSubscriptionController::class, 'destroy'])->name('destroy');
        Route::post('/{subscription}/toggle', [WebhookSubscriptionController::class, 'toggle'])->name('toggle');
        Route::post('/{subscription}/test', [WebhookSubscriptionController::class, 'test'])->name('test');
        Route::post('/{subscription}/rotate-secret', [WebhookSubscriptionController::class, 'rotateSecret'])->name('rotate-secret');
    });

    // Deliveries
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [WebhookDeliveryController::class, 'index'])->name('index');
        Route::get('/{delivery}', [WebhookDeliveryController::class, 'show'])->name('show');
        Route::post('/{delivery}/retry', [WebhookDeliveryController::class, 'retry'])->name('retry');
        Route::post('/bulk-retry', [WebhookDeliveryController::class, 'bulkRetry'])->name('bulk-retry');
    });

    // Events
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [WebhookEventController::class, 'index'])->name('index');
        Route::get('/{event}', [WebhookEventController::class, 'show'])->name('show');
        Route::post('/{event}/replay', [WebhookEventController::class, 'replay'])->name('replay');
    });
});
```

---

## 2. Controladores

### 2.1 `WebhookIntegrationController`

```php
<?php

namespace App\Http\Controllers\Managers\Settings\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\Settings\Webhooks\StoreIntegrationRequest;
use App\Http\Requests\Managers\Settings\Webhooks\UpdateIntegrationRequest;
use App\Models\Webhook\WebhookIntegration;
use App\Models\Webhook\WebhookApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebhookIntegrationController extends Controller
{
    /**
     * Display integrations list
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Integraciones Webhook';
        $breadcrumb = 'Configuración / Webhooks / Integraciones';

        $search = $request->get('search');
        $status = $request->get('status');
        $plan = $request->get('plan');

        $query = WebhookIntegration::query()
            ->withCount(['events', 'subscriptions', 'deliveries']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('uid', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($plan) {
            $query->where('plan', $plan);
        }

        $integrations = $query->orderByDesc('created_at')->paginate(15);

        return view('theme.views.backups.webhooks.integrations.index', compact(
            'integrations',
            'pageTitle',
            'breadcrumb',
            'search',
            'status',
            'plan'
        ));
    }

    /**
     * Show create form
     */
    public function create(): View
    {
        $pageTitle = 'Nueva Integración Webhook';
        $breadcrumb = 'Configuración / Webhooks / Integraciones / Crear';

        return view('theme.views.backups.webhooks.integrations.create', compact(
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Store integration
     */
    public function store(StoreIntegrationRequest $request): JsonResponse
    {
        try {
            $integration = WebhookIntegration::create([
                'name' => $request->name,
                'status' => 'active',
                'plan' => $request->plan ?? 'free',
                'daily_limit' => $request->daily_limit ?? 1000,
                'allowed_ips' => $request->allowed_ips ? explode(',', $request->allowed_ips) : null,
                'allowed_domains' => $request->allowed_domains ? explode(',', $request->allowed_domains) : null,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integración creada exitosamente',
                'redirect' => route('manager.backups.webhooks.integrations.edit', $integration),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la integración: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show edit form
     */
    public function edit(WebhookIntegration $integration): View
    {
        $pageTitle = 'Editar Integración - ' . $integration->name;
        $breadcrumb = 'Configuración / Webhooks / Integraciones / Editar';

        $integration->load(['apiKeys', 'events' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $stats = [
            'total_events' => $integration->events()->count(),
            'total_deliveries' => $integration->deliveries()->count(),
            'success_deliveries' => $integration->deliveries()->where('status', 'success')->count(),
            'failed_deliveries' => $integration->deliveries()->where('status', 'failed')->count(),
            'active_subscriptions' => $integration->subscriptions()->where('is_active', true)->count(),
        ];

        return view('theme.views.backups.webhooks.integrations.edit', compact(
            'integration',
            'pageTitle',
            'breadcrumb',
            'stats'
        ));
    }

    /**
     * Update integration
     */
    public function update(UpdateIntegrationRequest $request, WebhookIntegration $integration): JsonResponse
    {
        try {
            $integration->update([
                'name' => $request->name,
                'status' => $request->status,
                'plan' => $request->plan,
                'daily_limit' => $request->daily_limit,
                'allowed_ips' => $request->allowed_ips ? explode(',', $request->allowed_ips) : null,
                'allowed_domains' => $request->allowed_domains ? explode(',', $request->allowed_domains) : null,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integración actualizada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle integration status
     */
    public function toggle(WebhookIntegration $integration): JsonResponse
    {
        $newStatus = $integration->status === 'active' ? 'disabled' : 'active';
        $integration->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'status' => $newStatus,
        ]);
    }

    /**
     * Delete integration
     */
    public function destroy(WebhookIntegration $integration): JsonResponse
    {
        try {
            $integration->delete();

            return response()->json([
                'success' => true,
                'message' => 'Integración eliminada correctamente',
                'redirect' => route('manager.backups.webhooks.integrations.index'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show API keys for integration
     */
    public function apiKeys(WebhookIntegration $integration): View
    {
        $pageTitle = 'API Keys - ' . $integration->name;
        $breadcrumb = 'Configuración / Webhooks / Integraciones / API Keys';

        $apiKeys = $integration->apiKeys()->latest()->paginate(15);

        return view('theme.views.backups.webhooks.integrations.api-keys', compact(
            'integration',
            'apiKeys',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Store new API key
     */
    public function storeApiKey(Request $request, WebhookIntegration $integration): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'permissions' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:1000',
        ]);

        $rawSecret = Str::random(64);

        $apiKey = $integration->apiKeys()->create([
            'key' => 'whk_' . Str::random(40),
            'secret' => hash('sha256', $rawSecret),
            'name' => $request->name,
            'permissions' => $request->permissions ?? ['inbound', 'outbound'],
            'rate_limit_per_minute' => $request->rate_limit_per_minute ?? 60,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API Key creada exitosamente',
            'api_key' => [
                'key' => $apiKey->key,
                'secret' => $rawSecret, // Solo se muestra UNA VEZ
            ],
        ]);
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey(WebhookApiKey $apiKey): JsonResponse
    {
        $apiKey->update(['revoked_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'API Key revocada correctamente',
        ]);
    }

    /**
     * Delete API key
     */
    public function destroyApiKey(WebhookApiKey $apiKey): JsonResponse
    {
        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API Key eliminada correctamente',
        ]);
    }
}
```

### 2.2 `WebhookSubscriptionController`

```php
<?php

namespace App\Http\Controllers\Managers\Settings\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\Settings\Webhooks\StoreSubscriptionRequest;
use App\Http\Requests\Managers\Settings\Webhooks\UpdateSubscriptionRequest;
use App\Jobs\Webhook\DeliverWebhookJob;
use App\Models\Webhook\WebhookIntegration;
use App\Models\Webhook\WebhookSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebhookSubscriptionController extends Controller
{
    /**
     * Display subscriptions list
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Suscripciones Webhook';
        $breadcrumb = 'Configuración / Webhooks / Suscripciones';

        $search = $request->get('search');
        $integrationId = $request->get('integration_id');
        $isActive = $request->get('is_active');

        $query = WebhookSubscription::query()
            ->with('integration')
            ->withCount('deliveries');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        if ($integrationId) {
            $query->where('integration_id', $integrationId);
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool)$isActive);
        }

        $subscriptions = $query->orderByDesc('created_at')->paginate(15);
        $integrations = WebhookIntegration::where('status', 'active')->get();

        return view('theme.views.backups.webhooks.subscriptions.index', compact(
            'subscriptions',
            'integrations',
            'pageTitle',
            'breadcrumb',
            'search',
            'integrationId',
            'isActive'
        ));
    }

    /**
     * Show create form
     */
    public function create(): View
    {
        $pageTitle = 'Nueva Suscripción Webhook';
        $breadcrumb = 'Configuración / Webhooks / Suscripciones / Crear';

        $integrations = WebhookIntegration::where('status', 'active')->get();
        $eventCatalog = \App\Models\Webhook\WebhookEventCatalog::where('is_active', true)->get();

        return view('theme.views.backups.webhooks.subscriptions.create', compact(
            'pageTitle',
            'breadcrumb',
            'integrations',
            'eventCatalog'
        ));
    }

    /**
     * Store subscription
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        try {
            $subscription = WebhookSubscription::create([
                'integration_id' => $request->integration_id,
                'name' => $request->name,
                'url' => $request->url,
                'is_active' => $request->boolean('is_active', true),
                'subscribed_events' => $request->subscribed_events ?? [],
                'auth_type' => $request->auth_type ?? 'none',
                'auth_config' => $request->auth_config ?? [],
                'signing_secret' => Str::random(64),
                'timeout_ms' => $request->timeout_ms ?? 10000,
                'max_attempts' => $request->max_attempts ?? 6,
                'backoff_policy' => [60, 300, 900, 3600, 21600, 86400],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Suscripción creada exitosamente',
                'redirect' => route('manager.backups.webhooks.subscriptions.edit', $subscription),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la suscripción: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show edit form
     */
    public function edit(WebhookSubscription $subscription): View
    {
        $pageTitle = 'Editar Suscripción - ' . $subscription->name;
        $breadcrumb = 'Configuración / Webhooks / Suscripciones / Editar';

        $subscription->load(['integration', 'deliveries' => function ($query) {
            $query->latest()->limit(20);
        }]);

        $integrations = WebhookIntegration::where('status', 'active')->get();
        $eventCatalog = \App\Models\Webhook\WebhookEventCatalog::where('is_active', true)->get();

        $stats = [
            'total_deliveries' => $subscription->deliveries()->count(),
            'success' => $subscription->deliveries()->where('status', 'success')->count(),
            'failed' => $subscription->deliveries()->where('status', 'failed')->count(),
            'dead' => $subscription->deliveries()->where('status', 'dead')->count(),
        ];

        return view('theme.views.backups.webhooks.subscriptions.edit', compact(
            'subscription',
            'integrations',
            'eventCatalog',
            'stats',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Update subscription
     */
    public function update(UpdateSubscriptionRequest $request, WebhookSubscription $subscription): JsonResponse
    {
        try {
            $subscription->update([
                'integration_id' => $request->integration_id,
                'name' => $request->name,
                'url' => $request->url,
                'is_active' => $request->boolean('is_active'),
                'subscribed_events' => $request->subscribed_events ?? [],
                'auth_type' => $request->auth_type,
                'auth_config' => $request->auth_config ?? [],
                'timeout_ms' => $request->timeout_ms,
                'max_attempts' => $request->max_attempts,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Suscripción actualizada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle subscription status
     */
    public function toggle(WebhookSubscription $subscription): JsonResponse
    {
        $subscription->update(['is_active' => !$subscription->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'is_active' => $subscription->is_active,
        ]);
    }

    /**
     * Test subscription with dummy payload
     */
    public function test(WebhookSubscription $subscription): JsonResponse
    {
        $testPayload = [
            'test' => true,
            'message' => 'Test webhook from WebhookHub',
            'timestamp' => now()->toIso8601String(),
            'subscription' => [
                'id' => $subscription->uid,
                'name' => $subscription->name,
            ],
        ];

        DeliverWebhookJob::dispatch($subscription->id, $testPayload)
            ->onQueue('deliveries');

        return response()->json([
            'success' => true,
            'message' => 'Webhook de prueba enviado. Revisa los logs de entrega.',
        ]);
    }

    /**
     * Rotate signing secret
     */
    public function rotateSecret(WebhookSubscription $subscription): JsonResponse
    {
        $newSecret = Str::random(64);
        $subscription->update(['signing_secret' => $newSecret]);

        return response()->json([
            'success' => true,
            'message' => 'Signing secret rotado correctamente',
            'new_secret' => $newSecret, // Mostrar UNA VEZ
        ]);
    }

    /**
     * Delete subscription
     */
    public function destroy(WebhookSubscription $subscription): JsonResponse
    {
        try {
            $subscription->delete();

            return response()->json([
                'success' => true,
                'message' => 'Suscripción eliminada correctamente',
                'redirect' => route('manager.backups.webhooks.subscriptions.index'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage(),
            ], 500);
        }
    }
}
```

---

## 3. Vistas Blade

### 3.1 Index de Integraciones

**Archivo:** `resources/views/managers/views/settings/webhooks/integrations/index.blade.php`

```blade
@extends('layouts.managers')

@section('title', 'Integraciones Webhook')

@section('content')

    @include('managers.includes.card', ['title' => 'Integraciones Webhook'])

    <div class="widget-content searchable-container list">

        @include('theme.components.alerts')

        <!-- Integrations Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Integraciones Webhook</h5>
                        <p class="small mb-0 text-muted">Gestiona las integraciones que pueden recibir y enviar webhooks</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search') || request('status') || request('plan'))
                            <a href="{{ route('manager.settings.webhooks.integrations.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Limpiar filtros
                            </a>
                        @endif
                        <a href="{{ route('manager.settings.webhooks.integrations.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva integración
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search/Filters -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.settings.webhooks.integrations.index') }}">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por nombre o UID..."
                                       value="{{ $search }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">Todos los estados</option>
                                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspendido</option>
                                <option value="disabled" {{ $status === 'disabled' ? 'selected' : '' }}>Deshabilitado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="plan">
                                <option value="">Todos los planes</option>
                                <option value="free" {{ $plan === 'free' ? 'selected' : '' }}>Free</option>
                                <option value="pro" {{ $plan === 'pro' ? 'selected' : '' }}>Pro</option>
                                <option value="enterprise" {{ $plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card-body">
                @if($integrations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Integración</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Plan</th>
                                    <th class="text-center">Eventos</th>
                                    <th class="text-center">Suscripciones</th>
                                    <th class="text-center">Entregas</th>
                                    <th>Creada</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($integrations as $integration)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $integration->name }}</strong>
                                                <br>
                                                <small class="text-muted font-monospace">{{ $integration->uid }}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($integration->status === 'active')
                                                <span class="badge bg-success">Activo</span>
                                            @elseif($integration->status === 'suspended')
                                                <span class="badge bg-warning">Suspendido</span>
                                            @else
                                                <span class="badge bg-secondary">Deshabilitado</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ ucfirst($integration->plan) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $integration->events_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $integration->subscriptions_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $integration->deliveries_count }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $integration->created_at->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="{{ route('manager.settings.webhooks.integrations.edit', $integration) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('manager.settings.webhooks.integrations.api-keys', $integration) }}"
                                                   class="btn btn-sm btn-outline-secondary" title="API Keys">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger delete-integration"
                                                        data-id="{{ $integration->id }}"
                                                        data-name="{{ $integration->name }}"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $integrations->withQueryString()->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No se encontraron integraciones.
                        <a href="{{ route('manager.settings.webhooks.integrations.create') }}">Crear la primera integración</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    @include('managers.includes.delete')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete integration
    document.querySelectorAll('.delete-integration').forEach(btn => {
        btn.addEventListener('click', function() {
            const integrationId = this.dataset.id;
            const integrationName = this.dataset.name;

            Swal.fire({
                title: '¿Eliminar integración?',
                text: `Se eliminará "${integrationName}" y todos sus datos asociados.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/manager/settings/webhooks/integrations/${integrationId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Eliminado!', data.message, 'success')
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Error al eliminar la integración', 'error');
                    });
                }
            });
        });
    });
});
</script>
@endpush
```

### 3.2 Formulario Create/Edit de Integración

**Archivo:** `resources/views/managers/views/settings/webhooks/integrations/create.blade.php`

```blade
@extends('layouts.managers')

@section('title', 'Nueva Integración Webhook')

@section('content')

    @include('managers.includes.card', ['title' => 'Nueva Integración Webhook'])

    <div class="card">
        <div class="card-body">
            <form id="integration-form" method="POST" action="{{ route('manager.settings.webhooks.integrations.store') }}">
                @csrf

                <div class="row">
                    <!-- Nombre -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="Ej: Integración PrestaShop">
                    </div>

                    <!-- Plan -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Plan</label>
                        <select name="plan" class="form-select">
                            <option value="free">Free</option>
                            <option value="pro">Pro</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>

                    <!-- Daily Limit -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Límite diario de eventos</label>
                        <input type="number" name="daily_limit" class="form-control" value="1000" min="1">
                    </div>

                    <!-- Allowed IPs -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">IPs permitidas (opcional)</label>
                        <input type="text" name="allowed_ips" class="form-control"
                               placeholder="192.168.1.1, 10.0.0.5">
                        <small class="text-muted">Separar con comas. Dejar vacío para permitir todas.</small>
                    </div>

                    <!-- Allowed Domains -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dominios permitidos (opcional)</label>
                        <input type="text" name="allowed_domains" class="form-control"
                               placeholder="example.com, api.example.com">
                        <small class="text-muted">Separar con comas.</small>
                    </div>

                    <!-- Notes -->
                    <div class="col-12 mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Información adicional sobre esta integración..."></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('manager.settings.webhooks.integrations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Crear integración
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.getElementById('integration-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('¡Creado!', data.message, 'success')
                .then(() => window.location.href = data.redirect);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error al crear la integración', 'error');
        console.error(err);
    });
});
</script>
@endpush
```

---

## 4. Form Requests

### 4.1 `StoreIntegrationRequest`

**Archivo:** `app/Http/Requests/Managers/Settings/Webhooks/StoreIntegrationRequest.php`

```php
<?php

namespace App\Http\Requests\Managers\Settings\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // O implementar lógica de permisos
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'plan' => 'nullable|in:free,pro,enterprise',
            'daily_limit' => 'nullable|integer|min:1',
            'allowed_ips' => 'nullable|string',
            'allowed_domains' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la integración es obligatorio',
            'plan.in' => 'Plan inválido',
            'daily_limit.min' => 'El límite diario debe ser al menos 1',
        ];
    }
}
```

---

## 5. Resumen de Patrón

### ✅ Convenciones seguidas:

1. **Rutas**: Prefix `/manager/settings/webhooks` + resource names
2. **Controladores**: Namespace `Managers\Settings\Webhooks`
3. **Vistas**: `resources/views/managers/views/settings/webhooks/{resource}/{action}.blade.php`
4. **Layout**: `@extends('layouts.managers')`
5. **Includes**: `@include('managers.includes.card')`, `@include('theme.components.alerts')`
6. **Bootstrap**: Cards, tables, badges, buttons siguiendo Bootstrap 5.3
7. **Icons**: Font Awesome 6 (`fas fa-xxx`)
8. **AJAX**: Respuestas JSON con `success`, `message`, `redirect`
9. **SweetAlert2**: Para confirmaciones y notificaciones
10. **Paginación**: Laravel default con `withQueryString()`

### 📁 Estructura completa de archivos:

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Managers/
│   │       └── Settings/
│   │           └── Webhooks/
│   │               ├── WebhookIntegrationController.php
│   │               ├── WebhookSubscriptionController.php
│   │               ├── WebhookDeliveryController.php
│   │               └── WebhookEventController.php
│   └── Requests/
│       └── Managers/
│           └── Settings/
│               └── Webhooks/
│                   ├── StoreIntegrationRequest.php
│                   ├── UpdateIntegrationRequest.php
│                   ├── StoreSubscriptionRequest.php
│                   └── UpdateSubscriptionRequest.php

resources/
└── views/
    └── managers/
        └── views/
            └── settings/
                └── webhooks/
                    ├── integrations/
                    │   ├── index.blade.php
                    │   ├── create.blade.php
                    │   ├── edit.blade.php
                    │   └── api-keys.blade.php
                    ├── subscriptions/
                    │   ├── index.blade.php
                    │   ├── create.blade.php
                    │   └── edit.blade.php
                    ├── deliveries/
                    │   ├── index.blade.php
                    │   └── show.blade.php
                    └── events/
                        ├── index.blade.php
                        └── show.blade.php
```

---

**Documento generado por:** Claude Sonnet 4.5
**Basado en:** Análisis de controladores y vistas existentes en `Managers/Settings`
**Patrón aplicado:** Blade + Bootstrap 5.3 + Font Awesome 6 (SIN Filament)
