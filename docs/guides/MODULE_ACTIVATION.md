# Gestión de Módulos Activos/Inactivos

## Descripción General

El sistema Alsernet implementa un completo control de activación/desactivación de módulos que garantiza que:

1. **Los módulos desactivados NO se cargan** en la aplicación
2. **Los módulos desactivados NO aparecen** en la navegación
3. **Los módulos desactivados NO son accesibles** mediante URLs

## Cómo Funciona

### 1. Estado de Módulos (`modules_statuses.json`)

El archivo `modules_statuses.json` en la raíz del proyecto define qué módulos están activos:

```json
{
    "Activity": true,       // Módulo activo
    "Analytics": true,
    "Auth": true,
    "Campaign": false,      // Módulo desactivado
    "Core": true,
    "Event": false,         // Módulo desactivado
    ...
}
```

### 2. Carga de ServiceProviders (`bootstrap/providers.php`)

Los ServiceProviders se cargan dinámicamente basándose en `modules_statuses.json`:

```php
// Solo carga providers para módulos con status=true
if (isset($modulesStatus[$moduleName]) && $modulesStatus[$moduleName] === true) {
    $providers[] = $providerClass;
}
```

**Beneficio:** Los módulos desactivados no registran menús en `NavService`

### 3. Protección de Rutas (`EnsureModuleIsActive` Middleware)

Un middleware global valida que cada petición sea a un módulo activo:

```php
// En bootstrap/app.php
$middleware->group('web', [
    // ... otros middlewares
    \Modules\Core\Http\Middleware\EnsureModuleIsActive::class,
]);
```

**Beneficio:** Intenta acceso a `/manager/events` → 404 (si Event está desactivado)

## Cómo Activar/Desactivar Módulos

### Opción 1: Modificar `modules_statuses.json` Directamente

```bash
# Desactivar el módulo Event
vim modules_statuses.json
# Cambiar: "Event": true → "Event": false
```

### Opción 2: Usar Comando Artisan

```bash
# Desactivar un módulo
php artisan module:toggle Event --action=disable

# Activar un módulo
php artisan module:toggle Campaign --action=enable

# Alternar estado
php artisan module:toggle Event
```

### Opción 3: Panel de Administración

```
Configuración → Módulos → [Módulo] → Deshabilitar/Habilitar
```

## Módulos Críticos (Siempre Activos)

Los siguientes módulos siempre se cargan, incluso si están marcados como `false` en `modules_statuses.json`:

- **Core**: Sistema base y funcionalidad fundamental
- **Auth**: Autenticación y sesiones
- **Role**: Gestión de roles y permisos
- **Theme**: Tema visual y navegación
- **Modules**: Gestor de módulos

Estos módulos no se pueden desactivar para proteger la integridad de la aplicación.

## Módulos Desactivados por Defecto

Actualmente, los siguientes módulos están desactivados:

| Módulo | Razón |
|--------|-------|
| Campaign | En desarrollo |
| Event | En desarrollo |
| Helpdesk | En desarrollo |
| Faq | En desarrollo |
| Mail | Reemplazado por Mailer |
| Return | En desarrollo |

## Archivos Modificados

### 1. `bootstrap/providers.php`
- Lee dinámicamente `modules_statuses.json`
- Filtra ServiceProviders basado en estado del módulo
- Mantiene módulos críticos siempre activos

**Estrategia:**
```php
$modulesStatus = json_decode(file_get_contents($modulesStatusFile), true);

foreach ($allProviders as $providerClass => $moduleName) {
    if ($moduleName === true) {
        // Módulo crítico: siempre cargar
        $providers[] = $providerClass;
    } elseif (isset($modulesStatus[$moduleName]) && $modulesStatus[$moduleName]) {
        // Módulo normal: cargar solo si está activo
        $providers[] = $providerClass;
    }
}
```

### 2. `modules/Core/app/Http/Middleware/EnsureModuleIsActive.php`
- Middleware global que valida cada petición
- Extrae el módulo de la ruta (Event, Campaign, etc.)
- Devuelve 404 si el módulo está desactivado

**Estrategias de Extracción:**
1. Parámetro de ruta: `route('module')`
2. Mapeo de rutas: `/manager/events` → Event
3. Nombre de ruta: `manager.events.*` → Event

