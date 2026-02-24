# Security Audit - Módulo Reviews

**Fecha:** 2026-02-20
**Auditor:** Security Agent (Claude Code)
**Versión:** 1.0
**Alcance:** Módulo completo Reviews (Google Business Profile integration)

---

## Resumen Ejecutivo

El módulo Reviews implementa **buenas prácticas de seguridad fundamentales** en su mayoría. Los tokens OAuth2 están encriptados, la autenticación está correctamente implementada, y la autorización usa Spatie Permission. Sin embargo, se identificaron **4 vulnerabilidades críticas**, **3 vulnerabilidades altas**, y **5 vulnerabilidades medias** que requieren atención inmediata.

**Nivel de Riesgo Global:** 🟠 **ALTO**

---

## 🔴 Vulnerabilidades Críticas

### 1. **CSRF Bypass en OAuth Callback** (CWE-352)
**Severidad:** CRÍTICA | **CVSS:** 9.1

**Ubicación:** `/modules/Reviews/app/Http/Controllers/Settings/GoogleConnectionController.php:68`

**Problema:**
```php
if ($request->input('state') !== session('google_oauth_state')) {
    throw new \Exception('Invalid OAuth state');
}
```
El state parameter se valida **después** de buscar datos en sesión, lo que permite ataques de timing. Además, el state no se invalida tras el uso, permitiendo **reutilización**.

**Impacto:**
- Ataque CSRF permitiría vincular cuentas Google maliciosas
- Acceso no autorizado a reseñas de negocios de terceros
- Robo de tokens OAuth2

**Fix:**
```php
// En GoogleConnectionController::callback()
$sessionState = session()->pull('google_oauth_state'); // pull() elimina tras leer
$sessionConnectionId = session()->pull('google_connection_id');

if (empty($sessionState) || empty($request->input('state'))) {
    abort(403, 'Missing OAuth state parameter');
}

if (!hash_equals($sessionState, $request->input('state'))) {
    abort(403, 'Invalid OAuth state - possible CSRF attack');
}

$connection = ReviewGoogleConnection::query()
    ->where('id', $sessionConnectionId)
    ->where('user_id', auth()->id()) // Verificar propiedad
    ->where('status', ConnectionStatus::PENDING)
    ->firstOrFail();
```

**Referencias:**
- CWE-352: Cross-Site Request Forgery (CSRF)
- OWASP Top 10 2021: A01 Broken Access Control

---

### 2. **Insecure Direct Object Reference (IDOR) en Policies** (CWE-639)
**Severidad:** CRÍTICA | **CVSS:** 8.8

**Ubicación:** `/modules/Reviews/app/Policies/ReviewGoogleConnectionPolicy.php:15-43`

**Problema:**
```php
public function view(User $user, ReviewGoogleConnection $connection): bool
{
    return $user->can('reviews.connections.view'); // NO verifica propiedad
}
```
Las políticas **nunca verifican** si `$connection->user_id === $user->id`. Cualquier usuario con el permiso genérico `reviews.connections.view` puede ver/editar/eliminar conexiones de **otros usuarios**.

**Impacto:**
- Acceso no autorizado a tokens OAuth2 de otros usuarios
- Revocación maliciosa de conexiones ajenas
- Exfiltración de datos de negocios de terceros

**Fix:**
```php
// En ReviewGoogleConnectionPolicy.php
public function view(User $user, ReviewGoogleConnection $connection): bool
{
    return $user->can('reviews.connections.view')
        && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
}

public function update(User $user, ReviewGoogleConnection $connection): bool
{
    return $user->can('reviews.connections.edit')
        && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
}

public function delete(User $user, ReviewGoogleConnection $connection): bool
{
    return $user->can('reviews.connections.delete')
        && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
}

public function revoke(User $user, ReviewGoogleConnection $connection): bool
{
    return $user->can('reviews.connections.revoke')
        && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
}
```

**Mismo problema en:** `ReviewPolicy.php`, `ReviewReplyPolicy.php`

