# WebhookHub - Arquitectura para Laravel 12

> **Arquitecto:** Adaptado a la estructura existente de Manager (Alsernet)
> **Fecha:** 2025-12-23
> **Stack:** Laravel 12.42, PHP 8.4, MySQL/MariaDB, Redis + Horizon

---

## 📋 Tabla de Contenidos

1. [Contexto y Objetivo](#contexto-y-objetivo)
2. [Stack Técnico](#stack-técnico)
3. [Arquitectura General](#arquitectura-general)
4. [Modelo de Datos](#modelo-de-datos)
5. [Sistema de Autenticación y Seguridad](#sistema-de-autenticación-y-seguridad)
6. [Procesamiento de Eventos](#procesamiento-de-eventos)
7. [API REST](#api-rest)
8. [Panel de Administración](#panel-de-administración)
9. [Guía de Implementación](#guía-de-implementación)
10. [Testing](#testing)

---

## 1. Contexto y Objetivo

### 1.1 Propósito

WebhookHub es un **sistema centralizado de gestión de webhooks** que permitirá:

- **Recibir eventos (Inbound)**: Desde PrestaShop, n8n, Make, Zapier u otras plataformas
- **Entregar webhooks (Outbound)**: A endpoints configurados con entrega fiable
- **Automatizar procesos**: Envío de correos, registro de documentos, notificaciones CRM/ERP
- **Multi-tenant**: Soporte para múltiples integraciones aisladas
- **Observabilidad**: Panel admin, logs detallados, métricas de entrega

### 1.2 Casos de Uso

```
┌─────────────────┐
│   PrestaShop    │──┐
└─────────────────┘  │
                     │
┌─────────────────┐  │     ┌──────────────────┐     ┌─────────────┐
│      n8n        │──┼────▶│  WebhookHub      │────▶│   n8n/Make  │
└─────────────────┘  │     │  (Laravel 12)    │     └─────────────┘
                     │     └──────────────────┘            │
┌─────────────────┐  │            │                        │
│   Zapier/Make   │──┘            │                        ▼
└─────────────────┘               │                 ┌─────────────┐
                                  │                 │ CRM/ERP API │
                                  ▼                 └─────────────┘
                           ┌─────────────┐
                           │ Email Queue │
                           └─────────────┘
```

**Ejemplos:**
- PrestaShop dispara `order.created` → WebhookHub → Envía email al cliente
- PrestaShop envía `document.received` → WebhookHub → Registra en BD + Notifica ERP
- n8n notifica `product.updated` → WebhookHub → Fan-out a 3 endpoints configurados

---

## 2. Stack Técnico

### 2.1 Decisiones Tecnológicas

| Componente | Tecnología | Justificación |
|-----------|-----------|---------------|
| **Framework** | Laravel 12.42 | Ya existe en el proyecto, ecosistema robusto |
| **PHP** | 8.4.15 | Versión actual del proyecto |
| **Base de Datos** | MySQL/MariaDB | BD principal del proyecto (no PostgreSQL) |
| **Cache/Cola** | Redis + Horizon | Ya configurado, ideal para jobs asíncronos |
| **Panel Admin** | **Filament v4** | Recomendado por ecosistema Laravel, RAD, sin conflictos con DevExpress |
| **Autenticación** | API Key + HMAC-SHA256 | Patrón ya usado en `SupplierSourceWebhook` |
| **Multi-tenant** | Scoping por `integration_id` | Similar a `source_id` en suppliers |
| **Logs** | Laravel Log + Telescope | Herramientas ya integradas |
| **Testing** | PHPUnit | Framework estándar del proyecto |

### 2.2 Justificación de Filament v4

**¿Por qué Filament?**
- ✅ **RAD (Rapid Application Development)**: Panel admin completo en minutos
- ✅ **Livewire 3**: Ya instalado en el proyecto (v3.7.1)
- ✅ **Sin conflictos**: No interfiere con DevExpress (backend vs frontend)
- ✅ **Ecosistema Laravel**: Mantenido por la comunidad Laravel
- ✅ **Customizable**: Componentes Tailwind, extensible
- ✅ **Features built-in**: CRUD, búsqueda, filtros, relaciones, acciones masivas

**Alternativas descartadas:**
- ❌ **Nova**: Licencia de pago, menos customizable
- ❌ **Custom Blade**: Demasiado tiempo de desarrollo
- ❌ **DevExpress backend**: No diseñado para admin panels

---

## 3. Arquitectura General

### 3.1 Flujo de Datos

```
┌────────────────────────────────────────────────────────────────┐
│                          INBOUND FLOW                          │
└────────────────────────────────────────────────────────────────┘

1. Cliente (PrestaShop) ──POST──▶ /api/v1/webhooks/inbound
                                         │
                                         ▼
2. WebhookInboundController ──valida──▶ headers (API Key, Signature, Timestamp, Nonce)
                                         │
                                         ▼
3. EventStoreService ─────guarda─────▶ webhook_events (con idempotency_key)
                                         │
                                         ▼
4. Dispatch ──────────────────────────▶ ProcessWebhookEventJob (cola: webhooks)
                                         │
                                         ├──▶ Normaliza payload
                                         ├──▶ Aplica reglas/transformaciones
                                         └──▶ Crea DeliveryJobs por cada suscripción activa


┌────────────────────────────────────────────────────────────────┐
│                         OUTBOUND FLOW                          │
└────────────────────────────────────────────────────────────────┘

1. DeliverWebhookJob (cola: deliveries) ──lee──▶ webhook_deliveries
                                                      │
                                                      ▼
2. WebhookDeliveryService ────valida────────────▶ Subscription (activa, timeout, URL)
                                                      │
                                                      ▼
3. HTTP Client ─────firma HMAC───────────────────▶ POST a endpoint destino
                                                      │
                                    ┌─────────────────┴────────────────────┐
                                    ▼                                      ▼
4a. Success (2xx) ────────▶ delivery.status = 'success'    4b. Error (4xx/5xx) ──▶ Retry con backoff
                            logs guardados                                        │
                                                                                  ▼
                                                                    max_attempts? ──▶ dead
```

### 3.2 Componentes Principales

| Componente | Responsabilidad |
|-----------|----------------|
| **WebhookInboundController** | Recibe eventos, valida auth, anti-replay, idempotencia |
| **WebhookManagementController** | CRUD de suscripciones, endpoints, reglas |
| **EventStoreService** | Guarda eventos con hash, detecta duplicados |
| **WebhookDeliveryService** | Envía webhooks, maneja firmas, logs |
| **ProcessWebhookEventJob** | Normaliza, aplica reglas, crea deliveries |
| **DeliverWebhookJob** | Ejecuta envío HTTP con reintentos |
| **WebhookSignatureService** | Genera y valida firmas HMAC-SHA256 |
| **Filament Resources** | Panel admin para gestión visual |

---

## 4. Modelo de Datos

### 4.1 Diagrama ER

```
┌──────────────────────┐       ┌──────────────────────┐
│   integrations       │       │    webhook_events     │
├──────────────────────┤       ├──────────────────────┤
│ id (PK)              │◀──┐   │ id (PK)              │
│ uid (ULID)           │   │   │ uid (ULID)           │
│ name                 │   │   │ integration_id (FK)  │
│ status               │   │   │ event_key            │
│ daily_limit          │   │   │ idempotency_key (UQ) │
│ allowed_ips          │   │   │ payload (JSON)       │
│ created_at           │   │   │ payload_hash         │
└──────────────────────┘   │   │ received_at          │
                           │   │ processed_at         │
                           │   └──────────────────────┘
                           │
                           ├───┐
                           │   │
┌──────────────────────┐   │   │   ┌──────────────────────┐
│    api_keys          │   │   │   │   subscriptions      │
├──────────────────────┤   │   │   ├──────────────────────┤
│ id (PK)              │   │   │   │ id (PK)              │
│ integration_id (FK)  │───┘   │   │ uid (ULID)           │
│ key (public)         │       │   │ integration_id (FK)  │───┐
│ secret (hashed)      │       │   │ name                 │   │
│ permissions (JSON)   │       │   │ url                  │   │
│ revoked_at           │       │   │ is_active            │   │
│ last_used_at         │       │   │ subscribed_events    │   │
└──────────────────────┘       │   │ auth_type            │   │
                               │   │ auth_config (JSON)   │   │
                               │   │ signing_secret       │   │
                               │   │ timeout_ms           │   │
┌──────────────────────┐       │   │ max_attempts         │   │
│   event_catalog      │       │   │ backoff_policy (JSON)│   │
├──────────────────────┤       │   └──────────────────────┘   │
│ id (PK)              │       │                              │
│ key (order.created)  │       │                              │
│ description          │       ├──────────────────────────────┘
│ version (v1)         │       │
│ json_schema (JSON)   │       │   ┌──────────────────────┐
│ example_payload      │       │   │ subscription_rules   │
│ is_active            │       │   ├──────────────────────┤
└──────────────────────┘       │   │ id (PK)              │
                               │   │ subscription_id (FK) │───┐
                               │   │ rule_type            │   │
                               │   │ conditions (JSON)    │   │
                               │   │ transform_template   │   │
                               │   └──────────────────────┘   │
                               │                              │
                               │                              │
┌──────────────────────┐       │   ┌──────────────────────┐   │
│ webhook_deliveries   │       │   │  delivery_logs       │   │
├──────────────────────┤       │   ├──────────────────────┤   │
│ id (PK)              │       │   │ id (PK)              │   │
│ uid (ULID)           │       │   │ delivery_id (FK)     │───┘
│ integration_id (FK)  │───────┘   │ request_headers (J)  │
│ subscription_id (FK) │───────────│ request_body (J)     │
│ event_id (FK)        │           │ response_status      │
│ status               │           │ response_headers (J) │
│ attempt_count        │           │ response_body        │
│ next_retry_at        │           │ duration_ms          │
│ last_error           │           │ created_at           │
│ last_http_status     │           └──────────────────────┘
│ created_at           │
└──────────────────────┘

IDX: (status, next_retry_at)
IDX: (integration_id, event_id)
```

### 4.2 Migraciones

#### 4.2.1 `create_webhook_integrations_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique()->comment('ULID único');
            $table->string('name', 100);
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');
            $table->string('plan', 50)->default('free')->comment('free, pro, enterprise');
            $table->integer('daily_limit')->default(1000);
            $table->json('allowed_ips')->nullable()->comment('IPs permitidas (opcional)');
            $table->json('allowed_domains')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_integrations_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_integrations');
    }
};
```

#### 4.2.2 `create_webhook_api_keys_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');

            $table->string('key', 64)->unique()->comment('Public key (API_KEY_xxx)');
            $table->string('secret', 255)->comment('Hashed secret (HMAC key)');
            $table->string('name', 100)->nullable()->comment('Key label');
            $table->json('permissions')->nullable()->comment('inbound, outbound, admin');
            $table->integer('rate_limit_per_minute')->default(60);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('integration_id', 'idx_api_keys_integration');
            $table->index('key', 'idx_api_keys_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_api_keys');
    }
};
```

#### 4.2.3 `create_webhook_event_catalog_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_event_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('order.created, document.received');
            $table->string('version', 10)->default('v1');
            $table->text('description')->nullable();
            $table->json('json_schema')->nullable()->comment('JSON Schema for validation');
            $table->json('example_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['key', 'version'], 'idx_catalog_key_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_event_catalog');
    }
};
```

#### 4.2.4 `create_webhook_events_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');

            $table->string('event_key', 100)->comment('order.created');
            $table->string('event_version', 10)->default('v1');
            $table->string('external_event_id', 255)->nullable()->comment('ID del evento original');
            $table->string('idempotency_key', 255)->unique()->comment('Para evitar duplicados');

            $table->json('payload')->comment('Payload completo recibido');
            $table->string('payload_hash', 64)->comment('SHA256 del payload');

            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'event_key'], 'idx_events_integration_key');
            $table->index('idempotency_key', 'idx_events_idempotency');
            $table->index('received_at', 'idx_events_received');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
```

#### 4.2.5 `create_webhook_subscriptions_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');

            $table->string('name', 100);
            $table->string('url', 500)->comment('Endpoint destino');
            $table->boolean('is_active')->default(true);

            // Eventos suscritos
            $table->json('subscribed_events')->comment('["order.created", "order.updated"]');

            // Autenticación
            $table->enum('auth_type', ['none', 'bearer', 'basic', 'apikey', 'custom'])
                ->default('none');
            $table->json('auth_config')->nullable()->comment('Credenciales según auth_type');

            // Firma outbound
            $table->string('signing_secret', 255)->nullable()->comment('Secret para firmar outbound');

            // Configuración de entrega
            $table->integer('timeout_ms')->default(10000)->comment('Timeout en ms');
            $table->integer('max_attempts')->default(6)->comment('Máximo de reintentos');
            $table->json('backoff_policy')->nullable()->comment('[1m, 5m, 15m, 1h, 6h, 24h]');

            $table->timestamps();

            $table->index('integration_id', 'idx_subscriptions_integration');
            $table->index('is_active', 'idx_subscriptions_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
```

#### 4.2.6 `create_webhook_subscription_rules_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscription_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained('webhook_subscriptions')
                ->onDelete('cascade');

            $table->enum('rule_type', ['all', 'any'])->default('all')
                ->comment('all = AND, any = OR');

            $table->json('conditions')->comment('
                [
                    {"field": "data.order.total", "operator": "gt", "value": 100},
                    {"field": "data.customer.email", "operator": "contains", "value": "@example.com"}
                ]
            ');

            $table->json('transform_template')->nullable()->comment('
                {
                    "customerEmail": "data.customer.email",
                    "orderTotal": "data.order.total"
                }
            ');

            $table->timestamps();

            $table->index('subscription_id', 'idx_rules_subscription');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscription_rules');
    }
};
```

#### 4.2.7 `create_webhook_deliveries_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');
            $table->foreignId('subscription_id')
                ->constrained('webhook_subscriptions')
                ->onDelete('cascade');
            $table->foreignId('event_id')
                ->constrained('webhook_events')
                ->onDelete('cascade');

            $table->enum('status', ['pending', 'sending', 'success', 'failed', 'dead'])
                ->default('pending');

            $table->integer('attempt_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('last_http_status')->nullable();
            $table->integer('last_latency_ms')->nullable();

            $table->timestamps();

            $table->index(['status', 'next_retry_at'], 'idx_deliveries_retry');
            $table->index(['integration_id', 'event_id'], 'idx_deliveries_event');
            $table->index('subscription_id', 'idx_deliveries_subscription');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
```

#### 4.2.8 `create_webhook_delivery_logs_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')
                ->constrained('webhook_deliveries')
                ->onDelete('cascade');

            $table->json('request_headers')->nullable()->comment('Sanitized headers');
            $table->json('request_body')->nullable()->comment('Sanitized body');

            $table->integer('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('response_body')->nullable()->comment('Truncated to 5000 chars');

            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('delivery_id', 'idx_delivery_logs_delivery');
            $table->index('created_at', 'idx_delivery_logs_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_delivery_logs');
    }
};
```

### 4.3 Modelos Eloquent

#### 4.3.1 `WebhookIntegration`

```php
<?php

namespace App\Models\Webhook;

use App\Traits\HasUid;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookIntegration extends Model
{
    use HasUid;

    protected $fillable = [
        'name',
        'status',
        'plan',
        'daily_limit',
        'allowed_ips',
        'allowed_domains',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'allowed_ips' => 'array',
            'allowed_domains' => 'array',
        ];
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(WebhookApiKey::class, 'integration_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(WebhookEvent::class, 'integration_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class, 'integration_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'integration_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasReachedDailyLimit(): bool
    {
        $todayEvents = $this->events()
            ->whereDate('received_at', today())
            ->count();

        return $todayEvents >= $this->daily_limit;
    }
}
```

#### 4.3.2 `WebhookApiKey`

```php
<?php

namespace App\Models\Webhook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WebhookApiKey extends Model
{
    protected $fillable = [
        'integration_id',
        'key',
        'secret',
        'name',
        'permissions',
        'rate_limit_per_minute',
        'revoked_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->key)) {
                $model->key = 'whk_' . Str::random(40); // whk_xxx (webhook key)
            }
            if (empty($model->secret)) {
                $rawSecret = Str::random(64);
                $model->secret = hash('sha256', $rawSecret);
                // IMPORTANTE: Guardar $rawSecret temporalmente para mostrar al usuario UNA VEZ
            }
        });
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(WebhookIntegration::class, 'integration_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function validateSecret(string $providedSecret): bool
    {
        return hash_equals($this->secret, hash('sha256', $providedSecret));
    }

    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
```

#### 4.3.3 `WebhookEvent`

```php
<?php

namespace App\Models\Webhook;

use App\Traits\HasUid;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEvent extends Model
{
    use HasUid;

    protected $fillable = [
        'integration_id',
        'event_key',
        'event_version',
        'external_event_id',
        'idempotency_key',
        'payload',
        'payload_hash',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(WebhookIntegration::class, 'integration_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'event_id');
    }

    public static function generateHash(array $payload): string
    {
        return hash('sha256', json_encode($payload));
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function markAsProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }
}
```

#### 4.3.4 `WebhookSubscription`

```php
<?php

namespace App\Models\Webhook;

use App\Traits\HasUid;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    use HasUid;

    protected $fillable = [
        'integration_id',
        'name',
        'url',
        'is_active',
        'subscribed_events',
        'auth_type',
        'auth_config',
        'signing_secret',
        'timeout_ms',
        'max_attempts',
        'backoff_policy',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscribed_events' => 'array',
            'auth_config' => 'array',
            'backoff_policy' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->signing_secret)) {
                $model->signing_secret = \Illuminate\Support\Str::random(64);
            }
            if (empty($model->backoff_policy)) {
                $model->backoff_policy = [60, 300, 900, 3600, 21600, 86400]; // seconds
            }
        });
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(WebhookIntegration::class, 'integration_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(WebhookSubscriptionRule::class, 'subscription_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    public function isSubscribedTo(string $eventKey): bool
    {
        return in_array($eventKey, $this->subscribed_events ?? []);
    }

    public function getBackoffDelay(int $attemptCount): int
    {
        $policy = $this->backoff_policy ?? [60, 300, 900, 3600, 21600, 86400];
        $index = min($attemptCount - 1, count($policy) - 1);

        return $policy[$index] ?? 86400; // Default: 24h
    }
}
```

#### 4.3.5 `WebhookDelivery`

```php
<?php

namespace App\Models\Webhook;

use App\Traits\HasUid;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookDelivery extends Model
{
    use HasUid;

    protected $fillable = [
        'integration_id',
        'subscription_id',
        'event_id',
        'status',
        'attempt_count',
        'next_retry_at',
        'last_error',
        'last_http_status',
        'last_latency_ms',
    ];

    protected function casts(): array
    {
        return [
            'next_retry_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(WebhookIntegration::class, 'integration_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'event_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookDeliveryLog::class, 'delivery_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
            ->where('next_retry_at', '<=', now());
    }

    public function scopeDead($query)
    {
        return $query->where('status', 'dead');
    }

    public function incrementAttempt(): void
    {
        $this->increment('attempt_count');
    }

    public function markAsSuccess(int $httpStatus, int $latencyMs): void
    {
        $this->update([
            'status' => 'success',
            'last_http_status' => $httpStatus,
            'last_latency_ms' => $latencyMs,
            'last_error' => null,
        ]);
    }

    public function markAsFailed(string $error, ?int $httpStatus, int $latencyMs): void
    {
        $backoffDelay = $this->subscription->getBackoffDelay($this->attempt_count + 1);

        $status = ($this->attempt_count + 1 >= $this->subscription->max_attempts)
            ? 'dead'
            : 'failed';

        $this->update([
            'status' => $status,
            'last_error' => $error,
            'last_http_status' => $httpStatus,
            'last_latency_ms' => $latencyMs,
            'next_retry_at' => $status === 'failed' ? now()->addSeconds($backoffDelay) : null,
        ]);
    }
}
```

---

## 5. Sistema de Autenticación y Seguridad

### 5.1 Contrato de Seguridad Inbound

**Headers obligatorios:**

```http
POST /api/v1/webhooks/inbound

X-Webhook-Api-Key: whk_xxxxxxxxxxxxxxxxxx
X-Webhook-Signature: sha256=abc123...
X-Webhook-Timestamp: 1704067200
X-Webhook-Nonce: uuid-v4
Idempotency-Key: uuid-v4
Content-Type: application/json
```

**Canonical String para firma HMAC:**

```
canonical = HTTP_METHOD + "\n" +
            REQUEST_PATH + "\n" +
            TIMESTAMP + "\n" +
            NONCE + "\n" +
            SHA256(BODY_RAW)

signature = HMAC-SHA256(canonical, API_SECRET)
```

**Ejemplo en PHP:**

```php
$method = 'POST';
$path = '/api/v1/webhooks/inbound';
$timestamp = time();
$nonce = Str::uuid();
$bodyHash = hash('sha256', json_encode($payload));

$canonical = "{$method}\n{$path}\n{$timestamp}\n{$nonce}\n{$bodyHash}";
$signature = hash_hmac('sha256', $canonical, $apiSecret);

// Header enviado:
// X-Webhook-Signature: sha256={$signature}
```

### 5.2 Validaciones de Seguridad

#### 5.2.1 Anti-Replay Protection

```php
// En WebhookInboundController

protected function validateTimestamp(string $timestamp): bool
{
    $now = time();
    $diff = abs($now - (int)$timestamp);

    return $diff <= 300; // ±5 minutos
}

protected function validateNonce(string $nonce, int $integrationId): bool
{
    $key = "webhook:nonce:{$integrationId}:{$nonce}";

    if (Cache::has($key)) {
        return false; // Nonce duplicado
    }

    Cache::put($key, true, 600); // TTL 10 minutos
    return true;
}
```

#### 5.2.2 Idempotencia

```php
// En EventStoreService

public function storeEvent(array $data): ?WebhookEvent
{
    $idempotencyKey = $data['idempotency_key'] ?? null;

    if ($idempotencyKey) {
        $existing = WebhookEvent::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            Log::info('Idempotent request detected', [
                'idempotency_key' => $idempotencyKey,
                'event_id' => $existing->id,
            ]);

            return $existing; // Retornar evento existente sin duplicar
        }
    }

    return WebhookEvent::create([
        'integration_id' => $data['integration_id'],
        'event_key' => $data['event_key'],
        'event_version' => $data['event_version'] ?? 'v1',
        'external_event_id' => $data['external_event_id'] ?? null,
        'idempotency_key' => $idempotencyKey ?? Str::uuid(),
        'payload' => $data['payload'],
        'payload_hash' => WebhookEvent::generateHash($data['payload']),
        'received_at' => now(),
    ]);
}
```

### 5.3 Firma Outbound (WebhookHub → Endpoint)

**Headers enviados al endpoint destino:**

```http
POST {subscription.url}

X-WebhookHub-Event: order.created
X-WebhookHub-Delivery-Id: 01JGXXX
X-WebhookHub-Timestamp: 1704067200
X-WebhookHub-Signature: sha256=abc123...
X-WebhookHub-Idempotency: uuid-v4
Content-Type: application/json
```

**Canonical string outbound:**

```php
// En WebhookDeliveryService

protected function generateSignature(WebhookDelivery $delivery, array $payload): string
{
    $timestamp = time();
    $bodyHash = hash('sha256', json_encode($payload));

    $canonical = "{$timestamp}\n{$delivery->uid}\n{$bodyHash}";

    return hash_hmac('sha256', $canonical, $delivery->subscription->signing_secret);
}
```

**Validación en endpoint receptor (ejemplo):**

```php
// En el endpoint del cliente (ej: n8n webhook)

$receivedSignature = $_SERVER['HTTP_X_WEBHOOKHUB_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_WEBHOOKHUB_TIMESTAMP'] ?? '';
$deliveryId = $_SERVER['HTTP_X_WEBHOOKHUB_DELIVERY_ID'] ?? '';
$body = file_get_contents('php://input');

$bodyHash = hash('sha256', $body);
$canonical = "{$timestamp}\n{$deliveryId}\n{$bodyHash}";
$expectedSignature = 'sha256=' . hash_hmac('sha256', $canonical, $signingSecret);

if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(401);
    die('Invalid signature');
}
```

---

## 6. Procesamiento de Eventos

### 6.1 Flujo Completo

```
1. POST /api/v1/webhooks/inbound
   └─▶ WebhookInboundController::receive()
       ├─ Valida API Key
       ├─ Valida Signature HMAC
       ├─ Valida Timestamp (±5min)
       ├─ Valida Nonce (anti-replay)
       ├─ Verifica límite diario
       └─ EventStoreService::storeEvent()
           ├─ Chequea idempotencia
           ├─ Guarda en webhook_events
           └─ Dispatch ProcessWebhookEventJob
               ├─ Normaliza payload
               ├─ Busca subscriptions activas + reglas
               ├─ Filtra por subscribed_events
               ├─ Aplica condiciones (rules)
               ├─ Aplica transformaciones
               └─ Crea WebhookDelivery por cada match
                   └─ Dispatch DeliverWebhookJob (delayed si retry)

2. DeliverWebhookJob (queue: deliveries)
   └─▶ WebhookDeliveryService::deliver()
       ├─ Prepara payload normalizado
       ├─ Genera firma HMAC outbound
       ├─ Construye headers auth según subscription.auth_type
       ├─ HTTP::timeout($timeout)->post($url, $payload)
       └─ Log resultado en webhook_delivery_logs
           ├─ 2xx → markAsSuccess()
           ├─ 4xx (no retryable) → markAsFailed() + status=dead
           └─ 5xx/timeout → markAsFailed() + schedule retry
```

### 6.2 Jobs

#### 6.2.1 `ProcessWebhookEventJob`

```php
<?php

namespace App\Jobs\Webhook;

use App\Models\Webhook\WebhookDelivery;
use App\Models\Webhook\WebhookEvent;
use App\Models\Webhook\WebhookSubscription;
use App\Services\Webhook\WebhookRuleEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessWebhookEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        protected int $eventId
    ) {}

    public function handle(WebhookRuleEngineService $ruleEngine): void
    {
        $event = WebhookEvent::find($this->eventId);

        if (!$event || $event->isProcessed()) {
            return;
        }

        // Buscar subscriptions activas para este evento
        $subscriptions = WebhookSubscription::where('integration_id', $event->integration_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn($sub) => $sub->isSubscribedTo($event->event_key));

        foreach ($subscriptions as $subscription) {
            // Aplicar reglas de filtrado
            if (!$ruleEngine->shouldDeliver($subscription, $event->payload)) {
                Log::info('Event filtered by rules', [
                    'subscription_id' => $subscription->id,
                    'event_id' => $event->id,
                ]);
                continue;
            }

            // Aplicar transformación
            $transformedPayload = $ruleEngine->transformPayload($subscription, $event->payload);

            // Crear delivery
            $delivery = WebhookDelivery::create([
                'uid' => Str::ulid(),
                'integration_id' => $event->integration_id,
                'subscription_id' => $subscription->id,
                'event_id' => $event->id,
                'status' => 'pending',
                'attempt_count' => 0,
            ]);

            Log::info('Delivery created', [
                'delivery_id' => $delivery->id,
                'subscription_id' => $subscription->id,
                'event_id' => $event->id,
            ]);

            // Dispatch job de entrega
            DeliverWebhookJob::dispatch($delivery->id, $transformedPayload)
                ->onQueue('deliveries');
        }

        $event->markAsProcessed();
    }
}
```

#### 6.2.2 `DeliverWebhookJob`

```php
<?php

