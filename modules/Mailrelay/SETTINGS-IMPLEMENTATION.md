# Implementación de Sistema de Configuración - Mailrelay Module

**Fecha**: 2026-01-25
**Versión**: 2.1.0
**Estado**: ✅ IMPLEMENTADO (Pendiente migración)

## 📋 Resumen Ejecutivo

Se implementó un sistema completo de persistencia de configuraciones para el módulo Mailrelay, consolidando todas las configuraciones en base de datos usando el patrón Singleton.

### Antes vs Después

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Persistencia** | Runtime Config (se pierde) | Base de datos (permanente) |
| **Modelo** | Parcial (solo API) | Completo (General + API) |
| **Ubicación** | `App\Models\MailrelaySetting` | `Modules\Mailrelay\Entities\MailrelaySettings` |
| **Campos** | 5 campos (API solamente) | 20 campos (General + API + Avanzado) |
| **Patrón** | `getInstance()` | `instance()` + `updateSettings()` |

## 🎯 ¿Qué se Implementó?

### 1. Modelo MailrelaySettings
**Archivo**: `modules/Mailrelay/app/Entities/MailrelaySettings.php`

Modelo completo con 20 campos organizados en 5 categorías:

#### Sender Settings (Remitente)
- `sender_name` - Nombre del remitente
- `sender_email` - Email del remitente
- `reply_to_email` - Email de respuesta

#### Sync Settings (Sincronización)
- `auto_sync_enabled` - Sincronización automática habilitada
- `sync_frequency` - Frecuencia de sincronización (minutos)
- `sync_deleted` - Sincronizar eliminados

#### Limits (Límites)
- `emails_per_campaign` - Emails por campaña (default: 1000)
- `retry_attempts` - Reintentos en caso de error (default: 3)
- `timeout` - Timeout de API en segundos (default: 30)

#### Privacy (Privacidad)
- `double_optin` - Doble opt-in requerido
- `allow_unsubscribe` - Permitir cancelación
- `unsubscribe_footer` - Texto del footer de cancelación

#### Advanced (Avanzado)
- `detailed_logging` - Logging detallado
- `log_retention_days` - Días de retención de logs (default: 30)
- `sandbox_mode` - Modo de prueba

#### API Settings
- `api_key` - API Key (encriptada)
- `api_url` - URL de la API
- `cache_enabled` - Cachear respuestas
- `cache_ttl` - Tiempo de caché (minutos, default: 60)
- `retry_enabled` - Retry automático

### 2. Migration para Extender Tabla
**Archivo**: `modules/Mailrelay/database/migrations/2026_01_25_120000_add_general_settings_to_mailrelay_settings_table.php`

Esta migración EXTIENDE la tabla existente `mailrelay_settings` (que solo tenía 5 campos de API) agregando 15 campos nuevos para configuración general.

**IMPORTANTE**: No crea una tabla nueva, sino que agrega columnas a la existente.

### 3. Controladores Actualizados

#### GeneralSettingsController
**Archivo**: `modules/Mailrelay/app/Http/Controllers/Settings/GeneralSettingsController.php`

**Antes**:
```php
// ❌ Usaba Config runtime (no persistía)
$settings = (object) [
    'sender_name' => config('mailrelay.sender.name'),
    // ...
];
Config::set([...]) // Se perdía al finalizar request
```

**Después**:
```php
// ✅ Usa modelo con persistencia en DB
$settings = MailrelaySettings::instance();
MailrelaySettings::updateSettings($validated);
```

#### ApiSettingsController
**Archivo**: `modules/Mailrelay/app/Http/Controllers/Settings/ApiSettingsController.php`

**Antes**:
```php
// ❌ Usaba modelo parcial de app/Models
use App\Models\MailrelaySetting;
$settings = MailrelaySetting::getInstance();
```

**Después**:
```php
// ✅ Usa modelo completo del módulo
use Modules\Mailrelay\Entities\MailrelaySettings;
$settings = MailrelaySettings::instance();
```

## 🔄 Migración Existente vs Nueva

