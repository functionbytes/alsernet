# Auditoría core Helpdesk — Customer 360 & CRM

> Fecha: 2026-06-29 · Health score: 67/100 · Estado: solid-minor-issues

**Resumen:** Subsistema CRM bien estructurado (policies, form requests, normalización de teléfono, tests razonables) pero con un bypass de autorización cross-inbox en el endpoint de insights, un N+1 por cliente en el reporte de clientes en riesgo, y varios defectos medios de correctitud y cableado. El diagnóstico general es sólido: la arquitectura sigue las convenciones del proyecto y degrada con elegancia cuando módulos opcionales (Engagement/Tickets) están deshabilitados; los problemas son puntuales y mayoritariamente de bajo a medio esfuerzo. La prioridad clara es cerrar el IDOR de insights (CUST-01), que rompe la garantía de aislamiento por inbox que se respeta en todos los endpoints hermanos.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| CUST-01 | high | security | `modules/Helpdesk/app/Http/Controllers/Managers/CustomerInsightsController.php:15-25` | [CONFIRMADO] | S | Endpoint de insights omite el aislamiento por inbox (IDOR / fuga PII) |
| CUST-02 | medium | performance | `modules/Helpdesk/app/Http/Controllers/Managers/AtRiskCustomersReportController.php:74-104` | [AJUSTADO→medium] | M | Reporte at-risk calcula healthScore() por cliente dentro de un loop (N+1) |
| CUST-03 | medium | quality | `modules/Helpdesk/app/Services/CustomerInsightsService.php:188-200` | [CONFIRMADO] | S | journeyTimeline mezcla formatos de fecha y corrompe el orden cronológico |
| CUST-04 | medium | quality | `modules/Helpdesk/app/Http/Controllers/Managers/CustomerEcommerceController.php:31-79` | [CONFIRMADO] | S | 'total_spent' suma solo los últimos 10 pedidos pero se presenta como total histórico |
| CUST-05 | medium | wiring | `modules/Helpdesk/app/Http/Controllers/Managers/CustomerEcommerceController.php:8-10,27-55` | [CONFIRMADO] | M | Dependencia dura de modelos Remarketing sin guard de módulo |
| CUST-06 | medium | conventions | `modules/Helpdesk/app/Http/Requests/Api/StoreCustomerApiRequest.php:20` | [CONFIRMADO] | S | Regla unique del API store omite el prefijo de conexión helpdesk |
| CUST-07 | medium | quality | `modules/Helpdesk/app/Http/Controllers/Managers/CustomersController.php:250-279` | [CONFIRMADO] | S | ban()/unban() escriben un campo no fillable 'is_banned' y nunca capturan ban_reason |
| CUST-08 | medium | performance | `modules/Helpdesk/app/Models/Customer.php:202-209` | [CONFIRMADO] | M | scopeSearch usa LIKE con wildcard inicial (sin índice, wildcards sin escapar) |
| CUST-11 | medium | tests | `modules/Helpdesk/tests` | [CONFIRMADO] | M | Servicios de insights/360/commerce/stats y sus controllers sin tests |
| CUST-09 | low | quality | `modules/Helpdesk/routes/api.php:16-25` | [CONFIRMADO] | M | Dos controllers API de customer paralelos exponen endpoints solapados |
| CUST-10 | low | security | `modules/Helpdesk/app/Http/Controllers/Api/CustomersController.php:15-44` | [CONFIRMADO] | M | Endpoints API de customer no aplican aislamiento por inbox |
| CUST-12 | low | performance | `modules/Helpdesk/app/Services/CustomerStatsService.php:15-36` | [CONFIRMADO] | S | El caché de stats de clientes nunca se invalida en escrituras |
| CUST-13 | low | performance | `modules/Helpdesk/app/Http/Controllers/Managers/CustomersController.php:397-412` | [CONFIRMADO] | S | Endpoint AJAX conversations() dispara N+1 acotado en último mensaje |
| CUST-14 | low | performance | `modules/Helpdesk/app/Services/Customer360Service.php:126-158` | [CONFIRMADO] | M | Customer360 hace llamadas externas síncronas secuenciales en el request web |

## Hallazgos detallados

### CUST-01 · [CONFIRMADO] · high · security
**Endpoint de insights omite el aislamiento por inbox (IDOR / fuga PII)**
`modules/Helpdesk/app/Http/Controllers/Managers/CustomerInsightsController.php:15-25`

