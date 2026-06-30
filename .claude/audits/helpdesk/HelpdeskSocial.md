# Auditoría — HelpdeskSocial

> Fecha: 2026-06-29 · Health score: 56/100 · Estado: needs-work

**Resumen:** Módulo de helpdesk social bien estructurado y ampliamente testeado, con buena higiene de seguridad (tokens cifrados en reposo, webhooks firmados, sin SQL crudo ni XSS), pero **toda su superficie de gestión está rota en producción** por un desajuste de nombres de permisos que el seeder nunca crea y que la suite de tests enmascara activamente. A ello se suman varias funcionalidades a medio cablear (settings, aprobaciones, IA). El veredicto de verificación confirma los dos hallazgos prioritarios (HS-01 crítico, HS-02 alto) en su severidad original: con Spatie v6.25.0 el desajuste produce HTTP 500 (`PermissionDoesNotExist`) — no un 403 silencioso — para cualquier usuario, incluidos administradores, en las rutas de cuentas/reglas/plantillas/competidores/notas/aprobaciones.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HS-01 | critical | security | database/seeders/HelpdeskSocialPermissionsSeeder.php:12-19 | [CONFIRMADO] | M | Superficie de gestión rota en producción: código y Form Requests comprueban permisos que el seeder nunca crea |
| HS-02 | high | tests | tests/TestCase.php:16-25 | [CONFIRMADO] | S | La suite de tests enmascara el bug de permisos sembrando nombres que no existen en producción |
| HS-03 | medium | security | app/Http/Controllers/Webhooks/MetaWebhookController.php:49-52 | Sin verificar | S | La verificación de firma de webhook falla en abierto cuando `app_secret` no está configurado |
| HS-04 | medium | wiring | config/config.php:29-35 | Sin verificar | S | Funciones de IA deshabilitadas en silencio: `openai_api_key` leída por servicios pero nunca definida en config |
| HS-05 | medium | wiring | app/Http/Controllers/Settings/SocialModuleSettingsController.php:25-29 | Sin verificar | M | El update de settings es un no-op pero reporta éxito |
| HS-06 | medium | wiring | app/Models/SocialApprovalRequest.php:56-72 | Sin verificar | M | Flujo de aprobación incompleto: aprobar no publica la respuesta ni respeta el aprobador asignado |
| HS-07 | medium | conventions | app/Providers/HelpdeskSocialServiceProvider.php:146-152 | Sin verificar | M | Policies registradas pero nunca usadas; los controladores usan `abort_if(hasPermissionTo)` en vez de `authorize()` |
| HS-08 | medium | quality | app/Models/SocialComment.php:37 | Sin verificar | S | El atributo `SocialComment::intent` oculta la relación `intent()` |
| HS-09 | low | conventions | app/Http/Requests/StoreSocialAccountRequest.php:17-30 | Sin verificar | M | Form Requests usan sintaxis de validación con pipe en vez de array |
| HS-10 | low | conventions | app/Http/Controllers/Api/SocialInboxController.php:56-64 | Sin verificar | M | Respuestas de la API se desvían de la convención `{success,message,data}` + camelCase |
| HS-11 | low | performance | app/Http/Controllers/Api/SocialAnalyticsController.php:126-222 | Sin verificar | M | Analytics de agentes/SLA cargan colecciones completas en memoria; dos métodos de agente casi duplicados |
| HS-12 | low | ux | resources/views/managers/social-analytics/agents.blade.php:66,80-81,110-111 | Sin verificar | S | Estilos inline en varias blades de manager |
| HS-13 | low | conventions | app/Providers/HelpdeskSocialServiceProvider.php:5-6,88-89 | Sin verificar | S | Comandos de consola GDPR viven en el namespace `App\`, no en el módulo |
| HS-14 | low | quality | app/Jobs/ProcessSocialCommentJob.php:155-196 | Sin verificar | S | La creación de conversación desde comentario no es transaccional |

## Hallazgos detallados

### HS-01 · [CONFIRMADO] · critical · security
**Superficie de gestión rota en producción: código y Form Requests comprueban permisos que el seeder nunca crea**
`database/seeders/HelpdeskSocialPermissionsSeeder.php:12-19`

**Evidencia:** El seeder crea solo `helpdesksocial.{view, accounts.manage, rules.manage, templates.manage, analytics.view}`. Pero las comprobaciones de autorización usan nombres no definidos: `manage-rules` (33x), `manage-templates` (14x), `manage-accounts` (10x), `view-analytics` (5x), `manage` (3x), `approver` (1x), `mentions.update` (1x), `rules.view`, `templates.view`. Ej.: `SocialSettingsController.php:39` `abort_if(!...hasPermissionTo('helpdesksocial.manage-accounts'))`; `:172` `manage-rules`; `:232` `manage-templates`; `RespondSocialApprovalRequestRequest.php:11` `'helpdesksocial.approver'`; `StoreSocialNoteRequest.php:11` `'helpdesksocial.manage'`.

**Verificación:** Confirmado en su totalidad. El seeder (`HelpdeskSocialPermissionsSeeder.php:13-19`) crea exactamente 5 nombres en notación de punto: `view`, `accounts.manage`, `rules.manage`, `templates.manage`, `analytics.view`. El código de producción comprueba 9 nombres ausentes del seeder. Con Spatie v6.25.0 (instalado), `hasPermissionTo()` con un nombre ausente de la BD invoca `Permission::findByName()`, que lanza `PermissionDoesNotExist` — y esto se propaga más allá del guard `abort_if()` como un **500 sin manejar, no un 403**. Precisión: el controlador de la API de analytics (`SocialAnalyticsController`) sí usa correctamente `analytics.view` (9 ocurrencias, en seeder); son los **competidores** y sus Form Requests los que usan el roto `view-analytics`. Las operaciones de gestión dominantes (reglas, plantillas, cuentas) están todas rotas. Las Policies (`SocialAccountPolicy`, `SocialRulePolicy`, `SocialTemplatePolicy`) sí usan la notación de punto correcta, así que cualquier endpoint protegido por policy es parcialmente correcto en el lado `authorize()`; pero los `abort_if()` posteriores en los mismos controladores usan los nombres con guion erróneos, por lo que la petición igual falla a nivel de controlador.

**Impacto:** El CRUD de Cuentas/Reglas/Plantillas/Tags/SLA/Reglas de asignación/Competidores, la actualización de menciones, las notas y las aprobaciones son inutilizables en producción (500 o 403 duro) para todos los usuarios, incluidos administradores, porque los permisos que las protegen nunca se crean.

**Recomendación:** Renombrar todas las comprobaciones de código + Form Requests a los nombres en punto sembrados y conformes a la convención (`rules.manage`, `accounts.manage`, `templates.manage`, `analytics.view`) y añadir los permisos realmente faltantes (`helpdesksocial.manage`, `helpdesksocial.approver`, `helpdesksocial.mentions.update`, `helpdesksocial.rules.view`, `helpdesksocial.templates.view`) al seeder. Re-ejecutar el seeder de permisos y `cache:clear`.

---

### HS-02 · [CONFIRMADO] · high · tests
**La suite de tests enmascara el bug de permisos sembrando nombres que no existen en producción**
`tests/TestCase.php:16-25`

**Evidencia:** `TestCase::setUp` hace `firstOrCreate` de exactamente los nombres con guion/no definidos que comprueba el código (`manage-accounts`, `manage-rules`, `manage-templates`, `view-analytics`, `manage`, `approver`). El `HelpdeskSocialPermissionsSeeder` de producción crea nombres en punto distintos. Por eso todos los tests feature/web pasan en verde mientras la autorización de producción está rota (falsa confianza sobre toda la superficie de gestión).

**Verificación:** Confirmado en su totalidad. `TestCase.php:14-22` llama `Permission::firstOrCreate()` para `manage-accounts`, `manage-rules`, `manage-templates`, `view-analytics`, `manage` y `approver` — precisamente los nombres con guion que fallan en producción. Como la BD de test gana los permisos exactos que el código comprueba, cada test que llama a un endpoint de gestión pasa. CI verde; producción rota. El desajuste es bidireccional: el `TestCase` también omite `helpdesksocial.analytics.view` (que el controlador de la API de analytics sí requiere), de modo que ni la superficie "que pasa" es consistente.

**Impacto:** Una regresión crítica de autorización se publica sin detección; el verde de CI no refleja el comportamiento de producción.

**Recomendación:** Hacer que los tests arranquen el `HelpdeskSocialPermissionsSeeder` real (o aserten contra los nombres sembrados) en lugar de inventar nombres de permiso en `setUp`, de forma que la suite falle cuando código y seeder divergen.

---

### HS-03 · medium · security
**La verificación de firma de webhook falla en abierto cuando `app_secret` no está configurado**
`app/Http/Controllers/Webhooks/MetaWebhookController.php:49-52`

**Evidencia:** `if (filled($appSecret) && ! $this->verifier->verify(...)) { return 401; }` — cuando `app_secret` está vacío el guard se omite por completo y el POST público y no autenticado `POST /webhooks/helpdesk/social/meta` procede a parsear el cuerpo y despachar `ProcessSocialCommentJob` / `ProcessSocialWebhookJob`.

**Impacto:** Si el secreto no está fijado (o se borra accidentalmente), cualquiera puede inyectar comentarios/menciones/DMs falsificados que crean registros `SocialComment` + `Conversation` de Helpdesk y disparan auto-respuestas.

**Recomendación:** Tratar un `app_secret` ausente como fallo duro (devolver 401/503 y loguear) en vez de procesar payloads sin firmar; exigir el secreto en la validación de config en boot.

---

### HS-04 · medium · wiring
**Funciones de IA deshabilitadas en silencio: `openai_api_key` leída por servicios pero nunca definida en config**
`config/config.php:29-35`

**Evidencia:** `OpenAiIntentClassifier.php:19`, `AiCopilotService.php:18` y `SentimentAnalysisService.php:17` leen `config('helpdesksocial.intent_classification.openai_api_key')`, pero el bloque `intent_classification` solo define `enabled/provider/openai_model/confidence_threshold/cache_ttl_minutes` — sin clave `openai_api_key` ni binding de env. Por tanto `isAvailable()` siempre es false.

**Impacto:** La clasificación de intención OpenAI/híbrida, el análisis de sentimiento por IA y el copiloto de IA nunca pueden usar OpenAI; siempre caen al fallback basado en reglas/genérico incluso con `provider=openai` configurado.

**Recomendación:** Añadir `'openai_api_key' => env('HELPDESK_SOCIAL_OPENAI_API_KEY')` al bloque `intent_classification` (y documentar la variable de entorno).

---

### HS-05 · medium · wiring
**El update de settings es un no-op pero reporta éxito**
`app/Http/Controllers/Settings/SocialModuleSettingsController.php:25-29`

**Evidencia:** `update()` valida vía `UpdateSocialModuleSettingsRequest` y luego redirige con `'Configuración actualizada. Reinicia el servicio...'` sin persistir nada (comentario: "Config values are read-only at runtime; flash to session for display purposes.").

**Impacto:** Los administradores que cambian settings reciben un toast de éxito pero nada se guarda; la página de settings es decorativa.

**Recomendación:** O persistir los settings (store/override en BD) y leerlos en runtime, o hacer la UI de solo lectura y quitar el mensaje de éxito engañoso.

---

### HS-06 · medium · wiring
**Flujo de aprobación incompleto: aprobar no publica la respuesta ni respeta el aprobador asignado**
`app/Models/SocialApprovalRequest.php:56-72`

**Evidencia:** `approve()`/`reject()` solo fijan `status`/`approver_note`/`responded_at`; no hay llamada al API client para enviar la respuesta aprobada a la red. `SocialApprovalRequestsController::approve/reject` (líneas 65-87) solo comprueban `status==pending` y el permiso (no definido) `'helpdesksocial.approver'` — nunca verifican que el actor sea igual a `approver_user_id`, pese a que el índice expone un filtro `pending_for_me` sobre `approver_user_id`.

**Impacto:** Las respuestas en cola de aprobación nunca se entregan tras aprobarse (flujo sin salida); cualquier usuario con el permiso de aprobador puede aprobar solicitudes dirigidas a otra persona.

**Recomendación:** Al aprobar, despachar la respuesta real (vía motor de auto-reply / API client) y marcar el comentario como respondido; restringir approve/reject al aprobador designado (o a un permiso manage) mediante policy/ownership.

---

### HS-07 · medium · conventions
**Policies registradas pero nunca usadas; los controladores usan `abort_if(hasPermissionTo)` en vez de `authorize()`**
`app/Providers/HelpdeskSocialServiceProvider.php:146-152`

**Evidencia:** Se registra `Gate::policy` para `SocialAccount/SocialComment/SocialRule/SocialTemplate`, pero ningún controlador llama `$this->authorize()`/`authorizeResource` (grep de `authorize(` en Controllers no devuelve nada); todo el gating es `abort_if(auth()->user()?->hasPermissionTo(...))` inline. Las clases Policy (ej. `SocialCommentPolicy`) son código muerto efectivo.

**Impacto:** La lógica de autorización está duplicada e inconsistente con la convención del proyecto; las policies registradas dan falsa impresión de autz centralizada y no se pueden aplicar reglas de ownership por recurso.

**Recomendación:** Canalizar la autorización por las policies vía `$this->authorize()`/`authorizeResource()` (y `@can` en blades), o borrar las policies sin uso; en cualquier caso unificar los nombres de permiso (enlaza con HS-01).

---

### HS-08 · medium · quality
**El atributo `SocialComment::intent` oculta la relación `intent()`**
`app/Models/SocialComment.php:37`

**Evidencia:** `'intent'` es una columna string fillable Y existe un método de relación MorphOne `intent()` (devuelve `SocialIntent`). Eloquent resuelve `$comment->intent` al atributo, así que la relación es inalcanzable por acceso a propiedad. Los controladores hacen eager-load (`SocialInboxController.php:28` `with(['...','intent',...])`; `SocialSettingsController.php:115` `load([...,'intent',...])`) pero las blades solo leen el string (`$comment->intent`).

**Impacto:** Los eager loads ejecutan una query extra por petición cuyo resultado nunca es accesible; cualquier código futuro que use `$comment->intent->confidence` obtiene en silencio el string en vez de la relación (bug latente).

**Recomendación:** Renombrar la relación (ej. `intentClassification()`) para evitar la colisión con la columna, y eliminar los eager loads `with('intent')`/`load('intent')` desperdiciados (o usar la relación renombrada).

---

### HS-09 · low · conventions
**Form Requests usan sintaxis de validación con pipe en vez de array**
`app/Http/Requests/StoreSocialAccountRequest.php:17-30`

**Evidencia:** 20 de 26 Form Requests definen reglas con pipe strings, ej. `'name' => 'required|string|max:255'`. La regla del proyecto (`form-requests.md`) exige sintaxis array `['required','string','max:255']`. Varios también omiten `attributes()` (ej. `StoreSocialAccountRequest`).

**Impacto:** Inconsistente con las convenciones; más difícil extender reglas con objetos `Rule`.

**Recomendación:** Convertir reglas pipe a sintaxis array y añadir `attributes()` en español donde falten.

---

### HS-10 · low · conventions
**Respuestas de la API se desvían de la convención `{success,message,data}` + camelCase**
`app/Http/Controllers/Api/SocialInboxController.php:56-64`

**Evidencia:** Los endpoints de listado devuelven `{data, meta:{current_page,last_page,per_page,total}}` con claves snake_case; `api-controllers.md` requiere claves camelCase y un envoltorio `{success,message,data}`. El patrón se repite en los controladores Api (ej. `SocialApprovalRequestsController.php:31-39`).

**Impacto:** Contrato de API inconsistente con el estándar documentado; cosmético pero afecta la predictibilidad del cliente.

**Recomendación:** Estandarizar el envoltorio JSON / casing de claves (o documentar este módulo como excepción intencional).

---

### HS-11 · low · performance
**Analytics de agentes/SLA cargan colecciones completas en memoria; dos métodos de agente casi duplicados**
`app/Http/Controllers/Api/SocialAnalyticsController.php:126-222`

**Evidencia:** `agentsPerformance()`, `agentPerformance()` y `slaOverview()` hacen `->get()` de todos los comentarios de la ventana de fechas y agregan en PHP (count/avg sobre colecciones). `agentsPerformance` y `agentPerformance` son ~95% idénticos. Las métricas diarias en otras partes se calculan en SQL, así que el patrón es inconsistente.

**Impacto:** Memoria/latencia crecen linealmente con el volumen de comentarios por ventana; riesgo de grandes asignaciones en cuentas activas. Los métodos duplicados aumentan el coste de mantenimiento.

**Recomendación:** Agregar con `groupBy` + `selectRaw` (COUNT/AVG) en SQL como en `overview()`; colapsar los dos métodos de agente en uno.

---

### HS-12 · low · ux
**Estilos inline en varias blades de manager**
`resources/views/managers/social-analytics/agents.blade.php:66,80-81,110-111`

**Evidencia:** `style="width:36px;height:36px;..."` y progress-bar `style="width: {{ $score }}%"` más `social-inbox/show.blade.php:42` `style="background-color: {{ $tag->color }}"`. Estilos inline aparecen en ~9 blades. La regla del proyecto prohíbe `style=""` (algunos width/color dinámicos son la excepción justificada común).

**Impacto:** Deriva menor de convención; los casos estáticos (tamaños px fijos) deberían ser clases utilitarias.

**Recomendación:** Mover estilos inline estáticos a utilidades Bootstrap/clases CSS; mantener solo width/color genuinamente dirigidos por datos vía custom properties si hace falta.

---

### HS-13 · low · conventions
**Comandos de consola GDPR viven en el namespace `App\`, no en el módulo**
`app/Providers/HelpdeskSocialServiceProvider.php:5-6,88-89`

**Evidencia:** `ExportSocialUserDataCommand` y `AnonymizeSocialUserCommand` se importan desde `App\Console\Commands` y los registra el provider del módulo (los otros 8 comandos viven en `Modules\HelpdeskSocial\Console\Commands`). Las clases existen en disco pero rompen la encapsulación del módulo.

**Impacto:** Comportamiento específico del módulo se filtra al namespace de la app; el módulo no es autocontenido y puede romperse si esas clases `App` se eliminan (aparecían como borrados en `git status` al inicio de la sesión).

**Recomendación:** Mover ambos comandos a `Modules\HelpdeskSocial\Console\Commands` y actualizar los imports del provider.

---

### HS-14 · low · quality
**La creación de conversación desde comentario no es transaccional**
`app/Jobs/ProcessSocialCommentJob.php:155-196`

**Evidencia:** `createConversationFromComment()` hace `Customer::firstOrCreate` + `Conversation::create` + `ConversationItem::create` + `comment->update` secuencialmente dentro de un try/catch sin `DB::transaction()`; un fallo a mitad deja una `Conversation` huérfana sin su item ni back-link.

**Impacto:** Escrituras parciales en caso de fallo producen datos de Helpdesk inconsistentes; el catch solo loguea, así que la inconsistencia es silenciosa.

**Recomendación:** Envolver el bloque multi-escritura en `DB::transaction()`.

## Plan de ataque priorizado

1. **HS-01 (crítico) — Arreglar el desajuste de nombres de permisos.** Alinear controladores + Form Requests (y `tests/TestCase.php`) con los nombres conformes que crea el seeder (`rules.manage`, `accounts.manage`, `templates.manage`, `analytics.view`) y añadir los realmente faltantes (`manage`, `approver`, `mentions.update`, `rules.view`, `templates.view`) al seeder. Re-ejecutar seeder y `cache:clear`. Sin esto, toda la UI/API de gestión da 500 en producción.
2. **HS-02 (alto) — Dejar de enmascarar la config de producción en tests.** Eliminar la creación ad-hoc de permisos en `tests/TestCase.php` y arrancar el seeder real, para que los tests ejerciten los mismos nombres que se publican.
3. **HS-04 / HS-05 / HS-06 (medio) — Cerrar funciones a medio cablear.** Definir `openai_api_key` en config (IA deshabilitada en silencio), hacer que el update de settings persista de verdad, y completar el flujo de aprobación (publicar la respuesta aprobada + exigir el aprobador designado).
4. **HS-03 (medio) — Cerrar el fail-open del webhook** rechazando/logueando cuando `app_secret` no está configurado.
5. **HS-07 / HS-08 (medio) — Higiene de autz y modelo.** Canalizar autorización por policies (o borrarlas) y renombrar la relación `intent()`.
6. **HS-09 a HS-14 (bajo) — Limpieza de convenciones, rendimiento y robustez.**

## Quick wins

- Añadir `openai_api_key` al bloque `intent_classification` de `config/config.php` para que la intención OpenAI/híbrida, el sentimiento por IA y el copiloto dejen de caer en fallback silencioso. (HS-04)
- Hacer que `MetaWebhookController` rechace (o loguee+descarte) cuando `app_secret` no está configurado, en vez de procesar en abierto. (HS-03)
- Eliminar los locales muertos `$signature`/`$rawBody` en `MetaWebhookController::handle` (el verifier los re-lee del request). (HS-03)
- Envolver las escrituras de `ProcessSocialCommentJob::createConversationFromComment` en `DB::transaction()`. (HS-14)

## Fortalezas

- Los access tokens se cifran en reposo vía casts de atributo `Crypt` y nunca se exponen en `SocialAccountResource`; las firmas de webhook se verifican con `hash_hmac` + `hash_equals`; el GET de verify usa comparación de token en tiempo constante.
- Sin inyección SQL (`selectRaw` usa solo literales, sin concatenación de usuario), sin salida cruda `{!! !!}` en blades (el auto-escape de Blade mitiga XSS de comentarios almacenados), sin `shell_exec`/iconos Tabler/tema select2 bootstrap-5.
- Los jobs en cola están configurados consistentemente (`ShouldQueue`, `$tries`/`$timeout`, `failed()` con logging de contexto, colas nombradas vía config); el trabajo pesado de ingesta/IA está en cola, no es síncrono.
- Abstracción de canal limpia (`SocialChannelRegistry` + interfaces para `ApiClient`/`WebhookParser`/`WebhookVerifier`) que habilita plataformas futuras; los rate limiters de API y webhooks entrantes están bien definidos en `AppServiceProvider`.
- Amplia cobertura de tests: 31 archivos de test entre Api, Web, Jobs, Commands, Webhooks y Unit.

## Cobertura de la auditoría

Análisis estático únicamente (tests con BD no ejecutados, según instrucciones). Lectura profunda: `EventServiceProvider` + `HelpdeskSocialServiceProvider`, los 3 archivos de rutas, `MetaWebhookController`/`Verifier`/`Parser`, `MetaApiClient`/`ChannelProvider`/`Registry`, modelos `SocialAccount` + `SocialComment` (otros por grep), `Managers/SocialSettingsController` (completo), controladores Api Inbox/Analytics/ApprovalRequests, seeder de permisos, `tests/TestCase.php`, `config/config.php`, `RuleBasedAutoReplyEngine`, `OpenAiIntentClassifier`, `AiCopilotService`, `SocialTemplate::render`, `SocialCommentPolicy`, `SocialModuleSettingsController`, ~9 form requests. Verificada la config de reintentos en los 9 jobs y el uso de permisos en todo el repo.

**No leído en profundidad:** internals de `SmartAssignmentService`/`SlaTrackingService`/`SocialListeningService`/`KnowledgeBaseSearch`, los 9 listeners, 4 notificaciones, exports, widgets, middleware `LogSocialApiCalls`, los ~10 controladores API restantes (revisados por grep), y la mayoría de los 19 modelos/25 blades (muestreados).

**Nota de verificación:** Ambos hallazgos verificados (HS-01, HS-02) se confirman en su severidad original. La evidencia es inequívoca: seeder y mayoría del código de producción usan dos convenciones de nombres incompatibles (punto vs guion). Con Spatie v6.25.0 la consecuencia en runtime es HTTP 500 (`PermissionDoesNotExist`), no un 403 silencioso, para cada usuario que acceda a rutas de gestión de cuentas/reglas/plantillas/competidores/notas/aprobaciones. La suite de tests da falsa confianza sembrando los nombres rotos exactos. No se halló diseño intencional ni workaround mitigante (ningún handler que convierta `PermissionDoesNotExist` en una respuesta más suave). Los hallazgos HS-03 a HS-14 no formaron parte del pase de verificación profunda y se reportan en su severidad original.

## Descartados en verificación

Ninguno. Ningún hallazgo fue refutado durante la verificación.
