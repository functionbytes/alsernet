---
name: infrastructure_modules_audit
description: Audit findings for all 33 modules including second-pass quality review (2026-03-27). Security pass complete; second pass covers return types, FormRequest gaps, code duplication, N+1, dead code.
type: project
---

## Audit 2026-03-27 — MailsSettings, Media, Modules, Notification, Optimize, Page, Pulse

### Seguridad — Modules (CRÍTICO — sin autorización en acciones destructivas)
- `Modules/ModulesController.php` — enable(), disable(), install(), uninstall(), update() no llaman authorize(). Cualquier usuario autenticado puede instalar/desinstalar módulos del servidor con impacto total.
- `Modules/ModulesController.php:270-315` — install(): extrae ZIP con ZipArchive sin validar rutas dentro del ZIP. Entradas como `../../config/something.php` pueden sobrescribir archivos fuera de la carpeta modules (path traversal).

### Seguridad — Media (falta authorize en operaciones de escritura)
- `MediaController.php:153` — emptyTrash() no tiene authorize(). Cualquier usuario autenticado vacía la papelera global.
- `MediaController.php:138` — setActiveDisk() no tiene authorize(). Cualquier autenticado cambia el disco activo de sesión.
- `MediaFolderController.php:16` — store() no tiene authorize().
- `MediaFileController.php:68` — uploadFromUrl() no tiene authorize().

### Seguridad — MailsSettings (XSS en email de prueba)
- `OutgoingEmailSettingsController.php:207-252` — getTestEmailContent() interpola $settings['mail_host'], $settings['mail_port'], $settings['mail_encryption'] directamente en HTML sin htmlspecialchars(). Si esos valores se almacenaron con contenido HTML malicioso, se inyecta en el email generado.

### Seguridad — Pulse (sin autorización)
- `Pulse/PulseController.php:14` — index() no llama authorize() ni al gate `viewPulse` de Laravel Pulse. Dependencia exclusiva del middleware del ServiceProvider — si el prefijo/middleware cambia, el dashboard queda expuesto.

### Seguridad — Media corregida (verificado 2026-03-27)
- SSRF en uploadFromUrl: CORREGIDO — validateExternalUrl() bloquea IPs privadas y esquemas no-http/https.
- Private file exposure en PublicMediaController: CORREGIDO — abort_unless(auth()->check(), 403) presente.

### Warnings — Notification
- `NotificationSettingsController.php:60` — updateEnvFile() escribe .env con valores no saneados (aunque los campos están validados como tipos). Patrón arriesgado heredado.
- `NotificationService.php:16-20` — $pushService y $smsService declarados como `protected $x` sin type hint. No usa constructor property promotion (convención del proyecto).
- `NotificationController.php:201` — stats() dispara 5 queries independientes a la misma tabla (total, unread, read, today, this_week). Unificar en una sola query con selectRaw.

### Warnings — Modules
- `ModulesController.php:80-107` y `112-139` — show() y edit() construyen $moduleData con el mismo bloque duplicado. Extraer a método privado.

### Positivo
- Page: excelente cobertura de authorize() en PageController, PageAutoSaveController, PageLockController.
- Page: SearchController no expone datos de páginas no publicadas — filtra por Page::published().
- Media: SSRF y archivos privados ya corregidos desde la auditoría anterior.
- Notification: scope de usuario en todas las operaciones (notifications()->where('id', $id)) previene IDOR.
- Optimize: OptimizeController es clean, solo checkboxes predefinidos, sin input arbitrario.

## Hallazgos críticos confirmados (2026-03-26)

### Seguridad — RCE
- `Widget/WidgetController.php:118` — `new $widgetId` con input del request. CRÍTICO. Fix: validar contra Widget::getWidgets().

### Seguridad — SSRF
- `Media/MediaFileController.php:60` — `file_get_contents($url)` sin bloqueo de IPs privadas. CRÍTICO.

### Seguridad — Sin autorización
- `User/UsersController.php` — ningún método tiene authorize(). CRÍTICO.
- `Backup/BackupController.php` — download/destroy sin authorize(). CRÍTICO.
- `Widget/WidgetController.php` — update/destroy sin authorize(). CRÍTICO.