**Evidencia:** El constructor solo aplica `middleware('can:helpdesk.customers.view')`; `show()` nunca llama a `$this->authorize('view', $customer)`. Los endpoints hermanos (`CustomerProfileController:23`, `CustomerEcommerceController:16`, `CustomerIntegrationsController:42`) sí llaman a `authorize('view',$customer)`, que aplica `CustomerPolicy::sharesInboxWith()` (requiere al menos una conversación en inbox compartido).

**Impacto:** Cualquier agente con el permiso amplio `helpdesk.customers.view` puede pedir `/customers/{id}/insights` para CUALQUIER id de cliente y leer health score, métricas de ciclo de vida, CSAT y un timeline completo (asuntos de conversaciones, nombres de canal y tags aplicados) — incluso de clientes cuyos inboxes no tiene asignados. Es un IDOR real / fuga de PII que rompe la garantía de aislamiento por inbox respetada en todo el resto del subsistema.

**Recomendación:** Añadir `$this->authorize('view', $customer);` como primera sentencia de `show()` (el route-model binding ya provee `$customer`). Mantener el middleware del constructor como gate grueso.

**Veredicto:** Verificado en `CustomerInsightsController.php:15–25`. `CustomerPolicy::view()` (líneas 17–21) aplica `sharesInboxWith()`. Los tres controllers hermanos lo enforcen. La brecha permite IDOR sobre `health_score`, `lifetimeMetrics` y `journeyTimeline`. Confirmado como high.

---

### CUST-02 · [AJUSTADO→medium] · performance · (severidad original: high)
**Reporte at-risk calcula healthScore() por cliente dentro de un loop (N+1)**
`modules/Helpdesk/app/Http/Controllers/Managers/AtRiskCustomersReportController.php:74-104`

**Evidencia:** `$rows->map(... 'healthScore' => $this->insights->healthScore($customer) ...)`. `CustomerInsightsService::healthScore()` dispara exactamente 4 queries por cliente: media CSAT (línea 25–28), conteo de conversaciones cerradas (34–37), última conversación (41–44) y existencia de tag de sentimiento negativo vía `DB::connection` (50–57). El `.take(50)` se aplica DESPUÉS del map (línea 101), por lo que cada cliente distinto en `$rows` dispara las 4 queries — no solo 50.

**Impacto:** El endpoint de datos ejecuta cientos de queries por carga; degrada conforme crece la población at-risk. La colección de customers sí está batch-cargada correctamente (líneas 69–72), por lo que ese punto está bien.

**Recomendación:** Calcular las 4 entradas del health-score en batch (queries agrupadas por `customer_id`, como ya hace `HealthScoreBatchService` del módulo Analytics) o reutilizar ese servicio aquí en lugar de la llamada por cliente.

**Veredicto:** N+1 real y confirmado. Severidad ajustada de high a medium: el endpoint está detrás de `helpdesk.reports.view` (línea 29), permiso de alcance manager, por lo que la exposición se limita a managers autorizados, no a todos los agentes. Sigue siendo un problema arquitectónico que degradará bajo carga; la recomendación se mantiene.

---

### CUST-03 · [CONFIRMADO] · medium · quality
**journeyTimeline mezcla formatos de fecha y corrompe el orden cronológico**
`modules/Helpdesk/app/Services/CustomerInsightsService.php:188-200`

**Evidencia:** Los eventos de conversación y CSAT usan `->toIso8601String()` (`2026-...T..:..:..+00:00`) pero los eventos de tag empujan strings crudos de BD (`$tag->created_at`, `2026-... ..:..:..`); el `usort` final usa `strcmp($b['occurred_at'],$a['occurred_at'])`.

**Impacto:** `strcmp` entre ambos formatos ordena incorrectamente los eventos de tag respecto a conversación/CSAT, produciendo un orden de timeline erróneo y valores `occurred_at` inconsistentes devueltos a la UI.

**Recomendación:** Envolver el `created_at` del tag en Carbon y llamar `toIso8601String()` como en las otras ramas antes de empujarlo a `$events`.

---

### CUST-04 · [CONFIRMADO] · medium · quality
**'total_spent' suma solo los últimos 10 pedidos pero se presenta como total histórico**
`modules/Helpdesk/app/Http/Controllers/Managers/CustomerEcommerceController.php:31-79`

**Evidencia:** La query de pedidos usa `->limit(10)`, y luego `$stats = ['orders_count'=>$orders->count(), 'total_spent'=>$orders->sum('total'), ...]` se calcula en memoria sobre esa colección limitada.

**Impacto:** `orders_count` y `total_spent` quedan silenciosamente topados en 10 pedidos, subestimando el gasto de clientes con más de 10 pedidos — datos CRM/VIP engañosos mostrados a los agentes.

