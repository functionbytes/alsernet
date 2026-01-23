# Registrar Menús de un Módulo

## 📋 Estructura

Cada módulo debe registrar sus menús en su propio `ServiceProvider` usando `NavService`.

**Ubicación:** `modules/{ModuleName}/app/Providers/{ModuleName}ServiceProvider.php`

---

## 🚀 Ejemplo: Módulo de Documentos

```php
<?php

namespace Modules\Document\Providers;

use App\Services\NavService;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registrar los menús del módulo Document
        $this->registerMenus();
    }

    /**
     * Registrar menús del módulo
     */
    private function registerMenus(): void
    {
        // Mini-nav item para Documentos
        NavService::registerMiniItem('documents', [
            'icon' => 'fa-file-pdf',
            'tooltip' => 'Documentos',
            'sidebar_id' => 'documents',
            'order' => 20,
        ]);

        // Sidebar con los items del módulo
        NavService::registerSidebar('documents', [
            'title' => 'Documentos',
            'items' => [
                ['label' => 'Listado', 'route' => 'manager.documents.index'],
                ['label' => 'Tipos', 'route' => 'manager.documents.types'],
                ['label' => 'Configuración', 'route' => 'manager.documents.configurations'],
                ['label' => 'Validación', 'route' => 'manager.documents.validation'],
                ['label' => 'Historial', 'route' => 'manager.documents.history'],
            ],
        ]);
    }
}
```

---

## 🎯 Ejemplo: Módulo de Marketing

```php
<?php

namespace Modules\Marketing\Providers;

use App\Services\NavService;
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

---

## 📋 Ejemplo: Módulo de Reportes

```php
<?php

namespace Modules\Reports\Providers;

use App\Services\NavService;
use Illuminate\Support\ServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        NavService::registerMiniItem('reports', [
            'icon' => 'fa-bar-chart',
            'tooltip' => 'Reportes',
            'sidebar_id' => 'reports',
            'order' => 30,
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

---

## 🎨 Ejemplo: Módulo de Analítica con Iconos

```php
<?php

namespace Modules\Analytics\Providers;

use App\Services\NavService;
use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        NavService::registerMiniItem('analytics', [
            'icon' => 'fa-chart-line',
            'tooltip' => 'Analítica',
            'sidebar_id' => 'analytics',
            'order' => 35,
        ]);

        NavService::registerSidebar('analytics', [
            'title' => 'Analítica',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'manager.analytics.dashboard',
                    'icon' => 'fa-chart-line',
                ],
                [
                    'label' => 'Visitantes',
                    'route' => 'manager.analytics.visitors',
                    'icon' => 'fa-users',
                ],
                [
                    'label' => 'Conversiones',
                    'route' => 'manager.analytics.conversions',
                    'icon' => 'fa-funnel',
                ],
                [
                    'label' => 'Fuentes',
                    'route' => 'manager.analytics.sources',
                    'icon' => 'fa-share-alt',
                ],
            ],
        ]);
    }
}
```

---

## ✅ Checklist para cada módulo

- [ ] El módulo tiene `app/Providers/{ModuleName}ServiceProvider.php`
- [ ] El ServiceProvider importa `NavService`
- [ ] Se registra el mini-nav item en el `boot()`
- [ ] Se registra el sidebar en el `boot()`
- [ ] El módulo está registrado en `bootstrap/providers.php`
- [ ] Las rutas existen en `routes/web.php`
- [ ] Se usan iconos Font Awesome válidos
- [ ] El `order` es único y diferente del resto

---

## 📊 Orden recomendado de menús

```
order: 10  →  Settings (Core)
order: 20  →  Documents
order: 25  →  Marketing
order: 30  →  Reports
order: 35  →  Analytics
order: 40  →  Users
order: 50  →  Otros módulos
```

---

## 🔍 Verificar menús registrados

```php
// En Tinker o en una ruta
NavService::getAll();

// Salida esperada:
[
    'mini' => [
        'backups' => [...],
        'documents' => [...],
        'marketing' => [...],
    ],
    'sidebar' => [
        'backups' => [...],
        'documents' => [...],
        'marketing' => [...],
    ]
]
```

---

## ⚠️ Problemas comunes

### Menú no aparece
**Causa:** El ServiceProvider no está registrado en `bootstrap/providers.php`
**Solución:** Agregar el provider a la lista

### Ruta no existe
**Causa:** Se especificó una ruta que no está definida
**Solución:** Verificar que la ruta existe en `routes/web.php` con `route('manager.module.action')`

### Icono no se ve
**Causa:** Se usó un icono que no es Font Awesome
**Solución:** Usar `fa-{icon-name}` (Font Awesome 6)

---

## 📚 Referencia de Iconos Font Awesome

Algunos iconos comúnmente usados:

```
Documents:    fa-file-pdf, fa-file, fa-files
Commerce:     fa-shopping-cart, fa-shopping-bag, fa-money-bill
Reports:      fa-bar-chart, fa-chart-line, fa-chart-pie
Users:        fa-users, fa-user-tie, fa-user-check
Settings:     fa-cog, fa-sliders, fa-tools
Analytics:    fa-analytics, fa-chart-line, fa-graph
Marketing:    fa-bullhorn, fa-megaphone, fa-share
Notifications fa-bell, fa-envelope, fa-comment
Email:        fa-envelope, fa-paper-plane, fa-mailchimp
```

Consulta: [Font Awesome Icons](https://fontawesome.com/icons)

---

## 🚀 Paso a paso para agregar un nuevo módulo

1. **Crear el módulo** (si no existe):
   ```bash
   php artisan make:module MyModule
   ```

2. **Crear el ServiceProvider** (si no existe):
   ```bash
   php artisan make:provider MyModuleServiceProvider --namespace=Modules\\MyModule\\Providers
   ```

3. **Registrar menús en el provider**:
   ```php
   use App\Services\NavService;

   public function boot(): void
   {
       NavService::registerMiniItem('my-module', [...]);
       NavService::registerSidebar('my-module', [...]);
   }
   ```

4. **Registrar el provider** en `bootstrap/providers.php`:
   ```php
   Modules\MyModule\Providers\MyModuleServiceProvider::class,
   ```

5. **Crear las rutas** en `modules/MyModule/routes/web.php`:
   ```php
   Route::get('dashboard', [...]);
   Route::get('backups', [...]);
   ```

6. **¡Listo!** Los menús aparecerán automáticamente

---

## 💡 Ventajas de este enfoque

✅ **Modular:** Cada módulo controla su navegación
✅ **Escalable:** Agregar nuevos módulos es trivial
✅ **Mantenible:** No hay código central que modificar
✅ **Independiente:** Los módulos pueden activarse/desactivarse
✅ **Dinámico:** Los menús se registran en tiempo de ejecución