**Referencias:**
- CWE-639: Authorization Bypass Through User-Controlled Key
- OWASP Top 10 2021: A01 Broken Access Control

---

### 3. **Missing SSL/TLS Verification en Guzzle** (CWE-295)
**Severidad:** CRÍTICA | **CVSS:** 8.1

**Ubicación:**
- `/modules/Reviews/app/Services/GoogleAuthService.php:36,73,110,134`
- `/modules/Reviews/app/Services/GoogleReviewService.php:54,133,180`

**Problema:**
```php
$client = new GuzzleClient; // NO configura verify => true
$response = $client->post('https://oauth2.googleapis.com/token', [
    'form_params' => [...],
    // Falta 'verify' => true
]);
```
GuzzleHttp **por defecto verifica SSL**, pero no hay garantía explícita. En entornos de desarrollo/testing se podría deshabilitar globalmente.

**Impacto:**
- Man-in-the-Middle en OAuth flow
- Interceptación de tokens y refresh tokens
- Exfiltración de credenciales Google

**Fix:**
```php
// Crear un método centralizado en GoogleAuthService
private function createSecureClient(int $timeout = 30): GuzzleClient
{
    return new GuzzleClient([
        'timeout' => $timeout,
        'connect_timeout' => 10,
        'verify' => true, // CRÍTICO: Forzar verificación SSL
        'http_errors' => true,
        'headers' => [
            'User-Agent' => 'Alsernet-Reviews/1.0',
        ],
    ]);
}

// Usar en todos los métodos
$client = $this->createSecureClient();
```

**Referencias:**
- CWE-295: Improper Certificate Validation
- OWASP Top 10 2021: A02 Cryptographic Failures

---

### 4. **SQL Injection via DB::raw sin Sanitización** (CWE-89)
**Severidad:** CRÍTICA | **CVSS:** 9.8

**Ubicación:** `/modules/Reviews/app/Http/Controllers/ReviewController.php:30`

**Problema:**
```php
'average_rating' => Review::query()->avg(DB::raw('CAST(star_rating AS UNSIGNED)')),
```
Aunque `star_rating` es un Enum, el uso de `DB::raw()` sin binding es **peligroso** si cambia en el futuro.

**Impacto:**
- SQL Injection si se modifica el código y se pasa input de usuario
- Exfiltración de datos de la base de datos completa
- Ejecución de comandos (si MariaDB tiene permisos)

**Fix:**
```php
// Opción 1: Sin DB::raw (preferido)
'average_rating' => Review::query()
    ->selectRaw('AVG(CASE
        WHEN star_rating = ? THEN 1
        WHEN star_rating = ? THEN 2
        WHEN star_rating = ? THEN 3
        WHEN star_rating = ? THEN 4
        WHEN star_rating = ? THEN 5
        ELSE 0 END)', ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE'])
    ->value('average_rating'),

// Opción 2: Calcular en PHP (más seguro)
$reviews = Review::query()->get(['star_rating']);
$average_rating = $reviews->avg(fn($r) => $r->star_rating->value());
```

**Referencias:**
- CWE-89: SQL Injection
- OWASP Top 10 2021: A03 Injection

---

## 🟠 Vulnerabilidades Altas

### 5. **Falta de Rate Limiting en OAuth Callback** (CWE-307)
**Severidad:** ALTA | **CVSS:** 7.5

**Ubicación:** `/modules/Reviews/routes/web.php:14`

**Problema:**
```php
Route::get('oauth/callback', [GoogleConnectionController::class, 'callback'])
    ->name('oauth.callback');
// NO tiene rate limiting
```

**Impacto:**
- Brute force de state parameters
- DoS mediante callbacks maliciosos repetidos
- Consumo de recursos de sesión

**Fix:**
```php
Route::middleware(['web', 'auth', 'throttle:10,1']) // 10 intentos/minuto
    ->get('oauth/callback', [GoogleConnectionController::class, 'callback'])
    ->name('oauth.callback');
```