### 3. `bootstrap/app.php`
- Registra el middleware en el grupo 'web'
- Se ejecuta después de SubstituteBindings

## Testing

### Verificar Módulos Cargados

```bash
php artisan tinker
> app()->getProviders() | head -20
```

### Verificar Acceso a Módulo Desactivado

```bash
# Event está desactivado
curl -I http://localhost:8000/manager/events
# Respuesta esperada: 404

# Core está activo
curl -I http://localhost:8000/manager
# Respuesta esperada: 200 o redirect
```

### Verificar Navegación

```bash
# Iniciar sesión y visitar dashboard
# Los menús del módulo Event NO deben aparecer en la navegación
```

## Casos de Uso

### Caso 1: Ocultar un Módulo en Desarrollo

```json
{
    "Event": false,  // Está en desarrollo, no lo muestres a usuarios
    ...
}
```

**Resultado:**
- ❌ ServiceProvider no se carga (ahorra memoria)
- ❌ Menú no aparece en navegación
- ❌ URLs son inaccesibles (404)

### Caso 2: Habilitar un Módulo Nuevamente

```json
{
    "Event": true,  // Está listo para producción
    ...
}
```

**Resultado:**
- ✅ ServiceProvider se carga
- ✅ Menú aparece en navegación (si usuario tiene permisos)
- ✅ URLs son accesibles

## Desarrollo de Nuevos Módulos

Cuando creas un nuevo módulo:

1. **Registra el ServiceProvider en `bootstrap/providers.php`:**
   ```php
   'Modules\MyModule\Providers\MyModuleServiceProvider' => 'MyModule',
   ```

2. **Agrega el módulo a `modules_statuses.json`:**
   ```json
   {
       "MyModule": true,  // o false si aún está en desarrollo
   }
   ```

3. **Implementa `registerMenus()` en tu ServiceProvider:**
   ```php
   protected function registerMenus(): void
   {
       NavService::registerMiniItem('mymodule', [
           'icon' => 'fa-icon',
           'tooltip' => 'My Module',
           'sidebar_id' => 'mymodule',
       ]);
   }
   ```

## Solución de Problemas

### Problema: Módulo aparece en navegación pero está desactivado

**Causa:** El módulo se agregó al status file después de que se cargó el provider

**Solución:**
```bash
# Opción 1: Borrar cache
php artisan cache:clear

# Opción 2: Reiniciar el servidor
php artisan serve
```

### Problema: Middleware no bloquea acceso a módulo desactivado

**Causa:** La ruta no está mapeada en el middleware

**Solución:** Agregar mapeo en `EnsureModuleIsActive.php`:
```php
$routeModuleMap = [
    'manager/mymodule' => 'MyModule',  // Agregar aquí
    ...
];
```

### Problema: Módulo crítico no se carga

**Causa:** No está en la lista de `$criticalModules`

**Solución:** Agregarlo es arriesgado. Contacta con el equipo técnico.

## Mejores Prácticas

✅ **Desactiva** módulos en desarrollo para mantener la aplicación limpia
✅ **Usa** `php artisan module:toggle` para cambios frecuentes
✅ **Documenta** por qué un módulo está desactivado
✅ **Prueba** después de cambiar estado de módulos
❌ **No hagas** cambios directos a `modules_statuses.json` en producción
❌ **No desactives** módulos críticos (Core, Auth, Role, Theme, Modules)

## Variables de Entorno

No hay variables de entorno específicas para módulos. El estado se define en `modules_statuses.json`.

Si necesitas control por entorno:
```bash
# En .env
MODULE_STATE_FILE=modules_statuses.${APP_ENV}.json

# Luego usar: $statusFile = base_path(env('MODULE_STATE_FILE', 'modules_statuses.json'));
```

## Referencias

- Archivo de Estado: `modules_statuses.json`
- Providers Dinámicos: `bootstrap/providers.php`
- Middleware de Validación: `modules/Core/app/Http/Middleware/EnsureModuleIsActive.php`
- Configuración: `bootstrap/app.php`
- Servicio de Navegación: `modules/Theme/app/Services/NavService.php`