namespace App\Jobs\Webhook;

use App\Models\Webhook\WebhookDelivery;
use App\Services\Webhook\WebhookDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1; // Manejamos reintentos manualmente
    public int $timeout = 30;

    public function __construct(
        protected int $deliveryId,
        protected array $payload
    ) {}

    public function handle(WebhookDeliveryService $deliveryService): void
    {
        $delivery = WebhookDelivery::with('subscription')->find($this->deliveryId);

        if (!$delivery) {
            Log::error('Delivery not found', ['delivery_id' => $this->deliveryId]);
            return;
        }

        if ($delivery->status === 'success' || $delivery->status === 'dead') {
            return; // Ya procesado o muerto
        }

        // Marcar como enviando
        $delivery->update(['status' => 'sending']);
        $delivery->incrementAttempt();

        try {
            $result = $deliveryService->deliver($delivery, $this->payload);

            if ($result['success']) {
                $delivery->markAsSuccess($result['http_status'], $result['latency_ms']);

                Log::info('Webhook delivered successfully', [
                    'delivery_id' => $delivery->id,
                    'http_status' => $result['http_status'],
                ]);
            } else {
                $this->handleFailure($delivery, $result);
            }
        } catch (\Exception $e) {
            $this->handleFailure($delivery, [
                'success' => false,
                'error' => $e->getMessage(),
                'http_status' => null,
                'latency_ms' => 0,
            ]);
        }
    }

    protected function handleFailure(WebhookDelivery $delivery, array $result): void
    {
        $delivery->markAsFailed(
            $result['error'],
            $result['http_status'],
            $result['latency_ms']
        );

        Log::warning('Webhook delivery failed', [
            'delivery_id' => $delivery->id,
            'attempt' => $delivery->attempt_count,
            'http_status' => $result['http_status'],
            'error' => $result['error'],
        ]);

        // Programar reintento si no está muerto
        if ($delivery->status === 'failed' && $delivery->next_retry_at) {
            $delay = $delivery->next_retry_at->diffInSeconds(now());

            self::dispatch($delivery->id, $this->payload)
                ->onQueue('deliveries')
                ->delay(now()->addSeconds($delay));

            Log::info('Retry scheduled', [
                'delivery_id' => $delivery->id,
                'next_retry_at' => $delivery->next_retry_at->toIso8601String(),
            ]);
        }
    }
}
```

### 6.3 Servicios

#### 6.3.1 `WebhookDeliveryService`

```php
<?php

