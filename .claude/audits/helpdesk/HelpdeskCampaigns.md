# Auditoría — HelpdeskCampaigns

> Fecha: 2026-06-29 · Health score: 80/100 · Estado: solid-minor-issues

**Resumen:** Módulo de campañas maduro y bien estructurado (tracking asíncrono en cola, webhooks con protección SSRF, índices compuestos a medida, ~69 tests) con un único bug real de integridad de datos en el esquema de impresiones y un puñado de problemas de convención y limpieza. El diagnóstico general es sólido: la ruta caliente pública está bien diseñada (job asíncrono + frequency cap en Redis), los eventos están cableados de forma segura para `event:cache`, y la cobertura de pruebas es notable. El foco de remediación es HC-01 (desajuste de tipo de columna que rompe en silencio el capping por sesión), seguido de pequeñas alineaciones con las convenciones del proyecto.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|------|--------|-------------|----------------|----------------|----------|--------|
| HC-01 | high | quality | database/migrations/2026_05_05_220114_align_campaign_impressions_schema.php:15 | [CONFIRMADO] | M | `customer_session_id` es `unsignedBigInteger` pero recibe IDs de sesión string |
| HC-02 | medium | conventions | app/Http/Controllers/Managers/CampaignsController.php:327 | [CONFIRMADO] | S | `bulkAction` usa `$request->validate()` inline en vez de Form Request |
| HC-03 | medium | performance | app/Services/VariantSelector.php:18 | [CONFIRMADO] | M | `VariantSelector` y segmentación consultan la BD en cada impresión pública |
| HC-04 | low | quality | app/Http/Requests/StoreCampaignRequest.php:7 | [CONFIRMADO] | S | Form Requests duplicados y huérfanos |
| HC-05 | low | conventions | app/Http/Controllers/Managers/CampaignsController.php:22 | [CONFIRMADO] | S | Faltan return types explícitos en varios métodos del controller |
| HC-06 | low | ux | resources/views/managers/campaigns/templates.blade.php:36 | [CONFIRMADO] | S | Atributos `style=""` inline en vistas Blade |
| HC-07 | low | security | app/Http/Controllers/Public/ImpressionTrackingController.php:47 | [CONFIRMADO] | M | `recordView` público confía en `customer_id`/`customer_session_id` falsificables |
| HC-08 | low | conventions | app/Models/Campaign.php:157 | [CONFIRMADO] | M | El modelo usa accessors `getXAttribute` legacy sin return types |

## Hallazgos detallados

### HC-01 · [CONFIRMADO] · high · quality
**`customer_session_id` es `unsignedBigInteger` pero recibe IDs de sesión string**
Archivo: `modules/HelpdeskCampaigns/database/migrations/2026_05_05_220114_align_campaign_impressions_schema.php:15`

**Evidencia:** La migración declara `$table->unsignedBigInteger('customer_session_id')->nullable()`. Sin embargo, `RecordImpressionRequest` valida `customer_session_id` como `['nullable','string','max:255']` (línea 26) y `RecordImpressionJob` inserta el string crudo en esa columna (`RecordImpressionJob.php:51`). MariaDB convierte (coerción) los strings no numéricos a `0`, por lo que toda impresión de sesión anónima se almacena con `customer_session_id = 0`. Después, `FrequencyCapService::applyVisitorFilter` hace `where('customer_session_id', <string>)` que coincide con todas las filas en `0`, sobrecontando y rompiendo el cap para visitantes por sesión.

**Verificación:** Confirmado contra el esquema en vivo. La columna `customer_session_id` es `bigint` (verificado con la tool `database-schema`). `RecordImpressionRequest.php:26` la valida como `string max:255`; `RecordImpressionJob.php:51` inserta el string crudo en la columna `bigint`. MariaDB convierte cualquier string no numérico a `0` en la escritura. `FrequencyCapService.php:108` consulta `WHERE customer_session_id = '<string>'` contra la columna `bigint`, que MariaDB también evalúa como `= 0`, devolviendo todas las filas de sesión anónima. Como `invalidate()` (`FrequencyCapService.php:71`) se llama tras cada impresión, la caché Redis se limpia constantemente y la ruta rota de BD se ejecuta en cada `shouldShow()` posterior de cualquier visitante anónimo. Los conteos del frequency cap colapsan entre todas las sesiones anónimas. La relación `CampaignImpression::session()` `BelongsTo(CustomerSession)` (`CampaignImpression.php:74`) también se rompe porque todas las FKs se guardan como `0`. El índice compuesto `hci_campaign_session_viewed` queda inútil para consultas por sesión. No existe migración correctiva.

