# Security Agent - Project Memory

## Auditorías Realizadas

### Reviews Module (2026-02-20)
- **Nivel de Riesgo:** ALTO → MEDIO (después de fixes)
- **Vulnerabilidades:** 4 críticas, 3 altas, 5 medias
- **Reporte Auditoría:** `/modules/Reviews/SECURITY_AUDIT.md`
- **Reporte Fixes:** `/modules/Reviews/SECURITY_FIXES_IMPLEMENTED.md`
- **Status:** ✅ 4 vulnerabilidades CRÍTICAS corregidas
  - CSRF Bypass en OAuth: Fixed con rate limiting + hash_equals + state invalidation
  - IDOR en Policies: Fixed con ownership checks en 4 policies
  - Missing SSL Verification: Fixed con createSecureClient() en 4 services
  - SQL Injection: Already fixed (verificado durante implementación)
- **Archivos modificados:** 9 archivos (1 controller, 4 policies, 4 services)

## Patrones de Vulnerabilidad Comunes en Este Proyecto

### 1. IDOR (Insecure Direct Object Reference)
**Patrón detectado:** Policies que verifican permisos genéricos pero NO verifican ownership.
```php
// VULNERABLE
public function view(User $user, Model $model): bool {
    return $user->can('permission.view'); // Falta: $model->user_id === $user->id
}
```
**Buscar en:** Todos los archivos `app/Policies/*.php` con `user_id` en modelos.

### 2. CSRF en OAuth Flows
**Patrón detectado:** State parameter validado DESPUÉS de acceder a sesión, y sin invalidación tras uso.
```php
// VULNERABLE
if ($request->input('state') !== session('oauth_state')) // timing attack + reuse
// SEGURO
if (!hash_equals(session()->pull('oauth_state'), $request->input('state')))
```

### 3. Tokens en Activity Logs
**Patrón detectado:** Exception messages con tokens/secrets guardados en `activity_log`.
```php
// VULNERABLE
activity()->log('Error: '.$e->getMessage()); // puede contener tokens
// SEGURO
activity()->log('Operation failed'); // mensaje genérico
```

### 4. XSS en DataTables Rendering
**Patrón detectado:** Campos de usuario renderizados sin escapar en callbacks JavaScript.
```javascript
// VULNERABLE
render: function(data) { return data; }
// SEGURO
render: function(data) { return $('<div/>').text(data).html(); }
```

### 5. Missing SSL Verification
**Patrón detectado:** `new GuzzleClient` sin config explícito de `verify => true`.
**Siempre crear:** Método centralizado `createSecureClient()` con timeout, verify, http_errors.

## Checklist de Auditoría por Módulo

### OAuth/API Integration Modules
- [ ] State parameter con `hash_equals()` y `session()->pull()`
- [ ] Redirect URI validation
- [ ] SSL verification forzado
- [ ] Tokens encriptados en BD (cast `encrypted`)
- [ ] Activity log sin datos sensibles
- [ ] Rate limiting en callbacks
- [ ] Timeout en HTTP requests

### CRUD Modules
- [ ] Policies verifican ownership (`user_id` o `created_by`)
- [ ] Form Requests con validación completa
- [ ] Export con authorization + input validation
- [ ] DataTables escape HTML en rendering
- [ ] API filters validan ownership de IDs relacionados
- [ ] Soft deletes implementados
- [ ] Activity log en operaciones sensibles

### Settings/Config Modules
- [ ] Validación de paths en file operations
- [ ] Sanitización de inputs antes de guardar en config
- [ ] Autorización para cambiar settings globales
- [ ] Logs de cambios en settings críticos

## Herramientas & Comandos

### Automated Checks
```bash
# Dependency vulnerabilities
composer audit

# Unescaped Blade output
rg '\{\!!' modules/ -g '*.blade.php'

# Hardcoded credentials
rg 'password|secret|api_key' modules/ config/ -g '*.php'

# Open mass assignment
rg '\$guarded = \[\]' modules/ -g '*.php'

# Raw SQL
rg 'whereRaw|DB::raw|DB::select' modules/ -g '*.php'

# Routes without middleware
php artisan route:list --columns=method,uri,middleware
```

### Manual Review Focus
1. **Policies:** Verificar ownership checks en todos los métodos
2. **OAuth:** State parameter handling
3. **Activity Logs:** Sanitización de mensajes
4. **API Filters:** Validación de IDs relacionados
5. **Guzzle Clients:** Config de SSL/timeout

## Configuración de Seguridad del Proyecto

### Framework
- Laravel 12, PHP 8.4
- Sanctum para API auth
- Spatie Permission para roles/permisos

### Database
- MariaDB (primary)
- Prepared statements via Eloquent (seguro por defecto)

### Frontend
- jQuery + AJAX (verificar X-CSRF-TOKEN en headers)
- DataTables (escapar HTML en renders)

### No Usados
- NO Livewire
- NO Inertia

## Severidad & Priorización

### CRÍTICA (CVSS 9.0-10.0) - Fix < 48h
- SQL Injection
- Authentication bypass
- Token exposure en plaintext
- RCE (Remote Code Execution)

### ALTA (CVSS 7.0-8.9) - Fix < 1 semana
- IDOR con acceso a datos sensibles
- CSRF en operaciones críticas
- XSS con robo de sesión
- Missing SSL verification

### MEDIA (CVSS 4.0-6.9) - Fix < 2 semanas
- Path traversal limitado
- Missing rate limiting
- Information disclosure menor
- XSS en áreas restringidas

### BAJA (CVSS 0.1-3.9) - Fix < 1 mes
- Mejoras de hardening
- Security headers faltantes
- Logs verbosos

## Recursos Clave

### OWASP
- Top 10 2021: https://owasp.org/www-project-top-ten/
- API Security Top 10: https://owasp.org/www-project-api-security/
- Cheat Sheets: https://cheatsheetseries.owasp.org/

### CWE
- CWE Top 25: https://cwe.mitre.org/top25/

### Laravel Específico
- Security Docs: https://laravel.com/docs/12.x/security
- Sanctum Docs: https://laravel.com/docs/12.x/sanctum
- Authorization: https://laravel.com/docs/12.x/authorization

## Notas para Futuras Auditorías

### Estructura de Módulos
- Código en `modules/ModuleName/app/`
- Routes: `modules/ModuleName/routes/` (web.php, api.php, settings.php)
- Views: `modules/ModuleName/resources/views/`
- Policies: `modules/ModuleName/app/Policies/`

### Próximas Auditorías Sugeridas
1. **Auth Module** - JWT token handling
2. **User Module** - Password reset flow
3. **Backup Module** - File access controls
4. **API endpoints** - Global rate limiting y authorization

### Falsos Positivos Comunes
- `DB::raw()` con constantes (no input usuario) → Aceptable pero documentar
- `{!! $html !!}` con HTML Purifier → Verificar que purifier esté activo
- Permisos globales en super-admin → Aceptable si está bien documentado
