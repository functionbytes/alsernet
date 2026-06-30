# Auditoría — HelpdeskPrestashop
> Fecha: 2026-06-29 · Health score: 81/100 · Estado: solid-minor-issues

**Resumen:** Puente PrestaShop bien diseñado (webhooks firmados con HMAC, circuit breaker, caché stale-while-revalidate, resolución de precios por lotes) con algunas brechas de cableado: una URL de admin a localhost hardcodeada, un desajuste de id-producto PS-vs-Ecommerce al añadir al carrito, combinaciones de producto que se descartan y un endpoint de lectura sin autorización. El módulo está sólido en su núcleo de resiliencia y seguridad; los hallazgos son de severidad media o baja, concentrados en la integración UI↔servicio y en consistencia de configuración/conexiones. No se detectaron hallazgos critical ni high.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HPS-01 | medium | wiring | resources/views/modals/product-recommend.blade.php:429 | [CONFIRMADO] | S | URL admin PrestaShop hardcodeada a localhost:8091 en Blade |
| HPS-02 | medium | wiring | app/Services/AssistedCartService.php:55-59 | [CONFIRMADO] | M | El carrito asistido usa id de producto PS contra catálogo Ecommerce |
| HPS-03 | medium | wiring | app/Http/Controllers/Managers/AssistedCartController.php:43-63 | [CONFIRMADO] | M | La combinación seleccionada (id_product_attribute) se descarta al añadir al carrito |
| HPS-04 | medium | security | app/Http/Controllers/Managers/ProductSearchController.php:188-194 | [CONFIRMADO] | S | Endpoint categories() sin verificación de autorización |
| HPS-05 | medium | performance | resources/views/inbox-slots/right-panel-prestashop-tabs.blade.php:11-18 | [CONFIRMADO] | M | Fetch HTTP síncrono a PrestaShop ejecutado dentro del render del Blade |
| HPS-06 | low | quality | app/Http/Controllers/Managers/PsRecommendationController.php:27-46 | [CONFIRMADO] | S | store() usa validación inline y authorize de solo-lectura para una escritura |
| HPS-07 | low | wiring | app/Services/PrestashopProductQueryService.php:21 | [CONFIRMADO] | S | Clave de config ps_prefix referenciada pero nunca definida |
| HPS-08 | low | conventions | app/Models/PsRecommendation.php:7-9 | [CONFIRMADO] | S | Modelo/migración PsRecommendation no usan la conexión 'helpdesk' |
| HPS-09 | low | performance | app/Services/PrestashopProductQueryService.php:31-34 | [CONFIRMADO] | S | Comodines LIKE en búsqueda de productos sin escapar |
| HPS-10 | low | quality | app/Services/PrestashopContextService.php:407-409 | [CONFIRMADO] | S | Bloque if vacío (código muerto) en recordFailure del circuit breaker |

## Hallazgos detallados

### Medium

#### HPS-01 — URL admin PrestaShop hardcodeada a localhost:8091 en Blade [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/resources/views/modals/product-recommend.blade.php:429`
- **Evidencia:** `var psAdminUrl = 'http://localhost:8091/adminalsernet1/index.php?controller=AdminProducts&id_product=' + p.id + '&updateproduct';`
- **Impacto:** El enlace "PS Admin" está hardcodeado a un host/path de desarrollo y a un nombre de carpeta admin; dará 404 o se romperá en producción y filtra la ruta del controlador admin. Debe derivarse de la URL de tienda configurada (`api_url`) o de un valor de config.
- **Recomendación:** Exponer la URL base de tienda/admin vía config (o reutilizar la lógica de `PrestashopProductQueryService::getStoreUrl` en servidor) e inyectarla en la vista en lugar de un literal localhost.

#### HPS-02 — El carrito asistido usa id de producto PS contra catálogo Ecommerce [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Services/AssistedCartService.php:55-59`
- **Evidencia:** `product-recommend.blade.php:1073` envía `product_id: _selected.id` (un id de producto PrestaShop) a `base+'/cart/items'`; `StoreAssistedCartItemRequest:17` valida `['exists:ecommerce_products,id']`; `AssistedCartService::addItem` hace `Product::query()->findOrFail($productId)` sobre `Modules\Ecommerce\Models\Product`.
- **Impacto:** A menos que los ids de producto PS estén sincronizados 1:1 con los ids de `ecommerce_products`, añadir un producto PS recomendado al carrito asistido fallará la validación (422 'producto no existe') o se enlazará silenciosamente al producto local equivocado, corrompiendo los pedidos generados.
- **Recomendación:** Mapear el id de producto PS al producto Ecommerce local (por referencia/EAN) antes de validar, o almacenar campos snapshot del producto PS directamente sin requerir una fila en `ecommerce_products`; confirmar la fuente de catálogo prevista.

