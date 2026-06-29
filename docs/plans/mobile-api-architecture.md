# Plan de arquitectura: Mobile API para Ecommerce (Flutter / B2C)

> Análisis y plan de implementación para convertir el panel Laravel en backend microservicio de una app móvil Flutter para clientes finales, empezando por el módulo Ecommerce. La arquitectura es reutilizable para futuros módulos por audiencia (cliente, técnico, repartidor, etc.).

## Resumen ejecutivo

**Hallazgos clave (basados en código real):**

1. **Los módulos existen sin typo**: `modules/Ecommerce/` y `modules/EcommercePayment/` (con integración Wompi, BankTransfer y COD via `PaymentGatewayContract`). Hay un `GatewayRegistry` que auto-descubre módulos `type: payment-gateway` desde `module.json` — patrón ya generalizado.
2. **Cliente final ya está separado del admin**: existe `Modules\Ecommerce\Models\Customer` (tabla `ecommerce_customers`) que ya extiende `Authenticatable` y usa `HasApiTokens` (Sanctum). En `config/auth.php` hay guard `ecommerce` con provider `ecommerce_customers` y broker de password reset propio. **Esta es la pieza fundacional del API móvil.**
3. **Ya existe API v1 incipiente** en `modules/Ecommerce/routes/api.php` con prefix `v1/ecommerce`, pero tiene problemas críticos: `OrderApiController::store` recibe `customer_id` por request (IDOR), `CartApiController` mezcla session+sanctum (invalido en móvil sin cookies), Resources no siguen el formato `{success, message, data}` que exige `.claude/rules/api-controllers.md` (devuelven el Resource crudo), no usan ISO8601 ni camelCase, no hay paginación uniforme.
4. **`AuthApiController` del módulo Auth está hardcoded a `App\Models\User` (admin)**, usa Spatie roles y 2FA — no sirve para clientes B2C móviles. Hay que crear un `CustomerAuthApiController` paralelo.
5. **Stack listo**: Sanctum, Reverb, Horizon, Scout, l5-swagger, FCM via `Modules\Notification\Services\PushNotificationService`, idempotency middleware, multi-language vía trait propio `HasTranslations` (morph table `ecommerce_translations`), Activity Log.
6. **No es multi-tienda real**: existe `store_id` nullable en algunas tablas legacy de 2024 pero los modelos modernos (Brand, Product, Order recientes) lo ignoran. **Tratar como single-store.**
7. **Carrito persistido en backend** (`ecommerce_carts`) con `customer_id` o `session_id`. Para móvil → siempre `customer_id` (Sanctum) y eliminar la rama `session_id`.
8. **Conflicto de routing**: `routes/api.php` raíz define `prefix v1` con `name api.v1.*`, mientras `Ecommerce/RouteServiceProvider` añade `prefix api` y el archivo abre con `prefix v1/ecommerce` con `name api.ecommerce.*`. Coexisten, pero **rompe la convención** "una sola URL space `/api/v1/{alias}/*` con name `api.v1.{alias}.*`" que conviene unificar.

**Decisiones arquitectónicas tomadas:**

- Guard separado `ecommerce` para tokens Sanctum de clientes; middleware `auth:sanctum,ecommerce` (solo el guard `ecommerce`).
- Capa común `app/Http/Api/V1/` con `BaseApiController`, trait `ApiResponses`, `BaseApiRequest`, `BaseResource`, `JsonExceptionRenderer` registrado en `bootstrap/app.php`.
- Manifest de módulos móviles vía interfaz `MobileModuleManifest` que cada ServiceProvider implementa y registra en un container singleton `MobileModuleRegistry`.
- Endpoints existentes se **migran**, no se duplican: refactor a `Http/Controllers/Api/V1/Customer/*`.
- Pagos: nuevo endpoint `POST /api/v1/ecommerce/orders/{order}/payments` que delega a `PaymentGatewayManager`, devolviendo `payment_url` (Wompi widget hosted o URL de redirect) más un `app_return_url` que la app interceptará vía deep link. Webhook de Wompi (server-to-server) se mantiene web público — sin cambios.
- Documentación: **Scribe (`knuckleswtf/scribe`)** sustituye a l5-swagger en uso, porque l5-swagger requiere annotations en cada controller (alto coste); Scribe lee Form Requests, Resources y route names automáticamente y genera Postman + OpenAPI 3 + página HTML.
- Push: extender `push_notification_tokens` a polimorfica (`tokenable_type`, `tokenable_id`) o crear tabla `customer_push_tokens` separada (preferido — aislamiento limpio entre admin y B2C).

**Fases propuestas (resumen):**

- **F0 (1-2 días)**: capa común + `CustomerAuthApiController` + manifest. **Próximo paso recomendado.**
- **F1 (3-5 días)**: catálogo público (refactor de lo existente, mejorar Resources, caching, ETag, Scout).
- **F2 (2-3 días)**: cliente autenticado (perfil, addresses, wishlist, devices push).
- **F3 (5-7 días)**: carrito + checkout + pedidos + integración pagos.
- **F4 (3-4 días)**: Reverb + FCM + eventos de pedido.
- **F5 (2-3 días)**: Scribe, hardening, observabilidad.
- **F6 (1-2 días)**: skill `new-mobile-module` reutilizable.

---

## 1. Inventario del módulo Ecommerce

### 1.1. Tablas relevantes (extraídas de migraciones reales)

| Dominio | Tabla | Notas |
|---|---|---|
| Catálogo | `ecommerce_products`, `ecommerce_brands`, `ecommerce_product_categories`, `ecommerce_product_category` (pivot) | Slug en Brand desde 2026-04-24, label_id, SEO fields, subscription fields, digital products |
| Variantes/atributos | `ecommerce_product_options`, `ecommerce_product_option_values`, `ecommerce_product_specification_tables`, `ecommerce_product_attribute_*` | Product variations completas |
| Tags | `ecommerce_product_tags` (con slug) | |
| Cliente | `ecommerce_customers` | password, avatar, phone, status, email_verified_at, email_verification_token, provider/provider_id (social login), wishlist_share_token |
| Direcciones cliente | `ecommerce_customer_addresses` | con location_ids (Locations module) |
| Carrito | `ecommerce_carts` | customer_id nullable + session_id (eliminar para móvil) |
| Wishlist | `wishlists`, `ecommerce_customer_recently_viewed_products` | |
| Pedidos | `ecommerce_orders`, `ecommerce_order_items`, `ecommerce_order_addresses`, `ecommerce_order_histories` | con `token`, `transaction_id`, delivery fields |
| Envíos | `ecommerce_shipments` con tracking, `ecommerce_shipping`, `ecommerce_shipping_rules` | |
| Descuentos/cupones | `ecommerce_discounts` | |
| Impuestos | `ecommerce_tax_rules`, `ecommerce_taxes` | |
| Reseñas | `ecommerce_reviews` (con `verified_buyer`, `reply`), `ecommerce_review_replies`, `ecommerce_product_questions` | |
| Multi-language | `ecommerce_translations` (morph: translatable_type/id + locale + field + value) | trait `HasTranslations` |
| Newsletter | `ecommerce_newsletter_subscribers`, `ecommerce_email_campaigns` | |
| Restock alerts | `ecommerce_product_restock_alerts` | |
| Búsqueda | `ecommerce_search_logs`, `ecommerce_saved_searches` | |
| Páginas legales | `ecommerce_legal_pages` | |
| Webhooks | `ecommerce_webhook_logs` (salientes) | |
| Bundles/Gift Cards | `ecommerce_bundles`, `ecommerce_bundle_products`, `ecommerce_gift_cards` | |
| Suscripciones | `ecommerce_subscriptions` (recurring) | |
| Affiliate | `ecommerce_affiliates`, `ecommerce_affiliate_referrals` | |
| Analytics | `ecommerce_page_views`, `ProductView` | |
| Pagos (módulo aparte) | `ecommerce_payments`, `ecommerce_payment_logs` | con `deleted_at` |