### Seguridad — Archivos privados expuestos
- `Media/PublicMediaController.php:20` — archivos privados accesibles con hash sin verificar usuario. CRÍTICO.

### Seguridad — XSS almacenado
- `System/SettingsController.php:42` — page_map sin HTMLPurifier. CRÍTICO.

### Seguridad — Inyección Artisan
- `System/MantenanceSettingsController.php:31` — Artisan::call con string concatenado, no array.
- Fix: `Artisan::call('down', ['--secret' => $value])`.

### Bugs lógicos
- `Shortcode/ShortcodeCompiler.php:108` — $cacheKey usada sin definir cuando cache=false. Bug PHP 8.x.
- `Health/HealthController.php:185` — endpoint detailed sin auth cuando APP_DEBUG=true.
- `MailsSettings/OutgoingEmailSettingsController.php:170` — catch \Swift_TransportException obsoleto en Laravel 12. Errores SMTP se pierden.

### Vista incorrecta
- `System/SettingsController.php:24` — view('theme.views.backups.backups.setting') referencia ruta inválida.

### Archivos PHP en public
- `Theme/public/theme/libs/ckeditor/samples/old/*.php` — PHP ejecutable en carpeta pública.
- `Theme/public/theme/libs/jquery.repeater/test-post-parse.php` — mismo problema.

### Patrones recurrentes en el proyecto
- Módulos sin authorize() en controladores: User, Widget, Backup (parcial), Seo (SeoMetaController, SeoStaticUrlController, SeoMetaWebController), Template/ShortcodeController.
- Validación inline en controllers en lugar de Form Request: User, System/Settings, Backup/Schedules, Storage, Template/ShortcodeController.
- Dead code con comentarios "Shop references removed": User/UsersController (shouldAssignShop).
- La función `updateSettings()` global almacena sin validar campos HTML — XSS riesgo.
- Los endpoints públicos de Health no tienen rate limiting.

## Audit 2026-03-27 — Queue, Reviews, Seo, Sitemap, Storage, Template

### Seguridad — RCE via Blade::render (CRÍTICO)
- `Template/TemplateController.php:130` — `Blade::render($content, ...)` ejecuta contenido almacenado en DB. El sanitizeTemplateContent() solo bloquea `@php`, `{{!!`, `@inject` pero admite `@include`, `@extends`, `@component`, `@livewire` y otras directivas que pueden leer archivos del filesystem o escalar contexto. Cualquier usuario con permiso de editar templates puede lograr LFI/RCE.

### Seguridad — Sin autorización
- `Seo/SeoMetaController.php` — API controller (index/store/show/update/destroy/bulkUpdate/preview/statistics): ningún método llama authorize() ni tiene middleware de permisos. La ruta está bajo `auth` pero cualquier usuario autenticado tiene acceso total a todos los meta SEO.
- `Seo/SeoMetaWebController.php` — Web controller (index/show/edit/update/destroy/bulkDelete): igual, sin authorize(). Ruta bajo `auth` sin restricción de permiso.
- `Seo/SeoStaticUrlController.php` — CRUD completo sin authorize(). Ruta bajo `auth`.
- `Seo/RobotsTxtController.php:update` — Solo valida, no llama authorize(). Un usuario autenticado sin permisos puede sobreescribir robots.txt.
- `Template/ShortcodeController.php` — store/update/destroy/toggle/updateOrder sin authorize(). Rutas bajo `auth` pero sin permission check. Shortcodes tienen `render_template` y `js_code` arbitrario ejecutado en producción.
- `Template/MenuController.php:168` — `updateStructure` no llama authorize(). Un usuario autenticado puede reorganizar cualquier menu sin permiso.
- `Template/MenuController.php:237` — `destroyItem` no llama authorize().