#### HPS-03 — La combinación seleccionada (id_product_attribute) se descarta al añadir al carrito [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Http/Controllers/Managers/AssistedCartController.php:43-63`
- **Evidencia:** El JS envía `data.id_product_attribute` (`product-recommend.blade.php:1078`) pero `StoreAssistedCartItemRequest` no tiene regla para él, `addItem()` solo recibe `product_id+quantity`, y `AssistedCartItem`/migración no tienen columna de atributo.
- **Impacto:** Los agentes que eligen una variante/combinación específica (talla, color) en la UI obtienen el producto base; la combinación elegida se pierde, produciendo carritos y pedidos incorrectos.
- **Recomendación:** Persistir `id_product_attribute` (validarlo, añadir columna a `helpdesk_assisted_cart_items`, y pasarlo a través de `addItem` y la generación de pedido) o eliminar el selector de combinación si no se soporta.

#### HPS-04 — Endpoint categories() sin verificación de autorización [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Http/Controllers/Managers/ProductSearchController.php:188-194`
- **Evidencia:** `public function categories(Request $request): JsonResponse { $lang = $request->input('lang','es'); $categories = $this->ps->getCategories($lang); ... }` — sin llamada a `$this->authorize()` / `can()`; la ruta solo está tras `['web','auth']`.
- **Impacto:** Cualquier usuario autenticado del panel (incluso sin permisos de helpdesk/PS) puede enumerar el árbol completo de categorías de PrestaShop. Exposición menor de datos e inconsistente con el gating de permisos de cada acción hermana.
- **Recomendación:** Añadir un gate de autorización consistente con las otras acciones (p. ej. requerir `helpdeskprestashop.view` o `helpdesk.conversations.view`).

#### HPS-05 — Fetch HTTP síncrono a PrestaShop ejecutado dentro del render del Blade [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/resources/views/inbox-slots/right-panel-prestashop-tabs.blade.php:11-18`
- **Evidencia:** `@php ... app(PrestashopContextService::class)->getCustomerContext($rpCust->email) ... @endphp` se ejecuta en tiempo de render dentro del include del panel derecho del inbox.
- **Impacto:** Con caché en frío, el render de la página del inbox se bloquea en una llamada upstream a PS hasta `http_connect_timeout+http_timeout` (~12s) antes de devolver HTML. Mitigado por caché/circuit breaker/SWR, pero sigue siendo una llamada externa en la ruta de render y lógica de negocio en una vista.
- **Recomendación:** Cargar el contexto en el controlador (o lazy-load del tab vía AJAX tras el paint) en lugar de dentro del Blade; mantener la vista presentacional.

### Low

#### HPS-06 — store() usa validación inline y authorize de solo-lectura para una escritura [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Http/Controllers/Managers/PsRecommendationController.php:27-46`
- **Evidencia:** `store()` llama a `$request->validate([...])` inline (las reglas del proyecto prohíben validación inline) y protege la creación con `$this->authorize('view', $conversation)`.
- **Impacto:** Viola la convención de Form Request; autorizar una creación con la habilidad 'view' significa que cualquier usuario que pueda ver una conversación puede persistir registros de recomendación (autorización de escritura débil).
- **Recomendación:** Extraer un `StorePsRecommendationRequest` con `messages()`/`attributes()` en español y autorizar contra una habilidad update/create en lugar de 'view'.

#### HPS-07 — Clave de config ps_prefix referenciada pero nunca definida [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Services/PrestashopProductQueryService.php:21`
- **Evidencia:** `$this->prefix = (string) config('helpdeskprestashop.ps_prefix', 'aalv_');` pero `config/config.php` solo define `ps_db`, sin clave `ps_prefix` ni mapeo de env `HELPDESK_PS_PREFIX`.
- **Impacto:** El prefijo de tablas PrestaShop está efectivamente hardcodeado a 'aalv_' y no se puede configurar por entorno; una tienda con prefijo distinto fallaría silenciosamente todas las consultas directas de producto a la BD (capturadas y devueltas como vacío/null).
- **Recomendación:** Añadir `'ps_prefix' => env('HELPDESK_PS_PREFIX', 'aalv_')` a `config/config.php`.

#### HPS-08 — Modelo/migración PsRecommendation no usan la conexión 'helpdesk' [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Models/PsRecommendation.php:7-9`
- **Evidencia:** `AssistedCart`/`AssistedCartItem` fijan `protected $connection='helpdesk'` y sus migraciones usan `Schema::connection('helpdesk')`; `PsRecommendation` no tiene `$connection` y `2026_06_19_000001` crea la tabla en la conexión por defecto, aunque almacena `conversation_id` que referencia `helpdesk_conversations` (conexión helpdesk).
- **Impacto:** Inconsistente con el resto del módulo; si las tablas helpdesk viven en una BD separada, `helpdesk_ps_recommendations` cae en la BD de la app mientras las conversaciones relacionadas viven en otra (cross-DB, sin FK). Funciona hoy pero es una inconsistencia latente de integridad/ubicación.
- **Recomendación:** Decidir la conexión prevista y aplicarla consistentemente al modelo y a la migración (probablemente 'helpdesk').