**Impacto:** El frequency capping por sesión y la analítica por sesión están rotos en silencio; todas las impresiones de sesión anónima colapsan a `customer_session_id = 0`, contaminando los conteos y el índice compuesto `hci_campaign_session_viewed`.

**Recomendación:** Cambiar la columna a string (p. ej. `->string('customer_session_id',255)->nullable()`) en una migración correctiva con el set completo de atributos, alineada con cómo la usa el código; verificar que el índice de sesión siga aplicando. Alternativamente, si se pretende una relación FK real, cambiar la validación/inserción para usar un ID entero de `CustomerSession`.

---

### HC-02 · [CONFIRMADO] · medium · conventions
**`bulkAction` usa `$request->validate()` inline en vez de un Form Request**
Archivo: `modules/HelpdeskCampaigns/app/Http/Controllers/Managers/CampaignsController.php:327`

**Evidencia:** `public function bulkAction(Request $request): JsonResponse { ... $validated = $request->validate([...]); }` — las reglas del proyecto requieren Form Request para toda validación; el resto de acciones de este controller ya usa uno.

**Impacto:** Inconsistente con las convenciones del proyecto; la validación/mensajes no quedan centralizados ni traducidos al español como en los requests hermanos.

**Recomendación:** Extraer a `Managers/BulkActionCampaignRequest` con `authorize()` (`helpdesk.campaigns.manage`), `rules()`, `messages()` y `attributes()` en español.

---

### HC-03 · [CONFIRMADO] · medium · performance
**`VariantSelector` y la segmentación consultan la BD en cada impresión pública**
Archivo: `modules/HelpdeskCampaigns/app/Services/VariantSelector.php:18`

**Evidencia:** `pick()` ejecuta `$campaign->variants()->orderBy('id')->get()` de forma síncrona dentro de `ImpressionTrackingController::recordView` (la ruta caliente pública), y `TargetingService::matchesSegments()` ejecuta una consulta `whereHas` EXISTS cuando hay segmentos configurados. A diferencia de `FrequencyCapService`, ninguno está cacheado, así que cada vista pública golpea la BD antes incluso de encolar el job.

**Impacto:** Añade round-trips a la BD al endpoint público de tracking sensible a latencia que el diseño del job asíncrono pretendía evitar; escala linealmente con el tráfico.

**Recomendación:** Cachear las variantes de la campaña (y la búsqueda de campaña activa) en Redis con TTL corto, keyeado por `campaign_id`, invalidado al actualizar variante/campaña; mantener la lógica de selección en PHP.

---

### HC-04 · [CONFIRMADO] · low · quality
**Form Requests duplicados y huérfanos**
Archivo: `modules/HelpdeskCampaigns/app/Http/Requests/StoreCampaignRequest.php:7`