namespace App\Services\Webhook;

use App\Models\Webhook\WebhookDelivery;
use App\Models\Webhook\WebhookDeliveryLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDeliveryService
{
    public function deliver(WebhookDelivery $delivery, array $payload): array
    {
        $subscription = $delivery->subscription;
        $startTime = microtime(true);

        // Preparar payload normalizado
        $normalizedPayload = $this->buildNormalizedPayload($delivery, $payload);

        // Generar firma HMAC
        $timestamp = time();
        $signature = $this->generateSignature($delivery, $normalizedPayload, $timestamp);

        // Construir headers
        $headers = $this->buildHeaders($subscription, $delivery, $signature, $timestamp);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($subscription->timeout_ms / 1000)
                ->post($subscription->url, $normalizedPayload);

            $latencyMs = (int)((microtime(true) - $startTime) * 1000);

            // Log del request/response
            $this->logDelivery($delivery, $headers, $normalizedPayload, $response, $latencyMs);

            $httpStatus = $response->status();
            $isSuccess = $httpStatus >= 200 && $httpStatus < 300;

            return [
                'success' => $isSuccess,
                'http_status' => $httpStatus,
                'latency_ms' => $latencyMs,
                'error' => $isSuccess ? null : "HTTP {$httpStatus}: " . $response->body(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);

            $this->logDelivery($delivery, $headers, $normalizedPayload, null, $latencyMs, $e->getMessage());

            return [
                'success' => false,
                'http_status' => null,
                'latency_ms' => $latencyMs,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    protected function buildNormalizedPayload(WebhookDelivery $delivery, array $transformedData): array
    {
        $event = $delivery->event;

        return [
            'meta' => [
                'event_key' => $event->event_key,
                'event_version' => $event->event_version,
                'event_id' => $event->uid,
                'delivery_id' => $delivery->uid,
                'integration_id' => $delivery->integration_id,
                'occurred_at' => $event->received_at->toIso8601String(),
                'sent_at' => now()->toIso8601String(),
            ],
            'data' => $transformedData,
        ];
    }

    protected function generateSignature(WebhookDelivery $delivery, array $payload, int $timestamp): string
    {
        $bodyHash = hash('sha256', json_encode($payload));
        $canonical = "{$timestamp}\n{$delivery->uid}\n{$bodyHash}";

        return 'sha256=' . hash_hmac('sha256', $canonical, $delivery->subscription->signing_secret);
    }

    protected function buildHeaders($subscription, $delivery, string $signature, int $timestamp): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-WebhookHub-Event' => $delivery->event->event_key,
            'X-WebhookHub-Delivery-Id' => $delivery->uid,
            'X-WebhookHub-Timestamp' => (string)$timestamp,
            'X-WebhookHub-Signature' => $signature,
            'X-WebhookHub-Idempotency' => $delivery->event->idempotency_key,
        ];

        // Añadir autenticación según tipo
        match ($subscription->auth_type) {
            'bearer' => $headers['Authorization'] = 'Bearer ' . ($subscription->auth_config['token'] ?? ''),
            'basic' => $headers['Authorization'] = 'Basic ' . base64_encode(
                ($subscription->auth_config['username'] ?? '') . ':' . ($subscription->auth_config['password'] ?? '')
            ),
            'apikey' => $headers[$subscription->auth_config['header_name'] ?? 'X-API-Key'] = $subscription->auth_config['api_key'] ?? '',
            default => null,
        };

        return $headers;
    }

    protected function logDelivery(
        WebhookDelivery $delivery,
        array $requestHeaders,
        array $requestBody,
        $response,
        int $durationMs,
        ?string $error = null
    ): void {
        $sanitizedHeaders = $this->sanitizeHeaders($requestHeaders);

        WebhookDeliveryLog::create([
            'delivery_id' => $delivery->id,
            'request_headers' => $sanitizedHeaders,
            'request_body' => $requestBody,
            'response_status' => $response?->status(),
            'response_headers' => $response ? $response->headers() : null,
            'response_body' => $response ? substr($response->body(), 0, 5000) : $error,
            'duration_ms' => $durationMs,
        ]);
    }

    protected function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['Authorization', 'X-API-Key'];

        foreach ($sensitive as $key) {
            if (isset($headers[$key])) {
                $headers[$key] = '***REDACTED***';
            }
        }

        return $headers;
    }
}
```

#### 6.3.2 `WebhookRuleEngineService`

```php
<?php

