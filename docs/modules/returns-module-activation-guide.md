# Guía de Activación del Módulo Returns

## Descripción General

Esta guía explica cómo habilitar el módulo Returns y resolver problemas de autoloading de PSR-4.

---

## Estado Actual

**Módulo**: Returns (Devoluciones)
**Estado**: ✅ Refactorizado pero Deshabilitado
**Ubicación**: `Modules/Returns/`
**Problema Bloqueante**: Configuración de PSR-4 autoload

---

## Requisitos Previos

1. **Git Status Limpio**
   ```bash
   git status
   # No debe haber cambios no comprometidos en composer.json
   ```

2. **Composer Actualizado**
   ```bash
   composer --version
   # Requiere Composer 2.0+
   ```

3. **Migraciones Ejecutadas**
   ```bash
   php artisan migrate
   # Todas las tablas necesarias deben existir
   ```

---

## Paso 1: Configurar PSR-4 Autoload

### 1.1 Editar composer.json

Abrir `/composer.json` y ubicar la sección `autoload`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/",
            "Modules\\Documents\\": "modules/Document/app/",
            "Modules\\Mail\\": "modules/Mail/app/",
            // AGREGAR ESTA LÍNEA:
            "Modules\\Returns\\": "modules/Return/app/"
        }
    }
}
```

**Línea a agregar**:
```json
"modules\\Return\\": "modules/Return/app/"
```

### 1.2 Ubicación Exacta

Después de esta línea:
```json
"modules\\Document\\": "modules/Document/app/",
```

Agregar:
```json
"modules\\Return\\": "modules/Return/app/",
```

**Archivo Completo de Referencia**:
```json
{
    "name": "alsernet/manager",
    "description": "Alsernet Manager Application",
    "type": "project",
    "require": {
        "php": "^8.4"
        // ... otras dependencias
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/",
            "Modules\\Campaign\\": "modules/Campaign/app/",
            "Modules\\Documents\\": "modules/Document/app/",
            "Modules\\Mail\\": "modules/Mail/app/",
            "Modules\\Prestashop\\": "modules/Prestashop/app/",
            "Modules\\Returns\\": "modules/Return/app/",
            "Modules\\Subscriber\\": "modules/Subscriber/app/",
            "Modules\\Warehouse\\": "modules/Warehouse/app/"
        }
    }
}
```

---

## Paso 2: Actualizar Autoloader

Después de modificar `composer.json`, regenerar el autoloader:

```bash
composer dump-autoload
```

**Verificación**:
```bash
# El comando debe completarse sin errores
echo $?  # Debe mostrar 0 (éxito)
```

---

## Paso 3: Habilitar Módulo en modules_statuses.json

### 3.1 Ubicar Archivo

Archivo: `/modules_statuses.json`

### 3.2 Cambiar Estado

**Antes**:
```json
{
    "Documents": true,
    "Mail": false,
    "Prestashop": false,
    "Returns": false,
    "Warehouse": false
}
```

**Después**:
```json
{
    "Documents": true,
    "Mail": false,
    "Prestashop": false,
    "Returns": true,
    "Warehouse": false
}
```

### 3.3 Guardar

```bash
# Verificar cambios
git diff modules_statuses.json
```

---

## Paso 4: Registrar Service Provider

### 4.1 Actualizar bootstrap/providers.php

Ubicar el archivo: `/bootstrap/providers.php`

### 4.2 Agregar Proveedor de Servicio

Buscar la sección de módulos:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    // ... otros providers

    // Módulos
    \Modules\Documents\Providers\DocumentsServiceProvider::class,
    // AGREGAR ESTA LÍNEA:
    \Modules\Returns\Providers\ReturnsServiceProvider::class,
];
```

**Nota**: Agregar después de DocumentsServiceProvider pero antes de otros módulos.

### 4.3 Verificar Orden

El orden correcto es:
```php
// App providers primero
\App\Providers\AppServiceProvider::class,
\App\Providers\EventServiceProvider::class,

// Módulos después
\Modules\Documents\Providers\DocumentsServiceProvider::class,
\Modules\Returns\Providers\ReturnsServiceProvider::class,
```

---

## Paso 5: Ejecutar Bootstrap

### 5.1 Limpiar Cache

```bash
php artisan config:cache
php artisan cache:clear
php artisan view:clear
```

### 5.2 Verificar que la Aplicación Inicia

```bash
php artisan tinker
>>> exit
```

El comando debe completarse sin errores.

---

## Paso 6: Ejecutar Migraciones (Si Necesario)

### 6.1 Verificar Estado de Migraciones

```bash
php artisan migrate:status
```

### 6.2 Ejecutar Migraciones del Módulo

```bash
# Si hay migraciones pendientes
php artisan migrate

# O específicamente del módulo Return
php artisan migrate --path=database/migrations/returns
```

### 6.3 Ejecutar Seeders

```bash
# Seeders específicos de Return
php artisan db:seed --class=modules\\Return\\Database\\Seeders\\ReturnStatusSeeder
php artisan db:seed --class=modules\\Return\\Database\\Seeders\\ReturnReasonSeeder
```

---

## Paso 7: Pruebas de Validación

### 7.1 Verificar Que las Clases Son Autoloadables

```bash
php artisan tinker

>>> class_exists('modules\\Return\\Models\\ReturnRequest')
=> true

>>> class_exists('modules\\Return\\Services\\ReturnService')
=> true

>>> exit
```

### 7.2 Verificar que el Módulo se Cargó

```bash
php artisan tinker

>>> app(\Modules\Returns\Services\ReturnService::class)
# Debe retornar instancia del servicio

>>> exit
```

### 7.3 Verificar Rutas del Módulo

```bash
php artisan route:list | grep returns
```