#### HPS-09 — Comodines LIKE en búsqueda de productos sin escapar [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Services/PrestashopProductQueryService.php:31-34`
- **Evidencia:** `->where('pl.name','like',"%{$query}%")->orWhere('p.reference','like',"%{$query}%")` — el valor está bindeado (sin inyección SQL) pero `%` y `_` en la entrada del usuario no se escapan.
- **Impacto:** Un usuario que busque `%` o `_` empareja ampliamente, devolviendo resultados no deseados y forzando escaneos completos costosos. Severidad baja (parametrizado, BD PS de solo lectura).
- **Recomendación:** Escapar los metacaracteres LIKE en `$query` (p. ej. `addcslashes($query, '%_\\')`) antes de interpolar.

#### HPS-10 — Bloque if vacío (código muerto) en recordFailure del circuit breaker [CONFIRMADO]
- **Archivo:** `modules/HelpdeskPrestashop/app/Services/PrestashopContextService.php:407-409`
- **Evidencia:** `if (Cache::add(self::CIRCUIT_KEY, 0, $openSeconds)) { // first failure within the window, TTL initialised }` seguido de `Cache::increment`.
- **Impacto:** Bloque no-op confuso; la intención (inicializar TTL vía `add`, luego incrementar) es correcta pero la rama vacía es código muerto.
- **Recomendación:** Eliminar el if vacío y simplemente llamar `Cache::add(...)` y luego `Cache::increment(...)`, o añadir un comentario aclaratorio sin el cuerpo vacío.

## Plan de ataque priorizado

1. **HPS-01** (S) — Sacar la URL admin localhost:8091 del Blade a config/derivación de `store_url`. Rompe en producción; impacto inmediato.
2. **HPS-02** (M) — Resolver el desajuste id-producto PS vs `ecommerce_products` al añadir al carrito asistido. Corrupción de pedidos.
3. **HPS-03** (M) — Persistir `id_product_attribute` (combinación) en el flujo de carrito/pedido. Carritos incorrectos por variante.
4. **HPS-04** (S) — Añadir autorización a `categories()`. Exposición de datos.
5. **HPS-05** (M) — Mover el fetch de contexto PS fuera del render del Blade (controlador o AJAX lazy).
6. **HPS-06** (S) — Form Request para `PsRecommendationController::store` + autorización de escritura.
7. **HPS-07 / HPS-08 / HPS-09 / HPS-10** (S) — Consistencia de config/conexión, escape LIKE y limpieza de código muerto.

## Quick wins

- **HPS-04** — Añadir `$this->authorize()` / chequeo de permiso a `ProductSearchController::categories()`.
- **HPS-07** — Añadir la clave `'ps_prefix'` a `config/config.php` para que el prefijo de tablas sea realmente configurable.
- **HPS-10** — Eliminar el bloque if vacío muerto en `PrestashopContextService::recordFailure()`.
- **HPS-01** — Reemplazar la URL localhost hardcodeada por un valor de config (cambio de una línea + inyección a la vista).
- **HPS-09** — Escapar metacaracteres LIKE con `addcslashes()`.

## Fortalezas

- Resiliencia upstream robusta: peticiones firmadas con HMAC con tolerancia de timestamp (`HmacSigner`), circuit breaker, timeouts de connect/read, y revalidación en background stale-while-revalidate vía job único en cola (`RefreshPsContextJob`).
- El receptor de webhooks tiene middleware de verificación de firma + deduplicación de idempotencia en dos fases (processing/done) con liberación de lock en error (`PsEventReceiverController`).
- N+1 evitado deliberadamente: resolución por lotes de specific_price/descuento (`resolveEffectivePriceMap`/`resolveSpecificPriceMap`), eager loading con `withCount` en el index del carrito, cachés estáticas por request para idioma y tax.
- Higiene fuerte de claves de caché: la clave de caché del detalle de pedido incluye un hash del email para prevenir fuga de datos entre clientes (`PsOrderDetailController`, documentado).
- Form Requests con `messages()`/`attributes()` en español y `authorize()` Spatie real para la mayoría de endpoints; permisos siguen la convención lowercase `{alias}.{action}`; migraciones con índices, FKs y `down()` adecuados.
- Eventos consumidos aguas abajo (Remarketing/Engagement/HelpdeskChatFlow), no huérfanos; cobertura de tests decente (~877 LOC en 4 tests Feature).

## Cobertura de la auditoría

Se leyeron todos los controllers, services, models, jobs, form requests, middleware, ServiceProvider, routes, migraciones, seeder, comando, y se muestrearon los 6 Blades (lectura completa de los archivos grandes de service/controller; greps dirigidos sobre el modal product-recommend de 1306 líneas). Clases de evento revisadas por encima (una lectura representativa) — son DTOs delgados consumidos por otros módulos. No se ejecutaron tests (BD bloqueada por instrucciones); los archivos de test se inspeccionaron solo por existencia/cobertura, no por corrección.

## Descartados en verificación

Ninguno. No hubo hallazgos critical/high que verificar adversarialmente, y todos los hallazgos del cuerpo quedaron confirmados; ninguno fue refutado durante la verificación.