namespace App\Services\Webhook;

use App\Models\Webhook\WebhookSubscription;
use Illuminate\Support\Arr;

class WebhookRuleEngineService
{
    public function shouldDeliver(WebhookSubscription $subscription, array $payload): bool
    {
        $rules = $subscription->rules;

        if ($rules->isEmpty()) {
            return true; // Sin reglas = entregar siempre
        }

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule, $payload);

            if ($rule->rule_type === 'all' && !$result) {
                return false; // AND logic: una falla = rechazar
            }

            if ($rule->rule_type === 'any' && $result) {
                return true; // OR logic: una pasa = aceptar
            }
        }

        // Si llegamos aquí con 'any' = todas fallaron
        return $rules->first()->rule_type !== 'any';
    }

    protected function evaluateRule($rule, array $payload): bool
    {
        $conditions = $rule->conditions;

        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $expectedValue = $condition['value'];

            $actualValue = Arr::get($payload, $field);

            if (!$this->evaluateCondition($actualValue, $operator, $expectedValue)) {
                if ($rule->rule_type === 'all') {
                    return false;
                }
            } else {
                if ($rule->rule_type === 'any') {
                    return true;
                }
            }
        }

        return $rule->rule_type === 'all';
    }

    protected function evaluateCondition($actual, string $operator, $expected): bool
    {
        return match ($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'contains' => is_string($actual) && str_contains($actual, $expected),
            'in' => in_array($actual, (array)$expected),
            'gt' => $actual > $expected,
            'gte' => $actual >= $expected,
            'lt' => $actual < $expected,
            'lte' => $actual <= $expected,
            default => false,
        };
    }

    public function transformPayload(WebhookSubscription $subscription, array $payload): array
    {
        $rules = $subscription->rules->first();

        if (!$rules || !$rules->transform_template) {
            return $payload; // Passthrough
        }

        $template = $rules->transform_template;
        $transformed = [];

        foreach ($template as $targetKey => $sourcePath) {
            $transformed[$targetKey] = Arr::get($payload, $sourcePath);
        }

        return $transformed;
    }
}
```

---

## 7. API REST

### 7.1 Rutas (`routes/api.php`)

```php
<?php