### 1.2. Modelos y traits relevantes

- `Customer` (`Authenticatable` + `HasApiTokens` + `LogsActivity`) — listo para Sanctum.
- `Product` (`Searchable` Scout + `HasTranslations` propio + `LogsActivity`).
- `Order`, `OrderItem`, `OrderAddress`, `OrderHistory`.
- `Cart`, `Wishlist`.
- `CustomerAddress`.
- `Review`, `ReviewReply`.

### 1.3. Servicios de negocio reutilizables (no reescribir)

- `CartService`, `CheckoutService` (orquesta cart→shipping→tax→discount→order, llama a `cartService->clearCart()` al final).
- `OrderService`, `OrderStockService` (restock al cancelar).
- `ShippingService`, `HandleShippingFeeService`.
- `TaxService`, `TaxCalculatorService`.
- `DiscountService`, `HandleApplyCouponService`, `HandleRemoveCouponService`, `FlashSaleService`.
- `ProductPriceService`, `ProductPriceHandlerService`, `ProductSalePriceService`, `ProductDiscountPriceService`, `ProductFlashSalePriceService`, `ProductCrossSalePriceService`.
- `ProductRecommendationService`, `GetProductWithCrossSalesBySlugService`.
- `ReviewService`, `WishlistService`.
- `CreatePaymentForOrderService` (probable orquestador de inicio de pago).
- `WebhookService`, `OrderEmailService`.

### 1.4. Eventos clave del dominio

`OrderPlaced`, `OrderCreated`, `OrderConfirmed`, `OrderPaymentConfirmed`, `OrderCancelled`, `OrderCompleted`, `OrderReturned`, `OrderStatusUpdated`, `ShippingStatusChanged`, `CustomerRegistered`, `CustomerEmailVerified`, `AccountDeleted/Deleting`, `ProductFileUpdated`, `ProductQuantityUpdated`, `ProductViewed`.

### 1.5. Controladores API existentes (a refactorizar/migrar)

`AddressApiController`, `CartApiController`, `CompareApiController`, `CountryApiController`, `CouponApiController`, `CustomerApiController`, `DigitalDownloadController`, `EmailTrackingController`, `OrderApiController`, `ProductApiController`, `ProductFilterApiController`, `ReviewApiController`, `TaxApiController`, `WishlistApiController`.

### 1.6. Resources existentes

35 Resources ya creados (Product, Cart, Order, Wishlist, Address, etc.) — necesitan migración a `BaseResource` con camelCase + ISO8601 + URLs absolutas.

### 1.7. Permisos / Policies

22 Policies en `Modules\Ecommerce\Policies\*` — pero todas pensadas para ADMIN (BrandPolicy, ProductPolicy, etc.). Para B2C necesitamos solo:
- `OrderPolicy::view` (cliente solo ve sus pedidos).
- `ReviewPolicy::create` (cliente verified buyer).
- `CustomerAddressPolicy` (no existe — crearlo).

### 1.8. Módulo EcommercePayment

- **Contrato**: `PaymentGatewayContract` con `getChannel()`, `getName()`, `isEnabled()`, `makePayment(Order, $customerData): Response`, `handleCallback(Request): array`, `handleWebhook(Request): array`, `refund(Payment, $amount, $reason): array`, `getDescription()`, `getFee(float $subtotal): float`.
- **Gateways implementados**: `WompiGateway` (CHANNEL `wompi`, redirect-based widget), `BankTransferGateway`, `CodGateway`.
- **Manager**: `PaymentGatewayManager` (registro/get/has/all).
- **Discovery**: `GatewayRegistry::discover()` lee `module.json` con `type: payment-gateway` + `gateway_channel` + `gateway_class`.
- **Web routes**: `checkout/payment`, `payment/wompi/callback`, `payment/wompi/webhook`, `payment/bank-transfer/instructions/{order}`. La pasarela actual está pensada para flujo web con redirect.
- **API existente**: solo `GET payment/status` (consulta estado). **Falta initiate desde móvil.**

---

## 2. Decisiones arquitectónicas

### 2.1. Estructura de namespaces

```
modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/
    AuthController.php          (login, register, logout, me, forgot-password, reset-password)
    CatalogController.php       (productos, categorías, brands)
    SearchController.php
    CartController.php
    AddressController.php
    WishlistController.php
    OrderController.php
    CheckoutController.php      (create order)
    PaymentController.php       (initiate payment, status)
    ReviewController.php
    DeviceController.php        (push tokens)
    ProfileController.php

modules/Ecommerce/app/Http/Resources/Api/V1/
    ProductResource.php, ProductDetailResource.php, CategoryResource.php, ...

modules/Ecommerce/app/Http/Requests/Api/V1/
    Auth/RegisterRequest.php, Auth/LoginRequest.php, ...
    Cart/AddItemRequest.php, ...
    Checkout/CreateOrderRequest.php, ...

app/Http/Api/V1/                                  ← capa común reutilizable
    BaseApiController.php
    Concerns/ApiResponses.php
    BaseApiRequest.php
    BaseResource.php
    Filters/QueryFilter.php
    Manifest/MobileModuleManifest.php (interface)
    Manifest/MobileModuleRegistry.php (singleton)
```

**Justificación**: convención `Api/V1/Customer/` deja espacio para futuros `Api/V1/Technician/`, `Api/V1/Driver/` (otras audiencias mencionadas por el usuario) sin colisionar. Keep `app/Http/Api/V1/` (no `app/Api/V1/`) para alinearse con PSR-4 ya autoloaded de `App\Http\*`.

### 2.2. Versionado

Una sola URL space: `/api/v1/{alias}/*`, name `api.v1.{alias}.*`. Coexiste con la API existente cambiando los archivos `routes/api.php` de cada módulo para que registren bajo el grupo del archivo raíz `routes/api.php` (que ya define `prefix v1` + `name api.v1.*`). Se incluye desde el RouteServiceProvider del módulo el archivo `routes/api-mobile.php` nuevo, dejando `routes/api.php` actual como **legacy** para el panel admin.

