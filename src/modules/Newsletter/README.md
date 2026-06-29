# Newsletter

> Newsletter subscribers management

## Proposito

Gestiona la lista de suscriptores al newsletter del sitio. Permite ver, exportar en CSV y eliminar suscriptores desde el panel de administracion. La suscripcion publica se realiza via una ruta sin autenticacion con rate limiting.

## Componentes principales

- **Modelos**: `Subscriber`
- **Controladores**: `SubscriberController`
- **Rutas**:
  - `routes/web.php` — panel admin (index, bulk-action, export, destroy)
  - `routes/public.php` — formulario de suscripcion publica (sin auth, throttled)
  - `routes/settings.php` — configuracion del modulo

## Permisos

| Permiso | Descripcion |
|---------|-------------|
| `Newsletter.subscribers.index` | Ver listado de suscriptores |
| `Newsletter.subscribers.manage` | Gestionar suscriptores (eliminar, bulk) |
| `Newsletter.settings.manage` | Gestionar configuracion del modulo |

Roles asignados: `super-settings` (todos), `settings` (excepto settings.manage).

## Configuracion

- Archivo: `config/config.php` (minimo, sin claves especiales)
- Variables env relevantes: Ninguna especifica del modulo

## Dependencias

- **Core**: Si
- Otros: Ninguno
