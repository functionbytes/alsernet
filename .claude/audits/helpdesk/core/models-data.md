# Auditoría core Helpdesk — Modelos & capa de datos
> Fecha: 2026-06-29 · Health score: 73/100 · Estado: solid-minor-issues

**Resumen:** Capa de modelos sólida y consistente (uso del método `casts()` en lugar de la propiedad `$casts`, secretos cifrados, `SoftDeletes`, scopes tipados), pero con varias desincronizaciones `fillable`↔columna y relaciones a medio cablear (`is_public` huérfano, `active`/`is_active` duplicado, `Brand`/`Company` sin reverso) y fugas potenciales de secretos por falta de `$hidden` en 2 canales. El diagnóstico es de un subsistema maduro con deuda de cableado: ningún hallazgo critical/high, pero 5 medium concentrados en features multi-brand/multi-empresa y serialización de canales que están parcialmente implementadas a nivel de datos.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| MODE-01 | Medium | wiring | `app/Models/Conversation.php:53` | [CONFIRMADO] | S | `is_public` en `Conversation::$fillable` no tiene columna en `helpdesk_conversations` |
| MODE-02 | Medium | quality | `app/Models/AiAgent.php:22,37-40` | [CONFIRMADO] | M | Columna booleana duplicada `active` vs `is_active` en `helpdesk_ai_agents`; `scopeActive` consulta la no indexada |
| MODE-03 | Medium | wiring | `app/Models/Customer.php:25-50` | [CONFIRMADO] | S | `Customer.company_id` existe en BD pero no es fillable ni tiene relación `company()` |
| MODE-04 | Medium | wiring | `app/Models/Brand.php:38-46` | [CONFIRMADO] | M | Brand a medio cablear: `brand_id` no fillable en Inbox/Conversation y sin relación inversa `brand()` |
| MODE-05 | Medium | security | `app/Models/Channels/Api.php:46-63` | [CONFIRMADO] | S | Canales `Api` y `Whatsapp` exponen secretos descifrados al serializar (sin `$hidden`) |
| MODE-06 | Low | quality | `app/Models/Concerns/HasCustomAttributes.php:33,50` | [CONFIRMADO] | S | Colisión de nombres `get/setCustomAttribute` con el patrón accessor/mutator de Eloquent |
| MODE-07 | Low | quality | `app/Models/Conversation.php:50,83,251-259` | [CONFIRMADO] | M | Doble almacenamiento de tags en Conversation: columna JSON `tags` + pivot `conversationTags()` |
| MODE-08 | Low | performance | `app/Models/Conversation.php:552-592,659-693` | [CONFIRMADO] | M | `toInboxArray()` genera N+1 cuando no se precargan lastMessage/reads/customer/counts |
| MODE-09 | Low | conventions | `app/Models/ConversationTag.php:116-125` | [CONFIRMADO] | S | `ConversationTag::getBadgeHtmlAttribute()` emite HTML con estilo inline desde el modelo |
| MODE-10 | Low | quality | `app/Models/SlackIntegration.php:38,43` | [CONFIRMADO] | S | SlackIntegration accessor/mutator con type-hint `string` no-nullable sobre columna nullable |
| MODE-11 | Low | tests | `tests/Unit/Models` | [CONFIRMADO] | L | Cobertura de tests de modelos ~5% y factories ausentes para ~12 modelos con HasFactory |
| MODE-12 | Low | conventions | `app/Models/CannedReply.php:146` | [CONFIRMADO] | S | `CannedReply::canBeEditedBy()` usa permiso `manage-helpdesk` (formato legacy fuera de convención) |
| MODE-13 | Low | security | `app/Models/RoutingRule.php:41-48` | [CONFIRMADO] | S | `RoutingRule::matches()` ejecuta regex almacenada sobre texto entrante sin validación (ReDoS) |
| MODE-14 | Low | quality | `app/Models/Setting.php:13,28-33` | [CONFIRMADO] | S | `Setting.value` es text sin cast: settings no escalares no hacen round-trip |

## Hallazgos detallados

### Medium