### Seguridad — SSRF
- `Seo/SeoAuditService.php:17` — `Http::get($url)` con URL pasada directamente desde el request del usuario. No hay bloqueo de IPs privadas (127.0.0.1, 169.254.x.x, 10.x, 172.16-31.x, ::1). Un usuario puede usar auditUrl para hacer SSRF contra la red interna.

### Seguridad — Credenciales en base de datos en texto plano
- `Storage/StorageController.php:302` — Credenciales FTP/SFTP/S3 (password, secret) se almacenan como JSON en `system.custom_storage_disks` sin cifrado. También se cargan en el request response como strings planos en getStorageData().

### Seguridad — Path traversal parcial
- `Storage/StorageController.php:445` — La blocklist de `$dangerousPaths` es incompleta: rutas como `/etc/cron.d` o `/home/user/.ssh` no están bloqueadas. El patrón de 2 niveles mínimos tampoco es suficiente.

### Bugs lógicos
- `Reviews/GoogleLocationController.php:38` — Stats query `ReviewGoogleLocation::withCount('reviews')->get()->sum('reviews_count')` carga todos los registros a memoria para sumar. Usar `DB::table()->sum()` o `selectRaw(SUM(...))` en su lugar.
- `Template/TemplateController.php:postActivateTemplate/postRemoveTemplate` — Ambos métodos no llaman authorize(), dependen solo del try/catch. Cualquier usuario autenticado puede activar/desactivar templates.

### Warnings
- `Seo/SeoRedirectController.php:57` — `$query->orderBy($sortBy, $sortDirection)` con valores del request. `$sortBy` no está en una allowlist — podría ordenar por cualquier columna existente. No es inyección SQL (Eloquent parametriza) pero sí fuga de información de esquema a través de errores.
- `Queue/QueueServiceProvider.php` — Módulo solo publica config, sin controllers ni rutas propios. No tiene riesgo, pero tiene `vendor/` con código de terceros en el repositorio del módulo.
- `Shortcode/ShortcodeController.php:compile` — El endpoint `/api/shortcodes/compile` compila shortcodes con contenido arbitrario del usuario. Si un shortcode mal escrito ejecuta código peligroso, este endpoint lo dispara. No hay rate limiting.
- `Storage` — Las rutas no tienen verificación de que el usuario sea admin/superadmin más allá del middleware `settings`. Cualquier usuario con acceso a settings puede crear/modificar discos de almacenamiento.
- `Template/ThemeCustomHtmlController.php:update` / `ThemeCustomJsController.php:update` — Almacenan HTML/JS arbitrario sin sanitización. Correcto para un admin, pero el permission check es `template.custom-code` sin verificar que el usuario sea superadmin.

### Positivo
- Reviews: uso correcto de `authorizeResource()` y políticas en ReviewController, ReviewReplyController, ReviewSavedFilterController.
- Reviews: Form Requests presentes en todos los endpoints que los necesitan.
- Widget: RCE ya fue corregido (resolveWidget valida contra allowlist).
- User: authorize() presente en todos los métodos del UsersController (corregido desde auditoría anterior).
- Template/TemplateController: ZIP import tiene buena protección anti-path-traversal con doble verificación (pre y post extracción).

## Third Pass 2026-03-27 — Missing tests, scopes, events, caching, queue, API consistency, logging

### Missing Tests (High Priority — zero test coverage)
- Activity, Backup, Cache, Captcha, Core, Database, Health, Media, Modules, Optimize, Pulse, Storage, System, Theme, Widget — all have empty test directories (subdirs but no .php files)
- Role (3 files), User (2 files), Notification (1 feature file) have minimal coverage for critical modules

### Bug — Wrong column in AttentionStatisticsService (Critical)
- `Attention/app/Services/AttentionStatisticsService.php:419-436` — `getUserStats()` queries `assigned_to` (column does not exist) instead of `assigned_user_id`. Every call to this method will throw a QueryException at runtime.

### Missing Model Scopes — Form model (Warning)
- `Forms/app/Models/Form.php` — `scopeActive()` exists at line 132 BUT `FormPublicController.php:25,37,145,176,223` all call `->where('is_active', true)` directly instead of `->active()`. The scope is defined but unused in the controller.
- `Forms/app/Providers/FormsServiceProvider.php:115` — also calls `->where('is_active', true)` directly.

