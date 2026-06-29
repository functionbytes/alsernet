# System

> System configuration, monitoring, and management module

## Proposito

Panel de administracion del sistema para operadores y super-admins. Cubre monitoreo de colas, gestion de cache, logs de acceso al servidor, control de procesos Supervisor, configuracion de uploads, modo mantenimiento e informacion tecnica del sistema. Las rutas de escritura requieren roles elevados.

## Componentes principales

- **Modelos**: Ninguno
- **Controladores** (en `Settings/`):
  - `SystemSettingsController` — configuracion de colas y websockets
  - `SystemCacheController` — limpieza y gestion de cache
  - `SystemInfoController` — informacion tecnica del servidor
  - `SupervisorController` — procesos Supervisor, backups, scheduler
  - `ServerAccessController` — logs de acceso y descarga
  - `UploadingSettingsController` — configuracion de uploads
  - `MantenanceSettingsController` — modo mantenimiento
  - `GlobalSearchController`, `ImportController`, `ApiDocController`
- **Rutas**: `panel/setting/system` (prefijo `settings.system.`)

## Rutas principales

| Seccion | Prefijo |
|---------|---------|
| Principal | `settings.system.index` |
| Info del sistema | `settings.system.info.*` |
| Cache | `settings.system.cache.*` |
| Logs de acceso | `settings.system.access.*` |
| Supervisor | `settings.system.supervisor.*` |
| Uploads | `settings.system.uploading.*` |
| Mantenimiento | `settings.system.maintenance.*` |

Las rutas de escritura (restart, truncate, clear) requieren role `super-settings|administrative|manager`.

## Permisos

Controlados por roles de Spatie en middleware de ruta, no por permisos granulares individuales:
- Lectura: `auth` + `settings` middleware
- Escritura: role `super-settings|administrative|manager`

## Configuracion

- Archivo: Sin `config/*.php` propio (usa configuracion de Laravel directamente)
- Variables env relevantes: Las de queue, redis, websockets y mail ya configuradas en `.env` del proyecto

## Dependencias

- **Core**: Si
- Otros: `torann/geoip` (GeoIP para logs de acceso), Supervisor (proceso externo)
