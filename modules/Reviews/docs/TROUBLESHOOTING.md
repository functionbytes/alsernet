# Troubleshooting - Reviews Module

Guía para resolver problemas comunes y debugging.

## Problemas de Autenticación y OAuth

### "Invalid OAuth state" durante conexión

**Síntomas**:
- Error al hacer click en "Connect Google Account"
- Redirige a Google, pero al volver aparece "Invalid OAuth state"

**Causas Posibles**:
1. Token de sesión expirado
2. CSRF token inválido
3. Sesión guardada en ubicación incorrecta
4. Cookie de sesión bloqueada

**Soluciones**:

```bash
# 1. Limpiar caché y sesiones
php artisan cache:clear
php artisan session:table    # Crear tabla si no existe
php artisan migrate           # Ejecutar migraciones

# 2. Verificar configuración de sesión
php artisan tinker
> config('session.driver')    # Debería ser 'database', 'redis', o 'file'
> config('session.lifetime')  # Debería ser > 120 minutos

# 3. Si Redis, verificar conexión
redis-cli ping               # Debe devolver PONG
```

Cambiar a database sessions en `.env`:

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### "Invalid Client ID" error

**Síntomas**:
- "Client authentication failed" al conectar
- "Client ID or secret was invalid"

**Causas**:
1. Client ID mal copiado
2. Client Secret incorrecto
3. Credenciales expiradas

**Soluciones**:

```bash
# 1. Verificar que está bien en .env
grep GOOGLE_CLIENT /env

# 2. Limpiar config cache
php artisan config:clear

# 3. En Google Cloud Console:
#    - Ir a APIs & Services > Credentials
#    - Copiar exactamente el Client ID (incluyendo .apps.googleusercontent.com)
#    - Copiar exactamente el Client Secret
```

No olvidar ejecutar `php artisan config:cache` en producción.

### "Redirect URI mismatch" error

**Síntomas**:
- "The redirect URI provided does not match..."
- Error durante OAuth callback

**Causas**:
1. URL de callback no registrada en Google Cloud
2. Diferencia entre http/https
3. Diferencia entre www/sin www
4. Puerto diferente (localhost:8000 vs localhost:8001)

**Soluciones**:

```bash
# 1. Verificar URL configurada
php artisan tinker
> config('reviews.google.redirect_uri')
# Debe mostrar exactamente como en Google Cloud

# 2. En Google Cloud Console > Credentials:
#    - Click en el OAuth Client
#    - Agregar EXACTAMENTE esta URL:
#    - https://tu-dominio.com/settings/reviews/google/callback
#    - Si desarrollo local: http://localhost:8000/settings/reviews/google/callback
```

Esperar 5-10 minutos después de cambios en Google Cloud.

### "Access blocked by administrator"

**Síntomas**:
- "Access blocked by administrator" o "unauthorized_client"
- Usuario no puede acceder a Google OAuth

**Causas**:
1. Empresa tiene políticas de Google Workspace restringidas
2. App OAuth no fue aprobada por admin
3. Usuario no tiene permiso de acceso

**Soluciones**:

Contactar con el administrador de Google Workspace para:

1. Ir a [Google Admin Console](https://admin.google.com)
2. Ir a Security > Access and data control > API controls
3. Buscar app "Reviews Integration"
4. Marcar como "Trusted"
5. O aprobar manualmente en "User-unconfirmed apps"

Para usuarios sin Workspace:

```bash
# Usar cuenta personal de Google
# No usar cuenta corporativa (@empresa.com)
```

## Problemas de Sincronización

### "Reviews no se sincronizan"

**Síntomas**:
- No aparecen nuevas reseñas en la app
- Queue jobs fallidos o no se ejecutan

**Causas Más Comunes**:
1. Queue worker no está corriendo
2. Job en estado failed
3. Ubicación no está activa
4. Token OAuth expirado

**Soluciones - Paso a Paso**:

```bash
# 1. Verificar que queue worker está activo
ps aux | grep queue:work

# Si no:
php artisan queue:work --queue=google-sync &

# 2. Verificar jobs fallidos
php artisan queue:failed

# 3. Si hay jobs fallidos, reintentar
php artisan queue:retry all

# 4. Verificar ubicaciones
php artisan tinker
> ReviewGoogleLocation::active()->get()

# 5. Sincronizar manualmente
php artisan reviews:sync --force

# 6. Ver logs
tail -f storage/logs/laravel.log
```

Para Producción con Supervisor:

```bash
# Crear archivo de configuración
sudo nano /etc/supervisor/conf.d/reviews-queue.conf
```

Contenido:

```ini
[program:reviews-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=google-sync --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/reviews-queue.log
stopasgroup=true
killasgroup=true
```

Aplicar:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reviews-queue:*
```

### "Rate limit exceeded" error

**Síntomas**:
- "429 Too Many Requests" en logs
- Google rechaza requests
- Error "User Rate Limit Exceeded"

**Causas**:
1. Demasiadas ubicaciones sincronizando al mismo tiempo
2. Sincronización frecuente
3. Retry automático de jobs fallidos

**Soluciones**:

```env
# 1. Aumentar intervalo de sincronización
REVIEWS_SYNC_INTERVAL=30    # De 15 a 30 minutos

# 2. Reducir rate limit configurado
REVIEWS_RATE_LIMIT_PER_MINUTE=30    # De 60 a 30
```

O ajustar en `config/general.php`:

```php
'sync_interval_minutes' => 30,
'rate_limit_per_minute' => 30,
```

Para jobs fallidos:

```bash
# Ver queue fallida
php artisan queue:failed

# Reintentarlos gradualmente
php artisan queue:retry --limit=5
```

### "Invalid Access Token" error

**Síntomas**:
- "Invalid Credentials" en logs
- "Access Token Expired"
- Reseñas no se sincronizan

**Causas**:
1. Token OAuth expirado
2. Refresh token no se pudo renovar
3. Token revocado en Google

**Soluciones**:

```bash
# 1. Renovar tokens manualmente
php artisan reviews:cleanup-expired

# 2. Verificar token en BD
php artisan tinker
> $conn = ReviewGoogleConnection::first()
> $conn->token_expires_at
> $conn->is_expired()

# 3. Si está expirado, renovar
> $conn->refreshTokenIfNeeded()

# 4. Verificar estado
> $conn->status
> $conn->last_error
```

Si el error persiste, reconectar cuenta:

```bash
# En base de datos, encontrar la conexión expirada
# Eliminarla o marcarla como revocada
php artisan tinker
> $conn->markAsRevoked()

# Luego en UI: desconectar y volver a conectar
```

### "The user is not authenticated" al sincronizar

**Síntomas**:
- Error al sincronizar ubicación
- "User must be authenticated" en logs

**Causas**:
1. Usuario fue eliminado pero la conexión sigue en BD
2. Conexión huérfana sin usuario
3. Problema de relación en BD

**Soluciones**:

```bash
php artisan tinker
> ReviewGoogleConnection::whereNull('user_id')->get()

# Eliminar conexiones huérfanas
> ReviewGoogleConnection::whereNull('user_id')->delete()

# O asignar usuario
> $conn = ReviewGoogleConnection::find(1)
> $conn->user_id = 2
> $conn->save()
```

## Problemas de Respuestas

### "Reply publication failed" al publicar

**Síntomas**:
- Error al hacer click en "Publish"
- Reply queda en estado "approved" sin publicarse

**Causas**:
1. Token expirado (refresh fallido)
2. Ubicación no verificada en Google Business Profile
3. Review ya fue eliminada en Google
4. Permisos insuficientes en Google

**Soluciones**:

```bash
# 1. Renovar token
php artisan reviews:cleanup-expired

# 2. Ver error específico
php artisan tinker
> $reply = ReviewReply::find(1)
> activity()->get()  # Ver logs

# 3. Verificar ubicación en Google Business Profile
#    - Ir a https://www.google.com/business
#    - Verificar que está verificada

# 4. Forzar resync de ubicación
> $location = ReviewGoogleLocation::find(1)
> $location->markAsNeedingSync()
> SyncGoogleReviewsJob::dispatch($location)
```

### "Cannot delete reply published to Google"

**Síntomas**:
- Error al intentar eliminar una respuesta publicada
- "Cannot modify published reply"

**Causas**:
- Intento de eliminar reply que ya está publicado en Google

**Soluciones**:

Google no permite eliminar respuestas publicadas. Opciones:

1. Editar la respuesta directamente en Google Business Profile
2. Crear nueva respuesta para reemplazar la anterior
3. Contactar con Google Support si hay error

En la app, se puede:

```bash
# Marcar como eliminada sin borrar registro
php artisan tinker
> $reply = ReviewReply::find(1)
> $reply->update(['status' => 'deleted'])
```

## Problemas de Datos

### "Duplicated reviews" - reseñas duplicadas

**Síntomas**:
- Misma reseña aparece múltiples veces
- Mismo google_review_id en diferentes rows

**Causas**:
1. Sincronización interrumpida
2. Job retrying generó duplicados

**Soluciones**:

```bash
php artisan tinker
> Review::where('google_review_id', '=', 'reviews/123')->get()

# Si hay duplicados, eliminar los más recientes
> Review::where('google_review_id', '=', 'reviews/123')
    ->orderBy('created_at', 'desc')
    ->skip(1)
    ->delete()

# O script para limpiar todos los duplicados
> Review::query()
    ->select('google_review_id', DB::raw('count(*) as count'))
    ->groupBy('google_review_id')
    ->having('count', '>', 1)
    ->get()
    ->each(function ($group) {
      Review::where('google_review_id', $group->google_review_id)
        ->orderBy('created_at', 'desc')
        ->skip(1)
        ->delete();
    })
```

### "Old data not cleaned" - datos antiguos no se eliminan

**Síntomas**:
- BD muy grande
- Reseñas muy antiguas aún visibles

**Causas**:
- Comando `reviews:prune` nunca se ejecutó

**Soluciones**:

```bash
# Ver data antigua
php artisan tinker
> Review::where('review_time', '<', now()->subDays(90))->count()

# Eliminar data con más de 90 días
php artisan reviews:prune

# Eliminar con días customizado
php artisan reviews:prune --days=180

# Programar en scheduler (ya configurado automáticamente)
# app/Console/Kernel.php
$schedule->command('reviews:prune')->daily();
```

## Problemas de Permisos

### "Unauthorized - Missing Permission"

**Síntomas**:
- Error 403 "This action is unauthorized"
- No puede ver reseñas/responder

**Causas**:
1. Usuario no tiene permiso asignado
2. Rol sin permisos
3. Permiso no existe

**Soluciones**:

```bash
php artisan tinker
> $user = User::find(1)
> $user->permissions
> $user->roles

# Asignar permiso
> $user->givePermissionTo('reviews.reviews.view')

# O asignar rol completo
> $user->assignRole('admin')

# Verificar permiso
> $user->can('reviews.reviews.view')

# Ver todos los permisos disponibles
> \Spatie\Permission\Models\Permission::where('name', 'like', 'reviews%')->get()
```

### Permisos en seeders

```bash
# Ejecutar seeders de permisos
php artisan db:seed --class=ReviewsPermissionSeeder

# O instalar módulo
php artisan reviews:install
```

## Problemas de Exportación

### "Export fails" - error al exportar CSV

**Síntomas**:
- Error 500 al hacer click en "Export"
- "Memory limit exceeded"

**Causas**:
1. Muy muchas reseñas para exportar
2. Espacio en disco insuficiente
3. Permisos de escritura en storage

**Soluciones**:

```bash
# 1. Verificar permisos
ls -la storage/exports
chmod 755 storage/exports

# 2. Liberar espacio
du -sh storage/
rm storage/logs/*.log
rm storage/exports/*.csv

# 3. Aumentar memory limit en php.ini
memory_limit = 512M

# 4. Exportar con filtros para reducir volumen
# En lugar de exportar todo, filtrar por:
# - location_id
# - fecha
# - rating
```

### "CSV encoding issues" - caracteres extraños en CSV

**Síntomas**:
- Acentos y caracteres especiales se ven mal
- "????" en lugar de caracteres acentuados

**Causas**:
- Encoding incorrecto en exportación
- Excel abriendo con encoding incorrecta

**Soluciones**:

Para Excel en Windows:

1. Abrir CSV en Excel
2. Data > Text to Columns
3. Encoding: UTF-8
4. Finish

O abrir en LibreOffice que auto-detecta encoding.

## Problemas de BD

### "Migrations failed" - error al correr migraciones

**Síntomas**:
- Error al ejecutar `php artisan migrate`
- "Table already exists"

**Causas**:
1. Migraciones ejecutadas múltiples veces
2. BD corrupta

**Soluciones**:

```bash
# Ver migraciones ejecutadas
php artisan migrate:status

# Rollback y volver a ejecutar
php artisan migrate:refresh

# O para módulo específico
php artisan migrate:refresh --path=modules/Reviews/database/migrations
```

**Advertencia**: `migrate:refresh` elimina toda la data. Hacer backup primero:

```bash
mysqldump -u user -p database > backup.sql
```

### "Foreign key constraint error"

**Síntomas**:
- "Foreign key constraint fails"
- No se puede crear ubicación

**Causas**:
1. Conexión ya fue eliminada
2. Referencia a usuario que no existe

**Soluciones**:

```bash
# Ver relaciones en BD
SHOW CREATE TABLE review_google_locations;

# Desactivar foreign keys temporalmente
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM reviews WHERE user_id = 999;
SET FOREIGN_KEY_CHECKS = 1;

# O limpiar huérfanos
php artisan tinker
> ReviewGoogleLocation::whereDoesntHave('connection')->delete()
```

## Debugging Avanzado

### Habilitar Query Logging

```php
// En .env o en controller
DB::enableQueryLog();

// Ejecutar queries

$queries = DB::getQueryLog();
dd($queries);
```

### Ver Activity Log

```bash
php artisan tinker
> activity()->all()
> activity()->where('subject_type', 'Modules\Reviews\Models\Review')->get()
```

### Inspeccionar Jobs

```bash
php artisan queue:failed
php artisan queue:retry {id}
php artisan queue:forget {id}
```

### Monitorar Redis

```bash
redis-cli monitor

# Ver claves
redis-cli keys "reviews*"

# Limpiar caché
redis-cli FLUSHALL
```

## Contacto y Reportes

Ante problemas no cubiertos aquí:

1. Revisar logs: `storage/logs/laravel.log`
2. Ver activity log en BD: `activity_log` table
3. Habilitar debug en `.env`: `APP_DEBUG=true`
4. Reportar con:
   - Stack trace completo
   - Pasos para reproducir
   - Versión de Laravel, PHP
   - Logs relevantes

## Performance

Si la app está lenta:

```bash
# Analizar queries lentas
php artisan tinker
> Review::with('location', 'moderation', 'replies')->get()  # Bueno (eager loading)
> Review::all(); foreach($reviews as $r) { $r->location; }  # Malo (N+1)

# Ver índices
SHOW INDEX FROM reviews;

# Optimizar
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Limpiar cachés:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