### Tabla Existente (2026_01_22)
```sql
CREATE TABLE mailrelay_settings (
    id BIGINT PRIMARY KEY,
    api_url VARCHAR(255),
    api_key VARCHAR(255),
    cache_enabled BOOLEAN DEFAULT true,
    cache_ttl INTEGER DEFAULT 3600,
    retry_enabled BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Nueva Migración (2026_01_25) - ALTER TABLE
```sql
ALTER TABLE mailrelay_settings ADD COLUMN (
    -- Sender (3 campos)
    sender_name VARCHAR(255),
    sender_email VARCHAR(255),
    reply_to_email VARCHAR(255),

    -- Sync (3 campos)
    auto_sync_enabled BOOLEAN DEFAULT false,
    sync_frequency INTEGER DEFAULT 60,
    sync_deleted BOOLEAN DEFAULT false,

    -- Limits (3 campos)
    emails_per_campaign INTEGER DEFAULT 1000,
    retry_attempts INTEGER DEFAULT 3,
    timeout INTEGER DEFAULT 30,

    -- Privacy (3 campos)
    double_optin BOOLEAN DEFAULT false,
    allow_unsubscribe BOOLEAN DEFAULT true,
    unsubscribe_footer TEXT,

    -- Advanced (3 campos)
    detailed_logging BOOLEAN DEFAULT false,
    log_retention_days INTEGER DEFAULT 30,
    sandbox_mode BOOLEAN DEFAULT false
);
```

## 📊 Métodos Principales del Modelo

### `instance(): self`
Patrón Singleton para obtener la única instancia de configuración.
```php
$settings = MailrelaySettings::instance();
echo $settings->sender_name; // "Mi Empresa"
```

### `get(string $key, mixed $default = null): mixed`
Obtener un valor específico de configuración.
```php
$senderName = MailrelaySettings::get('sender_name', 'Default Name');
$timeout = MailrelaySettings::get('timeout', 30);
```

### `set(string $key, mixed $value): bool`
Establecer un valor específico de configuración.
```php
MailrelaySettings::set('sandbox_mode', true);
MailrelaySettings::set('retry_attempts', 5);
```

### `updateSettings(array $data): bool`
Actualizar múltiples configuraciones a la vez (usado por controllers).
```php
MailrelaySettings::updateSettings([
    'sender_name' => 'Nueva Empresa',
    'sender_email' => 'nuevo@example.com',
    'sandbox_mode' => false,
]);
```

## 🔐 Seguridad

### Encriptación Automática
El campo `api_key` usa el cast `encrypted` de Laravel:
```php
protected function casts(): array
{
    return [
        'api_key' => 'encrypted',
        // ...
    ];
}
```

**Esto significa:**
- ✅ Al guardar: Laravel encripta automáticamente
- ✅ Al leer: Laravel desencripta automáticamente
- ✅ En DB: Almacenado cifrado con AES-256

## 🎨 Vistas Existentes

Las vistas YA ESTABAN implementadas y funcionan perfectamente:

### General Settings
**Vista**: `modules/Mailrelay/resources/views/settings/general.blade.php`
- ✅ 5 secciones con pestañas colapsables
- ✅ Validación jQuery
- ✅ Select2 para sync_frequency
- ✅ Form switches para checkboxes
- ✅ 549 líneas de código

### API Settings
**Vista**: `modules/Mailrelay/resources/views/settings/api.blade.php`
- ✅ Test de conexión con AJAX
- ✅ Mostrar info de cuenta si conectado
- ✅ Toggle de visibilidad para API key
- ✅ Validación de formulario
- ✅ 443 líneas de código

## 🚀 Pasos para Activar (Pendientes)

### 1. Ejecutar Migración
```bash
# Una vez resuelto el issue de Helpdesk module, ejecutar:
php artisan migrate --path=modules/Mailrelay/database/migrations/2026_01_25_120000_add_general_settings_to_mailrelay_settings_table.php
```

### 2. Verificar Tabla
```bash
# Conectarse a PostgreSQL
psql -U tu_usuario -d tu_database

# Verificar estructura de tabla
\d mailrelay_settings

# Debería mostrar 20 columnas ahora (5 originales + 15 nuevas)
```

### 3. Probar Configuración

#### Acceder a Settings
```
URL: http://tu-dominio/settings/mailrelay/general
Permiso: mailrelay.settings.general

URL: http://tu-dominio/settings/mailrelay/api
Permiso: mailrelay.settings.manage
```

#### Flujo de Prueba
1. Ir a `/settings/mailrelay/general`
2. Llenar formulario con datos de remitente
3. Configurar opciones de sincronización
4. Guardar configuración
5. Refrescar página - verificar que los datos persistan ✅

## 📝 Uso Programático

### Desde Cualquier Parte de la Aplicación

```php
use Modules\Mailrelay\Entities\MailrelaySettings;

// Obtener configuración completa
$settings = MailrelaySettings::instance();

// Usar en CampaignService
$campaign->sender_name = $settings->sender_name;
$campaign->sender_email = $settings->sender_email;

// Verificar si está en modo sandbox
if ($settings->sandbox_mode) {
    Log::info('Sandbox mode enabled - simulating send');
    return true;
}