**Política de breaking changes**: cualquier cambio incompatible → `/api/v2/`. La capa común soporta múltiples versiones convivientes vía namespace `Api\V1`, `Api\V2`. Header `Accept: application/vnd.inoqualab.v1+json` opcional para clientes que no quieran cambiar URL.

### 2.3. Formato de respuesta unificado

```json
// Éxito con data (object)
{ "success": true, "message": "ok", "data": { ... } }

// Éxito con lista paginada
{ "success": true, "message": "ok", "data": [...], "meta": { "currentPage": 1, "perPage": 20, "total": 145, "lastPage": 8 }, "links": { "first": "...", "last": "...", "prev": null, "next": "..." } }

// Error de validación
{ "success": false, "message": "Datos inválidos.", "errors": { "email": ["..."], "password": ["..."] }, "code": "VALIDATION_ERROR" }

// Error de autenticación
{ "success": false, "message": "No autenticado.", "code": "UNAUTHENTICATED" }
```

**Trait `ApiResponses`** en `app/Http/Api/V1/Concerns/ApiResponses.php` con métodos: `ok($data, $message)`, `created($data, $message)`, `noContent($message)`, `paginated(LengthAwarePaginator, $resource)`, `error($message, $code, $status, $errors = null)`.

### 2.4. Manejo de errores

Reemplazar el handler genérico actual de `bootstrap/app.php` (que solo formatea 500s) con uno que detecte requests al prefix `api/v1/*` y devuelva siempre JSON:
- `ValidationException` → 422 con `errors` en formato de arriba.
- `AuthenticationException` → 401 `{ success:false, code:"UNAUTHENTICATED" }`.
- `AuthorizationException` → 403 `{ success:false, code:"FORBIDDEN" }`.
- `ModelNotFoundException` / `NotFoundHttpException` → 404 `{ success:false, code:"NOT_FOUND" }`.
- `ThrottleRequestsException` → 429 con header `Retry-After`.
- `Throwable` → 500 (en producción no exponer trace; mantener Sentry).

### 2.5. Auth Sanctum para Flutter

**Tokens, no SPA cookies** (es app móvil, sin dominio). Endpoints (todos en `modules/Ecommerce/routes/api-mobile.php`):

- `POST /api/v1/ecommerce/auth/register` — crea Customer, genera token, dispara `CustomerRegistered` (envía verification email vía listener existente).
- `POST /api/v1/ecommerce/auth/login` — con `device_name` requerido (Sanctum usa este nombre para el token), genera token via `$customer->createToken($deviceName)`.
- `POST /api/v1/ecommerce/auth/logout` (auth) — `currentAccessToken()->delete()`.
- `POST /api/v1/ecommerce/auth/logout-all` (auth) — `tokens()->delete()`.
- `POST /api/v1/ecommerce/auth/forgot-password` — usa el broker `ecommerce_customers` ya configurado.
- `POST /api/v1/ecommerce/auth/reset-password` — idem, con token.
- `POST /api/v1/ecommerce/auth/verify-email/{token}` (público) — confirma email.
- `POST /api/v1/ecommerce/auth/resend-verification` (auth) — reenvía.
- `POST /api/v1/ecommerce/auth/social/{provider}` — login con Google/Apple (Socialite ya instalado, modelo Customer ya tiene `provider`/`provider_id`).
- `GET /api/v1/me` — perfil + abilities + módulos.

**Guard**: `auth:sanctum` por defecto resuelve por modelo del token. Para garantizar que el token es de un Customer (no admin), middleware **`EnsureFrontendIsCustomer`**:

```php
if (! $request->user() instanceof \Modules\Ecommerce\Models\Customer) abort(403, 'Token no autorizado para esta API.');
```

Aplicar en el grupo `auth:sanctum` de `api-mobile.php`.

**Refresh tokens**: NO necesario con Sanctum. Política recomendada:
- `expiration` Sanctum global = `null` (sin expiración) o configurable por env `SANCTUM_MOBILE_EXPIRATION=43200` (30 días).
- Re-login al expirar → tokens nuevos.
- Si querés rotación: endpoint `POST /api/v1/ecommerce/auth/refresh` que valida token actual, lo borra y emite uno nuevo (mantiene `last_login_*`).

### 2.6. Identidad del cliente

Modelo: **`Modules\Ecommerce\Models\Customer`** (no `App\Models\User`). Tabla separada, password reset broker propio, guard propio. Esto aísla el escalado/seguridad B2C del admin.

### 2.7. Permisos en API (`/api/v1/me`)

Spatie Permission está activo pero solo para `User` admin. Los Customers NO tienen Spatie roles. Para móvil definimos **abilities planas**:

```json
{
  "user": { "id": 12, "name": "...", "email": "...", "emailVerifiedAt": "...", "avatarUrl": "..." },
  "abilities": ["orders.create", "orders.cancel", "reviews.create", "addresses.manage", "wishlist.manage"],
  "modules": [
    { "alias": "ecommerce", "name": "Tienda", "version": "v1", "endpoints": { "catalog": "/api/v1/ecommerce/products", "cart": "/api/v1/ecommerce/cart" } }
  ],
  "settings": { "currency": "COP", "locale": "es", "country": "CO" }
}
```

Las abilities derivan de:
- Email verificado → `orders.create`.
- Customer status `active` → `reviews.create`.
- Cliente con orden completada → `reviews.replyOwn`.

Implementadas en `Customer::abilities(): array` (método del modelo).

### 2.8. Endpoint de capacidades/módulos

**Manifest pattern** — cada `ServiceProvider` que expone un módulo a móvil hace `register()`:

```php
$this->app->resolving(MobileModuleRegistry::class, function (MobileModuleRegistry $registry) {
    $registry->register(new EcommerceMobileManifest());
});
```

`EcommerceMobileManifest implements MobileModuleManifest`:
- `alias(): string` → "ecommerce"
- `name(): string` → "Tienda"
- `version(): string` → "v1"
- `audiences(): array` → ["customer"] (futuro: ["technician", "driver"])
- `endpoints(): array` → mapa de URLs principales
- `requiresAbilities(): array`
- `featureFlags(): array` (Pennant ya instalado)

`/api/v1/me/modules` filtra por `audiences` que apliquen al token actual y por `Module::has('Ecommerce')->isEnabled()`.

### 2.9. Filtros, búsqueda, ordenamiento, paginación

**No instalar `spatie/laravel-query-builder`** — añade dependencia y la convención de la app ya tiene patrones. Crear `app/Http/Api/V1/Filters/QueryFilter.php` minimal que parsea:

- `?filter[name]=xx` → `where('name', 'like', '%xx%')` (whitelist por filter).
- `?sort=-createdAt,price` → `orderBy createdAt desc, price asc` (whitelist).
- `?per_page=20` (default 15, max 100).
- `?include=brand,categories` → `with()` (whitelist).

Cada controller declara `protected array $allowedFilters/Sorts/Includes`.

