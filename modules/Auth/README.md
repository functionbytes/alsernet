# Módulo Auth

Autenticación, autorización y seguridad de la aplicación. Gestiona login/logout,
2FA, gestión de sesiones, tokens API, impersonación, auditoría, y más.

## Features

| Feature | Estado |
|---|---|
| Login / logout (sesión web) | ✅ |
| 2FA TOTP (RFC 6238) + recovery codes + PDF export | ✅ |
| Password reset (token, 48h expiry) | ✅ |
| Password policy (StrongPassword + history) | ✅ |
| Password caducidad (`must_change_password`, expires_in_days) | ✅ |
| Email verification (signed URL) | ✅ |
| Magic link login (passwordless) | ✅ |
| Account lockout persistente tras N fallos | ✅ |
| Session management (listar, revocar otros) | ✅ |
| Device fingerprinting + trusted devices | ✅ |
| Alerta email en nuevo dispositivo | ✅ |
| Lock screen (re-auth sin logout) | ✅ |
| Impersonation con audit log + notif al impersonado | ✅ |
| Email change con verificación (token al nuevo) | ✅ |
| Account deletion self-service (soft delete + job diferido) | ✅ |
| Sanctum API (login, 2FA, me, tokens CRUD) | ✅ |
| Admin audit (login attempts + impersonations + filtros) | ✅ |
| Admin force-logout / unlock / restore | ✅ |
| Dashboard widget seguridad 24h | ✅ |
| Comando `auth:prune` (limpieza programada) | ✅ |

## Instalación

1. Migraciones:
   ```bash
   php artisan module:migrate Auth
   ```
2. Seed permisos (incluidos en CompleteRolesAndPermissionsSeeder global o standalone):
   ```bash
   php artisan module:seed Auth --class=AuthPermissionsSeeder
   ```
3. Variables de entorno (copiar de `.env.example` sección `AUTH MODULE POLICY`).

## Configuración

Archivo: `config/auth/auth-policy.php` (namespace `auth.auth-policy.*`).

### Password
```php
'min_length' => 10,
'require_uppercase' => true,
'require_lowercase' => true,
'require_numbers' => true,
'require_symbols' => true,
'history_count' => 5,          // Bloquea reutilización de últimas N
'expires_in_days' => 0,        // 0 = sin caducidad
```

### Rate limiting (Redis, por user + IP)
```php
'login'          => ['max_attempts' => 5, 'decay_seconds' => 60],
'two_factor'     => ['max_attempts' => 5, 'decay_seconds' => 300],
'password_reset' => ['max_attempts' => 3, 'decay_seconds' => 900],
```

### Account lockout
```php
'lockout' => [
    'enabled' => true,
    'max_attempts' => 10,          // Fallos consecutivos antes de bloquear
    'duration_minutes' => 30,      // Duración del bloqueo
],
```

### Magic link
```php
'magic_link' => [
    'enabled' => true,
    'expires_in_minutes' => 15,
],
```

### Device tracking
```php
'devices' => [
    'enabled' => true,
    'alert_new_device' => true,    // Email al usuario en nuevo device
    'trust_duration_days' => 30,
],
```

### Impersonation
```php
'impersonation' => [
    'enabled' => true,
    'max_duration_minutes' => 60,
    'required_permission' => 'auth.impersonate',
],
```

### Lock screen
```php
'lock_screen' => [
    'enabled' => true,
    'inactivity_minutes' => 15,
],
```

## Permisos (Spatie)

| Permiso | Descripción |
|---|---|
| `auth.impersonate` | Iniciar sesión como otro usuario |
| `auth.audit.view` | Ver audit logs globales |
| `auth.settings.view/update` | Ver/editar config del módulo |
| `auth.devices.manage` | Gestionar trusted devices (admin) |
| `auth.api-tokens.manage` | Gestionar API tokens (admin) |

## Rutas principales

