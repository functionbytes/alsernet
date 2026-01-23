# Guía de Instalación - Módulo Reverb

Pasos para activar y configurar el módulo Reverb en tu aplicación Alsernet.

## Paso 1: Verificar activación del módulo

El módulo Reverb debe estar activado en `modules_statuses.json`:

```json
{
  "Reverb": true
}
```

✅ **Estado actual**: El módulo está activado

## Paso 2: Verificar configuración de .env

Las siguientes variables deben estar configuradas en tu archivo `.env`:

```ini
# Broadcasting Driver
BROADCAST_CONNECTION=reverb

# Reverb App Configuration
REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret

# Broadcasting Configuration (para clientes)
REVERB_HOST=alsernet.test
REVERB_PORT=8080
REVERB_SCHEME=http

# Servidor Reverb (dónde escucha)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Vite Configuration (para JavaScript)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

✅ **Estado actual**: Las variables ya están configuradas

## Paso 3: Crear permisos en la base de datos

Ejecuta el seeder para crear los permisos de Reverb:

```bash
php artisan db:seed --class="Modules\\Reverb\\Database\\Seeders\\CreateReverbPermissionsSeeder"
```

**Permisos creados:**

| Permiso | Descripción |
|---------|-------------|
| `reverb.settings.view` | Ver configuración de Reverb |
| `reverb.settings.update` | Actualizar configuración de Reverb |
| `reverb.channels.view` | Ver canales activos |
| `reverb.channels.broadcast` | Emitir eventos a canales |
| `reverb.events.broadcast` | Broadcast de eventos |
| `reverb.presence.view` | Ver información de presencia |
| `reverb.monitoring.view` | Ver monitoreo/estadísticas |
| `reverb.monitoring.debug` | Depuración avanzada |

**Asignación por rol:**

- **super-admin**: Todos los permisos
- **manager**: Vista y broadcast limitado

## Paso 4: Limpiar cache de la aplicación

```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

## Paso 5: Iniciar el servidor Reverb

```bash
php artisan reverb:start
```

El servidor escuchará en `0.0.0.0:8080` y será accesible desde `alsernet.test:8080` (según tu configuración).

**Salida esperada:**

```
 INFO  Reverb listening on ws://0.0.0.0:8080
```

## Paso 6: Acceder al panel administrativo

Una vez que el servidor Reverb está corriendo, puedes acceder a:

- **Panel de Configuración**: `http://alsernet.test/settings/reverb`
- **Panel de Monitoreo**: `http://alsernet.test/settings/reverb/monitoring`

## Verificación de conexión

### Test desde el panel

1. Ve a `http://alsernet.test/settings/reverb`
2. Haz clic en "Probar conexión"
3. Deberías ver: "Conexión exitosa"

### Test desde terminal

```bash
# Verifica que el puerto 8080 está escuchando
lsof -i :8080

# Verifica que el servidor responde
curl http://localhost:8080
```

## Próximos pasos

Una vez activado, puedes:

1. **Crear eventos broadcastables** - Ver ejemplos en `docs/reverb/EXAMPLES.md`
2. **Configurar cliente JavaScript** - Usar Laravel Echo para escuchar eventos
3. **Implementar canales privados** - Para datos sensibles
4. **Usar presence channels** - Para rastrear usuarios en tiempo real

## Troubleshooting

### Puerto 8080 ya está en uso

```bash
# Encuentra qué proceso está usando el puerto
lsof -i :8080

# Usa otro puerto
php artisan reverb:start --port=9000
```

### Conexión rechazada desde JavaScript

1. Verifica que `REVERB_HOST` es accesible desde el cliente
2. Para desarrollo local, usa `localhost` o la IP local
3. En producción, usa un dominio válido con certificado SSL/TLS

### Permisos insuficientes

Si obtienes errores de permisos:

1. Verifica que el usuario tiene el permiso `reverb.settings.view`
2. Asigna permisos manualmente desde la base de datos:

```php
$user->givePermissionTo('reverb.backups.view');
$user->givePermissionTo('reverb.events.broadcast');
```

### Seeder no se ejecuta

Si el seeder falla por Telescope:

```bash
# Ejecuta sin Telescope
php artisan tinker

# Dentro de tinker:
Spatie\Permission\Models\Permission::create(['name' => 'reverb.backups.view', 'guard_name' => 'web']);
Spatie\Permission\Models\Permission::create(['name' => 'reverb.backups.update', 'guard_name' => 'web']);
Spatie\Permission\Models\Permission::create(['name' => 'reverb.channels.view', 'guard_name' => 'web']);
Spatie\Permission\Models\Permission::create(['name' => 'reverb.channels.broadcast', 'guard_name' => 'web']);
Spatie\Permission\Models\Permission::create(['name' => 'reverb.events.broadcast', 'guard_name' => 'web']);
Spatie\Permission\Models\Permission::create(['name' => 'reverb.presence.view', 'guard_name' => 'web']);
Spatie\Permission\Models\Permission::create(['name' => 'reverb.monitoring.view', 'guard_name' => 'web']);
Spatie\Permission\Models\Permission::create(['name' => 'reverb.monitoring.debug', 'guard_name' => 'web']);

# Asignar a super-admin
$role = Spatie\Permission\Models\Role::findByName('super-admin');
$role->givePermissionTo('reverb.*');
```

## Configuración en producción

Para desplegar en producción:

1. Lee `docs/reverb/PRODUCTION.md`
2. Configura un proxy inverso (Nginx/Apache)
3. Usa certificados SSL/TLS (WSS)
4. Configura múltiples procesos Reverb
5. Usa Supervisor para mantener procesos activos

## Recursos

- [Documentación Reverb](docs/reverb/README.md)
- [Ejemplos de código](docs/reverb/EXAMPLES.md)
- [API Reference](docs/reverb/API.md)
- [Guía de Producción](docs/reverb/PRODUCTION.md)
