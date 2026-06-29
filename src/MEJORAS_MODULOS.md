# Análisis exhaustivo de módulos — Mejoras posibles

**Fecha:** 2026-04-27
**Alcance:** 42 módulos activos del proyecto Alsernet (Inoqualab) — excluidos `Ecommerce` y `EcommercePayment`.
**Metodología:** auditoría por 9 agentes paralelos, revisando estructura PSR-4, controllers, services, modelos, requests, policies, routes, migrations, factories, tests, vistas, lang, configs y permisos vs. las convenciones del proyecto (`.claude/rules/*.md`, `CLAUDE.md`).

## Índice

1. [Resumen ejecutivo](#resumen-ejecutivo)
2. [Hallazgos transversales](#hallazgos-transversales)
3. [Módulos por categoría](#módulos-por-categoría)
   - Auth/Security: Auth, User, Role, Captcha, Notification
   - Public Content: Blog, Faqs, Page, Reviews, SimpleSlider
   - Marketing/Forms: Ads, Forms, Attention, Newsletter
   - Helpdesk family: Helpdesk, HelpdeskAgents, HelpdeskCampaigns, HelpdeskTickets
   - Mail: Mailer, MailsSettings, Mailrelay
   - SEO/Analytics: Seo, Sitemap, Analytics, Cookie
   - Infrastructure: Cache, Queue, Storage, Backup, Optimize
   - System/Admin: Core, System, Database, Health, Modules, Activity, Pulse
   - Visual/Misc: Template, Theme, Media, Shortcode, Locales, Locations
4. [Top 30 acciones priorizadas](#top-30-acciones-priorizadas)

---

## Resumen ejecutivo

| Categoría | Módulos | Tests totales | Convención OK | Riesgo agregado |
|---|---|---|---|---|
| Auth/Security | 5 | ~297 | Parcial (Auth/User usan `panel/setting/` singular) | **Alto** (Role sin authorize en mayoría de endpoints) |
| Public Content | 5 | ~36 archivos | Bueno | Medio (Blog `StoreBlogPostRequest::authorize===true`, Page namespace lowercase en composer) |
| Marketing/Forms | 4 | ~33 | Mixto | Alto (Newsletter sin tests, Attention God controller 2000 líneas, bug enum/string en `AttentionPolicy`) |
| Helpdesk family | 4 | 43 (Helpdesk core: 5) | Pobre (4 convenciones de permisos distintas) | **Crítico** (HelpdeskTickets usa `create_tickets` legacy snake_case, Helpdesk core sin coverage) |
| Mail | 3 | ~30 | Pobre (Mailer routes plurales, Mailrelay caos) | **Crítico** (Mailrelay tiene Auth scaffolding, vendor commit, sub-modules huérfanos, MailsSettings sin tests/policies/encryption) |
| SEO/Analytics | 4 | ~46 | Pobre (Seo y Analytics usan `panel/setting/` singular y `Module.entity.action` PascalCase) | Medio |
| Infrastructure | 5 | ~60 | Mixto | **Alto** (Backup ejecuta `sudo` desde HTTP, Optimize sin permission seeder) |
| System/Admin | 7 | ~50 | Pobre (mayoría sin permission seeder ni policies) | **Alto** (Health namespace mismatch en composer, Database `truncate()` sin protección sólida) |
| Visual/Misc | 6 | ~40 | Pobre | Alto (Locales y Locations sin tests, Theme sin tests del NavService crítico) |

**Salud general del repo:** Funcional pero con deuda técnica significativa. La calidad varía drásticamente entre módulos: algunos (Modules, Reviews, Page, HelpdeskTickets, Optimize, Forms, Storage) son de alta calidad ingenieril; otros (Newsletter, Locales, Locations, Pulse, Mailrelay, Database, Health) tienen debilidades que requieren intervención inmediata.

---

## Hallazgos transversales

Patrones observados en múltiples módulos. Resolverlos a nivel proyecto multiplica el impacto.

### 1. Convención de routes inconsistente (P0 transversal)
- **Plural correcto** (`panel/settings/{alias}` + `settings.{alias}.*`): Cookie, Storage, Queue, Cache, Page, Blog, Forms, Reviews, HelpdeskCampaigns, Mailrelay (parcial).
- **Singular roto** (`panel/setting/{alias}` o name `setting.`): Auth, User, Seo, Analytics, System, Database, Modules, Health, Backup, Shortcode.
- **Acción:** Decidir convención (la regla `routes.md` dice plural) y migrar con redirects 301 desde la legacy.

### 2. Convención de permisos divergente (P0 transversal)
Coexisten 4+ esquemas:
- `{alias}.{action}` (recomendado por `seeders.md`): Cookie, Storage, Captcha (parcial), HelpdeskCampaigns, Page, Blog (parcial).
- `{Alias}.{entity}.{action}` PascalCase: Activity, Cache, Backup, Mailer, MailsSettings (parte), Forms.
- `{alias}.{entity}.{action}` (3 segmentos lowercase): Reviews, Seo, Analytics, HelpdeskAgents.
- Legacy snake_case (`view_X`, `create_X`): HelpdeskTickets Form Requests, antiguo UserPolicy.
- **Acción:** Estandarizar a `{alias}.{action}` (2 segmentos) o `{alias}.{entity}.{action}` (3 segmentos) — pero **uno solo**. Refactor masivo de seeders + policies + Form Requests.

### 3. Validación inline en controllers (P0 transversal)
~150+ ocurrencias de `$request->validate([...])` en controllers en violación directa de `controllers.md` y `form-requests.md`. Concentración crítica en:
- Helpdesk core (~94 entre los 4 módulos Helpdesk)
- Attention (~7 endpoints en God controller)
- HelpdeskTickets, Reviews, Forms, Auth, Role, Captcha, Notification, Faqs, Page, Newsletter, Mailer, MailsSettings (todos), Mailrelay (~24 controllers), Activity, System, Database, Modules, Pulse, Backup, Optimize, Cache, Queue, Storage, Locales, Locations, Shortcode, Health, Template, Media (parcial).

### 4. Policies faltantes (P0 transversal)
Módulos sin policies cuando deberían tenerlas:
- **Sin policies en absoluto:** Auth, Newsletter, Mailrelay (parcial), MailsSettings, Locales, Locations, Cache, Queue, Storage, Backup, Optimize, Activity, Health, Pulse, Database, System, Core, Captcha (mal registrada).
- **Policies parciales:** Helpdesk (3 de ~25 modelos), Reviews (7 de ~19), HelpdeskTickets (6 de ~28), HelpdeskAgents (2 de 8), Notification (solo settings).
- **Policy con bug:** Attention (`AttentionPolicy` compara enum `$attention->status === 'closed'` como string — siempre falsa).

### 5. Form Requests con `authorize() { return true; }` (P0 seguridad)
Bypass de autorización en Form Request. Hallazgos:
- `Blog\StoreBlogPostRequest`, casi seguro `UpdateBlogPostRequest`, `StoreBlogCategoryRequest` etc.
- `User\StoreUserRequest`, `UpdateUserRequest` (depende solo del controller).
- `Locales\StoreLocaleRequest`, `UpdateLocaleRequest`.
- `Captcha\CaptchaSettingRequest` (bypass por hasRole en lugar de Spatie permission).
- `Mailrelay\CreateCampaignRequest` (probable).

### 6. Cobertura de tests muy desigual (P0)
- **Sin tests (0):** Newsletter, MailsSettings, Storage (cero feature), Cache, Database, Health, Pulse, Locales, Locations, Theme.
- **Cobertura excelente:** Modules (~30), HelpdeskTickets (24), Reviews (~33+), Optimize (~20), Forms (~11), Storage (50 mixed).
- **Cobertura intermedia:** Auth (74), Role (86), Page (16), Blog (18), Attention (12), Seo (19), Mailer (3 archivos/17 tests).

### 7. Tabler Icons en lugar de FontAwesome 6 (P0)
**Solo HelpdeskTickets** tiene 12+ ocurrencias confirmadas (`TicketViewSeeder`, `TicketCategory::getIconAttribute()` retorna `'ti ti-ticket'` por defecto). Resto del proyecto cumple con FA6.

### 8. Inline styles en views (P1 transversal — ~447+ ocurrencias)
Detectado en todos los módulos. Top concentraciones: Mailrelay (30+), Seo (329 en views), Attention (multiples), Helpdesk (4 módulos suman ~447).

### 9. Namespace lowercase en composer.json (P0 — rompe Linux)
- `Page`: `composer.json` declara `modules\\Page` en lowercase. Falla autoload PSR-4 case-sensitive en producción Linux.
- `Sitemap`: idem, `modules\\Sitemap`.
- `Health`: el namespace está mal — `Modules\\HealthCheck` cuando todo el código usa `Modules\\Health`.

### 10. Vendor commit dentro de módulos (P1)
- `Optimize/vendor/`, `Queue/vendor/`, `Backup/vendor/`, `Analytics/vendor/`, `Media/vendor/`, `Mailrelay/vendor/`. Hincha repo, conflictos en `composer update`. Mover al composer raíz.

### 11. JS/Frontend que viola "No Livewire/Inertia/React" (P1)
- Helpdesk core: `resources/js/widget/*.tsx` con React.
- HelpdeskAgents: `useAiAgent.ts`, `useFlowEditor.ts` React hooks.
- Pulse: depende intrínsecamente de Livewire (razón del disable).

### 12. Overlap arquitectónico (P0 estratégico)
- **Conversation (Helpdesk) vs Ticket (HelpdeskTickets):** 80% overlap.
- **AI Settings duplicado:** Helpdesk `Settings/SettingsController::aiUpdate` + HelpdeskAgents `AiAgentSettingsController`.
- **Mailer vs Mailrelay:** ambos tienen Templates, Variables, Components, Endpoints.
- **Sitemap:** módulo dedicado + `Page\SitemapController` + `Seo\SitemapAdminController`.
- **Pixels tracking:** Cookie + Analytics duplican IDs Meta/TikTok/LinkedIn.
- **HealthController (web) vs Health\Api\HealthController:** duplicado, segundo no registrado.

### 13. Falta lang/ files (P1)
~25 módulos sin `resources/lang/` (strings hardcoded en español): Auth, User, Role, Notification, Mailer, MailsSettings, Mailrelay, Activity, Pulse, System, Health, Database, Modules, Cache, Queue, Storage, Backup, Optimize, Theme, Locales, Locations, Newsletter, Page, Sitemap, Faqs (solo es).

### 14. Carpeta `Tests/` con T mayúscula (P2)
Rompe PSR-4 case-sensitive en Linux: User, Role, Optimize.

### 15. Faltan API Resources (P1)
La mayoría de endpoints API devuelven arrays/modelos crudos en lugar de Eloquent Resources según `api-controllers.md`: Auth, Notification, Activity, Forms (parcial), Reviews (4 de muchos), Attention (cero), Page (parcial), Mailrelay (parcial), Analytics (cero de 18), Cookie, Locations, Seo, Sitemap.

---

## Módulos por categoría

### Auth/Security

#### Módulo: Auth

**Estado actual.** Módulo de autenticación robusto y maduro. 21 controllers, 8 modelos, 7 servicios, 9 events, 6 listeners, 5 notifications, 8 middlewares, 1 Job, 2 Rules, 1 API Resource, 14 archivos de tests con 74 test methods, 18 migraciones, 1 PermissionsSeeder. NO tiene policies, NO tiene factories, NO tiene `lang/`. Login/Register/Reset/Magic Link/2FA/Impersonation/Account Deletion/Email Change/Devices/Sessions/API Tokens completos. Riesgo: módulo de seguridad crítico.

**P0**
- Faltan Policies dedicadas. La autorización en `AuditController` se hace via `Gate::define('viewAudit', ...)` en `AuthServiceProvider`. Crear `LoginAttemptPolicy`, `ImpersonationLogPolicy`, `SessionPolicy`, `TrustedDevicePolicy`.
- `LoginController::login` NO usa Form Request — usa `$request->validate()` inline.
- `ProfileController::updateAvatar/deleteAvatar`, `PasswordController::update`, `TwoFactorAuthenticationController` (4 métodos), `SessionController::destroyOthers`, `DeviceController::trust`, `ApiTokenController::store`, `AccountDeletionController`, `EmailChangeController::request`, `MagicLinkController::request/consume`, `ResetPasswordController::reset`, `ForgotPasswordController`, `TwoFactorChallengeController::verify`, `TokenApiController::store`, `Api/AuthApiController::twoFactorChallenge` — todos validan inline.
- API JWT en `composer.json` (`tymon/jwt-auth`) declarado pero NO se usa (Sanctum activo).
- `AuthApiController::issueChallengeToken` HMAC-SHA256 sin invalidación de token (replay window 5 min).
- `LockScreenController` sin Form Request.

**P1**
- Permisos seeder muy escaso (solo 6).
- `UserResource` está en módulo Auth pero debería estar en módulo `User`.
- NO existe `lang/` — mensajes hardcoded en español.
- Factories ausentes (`database/factories/.gitkeep` vacío).
- Inconsistencia de prefix: `routes/settings.php` usa `panel/setting/auth` (singular) — convención exige plural.
- `Gate::before` con super-bypass `super-settings` peligroso.
- Endpoints API muy reducidos — faltan password reset, magic link, 2FA setup/disable, sessions/devices/activity API.
- `AuthApiController::login` NO usa `AuthService::attempt` — duplica lógica.
- `AdminAuditController::loginAttempts/impersonations` no soporta export CSV.
- `ProfileController` 6 métodos `sessions/twoFactor/...` simplemente delegan a `index($request->merge(['tab'=>...]))`.
- `AuthService` mezcla concerns — separar `LockoutService`.
- `TwoFactorChallengeController::verify` y `Api/AuthApiController::twoFactorChallenge` usan `app(TwoFactorService::class)` en lugar de DI.
- Falta cobertura de `LockScreenController`, `ApiTokenController` web, `EmailChangeController::confirm` failure paths, `MagicLinkController::consume` con 2FA, `AccountDeletionController` job-deferred.

**P2**
- Inline styles en 8+ vistas (settings/profile/tabs/two-factor, password, account, activity, sessions, auth/passwords/reset, components/security-widget).
- Activity log inconsistente — Auth usa Events+Listeners pero NO Spatie ActivityLog directo.
- `ResetPasswordController::reset` revoca solo `sessions`, no `tokens()` Sanctum — riesgo: tras reset, tokens API siguen activos.
- `ImpersonationController::start` con `int|User $user` ambiguo.
- `AuditController::forceLogout/unlockAccount/restoreAccount` repiten `authorize('viewAudit', LoginAttempt::class)` — debería usar abilities diferentes.

**P3**
- README de 12K líneas — bien documentado.
- `MathCaptcha` no se usa en login form — debería integrarse opcional.
- `IpGeolocationService` debería ser queueable (latencia en login).

#### Módulo: User

**Estado actual.** 2 controllers (`UsersController` web monolítico de 374 líneas, `Api/UserApiController` solo `me()`), 1 Policy (`UserPolicy`), 2 Form Requests, 3 events, 1 console command (`AssignUserUids`), 6 vistas, 2 archivos de tests (38 methods). NO tiene migrations propias, NO tiene config/, NO tiene composer.json, NO tiene lang/, NO tiene factories, NO tiene services, NO tiene seeder de permisos, NO tiene UserResource, NO tiene listeners para los events.

**P0**
- NO existe `composer.json` del módulo. Crear con namespace `Modules\User\` PSR-4.
- NO existe permissions seeder — `UserPolicy` checkea `view-users`, `create-users`, `edit-users`, `delete-users`, `impersonate-users` pero ningún seeder los crea.
- `UserPolicy` viola convención — usa `view-users` en vez de `user.view`.
- `StoreUserRequest::authorize()` y `UpdateUserRequest::authorize()` retornan `true` sin verificar permiso.
- `UsersController::store` y `update` sin `DB::transaction`.
- Eventos `UserCreated/Updated/Deleted` se disparan pero NO hay listeners registrados.
- `UsersController::bulkAction` valida con pipe-syntax violando regla del proyecto (array-syntax).
- Servicio faltante: `UserService` debe contener crear/actualizar/eliminar/bulk con transacción.
- Logging excesivo (5 `Log::info/error`) directo en controller.

**P1**
- `UserApiController` casi vacío — solo `me()`. Faltan endpoints CRUD.
- `api.php` está en `panel/setting/users` (web middleware) — confuso, no es API REST.
- NO existe `UserResource`.
- `UpdateUserRequest::rules()` consulta BD para obtener userId — N+1 implícito.
- Email/Identification no normalizados (sin lowercase trim).
- NO hay `view-permissions` policy ability ni `bulkAction` registrada.
- Búsqueda con `like '%input%'` — performance mala en escala (considerar FULLTEXT o Scout).
- Listeners faltantes para `UserCreated/Updated/Deleted` (welcome mail, CRM sync, etc.).
- `destroy` retorna `RedirectResponse` siendo DELETE AJAX — debería ser `JsonResponse`.
- Confirmación de eliminación con `confirm()` JS nativo — reemplazar por modal Bootstrap.
- NO existe `lang/`.

**P2**
- `stats` en index hace 4 queries separadas — combinar en un solo `selectRaw` con `COUNT(CASE WHEN ...)`.
- `view($uid)` carga 20 activities sin paginación — cargar AJAX.
- `UpdateUserRequest::rules` no valida `password.confirmed`.
- Inline styles en `users/index.blade.php`, `users/view.blade.php`, `activitys/index.blade.php`.
- `fa-duotone fa-solid fa-ellipsis` en `users/index.blade.php:191` — debe ser `fa-ellipsis-vertical`.
- `Activity::class` hard-coded a Spatie\Activitylog\Models\Activity.
- `NavService::registerMiniItem` usa icono `fa-duotone fa-thin fa-users-medical` — no es de los grupos `fas/far/fab` permitidos.

**P3**
- `Tests/` con T mayúscula — viola PSR-4 convención del proyecto.
- README solo 1.2K — escaso.
- Falta endpoint `restore` para usuarios soft-deleted.

#### Módulo: Role

**Estado actual.** 3 controllers (`RoleController` 630 líneas, `PermissionController`, `UserPermissionController`), 1 model (`AppRoute`), 4 events, 2 listeners, 1 service (`ActivePermissionService`), 1 trait (`HasRolesAndPermissions`), 1 helper (`PermissionHelper`), 1 notification, 8 console commands, 1 middleware, 1 Form Request (`RoleRequest`), 5 archivos de tests con 86 test methods, 5 migraciones, 2 seeders, 11 vistas, 4 configs. NO tiene policies, NO tiene factories, NO tiene API controllers reales, NO tiene API Resources, NO tiene `lang/`.

**P0 — Falla crítica de seguridad**
- NO hay `Policies/` — `RoleRequest::authorize()` chequea solo `hasAnyRole(['super-settings', 'settings', 'manager'])` sin tocar Spatie permissions. `RoleController` NO llama `$this->authorize()` en NINGÚN método. **Cualquier usuario con web/auth+settings middleware puede acceder a `index/show/edit/destroy/showPermissions/showModules/showUsers/duplicate/clone/bulkAction/etc`.** Crear `RolePolicy`, `PermissionPolicy`, `UserPermissionPolicy`.
- `PermissionController::store/update/destroy/bulkAction` con validación inline + sin autorizar.
- `UserPermissionController::update/sync` con validación inline + sin autorizar.
- `RoleController::updatePermissions` con doble path inline-validate.
- `RoleController::assignUsers/removeUser/bulkRemoveUsers/bulkAction/copyPermissionsFrom/compare/updateModules` validan inline.
- No hay protección contra escalación de privilegios — un manager (que pasa `RoleRequest::authorize`) podría asignarse `super-settings`.
- `PermissionController::store` acepta `guard_name: web|api` cuando proyecto solo usa `web`.

**P1**
- NO hay API controllers ni rutas API reales — `routes/api.php` carga rutas pero todas son web.
- `assignUsers` no autoriza assignment según jerarquía.
- `updatePermissions` toggle individual sin disparar `RoleUpdated`.
- NO hay `lang/`.
- `RoleController::clone` y `duplicate` casi idénticos — consolidar.
- NO hay factories para Role/Permission tests.
- `updatePermissions` matrix transaccional faltante — race conditions.
- Cache invalidation manual en listeners — verificar `UserRoleChanged`.
- `bulkAction` solo soporta `delete`.
- `PermissionHelper`/`HasRolesAndPermissions` Trait sin documentar.

**P2**
- `showPermissionMatrix` carga `Role::with('permissions')` + `permissions` separado + `Module::all()` — cachear.
- `updateModules` usa `Module::all()` pero `showModules` hardcodea — inconsistencia.
- Inline styles en 6 views (roles/index, users, matrix, show, permissions/index, modules).
- Events `RoleCreated/Updated/Deleted` no implementan `ShouldBroadcast`.
- `AppRoute` model parece no usado — confirmar y eliminar.
- `activity()->log(...)` en español inconsistente.
- Falta `RoleResource`/`PermissionResource` API resources.

**P3**
- `Tests/` con T mayúscula.
- 8 console commands — varias responsabilidades; validar uso de `FixMediaPermissions`.
- 4 archivos config (`role.php`, `permission.php`, `permissions.php`, `validation-permissions.php`) — confuso, consolidar.

#### Módulo: Captcha

**Estado actual.** 1 controller (`Settings/CaptchaSettingController`), 1 Form Request, 1 Policy, 3 services (`Captcha`, `CaptchaV3`, `MathCaptcha`), 1 Facade, 2 events, 1 Contract, 7 vistas, 3 lang files (es/en/pt), 4 archivos de tests con 48 test methods, 3 configs. NO tiene migrations, factories, seeders, API controllers, ni Resources.

**P0**
- NO existe permissions seeder. Crear `CaptchaPermissionsSeeder` con `captcha.settings.view`, `captcha.settings.update`.
- `CaptchaSettingPolicy::update` bypass por rol `admin`/`super-admin` — convención del proyecto usa `super-settings`.
- `CaptchaSettingRequest::authorize` bypass por rol duplica policy. Refactorizar a `Gate::allows('captcha.settings.update')`.
- NO `Gate::policy(Setting::class, CaptchaSettingPolicy::class)` — provider hace `Gate::define` directo ignorando Policy class.
- Nombres de ruta hack: `->name('captcha.settings')` + `->name('.update')` que se concatena.

**P1**
- No hay rutas API.
- No hay middleware `auth.deny-impersonating` ni `password.confirm` en update.
- `CaptchaSettingController::update` NO verifica `Gate::authorize` — solo confía en Form Request.
- `Captcha::verify` usa `Modules\Core\Services\CircuitBreaker` — documentar dependencia.
- `MathCaptcha::sessionTimeout=10` hardcoded — mover a config.
- Bug: `CaptchaSettingRequest::rules` usa `recaptcha_score` (line 35) pero view usa `captcha_score` — el campo no se valida.
- `Captcha::verify` retorna `true` cuando captcha disabled — auditar.
- `Captcha::verify` no rate-limita.

**P2**
- Inline `style="display:..."` en `settings/edit.blade.php:69`.
- `config/config.php` solo tiene `'name' => 'Captcha'` — escaso.
- DRY violation: `CaptchaServiceProvider::resolveSecret` duplica `CaptchaSettingController::resolveSecret`.
- Mensajes incompletos.
- Logging inconsistente (`logger()` global vs `Log::channel('security')`).

**P3**
- Tiene 3 lang files (es/en/pt) — buen ejemplo.
- README de 2K — escaso.
- Falta `routes/settings.php` separado.

#### Módulo: Notification

**Estado actual.** 4 controllers, 2 modelos, 4 services, 1 Policy (solo settings), 1 Job, 1 Notification, 1 Event, 1 Observer, 1 Trait, 2 console commands, 6 Form Requests, 4 vistas, 3 migraciones, 1 seeder, 7 archivos de tests con 51 test methods, 3 configs. NO tiene factories, NO tiene `lang/`, NO tiene API Resources.

**P0**
- `NotificationsController` web NO usa Form Requests para `markAsRead/markAllAsRead/destroy/destroyAll` — accede a `$id` sin validación.
- `Api/NotificationController::index` usa `Illuminate\Routing\Controller` en lugar del base del proyecto.
- API routes bajo `web` middleware (`['web', 'auth:web']` para `prefix('api/notifications')`) — convención exige `['api', 'auth:sanctum']`.
- `Settings/NotificationSettingsController::update` ejecuta `Artisan::call('config:clear')` en runtime — anti-pattern grave.
- NO hay Policy para `Notification` model individual.
- `BulkActionRequest::authorize` retorna `true` sin chequeo.
- Validación inline en `Api/NotificationController::index`.

**P1**
- API mal nombradas — `api/notifications/preferences` con throttle `60,1` confuso.
- NO hay `NotificationResource` API resource.
- `NotificationsController::markAsRead` redirige a `action_url` con `str_starts_with(config('app.url'))` — bug si dominios cambian.
- `NotificationServiceProvider::loadNotificationSettings` con try/catch silencioso.
- `configureWebSocketSettings` mergea config de `Setting::query()->first()` — depende de un singleton no garantizado.
- `registerNotificationTypes` hardcodea 13 types — debería ser auto-discovery por módulo.
- NO hay `lang/`.
- Faltan factories.
- `SendNotificationDigestJob` solo diario — falta weekly/monthly configurable.
- `NotificationService::sendToUser` swallow exceptions per-channel.
- `SmsService` y `PushNotificationService` no documentados.
- `NotificationPermissionsSeeder` solo asigna a `super-settings`.
- `UpdateSettingsRequest::rules` requiere TODOS booleans/integers — sin `sometimes`.
- `destroyAll` sin password confirmation.

**P2**
- `bulkDestroy` no informa cuántos fueron borrados.
- Inline styles en `components/notifications.blade.php`, `preferences/index.blade.php`, `managers/notifications/index.blade.php`.
- `HasNotificationSystem` trait debería ser auto-aplicado.
- `Notification::observe` se llama sin verificar duplicados.
- `registerPolicies()` define gates inline en lugar de `Gate::policy(...)`.

**P3**
- `config/notification.php` con env-driven keys — bien.
- README 6.4K bien documentado.
- Falta vista admin para enviar notificación broadcast manual.

### Public Content

#### Módulo: Blog

**Estado actual.** ~85 archivos PHP. 9 controllers (Web admin + Public + API), 8 modelos (BlogPost, BlogCategory, BlogTag, BlogPostComment, BlogPostVersion, BlogPostTranslation, BlogTranslationLog, BlogGlossaryTerm), 4 enums, 4 policies, 4 services, 3 jobs (PublishScheduled, Translate, SendNewsletter), 4 events + 4 listeners, 4 notifications, 1 observer, 7 Form Requests, 3 API Resources. 14 tests Feature. Multi-idioma con tabla translations, versionado, comentarios moderables, RSS feed, integración Schema.org+SEO. composer.json todavía dice "nwidart/blog" (placeholder).

**P0**
- `StoreBlogPostRequest::authorize()` retorna `true` SIN check de permiso. Bypass total. Replicar al resto de Form Requests Blog.
- API endpoints sin autenticación ni rate-limit declarado en `routes/api.php` (`/blog/posts`, `/blog/posts/{slug}` etc.). Cualquiera puede scrappear.
- `BlogPostController::bulkAction` ejecuta `BlogPost::whereIn('id', $ids)->update(...)` sin check de policy por modelo. Cualquier user con `blog.post.publish` puede publicar/borrar posts ajenos.
- Web routes sin grupo `web` middleware: usa `Route::middleware(['auth'])` solo.
- `BlogPostController::ajaxSlug` sin authorize.
- Comentarios públicos sin sanitización de HTML real (depende de helper `clean_html` opcional).
- `BlogPublicController::search` no rate-limita query LIKE costosa.
- Cache key colisión multi-locale: `Cache::remember('blog.public.index.featured', ...)` no incluye locale.

**P1**
- Sin SitemapIntegration en BlogPost — no usa trait `HasSitemapItems`.
- Convención de permisos `blog.post.view` (3 segmentos) inconsistente con `{alias}.action`.
- `StoreBlogPostRequest` sin `messages()` ni `attributes()` en español.
- `BlogSettingsController::update` valida inline.
- `BlogCommentAdminController::bulkAction` valida inline con sintaxis pipe.
- `incrementViewCount()` actualiza modelo en cada request → write masivo. Mover a job afterResponse().
- N+1 potencial en `BlogPostController::index` con `whereHas` complejo.
- `BlogPostPolicy` sin métodos coherentes para Tag/Category — `BlogTagController::bulkAction` solo autoriza `create` aunque puede borrar.
- Sin `BlogPolicy` para settings — usa string Gate.
- Falta `Route::middleware('throttle:30,1')` en feed RSS.

**P2**
- Sin tests para policies — `BlogPoliciesTest` no cubre Category/Tag/Comment completas.
- API Resources incompletos: `BlogPostResource` no expone `seo_title/seo_description/keywords`, ni reading_time.
- Sin endpoint API para comentarios públicos.
- `composer.json` con metadata placeholder ("Nicolas Widart").
- `BlogServiceProvider::registerEventListeners()` registra manual — usar EventServiceProvider separado.
- `BlogPostController::versionDiff` strip_tags pierde formato — usar diff por palabras.
- `relatedPosts()` en model: lógica de negocio en modelo, mover a service.
- `BlogTagController::ajaxSlug` y `BlogCategoryController::ajaxSlug` duplican `generateSlug` — extraer a trait.
- Newsletter solo si `send_newsletter=true`, no UI para reenviar fallidos.
- Faltan API resources para comentarios y revisiones.

**P3**
- README sin diagrama del flujo de traducciones DeepL.
- lang/ solo tiene `es/messages.php` — falta en/pt.
- `config/config.php` no documenta `BLOG_SUPPORTED_LOCALES` en `.env.example`.
- Avatar URL hardcoded a ui-avatars.com.
- Falta soft-delete en `BlogTag` y `BlogCategory`.

#### Módulo: Faqs

**Estado actual.** Módulo recién creado y limpio (~14 archivos PHP). 4 controllers, 4 modelos (Faq, FaqCategory, FaqTranslation, FaqCategoryTranslation), 1 enum, 4 Form Requests, 2 factories, 1 permissions seeder, 8 vistas, 1 archivo lang ES, 4 migraciones, tests básicos. Soporta multi-idioma y shortcode `[faqs]`.

**P0**
- NO hay Policy — controllers usan `$this->authorize('faqs.view')` directo a Gate string. Crear `FaqPolicy` y `FaqCategoryPolicy`.
- Verificar `UpdateFaqRequest` y `UpdateFaqCategoryRequest` checkean Spatie permission.
- Sin `DB::transaction` en store/update — `FaqController::store` crea Faq + sincroniza traducciones SIN transacción.
- Permisos faltantes para settings: no hay `faqs.settings.view`/`update`. `FaqSettingsController` no autoriza.
- Sin layer de servicios — sync de traducciones duplicada en controllers.

**P1**
- Sin Schema.org FAQPage markup — pierde Rich Snippets.
- Sin SEO meta tags en `/faqs` pública.
- Sin slug ni URL única por FAQ — no se puede deep-link.
- No hay bulk actions en index.
- Falta cache en endpoint público.
- Sin soft-deletes.
- Sin Activity Log.
- Sin breadcrumbs Schema.org.
- Sin endpoint API público.
- Sin search en FAQ pública.

**P2**
- Modal de delete usa `confirm()` JS nativo — convención exige modal `modal-dialog-centered`.
- Métodos `getActiveLocales`/`getDefaultLocale` duplicados — extraer a trait `HandlesLocales`.
- `FaqStatus` enum con keys UPPER_CASE — convención sugiere TitleCase.
- Métodos `trans()` duplicados — extraer a trait `HasTranslations`.
- Faltan factory states.
- `FaqsDatabaseSeeder` solo llama PermissionsSeeder — sin demo.
- Sin tests para FaqSettingsController ni shortcode.
- `FaqsServiceProvider::registerShortcodes` define el shortcode dentro del provider (anti-pattern).

**P3**
- README ausente.
- Solo lang ES.
- Vista pública usa `nl2br(e($faq->trans('answer')))` — limita formato.
- No hay drag&drop reordering del campo `order`.
- Vista index sin filtro modal.

#### Módulo: Page

**Estado actual.** Módulo gigante (~145 archivos PHP). 22 controllers (web + 3 API), 16 modelos, 1 policy, 11 services, 7 jobs, 8 events, 3 listeners, 2 notifications, 1 observer, 1 mailable, 13 console commands, 2 Form Requests, 3 Resources, 2 middlewares, 31 migraciones, 7 factories, 14 tests Feature + 2 Unit. Tiene VisualEditor con autosave/locks, versionado completo, sistema de aprobaciones, webhooks salientes, analytics propio, cache multi-capa, sitemap+robots, multi-idioma, integración SEO/Schema.org. Composer.json con namespace **lower-case `modules\\Page`** (incumple PSR-4 convencional).

**P0**
- `composer.json` namespace `modules\\Page` lowercase — quebrará en producción Linux. Cambiar a `Modules\\Page` y `Modules\\Page\\Database\\Factories`. Mismo problema en `module.json`.
- Catchall route `/{path}` con regex blacklist frágil — añadir nueva sección requiere recordar editar regex.
- `PageController::analytics` autoriza con `update` cuando debería ser `view-analytics`.
- `bulkAction` autoriza `'publish', 'unpublish' => 'update'` — saltarse permiso `page.publish` específico.
- `PublicController::trackView` dispatch closure-based job — closures pueden romper con event:cache.
- API routes (`/v1/pages/*`) y web routes mezclan named routes con `settings.pages.publish/unpublish`.
- `PageController::ajaxSlug` sin authorize.
- Catchall regex bloquea `pages` pero permite `helpdesk` excluido sin justificar.

**P1**
- `Page` model usa `protected $casts = []` propiedad — convención Laravel 12 manda `casts()` method.
- `PagePolicy` único cubre todo — separar al menos `PageWebhookPolicy`, `PageCategoryPolicy`, `PageVersionPolicy`.
- Webhooks salientes sin Idempotency-Key — payloads pueden duplicarse.
- `Page` no tiene Activity Log Spatie.
- `PageController::store/update/destroy` con try/catch genérico que oculta errores.
- `PageWebhookController::test` permite testing 10/min pero no verifica URL nuevamente.
- `SitemapController` tiene `Modules\Blog\Models\BlogPost::class` hard-coupled.
- `CmsPermissionsSeeder` mezcla permisos de Page+Menu+Seo+Sitemap+Shortcode — separar.
- `PublicController::resolveHierarchicalPage` con `parent.parent.parent.parent` eager — limita 4 niveles.
- Sitemap NO incluye `lastmod` por idioma correctamente cuando solo hay 1 traducción.
- `PublicController::detectAndRedirectLocale` no protege contra spoofing del header.
- `PublicController::buildPageData` renderiza `$transContent` con `{!! !!}` — riesgo XSS.
- No hay tests para `RobotsController`, `SitemapController`, `PreviewController`.
- API route prefix es `v1` sin `pages` — convención manda `api/page/v1/...`.

**P2**
- Documentación excesiva: README.md + INSTALLATION.md + CACHE_SYSTEM.md + COMMANDS.md + SUMMARY.md + VERSIONING.md + CHECKLIST.md (7 .md). Consolidar.
- No hay `lang/` directory — `loadTranslationsFrom` apunta a directorio inexistente.
- `PageWebhookController::test` no implementa retry/timeout configurable.
- `PageObserver` y `PageServiceProvider` registran lógica diferente sin documentar.
- VisualEditor genera 30+ rutas con throttle individual — encapsular.
- Sin ratelimit estricto en `pages.search` GET.
- Múltiples comandos de cache duplican (`PageCacheClearCommand`, `WarmPageCacheCommand`, `PageCacheWarmCommand`).

**P3**
- Falta API Resource para Webhook, Approval, Version.
- `HomePageSeeder`, `PageCategorySeeder`, `PageTagSeeder` no llamados en `PageDatabaseSeeder`.
- `tests/VersioningTest.php` en root del directorio.
- `vite.config.js` y `package.json` propios — válido pero documentar.

#### Módulo: Reviews

**Estado actual.** Módulo enorme y maduro (~200+ archivos PHP). 25 controllers (web admin + Settings/* + Api/* + Public + Webhooks), 19 modelos, 7 policies, 26 services (Google API, AI, sentiment, anomaly detection, auto-reply, badges), 10 jobs, 9 events + 7 listeners, 11 notifications, 3 mailables, 17 console commands, 12+ Form Requests, 7 API Resources, 3 middlewares, 4 custom validation Rules, 2 platforms (Google), 39 migraciones, 7 factories, 5 seeders, 33+ tests Feature + Unit. OAuth Google, AI replies, competidores tracking, GDPR export, widget público, badges SVG, sentiment, anomaly detection, webhooks, reportes mensuales, SLA, multi-language reply templates.

**P0**
- API routes en `routes/api.php` no agrupadas por `auth:sanctum` parejo — webhook routes públicas (correcto), pero auth solo cubre `index/stats/show/suggestions`.
- `ReviewController::index` ejecuta query MySQL-specific con `selectRaw('SUM(CASE star_rating WHEN \'FIVE\'...)')` — no portable y costoso.
- `Review` modelo tiene `$appends = ['location_name', 'reply_status', 'is_visible']` — `reply_status` ejecuta query si no hay relación cargada → N+1 garantizado.
- Routes web: 8 grupos separados de `Route::middleware(['web', 'auth'])->prefix('panel/reviews')` — explosión de boilerplate.
- `PublicReviewController::cardsJson/dataJson` con `throttle:60,1` público — verificar que no devuelvan PII.
- Webhook handler `VerifyGoogleWebhookSignature` middleware no testeado.
- `ReviewController` constructor llama `$this->authorizeResource(Review::class, 'review')` — pero `data`, `tagsList`, `export`, `bulkModerate` no son métodos REST.
- Outbound webhook closures registrados directamente en ServiceProvider — pueden romper en `event:cache`.
- `reviews:api` rate limiter usa `hasAnyRole(['super-admin', ...])` — guest path sin fallback robusto.

**P1**
- 3 archivos MD de docs en raíz (CODE_REVIEW.md, SECURITY_AUDIT.md, ~13 archivos MD!) — mover a `docs/`.
- Routes file de 245 líneas con 8 grupos — refactorizar en sub-archivos.
- `Review::scopeRating` y otros sin return type.
- Falta tests para `Settings/AiSettingsController`, `Settings/NotificationPreferenceController`.
- `config/permissions.php` duplicado con `ReviewsPermissionsSeeder`.
- `ReviewsPermissionsSeeder::run` define manager TWICE (copy-paste bug).
- Convención de permisos con 3 segmentos `reviews.reviews.view` innecesario.
- `Review::booted()` flushes Cache::tags en cada created/updated/deleted — costoso. Async.
- No hay `published()` scope (solo `visible()`) — semántica confusa.
- `google_reply_text` en Review duplica info de `replies` table — desnormalización.
- `GoogleConnectionController` mezcla `Route::resource()` con custom.
- Falta API auth y throttle por usuario en widget endpoints.

**P2**
- Múltiples controllers reciben `Request $request` y validan inline — al menos `ReviewController::generateAiReply`, `ReviewController::data`.
- Tests Feature en `tests/Feature/E2E/` — convención prefiere Feature plain.
- `ReviewSettingsController::update` con `Route::match(['PUT', 'PATCH', 'POST'])` — múltiples verbs.
- No hay Activity Log en `ReviewReply`.
- `composer.json` requiere `google/apiclient: ^2.15` — verificar locked.
- `Review::getActivitylogOptions` solo logea `star_rating, comment` — review puede ser editada en `reply`.
- Shortcodes registered inline en ServiceProvider (~150 líneas) — extraer.
- `OutboundWebhookService::dispatch` llamado en closures inline — listener class.
- `tests/Unit/` con pocos tests — expandir cobertura unitaria de SentimentAnalysisService, AnomalyDetectionService.
- Falta tests para `ReviewWebhookController` y `Settings/GoogleConnectionController::callback` (OAuth flow).
- Sin tests para AiReplyService — crítico mockear LLM provider.
- Routes API expone solo `index/stats/show/suggestions` — falta paridad para `moderate`, `reply`, `update`.

**P3**
- README.md gigante (16KB) — split en `docs/`.
- lang/ tiene es y en — falta pt.
- `module.json::alias` está como `Reviews` (capitalizado) mientras el resto del módulo usa `reviews` (lowercase).
- Múltiples enums con buena estructura — bien.
- `tests/TestCase.php` revisar que extiende `Tests\TestCase` y configura factories.
- Spatie/Laravel-medialibrary no usado aunque hay `reviewer_photo_url` raw.
- Documentación de outbound webhooks (Zapier integration) no clara.

#### Módulo: SimpleSlider

**Estado actual.** Módulo placeholder mínimo (~3 archivos PHP). 1 controller (`SimpleSliderSettingsController`), 0 modelos, 0 migraciones, 0 seeders, 0 factories, 0 tests, 0 Form Requests, 0 policies, 0 servicios, 0 enums, 0 routes API, 1 vista de settings, 1 archivo lang ES, 1 ruta web. Solo persiste un toggle `use_default_config`.

**P0**
- `SimpleSliderSettingsController::update` sin authorize — cualquier autenticado modifica el setting.
- Sin Form Request — valida nada.
- Sin permisos definidos — no hay `SimpleSliderPermissionsSeeder`.
- Vista hardcodea rutas a `/vendor/core/plugins/simple-slider/...` sin validación.
- `SimpleSliderServiceProvider::boot()` no chequea `Module::isDisabled()` antes de algunos pasos.

**P1**
- Módulo no aporta funcionalidad real — solo un checkbox. Si la idea es Owl Carousel, falta CRUD completo, modelo Slider, SlideItem, vista pública, shortcode `[slider]`.
- NavService solo agrega 1 ítem en settings.
- Sin README — ¿qué hace este módulo? ¿es legacy?
- No hay tests.
- No registrado en `bootstrap/providers.php` (verificar).

**P2**
- `composer.json` ausente.
- `config/config.php` solo tiene `'name' => 'SimpleSlider'`.
- Vista usa `<br>` para separar paths.
- Naming inconsistente: ítem apunta a `settings.simple-sliders.index` (con guión) mientras módulo se llama `simple_slider` (snake).
- Lang solo ES.

**P3**
- `module.json::priority: 0` — bajo, OK.
- `Setting::clearPrefixCache` invalida cache correctamente.

**Recomendación:** Decidir si expandir a CRUD completo o eliminar (consolidar en Theme/Settings).

### Marketing/Forms

#### Módulo: Ads

**Estado actual.** 2 controllers (admin + click), 3 models (Ads, AdsClick, AdsTranslation), 2 enums, 2 Form Requests, 2 tests (1 feature, 1 unit), 3 migrations, 1 factory, 2 seeders. Multi-idioma del campo `name`, scopes `published`/`byLocation`, contador clicks. Sin servicios, policies, API.

**P0**
- Sin Policy `AdsPolicy`. Controller usa `$this->authorize('ads.view')` (string permission). Crear policy con `viewAny/view/create/update/delete/manage`.
- Sin SoftDeletes en `Ads`. Eliminación accidental irrecuperable.
- Endpoint público de click sin throttle ni anti-bot: `Route::get('/ad/click/{key}', ...)` sin throttle, sin Referer check.
- AdsClick almacena IP+UserAgent sin GDPR notice ni hash. Hashear IP o pipeline de retención.
- Falta tracking de impressions — solo clicks. Sin CTR métrica fundamental.

**P1**
- Convención de permisos no estándar: `ads.view` vs Forms/Newsletter (`Forms.forms.index`).
- No hay servicio (`AdsService`) — `syncTranslations` y manejo de imágenes en controller.
- Inputs de imagen son strings, no upload real (`<input type="text">` esperando rutas).
- Sin API endpoints (no `routes/api.php` ni `Http/Controllers/Api/`).
- Sin bulk actions en index.
- Tabla `ad_translations` incompleta — solo `name`. Falta URL/imágenes por idioma.
- Sin lang `en` — solo `es`.
- Test de StoreAdsRequest no cubre validación negativa (key duplicada, translations vacío, status inválido, expired_at en pasado, URL malformada, 403 user sin permisos).

**P2**
- Inline styles en `index.blade.php:71` y `partials/ad-display.blade.php:13,36`.
- Eager loading faltante en `index` — no carga `translations` ni `clicks`.
- `partials/ad-display.blade.php` consulta DB sin cache en cada render.
- Falta filtro por tipo (image vs google_adsense).
- `StoreAdsRequest::messages()` faltan `attributes()`.
- Endpoint click no respeta `expired_at`.
- Sin dashboard de estadísticas (CTR, top ads).
- README ausente.

**P3**
- `key` debería validar formato kebab-case (`/^[a-z0-9-]+$/`).
- Modelo `Ads` con nombre singular en plural confuso — debería ser `Ad`.
- `translations()` apunta a `ad_id` pero modelo es `Ads` (FK inferida `ads_id`).
- Faltan eventos `AdCreated`, `AdClicked`, `AdExpired`.
- Activity log no implementado.

#### Módulo: Forms

**Estado actual.** Módulo grande y maduro (~16 controllers, 14 models, 33+ Form Requests, 3 API Resources, 8 services, 5 Jobs, 2 Notifications, 2 Events, 1 Policy, 7 Console Commands, 5 factories, 3 seeders, 27 migraciones, 50+ vistas, 11 tests). Honeypot, captcha, time-based spam check, rate limiting combinado, multi-step, versionado, tokens de acceso, abandono tracking, follow-ups, GDPR retention, webhooks, conditional emails.

**P0**
- `FormController::importJson` con toda la lógica en controller (70 líneas, líneas 291-363). Mover a `FormService::importFromJson()`.
- `FormController::index` usa `selectRaw` con SUM(CASE WHEN ...) sin límites — lento con 100k+ submissions.
- `FormSubmission` no tiene Policy — todos usan `$user->can('Forms.submissions.delete')` strings.
- Mass assignment vulnerability potencial en `Form::booted` — race condition con flush old/new cache si dos editores tocan mismo form.
- `previewPublic` con HMAC token sin expiración — link nunca expira.
- `FormPublicController::submit` log con datos sensibles (IP, partial submissions).

**P1**
- `FormApiController` con rutas internas en `routes/web.php` solo `web` + `auth` — si es API real, sanctum.
- `api.php` solo tiene 5 rutas — `picker`, `meta`, `quickStore` están en web.php.
- `FormSubmissionResource`/`FormFieldResource` existen pero verificar uso consistente.
- `FormPolicy::view` no verifica ownership (si Form tuviera `created_by`).
- N+1 potencial en `FormController::index` — `$form->getSubmissionsCount()`/`getUnreadCount()` cachean por form.
- Validación inline en `FormApiController::previewHtml`. Crear `PreviewHtmlRequest`.
- Faltan tests para `BulkActionFormRequest`, `BulkAnonymizeRequest`, `BulkDeleteSubmissionsRequest`, `UpdateFormProtectionRequest`.
- `FormController::clone` falla silenciosamente con 500 — diferenciar tipos de error.
- `Form` model: scopes sin return type.
- `FormSubmission` no tiene activity log.

**P2**
- Inline styles en 8+ archivos: `analytics.blade.php`, `field-types/edit.blade.php`, `submissions/show.blade.php`, `templates-library/index.blade.php`, `follow-ups/index.blade.php`. (PDF se acepta inline).
- `FormController::index` filtra `where('is_active', $request->status === 'active')` siempre — bug menor.
- `FormController::store` autoriza con `$this->authorize('Forms.forms.create')` (string) en lugar de policy.
- `FormPolicy` no implementa `restore()` y `forceDelete()` aunque `Form` usa SoftDeletes.
- `FormsServiceProvider::registerPolicies` solo registra `Form` — faltan `FormSubmission`, `FormCategory`, `FormFieldTypeSetting`, `FormAccessToken`.
- `FormFollowUpController`, `FormVersionController` no auditados — auditar consistencia.
- `FormCategory` sin índice `(is_active, sort_order)`.
- No hay endpoint para descargar todos los archivos de submission en zip.
- `FormPageCacheInvalidator` no se invoca en `FormField` cambios.

**P3**
- Documentación de plantillas en `config/forms.template_library` debería estar en docs.
- `FormApiController::stats` retorna campos snake_case en JSON — convención manda camelCase.
- `preview-html` route duplicada en `routes/api.php` con throttle y en `routes/web.php` sin throttle.
- Falta OpenAPI/Scribe annotations.
- Logs sin contexto de usuario.

#### Módulo: Attention

**Estado actual.** Módulo gigante (PQRSF/peticiones colombiano): 13 controllers (incluyendo `AttentionController` API con ~2000 líneas), 14 models, 5 Events, 5 Listeners, 9 Mailables, 3 Notifications, 1 Policy completa, 7 services, 3 Jobs, 4 Console Commands, 8 Form Requests, 7 factories, 11 seeders, 26 migraciones, 12 tests. Soporta SLA, business days, festivos colombianos, routing rules, satisfaction surveys, multi-canal email.

**P0**
- `AttentionController` (API) tiene >2000 líneas — God class. Refactor en 6+ controllers separados.
- Inline `validate()` en 7 endpoints (líneas 428, 482, 548, 679, 739, 946, 1430). Crear `AssignDepartmentRequest`, `AssignUserRequest`, `ChangeStatusRequest`, etc.
- `AttentionController::bulkDelete` chequea `$user->hasRole('super-settings')` directo en controller.
- `AttentionController::export` tiene TODO sin implementar (línea 1838) — endpoint retorna 202 con `download_url` pero job nunca corre. Existe `ExportAttentionsJob` — conectarlo.
- **`AttentionPolicy::update` compara `$attention->status === 'closed'` (string) — el cast es a `AttentionStatus` enum. Comparar con `=== AttentionStatus::CLOSED`. SI el cast funciona, ESTOS COMPARES SIEMPRE FALLAN.** Bug de seguridad real. Mismo problema en `delete`, `manage`, `assign`, `changeStatus`, `resolve`, `close`.
- Email service público crea action sin captcha por defecto — verificar config `attention.captcha.enabled` en producción.
- `AttentionPublicController::submit` no maneja honeypot (a diferencia del módulo Forms). Solo `throttle:10,1`.

**P1**
- `AttentionController::dashboardStats`, `statsByType`, `statsByStatus` deberían estar en `AttentionStatisticsService`.
- `AttentionController::dashboardStats` cuenta TODA la tabla cada llamada (sin filtros de fecha).
- `AttentionController::index` retorna `$attentions->items()` exponiendo TODA la fila.
- Faltan API Resources: `AttentionResource`, `AttentionShowResource`, `AttentionListResource`, `AttentionStatsResource`.
- `AttentionPolicy` 411 líneas con duplicaciones — extraer `belongsToAttention()`.
- Permissions seeder usa `attention.action` (no `Attention.attention.action`) — inconsistente con Forms/Newsletter.
- No hay tests para SLA breaches.
- Faltan API Resources, sin OpenAPI.
- `AttentionController::submit` API pública sin captcha — agujero anti-spam.
- Factories modificados sin verificar consistencia con migraciones nuevas (soft deletes).

**P2**
- Inline styles en `settings/types/{index,create,edit}.blade.php`, `settings/sla-policies/*.blade.php`, `dashboard/partials/_kpi-cards.blade.php`.
- `AttentionController::update` compara `$value != $attention->$key` con `!=` (loose) — usar `!==`.
- `AttentionController::stats` y `dashboardStats` usan `DB::table` cuando existe modelo.
- `AttentionPublicController::form` retorna view `template::views.peticiones.form` (acoplamiento Theme/Template).
- `AttentionController::bulkAssign/bulkClose/bulkDelete` con try/catch GIGANTE por iteración — usar Job.
- `AttentionController::dashboardStats` calcula `slaComplianceRate` con N+1.
- `AttentionEmailController` no auditado pero múltiples rutas (sendCustom, sendConfirmation, etc.).
- `AttentionPolicy::isSuperAdmin` chequea `super-settings` y `Super Admin` — inconsistencia.
- 4 archivos de docs de tests (`tests/CHECKLIST.md`, `tests/EXAMPLES.md`, etc.) — consolidar.
- `SendCustomEmailRequest` no auditado — agregar HTMLPurifier si permite HTML.

**P3**
- `AttentionController` tiene métodos `@deprecated` (`sendConfirmation`, `sendResolution`) accesibles.
- Comentarios mezclan inglés/español inconsistente.
- `tracking_url` debería ser accessor del modelo.
- `scripts/verify-installation.php` debería ser Artisan command.
- Falta dashboard real con DevExpress charts.
- Activity log no implementado a pesar de tabla `attention_actions`.

#### Módulo: Newsletter

**Estado actual.** Módulo simple: 5 controllers (Settings + Subscriber admin + Public Subscribe + Public Unsubscribe + Popup), 1 model (Subscriber), 1 enum (SubscriberStatus), 2 Form Requests, 2 Mailables, 3 services (MailjetService, NewsletterEmailService, SubscriberService), 1 migración, 1 factory, 2 seeders, 4 vistas. Integración Mailjet, captcha condicional, popup AJAX.

**P0**
- `tests/` directory NO existe — CERO tests.
- `PublicSubscribeController::store` traga `QueryException` y devuelve 'Ya estás suscrito' para CUALQUIER QueryException — DB caída se reporta como "ya suscrito".
- `SubscriberService::subscribe` SIEMPRE envía email + add a Mailjet sin verificar `wasRecentlyCreated` — usuario haciendo POST 5 veces recibe 5 emails y 5 calls a Mailjet API.
- Sin double opt-in — subscribers se marcan `subscribed` directamente. Ilegal en GDPR (UE).
- Email del admin notification puede revelar datos sin protección.
- Convención permisos `Newsletter.subscribers.index` distinta del resto. `SubscriberController` admin **NO usa `$this->authorize()` en NINGUNA action**.
- `PublicUnsubscribeController` no usa Form Request — `$request->validate()` inline.
- Sin Policy para Subscriber.

**P1**
- Sin tabla `lang/`. Strings usan `__('newsletter_popup_subtitle')` sin namespace.
- `SubscriberFactory::definition` setea `status` aleatorio (0/1) — tests intermitentes.
- Inconsistencia: migration `tinyInteger` (0/1) vs enum string (`Subscribed`/`Unsubscribed`).
- Sin SoftDeletes en Subscriber.
- `SubscriberService::bulkDelete/Unsubscribe` no envía notificación a admin.
- `MailjetService::addContact` solo POST, no actualiza si existe. No guarda `mailjet_id` (columna inutilizada).
- `MailjetService` NO usa Job (síncrono).
- `bulkAction` admin con `$request->validate()` inline.
- Ruta `newsletter.unsubscribe` sin link tokenizado — usuario debe escribir email.
- Sin tracking de email open/click.

**P2**
- Inline styles en `subscribers/index.blade.php`.
- `Subscriber` model con return types en scopes — bien.
- `PublicUnsubscribeController` valida captcha 2 veces.
- `PublicUnsubscribeController::store` retorna 422 si subscriber no existe — debería ser 404 silencioso (privacy).
- `SubscriberController::index` cachea stats 300s sin invalidar — admin elimina pero stats viejos.
- No hay paginación configurable (`paginate(25)` hardcoded).
- `PopupController::show` retorna HTML en JSON.
- `registerComponents` registra components sin verificar.
- `NewsletterServiceProvider::boot` no chequea `Module::isDisabled()`.
- `config/config.php` solo `'name'` — sin defaults.
- Faltan permission checks en rutas settings.

**P3**
- `MailjetService::removeContact` se llama? buscar referencias.
- `SubscriberService::exportCsv` no respeta filtros aplicados.
- Translation keys con `_` en lugar de namespace `newsletter::`.
- No hay queue para `NewSubscriberAdminMail`.
- No hay segmentación de subscribers (tags, intereses, geolocalización).
- README de 1KB insuficiente.

### Helpdesk family

#### Módulo: Helpdesk (core)

**Estado actual.** Módulo "core/base" del ecosistema (priority 0). ~25 modelos (Conversation, Customer, Group, ConversationStatus, ConversationTag, ConversationItem, AgentShift/Vacation, OncallRotation, HelpCenter*, Webhook, AgentSettings, Setting), ~25 controllers, ~70 migraciones, 7 seeders, 6 factories, 3 policies, 4 jobs, 8 events + 6 listeners, 11 servicios, 5 tests Feature solamente. Chat omnicanal (widget + WhatsApp + Messenger + Instagram + email), conversaciones, customers, help center, webhooks salientes, equipos/turnos.

**P0**
- Validación inline en controllers críticos: `ConversationsController::store/update/storeMessage`, `Settings/SettingsController` (~150 líneas validate), `Settings/StatusesController`, `Settings/TagsController`, `CustomersController::store/update`. Crear FormRequests.
- Permission convention divergente: `ConversationsController` usa `authorize('manager.helpdesk.conversations.update')` (route names) mientras `CustomersController` usa `authorize('viewAny', Customer::class)` (policy).
- API key de Anthropic/OpenAI/Gemini en cache + DB en `ai.php` SIN encriptar.
- AI Settings duplicado entre Helpdesk core y HelpdeskAgents.
- Cobertura tests CRÍTICAMENTE baja: 5 Feature tests para 25 controllers.
- Routes con prefijos rotos: `routes/web.php` vacío, `RouteServiceProvider` carga con prefix `panel/helpdesk/settings` y name `helpdesk.backups.` (typo).
- Mass assignment: `Conversation` no incluye `assignee_id`, `status_id`, `is_archived` en `$fillable` pero controller los asigna directamente.

**P1**
- `HelpdeskServiceProvider::boot()` no registra rutas (delegado a RouteServiceProvider).
- NavService no se usa — Helpdesk core tiene `NavigationComposer` legacy.
- Sin Policy para ConversationStatus, ConversationTag, ConversationView, Group, Webhook, AgentShift, AgentVacation, Setting, HelpCenterCategory.
- Sin Form Request en API web controllers (solo 3 API requests).
- N+1 latente en `ConversationsController::show`.
- `SettingsController` mezcla cache + DB sin transactions.
- 2 vistas con `theme: 'bootstrap-5'` en select2 (conversations/edit:304, create:167).
- Inline styles abundantes (~447 ocurrencias en views entre los 4 módulos Helpdesk).
- Frontend React/Tailwind embebido (`resources/js/widget/*.tsx`) viola "No Livewire/Inertia/React".
- Cross-database connection compleja con `HasCrossDatabaseUserRelation` trait.
- Faltan factories: `ConversationStatus`, `ConversationView`, `Setting`, `Webhook`, etc.

**P2**
- `Managers/DashboardController` y `Reports/ReportsController` separados — consolidar.
- `ConversationsController::index` no usa Service.
- `AgentsController` actualiza usuarios desde Helpdesk — SoT poco clara.
- Bulk action solo para conversations.
- Routes legacy con HelpCenter usan GET para destroy/update.
- Sin búsqueda full-text en `Conversation::scopeSearch`.
- Email inbound (IMAP) y procesamiento webhooks compartidos — código en Helpdesk pero crea Tickets en HelpdeskTickets.
- Cache key collision risk: `helpdesk:default-status` y `helpdesk:conv-closed-status`.
- Falta lang/en para algunos módulos.
- Settings module no usa convención `panel/settings/{alias}`.

**P3**
- Comments en Spanish/English mezclados.
- No README.md.
- `getChannelInfoAttribute()` retorna emoji label vacío por defecto.
- `secret_key` random en `livechatIndex` defaults — `Str::random(40)` regenera cada request.
- Sin throttle middleware en routes principales.

#### Módulo: HelpdeskAgents

**Estado actual.** Módulo de agentes IA/LLM (priority 55). 8 modelos (AiAgent, AiAgentFlow, AiAgentFlowNode, AiAgentSession, AiAgentSessionMessage, AiAgentTag, AiAgentTool, AiAgentKnowledgeBase), 2 controllers, 2 policies, 1 servicio (PromptSanitizer), 1 servicio mayor (AiAgentFlowEngine), 1 job, 1 exception, 4 factories, 1 permissions seeder, 12 tests (5 Feature + 7 Unit). Vistas en `resources/views/managers/ai-agent/`. Config con rate limits LLM y patrones anti-prompt-injection.

**P0**
- `AiAgentSettingsController` violación masiva: 13 endpoints con validación inline. Crear `UpdateAiAgentSettingsRequest`, `Store/UpdateAiAgentTagRequest`, etc.
- `testConnection()` envía mensaje "test" a Anthropic consumiendo tokens reales — debería usar `/v1/models`.
- `AiAgent::first()` como singleton agent — asume un solo agente global.
- `tools.implementation` campo `string` sin sanitización — si se usa para `eval()`, RCE vector.
- No tests para `AiAgentSettingsController` — solo tests de model unit y policies.

**P1**
- Sin Service layer para tag/tool/knowledge — controller 632 líneas.
- `generateEmbedding()` solo "marca como procesado" — feature inacabada.
- `testOpenAIConnection`/`testAnthropicConnection`/`testGeminiConnection` exponen body de error en logs.
- `AiAgent::first() ?? new AiAgent` antipatrón — usar `firstOrCreate(['is_default' => true])`.
- Sin policy para AiAgentTool, AiAgentTag, AiAgentKnowledgeBase, AiAgentSession.
- Permissions seeder cubre solo `aiagents.*` — faltan tools/knowledge/tags/flows/sessions.
- Routes flow URLs `panel/helpdesk/ai/{flow}` colisión potencial con `Route::get('/create', ...)`.
- Sin lang/es para mensajes UI.
- Falta validación de `personality` (system prompt) contra `prompt_injection_patterns`.

**P2**
- Frontend React (`useAiAgent.ts`, `useFlowEditor.ts`) coexiste con jQuery.
- Settings AI duplicados con Helpdesk core.
- Sin bulk actions para tools/tags/knowledge.
- `statistics()` con query JOIN raw — extraer a `AiAgentStatsService`.
- No hay rate limiter en `flow.publish/archive/duplicate`.
- `backups` es nombre confuso para JSON column — renombrar a `model_settings`.
- Falta soft deletes en AiAgentSession y AiAgentSessionMessage.
- Sin event/listener para `AiAgentSessionStarted`, `AiAgentMessageSent`, `AiAgentToolInvoked`.

**P3**
- No README.md.
- Iconos string `'fa-duotone 🟢'` mezcla emoji con FA class.
- `models` lista hardcoded — usar `Cache::remember()` y consultar API.

#### Módulo: HelpdeskCampaigns

**Estado actual.** Módulo más simple (priority 60). 3 modelos (Campaign, CampaignTemplate, CampaignImpression), 1 controller (277 líneas), 1 policy, 2 Form Requests bien hechos, 3 factories, 1 permissions seeder, 2 tests (1 Feature, 1 Unit), 2 migraciones, 6 vistas. Campañas tipo Intercom (popup/banner/slide-in/full-screen) con CTR tracking.

**P0**
- `statistics()` endpoint público dentro de auth pero retorna `published_at` sin sanitizar fechas.
- `getConditionsDescriptionAttribute()` interpola sin escape — XSS si Blade usa `{!! !!}`.
- Sin tests para `publish/pause/resume/end/duplicate/statistics`.
- Falta `lang/en/helpdeskcampaigns.php`.
- Vista `campaigns/index.blade.php` usa `style="width: auto;"` inline.

**P1**
- No tracking endpoint para impressions/clicks — modelo `CampaignImpression` existe pero sin controller/route. Sin esto, métricas siempre 0.
- `destroy()` borra cache pero no impressions relacionadas.
- `duplicate()` sin DB::transaction.
- `publish/pause/resume/end` retornan `back()` sin JSON variant.
- Sin Service `CampaignService`.
- Sin bulk actions.
- Sin `CampaignTemplate` CRUD.
- Authorization en `index` usa `view` (debería `viewAny`).
- `getImpressionsCountAttribute` y `getConversionsCountAttribute` ejecutan COUNT() — N+1.

**P2**
- Sin migration para tabla `helpdesk_campaign_impressions` — vive en Helpdesk core.
- Sin tests Unit para CampaignTemplate ni CampaignImpression.
- Sin notification al finalizar campaña.
- Sin scheduled job para activar campañas con `published_at <= now()`.
- Vista cuenta `where('status', 'active')` en PHP en lugar de query.
- No filter por `created_by`.
- `condition` JSON sin schema validation.

**P3**
- No README.md.
- Mensajes éxito hardcoded en español.
- No CampaignActivityLog.

#### Módulo: HelpdeskTickets

**Estado actual.** Módulo más grande y complejo (priority 50). ~28 modelos, 25 controllers, 6 policies, 9 Mailables, 7 Jobs, 16 Events, 13 Listeners, 6 Notifications, 9 Services, 11 Form Requests, 6 Resources (incluyendo V1 versionada), 12 factories, 9 seeders, 19 Feature tests + 5 Unit tests = 24 tests.

**P0**
- **Permission convention totalmente rota en Form Requests**: `StoreTicketRequest::authorize()` usa `$this->user()->hasPermissionTo('create_tickets')` (snake_case con underscore) — pero el seeder crea `helpdesk.tickets.create` (dot-notation). Bug de autorización en `StoreTicketRequest`, `UpdateTicketRequest`, `StoreTicketsRequest`, `ReplaceTicketRequest`, `Update/StoreTicketCommentRequest`, `Update/StoreTicketNoteRequest`.
- Permissions semantic divergente en Controllers: `TicketsController::index` usa `authorize('manager.helpdesk.tickets.index')` (route name) pero seeder crea `helpdesk.tickets.view`.
- `TicketsController::store` con validación duplicada — `merge()`, `linkTicket`, `storeMessage`, `bulkReply`, `typing` con `$request->validate()` inline.
- `StoreTicketsRequest.php` y `StoreTicketRequest.php` ambos existen — duplicado.
- `TicketsController` 786 líneas y 25 métodos públicos — God controller.
- `merge()` ejecuta `$ticket->items()->update(['ticket_id' => $targetTicket->id])` directamente — pierde author original.
- `Ticket::getCategoryAttribute($value)` y `getStatusAttribute($value)` llaman `$this->load()` lazy en accessor — N+1 garantizado.
- **Tabler Icons en `TicketViewSeeder` (11 ocurrencias `ti ti-ticket`, `ti ti-user`, etc.) + `TicketCategory::getIconAttribute()` retorna `'ti ti-ticket'` por defecto**. Migrar a FA6.
- `storeMessage` despacha `broadcast()` Y `dispatch()` del mismo evento — duplicado.
- `agents/...` routes con middleware `role:helpdesk-agent|super-admin` — pero seeder no crea rol `helpdesk-agent`.
- `portal/...` routes con `web, throttle:60,1` SIN `auth` adicional sobre `/portal/login` — brute-force.

**P1**
- `TicketsController::index` permite `paginate(50)` sin límite máximo de `per_page`.
- `TicketsController::create` carga `Customer::orderBy('name')->limit(500)->get()` — performance bug si >500 customers.
- Mass-attachment storage sin escaneo virus a pesar de setting `enable_virus_scan`.
- `storeMessage` no usa Form Request.
- `TicketsController::merge` valida `'exists:helpdesk.helpdesk_tickets,id'` con prefijo DB hardcoded.
- `bulkReply` con `TicketItem::insert()` directo — bypass model events.
- `registerCommandSchedules()`: 5 comandos `everyMinute()` + 5 jobs — carga alta.
- `SlaService::pauseSla/resumeSla` sin tests directos.
- `Ticket` model con muchas responsabilidades — extraer a `TicketObserver`.
- `Ticket::booted()::updated` itera `getDirty()` y carga `TicketStatus::whereIn(...)` — N+1.
- `storeMessage` con `$item->load(['user'])` cross-DB — manejar null.
- `ticket_number` generación con `lockForUpdate()` y query LIKE — race condition.
- `exportPdf` con DomPDF dentro del request HTTP — bloqueante.
- No bulk action route `/tickets/bulk` documentada.
- `AutomationsController` validación inline `'json'` rule.

**P2**
- Settings (`Settings\TicketStatusesController/MacrosController/...`) validación inline.
- Faltan tests de portal (PortalLogin, magic link, PortalCustomerController).
- Falta test E2E de webhook → email-to-ticket → assignment → SLA → escalation flow.
- `TicketsController::smartReplies` sin throttle específico (LLM call costoso).
- `TicketsController::typing` sin throttle (DoS vector).
- `Helpdesk\Filters\TicketFilter` vive en Helpdesk pero usado solo aquí — mover.
- `Group` y `Customer` viven en Helpdesk core — documentar dependencia.
- `Resources\V1\TicketResource` — verificar API versioning, deprecar V1 si no.
- `registerMenus()` solo registra 3 items — faltan SLA Policies, Categorías, Estados, Macros, Automatizaciones.
- No event para `TicketDeleted`.
- `TicketAiService::smartReplySuggestions` sin cache.
- `bulkReply` sin authorize per ticket.
- Vistas con `style="z-index: 10;"` y otras inline styles.
- No keyboard shortcuts modal poblado.

**P3**
- No README.md.
- `prepareForValidation` en `StoreTicketRequest` mapea subject→title.
- 9 migrations el mismo día (2026-04-20) — consolidar.
- `TicketCategory::getIconAttribute()` retorna `'ti ti-ticket'`.
- `Models\Status` y `Models\TicketStatus` — duplicación posible.
- `SlaPolicy` y `TicketSlaPolicy` ambos modelos — consolidar.

#### Análisis cross-Helpdesk

**Redundancias y overlaps:**

1. **Conversation vs Ticket — duplicación arquitectónica masiva:** `Helpdesk\Models\Conversation` y `HelpdeskTickets\Models\Ticket` tienen 80% de overlap conceptual. Recomendación: consolidar en `helpdesk_threads` con `type='conversation|ticket'`, o extraer trait `HasMessageThread`.

2. **Settings AI duplicado:** `Helpdesk\Settings\SettingsController::aiUpdate` y `HelpdeskAgents\AiAgentSettingsController` ambos manejan provider/model/api_key. Decidir owner único (recomendado HelpdeskAgents).

3. **Permission convention inconsistente entre módulos:**
   - Helpdesk: `helpdesk.{entity}.{action}` ✓
   - HelpdeskTickets controllers: `manager.helpdesk.tickets.update` (route name) ✗ + Form Requests `create_tickets`/`edit_tickets` legacy ✗
   - HelpdeskAgents: `viewAny/create/...` via Policy ✓ + permissions `helpdesk.aiagents.{action}` ✓
   - HelpdeskCampaigns: `helpdesk.campaigns.{action}` ✓
   - **Estandarizar TODOS a `helpdesk.{entity}.{action}` y siempre vía Policy.**

4. **Cross-module imports fuertes:** `HelpdeskTickets` importa `Modules\Helpdesk\Models\{Customer, Group}` y `Modules\Helpdesk\Filters\TicketFilter` — TicketFilter debería vivir en HelpdeskTickets. `HelpdeskAgents` y `HelpdeskCampaigns` viven en migración compartida con `helpdesk_ai_agents`, `helpdesk_campaigns` que están en módulo Helpdesk core. Coupling de tablas.

5. **Routes prefix overlap:** Todos cargan rutas bajo `panel/helpdesk/...` — colisión potencial. `panel/helpdesk/ai` (HelpdeskAgents) vs `panel/helpdesk/settings/ai` (Helpdesk core).

6. **NavService::registerSidebar('helpdesk', ...)** en HelpdeskTickets/Agents/Campaigns — pero Helpdesk core NO usa NavService (usa NavigationComposer legacy).

7. **Connection mixing:** todos los modelos usan `protected $connection = 'helpdesk'` pero `App\Models\User` está en default — fuerza traits cross-DB.

8. **Tests cobertura desigual:** Helpdesk core 5 tests, HelpdeskTickets 24, HelpdeskAgents 12, HelpdeskCampaigns 2.

9. **Form Request adoption inconsistente** — HelpdeskCampaigns 2/2, HelpdeskTickets 11 pero authorize() legacy, HelpdeskAgents 0, Helpdesk core 3 (~94 inline validates entre los 4).

10. **UI rule violations transversales:** Tabler Icons (12 en HelpdeskTickets), inline styles (~447 ocurrencias), `theme: 'bootstrap-5'` en select2 (2), React en `Helpdesk/resources/js/`.

**Oportunidades de consolidación:**

- Considerar fusión HelpdeskAgents + HelpdeskCampaigns dentro de Helpdesk core.
- Crear `HelpdeskCore` con shared models (Customer, Group, ConversationStatus) y mover features a sub-módulos.
- Unificar Settings UI en `panel/helpdesk/settings` con tabs.
- Macros + Automations + Workflows — los tres son "rule engines". Considerar engine compartido.
- Notifications: crear `BaseHelpdeskNotification` con channels comunes.
- Reports: hay 3 ReportsController dispersos. Consolidar en uno con scopes.

### Mail

#### Módulo: Mailer

**Estado actual.** Módulo de plantillas email transaccionales y endpoints API HTTP. **5 controllers, 9 models con i18n, 4 services, 3 jobs, 4 commands, 5 policies, 11 migraciones, 2 form requests, 18 vistas Blade, 3 tests**. Maneja templates multi-idioma, layouts/componentes, variables, versionado, endpoints API con throttling, observers, webhooks Mailrelay (bounce/unsubscribe/complaint). Es el más maduro de los tres.

**P0**
- Falta cobertura de tests para 3 controllers de 5 — `MailerComponentController`, `MailerVariableController`, `MailrelayWebhookController` sin tests. Webhook es ENTRADA pública.
- Variables y Components con validación inline. Crear Form Requests dedicados.
- `MailerEndpointController::store/update` con `$request->validate()` inline. Crear `StoreMailerEndpointRequest`, `UpdateMailerEndpointRequest`.
- `MailrelayWebhookController` deshabilita CSRF y depende exclusivamente de un token estático en header — sin firma HMAC del payload, atacante con token puede falsificar bounces/unsubscribes.
- No hay factories para ninguno de los 9 modelos.
- Tests usan `Gate::before(fn () => true)` en TestCase — bypassa autorización.

**P1**
- Routes name no cumple convención: `mailers.*` (plural) vs convención `mailer.*` y prefix `panel/mailers/` vs `panel/mailer/`.
- No existe `routes/settings.php` separado.
- API públicas (`api/email-endpoints/{slug}/send`) no usan API Resources ni paginan.
- Falta `IncomingEmailSettings` policy referencia y permisos para webhooks.
- `SendEndpointEmailJob::mapVariables` con doble escape (htmlspecialchars + HTML purifier).
- Jobs sin `tries`/`backoff` parametrizables vía config.
- Bulk action solo en Templates — no en Components, Variables, Endpoints.
- `PurgeMailerLogsCommand` con schedule diario pero sin test de limpieza efectiva.
- Falta `routes/api.php` con prefijo `api/mailer`.
- Sin `resources/lang/`.

**P2**
- Permisos del seeder: usa `mailer.templates.view` (3 segmentos) inconsistente.
- Modelos no declaran return type en relaciones.
- `SendEndpointEmailJob` no implementa envío SMTP con failover.
- `MailerEndpointController::store` valida `lang_id` contra tabla `langs` (módulo Core).
- Vistas con inline styles (10 archivos).
- Falta plain-text alternativo robusto.
- No hay protección contra envío spam interno (60 emails/min indefinidamente).
- Settings policy no se registra como Gate completo.

**P3**
- README desactualizado — habla de `/manager/settings/mailers/` legacy.
- `paginationNumber()` helper sin documentar.
- Webhook acepta cualquier payload bien formado — no valida estructura completa.
- Faltan eventos de dominio.
- No hay endpoint `bulk-action` en Components/Variables/Endpoints.
- Console commands sin description.

#### Módulo: MailsSettings

**Estado actual.** Módulo de configuración email entrante/saliente. **3 controllers gigantes (~660 líneas IncomingEmailSettings), 0 models, 0 services, 0 form requests, 0 policies, 0 migrations, 0 factories, 0 seeders, 0 tests, 0 lang files, 0 jobs, 5 vistas Blade**. Wrapper sobre `Modules\Core\Models\Setting` para guardar config SMTP, IMAP, Gmail OAuth2, Mailgun, phpList API.

**P0**
- **CERO tests** — módulo crítico (controla envío de emails) sin un solo test.
- **CERO policies y cero permisos** — solo middleware `settings`. Cualquier usuario con rol `settings` puede cambiar credenciales SMTP/IMAP, generar API keys, eliminar conexiones.
- **CERO Form Requests** — TODA la validación inline en cada método (10+ métodos).
- **Credenciales SMTP/IMAP/API keys almacenadas SIN encriptación** — Setting::setEmailSettings guarda en JSON plano. Mailgun api_key, phpList api_key, Gmail tokens, IMAP password todos en plano.
- **Gmail OAuth2 callback NO valida `state` parameter** — vulnerabilidad CSRF clásica.
- `storeImapConnection` valida con `'password' => 'required|string'` pero no enmascara en logs.
- Inconsistencia routes: `settings.outgoing-email.index` con guión + `EmailSettingsController::update` POST sin método `update()`.

**P1**
- Controllers gigantes: `IncomingEmailSettingsController` 660 líneas y 12 métodos. Extraer a servicios.
- `testImapConnection` solo hace `fsockopen()` (TCP socket) — NO valida credenciales reales.
- `OutgoingEmailSettingsController::testConnection` igual — solo `fsockopen`.
- No hay job/queue para envío test (síncrono dentro del request).
- HTML email test con gradient `#90bb13 → #7a9f11` en código PHP (no en Blade) y viola paleta `#90bb13`.
- No hay policy para `apiDocumentation`.
- `Setting::set('incoming_email', json_encode([...]))` se llama múltiples veces — race condition.
- `generateApiKey` no invalida key vieja.
- No existe `routes/api.php`.
- No hay seeder de permisos.
- `registerMenus` registra menú dentro del sidebar sin verificar permisos.

**P2**
- Vistas con `style="..."` inline (2 archivos).
- Falta validación SPF/DKIM/DMARC.
- `config/mails-settings.php` solo tiene `name + description`.
- No usa `php artisan make:` ni patrones de módulos — falta migrations/seeders/factories.
- `updatePipe`, `updateApi`, `updateMailgun`, `updateGmail`, `updatePhplist` repiten patrón idéntico — abstraer.
- `webklex/laravel-imap` en require pero no se usa.
- Phplist subscribe no rate-limit.
- Falta loggear quién cambió qué setting.

**P3**
- README de 588 bytes, esencialmente vacío.
- Catch-all `\Exception` sin re-throw.
- `uniqid()` para IDs IMAP/Gmail no es criptográficamente seguro.
- Middleware `settings` no documentado.
- No hay vista `incoming-edit` análoga a `outgoing-edit`.

#### Módulo: Mailrelay

**Estado actual.** Módulo enorme de email marketing/automation multi-provider. **36 controllers, 47 services (~3540 líneas), 38 entities/models, 5 jobs, 1 notification, 5 form requests, 5 policies, 31 migrations (+25 en `acas/` no aplicadas), 2 factories, 4 seeders, 10 tests, 5 enums, 4 contracts, 6 exceptions, 9 commands, 5 mail providers (Mailrelay/Mailtrap/SendGrid/AWS SES/Postmark), validators de email, ~70 vistas Blade**. **Sub-jerarquía duplicada `modules/Mailrelay/modules/Mailrelay/`** + **8 archivos `.md` de documentación** (7900+ líneas).

**P0**
- **Estructura duplicada confusa** — `modules/Mailrelay/modules/Mailrelay/database/{factories,seeders}` y tests aislados del autoload PSR-4. Los `CampaignFactory`, `MailProviderFactory`, `CampaignApiTest`, `MailProviderApiTest` ahí dentro NO se ejecutan.
- **Migraciones `acas/` (25 archivos)** — no se cargan vía `loadMigrationsFrom`. Tablas como `bounces`, `unsubscribe_events`, `open_click_logs`, `error_logs`, `feedback_surveys`, `media_files`, `delivery_methods`, `automations`, `sms_*`, `bulk_email_sending`, `api_batches`, `activity_log` NO existen en BD. Múltiples Entities referencian tablas inexistentes → fatal cuando se llamen.
- **Auth controllers de Laravel UI completos** (`Auth/LoginController`, `RegisterController`, `ForgotPasswordController`, `ResetPasswordController`, `VerificationController`, `ConfirmPasswordController`) y vistas `auth/login.blade.php` — scaffolding del package original que NO debería estar.
- `Mailrelay/CampaignController` (vendor) acepta `$request->all()` SIN validación — vector RCE/XSS.
- Routes API publican TODO bajo `/api/...` SIN `auth:sanctum`. Solo `/api/v1/*` exige Sanctum. Las legacy son anónimas.
- `view()->composer('*', NavigationComposer::class)` se aplica a TODA vista del sistema — perf hit global.
- 24 controllers usan `$request->validate(...)` inline.
- `registerGates()` define `Gate::before` para `mailrelay.*` — bypassa todas las policies.
- Sin tests para 33 de 36 controllers.
- Newsletter API endpoints sin throttle, sin captcha.
- Imports API (`POST /api/imports/upload`) sin auth + sin tamaño máximo + sin tipo de archivo validado.

**P1**
- Routes mezclan `panel/managers/mailrelay`, `panel/mailrelay`, `panel/mailrelay/setting` — viola convención.
- Routes names dispersos (`settings.mailrelay.*`, `managers.mailrelay.*`, `mailrelay.*`).
- Usa namespace `Modules\Mailrelay\Entities\` en lugar de `Models\` — convención v12 manda Models.
- CampaignWebController, SubscriberController NO usan ServiceClasses — guardan/actualizan modelos directamente.
- `Lists` model class chocha con keyword PHP `list` reservado — usar `MailingList`.
- Provider credentials encryption — verificar implementación real.
- 5 `*.md` de documentación in-module + 3 archivos de "PROGRESS" / "SESSION-SUMMARY".
- `vendor/scramble/docs.blade.php` referenciado pero scramble no está en composer.
- `SyncMailrelayCommand` corre cada hora sin `--dry-run`.
- `SendCampaigns` cada 15 min sin verificar locking distribuido.
- Bulk actions en `SubscriberController` faltan en vista.
- `EmailValidatorService` con 11 validators — over-engineered.
- Ningún Form Request tiene `authorize()` con permiso Spatie.
- Falta retención automática de logs.

**P2**
- 30 vistas con `style="..."` inline.
- `view->where('email', 'ilike', ...)` — sintaxis PostgreSQL en proyecto MariaDB.
- Sin `lang/` — strings hardcoded.
- `Lists::all()` en `CampaignWebController::create` y `subscribers/create` — sin paginación.
- `Subscriber::query()` con filtros pero sin eager-loading.
- `registerScheduledTasks` se ejecuta dos veces (boot + booted).
- Routes con prefijos `panel/managers/mailrelay/providers` Y `panel/mailrelay/setting/providers` apuntan al MISMO controller.
- NavService registra menú `Permisos` (`settings.mailrelay.permissions.index`) — exponer permisos sin policy reforzada.
- `provider_*.php` sin tests específicos por provider.
- Migración con prefijo timestamp incorrecto (`2026_01_22_create_mails_webhooks_table.php` sin guión normal).
- Falta SPF/DKIM/DMARC validator.
- `config/mailrelay.php` debería marcar SECRET keys.
- `barryvdh/laravel-translation-manager` cargado en `loadTranslationsFrom` pero `resources/lang` no existe.
- 5 Console Commands sin description.

**P3**
- README.md de 36KB.
- `package.json` y `vite.config.js` propios — vestigio.
- `CHANGELOG.md` con emojis.
- Comentarios `// ✨ Multi-Provider Controllers (v2.0)`.
- `HasSafePermissionCheck` trait sin documentar.
- `AbstractMailProvider::htmlToPlainText` duplica lógica de Mailer.
- Controllers con base inconsistente (algunos `App\Http\Controllers\Controller`, otros propio).

**Decisión arquitectónica pendiente:** ¿Mailrelay reemplaza a Mailer o complementa? Hay overlap claro (Templates, Variables, Components, Endpoints). Documentación interna sugiere migración planeada — necesita decision-record (ADR).

**Refactor PRIORIZADO:**
1. Eliminar carpetas vendor (`Auth/`, `views/auth/`, `home.blade.php`, `welcome.blade.php`, `dashboard.blade.php`, `database/migrations/acas/`, sub-folder `modules/Mailrelay/`).
2. Auditar y eliminar Entities huérfanos sin migración aplicada.
3. Mover validación inline a Form Requests (~20 nuevos requests).
4. Añadir `auth:sanctum` a TODAS las rutas API legacy + rate-limiting.
5. Tests para los 33 controllers sin cobertura.
6. Renombrar `Entities/` → `Models/`.
7. Limpiar 8 archivos `.md` de planning del root.
8. Decidir destino del módulo: si es legacy, archivar; si activo, hardening sustancial.

### SEO/Analytics

#### Módulo: Seo

**Estado actual.** Módulo extenso (225 archivos PHP), versión 1.0.0. SEO end-to-end: 25 controllers, 11 modelos (SeoMeta, SeoRedirect, SeoTemplate, SeoAuditLog, SeoStaticUrl, SeoWebVital, SeoPagespeedSnapshot, SeoAlert, SeoRedirectHit, Seo404Log), 14 servicios (SeoService, SchemaOrgService, IndexNowService, KeywordRankingService, GoogleSearchConsoleService, HreflangService, RedirectChainDetector, etc.), 7 jobs, 9 console commands, 1 observer, 25 form requests, 5 mailables, 18 migrations, 19 tests Feature/Unit. Sirve sitemaps especializados (images/video/news), robots.txt, llms.txt, IndexNow key, beacon Core Web Vitals.

**P0**
- `composer.json` desactualizado: `require: {"php":"^8.2","illuminate/support":"^11.0"}` cuando proyecto es PHP 8.4 / Laravel 12.
- Convención de rutas/permisos rota: prefijo `panel/setting/seo` y nombre `setting.seo.*` (singular).
- Permisos en TitleCase: `Seo.metas.index`, `Seo.redirects.index` — convención exige `seo.metas.index`.
- API `seo-metas` sin `auth:sanctum` (`routes/api.php` solo `middleware('api')`) — cualquiera CRUD SeoMeta.
- API no usa Eloquent Resources — devuelve modelos crudos exponiendo columnas internas.
- Validación inline en `SeoMetaController::bulkUpdate` y `preview`.
- Push global de middleware (`RedirectMiddleware`, `Track404Middleware`, `PerformanceHintsMiddleware`, `AutoPaginationMiddleware`) a `web` group — perf hit en cada request.
- Falta `auth` en endpoints públicos `/api/seo/web-vitals` (solo throttle 120/min).
- Sin Policies (`app/Policies/` no existe).

**P1**
- `composer.json` no declara dependencias reales (DomPDF, IndexNow, Google API, Intervention/Image, Spatie Permission).
- Falta `tests/TestCase.php` propio del módulo con `createUser` helper.
- Factories solo para SeoMeta y SeoRedirect — faltan para 8 modelos.
- `SeoMetaWebController::index`: query `selectRaw` con CASE complejo — extraer a `SeoMetaStatsService`.
- Eager loading parcial — `SeoMeta::with('seoable')` polimórfico no eager-carga locale.
- `SeoMetaController` API: constructor con DI de `SeoService` mientras `preview()` instancia `new SeoService`.
- `bulkUpdate` permite update masivo sin scoping — vector mass-assignment cross-tenant.
- `SeoMetaWebController::index` sort whitelist con `orderByRaw` interpolado.
- Faltan API Resources.
- Falta `routes/api.php` con namespace `api.seo.*`.
- Lang/translations vacíos — directorio existe pero archivos no.
- README sin documentar endpoints API.
- 5 mailables pero no Notifications class — toda alerta solo email.
- Observer `SeoMetaObserver` no dispara eventos `SeoMetaUpdated` para terceros.
- Inline styles en views (329 ocurrencias).
- `SeoMetaController` API `preview()` con chain de 8 `if ($request->has(...))`.

**P2**
- Solo 11 tablas con factories y 18 migrations — faltan factories en `seo_templates`, `seo_static_urls`, `seo_404_logs`, `seo_alerts`, `seo_web_vitals`.
- 9 comandos artisan no documentados en README.
- `registerCleanupScheduler`: 8 jobs sin `onOneServer()`.
- `callAfterResolving(Schedule::class)` y `app->booted()` mezclados.
- Verificación de roles hardcoded `seo_setting('admin_roles', ['super-settings','settings'])`.
- `SeoServiceProvider::boot` usa `BlogCategory`/`BlogTag` directamente — acopla Seo al módulo Blog.
- `SitemapAdminController` y `SitemapController` ambos en Seo y Sitemap — duplicación cross-module.
- Activity log faltante sobre `SeoRedirect`, `SeoMeta`.
- Sin búsqueda full-text en metas.
- Cache de sitemap delegado al módulo Sitemap pero endpoints sitemap-images/video/news en este módulo NO cachean.
- `SeoMetaWebController::badgeData` POST sin paginación ni límite.
- `/{indexnowKey}.txt` regex `[A-Za-z0-9]{8,128}.txt` puede colisionar.

**P3**
- Carpeta `public/` del módulo sin `vendor:publish` tag.
- `SitemapPriorityCalculator` no testeado explícitamente.
- Comentarios mezcla español/inglés.
- `SeoServiceProvider::namespace` declarado pero no usado.
- Falta `module:Seo` middleware en grupo de rutas.

#### Módulo: Sitemap

**Estado actual.** Módulo minimalista (19 archivos PHP). 1 controller, 1 modelo (`SitemapGeneration`), 1 builder (`SitemapBuilder`), 1 helper, 1 facade, 3 console commands, 1 trait (`HasSitemapItems`), 0 form requests, 0 policies, 0 factories, 0 lang. 1 archivo test (`SitemapTest.php` con 31 métodos). 1 migration. Routes web sirve `/sitemap.xml`, `/sitemap-pages.xml`, `/sitemap-posts.xml`, `/sitemap-index.xml`. `routes/api.php` vacío. Schedule diario `sitemap:generate` 02:00.

**P0**
- Namespace inconsistente: `composer.json` y `module.json` declaran `modules\\Sitemap\\` (lowercase 'm') mientras código usa `Modules\\Sitemap\\` — falla autoload Linux.
- `SitemapServiceProvider::register()` registra `RouteServiceProvider` pero rutas se cargan vía `routes/web.php` global — doble carga.
- `routes/web.php` no usa middleware `auth` — correcto para público pero rutas sin prefix de módulo, colisionan con `Modules\Page\Http\Controllers\SitemapController`.
- Acoplamiento bidireccional Seo ↔ Sitemap: `SitemapController::posts` importa `Modules\Seo\Services\SitemapCallbackRegistry`; `GenerateSitemapCommand` importa SeoMeta.
- `SitemapBuilder::generate()` escribe a `public_path('sitemap.xml')` pero el route `sitemap.index` sirve la versión cacheada — pueden divergir.

**P1**
- `PingSitemapCommand` pinguea `google.com/ping` y `bing.com/ping` (deprecados desde junio 2023). Migrar a IndexNow / Search Console API.
- Sin Form Requests (no hay endpoints admin).
- Sin UI admin — imposible regenerar sitemap, ver historial `SitemapGeneration`.
- Permisos definidos pero sin uso (no hay endpoints admin).
- `SitemapGeneration` model existe pero no se inspecciona en vista.
- Falta NavService menu — módulo no aparece en sidebar.
- `SitemapBuilder::generate()` no maneja errores IO.
- Sin lang files.
- `max_items` enforcement lanza `OverflowException` en runtime — debería dividir automáticamente en sitemap-N.xml.

**P2**
- `composer.json` no declara `require`.
- `SitemapTest.php` recrea tabla manualmente con `Schema::create` en `setUp()` por conflicto con RefreshDatabase.
- No hay test del comando `sitemap:ping`.
- Cache key `sitemap-xml`, `sitemap-pages-xml` fijos — sin invalidar al cambio de dominio.
- `registerCommandSchedules`: schedule sin `withoutOverlapping()` ni `onOneServer()`.
- `SitemapBuilder` mantiene estado en propiedades — singleton via facade puede acumular items.
- Sin handling de URLs con caracteres especiales (encoding XML).
- Falta endpoint de imágenes/vídeos/news (Seo los tiene).
- `config('sitemap.robots')` definido pero el módulo Seo tiene su propio `RobotsTxtController`.

**P3**
- 8 archivos de docs en raíz — sobre-documentación, consolidar.
- `SitemapBuilder::generate()` sin parámetro de ruta.
- No expone evento `SitemapGenerated`.
- Controller sin DI del `SitemapBuilder` — instancia con `new`.

#### Módulo: Analytics

**Estado actual.** Módulo orientado a integración GA4 (Google Analytics Data API v0.23). 7 controllers, 1 modelo (`AnalyticsReportSchedule`), 2 servicios, 1 mailable, 2 events, 1 listener, 1 job, 2 commands, 10 traits, Period value object, Forms helper. 4 migrations, 3 form requests, 4 tests Feature, lang ES/EN. Sin policies, factories, Resources API, notifications. **Carpeta `modules/Analytics/modules/` interna sospechosa**.

**P0**
- Carpeta `modules/Analytics/modules/` contiene lang de Slug/Widget/CookieConsent/Captcha — RUTA INCORRECTA, no son submódulos.
- Carpeta `modules/Analytics/vendor/` dentro del módulo — commit incorrecto.
- Convención de rutas rota: `panel/setting/analytics` (singular) y `settings.analytics.*` (plural mismatched).
- Permisos con dot.notation extendido inconsistente: `analytics.dashboard.view`, `analytics.data.clear_cache`. 20 permisos sin asignar a roles.
- `AnalyticsPermissionSeeder` no asigna a roles — solo `firstOrCreate`.
- Credenciales GA4 cifradas en `setting()` table — riesgo si APP_KEY rota.
- `AnalyticsController` (API) usa `$this->authorize('analytics.data.view')` 18 veces — extraer a constructor.
- No hay test de los 18 endpoints API.

**P1**
- API endpoints sin Eloquent Resources.
- No hay throttle por usuario, solo por IP.
- Sin `auth:sanctum` en API.
- `AnalyticsController::query` acepta dimensions/metrics arbitrarias sin validación.
- Sin Notifications — emails enviados directos.
- Falta dashboard de errores GA4.
- `AnalyticsReportSchedule` sin policy.
- Sin factory para `AnalyticsReportSchedule`.
- `GenerateAnalyticsReport` job sin `failed()`, `$tries`, `$timeout`, `$backoff`.
- `registerScheduledTasks` solo registra `analytics:dispatch-schedules`.
- `Cache::tags(['analytics'])` requiere driver tags-capable.
- Pixels (Meta, TikTok, LinkedIn, Clarity) configurados aquí Y en Cookie — duplicación.
- `DashboardController::index` solo verifica configuración con `setting('google_analytics_credentials')` (no descifrado).
- Vistas con inline styles (62 ocurrencias).
- Paleta especial roja del módulo (#90bb13, #333333, #7b0000) sin validación visual.

**P2**
- Sobre-documentación: README.md, IMPLEMENTATION_SUMMARY.md, QUICK_START.md, SCHEDULED_TASKS.md, VERIFICATION_CHECKLIST.md.
- `AnalyticsServiceProvider::register` lanza `InvalidConfiguration` si no hay credenciales — rompe `php artisan`.
- `AliasLoader::getInstance()->alias('Analytics', AnalyticsFacade::class)` en register — conflicto global.
- Sin Activity logging en cambios de credenciales.
- `AnalyticsSettingController::update` con try/catch genérico.
- `AnalyticsDataService::fromSettings()` static factory dificulta testing.
- GDPR: tracking pixels cargan SIN verificar consent del módulo Cookie.
- `ScheduleCreated` event sin listener.
- `DashboardController::index` sin return type.
- `Analytics::query()->limit(N)` sin paginación.
- `config/analytics.php` no leído (publicado pero fallback funciona).

**P3**
- Controllers en subcarpeta `Settings/` y suelto `AnalyticsSettingsController` en raíz.
- `AnalyticsAbstract` y `Analytics` (concrete): solo una implementación — abstracción innecesaria.
- 10 traits — verificar uso real.
- `Period.php` y `AnalyticsResponse.php` en raíz `app/` — convencionalmente irían en Models/ValueObjects.

#### Módulo: Cookie

**Estado actual.** Módulo enfocado a GDPR/LOPD compliance. 3 controllers, 2 modelos (`CookieConsentLog`, `CookieInventory`), 4 form requests, 1 notification, 2 commands, 5 migrations, 2 seeders, 1 trait, 1 helper, 1 shortcode `[cookie-inventory]`. Lang en 5 idiomas (es/en/de/it/pt). 4 tests Feature (798 LOC). Sin factories, sin policies, sin Eloquent Resources, sin API admin endpoints. Routes: `panel/settings/cookie` (CORRECTO con `settings.cookie.*` namespace).

**P0**
- `CookieConsentController::status` decodifica cookie cliente JSON sin validación de schema.
- `CookieConsentLog::user` relación pero modelo SIN factory.
- Convención permisos: `cookie.settings.view` correctos, pero `module.json` declara `"alias":"Cookie"` capitalizado.
- `config_path('Cookie/general.php')` y `config('Cookie.general.cookie_name')`: namespace en mayúsculas — atípico.
- `StoreConsentRequest::accepted_categories.*` whitelist hardcoded — si admin agrega nueva categoría, validación rechaza.
- `CookieSettingsController::logs` query: `where('action', 'like')` y `orWhereHas('user'...)` sin paréntesis externos — SQL mal-grouped.
- No hay rate-limit en `CookieSettingsController::export` — memory bomb potencial.

**P1**
- `CookieConsentController::store` deduplicación por IP hash window 5 min — afecta privacidad (IP NAT empresarial compartida).
- Sin Policies — autorización en middleware del controller.
- `CookieInventoryController::store/update`: pattern `$data['is_active'] = $request->has('is_active')` correcto pero podría usar `$request->boolean()`.
- No hay endpoints API REST (`routes/api.php` no existe).
- `CookieConsentLog` modelo sin policy ni scope.
- `accepted_categories` JSON column sin índice.
- Inline styles en 8+ views.
- `shouldDisplayCookie()` chequea `Setting::get('cookie.enabled', '0') === '1'` en cada `RouteMatched`.
- `registerCookieAssets` se ejecuta en cada request.
- Sin tracking de versión de política aceptada por usuario.
- Falta `Notification` correspondiente al alert threshold.
- README no documenta el shortcode `[cookie-inventory]`.
- Migration `2026_03_21_000001_drop_cookie_settings_table.php` dropea legacy + `2026_02_17_create_cookie_settings_table.php` la crea — sucio.
- `CookieConsentController::policy` action referenciado pero no en routes — código muerto.

**P2**
- `registerShortcodes` lee `CookieInventory::active()->ordered()->get()` sin paginación.
- Falta auditoría (Activity log) sobre cambios de configuración cookie.
- Sin endpoint para que el usuario descargue/elimine sus consents propios (GDPR Art. 20 y 17).
- `CookieConsentLog::ip_hash` SHA256 sin sal — rainbow table.
- `Setting::get('cookie.X')` sin schema.
- Tests cubren bien consent + inventory + commands pero no shortcode ni banner Blade.
- `CookieSettingsController::update`: 9 checkboxes hardcoded.
- `UpdateCookieSettingsRequest::rules`: regex GA `/^(G|UA|AW|DC)-/i` acepta UA-* (deprecated jul 2023).
- Pixel IDs sin verificación cruzada de "_enabled".
- No hay invalidación de cache de partials `_meta_pixel.blade.php`.
- `CookieInventoryController` sin endpoint `bulkAction`.
- Falta búsqueda/filter en inventario.
- No notifica al actualizar cookie policy URL al sitemap.

**P3**
- `module.json` `priority: 0` y `active: 1` no se reflejan en boot order.
- `CookieInventory` no tiene `SoftDeletes`.
- `HasCookieConsentSeeder` trait con nombre raro.
- `helpers/CookieHelper.php` cargado vía `require_once` — debería ir en composer autoload.files.
- Lang de/it/en/pt parecen autotraducidos.
- `StoreConsentRequest::is_update` poco semántico.

### Infrastructure

#### Módulo: Cache

**Estado actual.** 1 controller (`CacheSettingsController`), 1 Form Request, 0 modelos propios (usa `Setting` de Core), 2 vistas + 1 componente, **0 tests**, 0 seeders, 0 lang. Página de ajustes (toggles para cache de menus/avatares/sitemap/páginas, TTLs), monitor de Redis con `Cache::flush` selectivo, endpoints JSON para stats. Routes bajo `panel/settings/cache` con nombre `settings.cache.*`.

**P0**
- 0 tests — `modules/Cache/tests/` no existe. Crítico porque expone `Cache::flush()` desde HTTP.
- No existe `CachePermissionsSeeder` — los permisos están en `config/permissions.php` pero no en seeder. Sin esto, `middleware('can:...')` siempre falla 403 en instalación limpia.
- Convención de naming de permisos rota — usa PascalCase `Cache.settings.index` mientras el resto usa lowercase.
- `flush('all')` ejecuta `Cache::flush()` que vacía TODO Redis (sesiones, queue, locks, Horizon). Debería ser `Cache::store('redis')->flush()`.

**P1**
- Validación abierta de `type` en `flush()` — `$request->input('type', 'all')` sin Form Request.
- `Cache::tags(['pages'])->flush()` falla silencioso si driver no soporta tags.
- `redisStats` cachea `Redis::connection()->info()` con TTL 5s sin tag — la propia llave queda en Redis monitoreado.
- Permisos doblemente aplicados sin tests que verifiquen 403.
- No hay `routes/api.php` real.
- `CacheServiceProvider::registerRoutes()` está vacío — lógica en `RouteServiceProvider`. Inconsistente.
- Falta middleware `settings`.

**P2**
- `Setting::set/get` con prefix `'cache.'` hardcoded — extraer a `CacheSettingsService`.
- No hay configuración de hit/miss rate por capa.
- Sin lang/translations.
- Sin migration ni seeder — depende de Core.
- Vista `monitor.blade.php` usa `confirm()` nativo en lugar de modal Bootstrap.

**P3**
- README desactualizado — TTL en segundos vs validación en minutos.
- `page-cache-stats.blade.php` con AJAX duplicado de `monitor.blade.php`.
- Sin documentación de comandos artisan equivalentes.

#### Módulo: Queue

**Estado actual.** Dashboard de gestión de colas. 1 controller (`QueueDashboardController`), 0 Form Requests, 0 modelos propios, 1 vista, 1 BaseJob abstracto, 3 commands (`queue:list`, `queue:purge-failed`, `queue:retry-all`), 1 seeder de permisos, 1 archivo tests (8 casos), 0 lang. Stats failed/pending, Horizon status.

**P0**
- TODOS los métodos de mutación (retry/delete) usan `$request->input('uuids', [])` SIN Form Request.
- No-CSRF en tests con `withoutMiddleware` — bypassa autorización. Falta test que pruebe 403.
- `retryAll` permite retry de TODOS los failed jobs sin filtro obligatorio.
- `composer.json` declara `spatie/laravel-rate-limited-job-middleware: ^2.8` pero `vendor/` está commiteado dentro del módulo.

**P1**
- `authorize('queue.view')` y `authorize('queue.manage')` en cada método — debería ser middleware en constructor.
- `config/queue_module.php` no aplica patrón estándar — `merge_config_from()` manual.
- No hay tests para los 3 commands.
- `extractJobClass($payload)` decodifica JSON en cada fila — N+1 effective.
- Bulk retry usa `Artisan::call('queue:retry', ['id' => $uuids])` sin chunking.
- Falta `Modules\Queue\Models\FailedJob` — todo accede via `DB::table`.
- No hay Resource API ni `routes/api.php`.
- `resolveHorizonStatus()` ejecuta `Artisan::call('horizon:status')` síncrono en cada request.

**P2**
- `stats()` hace 3 queries separadas.
- `top_failing` solo mira últimos 200 — sesgo.
- `registerCommandSchedules()` está vacío.
- Sin lang/.
- Vista usa `confirm()` nativo.
- Bulk-actions-bar hardcoded en HTML.
- No hay queue priorities visibles.

**P3**
- README documenta `vendor:publish --tag=queue-config` que no está implementado.
- `registerNavigation()` con label "Gestión de colas" — capitalizar solo primera palabra.
- `BaseJob::tags()` retorna solo `[class_basename(static::class)]`.
- `module.json` `requires: []` vacío.

#### Módulo: Storage

**Estado actual.** Gestión de discos de filesystem (local/ftp/sftp/s3). 1 controller (`StorageController`, 680 líneas), 3 Form Requests (`DiskRequest` abstract + Store + Update), 0 modelos, 5 vistas, 1 trait + 1 helper functions file, 1 seeder, 1 base test case + 1 feature test (35 tests) + 1 unit test (15 tests). Encripta credenciales (FTP password, S3 key/secret) via `Crypt::encryptString`.

**P0**
- `destroy()` usa `$request->validate()` inline — debe ser `DeleteDiskRequest`.
- `testConnection()` con bloque inline de 11 reglas.
- `StorageController::index/create/edit/destroy` usa `abort_unless(auth()->user()->can(...))` en lugar de middleware en constructor.
- Convención de rutas rota — `name('storage')` (sin punto final) + nombres `.index`, `.destroy`, `.create` resulta en `settings.storage.index`, `settings.storage`. Listado usa `route('settings.storage')` (sin .index).
- Persistir credenciales en `Setting` (string JSON) es anti-patrón — debería existir modelo Eloquent `StorageDisk`.
- `buildDiskData()` retorna 3 tipos diferentes (`null`, `array`, `array` con clave `error`).
- API endpoints faltantes — no hay `routes/api.php` ni Resources.

**P1**
- No hay servicio `StorageManagerService` — toda la lógica en controller.
- `testConnection()` registra dinámicamente disco temporal en `config()` — efectos secundarios persistentes.
- `loadStorageConfig()` cachea en Redis con `Cache::remember(...3600)` pero `saveCustomDisks` hace `Cache::forget` — race condition de hasta 60min.
- Sin policy.
- Sin migration.
- `limits.max_file_size` y `max_disk_size` en config NO se aplican.
- No hay file browser — feature crítica que falta.
- Sin cleanup automático de archivos huérfanos.
- No usa `Modules\Storage\Tests\StorageTestCase` consistentemente.

**P2**
- Vista `index.blade.php` muestra stats con `if ($statistics['total_disks'] > 0)` rodeando 4 col-md-3.
- `delete-disk-btn` usa `confirm()` nativo.
- `HasFileSystemPaths` trait usa `$this->uid` y `$this->user` sin declarar de dónde vienen.
- `PathHelper.php` define funciones globales — antipatrón.
- Sin lang.
- `testConnection()` usa `auth()->user()->can(...)` doble.
- Sin test del `loadStorageConfig` en SP.
- `buildDiskFromConfig()` con `$maskCredentials` boolean flag — code smell.

**P3**
- Driver options duplicados entre `config/storage.php` y `StorageController::driverOptions()`.
- README dice "Modelos: Ninguno" pero composer.json declara carpeta `Modules\Storage\Database\Factories\`.
- Trait `HasFileSystemPaths` con typo `home/templates` vs `home/inventaries`.
- `Setting::set('system.custom_storage_disks', json_encode($disks))` — prefix debería ser `storage.` no `system.`.

#### Módulo: Backup

**Estado actual.** Sistema completo de backups. 4 controllers (`BackupController` 880 líneas con lógica supervisor!, `BackupScheduleController`, `BackupNotificationController`, `Api\BackupScheduleApiController`), 0 Form Requests, 2 modelos (`BackupSchedule`, `SupervisorBackup`), 3 migrations, 2 seeders, 1 Mail, 3 Notifications, 2 Events, 1 Listener (silent), 1 Helper, 1 Job (`CreateBackupJob` 418 líneas), 1 Console Command, 9 vistas, 3 tests. Spatie Laravel Backup + custom job con `mysqldump` + `ZipArchive`.

**P0 — Riesgo crítico de seguridad**
- `BackupController` ejecuta `shell_exec` con `sudo -S` y password en stdin (`supervisorInstall`, `supervisorApply`, `supervisorRestart`). **Agujero de seguridad masivo:**
  - Ejecuta `apt-get install`, `systemctl enable`, `mkdir`, `mv` con sudo desde HTTP.
  - Si role `Backup.backups.index` se asigna por error a un user no-admin, gana root.
  - Password sudo viaja en POST plain (CSRF protege replay pero TLS-MITM lo lee).
  - **Eliminar completamente** la auto-instalación de supervisor desde HTTP.
- `schedulerConfigure()` modifica `crontab` del usuario web vía `shell_exec("crontab {$tmpFile}")` — mismo riesgo.
- `BackupScheduleController::store/update` con `$request->validate()` inline.
- `BackupScheduleController::store` no autoriza — solo `index/create/edit/getScheduleDetails` tienen middleware. `store/update/destroy/toggle/bulkAction` sin protección explícita.
- Convención de rutas rota: `panel/setting/backups` (singular).
- Convención naming de permisos rota — `Backup.backups.index` PascalCase.
- `CreateBackupJob::handle()` ejecuta `mysqldump` con `proc_open` y password en variable env — `Log::info('Executing mysqldump command: '.substr($cmdStr, 0, 100))` puede leak.
- `BackupController::supervisorApply` detecta `$phpBinary` heurísticamente con `shell_exec('command -v php')` — en containers/PATH limitado puede ejecutar binario incorrecto.

**P1**
- No hay Form Requests en NINGÚN controller.
- No hay `BackupSchedulePolicy`.
- Tests bypasan middleware con `withoutMiddleware([...])` con 9 middlewares incluyendo `Authorize::class` y `PermissionMiddleware::class`.
- `CreateBackupJob` mezcla 5 responsabilidades — extraer a 3 servicios.
- `BackupSchedule::shouldRunNow()` con tolerance "within 1 minute" + `app:run-scheduled-backups` `everyMinute()` — puede saltar ventana.
- `BackupSchedule::shouldRunNow()` para `frequency=daily` no chequea fecha — solo hora. Bug.
- Mail templates HTML hardcoded en seeder (`BackupEmailTemplatesSeeder.successTemplate()` retorna 600 líneas heredoc).
- `BackupController::index` lee `getBackupFiles()` en cada GET — `RecursiveDirectoryIterator` lento con miles.
- `destroy()` y `download()` usan `basename($filename)` — bien para path traversal pero no valida zip/tar.gz.
- No hay restore functionality — feature crítica.
- Sin retention policy enforcement runtime.
- `composer.json` con `vendor/` commiteado.

**P2**
- `registerSchedules` registra 4 schedules en `app->booted(...)` sin try/catch individual.
- `BackupScheduleApiController` solo `index` y `show` — asimétrico.
- `BackupNotification` clase "silent" extiende Spatie como workaround.
- `BackupEventListener` con 10 métodos que solo loguean.
- `BackupController::detectSchedulerActive` mezcla 3 detecciones.
- Vista `backups/index.blade.php` y `schedules/index.blade.php` no cumplen patrón estándar.
- `Backup\Models\BackupSchedule` no tiene factory.
- `SupervisorBackup` model sin tests, sin uso visible — ¿código muerto?
- Encryption: `config/backup.php` con AES-256 pero `CreateBackupJob` (custom) NO encripta.
- No hay multi-disk targets — solo `local`.

**P3**
- README desactualizado — describe rutas `/manager/settings/backups/` y arquitectura inexistente.
- `module.json` `providers: []` vacío.
- `BackupHelper.php` global function — antipatrón.
- `BackupSchedule::getScheduledTimeAttribute` con triple try/catch.
- `BackupController::shellExec` decide success por presencia de string "error".

#### Módulo: Optimize

**Estado actual.** Optimización HTML/assets/imágenes. 1 controller (`OptimizeController` 412 líneas), 0 Form Requests, 0 modelos, 4 services bien diseñados, 0 seeders, 22 middlewares HTTP de optimización, 8 console commands, 17 unit tests + 3 feature tests, 0 lang. 2 vistas. Schedules: weekly purge, weekly `media:optimize-all`. Sin permissions seeder.

**P0**
- **NO HAY PermissionsSeeder** — `optimize.view`, `optimize.update`, `optimize.run-command` no existen como permisos. Las rutas usan `['web', 'auth']` sin `can:`. Cualquier autenticado puede:
  - Cambiar configuración global de optimization.
  - Ejecutar `optimize:enable-all`, `optimize:purge-cache` (artisan!), `media:convert-webp`, `theme:audit-a11y --fix`.
  - El `--fix` flag modifica archivos del theme.
- `OptimizeController` no tiene middleware `can:` en constructor.
- `runCommand()` y `runAll()` ejecutan `Artisan::call($command, $params)` desde HTTP. Aunque hay allowlist + sanitizeParams, si autorización falla daño es real.
- `update()` no usa Form Request — itera `CHECKBOXES` const sin validación.
- `resetStats()` no usa Form Request, no requiere permission.

**P1**
- `OptimizeServiceProvider::registerOptimizationMiddleware()` modifica web group dinámicamente en cada request via `RouteMatched` event — coste de lectura de 20+ Settings por request.
- No hay caché de "optimize.enabled" + checkbox flags.
- 22 middlewares de transformación HTML — riesgo de bugs combinatorios. Faltan tests integración.
- `AssetOptimizerService::minifyCss/minifyJs` usa regex casero — rompe URLs `https://` dentro de strings. Usar librería real.
- `ThemeOptimizerService::optimizeThemeImages` itera todo `public/images` sin límite por defecto — timeout HTTP.
- Falta `routes/api.php`.
- **`Tests/Unit/` y `Tests/Feature/` con T mayúscula** — convención `tests/Unit/` minúscula. Romperá PSR-4 case-sensitive en Linux.
- Sin tests de los services.
- `runAll()` ejecuta 10 comandos secuenciales en HTTP — request entero se cuelga.

**P2**
- `MIDDLEWARE_MAP` en const class — 19 entries hardcoded. Mover a config.
- `COMMANDS` allowlist tiene comandos de otros módulos (`media:convert-webp`, `theme:audit-a11y`, `page:audit`) — acoplamiento implícito.
- No hay `lang/`.
- `logExecution()` guarda solo 20 entries en cache — debería persistirse en DB.
- `OptimizeController::flushResponseCache` con try/catch BadMethodCallException — comentario "clear by prefix pattern" no implementado.
- Stats por capa — solo `requests` y `bytes_saved` global.
- Vista `tools.blade.php` muy compleja (550 líneas) con JS inline grande.
- `tools.blade.php` no usa modal-dialog-centered para confirmaciones.
- `runAllSteps` JS no envía CSRF en headers — body como `_token`.

**P3**
- `config/general.php` `skip` patterns — incluye `*.svg` cuando inline-CSS optimization sería útil.
- README minimalista — no documenta los 22 middlewares.
- No hay seeder de email templates.
- `PageSpeed.php` middleware sin documentación de cuándo se ejecuta.

### System/Admin

#### Módulo: Core

**Estado actual.** Módulo fundacional (priority 1). Provee dashboard general, helpers globales (DateHelper, SiteHelper, RateLimitHelper, SecretHelper, UrlValidationHelper), middlewares transversales (Cors, MinifyHtml, FontOptimization, SecurityHeaders, Compression), comandos producción (OptimizeProduction, SystemCleanup, GeoIpCheck), modelos compartidos (Setting, Lang, Countrie, IpLocation, Job), masking de logs sensibles, servicios HTTP (CircuitBreaker, HttpClientService) y validadores. Solo expone `/panel/dashboard` con 12 endpoints AJAX. **No tiene `composer.json`**, ni `routes/`, ni seeders de permisos.

**P0**
- Falta `composer.json` en `modules/Core/composer.json`. Sin esto el namespace PSR-4 no se autoloadea correctamente.
- Falta seeder de permisos: `DashboardController` usa `$this->authorize('settings.view')` y `$this->authorize('settings.system')` pero esos permisos no se crean. Crear `CorePermissionsSeeder` con `core.dashboard.view`, `core.dashboard.system`.
- Migraciones en Core no pertenecen a Core: `create_users_table`, `create_sessions_table`, `create_categories_table`, `create_notifications_table` son tablas globales. Mover.

**P1**
- Dashboard acopla 6 módulos directamente (Reviews, Attention, Forms, Auth, Spatie\Activitylog, Modules\Core). Cada KPI hace `class_exists()` defensivo. Refactorizar a `DashboardKpiCollectorService` event-driven.
- `DashboardController::kpis()` con SQL crudo masivo (`selectRaw` con `CURDATE()`, `DATE_SUB`, `TIMESTAMPDIFF`) — solo MySQL/MariaDB, incompatible con PgSQL/SQLite (rompe tests in-memory).
- Sin tests del DashboardController — 12 endpoints sin coverage.
- Helpers globales sin tests: faltan `DateHelper`, `DataFormatHelper`, `LocalizationHelper`, `SiteHelper`, `UrlValidationHelper`, `RateLimitHelper`, `CircuitBreaker`, `HttpClientService`.
- `HealthCheckController` duplica lógica con `Health\HealthController::ping/health`.
- Inline styles en dashboard (`Core/resources/views/dashboard/index.blade.php`).

**P2**
- Modelo `Countrie` mal nombrado (debería ser `Country`).
- `SystemCleanup` y `GeoIpCheck` agendados a daily sin `withoutOverlapping()` ni `onOneServer()`.
- Cache keys sin namespace: `dashboard:kpis:`, `dashboard:alerts`, `dashboard:recent_activity` — colisiones potenciales.
- `Auth\Models\Session as UserSession` importado — confirmar ruta correcta.
- Faltan factories para `Setting`, `Lang`, `Countrie`, `IpLocation`.
- `registerSchedules()` usa `app->booted()` dentro de `boot()` — usar `callAfterResolving(Schedule::class, ...)`.

**P3**
- Sin idiomas (`resources/lang` no existe). Strings hardcoded en español en controller.
- `registerTranslations()` carga `resources/lang` pero el directorio no existe.
- README desactualizado.
- `registerConfig()` publica configs pero no los `mergeConfigFrom`.

#### Módulo: System

**Estado actual.** Módulo de configuración (priority 110). ~13 controllers (Settings/SystemSettings, SystemCache, ServerAccess, Supervisor, Maintenance, Uploading, Settings, SettingsPanel, Localization, Search, Categories, Langs, Translation + SystemInfo, ApiDoc, GlobalSearch, Import). Servicios: SystemInfoService, GlobalSearchService, GlobalSearchRegistrar, SupervisorService. 5 archivos tests. 4 Form Requests. Sin migrations, policies, factories, seeder de permisos, lang. composer.json requiere `torann/geoip` que parece duplicado con Core.

**P0**
- Sin seeder de permisos pero el módulo gestiona supervisor, cache clear, queue restart, env writes — operaciones extremadamente sensibles. Existe `AuditPermissionsCommand` pero no seeder. Crear `SystemPermissionsSeeder` con: `system.cache.clear`, `system.cache.write`, `system.supervisor.read`, `system.supervisor.execute`, `system.queue.manage`, `system.maintenance.toggle`, etc.
- `SystemSettingsController` usa `$request->validate()` inline en `updateQueue`, `updateWebsockets`, `testQueue`. Crear FormRequests.
- `MaintenanceSettingsController`, `UploadingSettingsController`, `SettingsController`, `LocalizationSettingsController`, `TranslationController`, `SettingsPanelController` — confirmar que todos usen FormRequests.
- `CategoriesController` no está registrado en routes/web.php — código muerto.
- `write_env()` reescribe `.env` directamente sin atomicidad ni backup. Race condition.
- `SystemCacheController` permite ejecutar `composer dump-autoload` desde web — peligroso.
- Routes con prefix raro `panel/setting/system` (singular).

**P1**
- `SupervisorController` ejecuta `supervisorctl start/stop/restart` requiere `sudo`. Hay `restart-all`, `restart-service` que afectan TODA la máquina.
- Faltan tests para SystemSettings, SystemCache, SystemInfo, MaintenanceSettings, UploadingSettings, ServerAccess (download/clear logs), LocalizationSettings, Translation, Langs.
- `ImportController` y `GlobalSearchController` registrados en `routes/web.php` raíz — migrar al módulo.
- `ApiDocController` registrado fuera del prefix del módulo (`/api-docs/`).
- `Tests/` con T mayúscula.
- `MaintenanceMiddleware` y `LanguageMiddleware` sin tests.
- `SettingsHelper` y `EnvironmentHelper` sin tests.

**P2**
- `SystemSettingsController::getQueueSettings()` y `getWebsocketsSettings()` privados sin type-hint.
- `writeEnv()` para multiples keys — refactorizar.
- `SupervisorController::ALLOWED_COMMANDS` debería validar contra `command:reload`, `route:cache`.
- `SupervisorService::supervisorctlBin()` y `confDir()` no funcionan con paths no estándar.
- Falta `config/system.php` — el SP lo publica pero el archivo no existe en módulo.
- `StoreCategorieRequest`/`UpdateCategorieRequest` con typo.
- `CategoriesController::generate_uid()` viene de un trait global no documentado.
- Faltan policies.

**P3**
- README existe.
- Inline styles en 11 vistas.
- Faltan idiomas — strings hardcoded en 13 controllers.
- `SupervisorController::ALLOWED_COMMANDS` permisiva — documentar.
- `SupervisorService` returns `array` en muchos métodos — extraer DTOs.

#### Módulo: Database

**Estado actual.** Módulo de configuración y limpieza de BD (priority 85). 4 controllers: `DatabaseController` (CRUD genérico — abort 501 en metodos de escritura, vacío), `DatabaseSettingsController`, `DatabaseCleanupController` (truncate masivo). Servicio: `DatabaseConnectionTester`. Helper `QueryHelper`. **Tiene `CreateDatabasePermissionsSeeder`** correcto. **Sin tests**, sin policies, sin factories, sin migraciones, sin Form Requests, sin lang.

**P0**
- `DatabaseCleanupController::truncate` es operación catastrófica — hasta 50 tablas en una llamada con `SET FOREIGN_KEY_CHECKS=0`. Sin tests. Sin confirmación token. Si bien hay feature flag (`cleanup_enabled`), una vez activado un usuario con permiso `database.cleanup.truncate` puede destruir BD entera. Añadir: confirmación con password, exclusión explícita de tablas críticas (`users`, `password_resets`, `sessions`, `failed_jobs`, `permissions`, `roles`), backup automático previo.
- Sin tests para ningún controller — alto riesgo sin cobertura.
- `DatabaseCleanupController::truncate` y `getTableCount` usan `$request->validate()` inline.
- `DatabaseSettingsController::update` usa `$request->validate(Setting::getDatabaseRules())` inline.
- `DatabaseSettingsController::update` reescribe conexión DB pero NO valida con conexión en vivo antes de guardar — usuario puede dejar app sin acceso a su BD.

**P1**
- `DatabaseController` es código muerto — 7 métodos retornan vista o `abort(501)`. Eliminar o implementar.
- Routes API en `api.php` usan `apiResource` con un `DatabaseController` que no implementa nada.
- `DatabaseServiceProvider::loadDynamicDatabaseConfig()` intenta sobrescribir conexión DB desde `settings` — dependencia circular.
- `config/config.php` solo tiene `cleanup.enabled` — agregar lista de tablas excluidas, conexiones permitidas, timeouts.
- Faltan policies.
- `registerMenus()` referencia `settings.database.cleanup.index` — verificar ocultar cuando feature flag off.

**P2**
- `prefix('panel/setting/database')` vs convención plural.
- `DatabaseConnectionTester` sin tests.
- Sin idiomas.
- `getTablesList()` con `LIMIT 200` — silently ignora tablas extra.
- `activity()->log()` dentro del controller.

**P3**
- README existe.
- `DatabaseDatabaseSeeder` (typo nombre) — está vacío.
- Inline styles en `cleanup/index.blade.php`, `settings/index.blade.php`, `cleanup/disabled.blade.php`.
- `require setasign/fpdi-tcpdf` innecesario para módulo de DB.
- `require doctrine/dbal` solo necesario para `change()` en migrations — sin migrations aquí.

#### Módulo: Health

**Estado actual.** Módulo de monitoreo Spatie Laravel Health (priority 100). 3 controllers: `HealthController` (web + api), `Api/HealthController` (duplicado parcial), `AlertThresholdController`. Modelo `AlertThreshold`. 2 mailables, 2 notifications, 2 commands. 3 checks customs. 2 view components. Service: `AlertThresholdService`. 2 Form Requests. Migración crea solo `health_check_result_history_items`. **El namespace en composer.json es `Modules\HealthCheck` pero el folder y todo el código usa `Modules\Health`** — bug crítico.

**P0**
- **MISMATCH NAMESPACE**: `composer.json` declara `"Modules\\HealthCheck\\": "app/"` pero código real es `Modules\Health\...`. Rompe autoload.
- Falta migración `create_alert_thresholds_table` en el módulo Health (existe en raíz).
- Falta seeder de permisos: `AlertThresholdController` no usa `$this->authorize()` ni Spatie permissions; rutas en `routes/web.php` raíz sin middleware Spatie.
- `AlertThresholdController` rutas en `routes/web.php` raíz fuera del módulo — registrar dentro del módulo.
- Sin tests — 0 tests para módulo que envía alertas, ejecuta queue/schedule, genera Supervisor configs.
- `HealthController::generateSupervisorConfig` ejecuta artisan command que escribe archivos — sin permission check Spatie.
- `HealthController::detailed` y `health` exponen datos sensibles — token solo verifica `Bearer`/query, throttle 30/min permisivo.

**P1**
- Duplicación `HealthController` (web) vs `Api/HealthController` — los dos tienen `ping()` y `health()`. Api/HealthController NO está registrado — código muerto.
- Cero tests para checks customs `DatabaseCheck`, `RedisCheck`, `StorageCheck`.
- Sin tests para `AlertThresholdController` y sus 2 Form Requests.
- Sin tests para `HealthCheckAlertCommand` y `CheckAlertThresholdsCommand`.
- `AlertThresholdService` sin tests.
- `AlertThreshold` model sin policy — cualquier usuario podría editar alertas de otros.
- Vendor publish — verificar carga del path.
- `isRealProduction` heurística string-matching `.test`/`.local` — usar `app()->environment('production')`.
- `config/health.php` valor `to => 'your@example.com'` placeholder.

**P2**
- `HealthController::history` retorna view o JSON según `wantsJson` — separar.
- Inline styles en `settings/index.blade.php`, `settings/history.blade.php`, `settings/alerts/index.blade.php`.
- Sin idiomas.
- `AlertThresholdController::store` y `update` repiten lógica.
- `AlertThreshold` con `casts()` correcto pero sin scopes (`scopeActive`, `scopeOfType`).
- `HealthController::queueStatus` parsea `ps aux` con `stripos` — frágil.
- Throttle `api/health` en 30/min — para load balancer es bajo.

**P3**
- README existe.
- `AlertThreshold` model sin `HasFactory`.
- `AlertThresholdController::parseRecipients` podría validar formato email.
- Component Logo / StatusIndicator sin tests.

#### Módulo: Modules

**Estado actual.** Módulo de gestión de módulos (priority 10). 1 controller (`ModulesController`) con 9 métodos. 2 services. 2 commands. 2 Form Requests. 1 middleware. Tiene `ModulesPermissionsSeeder` con permission `modules.manage` asignada a `super-settings`. 1 test feature `ModulesControllerTest` con ~30 tests (excelente cobertura). Sin lang files. Sin migraciones propias. Sin policies. Sin factories.

**P0**
- `ModuleService::install` desempaqueta ZIP en `storage/temp/modules` y mueve a `base_path('Modules')` — pero convención es `modules/` minúscula. Usar `module_path()` helper.
- `ModuleService::install` ejecuta `module:migrate --force` automático — peligroso. Hacer dry-run + listado de migraciones nuevas + confirmación.
- `InstallModuleRequest` valida pero el endpoint solo verifica `modules.manage` — un module ZIP malicioso puede contener providers que se ejecuten al `register`. Añadir verificación de checksum/firma.
- `uninstall` ejecuta `module:migrate-rollback` y borra el directorio sin backup previo.
- `enable`/`disable` no verifica dependencias entre módulos — si Theme se desactiva, todos los módulos que llaman `NavService::registerSidebar` fallan en boot.

**P1**
- No expone API pública para gestión de módulos (api.php documentado por seguridad). OK.
- Falta policy `ModulesPolicy` — todo va por `$this->authorize('modules.manage')` directo.
- `coreModules = ['Role', 'Modules']` hardcoded en `disable` y `uninstall`. Core, System, Database, Health no protegidos — desactivarlos rompería el sistema.
- Faltan tests para `InstallModuleRequest` validación tamaño max ZIP, content-type real, `ModuleService::deleteDirectory`, `ModuleConfigReader`, `EnsureModuleEnabled` middleware.
- `registerMenus()` usa `addItemsToSection` mientras los demás usan `registerSidebar` — inconsistencia.

**P2**
- Sin idiomas.
- `ModuleService::install` no maneja módulos que requieran composer dependencies.
- `ModuleService::install` no registra en `bootstrap/providers.php` ni en `composer.json` raíz.
- `updateConfig()` reescribe `module.json` sin schema validation.
- `InstallModuleRequest::authorize()` retorna `true` — debe verificar `$this->user()->can('modules.manage')`.
- Convención de routes: `panel/setting/modules` (singular).

**P3**
- README existe.
- `buildModuleData` no incluye `requires` del module.json.
- `ModulesStatusCommand` y `ToggleModuleCommand` sin tests.
- `ModuleService::deleteDirectory` usa `@unlink`/`@rmdir` (silencia errores).
- `registerCommandSchedules` vacío — código muerto.

#### Módulo: Activity

**Estado actual.** Módulo de auditoría sobre `spatie/laravel-activitylog` (priority 0). 1 controller `ActivityController`. 1 service `ActivityLogService`. 1 model `ApplicationLog`. 1 command `PruneActivityLogsCommand`. config/activity.php. 1 test feature `ActivityStatsEndpointTest` (4 tests). **Sin migraciones** (la tabla `activity_log` se crea en `Core/database/migrations/`). Sin Form Requests. Sin policies. Sin factories. Sin lang. Sin seeders de permisos. **Permisos usados con TitleCase**: `Activity.logs.index` viola convención lowercase.

**P0**
- Convención de permisos rota: usa `Activity.logs.index` en `authorize()`. Crear `ActivityPermissionsSeeder` con `activity.logs.view`, `activity.audit.view`, `activity.logs.export`, `activity.logs.delete`.
- Sin seeder de permisos: el test crea el permiso al vuelo con `Permission::firstOrCreate`.
- `ActivityController::bulkAction` valida con `$request->validate()` inline. Crear `BulkDeleteActivityRequest`.
- `ActivityController::export` filtros sin Form Request — `whereDate('created_at', '>=', $request->from)` sin validar formato.
- `ActivityController::auditData` mismos filtros sin validar.
- El módulo no tiene migración propia para `activity_log` — la tabla se crea en Core (acoplamiento).

**P1**
- `registerMenus()` referencia rutas `activity.logs` y `activity.audit`. Sidebar `'settings'` mezclado.
- `logs` y `audit` retornan vistas casi idénticas — consolidar.
- `bulkAction` solo maneja `delete` — usar `match()`.
- `auditData::map` no en API Resource — crear `ActivityResource`.
- `logs/export` con `chunk(500)` y throttle `10,1` — para 100K rows mover a queue + email link.
- Faltan tests para `logs`, `audit`, `export`, `show`, `bulkAction`.
- `ActivityLogService` sin unit test directo.
- `PruneActivityLogsCommand` sin tests; lógica de retention crítica.
- `ApplicationLog` model sin uso aparente — código muerto?

**P2**
- `paginationNumber()` helper global sin source explícito.
- Cache key `activity:event_stats` y `activity:count-by-event` — keys distintos para misma cosa.
- `eventStats()` privado vs `countByEvent()` en service — duplicación.
- No hay tests para `actingAs($user)->getJson('activity.audit.data')`.
- Sin policy.
- No hay registro de `bulkAction` en routes — verificar.
- Inline styles en `logs/index.blade.php` y `audit/index.blade.php`.

**P3**
- `prefix('panel/activity')` OK pero podría ser `panel/{alias}` más explícito.
- Sin idiomas.
- README existe.
- `config/activity.php` retention 365 días — confirmar uso por command.
- Spanish CSV headers.

#### Módulo: Pulse

**Estado actual.** Módulo Laravel Pulse (priority 90). **DESHABILITADO en `modules_statuses.json`**. 2 controllers. 3 providers (`PulseServiceProvider` deprecated, `EventServiceProvider`, `RouteServiceProvider`). config/pulse.php (custom con `Module::find('Pulse')->isDisabled()`). Migración Pulse oficial. Seeder vacío. Routes en web/api/settings (3 archivos). Layout en `components/layouts/master.blade.php`. **Sin Form Requests, sin policies, sin factories, sin tests, sin seeder de permisos, sin lang**. Composer.json declara `laravel/pulse: ^1.4`.

**P0**
- `PulseSettingsController::update` usa `$request->validate()` inline con 13 reglas.
- Sin seeder de permisos: usa `Gate::define('view-pulse')` y `Gate::define('manage-pulse-backups')`. Crear `PulsePermissionsSeeder` con `pulse.dashboard.view`, `pulse.settings.update`.
- `PulseServiceProvider extends EventServiceProvider` marcado deprecated pero `module.json` lo declara como provider principal.
- Routes API `apiResource('pulses', PulseController::class)` con `auth:sanctum` apunta al controller que solo tiene `index()` retornando view — métodos REST faltantes tirarían 405.
- Sin tests.

**P1**
- `module.json` no declara `requires: ["Theme"]` pero usa `NavService` de Theme.
- Menú con titulo "Monitoreo del sistema" — colisión con otros módulos `registerSidebar('settings')`.
- `PulseSettingsController::index` lee 22+ keys de Settings.
- `Setting::clearPrefixCache('pulse.')` depende de método custom en Core.
- `config/pulse.php` con `Module::find('Pulse')?->isDisabled()` evaluado en runtime.
- **`vendor/pulse/dashboard.blade.php` usa `<livewire:pulse.servers />`** — el proyecto NO usa Livewire (`CLAUDE.md`). Esto es conflicto fundamental — razón del disable.
- `composer.json` requiere `livewire/livewire` indirectamente vía Pulse.

**P2**
- `registerCommands()` y `registerCommandSchedules()` vacíos.
- Sin idiomas.
- README existe — verificar si documenta conflicto Livewire.
- `PulseController::index` solo autoriza `view-pulse` pero no muestra mensaje cuando module disabled.
- Inline styles en `settings/index.blade.php`.

**P3**
- `registerConfig()` con recursive iterator — overkill.
- `config/config.php` solo `'name'`.
- Component blade `master.blade.php` — si no se usa, borrar.

**Recomendación fuerte:** Mantener deshabilitado. Pulse requiere Livewire que contradice convención del proyecto. Opciones:
1. Re-implementar las vistas Pulse en jQuery/AJAX (gran esfuerzo).
2. Aceptar Livewire solo para `/panel/pulse` aislado (riesgo de conflicto).
3. Reemplazar Pulse con dashboard custom usando datos crudos de `pulse_*` tables.

### Visual/Misc

#### Módulo: Template

**Estado actual.** Módulo grande y central. Combina 3 entidades en uno: Templates (plantillas/temas web), Menus (navegación frontend) y Shortcodes (registro en BD). Motor de tema (Botble-like) con facades, versionado de templates, import desde ZIP, custom CSS/JS/HTML por settings, registro dinámico de shortcodes desde BD al compilador del módulo Shortcode.
- 9 controllers (Template, Menu, Shortcode, ShortcodeCategory, CustomCss, ThemeOption, ThemeCustomJs, ThemeCustomHtml, TemplateWeb)
- 6 modelos (Template, TemplateVersion, Menu, MenuItem, Shortcode, ShortcodeCategory)
- 3 policies (Template, Menu, MenuItem) — falta `ShortcodePolicy`
- 7 Form Requests, 5 factories, 4 seeders, 7 tests Feature
- Lang `es/en`

**P0**
- `ShortcodeController` validación inline + falta Form Requests — `store()` y `update()` validan inline 12+ reglas duplicadas. Crear `StoreShortcodeRequest` y `UpdateShortcodeRequest`.
- `TemplateController::preview` y `importZip` validan inline. Mover a `PreviewTemplateRequest` y `ImportTemplateZipRequest`.
- Falta `ShortcodePolicy` — controllers usan `$this->authorize('create', Shortcode::class)` pero no hay policy registrada.
- Falta `ShortcodeCategoryPolicy`.
- `TemplateController::postActivateTemplate` y `postRemoveTemplate` con dos rutas POST sin permiso granular — riesgo escalación: cualquier user con `template.update` puede activar cualquier template.
- Permissions seeder usa singular (`template.view`, `menu.view`, `shortcode.view`) pero routes anuncian `panel/settings/templates` (plural).
- Falta permiso `template.custom-code` en seeder.
- `registerDynamicShortcodes()` en ServiceProvider corre en `app->booted()` con query a BD en CADA request. Cachear con `Cache::remember('template.dynamic_shortcodes', 3600, ...)`.

**P1**
- Sin tests para `MenuController::getNode/references/updateStructure/storeItem/updateItem/destroyItem`.
- Sin tests para `CustomCssController`, `ThemeOptionController`, `ThemeCustomJsController`, `ThemeCustomHtmlController`, `ShortcodeCategoryController`.
- Sin API endpoints REST (`routes/api.php` vacío).
- Sin `TemplateResource`, `MenuResource`, `ShortcodeResource`.
- `registerDynamicShortcodes` ejecuta `e($content)` (línea 375) pero acepta HTML — escapa doblemente.
- `MenuController::index` usa `withCount('allItems')` y carga todos los menús sin paginar.
- Activity log ausente en Template, Menu, Shortcode.
- `ServiceProvider::registerDynamicShortcodes` swallow exceptions.
- `importZip` no valida tamaño total tras descompresión (zip-bomb).
- `Template::getInheritanceChain` con cycle detection — debería loguear violations.

**P2**
- `MenuController::store` usa `Str::slug` en controller — mover a `StoreMenuRequest::passedValidation()`.
- Inline styles en `settings/form.blade.php`, `settings/show.blade.php`, `settings/import.blade.php`, `settings/menus/edit.blade.php`, `shortcodes/form.blade.php`.
- `TemplateController::store` y `update` con try/catch genérico que oculta errores.
- Routes mezclan templates/menus/shortcodes/themes en `web.php` sin separar — crear archivos separados.
- `Shortcode` no tiene factory state para shortcodes con `render_template`.
- `TemplateService::activate` no es atómica.
- Sin events: `TemplateActivated`, `TemplateRestored`, `MenuStructureUpdated`, `ShortcodeRegistered`.

**P3**
- Mover `helpers/MenuHelper.php` a `app/Helpers/`.
- `protected string $name` y `$nameLower` deberían ser `final` o constants.
- Documentación en `composer.json::description` vacía.
- No existe README del módulo.
- Agregar `php artisan template:list` artisan command.
- Variables de preview hardcodeadas en `TemplateController::preview`.

**Notas técnicas:** El mezclar Templates+Menus+Shortcodes+Theme engine en un solo módulo viola SRP. Considerar split: `Template` (solo plantillas), `Menu` (módulo aparte), `Shortcode` (ya existe pero esto duplica con BD-based).

#### Módulo: Theme

**Estado actual.** Módulo "support" sin entidades de BD: provee layouts base (`layouts.theme`, `layouts.auth`), partials, assets públicos (CSS/JS de plantilla Modernize) y `NavService` (servicio centralizado de navegación usado por TODOS los módulos). Incluye `AssetController` para servir assets directamente.
- 1 controller (`AssetController`)
- 1 service core (`NavService`) — usado por TODOS los módulos
- 1 console command (`AuditA11yCommand`)
- 0 modelos, 0 migraciones, 0 factories, 0 seeders, 0 tests, 0 lang

**P0**
- **CERO TESTS para `NavService`** (615 líneas): es el componente CENTRAL de navegación del proyecto. Romperlo afecta TODOS los módulos. Crear `tests/Unit/NavServiceTest.php` cubriendo: `registerMiniItem`, `registerSidebar`, `addItemsToSection`, `findActiveSidebarForUser`, `findBestMatchingItemRoute`, permission filtering, `flush()`.
- CERO TESTS para `AssetController`: público y serves files. Test path traversal, MIME types, 404.
- Sin permissions seeder ni `module.view.theme`: NavService usa `modules.view.{moduleId}` como permiso (línea 290) pero el seeder de Theme no lo crea.
- `NavService` usa state estático (`private static $menus`) — en tests requiere `flush()` manual.

**P1**
- `AssetController::asset` cache-control 1 año sin versioning de assets.
- Falta theme switcher: settings page para cambiar layout/colorTheme/dark mode runtime.
- Falta dark mode toggle funcional persistente (cookie/setting).
- Falta `lang/` files: strings como "Ubicaciones", "Configuraciones" hardcoded.
- Falta `composer.json::description`.
- `NavService::userCanAccessModule` usa `$user->hasPermissionTo($permissionName)` sin try/catch — si permiso no existe, lanza `PermissionDoesNotExist`.
- MenuServiceProvider existe pero solo provee NavService — consolidar con `ThemeServiceProvider`.

**P2**
- Theme assets servidos vía controller (HTTP cycle completo) en lugar de symlink.
- Layout `theme.blade.php` carga 11 librerías CSS y N JS en cabecera sin defer/async.
- Sin tests del flujo completo nav (mini + sidebar) renderizado para distintos roles.
- `AuditA11yCommand` sin test.
- `MenuServiceProvider` en `module.json` pero contenido no leído.

**P3**
- Crear `config/theme.php` con keys `default_color`, `default_layout`, `available_themes`.
- Documentación `README.md` con flujo de NavService.
- Nombrar métodos de NavService consistentes.
- `theme/libs/` 200+ MB de libs vendored — documentar gitignore.
- Mover `helpers/helpers.php` a un Service.

#### Módulo: Media

**Estado actual.** Módulo MUY GRANDE y maduro: gestor de archivos enterprise-grade. Multi-disk, multi-folder, versioning, comments, shares (token-based), workflow (submit/approve/reject), tags morphic, locks colaborativos, cloud import (Drive/Dropbox/OneDrive), AI auto-caption/auto-tag/PII detection con drivers (OpenAI, Claude, Google Vision, Whisper), webdav gateway, sprite sheets, srcset generation, retention/GDPR commands, Pulse recorders, audit signature observer (HMAC).
- 14 controllers (incl. 3 API)
- 11 modelos
- 3 policies (`MediaFile`, `MediaFolder`, `MediaTag`)
- 13 Form Requests, 4 factories, 4 seeders
- 17 jobs, 6 events, 1 notification, 1 mail
- 6 contracts + 13 drivers (AutoCaption, AutoTagging, Cloud, PiiDetection, SmartCrop, Transcription)
- 15 console commands
- 7 tests Feature + 1 Unit (en `tests/Feature/Media/`)

**P0**
- `MediaController::getList` ejecuta queries diferentes por `$view` con duplicación de lógica (442 líneas total). Mover toda la lógica al `MediaFileService` o nuevo `MediaListingService`.
- Inline validación en multiples controllers (`MediaController`, `MediaCommentController`, `MediaEmbedController`, `MediaFileController`, `FolderTemplateController`, `WebDavController`).
- Falta `MediaSharePolicy`, `MediaCommentPolicy`, `MediaWorkflowPolicy`, `FolderTemplatePolicy`.
- `PublicMediaController::show` recibe `{hash}/{id}` — verificar que valida HMAC del hash contra ID (timing-safe).
- `MediaFileController::uploadChunk` no presente en Form Request.
- Permisos seeder asigna a "admin/super-admin/super-settings" — verificar consistencia.
- Sin `media.workflow.*`, `media.tag.*`, `media.comment.*`, `media.share.*` permissions específicos.

**P1**
- Repositorios + Interfaces + Services patron mixto que confunde.
- `MediaFile::booted()` setea `user_id = auth()->id()` — si jobs/console crean files sin auth, `null`.
- N+1 potencial en `getList` favorites/recent.
- Sin tests para Workflow, Tags, Comments, Shares, Locks, CloudImport, FolderTemplate, Moderation, Embed, MediaShare.
- `api/media/health` (`MediaHealthController`) sin auth — auditar exposición.
- `UploadFromUrl` valida URL con `RateLimiter` per-user, pero auditar SSRF.
- Lang `es/en/media.php` con gaps — strings hardcoded en `MediaController` (`'Papelera'`, `'Favoritos'`, `'Recientes'`).
- `alt` text NO obligatorio en uploads (a11y).
- Activity log activo solo en `MediaFile`, no en MediaFolder/MediaShare.
- Falta srcset responsive automático (existe `GenerateSrcsetCommand` manual).

**P2**
- `view/index.blade.php` con CSS inline custom + bug en línea 25: `background: #90bb13, var(--stat-color));` — coma extra, malformed CSS.
- Carpeta `vendor/` dentro del módulo con autoload.
- `config/media-library.php` y `config/media.php` separados — consolidar o documentar.
- Bulk action via `bulkAction` en `MediaController`: validación en `BulkActionRequest`. Verificar acciones soportadas.
- `MediaFile::isFavoritedBy` ejecuta query separada — N+1 en getList.
- `PublicMediaController::showShared` requiere token revocation check.
- Drivers (OpenAI/Claude/etc.) sin tests — al menos `NullDriver` debe verificarse.
- No CDN integration documentada.

**P3**
- `MediaPermissionSeeder` asigna a 3 roles con `whereIn` y `givePermissionTo($permissions)` pero solo `firstOrCreate` el primero.
- Sin Resource Collection o explicit pagination meta.
- Falta `UseDeepLDescription` integration para alt text traducido.
- Documentación de drivers AI faltante.
- `MediaFile::fillable` incluye `metadata` json sin estructura tipada.

**Notas técnicas:** Módulo bien arquitectado. Riesgo alto en lógica de hashing/dedup (`file_hash` + `phash` perceptual hash). Auditoría de actividades firmada con HMAC — buena práctica de tamper-proofing. 17 jobs — verificar `failed()` method en todos.

#### Módulo: Shortcode

**Estado actual.** Compilador de shortcodes (estilo WordPress: `[alert type="warning"]...[/alert]`). Compilador, helpers, facade `Shortcode`, panel admin (listado, referencia, tester visual), API REST con rate limiter custom, eventos (Compiling, Compiled, Registered), Listener (RecordShortcodeUsage), service de stats de uso, 8 console commands, Pulse Telescope watcher.
- 1 controller (`ShortcodeController` web+api)
- 0 modelos en este módulo (los `Shortcode` BD-based están en Template)
- 3 Form Requests (`Compile`, `Strip`, `Check`)
- 6 tests Feature + 5 tests Unit (¡EXCELENTE COBERTURA del compiler!)
- Lang `es/en/shortcode.php`
- Permissions: solo `shortcode.view`, `shortcode.manage`

**P0**
- `ShortcodeController` mezcla lógica de presentación con BD: `class_exists(\Modules\Template\Models\Shortcode::class)` — acoplamiento inverso.
- Doble fuente de shortcodes: PHP-registered + BD-based (Template). Documentación del orden de precedencia opaca.
- Sin permission `shortcode.create`/`update`/`delete` — solo `view` y `manage`.
- `compilePreview` (web) duplica `compile` (api) — uno con throttle:120, otro con shortcode-api throttler.

**P1**
- `ShortcodeController` no tiene Form Request para `resetStatsWeb`/`stats`/`clearCache`.
- Routes web prefix `panel/setting` (singular) y name `setting.` — INCONSISTENTE con plural.
- No registra mini-item en NavService.
- Sin tests para el panel admin (index, reference, tester views).
- Sin tests E2E para `/api/shortcodes/compile` con auth Sanctum.
- Documentación de shortcodes: `IntegrationShortcodes` y `LogicShortcodes` deben tener `description`/`example`/`attributes` consistentes.
- Pulse `ShortcodeWatcher`: agregar test que confirma metricas.

**P2**
- Compilador permite contenido no-confiable. Falta hook automático para sanitizar con HTMLPurifier.
- `registered` endpoint expone metadata completa.
- CSP nonce support (`withCspNonce`) bien implementado pero ningún shortcode default lo usa.
- `MAX_CONTENT_SIZE = 1MB` hardcoded — mover a config.
- `alias_of` y `deprecated` sin test directo.

**P3**
- README ausente.
- `composer.json::description` vacía.
- `tests/Feature/ShortcodeHelperTest.php` vs `ShortcodeHelpersTest.php` — consolidar nombres.
- `ShortcodeMakeCommand` generador debería incluir test stub.

#### Módulo: Locales

**Estado actual.** Módulo de gestión multi-idioma. Maneja entidad `Locale` (codigo, nombre, native_name, flag, RTL, is_default, is_active, order) y traduce archivos lang del tema activo via UI (`ThemeTranslationController`). Integra DeepL como servicio de traducción automática (cached 24h). Provee `LocaleService` con cache y 2 console commands.
- 2 controllers (`LocaleController`, `ThemeTranslationController`)
- 1 modelo (`Locale`)
- 3 services (`LocaleService`, `DeepLTranslationService`, `ThemeTranslationService`)
- 2 Form Requests (`StoreLocale`, `UpdateLocale`)
- **0 policies, 0 factories, 0 tests, 0 lang files, 0 PERMISSIONS SEEDER**

**P0**
- **CERO TESTS** — módulo crítico para multi-idioma sin cobertura.
- **NO HAY `LocalesPermissionsSeeder`**: el menú exige `locale.view` (línea 140-141) pero no se crea — NavService::userCanAccessItem fallará con `PermissionDoesNotExist`.
- **`StoreLocaleRequest::authorize` retorna `true`** SIN VERIFICAR PERMISO. Mismo en `UpdateLocaleRequest`. CRÍTICO.
- **CERO POLICIES** (Locale, ThemeTranslation): ninguna ruta usa `$this->authorize()`. Cualquier usuario autenticado puede modificar idiomas globales.
- `LocaleController::index` sin `authorize`. Mismo en todos los métodos.
- `ThemeTranslationController` recibe `{group}` parameter en URL y lo usa para escribir archivos en filesystem — verificar path traversal: `{group}` puede ser `../../etc/passwd.json`.
- **CERO factories**: tests no podrán crear Locales fácilmente.
- Falta `lang/es` y `lang/en` del módulo: strings hardcoded en español.

**P1**
- `bulkAction` valida sin Form Request (33-77 inline).
- `storeSettings`, `setDefault`, `toggle` sin Form Requests.
- `DeepLTranslationService::__construct` lee config y guarda en propiedad — testing requiere reinstanciar.
- `DeepLTranslationService` no maneja rate limit DeepL (429).
- `DeepLTranslationService::translate` no chunk-encodea texts.
- No hay endpoints API REST.
- Ruta de `bulk-action` y `config` antes de `{locale}` en routes — falta `where(['locale' => '[0-9]+'])`.
- `ThemeTranslationService` lee/escribe filesystem direct sin lock concurrente.
- `LocalesServiceProvider` hace reflection sobre translator.

**P2**
- Tabla `locales` y tabla `langs` legacy coexisten — `Locale::resolveLegacyLangId()` mapea entre ambas.
- `Locale::getDefault()` devuelve `?static` — bien tipado.
- No hay event `LocaleCreated`, `LocaleDefaultChanged`, `TranslationUpdated`.
- `presetLanguages()` debe estar como config o JSON estático.
- `SyncLangsCommand` y `TranslationsMissingCommand` sin tests.
- `LocaleObserver` sin test.

**P3**
- README ausente.
- `config/config.php` solo tiene `'name' => 'Locales'`.
- `v5.blade.php` view en root resources/views — nombre poco descriptivo.
- `composer.json`/`module.json` description vacía.
- Falta auditoría con `Spatie\ActivityLog`.

#### Módulo: Locations

**Estado actual.** Módulo CRUD básico de jerarquía geográfica: Country → State → City. Vistas siguen patrón index/create/edit estándar con bulk actions. Seeder con 22 países hispanohablantes + emojis bandera.
- 4 controllers (`Country`, `State`, `City`, `LocationImport`, `LocationApi`)
- 3 modelos (`Country`, `State`, `City`)
- 6 Form Requests (Store/Update por entidad)
- **0 policies, 0 factories, 0 tests, 0 services, 0 lang files, 0 events/listeners/jobs**
- 1 permissions seeder + 1 countries seeder

**P0**
- **CERO TESTS** para módulo CRUD con relaciones jerárquicas.
- **API endpoints SIN AUTH** (`routes/api.php` líneas 6-13): middleware solo `['api']`. Cualquiera puede listar países/estados/ciudades.
- **API responses sin Resources**: devuelve modelos crudos. No estandariza `{success, data, message}`. No paginación.
- Inline validation en `LocationApiController` (líneas 27, 41).
- Inline validation en `bulkAction` de los 3 controllers — idéntico, crear `BulkLocationActionRequest`.
- **CERO POLICIES**: usa `$this->authorize('locations.countries.view')` (string permission directo).
- **CERO factories**.
- `LocationImportController` no auditado — verificar SSRF/path traversal en import.
- `destroy` no protege países con states/cities asociados — hard cascade puede dejar FKs huérfanos.

**P1**
- Sin `country.code` ISO 3166-1 alpha-2 vs alpha-3 enforcement: `code` max:10 demasiado permisivo.
- `StateController` y `CityController` idénticos a `CountryController` — extraer Trait o BaseController.
- No hay search por phone_code o currency_code.
- No hay endpoint API para detectar país desde IP (GeoIP package disponible).
- No hay flag images (solo emoji).
- `order` field sin UI drag-drop.
- `CityController::index` sin filtros por country/state.
- N+1: index no eager-load `country` o `state` en `City` listing.
- `Country::states()` y `cities()` devuelven HasMany — falta `belongsToMany`.
- `Country` casts no incluye `phone_code` o `currency_code` como string explicito.
- Falta `lang/` del módulo.

**P2**
- Inline styles en `countries/index.blade.php`, `cities/index.blade.php`.
- `State` y `City` models no leídos pero presumiblemente igual a Country.
- Sin `LocationsServiceProvider::registerCommands` ni console command para sync países desde API externa (REST Countries API).
- Sin event `LocationImported` para que Ecommerce/Attention reaccionen.
- Sin cache en API endpoints públicos.
- `LocationsCountriesSeeder` solo 22 países hispanos.

**P3**
- README ausente.
- `config/config.php` solo `'name' => 'Locations'`.
- `composer.json::description` muy genérica.
- Sin Activity log.
- `module.json::priority = 0` — debe ser >0.
- Falta `php artisan locations:install`.
- No hay relación mórfica `addressable` para reusar en User, Customer, Supplier.

---

## Top 30 acciones priorizadas

Acciones de mayor impacto, ordenadas por ratio (riesgo evitado / esfuerzo).

### Acciones críticas inmediatas (esta semana)

1. **Eliminar auto-instalación de Supervisor desde HTTP en módulo Backup** (`BackupController::supervisorInstall/Apply/Restart` ejecutan `sudo` con password en stdin). Riesgo de root via privilege escalation. Reemplazar por guía de comandos copy-pasteables.
2. **Corregir namespace mismatch en `Health/composer.json`**: cambiar `"Modules\\HealthCheck\\"` a `"Modules\\Health\\"`. Sin esto el módulo no carga en producción.
3. **Corregir bug `AttentionPolicy` enum vs string**: `$attention->status === 'closed'` cuando el cast es enum — todas las comparaciones siempre fallan. Comparar con `=== AttentionStatus::CLOSED`.
4. **Corregir Form Requests con `authorize() { return true; }`**: Blog (Store/UpdateBlogPostRequest), Locales (Store/UpdateLocaleRequest), User (Store/UpdateUserRequest), Mailrelay (probable). Riesgo de bypass total.
5. **Fix `RoleController` sin `$this->authorize()`** — `RoleRequest::authorize` solo cubre store/update, dejando `index/show/edit/destroy/duplicate/clone/bulkAction/compare` accesibles a cualquier usuario con middleware `settings`. Crítico de seguridad.
6. **Renombrar `Page/composer.json` y `module.json` namespace `modules\\Page` → `Modules\\Page`** y `Sitemap/composer.json` igual. Sin esto producción Linux falla.
7. **Eliminar carpeta vendor `Mailrelay/Auth/` + `views/auth/` + scaffolding Laravel UI** del módulo Mailrelay. Es vestigio del package original y crea rutas conflictivas + registro libre de usuarios.
8. **Proteger `DatabaseCleanupController::truncate`** con backup automático previo + lista de tablas críticas excluidas (users, sessions, permissions, roles, failed_jobs, password_resets) + confirmación con password.

### Acciones de alta prioridad (próximas 2-4 semanas)

9. **Crear permission seeders faltantes** en: Cache, Optimize, MailsSettings, Captcha, Pulse, Health, Activity, Locales, Newsletter, User, Captcha, Theme, System (con `system.cache.clear`, `system.supervisor.execute`, etc.). Sin estos, autorización rota en instalación limpia.
10. **Estandarizar convención de permisos** a `{alias}.{action}` (2 segmentos) en TODO el proyecto. Refactor masivo afecta seeders + policies + Form Requests + middleware. Documentar la decisión en un ADR.
11. **Estandarizar convención de routes** a `panel/settings/{alias}` plural. Migrar Auth, User, Seo, Analytics, System, Database, Modules, Health, Backup, Shortcode con redirects 301.
12. **Migrar inline `$request->validate()` a Form Requests** en los ~150+ ocurrencias. Priorizar: Helpdesk core (~94), Attention God controller, Mailrelay (~24).
13. **Refactorizar `AttentionController` 2000 líneas** a 6+ controllers separados (PublicApi/AdminApi/StatsApi/SlaApi/BulkApi/ExportApi).
14. **Implementar `TODO export job`** en Attention — `ExportAttentionsJob` existe pero no se conecta a la ruta.
15. **Migrar Tabler Icons → FontAwesome 6** en HelpdeskTickets (`TicketViewSeeder` 11 ocurrencias, `TicketCategory::getIconAttribute()` default `ti ti-ticket`).
16. **Eliminar `theme: 'bootstrap-5'` en select2** en Helpdesk conversations create/edit (2 ocurrencias).
17. **Crear tests faltantes** para módulos sin coverage: Newsletter, MailsSettings, Cache, Database, Health, Pulse, Locales, Locations, Theme (NavService crítico), Storage feature tests.
18. **Implementar double opt-in en Newsletter** + link unsubscribe tokenizado + fix `QueryException` swallow + queue para Mailjet.
19. **Crear policies faltantes** para Reviews (12 modelos), Helpdesk core (22 modelos sin policy), HelpdeskTickets (TicketStatus, TicketCategory, TicketGroup), HelpdeskAgents (AiAgentTool/Tag/Knowledge), Notification (Notification model), Forms (FormSubmission), Attention.
20. **Eliminar `Settings AI` duplicado** entre Helpdesk core y HelpdeskAgents (decidir owner — recomendado HelpdeskAgents).

### Acciones de prioridad media (1-2 meses)

21. **Encriptar credenciales en MailsSettings** (SMTP, IMAP, Gmail tokens, Mailgun, phpList API keys) con `Crypt::encryptString` o `encrypted` cast. Implementar `state` parameter en Gmail OAuth2 callback (CSRF).
22. **Mover migraciones `Mailrelay/database/migrations/acas/`** a la raíz o eliminarlas; auditar y eliminar Entities huérfanos (Bounce, UnsubscribeEvent, MediaFile, etc.).
23. **Renombrar `Mailrelay\Entities\` → `Mailrelay\Models\`** (refactor masivo, alinear con convención v12).
24. **Mover `vendor/` commit dentro de módulos al composer raíz**: Optimize, Queue, Backup, Analytics, Media, Mailrelay.
25. **Refactorizar JS React/TSX en Helpdesk widget** + HelpdeskAgents flow editor a jQuery + AJAX (o aislar como microfrontend documentado).
26. **Decidir arquitectura Conversation vs Ticket** (ADR): consolidar en `helpdesk_threads` con `type` polimórfico, o extraer `HasMessageThread` trait.
27. **Decidir arquitectura Mailer vs Mailrelay** (ADR): hay overlap en Templates, Variables, Components, Endpoints. Documentación interna sugiere migración planeada.
28. **Crear API Resources faltantes** en módulos sin coverage API estandarizada: Auth, Notification, Activity, Reviews (faltantes), Attention (cero), Page (parcial), Mailrelay (parcial), Analytics (cero de 18), Cookie, Locations, Seo, Sitemap.
29. **Migrar inline styles a CSS** en views (~447+ ocurrencias). Priorizar Mailrelay (30+), Seo (329), Attention.
30. **Crear `lang/` files** en los ~25 módulos sin traducciones. Priorizar Auth, User, Role, Notification, Mailer, Activity, Pulse, System, Health, Database, Newsletter, Page, Locales, Locations, Theme.

---

## Notas finales

**Convenciones críticas a documentar como ADR (Architectural Decision Record):**
1. Convención de nombres de permisos (`{alias}.{action}` vs `{alias}.{entity}.{action}`).
2. Convención de prefix de rutas (`panel/settings/{alias}` plural).
3. Política de Form Requests (siempre, sin excepción inline).
4. Política de Policies (siempre con Spatie permission).
5. Estrategia ante overlap arquitectónico (Conversation/Ticket, Mailer/Mailrelay, Pixels Cookie/Analytics).

**Riesgos sistémicos detectados:**
- Sistema dispersa la responsabilidad de autorización entre middleware role, middleware permission, `Gate::define`, policies, Form Request `authorize()`. Hay módulos con bypass dobles (Captcha) y otros con cero protección (Role).
- Cero tests del NavService (componente compartido) significa que cualquier refactor afecta TODOS los módulos sin red de seguridad.
- Helpdesk family (4 módulos) tiene 4 convenciones de permisos distintas — refactor inmediato.
- Mailrelay parece un fork incompleto de un sistema externo (presencia de Auth scaffolding, vendor commit, sub-modules huérfanos, 8 archivos de planning markdown). Decidir destino estratégico.

**Módulos con calidad ingenieril alta** (modelo a replicar): Modules, Page, HelpdeskTickets, Optimize, Forms, Storage, Reviews, Mailer.

**Módulos con deuda técnica crítica** (intervención inmediata): Newsletter, Locales, Locations, Pulse, Mailrelay, Database, Health, Backup, MailsSettings.

---

_Reporte generado por auditoría automatizada de 9 agentes paralelos. Para acciones específicas o re-auditorías por módulo, ejecutar `/module-audit {ModuleName}` o solicitar análisis profundo de un dominio concreto._