### Missing Model Scopes — Mailer (Warning)
- `Mailer/app/Models/MailerVariable.php` — has no `scopeEnabled()` yet `MailerVariableService.php` repeats `->where('is_enabled', true)` 8 times (lines 15, 37, 63, 84, 116, 125, 136 and controllers at 245, 265, 295).
- `Mailer/app/Http/Controllers/MailerTemplateController.php:81,221` and `MailerComponentController.php:472` — use `->where('is_enabled', true)` on MailerLayout; no scope exists on that model.

### API Response Key Inconsistency (Warning)
- Analytics module (AnalyticsController, AnalyticsSettingController) uses `'status' => true/false` as the envelope key.
- All other modules (Attention, Forms, Backup, User, Blog, Notification, Mailer, Reviews, Page) use `'success' => true/false`.
- Two conventions coexist. Frontend JS must handle both. Standardize to `'success'` (majority convention) or document intentionally.

### Missing Activity Logging — User, Backup, Role, Mailer, Blog (Warning)
- `User/UsersController.php` — store/update/destroy use `Log::info` (app log) but never call `activity()`. User creation/deletion is a critical auditable event.
- `Backup/BackupController.php` — store/destroy not activity-logged. Backup deletions are irreversible.
- `Role/RoleController.php` — role create/update/destroy/assignUsers/updatePermissions not activity-logged.
- `Mailer/MailerTemplateController.php` — template store/update/destroy not activity-logged (templates control all system emails).
- `Blog/BlogPostController.php` — no activity logging on publish/unpublish. Reviews module correctly uses LogsActivity trait.

### Missing Events — User creation, Role assignment (Suggestion)
- `User/UsersController.php:88-89` — user is saved and role assigned with no event fired. Adding a `UserCreated` event would allow other modules (Notification, Mailer) to react without coupling.
- `Role/RoleController.php` — `assignUsers()` / `removeUser()` do not fire events; audit trail depends entirely on log().

### Caching Opportunities (Suggestion)
- `Attention/app/Services/AttentionStatisticsService.php:36-79` — `getDashboardStats()` fires 5 sequential COUNT queries on `attentions` table (total, pending, in_process, resolved, closed). These should be merged into a single `selectRaw('COUNT(*) as total, SUM(CASE ...) as pending ...')` query. The cache TTL is 5 min which masks the problem but a single query is still faster.
- `Forms/app/Providers/FormsServiceProvider.php:115` — view composer fires `Form::with('fields')->where('is_active', true)` on every request that loads a view. Should use `Cache::remember`.
- `Role/RoleController.php` — `showPermissions()` and `showModules()` likely re-query permissions/modules on every load with no caching, for an entity that rarely changes.

### Queue Improvements — User export is synchronous (Warning)
- `User/UsersController.php:270-300` — `export()` calls `User::query()->with('roles')->...->get()` synchronously, streaming response directly. With thousands of users this could timeout or exhaust memory. Should use `ExportUsersJob::dispatch()` pattern matching Attention/Forms modules.
- `Activity/ActivityController.php:77-113` — `export()` calls `Activity::query()->...->limit(5000)->get()` synchronously. 5000 activity log rows with causer eager-loaded is significant memory pressure. Should be queued.

### Middleware Gap — Analytics data endpoints (Warning)
- `Analytics/routes/web.php:48-72` — 18 data endpoints under `/api/analytics/*` have `auth` middleware but no rate limiting. These endpoints call Google Analytics API on every request (even with internal caching). A burst of 50+ simultaneous requests could exhaust GA API quotas. Add `throttle:60,1` to the group.

### Middleware Gap — Activity export endpoint (Warning)
- `Activity/routes/web.php` — `/activity/logs/export` has no rate limiting. The export streams up to 5000 rows. Add `throttle:10,1`.