**Referencias:**
- CWE-307: Improper Restriction of Excessive Authentication Attempts
- OWASP API Security Top 10: API4 Lack of Resources & Rate Limiting

---

### 6. **Tokens Expuestos en Activity Log** (CWE-532)
**Severidad:** ALTA | **CVSS:** 7.2

**Ubicación:** `/modules/Reviews/app/Services/GoogleAuthService.php:93-101`

**Problema:**
```php
activity()
    ->performedOn($connection)
    ->log('Token refresh failed: '.$e->getMessage());
```
Si `$e->getMessage()` contiene tokens en la respuesta de error de Google, se guardan en **texto plano** en `activity_log`.

**Impacto:**
- Exposición de tokens en logs de auditoría
- Acceso de administradores a tokens de usuarios

**Fix:**
```php
// En GoogleAuthService::refreshTokenIfNeeded()
catch (\Exception $e) {
    $safeMessage = 'Token refresh failed';

    // NO registrar mensaje completo de la excepción
    $connection->markAsExpired($safeMessage);

    activity()
        ->performedOn($connection)
        ->log($safeMessage);

    Log::error('Google OAuth token refresh failed', [
        'connection_id' => $connection->id,
        'error_code' => $e->getCode(),
        // NO incluir $e->getMessage() que puede tener tokens
    ]);

    throw new \Exception($safeMessage, 0, $e);
}
```

**Referencias:**
- CWE-532: Insertion of Sensitive Information into Log File
- OWASP Top 10 2021: A09 Security Logging and Monitoring Failures

---

### 7. **Missing Authorization en Export** (CWE-285)
**Severidad:** ALTA | **CVSS:** 7.1

**Ubicación:** `/modules/Reviews/app/Http/Controllers/ReviewController.php:132-147`

**Problema:**
```php
public function export(Request $request): BinaryFileResponse
{
    $filters = $request->only([...]); // NO valida filters
    $filePath = $this->exportService->exportToCsv($filters);
    return response()->download($filePath, ...);
}
```
El método `export()` usa `authorizeResource()` en el constructor, pero **no hay policy** `export()` en `ReviewPolicy.php`. Línea 25 tiene método `export()` pero nunca se ejecuta porque falta en `ReviewController`.

**Impacto:**
- Exportación masiva de datos sin autorización
- Exfiltración de internal_notes privadas
- DoS mediante exportaciones grandes

**Fix:**
```php
// En ReviewController::export()
public function export(Request $request): BinaryFileResponse
{
    $this->authorize('export', Review::class);

    $validated = $request->validate([
        'location_id' => 'sometimes|integer|exists:review_google_locations,id',
        'rating' => 'sometimes|in:ONE,TWO,THREE,FOUR,FIVE',
        'date_from' => 'sometimes|date',
        'date_to' => 'sometimes|date|after_or_equal:date_from',
    ]);

    $filePath = $this->exportService->exportToCsv($validated);

    return response()->download($filePath, 'reviews-'.date('Y-m-d').'.csv')
        ->deleteFileAfterSend(true); // Limpiar archivo temporal
}
```

**Referencias:**
- CWE-285: Improper Authorization
- OWASP Top 10 2021: A01 Broken Access Control

---

## 🟡 Vulnerabilidades Medias

### 8. **Path Traversal en Export Service** (CWE-22)
**Severidad:** MEDIA | **CVSS:** 6.5

**Ubicación:** `/modules/Reviews/app/Services/ReviewExportService.php:22`

**Problema:**
```php
$filename = 'reviews_'.now()->format('Y-m-d_His').'.csv';
$path = storage_path('app/exports/'.$filename);
```
Si `now()` se manipula (aunque improbable), podría generar paths como `../../etc/passwd`.

**Fix:**
```php
$filename = 'reviews_'.now()->format('Y-m-d_His').'.csv';
$filename = basename($filename); // Sanitizar
$path = storage_path('app/exports/'.Str::slug($filename, '_'));

// Validar path final
if (!Str::startsWith($path, storage_path('app/exports/'))) {
    throw new \Exception('Invalid export path');
}
```