#### MODE-01 — `is_public` en `Conversation::$fillable` no tiene columna en `helpdesk_conversations` · [CONFIRMADO]
- **Evidencia:** Conversation declara `is_public` en `$fillable` (línea 53). Ninguna migración añade `is_public` a `helpdesk_conversations`; grep solo lo encuentra en `helpdesk_conversation_views` (`2026_04_20_000030`). No está en `casts()` ni se usa en `app/`. Mass-assignar `is_public` en una Conversation provocaría `Unknown column is_public`.
- **Impacto:** Fallo latente de escritura si alguna acción rellena `is_public`; entrada de configuración muerta que sugiere una feature (conversación pública) nunca cableada.
- **Recomendación:** Eliminar `is_public` de `$fillable`, o crear la migración `add column is_public` + cast boolean si la feature se pretende usar.
- **Archivo:** `modules/Helpdesk/app/Models/Conversation.php:53`

#### MODE-02 — Columna booleana duplicada `active` vs `is_active` en `helpdesk_ai_agents`; `scopeActive` consulta la no indexada · [CONFIRMADO]
- **Evidencia:** La tabla tiene `active` (default true, INDEXADA, `2025_12_29_020937`) y, añadida después, `is_active` (default false, SIN índice, `2026_05_01_600010`). El modelo `AiAgent` solo conoce `is_active` (fillable, cast boolean, `scopeActive` where('is_active')). La migración sincroniza una sola vez (`UPDATE ... SET is_active=active`) pero ambas columnas pueden divergir tras cualquier escritura.
- **Impacto:** Doble fuente de verdad para el mismo estado → deriva silenciosa; `scopeActive()` filtra una columna sin índice mientras la indexada (`active`) queda ignorada por el modelo.
- **Recomendación:** Consolidar en una sola columna (migración que copie `active`→`is_active`, elimine `active`, e indexe `is_active`) y dejar de mantener ambas.
- **Archivo:** `modules/Helpdesk/app/Models/AiAgent.php:22,37-40`

#### MODE-03 — `Customer.company_id` existe en BD pero no es fillable ni tiene relación `company()` · [CONFIRMADO]
- **Evidencia:** Migración `2026_05_01_500006` añade `company_id` (+ índice) a `helpdesk_customers`. `Customer::$fillable` NO lo incluye y el modelo no define `company(): BelongsTo`. Solo existe la relación inversa `Company::customers()` (`Company.php:48`). grep `company` en `Customer.php` no devuelve nada.
- **Impacto:** No se puede asignar empresa a un cliente por asignación masiva (se descarta en silencio) ni navegar `$customer->company`; relación 1-direccional incompleta.
- **Recomendación:** Añadir `company_id` a `$fillable` y un método `company(): BelongsTo => belongsTo(Company::class, 'company_id')`.
- **Archivo:** `modules/Helpdesk/app/Models/Customer.php:25-50`

#### MODE-04 — Brand a medio cablear: `brand_id` no fillable en Inbox/Conversation y sin relación inversa `brand()` · [CONFIRMADO]
- **Evidencia:** Migración `2026_05_01_500008` crea `helpdesk_brands` y añade `brand_id` (nullable, indexado) a `helpdesk_inboxes` y `helpdesk_conversations`. `Brand` define `inboxes()`/`conversations()` sobre `brand_id`, pero `Inbox::$fillable` (`Inbox.php:74-93`) y `Conversation::$fillable` NO incluyen `brand_id`, y ninguno define `brand(): BelongsTo`.
- **Impacto:** La asociación de marca no puede establecerse vía `create()`/`update()` (se descarta) y falta navegación inversa: feature multi-brand inerte a nivel de datos.
- **Recomendación:** Añadir `brand_id` a los `$fillable` de Inbox y Conversation y métodos `brand(): BelongsTo` en ambos; o retirar las columnas/relación si la feature se descarta.
- **Archivo:** `modules/Helpdesk/app/Models/Brand.php:38-46`