use App\Http\Controllers\Api\Webhook\WebhookInboundController;
use App\Http\Controllers\Api\Webhook\WebhookManagementController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC INBOUND WEBHOOK ROUTES (sin autenticación Sanctum)
// Validación por API Key + Signature en controller
// ============================================================
Route::prefix('v1/webhooks')->middleware('throttle:300,1')->group(function () {

    // Recepción de webhooks
    Route::post('/inbound', [WebhookInboundController::class, 'receive']);

    // Catálogo de eventos (público para docs)
    Route::get('/events/catalog', [WebhookInboundController::class, 'catalog']);
});


// ============================================================
// MANAGEMENT API (requiere autenticación)
// Gestión de subscriptions, deliveries, etc.
// ============================================================
Route::prefix('v1/webhooks')->middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // Subscriptions
    Route::apiResource('subscriptions', WebhookManagementController::class);
    Route::post('subscriptions/{id}/test', [WebhookManagementController::class, 'testSubscription']);
    Route::post('subscriptions/{id}/rotate-secret', [WebhookManagementController::class, 'rotateSecret']);

    // Deliveries
    Route::get('deliveries', [WebhookManagementController::class, 'listDeliveries']);
    Route::get('deliveries/{id}', [WebhookManagementController::class, 'showDelivery']);
    Route::post('deliveries/{id}/retry', [WebhookManagementController::class, 'retryDelivery']);
    Route::post('deliveries/bulk-retry', [WebhookManagementController::class, 'bulkRetry']);

    // Events
    Route::get('events', [WebhookManagementController::class, 'listEvents']);
    Route::get('events/{id}', [WebhookManagementController::class, 'showEvent']);
    Route::post('events/{id}/replay', [WebhookManagementController::class, 'replayEvent']);
});
```

### 7.2 Controladores

#### 7.2.1 `WebhookInboundController`

```php
<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\Webhook\ProcessWebhookEventJob;
use App\Models\Webhook\WebhookApiKey;
use App\Models\Webhook\WebhookEvent;
use App\Models\Webhook\WebhookEventCatalog;
use App\Services\Webhook\EventStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookInboundController extends Controller
{
    public function __construct(
        protected EventStoreService $eventStore
    ) {}

    /**
     * POST /api/v1/webhooks/inbound
     */
    public function receive(Request $request): JsonResponse
    {
        // 1. Validar headers obligatorios
        $apiKey = $request->header('X-Webhook-Api-Key');
        $signature = $request->header('X-Webhook-Signature');
        $timestamp = $request->header('X-Webhook-Timestamp');
        $nonce = $request->header('X-Webhook-Nonce');
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$apiKey || !$signature || !$timestamp || !$nonce) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required headers',
            ], 400);
        }

        // 2. Validar API Key
        $keyModel = WebhookApiKey::where('key', $apiKey)
            ->whereNull('revoked_at')
            ->with('integration')
            ->first();

        if (!$keyModel) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        $integration = $keyModel->integration;

        if (!$integration->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Integration is not active',
            ], 403);
        }

        // 3. Validar timestamp (anti-replay)
        if (!$this->validateTimestamp($timestamp)) {
            return response()->json([
                'success' => false,
                'message' => 'Timestamp out of acceptable range',
            ], 400);
        }

        // 4. Validar nonce (anti-replay)
        if (!$this->validateNonce($nonce, $integration->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Nonce already used',
            ], 400);
        }

        // 5. Validar firma HMAC
        if (!$this->validateSignature($request, $keyModel, $timestamp, $nonce, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        // 6. Verificar límite diario
        if ($integration->hasReachedDailyLimit()) {
            return response()->json([
                'success' => false,
                'message' => 'Daily limit reached',
            ], 429);
        }

        // 7. Validar payload
        $payload = $request->json()->all();

        if (empty($payload['event_key'])) {
            return response()->json([
                'success' => false,
                'message' => 'Missing event_key in payload',
            ], 422);
        }

        // 8. Guardar evento
        $event = $this->eventStore->storeEvent([
            'integration_id' => $integration->id,
            'event_key' => $payload['event_key'],
            'event_version' => $payload['event_version'] ?? 'v1',
            'external_event_id' => $payload['external_event_id'] ?? null,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
        ]);

        if (!$event->wasRecentlyCreated) {
            // Idempotencia: evento duplicado
            return response()->json([
                'success' => true,
                'message' => 'Event already processed (idempotent)',
                'event_id' => $event->uid,
            ], 200);
        }

        // 9. Dispatch job de procesamiento
        ProcessWebhookEventJob::dispatch($event->id)
            ->onQueue('webhooks');

        // 10. Actualizar stats
        $keyModel->touchLastUsed();

        Log::info('Webhook event received', [
            'integration_id' => $integration->id,
            'event_key' => $payload['event_key'],
            'event_id' => $event->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event accepted for processing',
            'event_id' => $event->uid,
        ], 202);
    }

    protected function validateTimestamp(string $timestamp): bool
    {
        $now = time();
        $diff = abs($now - (int)$timestamp);

        return $diff <= 300; // ±5 minutos
    }

    protected function validateNonce(string $nonce, int $integrationId): bool
    {
        $key = "webhook:nonce:{$integrationId}:{$nonce}";

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, 600); // 10 minutos
        return true;
    }

    protected function validateSignature(
        Request $request,
        WebhookApiKey $keyModel,
        string $timestamp,
        string $nonce,
        string $providedSignature
    ): bool {
        $method = $request->method();
        $path = $request->path();
        $bodyHash = hash('sha256', $request->getContent());

        $canonical = "{$method}\n{$path}\n{$timestamp}\n{$nonce}\n{$bodyHash}";

        // Generar firma esperada con el secret hasheado
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $canonical, $keyModel->secret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * GET /api/v1/webhooks/events/catalog
     */
    public function catalog(): JsonResponse
    {
        $events = WebhookEventCatalog::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}
```

### 7.3 Ejemplos de Payloads

#### 7.3.1 `order.created` (v1)

```json
{
  "event_key": "order.created",
  "event_version": "v1",
  "external_event_id": "PS_ORDER_12345",
  "meta": {
    "source": "prestashop",
    "shop_id": 1,
    "occurred_at": "2026-01-15T10:30:00Z"
  },
  "data": {
    "order": {
      "id": 12345,
      "reference": "XKBKNABJK",
      "status": "payment_accepted",
      "total_paid": 125.50,
      "total_shipping": 8.50,
      "currency": "EUR",
      "payment_method": "stripe",
      "created_at": "2026-01-15T10:30:00Z"
    },
    "customer": {
      "id": 789,
      "email": "cliente@example.com",
      "firstname": "Juan",
      "lastname": "Pérez",
      "phone": "+34600123456"
    },
    "addresses": {
      "shipping": {
        "address1": "Calle Mayor 123",
        "city": "Madrid",
        "postcode": "28001",
        "country": "ES"
      },
      "billing": {
        "address1": "Calle Mayor 123",
        "city": "Madrid",
        "postcode": "28001",
        "country": "ES"
      }
    },
    "items": [
      {
        "product_id": 456,
        "product_name": "Camiseta Roja",
        "quantity": 2,
        "unit_price": 25.00,
        "total_price": 50.00
      },
      {
        "product_id": 457,
        "product_name": "Pantalón Vaquero",
        "quantity": 1,
        "unit_price": 67.00,
        "total_price": 67.00
      }
    ],
    "carrier": {
      "id": 3,
      "name": "SEUR",
      "tracking_number": "SEUR123456789"
    }
  },
  "links": {
    "order_url": "https://mitienda.com/admin/orders/12345",
    "customer_url": "https://mitienda.com/admin/customers/789"
  }
}
```

#### 7.3.2 `document.received` (v1)

```json
{
  "event_key": "document.received",
  "event_version": "v1",
  "external_event_id": "DOC_RCV_98765",
  "meta": {
    "source": "prestashop",
    "shop_id": 1,
    "occurred_at": "2026-01-15T11:00:00Z"
  },
  "data": {
    "document": {
      "id": 98765,
      "type": "invoice",
      "order_id": 12345,
      "order_reference": "XKBKNABJK",
      "url": "https://storage.example.com/documents/invoice_98765.pdf",
      "filename": "invoice_98765.pdf",
      "mime_type": "application/pdf",
      "size_bytes": 245678,
      "uploaded_at": "2026-01-15T11:00:00Z"
    },
    "validation": {
      "status": "pending",
      "required_fields": ["order_reference", "total_amount", "issue_date"],
      "missing_fields": []
    },
    "order": {
      "id": 12345,
      "reference": "XKBKNABJK",
      "customer_email": "cliente@example.com"
    }
  },
  "links": {
    "document_url": "https://mitienda.com/admin/documents/98765",
    "order_url": "https://mitienda.com/admin/orders/12345"
  }
}
```

---

## 8. Panel de Administración

### 8.1 Instalación de Filament v4

```bash
# 1. Instalar Filament
composer require filament/filament:"^4.0"

# 2. Publicar assets
php artisan filament:install --panels

# 3. Crear usuario admin
php artisan make:filament-user

# 4. Configurar panel (config/filament.php)
# Ya viene configurado por defecto en /admin
```

### 8.2 Recursos Filament

#### 8.2.1 `IntegrationResource`

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IntegrationResource\Pages;
use App\Models\Webhook\WebhookIntegration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IntegrationResource extends Resource
{
    protected static ?string $model = WebhookIntegration::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'Integrations';
    protected static ?string $navigationGroup = 'WebhookHub';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(100),

            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'disabled' => 'Disabled',
                ])
                ->default('active')
                ->required(),

            Forms\Components\Select::make('plan')
                ->options([
                    'free' => 'Free',
                    'pro' => 'Pro',
                    'enterprise' => 'Enterprise',
                ])
                ->default('free'),

            Forms\Components\TextInput::make('daily_limit')
                ->numeric()
                ->default(1000),

            Forms\Components\TagsInput::make('allowed_ips')
                ->placeholder('192.168.1.1'),

            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'disabled',
                    ]),

                Tables\Columns\TextColumn::make('plan')
                    ->badge(),

                Tables\Columns\TextColumn::make('events_count')
                    ->counts('events')
                    ->label('Events'),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Subscriptions'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'disabled' => 'Disabled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('api_keys')
                    ->url(fn ($record) => route('filament.resources.integrations.api-keys', $record))
                    ->icon('heroicon-o-key'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIntegrations::route('/'),
            'create' => Pages\CreateIntegration::route('/create'),
            'edit' => Pages\EditIntegration::route('/{record}/edit'),
        ];
    }
}
```

#### 8.2.2 `SubscriptionResource`

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Webhook\WebhookSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = WebhookSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';
    protected static ?string $navigationLabel = 'Subscriptions';
    protected static ?string $navigationGroup = 'WebhookHub';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('integration_id')
                ->relationship('integration', 'name')
                ->required(),

            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('url')
                ->url()
                ->required()
                ->maxLength(500),

            Forms\Components\Toggle::make('is_active')
                ->default(true),

            Forms\Components\TagsInput::make('subscribed_events')
                ->placeholder('order.created')
                ->helperText('Events this subscription will receive'),

            Forms\Components\Select::make('auth_type')
                ->options([
                    'none' => 'None',
                    'bearer' => 'Bearer Token',
                    'basic' => 'Basic Auth',
                    'apikey' => 'API Key',
                    'custom' => 'Custom',
                ])
                ->default('none'),

            Forms\Components\KeyValue::make('auth_config')
                ->keyLabel('Field')
                ->valueLabel('Value'),

            Forms\Components\TextInput::make('timeout_ms')
                ->numeric()
                ->default(10000)
                ->suffix('ms'),

            Forms\Components\TextInput::make('max_attempts')
                ->numeric()
                ->default(6),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('integration.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('url')
                    ->limit(40),

                Tables\Columns\ToggleColumn::make('is_active'),

                Tables\Columns\TextColumn::make('deliveries_count')
                    ->counts('deliveries')
                    ->label('Deliveries'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('test')
                    ->icon('heroicon-o-beaker')
                    ->action(fn ($record) => static::testWebhook($record))
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('rotate_secret')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn ($record) => static::rotateSecret($record))
                    ->requiresConfirmation(),
            ]);
    }

    protected static function testWebhook($subscription): void
    {
        // Implementar lógica de envío de webhook de prueba
        \App\Jobs\Webhook\DeliverWebhookJob::dispatch(
            $subscription->id,
            ['test' => true, 'message' => 'Test webhook from Filament']
        )->onQueue('deliveries');

        \Filament\Notifications\Notification::make()
            ->title('Test webhook sent')
            ->success()
            ->send();
    }

    protected static function rotateSecret($subscription): void
    {
        $subscription->update([
            'signing_secret' => \Illuminate\Support\Str::random(64),
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Signing secret rotated')
            ->body('New secret: ' . $subscription->signing_secret)
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
```

#### 8.2.3 `DeliveryResource`

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Models\Webhook\WebhookDelivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveryResource extends Resource
{
    protected static ?string $model = WebhookDelivery::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Deliveries';
    protected static ?string $navigationGroup = 'WebhookHub';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'info' => 'sending',
                        'success' => 'success',
                        'warning' => 'failed',
                        'danger' => 'dead',
                    ]),

                Tables\Columns\TextColumn::make('subscription.name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('event.event_key')
                    ->label('Event'),

                Tables\Columns\TextColumn::make('attempt_count')
                    ->label('Attempts'),

                Tables\Columns\TextColumn::make('last_http_status')
                    ->badge(),

                Tables\Columns\TextColumn::make('next_retry_at')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sending' => 'Sending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'dead' => 'Dead',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn ($record) => in_array($record->status, ['failed', 'dead']))
                    ->action(function ($record) {
                        \App\Jobs\Webhook\DeliverWebhookJob::dispatch(
                            $record->id,
                            $record->event->payload
                        )->onQueue('deliveries');

                        \Filament\Notifications\Notification::make()
                            ->title('Delivery retried')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('retry_selected')
                    ->label('Retry Selected')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function ($records) {
                        foreach ($records as $delivery) {
                            \App\Jobs\Webhook\DeliverWebhookJob::dispatch(
                                $delivery->id,
                                $delivery->event->payload
                            )->onQueue('deliveries');
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Deliveries retried')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'view' => Pages\ViewDelivery::route('/{record}'),
        ];
    }
}
```

---

## 9. Guía de Implementación

### 9.1 Orden de Implementación

```
Fase 1: Fundamentos (Semana 1)
├── 1.1 Crear migraciones (integrations, api_keys, event_catalog)
├── 1.2 Crear modelos (WebhookIntegration, WebhookApiKey, WebhookEventCatalog)
├── 1.3 Seeders para event_catalog
├── 1.4 Tests unitarios de modelos
└── 1.5 Instalar Filament v4

Fase 2: Inbound (Semana 2)
├── 2.1 Crear migraciones (events, subscriptions, rules)
├── 2.2 Crear modelos (WebhookEvent, WebhookSubscription, WebhookSubscriptionRule)
├── 2.3 WebhookInboundController + EventStoreService
├── 2.4 ProcessWebhookEventJob
├── 2.5 WebhookRuleEngineService
├── 2.6 Tests de integración inbound
└── 2.7 Filament Resources (Integrations, Events)

Fase 3: Outbound (Semana 3)
├── 3.1 Crear migraciones (deliveries, delivery_logs)
├── 3.2 Crear modelos (WebhookDelivery, WebhookDeliveryLog)
├── 3.3 WebhookDeliveryService
├── 3.4 DeliverWebhookJob
├── 3.5 Sistema de reintentos con backoff
├── 3.6 Tests de integración outbound
└── 3.7 Filament Resources (Subscriptions, Deliveries)

Fase 4: Gestión y Observabilidad (Semana 4)
├── 4.1 WebhookManagementController (API REST)
├── 4.2 Commands Artisan (retry-failed, cleanup-old)
├── 4.3 Widgets Filament (stats, gráficos)
├── 4.4 Logs y métricas
├── 4.5 Documentación API (OpenAPI/Swagger)
└── 4.6 Tests E2E completos
```

### 9.2 Commands Artisan

#### 9.2.1 `RetryFailedDeliveriesCommand`

```php
<?php

namespace App\Console\Commands\Webhook;

use App\Jobs\Webhook\DeliverWebhookJob;
use App\Models\Webhook\WebhookDelivery;
use Illuminate\Console\Command;

class RetryFailedDeliveriesCommand extends Command
{
    protected $signature = 'webhookhub:retry-failed {--limit=100}';
    protected $description = 'Retry failed webhook deliveries that are ready for retry';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $deliveries = WebhookDelivery::retryable()
            ->limit($limit)
            ->get();

        if ($deliveries->isEmpty()) {
            $this->info('No deliveries ready for retry');
            return 0;
        }

        $this->info("Found {$deliveries->count()} deliveries to retry");

        $bar = $this->output->createProgressBar($deliveries->count());

        foreach ($deliveries as $delivery) {
            DeliverWebhookJob::dispatch($delivery->id, $delivery->event->payload)
                ->onQueue('deliveries');

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Deliveries dispatched successfully');

        return 0;
    }
}
```

#### 9.2.2 `CleanupOldEventsCommand`

```php
<?php

namespace App\Console\Commands\Webhook;

use App\Models\Webhook\WebhookEvent;
use Illuminate\Console\Command;

class CleanupOldEventsCommand extends Command
{
    protected $signature = 'webhookhub:cleanup {--days=90}';
    protected $description = 'Delete webhook events older than X days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        if (!$this->confirm("Delete events older than {$cutoffDate->toDateString()}?")) {
            return 0;
        }

        $count = WebhookEvent::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$count} events");

        return 0;
    }
}
```

### 9.3 Scheduler

```php
<?php

// bootstrap/app.php (Laravel 12)

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function ($schedule) {
        // Reintentar deliveries fallidos cada 5 minutos
        $schedule->command('webhookhub:retry-failed --limit=500')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Limpiar eventos antiguos mensualmente
        $schedule->command('webhookhub:cleanup --days=90')
            ->monthly();
    })
    ->create();
```

---

## 10. Testing

### 10.1 Tests Unitarios

#### 10.1.1 `WebhookIntegrationTest`

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Webhook\WebhookIntegration;
use App\Models\Webhook\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_uid_on_creation(): void
    {
        $integration = WebhookIntegration::factory()->create();

        $this->assertNotNull($integration->uid);
        $this->assertEquals(26, strlen($integration->uid)); // ULID length
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $integration = WebhookIntegration::factory()->create(['status' => 'active']);

        $this->assertTrue($integration->isActive());
    }

    public function test_has_reached_daily_limit_returns_false_when_below_limit(): void
    {
        $integration = WebhookIntegration::factory()->create(['daily_limit' => 10]);

        WebhookEvent::factory()->count(5)->create([
            'integration_id' => $integration->id,
            'received_at' => now(),
        ]);

        $this->assertFalse($integration->hasReachedDailyLimit());
    }

    public function test_has_reached_daily_limit_returns_true_when_at_limit(): void
    {
        $integration = WebhookIntegration::factory()->create(['daily_limit' => 5]);

        WebhookEvent::factory()->count(5)->create([
            'integration_id' => $integration->id,
            'received_at' => now(),
        ]);

        $this->assertTrue($integration->hasReachedDailyLimit());
    }
}
```

### 10.2 Tests de Integración

#### 10.2.1 `WebhookInboundTest`

```php
<?php

namespace Tests\Feature\Webhook;

use App\Models\Webhook\WebhookApiKey;
use App\Models\Webhook\WebhookIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebhookInboundTest extends TestCase
{
    use RefreshDatabase;

    protected WebhookIntegration $integration;
    protected WebhookApiKey $apiKey;
    protected string $rawSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = WebhookIntegration::factory()->create([
            'status' => 'active',
            'daily_limit' => 1000,
        ]);

        $this->rawSecret = Str::random(64);

        $this->apiKey = WebhookApiKey::create([
            'integration_id' => $this->integration->id,
            'key' => 'whk_test_key',
            'secret' => hash('sha256', $this->rawSecret),
            'name' => 'Test Key',
        ]);
    }

    public function test_it_accepts_valid_webhook_request(): void
    {
        $payload = [
            'event_key' => 'order.created',
            'event_version' => 'v1',
            'data' => ['order_id' => 123],
        ];

        $timestamp = time();
        $nonce = Str::uuid();
        $signature = $this->generateSignature('POST', '/api/v1/webhooks/inbound', $timestamp, $nonce, $payload);

        $response = $this->postJson('/api/v1/webhooks/inbound', $payload, [
            'X-Webhook-Api-Key' => 'whk_test_key',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => (string)$timestamp,
            'X-Webhook-Nonce' => $nonce,
            'Idempotency-Key' => Str::uuid(),
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'success' => true,
            'message' => 'Event accepted for processing',
        ]);

        $this->assertDatabaseHas('webhook_events', [
            'integration_id' => $this->integration->id,
            'event_key' => 'order.created',
        ]);
    }

    public function test_it_rejects_request_with_invalid_signature(): void
    {
        $payload = ['event_key' => 'order.created'];
        $timestamp = time();
        $nonce = Str::uuid();

        $response = $this->postJson('/api/v1/webhooks/inbound', $payload, [
            'X-Webhook-Api-Key' => 'whk_test_key',
            'X-Webhook-Signature' => 'sha256=invalid_signature',
            'X-Webhook-Timestamp' => (string)$timestamp,
            'X-Webhook-Nonce' => $nonce,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid signature',
        ]);
    }

    public function test_it_prevents_replay_attacks_with_duplicate_nonce(): void
    {
        $payload = ['event_key' => 'order.created'];
        $timestamp = time();
        $nonce = Str::uuid();
        $signature = $this->generateSignature('POST', '/api/v1/webhooks/inbound', $timestamp, $nonce, $payload);

        // Primera petición (válida)
        $this->postJson('/api/v1/webhooks/inbound', $payload, [
            'X-Webhook-Api-Key' => 'whk_test_key',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => (string)$timestamp,
            'X-Webhook-Nonce' => $nonce,
            'Idempotency-Key' => Str::uuid(),
        ])->assertStatus(202);

        // Segunda petición con mismo nonce (rechazada)
        $response = $this->postJson('/api/v1/webhooks/inbound', $payload, [
            'X-Webhook-Api-Key' => 'whk_test_key',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => (string)$timestamp,
            'X-Webhook-Nonce' => $nonce,
            'Idempotency-Key' => Str::uuid(),
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Nonce already used',
        ]);
    }

    protected function generateSignature(string $method, string $path, int $timestamp, string $nonce, array $payload): string
    {
        $bodyHash = hash('sha256', json_encode($payload));
        $canonical = "{$method}\n{$path}\n{$timestamp}\n{$nonce}\n{$bodyHash}";

        return 'sha256=' . hash_hmac('sha256', $canonical, $this->apiKey->secret);
    }
}
```

---

## 11. Anexos

### 11.1 Configuración de Horizon

```php
<?php

// config/horizon.php (ya existe en el proyecto)

return [
    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'waits' => [
        'redis:webhooks' => 60,
        'redis:deliveries' => 60,
    ],

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'auto',
                'processes' => 5,
                'tries' => 3,
                'timeout' => 120,
            ],
            'supervisor-deliveries' => [
                'connection' => 'redis',
                'queue' => ['deliveries'],
                'balance' => 'auto',
                'processes' => 10,
                'tries' => 1,
                'timeout' => 30,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['webhooks', 'deliveries', 'default'],
                'balance' => 'auto',
                'processes' => 3,
                'tries' => 3,
            ],
        ],
    ],
];
```

### 11.2 Ejemplo de Cliente PrestaShop (PHP)

```php
<?php