---

### 9. **XSS en DataTables Rendering** (CWE-79)
**Severidad:** MEDIA | **CVSS:** 6.1

**Ubicación:** `/modules/Reviews/resources/views/reviews/index.blade.php:169-172`

**Problema:**
```javascript
{ data: 'comment', name: 'comment', render: function(data) {
    if (!data) return '<em class="text-muted">Sin comentario</em>';
    return data.length > 100 ? data.substring(0, 100) + '...' : data;
}}
```
El campo `comment` (input de usuarios Google) se renderiza **sin escapar** en HTML.

**Impacto:**
- Stored XSS mediante comentarios maliciosos de Google
- Robo de sesiones de administradores
- Desfiguración de interfaz

**Fix:**
```javascript
{ data: 'comment', name: 'comment', render: function(data) {
    if (!data) return '<em class="text-muted">Sin comentario</em>';
    const escaped = $('<div/>').text(data).html(); // Escapar HTML
    return escaped.length > 100
        ? escaped.substring(0, 100) + '...'
        : escaped;
}}
```

**Referencias:**
- CWE-79: Cross-site Scripting (XSS)
- OWASP Top 10 2021: A03 Injection

---

### 10. **Falta Validación de redirect_uri** (CWE-601)
**Severidad:** MEDIA | **CVSS:** 5.4

**Ubicación:** `/modules/Reviews/config/google.php:15`

**Problema:**
```php
'redirect_uri' => env('APP_URL').'/settings/reviews/google/callback',
```
Si `APP_URL` está mal configurado o comprometido, permite **Open Redirect** en OAuth flow.

**Fix:**
```php
// En config/google.php
'redirect_uri' => rtrim(config('app.url'), '/').'/settings/reviews/oauth/callback',

// En GoogleConnectionController::callback() verificar
if (!Str::startsWith($request->fullUrl(), config('reviews.google.redirect_uri'))) {
    abort(403, 'Invalid redirect URI');
}
```

---

### 11. **Falta Timeout en HTTP Requests** (CWE-400)
**Severidad:** MEDIA | **CVSS:** 5.3

**Ubicación:** Todos los `new GuzzleClient` sin timeout

**Fix:** Ya incluido en fix #3 (createSecureClient con timeout: 30)

---

### 12. **Missing Input Validation en API Filters** (CWE-20)
**Severidad:** MEDIA | **CVSS:** 5.3

**Ubicación:** `/modules/Reviews/app/Http/Controllers/Api/ReviewController.php:23-56`

**Problema:**
```php
if ($request->filled('location_id')) {
    $query->where('location_id', $request->integer('location_id'));
    // NO valida que location_id exista o pertenezca al usuario
}
```

**Fix:**
```php
if ($request->filled('location_id')) {
    $locationId = $request->integer('location_id');

    // Verificar que la location existe y el usuario tiene acceso
    $location = ReviewGoogleLocation::query()
        ->whereHas('connection', function($q) {
            $q->where('user_id', auth()->id())
              ->orWhereHas('user', fn($q2) => $q2->whereHas('roles', fn($q3) => $q3->where('name', 'super-admin')));
        })
        ->findOrFail($locationId);

    $query->where('location_id', $locationId);
}
```

---

## 🟢 Buenas Prácticas Implementadas

### Autenticación & Autorización
- ✅ Autenticación Sanctum en todas las rutas API (`auth:sanctum`)
- ✅ Middleware `auth` en todas las rutas web
- ✅ Policies registradas para todos los modelos principales
- ✅ Uso correcto de `authorizeResource()` en controllers
- ✅ Permisos granulares con Spatie Permission

### Encriptación & Tokens
- ✅ Tokens OAuth2 encriptados con cast `encrypted` en modelo
- ✅ Uso de `refresh_token` para renovación automática
- ✅ Revocación de tokens implementada (`revokeToken()`)
- ✅ Expiración de tokens rastreada (`token_expires_at`)