### Config Improvements (Suggestion)
- `Attention/app/Services/AttentionStatisticsService.php:25` — `CACHE_TTL = 5` (minutes) is a hardcoded constant. Should be `config('attention.stats_cache_ttl', 5)` to allow environment-specific tuning without code changes.
- `Activity/ActivityController.php:88` — `->limit(5000)` hardcoded. Should be `config('activity.export_limit', 5000)`.

## Audit 2026-03-27 — Activity, Analytics, Attention, Auth, Backup, Blog, Cache

### Seguridad — Sin autorización (CRÍTICO)
- `Attention/AttentionWebController.php` — ningún método usa authorize(). Cualquier usuario autenticado puede gestionar, borrar, asignar y exportar cualquier PQRSF.
- `Attention/AttentionWebController.php:464` — bulkAction permite 'delete' sin verificación de permiso.
- `Backup/BackupController.php:120,152` — download/destroy sin authorize().
- `Backup/BackupScheduleController.php` — store/update/destroy/toggle sin authorize().
- `Activity/ActivityController.php` — todos los endpoints sin authorize().

### Seguridad — Enumeración de usuarios
- `Auth/LoginController.php:176-196` — diferencia "email no existe" de "contraseña incorrecta".

### Seguridad — Logout via GET (CSRF risk)
- `Auth/routes/web.php:26` — GET /logout sin CSRF.

### Seguridad — XSS almacenado
- `Blog/resources/views/public/post.blade.php:67` — {!! $post->content !!} sin HTMLPurifier.

### Seguridad — Path traversal en Backup
- `Backup/BackupController.php:120-126` — $filename sin basename() validation.

### Performance — N+1 en Activity
- `Activity/ActivityController.php:21-42` — logs/audit sin with('causer').
- `Activity/ActivityController.php:44-48` — 4 COUNT queries. Fix: selectRaw con SUM/CASE.

### Warning — Validación faltante
- `Attention/AttentionWebController.php:587` — assigned_user_id sin exists: validation.
- `Attention/AttentionWebController.php:476` — change_status sin validar enum.
- `Attention/AttentionWebController.php:758` — getUserDepartments() siempre retorna []. Dead code.

## Second Pass — Quality Review (2026-03-27)

### Return Types Missing (Warning — all controllers below lack `:` return type declarations)
- `Database/DatabaseController.php` — all 7 methods: index, create, store, show, edit, update, destroy. Also methods are stubs with abort(501).
- `Database/DatabaseCleanupController.php` — index(), truncate(), getTableCount()
- `Database/DatabaseSettingsController.php` — index(), edit(), update(), checkConnection()
- `Health/HealthController.php` — index(), check(), history(), ping(), health(), detailed(), runSchedule(), queueStatus(), processQueue(), scheduleList()
- `Role/RoleController.php` — index(), create(), store(), show(), edit(), update(), destroy(), duplicate(), assignUsers(), removeUser(), showPermissions(), updatePermissions(), showUsers(), showModules(), updateModules()
- `Role/PermissionController.php` — index(), create(), store(), edit(), update(), destroy()
- `User/UsersController.php` — index(), create(), store(), view(), edit(), update(), destroy(), search(), export()
- `Mailer/MailerTemplateController.php` — index(), create(), store(), edit(), update(), preview(), previewAjax(), versions(), restoreVersion(), destroy(), formatHtml(), getVariables(), variables(), variablesByModule(), toggleStatus(), sendTest()
- `Mailer/MailerVariableController.php` — index(), create(), store(), edit(), update(), destroy(), toggleStatus(), getByModule(), getGroupedByCategory(), getAvailableKeys()
- `Analytics/DashboardController.php:11` — index()
- `Attention/AttentionWebController.php` — pending(), index(), create(), store(), edit(), show(), manage(), tracking(), emails(), survey(), storeSurvey(), updateManagement()
- `Attention/AttentionCategoriesController.php` — all methods
- `Attention/AttentionTypesController.php` — all methods
- `Attention/AttentionDepartmentsController.php` — all methods
- `Attention/AttentionSedesController.php` — all methods
- `Attention/AttentionSlaPoliciesController.php` — all methods
- `Page/PageController.php` — all methods (index through forceDelete)
- `Page/PageVersionController.php` — all methods
- `Page/PreviewController.php` — generate(), revoke(), index()