**Búsqueda full-text**: ya hay Scout + `Searchable` en `Product`. Endpoint `GET /api/v1/ecommerce/products?q=...` usa `Product::search($q)`. Driver: revisar `.env` (probable `database` driver — recomendar `meilisearch` para producción).

### 2.10. Resources (BaseResource)

`app/Http/Api/V1/BaseResource.php` con helpers:
- `iso(string $field)`: `$this->{$field}?->toIso8601String()`.
- `media(string $field, ?string $conversion = null)`: devuelve URL absoluta robusta a CDN.
- `whenIncluded(string $relation, callable $cb)` wrapper sobre `whenLoaded`.
- Auto-camelCase de keys en `toArray()` opcional.

Migrar los 35 Resources existentes uno por uno (la mayoría devuelven snake_case y dates raw).

### 2.11. Imágenes

- `Product::images` es JSON array de paths. `featured_image` es path único.
- `Modules\Ecommerce\Supports\ProductImageHelper::url($path, $conversion, $extension)` ya existe — usarlo en Resources.
- Conversiones (thumb, medium, large) generadas por `ProcessProductImageJob` (cola) — ya existente.
- Avatar customer: `Customer::avatar` (path simple). Endpoint `POST /api/v1/me/avatar` con upload y `intervention/image` (revisar si está instalado vía `spatie/laravel-medialibrary` autoload del módulo Media).

### 2.12. Real-time con Reverb

Reverb ya instalado. Canales privados:
- `private-customer.{customerId}` — para notificar `OrderStatusUpdated`, `ShippingStatusChanged`, `OrderPaymentConfirmed`.
- Authorization en `routes/channels.php`:
  ```php
  Broadcast::channel('customer.{id}', fn ($user, $id) => $user instanceof Customer && (int) $user->id === (int) $id);
  ```
- En `bootstrap/app.php` el `withBroadcasting` actual usa middleware `['web','auth']` — añadir o reemplazar con `['auth:sanctum']` para que Flutter (que envía Bearer token) pueda firmar canales.
- Eventos a marcar `ShouldBroadcastNow` o queue: `OrderStatusUpdated`, `OrderPaymentConfirmed`, `ShippingStatusChanged` (estos tres ya existen, solo añadir interface + `broadcastOn`).

Flutter usa `pusher_channels_flutter` apuntando al servidor Reverb con auth endpoint `/broadcasting/auth` enviando `Authorization: Bearer <token>`.

### 2.13. Push notifications (FCM/APNs)

- Reusar `Modules\Notification\Services\PushNotificationService` (ya envía a FCM v1).
- Crear tabla **`ecommerce_customer_push_tokens`** (NO extender `push_notification_tokens` que está atado a `users` admin):
  ```
  id, customer_id, token (unique), platform (ios|android), device_id, app_version, locale, last_used_at, is_active, timestamps
  ```
- Endpoints:
  - `POST /api/v1/me/devices` — registra token (idempotente con `device_id`).
  - `DELETE /api/v1/me/devices/{deviceId}` — desregistra (logout).
- Notification channel `fcm_customer`: extender `Notification` con `via()` que retorne `['database','fcm_customer']` cuando notifiable es Customer. Implementar driver custom o adaptar el existente.
- Notifications a portar a FCM: `OrderShippedNotification`, `OrderConfirmedNotification`, `OrderRecoveryNotification` (todas ya existen como mail-only — añadir canal).

### 2.14. Pagos desde móvil

**Problema**: `WompiGateway::makePayment()` devuelve un widget HTML embed (no es API-friendly). El flujo móvil correcto:

- Endpoint nuevo: `POST /api/v1/ecommerce/orders/{order}/payments`
  - Body: `{ "channel": "wompi", "returnUrl": "inoqualabapp://orders/{id}" }` (deep link).
  - Validations: order pertenece al customer, status `pending`, payment_status `pending`.
  - Idempotencia: header `Idempotency-Key` (middleware ya disponible).
  - Lógica: detecta channel.
    - **Wompi**: usa `WompiService` para crear sesión hosted checkout (Wompi tiene endpoint REST para iniciar transacciones); devuelve `{ paymentUrl, transactionId, expiresAt }`.
    - **COD**: marca `payment_status=pending_cod`, `payment_method=cod`, devuelve `{ status: "confirmed", paymentUrl: null }`.
    - **Bank transfer**: marca pending, devuelve instrucciones de transferencia (`{ bankAccount, beneficiary, reference, paymentUrl: null }`).
  - Response: `{ success, message, data: { paymentId, paymentUrl, status, channel, instructions } }`.

- Flutter abre `paymentUrl` en `webview_flutter` o navegador externo. Usuario completa pago. Wompi redirect actual (`payment.wompi.callback`) hace `route('checkout.confirmation', $order)` — para móvil debemos detectar User-Agent móvil o pasar query `?source=mobile&return_url=inoqualabapp://orders/{id}` y al final hacer:
  - Si `source=mobile`: render una página HTML mínima con `window.location = $returnUrl` (deep link) y meta refresh fallback.
- **Webhook server-to-server de Wompi** sigue idéntico (público, firmado con HMAC). Cuando webhook confirma, dispara `OrderPaymentConfirmed` que ahora también va por broadcast → Flutter recibe push/socket → app refresca pedido.

- Endpoint complementario: `GET /api/v1/ecommerce/orders/{order}/payments/{payment}` — estado del pago (polling fallback).
- `GET /api/v1/ecommerce/payment-methods` — lista los gateways enabled (`PaymentGatewayManager::all()` filtrado por `isEnabled()`), con `getName()`, `getDescription()`, `getFee($subtotal)`, `getChannel()`. Esto deja ya conectado el flujo "qué métodos hay disponibles" sin reescribir reglas de negocio.

### 2.15. Rate limiting

Configurado en `RouteServiceProvider` raíz de la app (revisar) o en archivo de boot. Limits propuestos (named `RateLimiter::for(...)`):

- `auth-login` — 5 attempts / 1 min por email+IP.
- `auth-register` — 3 / 1 min por IP.
- `auth-forgot` — 3 / 1 hour por email.
- `api-mobile` — 120 / 1 min por user (auth) o IP (guest) — para catalog.
- `api-mobile-write` — 30 / 1 min por user — para POST/PUT/DELETE.
- `wompi-webhook` — ya existe.

### 2.16. Idempotencia

Middleware `IdempotencyKey` ya existe en `app/Http/Middleware/`. Aplicar a:
- `POST /api/v1/ecommerce/orders` (create from cart).
- `POST /api/v1/ecommerce/orders/{order}/payments`.
- `POST /api/v1/me/devices`.
Header: `Idempotency-Key: <uuid>`.

### 2.17. Caching y ETag

- Catálogo público (productos lista, categorías, brands): cache con tag `ecommerce:catalog` y TTL 10min, invalidado por observers existentes (`ProductObserver`, `BrandObserver`, `ProductCategoryObserver`).
- ETag/304: middleware `cache.headers` ya existe — aplicar a endpoints públicos GET de catálogo.
- HTTP cache headers: `Cache-Control: public, max-age=60, stale-while-revalidate=300`.

