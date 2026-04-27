# Database

> Database Management - Settings, configuration and cleanup tools

## Proposito

Provee herramientas administrativas para gestionar la conexion a base de datos y limpiar tablas desde el panel de configuracion. Permite a administradores verificar la conexion, modificar parametros de configuracion, y truncar tablas seleccionadas cuando se requiere un reset de datos.

## Componentes principales

- **Modelos**: Ninguno (trabaja directamente con la conexion de DB)
- **Controladores**: `DatabaseSettingsController`, `DatabaseCleanupController`
- **Rutas**: `panel/settings/database` (prefijo aplicado por ServiceProvider)
  - `GET /` — ver configuracion
  - `GET /edit` — formulario de edicion
  - `PUT /update` — actualizar configuracion
  - `POST /check-connection` — verificar conexion
  - `GET /cleanup` — herramienta de limpieza
  - `POST /cleanup/truncate` — truncar tabla
  - `POST /cleanup/table-count` — contar registros

## Permisos

| Permiso | Descripcion |
|---------|-------------|
| `database.backups.view` | Ver configuracion de base de datos |
| `database.backups.update` | Actualizar configuracion |
| `database.backups.test_connection` | Probar conexion |
| `database.cleanup.view` | Ver herramienta de limpieza |
| `database.cleanup.truncate` | Truncar tablas |
| `database.cleanup.get_table_count` | Obtener conteo de registros |

Roles asignados: `super-settings` (todos), `manager` (solo view).

## Configuracion

- Archivo: `config/config.php`
- Variables env relevantes:
  - `CMS_ENABLED_CLEANUP_DATABASE` — habilita la funcionalidad de cleanup (default: `false`)

## Cambios criticos en truncate

> **IMPORTANTE**: `truncate()` ahora requiere confirmacion reforzada antes de ejecutar.

### Flujo de confirmacion

El endpoint `POST /cleanup/truncate` ahora valida via `TruncateTablesRequest`:

1. **Password confirm** — el usuario debe ingresar su contrasena actual
2. **Checkbox de confirmacion** — campo `confirmed` requerido en `true`
3. **Lista de tablas** — solo se aceptan tablas no protegidas
4. **Backup automatico previo** — se dispara un snapshot antes de truncar

### Tablas protegidas

18 tablas estan hardcoded en `DatabaseCleanupController::PROTECTED_TABLES` y nunca pueden ser truncadas, independientemente de los permisos del usuario:

`users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `settings`, `migrations`, `failed_jobs`, `personal_access_tokens`, `password_reset_tokens`, `sessions`, `oauth_access_tokens`, `oauth_clients`, `oauth_auth_codes`, `oauth_refresh_tokens`, `telescope_entries`, `pulse_entries`

### Form Request

`TruncateTablesRequest` — valida `table` (string, not in protected list), `password` (current user password), `confirmed` (boolean true).

## Dependencias

- **Core**: Si (usa `Setting` model indirectamente)
- Otros: `doctrine/dbal` (en vendor local para operaciones de schema)