#### MODE-05 — Canales `Api` y `Whatsapp` exponen secretos descifrados al serializar (sin `$hidden`) · [CONFIRMADO]
- **Evidencia:** `Channels\Api` define accessors que descifran `hmac_token` y `webhook_verify_token` pero NO declara `$hidden`, así que `toArray()`/`toJson()` devuelven los secretos en claro. Igual en `Channels\Whatsapp`: `provider_config` descifra `api_key`/`access_token` (`Whatsapp.php:46-82`) y el modelo no tiene `$hidden`. Contraste con Email/Facebook/Instagram/Sms que sí ocultan sus tokens. Además `Sms.webhook_verify_token` se guarda en claro (no cifrado, a diferencia de Api).
- **Impacto:** Si el modelo de canal se serializa (API Resource, log, `morphTo` channel cargado en respuesta) se filtran credenciales descifradas de WhatsApp Cloud/360dialog/Evolution y el HMAC de la API entrante.
- **Recomendación:** Añadir `protected $hidden` a `Channels\Api` (`['hmac_token','webhook_verify_token']`) y a `Whatsapp` (`['provider_config']` o un accessor que enmascare `api_key`/`access_token`); cifrar `webhook_verify_token` en Sms para consistencia.
- **Archivo:** `modules/Helpdesk/app/Models/Channels/Api.php:46-63`

### Low

#### MODE-06 — Colisión de nombres `get/setCustomAttribute` con el patrón accessor/mutator de Eloquent · [CONFIRMADO]
- **Evidencia:** El trait define `getCustomAttribute(string $key)` y `setCustomAttribute(string $key,$value)`. Eloquent interpreta `get/setCustomAttribute` como accessor/mutator del atributo `custom` (`Str::studly('custom')='Custom'`). Usado por Conversation, Customer y Ticket.
- **Impacto:** Acceder a `$model->custom` dispara una query parásita (where key=null) y `$model->custom='x'` lanzaría TypeError (falta el 2º argumento). Footgun latente si algún día existe un atributo/columna `custom`.
- **Recomendación:** Renombrar a `getCustomField()`/`setCustomField()` (o `customAttr`) para evitar el patrón mágico `get*Attribute`.
- **Archivo:** `modules/Helpdesk/app/Models/Concerns/HasCustomAttributes.php:33,50`

#### MODE-07 — Doble almacenamiento de tags en Conversation: columna JSON `tags` + pivot `conversationTags()` · [CONFIRMADO]
- **Evidencia:** Conversation tiene `tags` en `$fillable` + cast `array` (columna JSON, migración 020915 línea 25) y a la vez la relación `belongsToMany conversationTags()` vía `helpdesk_conversation_tag_pivot`. Dos fuentes de verdad para las etiquetas.
- **Impacto:** Riesgo de divergencia entre el array JSON y los registros del pivot; consultas/filtrados pueden basarse en uno u otro y dar resultados inconsistentes.
- **Recomendación:** Elegir una única representación (preferible el pivot normalizado) y deprecar la columna JSON, o documentar explícitamente cuál es la canónica y sincronizar vía observer.
- **Archivo:** `modules/Helpdesk/app/Models/Conversation.php:50,83,251-259`

#### MODE-08 — `toInboxArray()` genera N+1 cuando no se precargan lastMessage/reads/customer/counts · [CONFIRMADO]
- **Evidencia:** `toInboxArray()` llama `getLatestMessage()` (query si `!relationLoaded('lastMessage')`), `unreadCountForInbox()` (query a `helpdesk_conversation_reads` + count de items si faltan reads/incoming_messages_count) y `$this->customer?->name` (query si customer no cargado). Los guards `relationLoaded` mitigan solo si el caller precarga TODO el set.
- **Impacto:** En listados del inbox sin el eager-load completo, una query por fila (mensaje + read + customer), degradando el render de la bandeja.
- **Recomendación:** Documentar/forzar en el caller el set requerido: `with(['lastMessage','reads','customer'])->withCount([...])`. Considerar un método estático que aplique el eager-load canónico.
- **Archivo:** `modules/Helpdesk/app/Models/Conversation.php:552-592,659-693`

#### MODE-09 — `ConversationTag::getBadgeHtmlAttribute()` emite HTML con estilo inline desde el modelo · [CONFIRMADO]
- **Evidencia:** Accessor que devuelve `<span class="badge" style="background-color: %s; color: white;">...`. Valores escapados con `htmlspecialchars` (sin XSS), pero usa style inline y mezcla lógica de vista en el modelo, contra la regla de proyecto (no `style=""` inline).
- **Impacto:** Violación de convención blade-views (no inline styles) y acoplamiento vista/modelo; dificulta theming.
- **Recomendación:** Mover el badge a un componente/partial Blade que reciba name/color y use clases utilitarias o CSS var; eliminar el accessor HTML del modelo.
- **Archivo:** `modules/Helpdesk/app/Models/ConversationTag.php:116-125`