### 2.18. Observabilidad

- Telescope habilitado solo en local/staging.
- Pulse activado.
- Activity Log: `Customer`, `Order`, `Product` ya logean. Añadir log de "API mobile login" en `CustomerLoggedIn` event.
- Sentry ya configurado (sentry/sentry-laravel).
- Crear log channel `api-mobile` para separar logs (configurar en `config/logging.php`).

### 2.19. Documentación: Scribe

**Recomendación**: instalar `knuckleswtf/scribe ^4.x`. Justificación:
- Scribe lee FormRequests + Resources + route names + comentarios PHPDoc → genera doc sin anotaciones invasivas.
- Soporta groups (un grupo por módulo móvil).
- Genera Postman + OpenAPI 3.0 + Static HTML (publicable a `/docs/api`).
- l5-swagger requiere annotations OpenAPI en cada controller → mucho boilerplate y desalinea con la convención del proyecto (que evita anotaciones).

Config: `config/scribe.php` con `routes` filtrando `api/v1/*`. Path `public/docs`. Comando `php artisan scribe:generate`. CI ejecuta esto en pipeline.

---

## 3. Capa común reutilizable (definiciones exactas)

| Archivo | Tipo | Propósito |
|---|---|---|
| `app/Http/Api/V1/Concerns/ApiResponses.php` | Trait | `ok/created/noContent/paginated/error` returning `JsonResponse` con formato unificado |
| `app/Http/Api/V1/BaseApiController.php` | Class abstract | extends `Controller`, `use ApiResponses` |
| `app/Http/Api/V1/BaseApiRequest.php` | Class abstract | extends `FormRequest`, override `failedValidation` para 422 unificado, `failedAuthorization` para 403 |
| `app/Http/Api/V1/BaseResource.php` | Class abstract | extends `JsonResource` con helpers iso/media/camelCase |
| `app/Http/Api/V1/Filters/QueryFilter.php` | Class | parser de `filter[]/sort/per_page/include` |
| `app/Http/Middleware/EnsureFrontendIsCustomer.php` | Middleware | aborta si `$request->user()` no es Customer |
| `app/Http/Api/V1/Manifest/MobileModuleManifest.php` | Interface | contrato del manifest |
| `app/Http/Api/V1/Manifest/MobileModuleRegistry.php` | Singleton | bind en `app('mobile.modules')` |
| `app/Http/Api/V1/Controllers/MeController.php` | Controller | `me()` y `modules()` endpoints comunes |
| `app/Exceptions/Api/JsonExceptionRenderer.php` | Service | invocable usado desde `bootstrap/app.php` `withExceptions()` |
| `routes/api.php` (root) | Routes | añadir grupo `prefix v1 middleware api` que incluye `require module routes/api-mobile.php` de cada módulo enabled |
| `config/scribe.php` | Config | groups por módulo, routes filtrados a `api/v1/*` |

---

## 4. Diseño de endpoints v1 para Ecommerce

Convención: prefix `/api/v1/ecommerce` (excepto `me` que es global). Auth implícita en grupos.

### 4.1. Auth

| Método | Path | Auth | Form Request | Resource | Descripción |
|---|---|---|---|---|---|
| POST | `/auth/register` | público (throttle:auth-register) | `RegisterCustomerRequest` | `CustomerResource` + token | Registra Customer; envía verification email |
| POST | `/auth/login` | público (throttle:auth-login) | `LoginCustomerRequest` | idem | Login con email+password+device_name |
| POST | `/auth/logout` | sanctum | — | message | Revoca token actual |
| POST | `/auth/logout-all` | sanctum | — | message | Revoca todos los tokens |
| POST | `/auth/forgot-password` | público (throttle:auth-forgot) | `ForgotPasswordRequest` | message | Envía mail con token |
| POST | `/auth/reset-password` | público | `ResetPasswordRequest` | message | Reset con token |
| POST | `/auth/verify-email/{token}` | público | — | message | Marca email_verified_at |
| POST | `/auth/resend-verification` | sanctum | — | message | Reenvía verification mail |
| POST | `/auth/social/{provider}` | público | `SocialLoginRequest` | `CustomerResource` + token | Google/Apple via Socialite |

### 4.2. Cliente autenticado

| Método | Path | Auth | Resource | Descripción |
|---|---|---|---|---|
| GET | `/api/v1/me` | sanctum | `MeResource` | user + abilities + modules + settings |
| PUT | `/api/v1/me` | sanctum | `CustomerResource` | actualiza nombre/phone |
| POST | `/api/v1/me/avatar` | sanctum + idempotency | url | upload avatar |
| DELETE | `/api/v1/me` | sanctum | message | request account deletion (usa `CustomerDeleteAccountJob`) |
| GET | `/api/v1/me/modules` | sanctum | array of modules | manifest filtrado por audiencia customer |
| GET | `/api/v1/me/notifications` | sanctum | paginated | notifications database |
| POST | `/api/v1/me/notifications/{id}/read` | sanctum | message | |
| POST | `/api/v1/me/devices` | sanctum + idempotency | device | registra push token |
| DELETE | `/api/v1/me/devices/{deviceId}` | sanctum | message | |

### 4.3. Catálogo público

| Método | Path | Auth | FormRequest | Resource | Descripción |
|---|---|---|---|---|---|
| GET | `/ecommerce/products` | público (throttle:api-mobile) | `ListProductsRequest` | `ProductResource` collection paginated | filter[brand,category,price_min,price_max,inStock], sort[-price,name,createdAt], q (Scout) |
| GET | `/ecommerce/products/{slug}` | público | — | `ProductDetailResource` | con related, variations, reviews count, avg rating |
| GET | `/ecommerce/products/{slug}/related` | público | — | `ProductResource` collection | usa `ProductRecommendationService` |
| GET | `/ecommerce/products/suggestions?q=` | público (throttle 60,1) | — | array | autocomplete |
| GET | `/ecommerce/categories` | público | — | `CategoryResource` collection (tree) | |
| GET | `/ecommerce/categories/{slug}` | público | — | `CategoryDetailResource` | con productos paginated |
| GET | `/ecommerce/brands` | público | — | `BrandResource` collection | |
| GET | `/ecommerce/brands/{slug}` | público | — | `BrandDetailResource` | |
| GET | `/ecommerce/filters` | público (throttle 60,1) | — | `FilterResource` | filtros disponibles (brands, categories, price range) |
| GET | `/ecommerce/products/{product}/reviews` | público | — | `ReviewResource` paginated | |
| GET | `/ecommerce/legal-pages/{slug}` | público | — | `LegalPageResource` | terms, privacy, returns |

### 4.4. Carrito