**Recomendación:** Calcular `orders_count` y `total_spent` con queries agregadas dedicadas sobre todos los pedidos coincidentes (ej. `Order::whereIn(...)->selectRaw('COUNT(*), SUM(total)')`), separadas de la lista de visualización limitada.

---

### CUST-05 · [CONFIRMADO] · medium · wiring
**Dependencia dura de modelos Remarketing sin guard de módulo**
`modules/Helpdesk/app/Http/Controllers/Managers/CustomerEcommerceController.php:8-10,27-55`

**Evidencia:** Importa `Modules\Remarketing\Models\{Cart,Customer,Order}` y los usa directamente; la ruta `customers/{customer}/ecommerce` no tiene guard `Module::isEnabled('Remarketing')`, a diferencia de `Customer360Service` que protege Engagement/Tickets con `Module::find()`/contract.

**Impacto:** Si el módulo Remarketing está deshabilitado o desinstalado, golpear el endpoint ecommerce lanza un 500 class-not-found. Inconsistente con el patrón de degradación elegante usado en el resto del subsistema.

**Recomendación:** Proteger la ruta/método con un check de módulo habilitado (o resolver los datos de Remarketing vía contract/null-object) y devolver un payload vacío cuando Remarketing no esté disponible.

---

### CUST-06 · [CONFIRMADO] · medium · conventions
**Regla unique del API store omite el prefijo de conexión helpdesk**
`modules/Helpdesk/app/Http/Requests/Api/StoreCustomerApiRequest.php:20`

**Evidencia:** `'email' => [...,'unique:helpdesk_customers,email']` — sin prefijo de conexión, mientras el modelo está en la conexión `helpdesk` y `StoreCustomerRequest`/`UpdateCustomerApiRequest` usan correctamente `'helpdesk.helpdesk_customers'`. La conexión por defecto es `env('DB_CONNECTION')`, distinta de `helpdesk`.

**Impacto:** La validación de unicidad corre contra la tabla `helpdesk_customers` de la conexión por defecto; si esa tabla no existe el API store da 500, y si diverge el check es erróneo — pueden colarse emails duplicados por el camino API.

**Recomendación:** Cambiar a `Rule::unique('helpdesk.helpdesk_customers','email')` (o `'unique:helpdesk.helpdesk_customers,email'`) para igualar el resto del subsistema.

---

### CUST-07 · [CONFIRMADO] · medium · quality
**ban()/unban() escriben un campo no fillable 'is_banned' y nunca capturan ban_reason**
`modules/Helpdesk/app/Http/Controllers/Managers/CustomersController.php:250-279`

**Evidencia:** El controller llama `$customer->update(['is_banned'=>true,'banned_at'=>now()])`; `is_banned` es un accessor, no está en `$fillable`, por lo que se descarta silenciosamente. Los helpers dedicados del modelo `ban($reason)`/`unban()` (que además limpian/asignan `ban_reason`) nunca se usan.

**Impacto:** Escritura muerta confusa que depende solo de `banned_at`; `ban_reason` nunca se persiste desde la UI, y el helper `ban()` previsto es código muerto. Un futuro `preventSilentlyDiscardingAttributes()` convertiría esto en error duro.

**Recomendación:** Reemplazar los `update()` por `$customer->ban($request->input('reason'))` y `$customer->unban()`; eliminar la clave `is_banned`.

---

### CUST-08 · [CONFIRMADO] · medium · performance
**scopeSearch usa LIKE con wildcard inicial (sin índice, wildcards sin escapar)**
`modules/Helpdesk/app/Models/Customer.php:202-209`

**Evidencia:** `where('name','like',"%{$term}%")->orWhere('email','like',"%{$term}%")->orWhere('phone','like',"%{$term}%")`; usado por index, search (limit 10) y ambos endpoints API index.

**Impacto:** El `%` inicial fuerza un full table scan sobre tres columnas en cada búsqueda/autocomplete; en tablas `helpdesk_customers` grandes es lento. Además `%` y `_` en el término no se escapan, por lo que la entrada del usuario altera la semántica de coincidencia.

**Recomendación:** Preferir LIKE de prefijo (`"{$term}%"`) para columnas indexadas donde la UX lo permita, escapar wildcards LIKE en el término, y/o respaldar la búsqueda con un índice FULLTEXT o la infraestructura de búsqueda existente.

---

### CUST-11 · [CONFIRMADO] · medium · tests
**Servicios de insights/360/commerce/stats y sus controllers sin tests**
`modules/Helpdesk/tests`