#### MODE-10 — SlackIntegration accessor/mutator con type-hint `string` no-nullable sobre columna nullable · [CONFIRMADO]
- **Evidencia:** `setWebhookUrlAttribute(string $value)` y `getWebhookUrlAttribute(string $value)` declaran `string` no-nullable; `webhook_url` puede ser null (modelo nuevo / sin valor). Leer/serializar un registro con `webhook_url` null lanza TypeError.
- **Impacto:** Excepción al instanciar o serializar una integración sin URL configurada.
- **Recomendación:** Cambiar la firma a `?string $value` y devolver null cuando proceda (alinear con los accessors de Channels que ya usan `?string`).
- **Archivo:** `modules/Helpdesk/app/Models/SlackIntegration.php:38,43`

#### MODE-11 — Cobertura de tests de modelos ~5% y factories ausentes para ~12 modelos con HasFactory · [CONFIRMADO]
- **Evidencia:** Solo 3 tests unitarios de modelo (Conversation, Customer, ConversationTag) sobre 63 modelos. 21 modelos declaran `use HasFactory` pero solo existen 9 factories: llamar `::factory()` sobre Brand, Company, Skill, EmailAccount, CustomField, Workflow, WorkflowRun, SlaPolicy, AutomationRule, Broadcast, DripCampaign… fallaría por factory no resoluble.
- **Impacto:** Comportamientos de modelo (scopes, accessors, casts, lógica de `assignTo`/`close`/`reopen`) sin red de regresión; bloquea pruebas que dependan de factories inexistentes.
- **Recomendación:** Crear factories para los modelos con HasFactory que carecen de ellas y añadir tests unitarios de scopes/accessors/casts de los modelos centrales.
- **Archivo:** `modules/Helpdesk/tests/Unit/Models`

#### MODE-12 — `CannedReply::canBeEditedBy()` usa permiso `manage-helpdesk` (formato legacy fuera de convención) · [CONFIRMADO]
- **Evidencia:** `return $this->user_id === $userId || auth()->user()?->can('manage-helpdesk');` El proyecto usa convención Spatie `{alias}.{action}` (p.ej. `helpdesk.conversations.manage`); `manage-helpdesk` con guion no existe en los seeders de permisos.
- **Impacto:** El check de permiso probablemente siempre es false → solo el autor puede editar; autorización inconsistente con el resto del módulo.
- **Recomendación:** Reemplazar por el permiso real con convención (p.ej. `helpdesk.canned-replies.manage` o el alias correspondiente).
- **Archivo:** `modules/Helpdesk/app/Models/CannedReply.php:146`

#### MODE-13 — `RoutingRule::matches()` ejecuta regex almacenada sobre texto entrante sin validación (ReDoS) · [CONFIRMADO]
- **Evidencia:** `'regex' => (bool) @preg_match($this->keyword, $text)` — el patrón completo se toma de la regla (configurada por admin) y se aplica a cada mensaje entrante; sin límite de backtracking ni validación de delimitadores; el `@` silencia patrones inválidos (no-op silencioso).
- **Impacto:** Una regla con patrón patológico provoca backtracking catastrófico (ReDoS) al procesar mensajes; patrones mal formados fallan en silencio sin avisar al admin.
- **Recomendación:** Validar el patrón al guardar (`preg_match` de prueba con timeout), limitar con `pcre.backtrack_limit`, y registrar/avisar cuando `preg_match` devuelva false por error.
- **Archivo:** `modules/Helpdesk/app/Models/RoutingRule.php:41-48`

#### MODE-14 — `Setting.value` es text sin cast: settings no escalares no hacen round-trip · [CONFIRMADO]
- **Evidencia:** Columna `value` text (migración `2026_04_01_000015`) sin cast en el modelo. `Setting::set($key,$value)` guarda el valor crudo; un array se almacenaría como `'Array'` (warning) y los booleanos como cadenas. `Setting::get()` devuelve siempre string.
- **Impacto:** Pérdida silenciosa de datos para settings array/objeto; los consumidores deben castear manualmente booleanos. Hoy los callers pasan escalares, pero la API invita al fallo.
- **Recomendación:** Documentar que `value` es escalar/serializar explícitamente (json_encode/decode en set/get) o añadir un cast/columna JSON dedicada para settings estructurados.
- **Archivo:** `modules/Helpdesk/app/Models/Setting.php:13,28-33`