### FormRequests Exist But Are Not Used (Warning)
- `Attention/AttentionCategoriesController.php:65,111` — uses inline $request->validate() when CreateAttentionCategoryRequest / UpdateAttentionCategoryRequest already exist in the module.
- `Attention/AttentionTypesController.php:66,113` — same: CreateAttentionTypeRequest / UpdateAttentionTypeRequest exist.
- `Attention/AttentionDepartmentsController.php:68,127` — CreateAttentionDepartmentRequest / UpdateAttentionDepartmentRequest exist.
- `Attention/AttentionSedesController.php:76,120` — CreateAttentionSedeRequest / UpdateAttentionSedeRequest exist.
- `Attention/AttentionSlaPoliciesController.php:50,118` — 17-rule inline validation; UpdateSlaSettingsRequest exists.

### Inline Validation Complex Enough to Warrant FormRequests (Suggestion)
- `User/UsersController.php:67,172` — 10-rule user create/update with custom messages; no FormRequest class exists.
- `Mailer/MailerTemplateController.php:119,264` — 9-10 rule validation repeated in store/update; no FormRequest.
- `System/SystemSettingsController.php:112-160` — websocket settings with 10+ rules inline.

### N+1 / Redundant Query Patterns (Warning)
- `Attention/AttentionCategoriesController.php:36-38` — 3 separate COUNT queries for stats. Consolidate: `selectRaw('SUM(1) as total, SUM(is_active) as active, SUM(NOT is_active) as inactive')`.
- `Attention/AttentionTypesController.php:37-39` — same pattern.
- `Attention/AttentionDepartmentsController.php:33-35` — same pattern.
- `Attention/AttentionSedesController.php:44-46` — same pattern.
- `Attention/AttentionTypesController.php:151-153` — N+1: UPDATE inside foreach for reorder. Use `DB::table()->upsert()` or batch update.
- `Template/ShortcodeController.php:113-115` — same reorder N+1 pattern.

### Code Duplication (Suggestion)
- `Attention/AttentionCategoriesController`, `AttentionTypesController`, `AttentionDepartmentsController`, `AttentionSedesController` — identical structure: index with search+status filter, create with max(order)+1, store/update with is_active boolean cast, toggle, destroy with guard. Extract to abstract `AttentionSettingsController` or trait.
- `Database/DatabaseSettingsController.php:95-189` — testMySQLConnection(), testPostgreSQLConnection(), testSQLiteConnection() private methods inflate controller; should be in a DatabaseConnectionTester service.

### Dead Code / Stub Controllers (Warning)
- `Attention/AttentionControllerExample.php` — not registered in any route file. Should be deleted or moved to /docs.
- `Auth/Settings/DeviceController.php` — TODO stubs. destroy() has no return type.
- `Auth/Settings/SessionController.php` — TODO stubs. destroy() has no return type.
- `Database/DatabaseController.php` — store(), update(), destroy() all call abort(501). Either implement or remove routes.

### Inconsistent Pagination (Suggestion)
- paginate(20) hardcoded in: AttentionCategoriesController, AttentionTypesController, AttentionDepartmentsController, AttentionSedesController, AttentionEmailController, Forms/FormApiController, NotificationsController, Mailer/MailerVariableController.
- paginate(15) hardcoded in: Forms/FormController, Mailer/MailerTemplateController, Mailer/MailerComponentController, Mailer/MailerEndpointController.
- Template/TemplateController uses paginate(10) and paginate(15) in different methods.
- Project has a `paginationNumber()` helper and `config('pagination.*')` used in some places (e.g., AttentionWebController uses `config('pagination.attentions')`). Hardcoded values should use the helper.

### Error Response Inconsistency (Suggestion)
- `Database/DatabaseSettingsController.php:88` — checkConnection() returns HTTP 200 on error ("Return 200 so JavaScript success block handles it" comment). Correct pattern is HTTP 422/500 with the client parsing `success: false`.
