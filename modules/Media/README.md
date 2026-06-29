# Media Module

Sistema completo de gestión de archivos multimedia para Alsernet.

## Características

- Gestión de archivos y carpetas
- Soporte multi-disco (local, S3, etc.)
- Sistema de carpetas jerárquico
- Papelera (soft deletes)
- Favoritos
- Subir desde URL
- Metadatos automáticos (dimensiones de imágenes)
- Multi-usuario
- Sistema de permisos

## Rutas

### Manager Routes (`/manager/media/`)
- GET / - Vista principal del Media Manager
- GET /list - Listar archivos y carpetas (AJAX)
- POST /upload - Subir archivo
- POST /upload-url - Subir desde URL
- POST /folder/create - Crear carpeta
- PUT /file/{file}/rename - Renombrar archivo
- PUT /folder/{folder}/rename - Renombrar carpeta
- POST /file/{file}/copy - Copiar archivo
- DELETE /file/{file} - Eliminar archivo (soft delete)
- DELETE /folder/{folder} - Eliminar carpeta (soft delete)
- POST /file/{file}/restore - Restaurar archivo
- POST /folder/{folder}/restore - Restaurar carpeta
- PUT /file/{file}/move - Mover archivo
- PUT /folder/{folder}/move - Mover carpeta
- POST /file/{file}/toggle-favorite - Toggle favorito
- DELETE /trash/empty - Vaciar papelera

## Modelos

### MediaFile
- Archivos multimedia con metadatos
- Soft deletes
- Scope: byUser, ofType, public
- Relaciones: folder, user

### MediaFolder
- Carpetas jerárquicas
- Soft deletes
- Cascade delete/restore de archivos
- Relaciones: files, parent, children, user

## Licencia

Proprietary - Alsernet
