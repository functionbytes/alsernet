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

## Dependencias

- **Core**: Si (usa `Setting` model indirectamente)
- Otros: `doctrine/dbal` (en vendor local para operaciones de schema)