### Web públicas
```
GET  /login                          auth.login
POST /login                          auth.login.post
POST /logout                         auth.logout
GET  /forgot-password                auth.password.request
POST /forgot-password                auth.password.email
GET  /reset-password/{token}         auth.password.reset
POST /reset-password                 auth.password.update
GET  /magic-link                     auth.magic-link
POST /magic-link                     auth.magic-link.request
GET  /magic-link/consume/{token}     auth.magic-link.consume
GET  /email/verify/{id}/{hash}       auth.verification.verify
GET  /email/change/confirm/{token}   auth.email-change.confirm
```

### Web autenticadas
```
GET  /two-factor/challenge           two-factor.challenge
POST /two-factor/challenge           two-factor.verify
GET  /lock                           auth.lock
POST /lock/unlock                    auth.lock.unlock
POST /impersonate/{user}             auth.impersonation.start
POST /impersonate/stop               auth.impersonation.stop
```

### Settings (prefix `panel/setting/auth`, name `settings.auth.*`)
```
GET    /profile                      settings.auth.profile
PUT    /profile/info                 settings.auth.profile.update-info
POST   /profile/avatar               settings.auth.profile.update-avatar
POST   /password/update              settings.auth.password.update
POST   /email/change                 settings.auth.email.change.request
DELETE /account                      settings.auth.account.delete
POST   /two-factor/setup             settings.auth.two-factor.setup
POST   /two-factor/confirm           settings.auth.two-factor.confirm
DELETE /two-factor/disable           settings.auth.two-factor.disable
POST   /two-factor/recovery-codes    settings.auth.two-factor.recovery-codes
GET    /two-factor/recovery-codes/pdf  settings.auth.two-factor.recovery-codes.pdf
GET    /sessions/list                settings.auth.sessions.list
DELETE /sessions/{id}                settings.auth.sessions.destroy
POST   /sessions/destroy-others      settings.auth.sessions.destroy-others
GET    /devices/list                 settings.auth.devices.list
POST   /devices/{id}/trust           settings.auth.devices.trust
POST   /devices/{id}/revoke          settings.auth.devices.revoke
DELETE /devices/{id}                 settings.auth.devices.destroy
GET    /api-tokens/list              settings.auth.api-tokens.list
POST   /api-tokens                   settings.auth.api-tokens.store
DELETE /api-tokens/{id}              settings.auth.api-tokens.destroy
GET    /activity/list                settings.auth.activity.list

Admin (requiere auth.audit.view):
GET    /audit/login-attempts         settings.auth.audit.login-attempts
GET    /audit/impersonations         settings.auth.audit.impersonations
POST   /audit/users/{id}/force-logout  settings.auth.audit.force-logout
POST   /audit/users/{id}/unlock      settings.auth.audit.unlock
POST   /audit/users/{id}/restore     settings.auth.audit.restore
```

### API (prefix `api/auth`, Sanctum)
```
POST /login                          api.auth.login
POST /two-factor/challenge           api.auth.two-factor
GET  /me                             api.auth.me
POST /logout                         api.auth.logout
POST /logout-all                     api.auth.logout-all
GET  /tokens                         api.auth.tokens.index
POST /tokens                         api.auth.tokens.store
DELETE /tokens/{id}                  api.auth.tokens.destroy
```

## Ejemplos de uso

### Listar dispositivos del usuario (AJAX)
```js
$.get("{{ route('settings.auth.devices.list') }}", function (response) {
    console.log(response.devices);
});
```

### Consumir API (Sanctum)
```bash
curl -X POST https://app.test/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","device_name":"CLI"}'

# Respuesta:
# { "success": true, "data": { "token": "X|abc123...", "user": {...} } }

curl https://app.test/api/auth/me \
  -H "Authorization: Bearer X|abc123..."
```

### Dispatch impersonación (backend)
```php
use Modules\Auth\Services\ImpersonationService;

$service = app(ImpersonationService::class);
$service->start($admin, $target, $request, 'Soporte ticket #123');
// Dispatch ImpersonationStarted → log + notif al target
```

### Fire evento desde fuera
```php
use Modules\Auth\Events\PasswordChanged;

PasswordChanged::dispatch($user, $request->ip(), 'reset');
// Listener RecordPasswordChange guarda hash en history
```