// Respetar límites
if ($recipients->count() > $settings->emails_per_campaign) {
    throw new \Exception("Exceeded limit of {$settings->emails_per_campaign} emails");
}

// Usar timeout configurado
$response = Http::timeout($settings->timeout)
    ->get($url);
```

### En Providers y Services

```php
use Modules\Mailrelay\Entities\MailrelaySettings;

class MailrelayProvider implements MailProviderInterface
{
    public function send($to, $subject, $content, $options = [])
    {
        $settings = MailrelaySettings::instance();

        // Usar configuración de retry
        $maxAttempts = $settings->retry_enabled
            ? $settings->retry_attempts
            : 1;

        // Usar configuración de logging
        if ($settings->detailed_logging) {
            Log::debug('Sending email', compact('to', 'subject'));
        }

        // ...
    }
}
```

## 🔍 Comparación con Mailer Module

| Característica | Mailer | Mailrelay (Nuevo) |
|----------------|--------|-------------------|
| Configuración | `.env` variables | Base de datos |
| Interfaz UI | ❌ No tiene | ✅ Vistas completas |
| Persistencia | Archivos config | Tabla DB |
| Gestión | Manual (código) | Panel admin |
| Validación | ❌ Ninguna | ✅ Form validation |
| Encriptación | ❌ Plain text | ✅ Encrypted cast |
| Multi-provider | ❌ No | ✅ Sí (MailProvider) |
| Settings runtime | Config::get() | Model::instance() |

**Conclusión**: Mailrelay tiene un sistema de configuración MUCHO más avanzado que Mailer.

## ⚠️ Notas Importantes

### Deprecación de Modelo Antiguo
El modelo `App\Models\MailrelaySetting` ya NO se usa. Está obsoleto:
- ❌ `App\Models\MailrelaySetting` - Antiguo (solo API, 5 campos)
- ✅ `Modules\Mailrelay\Entities\MailrelaySettings` - Nuevo (completo, 20 campos)

**Acción recomendada**: Puedes eliminar `app/Models/MailrelaySetting.php` después de verificar que nada más lo use.

### Patrón Singleton
Solo debe existir UNA fila en la tabla `mailrelay_settings` con `id = 1`.

```php
// ✅ CORRECTO - Siempre devuelve la misma instancia
$settings1 = MailrelaySettings::instance(); // Crea o recupera id=1
$settings2 = MailrelaySettings::instance(); // Recupera id=1
// $settings1 === $settings2 (misma fila)

// ❌ INCORRECTO - No hacer esto
$settings = new MailrelaySettings(); // No usar constructor
$settings = MailrelaySettings::create([...]); // No crear registros adicionales
```

### Valores por Defecto
Si la tabla está vacía, `instance()` crea automáticamente un registro con valores por defecto tomados de:
1. Config files (`config('mail.from.name')`, etc.)
2. Hardcoded defaults (1000 emails, 3 retries, etc.)

## ✅ Checklist de Implementación

- [x] Crear modelo `MailrelaySettings` con 20 campos
- [x] Implementar patrón Singleton con `instance()`
- [x] Agregar cast `encrypted` para `api_key`
- [x] Crear migración para agregar 15 columnas nuevas
- [x] Actualizar `GeneralSettingsController` para usar modelo
- [x] Actualizar `ApiSettingsController` para usar modelo
- [x] Documentar uso programático
- [x] Documentar comparación con Mailer
- [ ] Ejecutar migración (pendiente issue Helpdesk)
- [ ] Probar formulario de General Settings
- [ ] Probar formulario de API Settings
- [ ] Verificar persistencia en DB
- [ ] Eliminar modelo obsoleto `App\Models\MailrelaySetting`

## 🎉 Resultado Final

Después de ejecutar la migración, tendrás:

1. **Panel de Configuración Completo**:
   - ✅ Configuración general (remitente, sync, límites, privacidad)
   - ✅ Configuración de API (credenciales, cache, retry)
   - ✅ Test de conexión en vivo
   - ✅ Persistencia en base de datos

2. **API Unificada**:
   ```php
   // Cualquier configuración desde un solo lugar
   $settings = MailrelaySettings::instance();
   ```

3. **Seguridad**:
   - ✅ API Key encriptada en DB
   - ✅ Validación de formularios
   - ✅ Autorización por permisos

4. **Flexibilidad**:
   - ✅ Valores configurables sin tocar código
   - ✅ Modo sandbox para testing
   - ✅ Logging configurable
   - ✅ Límites ajustables

---

**Implementado por**: Development Team
**Fecha**: 2026-01-25
**Contacto**: dev@alsernet.com