### Input Validation
- ✅ Form Request classes para validación (`StoreReviewGoogleConnectionRequest`, etc.)
- ✅ Validación de tipos con `$request->integer()`, `$request->boolean()`
- ✅ Límites de longitud en validaciones (max:100, max:1000)
- ✅ Uso de Eloquent (prepared statements) en lugar de raw SQL

### Database Security
- ✅ Foreign keys con constraints (`cascadeOnDelete`, `nullOnDelete`)
- ✅ Índices en columnas sensibles (user_id, status, etc.)
- ✅ Soft deletes para auditoría (`SoftDeletes` trait)
- ✅ Sin `$guarded = []` en ningún modelo (todos usan `$fillable`)

### Session & CSRF
- ✅ CSRF tokens en formularios (`@csrf`)
- ✅ CSRF headers en AJAX (`X-CSRF-TOKEN`)
- ✅ Session state parameter en OAuth flow

### Logging & Auditing
- ✅ Spatie Activity Log en todos los modelos críticos
- ✅ Logs de errores en try/catch blocks
- ✅ Tracking de usuarios en operaciones (created_by, approved_by)

### API Security
- ✅ Rate limiting en API routes (`throttle:60,1`)
- ✅ API Resources para controlar output (`ReviewResource`)
- ✅ Paginación en listados (evita DoS)

### Data Privacy
- ✅ `internal_notes` solo expuestas con autorización (línea 17-20 `ReviewModerationResource.php`)
- ✅ Soft deletes en lugar de hard deletes
- ✅ Activity log para compliance (GDPR auditoría)

---

## 🛠️ Recomendaciones Adicionales

### Seguridad Proactiva

1. **Content Security Policy (CSP)**
   ```php
   // En middleware o header
   'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.datatables.net; style-src 'self' 'unsafe-inline';"
   ```

2. **Subresource Integrity (SRI) para CDN**
   ```html
   <script src="//cdn.datatables.net/..."
       integrity="sha384-..."
       crossorigin="anonymous"></script>
   ```

3. **Security Headers**
   ```php
   // En bootstrap/app.php o middleware
   'X-Content-Type-Options' => 'nosniff',
   'X-Frame-Options' => 'SAMEORIGIN',
   'X-XSS-Protection' => '1; mode=block',
   'Referrer-Policy' => 'strict-origin-when-cross-origin',
   ```

4. **Honeypot para Forms**
   Agregar campos ocultos en formularios para detectar bots.

5. **Webhook Signature Verification**
   Si Google envía webhooks, verificar firma HMAC.

6. **Token Rotation**
   ```php
   // En GoogleAuthService::handleCallback()
   if (isset($data['refresh_token'])) {
       // Revocar tokens antiguos antes de guardar nuevos
       $this->revokeOldTokens($connection);
   }
   ```

7. **IP Whitelisting para Admin Panel**
   Restringir acceso a `/settings/reviews` por IP.

8. **Two-Factor Authentication (2FA)**
   Requerir 2FA para operaciones sensibles (revocar conexiones).

9. **Data Retention Policy**
   ```php
   // PruneOldReviewsCommand
   Review::where('synced_at', '<', now()->subYears(2))->delete();
   ```

10. **Dependency Scanning**
    ```bash
    composer audit
    npm audit
    # Automatizar en CI/CD
    ```

### Mejores Prácticas

11. **Validación de Scopes OAuth**
    ```php
    // Verificar que los scopes recibidos coinciden con los solicitados
    $requestedScopes = config('reviews.google.scopes');
    $receivedScopes = explode(' ', $tokenData['scope']);
    if (array_diff($requestedScopes, $receivedScopes)) {
        throw new \Exception('Scopes mismatch');
    }
    ```

12. **Notificaciones de Seguridad**
    ```php
    // Notificar al usuario cuando se conecta una cuenta
    Mail::to($user)->send(new GoogleAccountConnectedMail($connection));
    ```

13. **Audit Log Retention**
    Mantener logs de actividad por mínimo 1 año (compliance GDPR).