| Método | Path | Auth | FormRequest | Resource | Descripción |
|---|---|---|---|---|---|
| GET | `/ecommerce/cart` | sanctum (customer) | — | `CartResource` (con totales calc) | |
| POST | `/ecommerce/cart/items` | sanctum | `AddCartItemRequest` | `CartResource` | product_id, qty, options (variant) |
| PUT | `/ecommerce/cart/items/{cartItem}` | sanctum | `UpdateCartItemRequest` | `CartResource` | qty |
| DELETE | `/ecommerce/cart/items/{cartItem}` | sanctum | — | message | |
| POST | `/ecommerce/cart/coupons` | sanctum (throttle 30,1) | `ApplyCouponRequest` | `CartResource` | usa `HandleApplyCouponService` |
| DELETE | `/ecommerce/cart/coupons` | sanctum | — | `CartResource` | |
| POST | `/ecommerce/cart/calculate-shipping` | sanctum | `CalculateShippingRequest` | shipping options | |

### 4.5. Wishlist

| Método | Path | Auth | Resource |
|---|---|---|---|
| GET | `/ecommerce/wishlist` | sanctum | `WishlistResource` paginated |
| POST | `/ecommerce/wishlist/{product}` | sanctum | `WishlistResource` |
| DELETE | `/ecommerce/wishlist/{wishlistItem}` | sanctum | message |

### 4.6. Direcciones

| Método | Path | Auth | FormRequest |
|---|---|---|---|
| GET | `/ecommerce/addresses` | sanctum | — |
| POST | `/ecommerce/addresses` | sanctum | `StoreAddressRequest` |
| GET | `/ecommerce/addresses/{address}` | sanctum + policy | — |
| PUT | `/ecommerce/addresses/{address}` | sanctum + policy | `UpdateAddressRequest` |
| DELETE | `/ecommerce/addresses/{address}` | sanctum + policy | — |
| POST | `/ecommerce/addresses/{address}/default` | sanctum | — |

### 4.7. Checkout / Pedidos / Pagos

| Método | Path | Auth | FormRequest | Resource | Descripción |
|---|---|---|---|---|---|
| GET | `/ecommerce/payment-methods` | sanctum | — | array | gateways enabled, fee, descripción |
| GET | `/ecommerce/shipping-methods` | sanctum | `ListShippingMethodsRequest` | array | con cost calc |
| POST | `/ecommerce/orders` | sanctum + idempotency | `CreateOrderRequest` | `OrderDetailResource` | crea pedido vía `CheckoutService::process()` |
| GET | `/ecommerce/orders` | sanctum | — | `OrderResource` paginated | mis pedidos, filter[status] |
| GET | `/ecommerce/orders/{order}` | sanctum + policy | — | `OrderDetailResource` | con items, address, history, payment |
| POST | `/ecommerce/orders/{order}/cancel` | sanctum + policy | `CancelOrderRequest` | `OrderResource` | si status permite (`OrderStatus::canCancel()`) |
| POST | `/ecommerce/orders/{order}/payments` | sanctum + idempotency + policy | `InitiatePaymentRequest` | payment data | inicia pago, devuelve url o instrucciones |
| GET | `/ecommerce/orders/{order}/payments/{payment}` | sanctum + policy | — | `PaymentResource` | estado pago |
| POST | `/ecommerce/orders/{order}/repeat` | sanctum + policy | — | `CartResource` | añade items al carrito |
| GET | `/ecommerce/orders/{order}/invoice` | sanctum + policy | — | url PDF | |

### 4.8. Reseñas

| Método | Path | Auth | FormRequest |
|---|---|---|---|
| POST | `/ecommerce/products/{product}/reviews` | sanctum + verified buyer | `StoreReviewRequest` |
| PUT | `/ecommerce/reviews/{review}` | sanctum + policy(own, dentro de 7 días) | `UpdateReviewRequest` |
| DELETE | `/ecommerce/reviews/{review}` | sanctum + policy | — |

### 4.9. Misc

| Método | Path | Auth | Notas |
|---|---|---|---|
| GET | `/ecommerce/countries` | público | ya existe |
| POST | `/ecommerce/restock-alerts` | público o sanctum | ya hay `StoreRestockAlertRequest` |
| POST | `/ecommerce/newsletter/subscribe` | público (throttle) | ya hay `StoreNewsletterSubscriberRequest` |

---

## 5. Plan por fases

### Fase 0 — Fundaciones reutilizables (1-2 días)

**Agentes**: `api`, `backend`, `testing`.

**Archivos a crear:**
- `app/Http/Api/V1/Concerns/ApiResponses.php`
- `app/Http/Api/V1/BaseApiController.php`
- `app/Http/Api/V1/BaseApiRequest.php`
- `app/Http/Api/V1/BaseResource.php`
- `app/Http/Api/V1/Filters/QueryFilter.php`
- `app/Http/Api/V1/Manifest/MobileModuleManifest.php`
- `app/Http/Api/V1/Manifest/MobileModuleRegistry.php`
- `app/Http/Api/V1/Controllers/MeController.php`
- `app/Http/Api/V1/Resources/MeResource.php`
- `app/Http/Middleware/EnsureFrontendIsCustomer.php`
- `app/Exceptions/Api/JsonExceptionRenderer.php`
- `modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/AuthController.php`
- `modules/Ecommerce/app/Http/Requests/Api/V1/Auth/{Register,Login,ForgotPassword,ResetPassword,SocialLogin}Request.php`
- `modules/Ecommerce/app/Http/Resources/Api/V1/CustomerResource.php` (refactor)
- `modules/Ecommerce/app/Notifications/CustomerResetPasswordNotification.php` (apuntando a deep link `inoqualabapp://reset-password?token=...`)
- `modules/Ecommerce/app/EcommerceMobileManifest.php`
- `modules/Ecommerce/routes/api-mobile.php` (nuevo, separado del legacy `api.php`)

**Archivos a modificar:**
- `routes/api.php` raíz — incluir `require module_path('Ecommerce', 'routes/api-mobile.php')` dentro del grupo `v1`.
- `bootstrap/app.php` — registrar middleware alias `customer` => `EnsureFrontendIsCustomer`, registrar `JsonExceptionRenderer` en `withExceptions()`.
- `modules/Ecommerce/app/Providers/EcommerceServiceProvider.php` — registrar manifest en `MobileModuleRegistry`.
- `modules/Ecommerce/app/Providers/RouteServiceProvider.php` — cargar `api-mobile.php` con prefix vacío (el grupo raíz ya pone `v1`).
- `modules/Auth/config/sanctum.php` — añadir guard `ecommerce` al array `'guard' => ['web','ecommerce']`.
- `composer.json` raíz — `require: knuckleswtf/scribe ^4.0`.

**Migrations**: ninguna en F0.

**Tests:**
- `tests/Feature/Api/V1/AuthFlowTest.php` — register, login, me, logout, forgot/reset.
- `tests/Feature/Api/V1/MeModulesTest.php`.
- `tests/Feature/Api/V1/ApiResponseFormatTest.php` — valida shape `{success,message,data}`.
- `tests/Feature/Api/V1/ExceptionHandlerTest.php` — 401/403/404/422/500 JSON.

