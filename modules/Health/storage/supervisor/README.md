# Supervisor Configuration Storage

Este directorio almacena los archivos de configuración de Supervisor generados automáticamente.

## Generación

Los archivos `.conf` se generan automáticamente usando:

### Desde el Dashboard
1. Ir a `/settings/health`
2. Sección "Configuración de Supervisor"
3. Click en "Generar configuración"

### Desde la Terminal
```bash
php artisan health:supervisor-config
```

## Contenido

- `{app-name}-worker.conf` - Archivo de configuración de Supervisor generado

## Nota

Los archivos `.conf` generados NO se versionan en Git (están en `.gitignore`) porque contienen rutas específicas de cada servidor/entorno.

Cada entorno (desarrollo, staging, producción) debe generar su propio archivo de configuración.
