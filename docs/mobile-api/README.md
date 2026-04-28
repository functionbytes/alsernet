# Mobile API v1 — Documentation

API REST para la app móvil Flutter (clientes finales B2C). Construida en Laravel 12 con autenticación Sanctum, broadcasting Reverb y push notifications FCM.

## Estado de implementación

| Fase | Alcance | Estado |
|------|---------|--------|
| F0 | Capa común + Auth Customer (register/login/logout/me/forgot/reset/verify) | ✅ Completo (30 tests pasando) |
| F1 | Catálogo público (productos, categorías, marcas, filtros, reseñas) | ✅ Implementado |
| F2 | Cliente autenticado (perfil, direcciones, wishlist, push tokens) | ✅ Implementado |
| F3 | Carrito, checkout, pedidos, pagos | ✅ Implementado |
| F4 | Real-time (Reverb) + Push (FCM) | ✅ Implementado |
| F5 | Documentación + hardening | ⏳ En progreso |
| F6 | Skill `new-mobile-module` para futuros módulos | ⏳ Pendiente |

## Auth flow para Flutter

```dart
// 1. Registro
POST /api/v1/ecommerce/auth/register
Body: { name, email, password, password_confirmation, phone?, device_name?, accepts_terms: true }

// 2. Login
POST /api/v1/ecommerce/auth/login
Body: { email, password, device_name }
Response: { customer, token, tokenType: "Bearer" }

// 3. Guardar el token y enviarlo en cada request
Header: Authorization: Bearer <token>

// 4. Perfil + capabilities + módulos disponibles
GET /api/v1/me
Response: { user, abilities, modules, settings }

// 5. Logout
POST /api/v1/ecommerce/auth/logout
```

## Formato de respuesta unificado

### Éxito (objeto)
```json
{ "success": true, "message": "ok", "data": { ... } }
```