**Evidencia:** Existen tanto `Http/Requests/StoreCampaignRequest.php` como `Http/Requests/UpdateCampaignRequest.php`, pero grep muestra que nunca se importan/usan; los controllers usan las variantes `Managers\` que son supersets (también validan campos de frequency/goal/approval).

**Impacto:** Código muerto; riesgo de que una edición futura aterrice en la clase incorrecta (inerte).

**Recomendación:** Borrar las dos clases request de nivel raíz.

---

### HC-05 · [CONFIRMADO] · low · conventions
**Faltan return types explícitos en varios métodos del controller**
Archivo: `modules/HelpdeskCampaigns/app/Http/Controllers/Managers/CampaignsController.php:22`

**Evidencia:** `index()`, `create()`, `show()`, `edit()`, `templates()` no tienen return type (debería ser `: View`); `store/update/etc.` sí los declaran. Las reglas de controllers exigen return types explícitos en todos los métodos.

**Impacto:** Inconsistencia menor; análisis estático más débil.

**Recomendación:** Añadir `: \Illuminate\Contracts\View\View` a los métodos que devuelven vista.

---

### HC-06 · [CONFIRMADO] · low · ux
**Atributos `style=""` inline en vistas Blade**
Archivo: `modules/HelpdeskCampaigns/resources/views/managers/campaigns/templates.blade.php:36`

**Evidencia:** `style="cursor: pointer; border-style: dashed !important;"` (templates l.36/40/55/58), selects `width:auto` en index.blade (l.90/98/124) y estilos de progress/chart en show.blade (l.235/295). Regla del proyecto: nunca usar `style=""` inline.

**Impacto:** Viola las convenciones de Blade; estilos no reutilizables.

**Recomendación:** Mover a clases/utilidades CSS en la hoja de estilos del módulo.

---

### HC-07 · [CONFIRMADO] · low · security
**`recordView` público confía en `customer_id`/`customer_session_id` falsificables**
Archivo: `modules/HelpdeskCampaigns/app/Http/Controllers/Public/ImpressionTrackingController.php:47`

**Evidencia:** `$visitor['customer_id']` y `customer_session_id` se toman directamente del input de una petición no autenticada y se usan para frequency capping, stickiness de variante y targeting; un atacante puede suministrar el id de otro cliente para agotar su frequency cap o sondear targeting/variante.

**Impacto:** Bajo — endpoint del widget público por diseño, pero permite abuso leve del cap/targeting por cliente y envenenamiento de analítica.

**Recomendación:** Cuando exista un cliente autenticado en servidor, preferir la identidad de sesión/auth sobre el `customer_id` suministrado por el cliente; en caso contrario, documentar la frontera de confianza y apoyarse en rate limiting.

---

### HC-08 · [CONFIRMADO] · low · conventions
**El modelo usa accessors `getXAttribute` legacy sin return types**
Archivo: `modules/HelpdeskCampaigns/app/Models/Campaign.php:157`

**Evidencia:** `getCtrAttribute()`, `getIsActiveAttribute()`, `getTypeLabelAttribute()`, `getAverageDailyImpressionsAttribute()`, etc. usan el estilo antiguo `get*Attribute` y la mayoría carecen de return type; la regla de modelos prefiere la clase `Attribute` de Laravel 11 con accessors tipados. `getImpressionsCountAttribute()` además ejecuta `->count()` de forma lazy si la columna está ausente (potencial N+1 si se lee sin la columna denormalizada cargada).

**Impacto:** Deriva estilística respecto a las convenciones; riesgo menor de N+1 en la ruta de fallback de conteo.

**Recomendación:** Migrar a accessors `Attribute::make()` con return types; seguir apoyándose en las columnas denormalizadas `impressions_count`/`clicks_count` para evitar conteos por fila.

---

## Plan de ataque priorizado

1. **HC-01 (high):** Migración correctiva para `customer_session_id` → string(255) con set completo de atributos. Corrige integridad de datos y capping por sesión. Bloquea analítica fiable por sesión.
2. **HC-02 (medium):** Extraer `bulkAction` a `Managers/BulkActionCampaignRequest`. Esfuerzo bajo, alinea con convenciones.
3. **HC-03 (medium):** Cachear variantes/campaña activa en Redis para sacar las consultas de la ruta caliente pública.
4. **HC-04 → HC-08 (low):** Limpieza y alineación de convenciones (ver Quick wins).

## Quick wins

- Borrar los `Http/Requests/StoreCampaignRequest.php` y `UpdateCampaignRequest.php` huérfanos (HC-04).
- Reemplazar el `$request->validate()` inline de `bulkAction` por un Form Request `Managers/` (HC-02).
- Añadir return types explícitos (`: View`) a `index/create/show/edit/templates` (HC-05).
- Mover atributos `style=""` inline de index/show/templates a clases CSS (HC-06).

## Fortalezas

- El endpoint público de tracking es asíncrono (`RecordImpressionJob` en cola `impressions`) con frequency capping respaldado por Redis y ventana de dedup de 30s, manteniendo la ruta caliente rápida.
- El listener de webhooks (`DispatchCampaignWebhooks`) implementa protección SSRF (`isSafeUrl` bloquea IPs privadas/reservadas y localhost) y firmas HMAC-SHA256.
- Índices compuestos diseñados para las consultas reales (frequency cap, timeline GROUP BY, analítica de variantes) en la migración `2026_05_21`.
- Eventos registrados explícitamente en `EventServiceProvider` con `shouldDiscoverEvents()=false` y listeners basados en clase (sin closures), por lo que `event:cache` es seguro.
- Fuerte huella de tests: ~69 métodos de prueba en 5 archivos usando `DatabaseTransactions` según la convención del proyecto; sanitización de color CSS en el accessor de apariencia; `selectRaw` usa solo strings estáticos (sin inyección).

## Cobertura de la auditoría

Revisados todos los controllers (managers/api/public), todos los services, el modelo, todos los jobs, el observer, todos los listeners, el cableado de eventos, la policy, todos los form requests, todas las migraciones, config, seeder, routes, y blades muestreados (index/show/templates) vía grep. NO se ejecutaron tests (BD bloqueada según instrucciones); los cuerpos JS de blades y los archivos de idioma solo se muestrearon; los modelos `CampaignTemplate`/`CampaignVariant`/`CampaignImpression` y sus factories no se leyeron en profundidad.

## Descartados en verificación

Ninguno. Los 8 hallazgos del informe inicial fueron confirmados en verificación; no hubo refutaciones.