// En módulo PrestaShop: modules/alsernethook/alsernethook.php

class AlsernetHook extends Module
{
    private $apiKey = 'whk_xxxxxxxxxxxxxxxx';
    private $apiSecret = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    private $webhookUrl = 'https://webhookhub.example.com/api/v1/webhooks/inbound';

    public function hookActionObjectOrderAddAfter($params)
    {
        $order = $params['object'];

        $payload = [
            'event_key' => 'order.created',
            'event_version' => 'v1',
            'external_event_id' => 'PS_ORDER_' . $order->id,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'reference' => $order->reference,
                    'total_paid' => (float)$order->total_paid,
                    'currency' => $order->id_currency,
                ],
                'customer' => [
                    'id' => $order->id_customer,
                    'email' => $order->getCustomer()->email,
                ],
            ],
        ];

        $this->sendWebhook($payload);
    }

    protected function sendWebhook(array $payload)
    {
        $timestamp = time();
        $nonce = $this->generateUUID();
        $idempotencyKey = $this->generateUUID();

        $signature = $this->generateSignature('POST', '/api/v1/webhooks/inbound', $timestamp, $nonce, $payload);

        $ch = curl_init($this->webhookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Webhook-Api-Key: ' . $this->apiKey,
            'X-Webhook-Signature: ' . $signature,
            'X-Webhook-Timestamp: ' . $timestamp,
            'X-Webhook-Nonce: ' . $nonce,
            'Idempotency-Key: ' . $idempotencyKey,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        PrestaShopLogger::addLog("Webhook sent: HTTP {$httpCode} - {$response}");
    }

    protected function generateSignature($method, $path, $timestamp, $nonce, $payload)
    {
        $bodyHash = hash('sha256', json_encode($payload));
        $canonical = "{$method}\n{$path}\n{$timestamp}\n{$nonce}\n{$bodyHash}";

        return 'sha256=' . hash_hmac('sha256', $canonical, hash('sha256', $this->apiSecret));
    }

    protected function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
```

---

## 12. Próximos Pasos

### 12.1 Mejoras Futuras (v2)

- **Rate Limiting por integración**: Throttle por minuto/hora con Redis
- **Webhooks bidireccionales**: Callbacks de confirmación desde endpoints
- **Webhook Templates**: Payloads pre-configurados para casos comunes
- **Webhook Discovery**: Autodescubrimiento de eventos disponibles
- **GraphQL API**: Alternativa a REST para queries complejas
- **Multi-región**: Deploy en múltiples regiones para latencia baja
- **Audit Log**: Historial completo de cambios en subscriptions
- **Custom Headers**: Headers personalizados por subscription
- **Payload Encryption**: Cifrado end-to-end opcional

### 12.2 Documentación Adicional

Crear en `docs/backend/`:

- `webhookhub-api-reference.md`: Documentación OpenAPI/Swagger
- `webhookhub-prestashop-integration.md`: Guía específica para PrestaShop
- `webhookhub-n8n-integration.md`: Guía para n8n workflows
- `webhookhub-monitoring.md`: Métricas, alertas, Telescope
- `webhookhub-troubleshooting.md`: Problemas comunes y soluciones

---

## 📚 Referencias

- **Laravel 12 Docs**: https://laravel.com/docs/12.x
- **Filament v4 Docs**: https://filamentphp.com/docs/4.x/panels/installation
- **Horizon Docs**: https://laravel.com/docs/12.x/horizon
- **HMAC Signature Best Practices**: https://webhooks.fyi/security/hmac
- **Webhook Design Guide**: https://github.com/adnanh/webhook
- **PrestaShop Hooks**: https://devdocs.prestashop-project.org/8/modules/concepts/hooks/

---

**Documento generado por:** Claude Sonnet 4.5
**Basado en:** Análisis de estructura del proyecto Manager (Alsernet)
**Patrones existentes aplicados:** HasUid, Jobs con tries/timeout, API tokens, HMAC signatures
**Próxima revisión:** Después de implementar Fase 1