**Evidencia:** Existen tests para `CustomersController` (17), `CustomerIntegrationsController` (30) y `PhoneNormalizerService` (unit). No hay tests que cubran `CustomerInsightsService` (healthScore/lifetimeMetrics/journeyTimeline), `Customer360Service`, `CustomerCommerceSyncService`, `CustomerStatsService`, `CustomerProfileController`, `CustomerEcommerceController`, `CustomerInsightsController` ni `AtRiskCustomersReportController`.

**Impacto:** La lógica de scoring, el bug de orden del timeline (CUST-03), el tope de total_spent (CUST-04) y la brecha de autorización (CUST-01) no tienen cobertura de regresión.

**Recomendación:** Añadir tests unit para los límites de healthScore y el orden de journeyTimeline, y tests feature que aserten autorización (aislamiento por inbox) en los endpoints insights/profile/ecommerce.

---

### CUST-09 · [CONFIRMADO] · low · quality
**Dos controllers API de customer paralelos exponen endpoints solapados**
`modules/Helpdesk/routes/api.php:16-25`

**Evidencia:** `routes/api.php` cablea tanto `Api\CustomersController` (index/store/show) como `Api\V1\CustomersApiController` (apiResource), que comparten `IndexCustomerApiRequest`/`StoreCustomerApiRequest` y `CustomerResource` con cuerpos index/store casi idénticos.

**Impacto:** Lógica duplicada y dos superficies que mantener sincronizadas (la deriva de la regla unique de CUST-06 es un síntoma directo); aumenta el mantenimiento y la superficie de auditoría.

**Recomendación:** Consolidar en el controller V1 y deprecar/redirigir el legacy `Api\CustomersController`, o que uno delegue en el otro.

---

### CUST-10 · [CONFIRMADO] · low · security
**Endpoints API de customer no aplican aislamiento por inbox**
`modules/Helpdesk/app/Http/Controllers/Api/CustomersController.php:15-44`

**Evidencia:** index/store dependen solo del `authorize()` del FormRequest (permiso global); `show()` usa `authorize('helpdesk.customers.view')` como ability simple sin llamar a `CustomerPolicy::view($customer)`, por lo que el aislamiento por inbox (`sharesInboxWith`) no se aplica como sí ocurre en los endpoints web de manager.

**Impacto:** Tokens Sanctum con `helpdesk.customers.view` pueden leer/listar PII de cualquier cliente vía API sin importar la asignación de inbox — modelo de confianza inconsistente con la UI web. Aceptable solo si los tokens API son exclusivamente service-level.

**Recomendación:** Si los consumidores del API son agentes, cambiar `show()` a `authorize('view',$customer)` y scopear `index()` a los inboxes del llamante; si no, documentar que el API es solo de service-token.

---

### CUST-12 · [CONFIRMADO] · low · performance
**El caché de stats de clientes nunca se invalida en escrituras**
`modules/Helpdesk/app/Services/CustomerStatsService.php:15-36`

**Evidencia:** `getStats()` cachea `helpdesk:customer_stats` durante 5 minutos; no hay `Cache::forget` en create/update/ban/unban/delete en `CustomersController`.

**Impacto:** Los conteos del tab index (all/verified/banned/active) y totales pueden quedar obsoletos hasta 5 minutos tras una mutación, confundiendo a agentes que acaban de banear/crear un contacto.

**Recomendación:** `Cache::forget('helpdesk:customer_stats')` tras create/update/ban/unban/destroy (o usar un model observer).

---

### CUST-13 · [CONFIRMADO] · low · performance
**Endpoint AJAX conversations() dispara N+1 acotado en último mensaje**
`modules/Helpdesk/app/Http/Controllers/Managers/CustomersController.php:397-412`

**Evidencia:** La query hace eager-load solo de `['status']`; el map luego llama `$c->getLatestMessage()`, que (cuando `lastMessage` no está `relationLoaded`) corre una query por conversación, más acceso a `channel_info` por fila.

**Impacto:** Hasta ~10 queries extra por llamada; acotado pero evitable, y el patrón puede copiarse a listados no acotados.

**Recomendación:** Añadir `->with('lastMessage')` (y la relación de inbox para `channel_info`) a la query para que `getLatestMessage()` use la relación precargada.

---

### CUST-14 · [CONFIRMADO] · low · performance
**Customer360 hace llamadas externas síncronas secuenciales en el request web**
`modules/Helpdesk/app/Services/Customer360Service.php:126-158`

**Evidencia:** `platformsData` itera sobre 4 acciones (profile/orders/balance/loyaltyPoints) llamando `$orchestrator->fetchAcrossInbox()` por acción a través de todas las integraciones del inbox, síncronamente dentro del request HTTP (`force` bypassa el caché).

