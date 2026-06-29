# Modules Management Module

Un módulo completo para administrar, instalar, desinstalar, habilitar y deshabilitar todos los módulos del sistema Alsernet.

## Características

- ✅ **Ver todos los módulos** - Listado completo de módulos con estado actual
- ✅ **Habilitar módulos** - Activar módulos deshabilitados
- ✅ **Deshabilitar módulos** - Desactivar módulos sin eliminarlos
- ✅ **Instalar módulos** - Subir archivos ZIP de nuevos módulos
- ✅ **Desinstalar módulos** - Eliminar módulos completamente (no disponible para módulos protegidos)
- ✅ **Ver detalles** - Información detallada de cada módulo
- ✅ **Artisan Commands** - Administración desde línea de comandos
- ✅ **Protección de módulos core** - Los módulos Role y Modules no pueden ser deshabilitados o desinstalados

## Rutas Web

```
GET  /modules                          # Listar todos los módulos
GET  /modules/{moduleAlias}            # Ver detalles de un módulo
POST /modules/{moduleAlias}/enable     # Habilitar un módulo
POST /modules/{moduleAlias}/disable    # Deshabilitar un módulo
GET  /modules/upload/form              # Mostrar formulario de instalación
POST /modules/install                  # Instalar un nuevo módulo
POST /modules/{moduleAlias}/uninstall  # Desinstalar un módulo
```

## Artisan Commands

### Ver estado de todos los módulos

```bash
php artisan modules:status
```

Muestra una tabla con:
- Nombre del módulo
- Alias
- Estado (Enabled/Disabled)
- Prioridad
- Versión

### Alternar estado de un módulo

```bash
# Cambiar estado (habilitar si está deshabilitado, deshabilitar si está habilitado)
php artisan module:toggle NombreDelModulo

# Habilitar específicamente
php artisan module:toggle NombreDelModulo --action=enable

# Deshabilitar específicamente
php artisan module:toggle NombreDelModulo --action=disable
```

## Ejemplo de uso

### A través de la interfaz web

1. Ir a `/modules` para ver el listado
2. Hacer clic en "Instalar módulo" para subir un nuevo módulo
3. Seleccionar un archivo ZIP que contenga el módulo
4. El módulo se instalará automáticamente
5. Usar los botones de acciones para habilitar/deshabilitar/desinstalar

### A través de Artisan

```bash
# Ver estado actual
php artisan modules:status

# Habilitar el módulo Document
php artisan module:toggle Document --action=enable

# Deshabilitar el módulo Campaign
php artisan module:toggle Campaign --action=disable

# Alternar estado del módulo Mailer
php artisan module:toggle Mailer
```

## Estructura esperada de un módulo para instalar

Cuando instales un módulo, el archivo ZIP debe tener la siguiente estructura:

```
ModuleName/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   ├── Providers/
│   │   └── ModuleNameServiceProvider.php
│   └── Console/
│       └── Commands/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   └── assets/
├── routes/
│   ├── web.php
│   └── api.php
├── config/
│   └── config.php
├── module.json (REQUERIDO)
├── composer.json (opcional)
└── README.md (opcional)
```

### Contenido mínimo de module.json

```json
{
    "name": "MyModule",
    "alias": "mymodule",
    "description": "Descripción del módulo",
    "version": "1.0.0",
    "priority": 0,
    "providers": [
        "Modules\\MyModule\\Providers\\MyModuleServiceProvider"
    ],
    "files": []
}
```

## Módulos protegidos

Los siguientes módulos no pueden ser deshabilitados ni desinstalados:
- `Role` - Sistema de roles y permisos
- `Modules` - Administración de módulos

## Notas de desarrollo

- Todos los módulos deben tener un archivo `module.json` en su raíz
- El servicio provider del módulo se registra automáticamente
- Los módulos se cargan en orden de prioridad
- La desinstalación es permanente y no se puede deshacer
- Se recomienda respaldar los datos antes de desinstalar módulos
- La interfaz requiere autenticación

## Gestión de permisos

Para acceder a este módulo, se requiere:
- Estar autenticado
- Verificar email

En futuras versiones se puede agregar:
- Permisos granulares por acción
- Auditoría de cambios en módulos
- Backup automático antes de desinstalar