### Éxito (lista paginada)
```json
{
  "success": true,
  "message": "ok",
  "data": [...],
  "meta": { "currentPage": 1, "perPage": 20, "total": 145, "lastPage": 8 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

### Error de validación (422)
```json
{
  "success": false,
  "message": "Datos inválidos.",
  "errors": { "email": ["..."], "password": ["..."] },
  "code": "VALIDATION_ERROR"
}
```

### Otros errores
- 401: `{ "success": false, "code": "UNAUTHENTICATED" }`
- 403: `{ "success": false, "code": "FORBIDDEN" }` o `"AUDIENCE_MISMATCH"`
- 404: `{ "success": false, "code": "NOT_FOUND" }`
- 429: `{ "success": false, "code": "TOO_MANY_REQUESTS" }` con header `Retry-After`
- 500: `{ "success": false, "code": "SERVER_ERROR" }`

## Endpoints

### Autenticación
| Método | Path | Auth |
|--------|------|------|
| POST | `/api/v1/ecommerce/auth/register` | público |
| POST | `/api/v1/ecommerce/auth/login` | público |
| POST | `/api/v1/ecommerce/auth/logout` | sanctum + customer |
| POST | `/api/v1/ecommerce/auth/logout-all` | sanctum + customer |
| POST | `/api/v1/ecommerce/auth/forgot-password` | público |
| POST | `/api/v1/ecommerce/auth/reset-password` | público |
| POST | `/api/v1/ecommerce/auth/verify-email/{token}` | público |
| POST | `/api/v1/ecommerce/auth/resend-verification` | sanctum + customer |

### Cliente
| Método | Path | Auth |
|--------|------|------|
| GET | `/api/v1/me` | sanctum + customer |
| PUT | `/api/v1/me` | sanctum + customer |
| GET | `/api/v1/me/modules` | sanctum + customer |
| POST | `/api/v1/me/avatar` | sanctum + customer |
| DELETE | `/api/v1/me/avatar` | sanctum + customer |
| DELETE | `/api/v1/me` | sanctum + customer |
| POST | `/api/v1/me/devices` | sanctum + customer |
| DELETE | `/api/v1/me/devices/{device}` | sanctum + customer |

### Catálogo
| Método | Path | Auth |
|--------|------|------|
| GET | `/api/v1/ecommerce/products` | público |
| GET | `/api/v1/ecommerce/products/{slug}` | público |
| GET | `/api/v1/ecommerce/products/{slug}/related` | público |
| GET | `/api/v1/ecommerce/products/suggestions?q=` | público |
| GET | `/api/v1/ecommerce/products/{product}/reviews` | público |
| GET | `/api/v1/ecommerce/categories` | público |
| GET | `/api/v1/ecommerce/categories/{slug}` | público |
| GET | `/api/v1/ecommerce/brands` | público |
| GET | `/api/v1/ecommerce/brands/{slug}` | público |
| GET | `/api/v1/ecommerce/filters` | público |
| GET | `/api/v1/ecommerce/legal-pages/{slug}` | público |

### Direcciones / Wishlist
| Método | Path | Auth |
|--------|------|------|
| GET / POST / GET / PUT / DELETE | `/api/v1/ecommerce/addresses[/{address}]` | sanctum + customer |
| POST | `/api/v1/ecommerce/addresses/{address}/default` | sanctum + customer |
| GET | `/api/v1/ecommerce/wishlist` | sanctum + customer |
| POST | `/api/v1/ecommerce/wishlist/{product}` | sanctum + customer |
| DELETE | `/api/v1/ecommerce/wishlist/{wishlist}` | sanctum + customer |

### Carrito / Checkout / Pedidos / Pagos
| Método | Path | Auth |
|--------|------|------|
| GET | `/api/v1/ecommerce/cart` | sanctum + customer |
| POST | `/api/v1/ecommerce/cart/items` | sanctum + customer |
| PUT | `/api/v1/ecommerce/cart/items/{cart}` | sanctum + customer |
| DELETE | `/api/v1/ecommerce/cart/items/{cart}` | sanctum + customer |
| POST/DELETE | `/api/v1/ecommerce/cart/coupons` | sanctum + customer |
| GET | `/api/v1/ecommerce/payment-methods` | sanctum + customer |
| GET | `/api/v1/ecommerce/orders` | sanctum + customer |
| POST | `/api/v1/ecommerce/orders` | sanctum + customer (idempotency) |
| GET | `/api/v1/ecommerce/orders/{order}` | sanctum + customer |
| POST | `/api/v1/ecommerce/orders/{order}/cancel` | sanctum + customer |
| POST | `/api/v1/ecommerce/orders/{order}/payments` | sanctum + customer (idempotency) |
| GET | `/api/v1/ecommerce/orders/{order}/payments/{payment}` | sanctum + customer |

## Filtros, búsqueda, ordenamiento, paginación

```
GET /api/v1/ecommerce/products?filter[brand]=apple&filter[category]=phones&filter[priceMin]=100&filter[priceMax]=2000&sort=-createdAt,price&per_page=20&q=iphone&include=brand,categories
```

- `filter[x]=valor` — whitelist por endpoint
- `sort=-x,y` — `-` indica desc
- `per_page` — default 15, máx 100
- `include` — relaciones a cargar
- `q` — búsqueda Scout (cuando aplique)

## Rate limiting

| Limiter | Límite |
|---------|--------|
| `auth-login` | 5/min por email+IP |
| `auth-register` | 3/min por IP |
| `auth-forgot` | 3/h por email+IP |
| `api-mobile` | 120/min por user/IP |
| `api-mobile-write` | 30/min por user |

## Idempotencia

Los endpoints que crean recursos críticos requieren header `Idempotency-Key`:
- `POST /api/v1/ecommerce/orders`
- `POST /api/v1/ecommerce/orders/{order}/payments`
- `POST /api/v1/me/devices`
- `POST /api/v1/me/avatar`

## Internacionalización

Header opcional `Accept-Language: es | en | pt`. Default: `es`.

## Real-time (Reverb)

Canales privados para clientes:
- `private-customer.{customerId}` — recibe eventos del propio cliente

Eventos broadcast:
- `order.status.updated` — cambio de estado del pedido
- `order.payment.confirmed` — pago confirmado por la pasarela
- `shipping.status.changed` — cambio de estado del envío

Auth de canal: el cliente Flutter envía `Authorization: Bearer <token>` al endpoint `/api/v1/broadcasting/auth`.

## Push notifications (FCM)

1. La app obtiene el token FCM y lo registra:
   ```
   POST /api/v1/me/devices
   Body: { token, platform: "ios|android", device_id, app_version, locale }
   ```

2. Backend usa `Modules\Notification\Channels\FcmCustomerChannel` para enviar push a tokens activos del cliente.

3. Notifications disponibles:
   - `OrderStatusChangedNotification`
   - `OrderPaymentConfirmedNotification`

## Manifest de módulos móviles

Cada módulo expuesto a la app implementa `App\Http\Api\V1\Manifest\MobileModuleManifest` y se registra en `App\Http\Api\V1\Manifest\MobileModuleRegistry`.

Endpoint: `GET /api/v1/me/modules` retorna los módulos disponibles para el rol del cliente.

## Documentación interactiva

Para generar OpenAPI/Postman automáticamente:

```bash
composer require knuckleswtf/scribe
php artisan vendor:publish --tag=scribe-config
php artisan scribe:generate
```

Documentación HTML quedará en `public/docs`, OpenAPI en `public/docs/openapi.yaml`, Postman collection en `public/docs/collection.json`.

## Tests

```bash
# Recrear DB de testing
mysql -uroot -p... -e "DROP DATABASE IF EXISTS system_testing; CREATE DATABASE system_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run tests F0
php artisan test --compact tests/Feature/Api/V1/ modules/Ecommerce/tests/Feature/Api/V1/
```

**Nota**: el entorno de testing tiene bugs pre-existentes en migraciones de los módulos `Campaign`/`CampaignSendingServers` que pueden interferir con la suite completa. F0 (auth) tiene 30 tests pasando con un schema dump regenerado desde producción.

## Hardening checklist

- [x] Tokens Sanctum hashed (default)
- [x] Rate limiters por endpoint sensible
- [x] IDOR protection: middleware `customer` + policy ownership checks
- [x] CSRF off para `api/v1/*` (no parte del grupo `web`)
- [x] Validación obligatoria via Form Requests con `authorize()`
- [x] Activity Log en Customer y Order
- [x] Sentry para errores 500
- [x] Idempotency-Key middleware en endpoints críticos
- [ ] Scribe instalado para docs auto-generadas
- [ ] OpenAPI spec validado
- [ ] Tests E2E de pago con Wompi mocked
- [ ] Performance benchmark sobre catálogo (k6 o ab)
- [ ] Cache headers + ETag en endpoints públicos GET

## Decisiones arquitectónicas clave

1. **Customer separado de User admin**: tabla `ecommerce_customers`, guard `ecommerce`, broker propio. Aísla el escalado y la seguridad B2C del admin.
2. **Audience middleware (`customer`)**: garantiza que el token Sanctum es de un Customer (no admin) en endpoints móviles.
3. **`abilities()` plano** sin Spatie Permission: las abilities derivan de `status`, `email_verified_at`, y existencia de pedidos completados.
4. **Manifest de módulos**: cada módulo registra su mobile manifest para que la app descubra capacidades disponibles. Reutilizable para futuras audiencias (técnico, repartidor).
5. **Pagos sin reescribir**: reutilizamos `PaymentGatewayManager` y los gateways existentes (Wompi, COD, BankTransfer). El endpoint mobile devuelve URL/instrucciones; el webhook server-to-server sigue idéntico.
6. **Single-store**: confirmado del análisis, no hay multi-tenant en modelos modernos.

## Capa común reutilizable

| Path | Propósito |
|------|-----------|
| `app/Http/Api/V1/BaseApiController.php` | Base con trait `ApiResponses` |
| `app/Http/Api/V1/Concerns/ApiResponses.php` | `ok/created/noContent/paginated/errorResponse` |
| `app/Http/Api/V1/BaseApiRequest.php` | FormRequest base con failedValidation/Authorization JSON |
| `app/Http/Api/V1/BaseResource.php` | Resource con helpers `iso`, `mediaUrl`, `whenIncluded` |
| `app/Http/Api/V1/Filters/QueryFilter.php` | Filtros/sort/include con whitelist |
| `app/Http/Api/V1/Manifest/MobileModuleManifest.php` | Interfaz manifest |
| `app/Http/Api/V1/Manifest/MobileModuleRegistry.php` | Singleton registry |
| `app/Http/Api/V1/Controllers/MeController.php` | `/api/v1/me`, `/me/modules`, `/me/update` |
| `app/Http/Middleware/EnsureFrontendIsCustomer.php` | Audience guard |
| `app/Http/Middleware/AcceptLanguageMiddleware.php` | i18n |
| `app/Exceptions/Api/JsonExceptionRenderer.php` | Handler unificado JSON |