**Riesgos:**
- Conflicto de prefix entre `routes/api.php` raíz (con `v1`) y `Ecommerce/RouteServiceProvider` actual que añade `api`. Mitigación: deprecar el archivo `api.php` actual del módulo Ecommerce (mantenerlo como `api-legacy.php` durante 1 release) y cargar `api-mobile.php` desde el archivo raíz, no desde el `RouteServiceProvider` del módulo.
- `currentAccessToken()` falla si Sanctum no reconoce el modelo en `personal_access_tokens.tokenable_type` — verificar que `Customer` está en `Sanctum::usePersonalAccessTokenModel(...)` no es necesario porque la columna es polimórfica, pero asegurarse de que `ecommerce_customers` tabla está accesible para Sanctum.

### Fase 1 — Catálogo público (read-only) (3-5 días)

**Agentes**: `api`, `backend`, `database`, `testing`.

**Archivos a crear/modificar:**
- `modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/CatalogController.php` (refactor de `ProductApiController`).
- `modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/CategoryController.php`.
- `modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/BrandController.php`.
- `modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/SearchController.php`.
- `modules/Ecommerce/app/Http/Resources/Api/V1/{ProductResource, ProductDetailResource, CategoryResource, BrandResource, FilterResource}.php` (migración con BaseResource).
- `modules/Ecommerce/app/Http/Requests/Api/V1/Catalog/ListProductsRequest.php`.
- `modules/Ecommerce/app/Http/Filters/ProductFilter.php` (extends QueryFilter).
- `app/Http/Middleware/AcceptLanguageMiddleware.php` — setea `app()->setLocale($request->header('Accept-Language', 'es'))`.

**Migrations:**
- `database/migrations/*_add_search_indexes_to_ecommerce_products.php` — fulltext index sobre `name`, `description`, `sku` si Scout driver es `database`.

**Tests:**
- `tests/Feature/Api/V1/Catalog/ProductIndexTest.php` — filtros, sort, paginación, ETag, Accept-Language.
- `tests/Feature/Api/V1/Catalog/ProductDetailTest.php`.
- `tests/Feature/Api/V1/Catalog/SearchTest.php`.
- `tests/Feature/Api/V1/Catalog/CachingTest.php`.

**Riesgos:**
- N+1 queries — verificar `with('brand','categories','reviews:id,product_id,rating')` y usar `withCount('reviews')`.
- Scout driver: confirmar `.env` SCOUT_DRIVER y migrar a meilisearch para producción.
- Multi-language: cargar translations eager `with('translations')` si `Accept-Language` != fallback.

### Fase 2 — Cliente autenticado (2-3 días)

**Agentes**: `api`, `backend`, `testing`.

**Archivos a crear:**
- `modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/{ProfileController,AddressController,WishlistController,DeviceController}.php`.
- `modules/Ecommerce/app/Http/Resources/Api/V1/{AddressResource,WishlistResource,DeviceResource}.php`.
- `modules/Ecommerce/app/Http/Requests/Api/V1/Profile/{UpdateProfileRequest,UploadAvatarRequest}.php`.
- `modules/Ecommerce/app/Http/Requests/Api/V1/Address/{StoreAddressRequest,UpdateAddressRequest}.php`.
- `modules/Ecommerce/app/Http/Requests/Api/V1/Device/RegisterDeviceRequest.php`.
- `modules/Ecommerce/app/Policies/CustomerAddressPolicy.php`.
- `modules/Ecommerce/app/Models/CustomerPushToken.php`.

**Migrations:**
- `database/migrations/*_create_ecommerce_customer_push_tokens_table.php`.

**Tests:**
- `tests/Feature/Api/V1/Customer/ProfileTest.php`.
- `tests/Feature/Api/V1/Customer/AddressCrudTest.php` — incluyendo IDOR (cliente no puede ver address de otro).
- `tests/Feature/Api/V1/Customer/WishlistTest.php`.
- `tests/Feature/Api/V1/Customer/DeviceTokenTest.php`.

**Riesgos:**
- IDOR — mandatory policy en cada acción + tests específicos.
- Avatar upload: validar mime/size, mover a queue async para procesar conversiones.

### Fase 3 — Carrito + checkout + pedidos + pagos (5-7 días)

**Agentes**: `api`, `backend`, `database`, `testing`.

**Archivos a crear:**
- `modules/Ecommerce/app/Http/Controllers/Api/V1/Customer/{CartController,CheckoutController,OrderController,PaymentController}.php`.
- `modules/Ecommerce/app/Http/Resources/Api/V1/{CartResource,CartItemResource,OrderResource,OrderDetailResource,PaymentMethodResource,PaymentResource}.php`.
- `modules/Ecommerce/app/Http/Requests/Api/V1/Cart/{AddCartItemRequest,UpdateCartItemRequest,ApplyCouponRequest,CalculateShippingRequest}.php`.
- `modules/Ecommerce/app/Http/Requests/Api/V1/Checkout/CreateOrderRequest.php`.
- `modules/Ecommerce/app/Http/Requests/Api/V1/Order/{CancelOrderRequest,InitiatePaymentRequest}.php`.
- `modules/EcommercePayment/app/Http/Controllers/Api/V1/Customer/PaymentController.php` (initiate / status).
- `modules/EcommercePayment/app/Services/MobilePaymentInitiator.php` — orquestador específico móvil que devuelve URL/instrucciones en vez de view HTML.
- `modules/EcommercePayment/app/Http/Controllers/WompiController.php` — modificar `callback` para detectar `?source=mobile&return_url=...` y renderizar página intermedia con redirect a deep link.

**Migrations:** ninguna; tablas existen.

**Tests:**
- `tests/Feature/Api/V1/Cart/CartFlowTest.php`.
- `tests/Feature/Api/V1/Cart/CouponTest.php`.
- `tests/Feature/Api/V1/Checkout/CreateOrderTest.php` — incluye idempotencia.
- `tests/Feature/Api/V1/Order/MyOrdersTest.php` — IDOR.
- `tests/Feature/Api/V1/Order/CancelOrderTest.php`.
- `tests/Feature/Api/V1/Payment/InitiatePaymentTest.php` — wompi (mocked), cod, bank_transfer.
- `tests/Feature/Api/V1/Payment/WebhookFlowTest.php`.

**Riesgos:**
- **Wompi widget vs hosted checkout**: si Wompi solo soporta widget JS, hay que migrar a su REST de checkout sessions o usar webview. Verificar primero con su doc actual via Context7 antes de implementar.
- **Stock concurrente**: usar transacción + lock pesimista en `OrderStockService` (ya existe — verificar que usa `lockForUpdate`).
- **Idempotency-Key**: garantizar TTL ≥ 24h para reintentos por mala señal móvil.
- **Cancelación de pedido**: solo si `OrderStatus::PENDING` o `PROCESSING` antes de envío — dispara `OrderCancelled` event que ya tiene listeners que restauran stock/invoice/shipping.

### Fase 4 — Real-time + push (3-4 días)

**Agentes**: `backend`, `devops`, `testing`.