## Eventos

| Event | Payload | Listeners |
|---|---|---|
| `UserLoggedIn` | user, ip, userAgent, remember | LogLoginActivity |
| `UserLoggedOut` | user, ip | — |
| `LoginFailed` | email, ip, userAgent, user?, reason | LogLoginFailure |
| `TwoFactorVerified` | user, ip, method | LogTwoFactorActivity |
| `TwoFactorFailed` | user, ip | LogTwoFactorActivity |
| `PasswordChanged` | user, ip, context | RecordPasswordChange |
| `NewDeviceDetected` | user, device | SendNewDeviceAlert |
| `ImpersonationStarted` | impersonator, impersonated, ip | NotifyImpersonated |
| `ImpersonationEnded` | impersonator, impersonated | — |

## Middleware aliases

```
auth.session.lock        → CheckSessionLock (pantalla bloqueada)
auth.deny-impersonating  → DenyWhenImpersonating (bloquea acciones sensibles)
auth.password.expired    → CheckPasswordExpired (fuerza cambio)
```

## Comandos Artisan

```bash
# Limpiar logs de auditoría antiguos (programado weekly a las 03:00)
php artisan auth:prune --days=90
php artisan auth:prune --dry-run
```

## Testing

```bash
# Tests del módulo
vendor/bin/phpunit modules/Auth/tests/

# Solo feature específica
vendor/bin/phpunit --filter=PasswordPolicy
```

Los tests heredan de `Modules\Auth\Tests\AuthTestCase` (DatabaseTransactions +
lazy schema). No usar `RefreshDatabase` directamente — el ambiente de testing
del proyecto tiene issues pre-existentes con `migrate:fresh`.

## Estructura

```
modules/Auth/
├── app/
│   ├── Console/Commands/         # auth:prune
│   ├── Events/                   # Login/Logout/2FA/Password/Device/Impersonation
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # AuditController
│   │   │   ├── Api/              # Sanctum endpoints
│   │   │   ├── Settings/         # UI settings tabs
│   │   │   ├── ImpersonationController
│   │   │   ├── LockScreenController
│   │   │   ├── LoginController
│   │   │   ├── MagicLinkController
│   │   │   ├── ForgotPasswordController
│   │   │   ├── ResetPasswordController
│   │   │   ├── TwoFactorChallengeController
│   │   │   └── VerificationController
│   │   ├── Middleware/           # 5 middleware aliases
│   │   ├── Requests/             # LoginApiRequest, UpdateProfileInfoRequest
│   │   └── Resources/            # UserResource
│   ├── Jobs/                     # DeleteUserAccountJob
│   ├── Listeners/                # 6 listeners (queued)
│   ├── Models/                   # 7 models (Session, LoginAttempt, TrustedDevice, ...)
│   ├── Notifications/            # 5 notifications
│   ├── Providers/AuthServiceProvider.php
│   ├── Rules/                    # StrongPassword, PasswordNotReused
│   ├── Services/                 # 6 services
│   └── Traits/                   # HasUserAttributes/Scopes/BasicRelations
├── config/
│   ├── auth-policy.php           # Todas las policies
│   ├── sanctum.php               # Sanctum config
│   └── verification.php          # Email verification services
├── database/
│   ├── migrations/               # 15+ migrations
│   └── seeders/                  # AuthPermissionsSeeder
├── resources/
│   └── views/
│       ├── admin/audit/          # Login attempts + impersonations
│       ├── auth/                 # login, register, passwords, 2FA, lock, magic-link
│       ├── components/           # security-widget (dashboard)
│       ├── partials/             # impersonation-banner
│       └── settings/             # profile, password, sessions, two-factor
├── routes/
│   ├── api.php                   # Sanctum endpoints
│   ├── settings.php              # Panel settings
│   └── web.php                   # Public auth routes
└── tests/
    ├── AuthTestCase.php          # Base con DatabaseTransactions
    └── Feature/
```
