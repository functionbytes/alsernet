# Storage

> Storage configuration and management module

## Proposito

Permite configurar y gestionar discos de almacenamiento de archivos desde el panel de administracion. Soporta los drivers `local`, `ftp`, `sftp` y `s3`. Los discos `local` y `public` son de sistema y no se pueden eliminar. Al crear un disco, se valida la conexion antes de guardarlo en la configuracion dinamica de Laravel.

## Componentes principales

- **Modelos**: Ninguno (persiste configuracion via `Setting` model de Core)
- **Controladores**: `StorageController`
- **Rutas**: `panel/settings/storage` (prefijo `storage`)
  - `GET /` — listar discos configurados
  - `GET /create` — formulario de nuevo disco
  - `POST /store` — guardar disco
  - `POST /test-connection` — verificar conexion al disco
  - `GET /{name}/edit` — editar disco existente
  - `PATCH /{name}` — actualizar disco
  - `DELETE /` — eliminar disco

## Permisos

| Permiso | Descripcion |
|---------|-------------|
| `storage.view` | Ver configuracion de almacenamiento |
| `storage.create` | Crear discos de almacenamiento |
| `storage.update` | Editar discos |
| `storage.delete` | Eliminar discos |
| `storage.manage` | Gestion completa |

Roles asignados: `super-settings` y `settings`.

## Configuracion

- Archivo: `config/storage.php`
- Drivers soportados: `local`, `ftp`, `sftp`, `s3`
- Discos de sistema (no eliminables): `local`, `public`
- Limites: max file size 102400 MB, max disk size 1000000 MB
- Variables env relevantes: Ninguna especifica (usa configuracion del disco de Laravel)

## Dependencias

- **Core**: Si (usa `Setting` model para persistir configuracion de discos)
- Otros: Ninguno