## Plan de ataque priorizado

1. **Desync fillable↔columna (MODE-01):** quitar `is_public` de `Conversation::$fillable` (o migrar la columna). Esfuerzo S, elimina un fallo de escritura latente.
2. **Estado duplicado de AiAgent (MODE-02):** consolidar `active`/`is_active` en una sola columna indexada. Esfuerzo M, evita deriva silenciosa de datos.
3. **Cableado multi-empresa / multi-brand (MODE-03, MODE-04):** añadir `company_id`/`brand_id` a `$fillable` y relaciones `company()`/`brand()` inversas, o retirar las columnas si las features se descartan. Decidir primero el estado de producto.
4. **Fuga de secretos en canales (MODE-05):** añadir `$hidden` a `Channels\Api` y `Whatsapp` y cifrar `webhook_verify_token` en Sms. Esfuerzo S, riesgo de seguridad.
5. **Endurecer RoutingRule (MODE-13):** validar regex al guardar + límite de backtracking.
6. **Deuda de tests/factories (MODE-11):** crear factories faltantes y tests de scopes/accessors. Esfuerzo L, habilita el resto de la verificación.

## Quick wins

- Quitar `is_public` de `Conversation::$fillable` (columna inexistente). [MODE-01]
- Añadir `brand_id` a Inbox/Conversation `$fillable` + `brand()` y `company_id` a Customer + `company()`. [MODE-03, MODE-04]
- Añadir `protected $hidden = ['hmac_token','webhook_verify_token']` a `Channels\Api` y proteger `provider_config` en `Channels\Whatsapp`. [MODE-05]
- Corregir permiso `manage-helpdesk` → `helpdesk.*.manage` en `CannedReply::canBeEditedBy()`. [MODE-12]
- Cambiar timezone por defecto `America/Mexico_City` → `Europe/Madrid` en `BusinessHour::initializeDefaults()`.

## Fortalezas

- Uso correcto del método `casts()` (no propiedad `$casts`) en prácticamente todos los modelos, con datetimes/arrays/booleans explícitos.
- Secretos cifrados con `Crypt::encryptString` en accessors (Email, Facebook, Instagram, Sms, Webhook, SlackIntegration, `Inbox.credentials`) y `$hidden` en la mayoría.
- `$fillable` explícito en los 63 modelos (ningún `$guarded=[]`); return types en casi todas las relaciones BelongsTo/HasMany/MorphOne.
- Manejo cuidado del cruce de conexiones helpdesk↔default vía trait `HasCrossDatabaseUserRelation` y `Group::users()` para la tabla users.
- Optimización deliberada del N+1 del inbox: relación `lastMessage()->ofMany`, `withCount`, guards `relationLoaded()`, y eliminación de `$appends` costosos en Customer.

## Cobertura de la auditoría

Cobertura COMPLETA del subsistema: leídos íntegramente los 63 modelos de `app/Models/*` (incluyendo subdirs `Channels/`, `Campaigns/`, `Concerns/`) y ambos traits (`HasCrossDatabaseUserRelation`, `HasCustomAttributes`). Cruzado con ~15 migraciones reales (conversations, ai_agents x5, brands, companies, customers/company_id, settings, snooze, social fields, indexes) para detectar `fillable`/columna desincronizados, y con el inventario de tests/factories. Análisis 100% estático (Read/Grep/Bash) — no se ejecutaron tests (BD de test bloqueada). No se re-listan hallazgos ya conocidos (ARCH-01 clave OpenAI, RW-01, PERF-08, SSRF link-preview) salvo detalle nuevo de capa de datos.

## Descartados en verificación

Sin hallazgos refutados. No había hallazgos critical/high pendientes de verificación (`verify_note`: "Sin hallazgos critical/high que verificar"). Los 14 hallazgos se confirman por inspección estática directa de modelos y migraciones.
