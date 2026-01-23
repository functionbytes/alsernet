# Sistema de Menús Administrativos (NavService)

## 📋 Descripción General

El `NavService` es un sistema centralizado para gestionar dinámicamente la navegación del panel administrativo. Permite que cada módulo registre sus propios items de menú sin necesidad de modificar código central.

**Beneficios:**
- ✅ Modular: Cada módulo define su propia navegación
- ✅ Dinámico: Los menús se registran en tiempo de ejecución
- ✅ Escalable: Agregar nuevos módulos es trivial
- ✅ Centralizado: Un único punto de verdad para la navegación
- ✅ Mantenible: No requiere modificar vistas centrales

## 🏗️ Arquitectura

```
NavService (app/Services/NavService.php)
    └── Gestiona la estructura de menús

MenuServiceProvider (app/Providers/MenuServiceProvider.php)
    └── Registra menús del core y coordina módulos

nav.blade.php (resources/views/theme/includes/)
    └── Renderiza dinámicamente mini-nav items y sidebars

Module ServiceProviders
    └── Cada módulo registra sus menús en su boot()
```

## 📝 Cómo Usar

### 1. Registrar items en el Mini-Nav (navegación con iconos)

En el `ServiceProvider` de tu módulo, en el método `boot()`:

```php
use App\Services\AdminMenuService;

public function boot(): void
{
    // Registrar item en mini-nav
    NavService::registerMiniItem('my-module', [
        'icon' => 'fa-rocket',           // Icono Font Awesome (requerido)
        'tooltip' => 'Mi Módulo',        // Texto del tooltip (requerido)
        'sidebar_id' => 'my-module',     // ID del sidebar a abrir (requerido)
        'order' => 30,                   // Orden de aparición (opcional, default: 999)
    ]);
}
```

### 2. Registrar items en un Sidebar (menú desplegable)

```php
public function boot(): void
{
    // Registrar sidebar
    NavService::registerSidebar('my-module', [
        'title' => 'Mi Módulo',
        'items' => [
            [
                'label' => 'Dashboard',
                'route' => 'manager.my-module.dashboard',
            ],
            [
                'label' => 'Configuración',
                'route' => 'manager.my-module.backups',
                'icon' => 'fa-cog',  // Opcional
            ],
            [
                'label' => 'Reportes',
                'route' => 'manager.my-module.reports',
            ],
        ],
    ]);
}
```

### 3. Usar en la vista

En tu layout principal:

```blade
<x-admin.sidebar-navigation :activeMiniNav="$activeMiniNav ?? null" />
```

O simplemente:

```blade
@include('components.admin.sidebar-navigation')
```

## 📚 Ejemplos Completos

### Ejemplo 1: Módulo de Marketing

```php
// modules/Marketing/app/Providers/MarketingServiceProvider.php

namespace Modules\Marketing\Providers;

use App\Services\AdminMenuService;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Mini-nav item
        NavService::registerMiniItem('marketing', [
            'icon' => 'fa-bullhorn',
            'tooltip' => 'Marketing',
            'sidebar_id' => 'marketing',
            'order' => 25,
        ]);

        // Sidebar
        NavService::registerSidebar('marketing', [
            'title' => 'Marketing',
            'items' => [
                ['label' => 'Campañas', 'route' => 'manager.marketing.campaigns'],
                ['label' => 'Newsletters', 'route' => 'manager.marketing.newsletters'],
                ['label' => 'Promociones', 'route' => 'manager.marketing.promotions'],
                ['label' => 'Suscriptores', 'route' => 'manager.marketing.subscribers'],
            ],
        ]);
    }
}
```

### Ejemplo 2: Módulo de Reportes

```php
// modules/Reports/app/Providers/ReportsServiceProvider.php

namespace Modules\Reports\Providers;

use App\Services\AdminMenuService;
use Illuminate\Support\ServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        NavService::registerMiniItem('reports', [
            'icon' => 'fa-bar-chart',
            'tooltip' => 'Reportes',
            'sidebar_id' => 'reports',
            'order' => 35,
        ]);

        NavService::registerSidebar('reports', [
            'title' => 'Reportes',
            'items' => [
                ['label' => 'Ventas', 'route' => 'manager.reports.sales'],
                ['label' => 'Clientes', 'route' => 'manager.reports.customers'],
                ['label' => 'Productos', 'route' => 'manager.reports.products'],
                ['label' => 'Exportar', 'route' => 'manager.reports.export'],
            ],
        ]);
    }
}
```

### Ejemplo 3: Con Subitems (Menús jerárquicos)