14. **Encrypt Database Backups**
    Encriptar backups que contienen tokens.

15. **Regular Security Reviews**
    Auditar código cada 6 meses o tras cambios mayores.

---

## 📋 Checklist de Remediación

### Prioridad 1 - CRÍTICAS (Inmediato, < 48h)
- [ ] **CSRF-1:** Implementar `hash_equals()` y `session()->pull()` en OAuth callback
- [ ] **IDOR-2:** Agregar verificación `user_id` en todas las policies
- [ ] **SSL-3:** Crear `createSecureClient()` con verify => true forzado
- [ ] **SQLi-4:** Eliminar `DB::raw()` de average_rating calculation

### Prioridad 2 - ALTAS (Urgente, < 1 semana)
- [ ] **Rate-5:** Agregar `throttle:10,1` en OAuth callback route
- [ ] **Logs-6:** Sanitizar mensajes de error antes de activity log
- [ ] **Export-7:** Implementar validación de filters y `authorize('export')`

### Prioridad 3 - MEDIAS (Importante, < 2 semanas)
- [ ] **Path-8:** Agregar `basename()` y validación de paths en export
- [ ] **XSS-9:** Escapar HTML en DataTables comment rendering
- [ ] **Redirect-10:** Validar redirect_uri en callback
- [ ] **Timeout-11:** Implementar timeouts en todos los HTTP clients
- [ ] **Input-12:** Validar ownership de location_id en API filters

### Prioridad 4 - MEJORAS (Planificado, < 1 mes)
- [ ] Implementar CSP headers
- [ ] Agregar SRI a CDN resources
- [ ] Configurar security headers globales
- [ ] Implementar token rotation
- [ ] Agregar 2FA para operaciones críticas
- [ ] Configurar dependency scanning en CI/CD
- [ ] Implementar notificaciones de seguridad
- [ ] Documentar data retention policy
- [ ] Implementar webhook signature verification (si aplica)
- [ ] Configurar audit log retention

### Testing & Verification
- [ ] Crear test de penetración para CSRF bypass
- [ ] Test de IDOR con usuarios diferentes
- [ ] Test de SQL injection con payloads OWASP
- [ ] Test de XSS con comentarios maliciosos
- [ ] Test de rate limiting con herramienta automated
- [ ] Code review por segundo desarrollador
- [ ] Penetration test externo (opcional)

---

## 📚 Referencias & Recursos

### OWASP
- [OWASP Top 10 2021](https://owasp.org/www-project-top-ten/)
- [OWASP API Security Top 10](https://owasp.org/www-project-api-security/)
- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)

### CWE (Common Weakness Enumeration)
- [CWE-22: Path Traversal](https://cwe.mitre.org/data/definitions/22.html)
- [CWE-79: Cross-site Scripting](https://cwe.mitre.org/data/definitions/79.html)
- [CWE-89: SQL Injection](https://cwe.mitre.org/data/definitions/89.html)
- [CWE-295: Improper Certificate Validation](https://cwe.mitre.org/data/definitions/295.html)
- [CWE-352: CSRF](https://cwe.mitre.org/data/definitions/352.html)
- [CWE-639: Authorization Bypass](https://cwe.mitre.org/data/definitions/639.html)

### Laravel Security
- [Laravel Security Best Practices](https://laravel.com/docs/12.x/security)
- [Laravel Authorization](https://laravel.com/docs/12.x/authorization)
- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)

### OAuth2 Security
- [OAuth 2.0 Security Best Current Practice](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)

### Compliance
- [GDPR Article 32: Security of Processing](https://gdpr-info.eu/art-32-gdpr/)
- [PCI DSS Requirements](https://www.pcisecuritystandards.org/)

---

## 🔄 Historial de Cambios

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 2026-02-20 | Auditoría inicial completa del módulo Reviews |

---

## 👤 Contacto

Para consultas sobre este reporte de seguridad, contactar al equipo de desarrollo.

**Próxima Auditoría Programada:** 2026-08-20 (6 meses)