**Impacto:** La latencia se acumula con cada integración/acción habilitada; un backend lento de PrestaShop/ERP bloquea el hilo del request (solo mitigado parcialmente por el caché del orchestrator cuando `force=false`).

**Recomendación:** Agrupar las acciones por integración en una llamada donde el orchestrator lo soporte, imponer timeouts por llamada, y considerar lazy-load de las secciones de plataforma vía endpoints async separados en lugar de en la respuesta agregada.

---

## Plan de ataque priorizado

1. **CUST-01 (high, S) — Cerrar el IDOR de insights.** Añadir `$this->authorize('view', $customer)` en `show()`. Restaura el aislamiento por inbox y elimina la fuga de PII. Máxima prioridad.
2. **CUST-02 (medium, M) — Eliminar el N+1 del reporte at-risk.** Vectorizar las 4 entradas de healthScore reusando `HealthScoreBatchService` de Analytics.
3. **CUST-07 (medium, S) — Arreglar ban/unban.** Usar los helpers `ban($reason)`/`unban()` del modelo; persistir `ban_reason`.
4. **CUST-04 (medium, S) — Total spent histórico real** vía queries agregadas separadas de la lista limitada.
5. **CUST-05 (medium, M) — Guard de módulo Remarketing** en el endpoint ecommerce.
6. **CUST-08 (medium, M) — Búsqueda indexable** (prefijo LIKE / escape de wildcards / FULLTEXT).
7. **CUST-11 (medium, M) — Cobertura de tests** para servicios y controllers sin probar (regresión de CUST-01/03/04).
8. Resto de low (CUST-09/10/12/13/14) como limpieza incremental.

## Quick wins

- **CUST-06 (S):** corregir la regla unique del API store para incluir el prefijo de conexión helpdesk.
- **CUST-03 (S):** normalizar `occurred_at` del tag a `toIso8601String()` en journeyTimeline para que el orden cronológico sea correcto.
- **CUST-13 (S):** eager-load `lastMessage` en `CustomersController::conversations()` para eliminar el N+1 acotado.
- **CUST-12 (S):** invalidar el caché `helpdesk:customer_stats` en create/update/ban/delete de cliente.

## Fortalezas

- `CustomerPolicy` aplica aislamiento por inbox por cliente (`sharesInboxWith`) y está registrada vía `Gate::policy` en `HelpdeskServiceProvider`.
- Los Form Requests siguen las convenciones del proyecto: reglas en array, mensajes/atributos en español, `authorize()` real con Spatie, regla unique con prefijo de conexión (manager + API V1).
- Los tokens de portal se almacenan solo como hashes SHA-256 con expiración; `portal_token`/`portal_password` en `$hidden`; la PII no se sobreexpone.
- `PhoneNormalizerService` está bien documentado, con tests unit, y se aplica consistentemente vía mutators.
- `Customer360Service` degrada correctamente cuando los módulos Engagement/Tickets están deshabilitados (`Module::find` / acceso a tickets basado en contract).
- Los `$appends` con accessors respaldados por queries se eliminaron deliberadamente por rendimiento (documentado en el modelo).

## Cobertura de la auditoría

Leídos en su totalidad: los 5 servicios (`Customer360Service`, `CustomerCommerceSyncService`, `CustomerInsightsService`, `CustomerStatsService`, `PhoneNormalizerService`), los 7 controllers (Managers: Customers, CustomerProfile, CustomerEcommerce, CustomerInsights, CustomerIntegrations, AtRiskCustomersReport; más `Api\CustomersController` y `Api\V1\CustomersApiController`), el modelo `Customer`, `CustomerPolicy`, `StoreCustomerRequest`/`UpdateCustomerRequest`/`MergeCustomerRequest` + Index/Store/Update requests del API, `CustomerResource`, las definiciones de rutas (`managers.php` + `api.php`), el middleware de `RouteServiceProvider`, el registro de policy en `HelpdeskServiceProvider`, y el inventario de tests. Confirmados `getLatestMessage()` y la config de conexión vía grep.

No se leyeron en profundidad las vistas Blade ni los internos de `CustomerMergeAction` (fuera del alcance estricto). Los tests con BD están bloqueados, por lo que el análisis es estático.

## Descartados en verificación

Ninguno. No hubo hallazgos refutados durante la verificación; los dos hallazgos con veredicto explícito quedaron confirmados (CUST-01) y ajustado en severidad (CUST-02, high→medium), y el resto se mantienen como confirmados según la evidencia recogida.