```php
NavService::registerSidebar('analytics', [
    'title' => 'Analítica',
    'items' => [
        [
            'label' => 'Dashboard',
            'route' => 'manager.analytics.dashboard',
            'icon' => 'fa-chart-line',
        ],
        [
            'label' => 'Visitors',
            'route' => 'manager.analytics.visitors',
            'icon' => 'fa-users',
        ],
        [
            'label' => 'Conversiones',
            'route' => 'manager.analytics.conversions',
            'icon' => 'fa-funnel',
        ],
    ],
]);
```

## 🔧 API Completa del AdminMenuService

### Métodos Disponibles

```php
// Registrar un item en mini-nav
NavService::registerMiniItem(string $moduleId, array $config): void

// Registrar items en un sidebar
NavService::registerSidebar(string $sidebarId, array $config): void

// Obtener todos los items del mini-nav
NavService::getMiniItems(): Collection

// Obtener un item específico del mini-nav
NavService::getMiniItem(string $moduleId): ?array

// Obtener un sidebar específico
NavService::getSidebar(string $sidebarId): ?array

// Obtener todos los sidebars
NavService::getAllSidebars(): array

// Verificar si un módulo está registrado
NavService::hasMiniItem(string $moduleId): bool

// Verificar si un sidebar está registrado
NavService::hasSidebar(string $sidebarId): bool

// Obtener toda la estructura (debugging)
NavService::getAll(): array

// Limpiar todos los menús (testing)
NavService::clear(): void
```

## 🎨 Personalizando Estilos

El componente `sidebar-navigation.blade.php` incluye estilos por defecto, pero puedes personalizarlos:

```blade
<style>
    /* Personalizar mini-nav items */
    .mini-nav-item a {
        /* Tu CSS aquí */
    }

    /* Personalizar sidebar */
    .sidebar-link.active {
        /* Tu CSS aquí */
    }
</style>
```

## 📦 Orden de Ejecución Recomendada

Usa el parámetro `order` para controlar el orden de aparición:

```
1-10:   Menús del core (Settings, Configuración)
11-20:  Módulos principales (Documents, Users)
21-30:  Módulos secundarios (Marketing, Reports)
31+:    Módulos adicionales
```

Ejemplo:

```php
'order' => 10,  // Aparecerá primero
'order' => 20,  // Aparecerá segundo
'order' => 30,  // Aparecerá tercero
```

## 🚀 Pasos para Agregar un Nuevo Módulo

1. **Crear el módulo** (si no existe):
   ```bash
   php artisan make:module MyModule
   ```

2. **Registrar menús en el ServiceProvider**:
   ```php
   // modules/MyModule/app/Providers/MyModuleServiceProvider.php
   public function boot(): void
   {
       NavService::registerMiniItem('my-module', [...]);
       NavService::registerSidebar('my-module', [...]);
   }
   ```

3. **Registrar el ServiceProvider** en `bootstrap/providers.php`:
   ```php
   Modules\MyModule\Providers\MyModuleServiceProvider::class,
   ```

4. **¡Listo!** Los menús aparecerán automáticamente

## 🔍 Debugging

Para verificar los menús registrados:

```php
// En Tinker o en una ruta
$menus = NavService::getAll();
dd($menus);
```

Salida esperada:
```php
[
    'mini' => [
        'backups' => [
            'icon' => 'fa-sliders',
            'tooltip' => 'Configuración',
            'sidebar_id' => 'backups',
            'order' => 10,
        ],
        // ... más items
    ],
    'sidebar' => [
        'backups' => [
            'title' => 'Configuración',
            'items' => [...]
        ],
        // ... más sidebars
    ]
]
```

## ⚠️ Errores Comunes

### Error: "Missing required field 'icon'"
**Causa:** Olvidó especificar el icono en el mini-nav item
**Solución:**
```php
NavService::registerMiniItem('my-module', [
    'icon' => 'fa-rocket',  // ← Requerido
    'tooltip' => 'Mi Módulo',
    'sidebar_id' => 'my-module',
]);
```

### Error: "route() does not exist"
**Causa:** La ruta especificada no está registrada
**Solución:** Verificar que la ruta existe en `routes/web.php`

### Menú no aparece
**Causa:** El ServiceProvider no está registrado en `bootstrap/providers.php`
**Solución:** Agregar el provider a la lista

## 📋 Checklist para Nuevo Módulo

- [ ] Crear ServiceProvider del módulo
- [ ] Registrar menús en el `boot()` del provider
- [ ] Registrar el provider en `bootstrap/providers.php`
- [ ] Crear rutas para cada item del menú
- [ ] Probar que los menús aparecen correctamente
- [ ] Verificar que los enlaces funcionan
- [ ] Usar Font Awesome para los iconos

---

**Última actualización:** 2024
**Mantenedor:** Sistema de Menús Centralizados