Deberías ver rutas como:
```
GET|HEAD        api/returns               ...
POST            api/returns               ...
GET|HEAD        manager/settings/returns/rules  ...
```

---

## Paso 8: Ejecutar Tests

### 8.1 Tests del Módulo Returns

```bash
php artisan test modules/Return/tests
```

### 8.2 Tests Completos

```bash
php artisan test
```

**Resultado Esperado**: Algunos tests pueden fallar por dependencias de otros módulos, pero no debe haber errores de clase no encontrada del módulo Returns.

---

## Troubleshooting

### Problema: Class "Modules\Returns\..." not found

**Causa**: El autoloader no se regeneró correctamente.

**Solución**:
```bash
# 1. Limpiar composer cache
rm -rf vendor/composer/installed.php

# 2. Regenerar autoload
composer dump-autoload

# 3. Limpiar cache de Laravel
php artisan cache:clear
php artisan config:cache
```

---

### Problema: Service Provider not found

**Causa**: El proveedor no se registró en `bootstrap/providers.php`

**Solución**:
1. Verificar que la línea está en `bootstrap/providers.php`
2. Verificar que no hay typos en el namespace
3. Ejecutar `php artisan cache:clear`

---

### Problema: Rutas no se cargan

**Causa**: El RouteServiceProvider no se registró correctamente.

**Solución**:
```bash
# 1. Verificar ruta en ReturnsServiceProvider
grep -n "RouteServiceProvider" modules/Return/app/Providers/ReturnsServiceProvider.php

# 2. Limpiar cache de rutas
php artisan route:clear

# 3. Listar rutas nuevamente
php artisan route:list | grep returns
```

---

### Problema: Base de datos - tabla no existe

**Causa**: Las migraciones del módulo no se han ejecutado.

**Solución**:
```bash
# 1. Verificar estado de migraciones
php artisan migrate:status

# 2. Ejecutar migraciones
php artisan migrate

# 3. Si falla, ver el error específico
php artisan migrate:reset
php artisan migrate
```

---

## Verificación Completa

### Checklist de Activación

- [ ] `composer.json` actualizado con PSR-4 para Returns
- [ ] `composer dump-autoload` ejecutado
- [ ] `modules_statuses.json` actualizado a `"Returns": true`
- [ ] `bootstrap/providers.php` incluye ReturnsServiceProvider
- [ ] `php artisan tinker` se ejecuta sin errores
- [ ] Clases de Returns son encontradas por autoloader
- [ ] `php artisan route:list` muestra rutas de returns
- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Tests pasan (`php artisan test Modules/Returns/tests`)

### Comando de Verificación Rápida

```bash
# Todo en uno
composer dump-autoload && \
php artisan config:cache && \
php artisan route:clear && \
php artisan tinker << 'EOF'
echo "Testing Returns Module...\n";
echo "1. ReturnRequest: " . (class_exists('Modules\Returns\Models\ReturnRequest') ? "✓" : "✗") . "\n";
echo "2. ReturnService: " . (class_exists('Modules\Returns\Services\ReturnService') ? "✓" : "✗") . "\n";
echo "3. ReturnsServiceProvider: " . (class_exists('Modules\Returns\Providers\ReturnsServiceProvider') ? "✓" : "✗") . "\n";
echo "4. Routes loaded: " . count(Route::getRoutes()) . " routes\n";
exit
EOF
```

---

## Desactivación del Módulo

Si necesitas desactivar el módulo temporalmente:

### 1. Cambiar modules_statuses.json
```json
{
    "Returns": false
}
```

### 2. Comentar en bootstrap/providers.php
```php
// \modules\Return\Providers\ReturnsServiceProvider::class,
```

### 3. Limpiar Cache
```bash
php artisan config:cache
php artisan cache:clear
```

**Nota**: No necesitas remover la línea de PSR-4 en `composer.json`

---

## Cambios Realizados en Esta Sesión

### Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `composer.json` | **Pendiente** - Agregar PSR-4 para Returns |
| `bootstrap/providers.php` | **Pendiente** - Registrar ReturnsServiceProvider |
| `modules_statuses.json` | Cambiar `"Returns"` a `true` |
| `app/Providers/AppServiceProvider.php` | ✅ Registrar HookManager |
| `app/Library/Facades/Hook.php` | ✅ Crear Hook Facade |
| `app/Library/HookManager.php` | ✅ Crear HookManager |

### Cambios Pendientes

```bash
# Ver cambios que faltan
git status

# Ver cambios exactos
git diff composer.json
git diff bootstrap/providers.php
```

---

## Referencia Rápida

```bash
# Habilitar módulo
cat > /tmp/enable_returns.sh << 'EOF'
#!/bin/bash
# 1. Actualizar composer.json (manual)
# 2. Regenerar autoload
composer dump-autoload
# 3. Habilitar en modules_statuses.json
sed -i 's/"Returns": false/"Returns": true/' modules_statuses.json
# 4. Registrar provider (manual)
# 5. Limpiar cache
php artisan config:cache
php artisan cache:clear
# 6. Verificar
php artisan tinker << 'EOF2'
echo class_exists('Modules\Returns\Models\ReturnRequest') ? "✓ Returns Module Active\n" : "✗ Module Failed\n";
exit;
EOF2
EOF

chmod +x /tmp/enable_returns.sh
bash /tmp/enable_returns.sh
```

---

## Soporte

Si encuentras problemas durante la activación:

1. **Verificar logs**: `storage/logs/laravel.log`
2. **Revisar migraciones**: `php artisan migrate:status`
3. **Limpiar cache completo**: `php artisan cache:clear && php artisan config:clear`
4. **Regenerar autoload**: `composer dump-autoload -o`

---

**Última Actualización**: 29 de Diciembre de 2025
**Status**: Guía Completa ✅