**Archivos a crear/modificar:**
- `routes/channels.php` — añadir `Broadcast::channel('customer.{id}', ...)`.
- `modules/Ecommerce/app/Events/OrderStatusUpdated.php` — añadir `implements ShouldBroadcast` + `broadcastOn`.
- `modules/Ecommerce/app/Events/{OrderPaymentConfirmed,ShippingStatusChanged}.php` — idem.
- `modules/Ecommerce/app/Notifications/{OrderShippedNotification,OrderConfirmedNotification,OrderRecoveryNotification}.php` — añadir canal `fcm_customer` en `via()` y método `toFcm()`.
- `modules/Notification/app/Channels/FcmCustomerChannel.php` — driver custom que lee tokens de `ecommerce_customer_push_tokens`.
- `bootstrap/app.php` — `withBroadcasting(channels: ..., ['middleware' => ['auth:sanctum']])`.

**Migrations:** ninguna.

**Tests:**
- `tests/Feature/Api/V1/Realtime/BroadcastingAuthTest.php`.
- `tests/Feature/Api/V1/Realtime/OrderEventsTest.php` — assertBroadcasted.
- `tests/Feature/Api/V1/Notifications/FcmDeliveryTest.php` — mocking PushNotificationService.

**Riesgos:**
- Reverb auth con Bearer token — confirmar que `EnsureFrontendRequestsAreStateful` no rompe la firma de canal (probable que sí, así que el grupo de broadcasting debe estar fuera del CSRF).
- FCM credentials path — garantizar `storage/app/firebase-credentials.json` existe en producción y rotación.
- Circuit breaker FCM ya existe — buenas noticias.

### Fase 5 — Documentación + hardening (2-3 días)

**Agentes**: `docs`, `security`, `performance`.

**Archivos:**
- `config/scribe.php`.
- `app/Console/Commands/Docs/GenerateMobileApiDocsCommand.php` — wrapper que ejecuta `scribe:generate` solo para grupo móvil.
- `tests/Feature/Api/V1/SecurityHardeningTest.php` — rate limit, IDOR, CSRF off API, HTTPS only.

**Tareas:**
- Configurar Scribe groups por módulo via `apply.routes` con name pattern `api.v1.*`.
- Auditoría de seguridad: tokens hashed (Sanctum default ✓), no logs de tokens, password Argon2 (default Laravel), rate limits aplicados, CORS solo a dominios necesarios.
- Performance: ejecutar `php artisan route:list | grep api/v1` y benchmark con k6 o ab. Revisar índices DB faltantes (slug, status combos).
- Activity Log: filtrar logs de tokens que no incluyan `plainTextToken`.

**Riesgos:**
- Scribe a veces falla con bindings no estándar (ej: `{product:slug}`). Mitigar con `@urlParam` PHPDoc.

### Fase 6 — Generalización (1-2 días)

**Agentes**: `backend`, `docs`.

**Entregables:**
- `.claude/skills/new-mobile-module/skill.md` — instrucciones para que el siguiente módulo (técnico, repartidor) implemente `MobileModuleManifest`, registre en provider, cree `routes/api-mobile.php`, controllers en `Api/V1/{Audience}/`, etc.
- `stubs/api/v1/{Controller,Resource,Request}.stub` — stubs reusables.
- Comando `php artisan make:mobile-module-skeleton {ModuleName} {Audience}`.

---

## 6. Riesgos y consideraciones

1. **Coexistencia Customer vs User admin**: ambos existen como Authenticatable con `HasApiTokens`. Si se usa `auth:sanctum` sin guard → cualquier token (admin o customer) entra. Mitigación: middleware `EnsureFrontendIsCustomer` aplicado a todo `api/v1/ecommerce/*` autenticado. Para grupos abiertos a varias audiencias se usa `EnsureFrontendIsAudience(customer|technician)`.
2. **Permisos Spatie no aplican a Customer** — Customer no usa spatie/permission. La lógica de abilities es 100% Customer-side (status, email_verified_at, has_orders).
3. **N+1 catálogo**: `Product::with('brand','categories','reviews','translations')` + `withCount('reviews')` y cache 10min. Test específico con `assertQueryCount`.
4. **Wompi flow**: el widget original es HTML. Hay que migrar a hosted checkout con REST API de Wompi y devolver URL al móvil. Confirmar con doc actual de Wompi.
5. **Webhook → móvil**: webhook server-to-server actualiza pedido + dispara evento broadcast → app refresca via socket O recibe push FCM. El callback HTTP del usuario (redirect tras pagar) renderiza HTML que hace `window.location = inoqualabapp://orders/{id}` y muestra fallback "Si no se abrió la app, toca aquí".
6. **Versionado API**: política — breaking change requiere V2 + deprecation header `Sunset: <fecha>` en V1 durante 90 días.
7. **Multi-language**: `Accept-Language` header → middleware setea locale → trait `HasTranslations` resuelve. Resources retornan campos ya traducidos. Cache key incluye locale (`products.{filterhash}.{locale}`).
8. **Single-store**: confirmado (no hay store_id activo en modelos modernos). No exponer concept "store" en API.
9. **Tamaño payload Flutter/4G**: ETag + gzip (CompressResponse middleware ya existe). Resources mínimos por defecto, datos extras vía `?include=`.
10. **Account deletion**: `CustomerDeleteAccountJob` ya existe — exponerlo en `DELETE /api/v1/me` con confirmación (token email).
11. **Migración del API legacy actual** (`/api/v1/ecommerce/*` ya existente): estrategia → mantener controllers actuales como `Legacy` (renombrar a `Api/Legacy/`), nuevos en `Api/V1/Customer/`, **mismas URLs** se sobrescriben con la nueva implementación. Tests legacy → tests nuevos. Sin ventana de breaking change porque el cliente Flutter aún no consume.

---

## Próximo paso recomendado

**Empezar por Fase 0**, específicamente:
1. Crear `app/Http/Api/V1/{ApiResponses, BaseApiController, BaseResource, BaseApiRequest}` (la base de todo lo demás).
2. Registrar `JsonExceptionRenderer` en `bootstrap/app.php` (sin esto no podemos validar el formato de respuesta).
3. Refactor de `AuthController` para Customer (login/register/logout/me) — el primer flujo end-to-end con tests verifica que la fundación funciona antes de invertir en las fases siguientes.

Esto da en 1-2 días una API "Hello World" autenticada con formato consistente, sobre la cual se construyen las fases sin retrabajo.

---

## Archivos críticos para implementación (referencia)

- `bootstrap/app.php`
- `routes/api.php`
- `config/auth.php`
- `modules/Ecommerce/routes/api.php`
- `modules/Ecommerce/app/Models/Customer.php`
- `modules/Ecommerce/app/Providers/EcommerceServiceProvider.php`
- `modules/EcommercePayment/app/Contracts/PaymentGatewayContract.php`
- `modules/EcommercePayment/app/Services/PaymentGatewayManager.php`
- `modules/Auth/app/Http/Controllers/Api/AuthApiController.php`
- `modules/Auth/config/sanctum.php`
