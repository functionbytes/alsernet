# Analytics Module - Complete Setup & Usage Guide

## ✅ Implementación Completada

El módulo Analytics está ahora **100% funcional** con integración real a Google Analytics GA4, dashboard con gráficos y reportes automáticos.

---

## 🔧 Pasos de Instalación

### 1. Instalar Dependencia

```bash
composer require google/analytics-data:^2.0
```

O si ya la agregué a `composer.json`:

```bash
composer install
```

### 2. Configurar Google Analytics

**a) Crear credenciales en Google Cloud:**

1. Ve a [Google Cloud Console](https://console.cloud.google.com)
2. Crea un nuevo proyecto
3. Habilita Google Analytics Data API v1beta
4. Crea una "Service Account"
5. Genera una clave JSON y descárgala

**b) Configurar en Manager:**

1. Ve a `/settings/analytics`
2. Habilita "Google Analytics"
3. Ingresa tu **GA4 Property ID** (número de 9-10 dígitos)
4. Copia y pega el contenido del JSON de credenciales
5. Haz clic en "Validar credenciales"
6. Guarda la configuración

### 3. (Opcional) Programar Reportes Automáticos

Agrega a tu archivo `routes/console.php`:

```php
use Modules\Analytics\Jobs\GenerateAnalyticsReport;
use Illuminate\Support\Facades\Schedule;

// Reportes diarios a las 2 AM
Schedule::job(new GenerateAnalyticsReport('daily'))
    ->dailyAt('02:00')
    ->name('analytics:daily-report')
    ->onOneServer();

// Reportes semanales los lunes a las 3 AM
Schedule::job(new GenerateAnalyticsReport('weekly'))
    ->weeklyOn(1, '03:00')
    ->name('analytics:weekly-report')
    ->onOneServer();

// Reportes mensuales el 1ro de cada mes a las 4 AM
Schedule::job(new GenerateAnalyticsReport('monthly'))
    ->monthlyOn(1, '04:00')
    ->name('analytics:monthly-report')
    ->onOneServer();
```

Luego ejecuta el scheduler:

```bash
# En desarrollo
php artisan schedule:work

# En producción (agregar a crontab)
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📍 URLs de Acceso

Una vez configurado, accede a:

| URL | Descripción |
|-----|-------------|
| `/settings/analytics` | Página de configuración |
| `/analytics/dashboard` | Dashboard con gráficos |
| `/api/analytics/overview` | Endpoint: Estadísticas generales |
| `/api/analytics/top-pages` | Endpoint: Páginas principales |
| `/api/analytics/top-browsers` | Endpoint: Navegadores |
| `/api/analytics/top-referrers` | Endpoint: Fuentes de tráfico |

---

## 💻 Uso en Código

### Opción 1: Usar la Facade

```php
use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

// Última semana
$data = Analytics::dateRange(Period::last7Days())
    ->metrics('sessions')
    ->dimensions('date')
    ->get();

// Acceder a datos
$data->getTable();      // Collection formateada
$data->getTotals();     // Totales
$data->toArray();       // Array
$data->toJson();        // JSON
```

### Opción 2: Métodos Predefinidos

```php
// Páginas más visitadas
Analytics::fetchMostVisitedPages(Period::last30Days(), 20);

// Top navegadores
Analytics::fetchTopBrowsers(Period::last30Days());

// Top referrers
Analytics::fetchTopReferrers(Period::last30Days());

// Query personalizada
Analytics::performQuery(
    Period::last7Days(),
    ['sessions', 'screenPageViews'],
    ['date', 'browser']
);
```

### Opción 3: En Controladores

```php
<?php

namespace App\Http\Controllers;

use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

class ReportController extends Controller {
    public function weekly() {
        $period = Period::last7Days();

        $stats = Analytics::dateRange($period)
            ->metrics(['sessions', 'totalUsers'])
            ->get();

        return view('report', compact('stats'));
    }
}
```

---

## 🎯 Dashboard

El dashboard es totalmente funcional con:

✅ **Selector de período** - Cambiar rango de fechas
✅ **4 tarjetas de estadísticas** - Sesiones, Usuarios, Vistas, Tasa de Rebote
✅ **Gráfico de línea** - Sesiones y vistas por día (ApexCharts)
✅ **Gráfico donut** - Distribución de navegadores
✅ **Tabla de páginas** - Top 10 páginas más visitadas
✅ **Tabla de referrers** - Top fuentes de tráfico

**Acceder:** `/analytics/dashboard`

---

## 📊 Generación de Reportes

### Opción 1: Automático (Programado)

Una vez configurado en `routes/console.php`, los reportes se generan automáticamente:
- **Diarios** a las 2 AM
- **Semanales** los lunes a las 3 AM
- **Mensuales** el 1ro de cada mes a las 4 AM

### Opción 2: Manual via CLI

```bash
# Reporte diario
php artisan analytics:report --type=daily

# Reporte semanal
php artisan analytics:report --type=weekly

# Reporte mensual
php artisan analytics:report --type=monthly

# Enviar a correo (requiere configuración de email)
php artisan analytics:report --type=daily --email=admin@example.com

# Encolar para procesamiento en background
php artisan analytics:report --type=daily --queue
```

### Opción 3: Dispatch desde Código

```php
use Modules\Analytics\Jobs\GenerateAnalyticsReport;

// Ejecución inmediata
dispatch_sync(new GenerateAnalyticsReport('daily'));

// En queue
dispatch(new GenerateAnalyticsReport('weekly', 'admin@example.com'));
```

### Dónde se guardan

Los reportes se guardan en: `storage/app/analytics-reports/`

```bash
# Ver reportes
ls storage/app/analytics-reports/

# Ver un reporte específico
cat storage/app/analytics-reports/analytics_report_daily_2024-01-15_14-30-45.json
```

---

## 📈 Períodos Disponibles

```php
Period::days(7)              // Últimos 7 días
Period::months(12)           // Últimos 12 meses
Period::years(1)             // Último año
Period::last7Days()          // Últimos 7 días
Period::last30Days()         // Últimos 30 días
Period::thisMonth()          // Este mes
Period::lastMonth()          // Mes anterior
Period::thisYear()           // Este año
Period::create($start, $end) // Período personalizado
```

---

## 🔌 Query Builder (Fluent)

```php
Analytics::dateRange(Period)
    ->metrics(['sessions', 'totalUsers'])        // Métricas a obtener
    ->dimensions(['date', 'browser'])             // Agrupar por
    ->orderByMetricDesc('sessions')               // Ordenar
    ->limit(20)                                   // Limitar resultados
    ->whereDimension('browser', 'contains', 'Chrome')  // Filtrar
    ->get();                                      // Ejecutar query
```

---

## ⚙️ Configuración

Todo se configura vía interfaz web en `/settings/analytics`:

| Setting | Descripción |
|---------|-------------|
| `google_analytics_enable` | Habilitar/deshabilitar |
| `google_analytics_property_id` | ID de propiedad GA4 |
| `google_analytics_credentials` | JSON de credenciales |

Se guardan automáticamente en la tabla `settings` de la base de datos.

---

## 🚨 Manejo de Errores

```php
try {
    $data = Analytics::dateRange(Period)->get();
} catch (\InvalidArgumentException $e) {
    // Período inválido
} catch (\RuntimeException $e) {
    // Error ejecutando query
} catch (\Exception $e) {
    // Credenciales no configuradas
}
```

El módulo registra errores en `storage/logs/laravel.log`.

---

## 🔒 Seguridad

✅ Las credenciales JSON se almacenan en `storage/app/`
✅ Las credenciales se validan antes de guardar
✅ Todo acceso requiere autenticación (`auth` middleware)
✅ Colas de trabajo (jobs) se procesan en background
✅ Logs de errores pero sin exponer datos sensibles

---

## 📊 Arquitectura

```
Facade (Analytics)
    ↓
Analytics class (query builder)
    ├── 6 Traits (métodos fluent)
    │   ├── DateRangeTrait
    │   ├── MetricTrait
    │   ├── DimensionTrait
    │   ├── OrderByTrait
    │   ├── FilterTrait
    │   └── RowOperationTrait
    ├── Google Analytics API v1beta (real integration)
    ├── Cache (24h)
    └── AnalyticsResponse (formatea resultados)
```

---

## 📁 Estructura de Archivos

```
modules/Analytics/
├── app/
│   ├── Period.php
│   ├── Analytics.php              ← Motor principal
│   ├── AnalyticsResponse.php
│   ├── Facades/Analytics.php
│   ├── Traits/                    ← 6 traits reutilizables
│   ├── Http/Controllers/
│   │   ├── DashboardController.php     (NEW - Dashboard)
│   │   ├── AnalyticsController.php     (API endpoints)
│   │   └── AnalyticsSettingsController.php
│   ├── Jobs/
│   │   └── GenerateAnalyticsReport.php (NEW - Reportes)
│   ├── Console/Commands/
│   │   └── GenerateAnalyticsReportCommand.php (NEW)
│   └── Providers/AnalyticsServiceProvider.php
├── routes/web.php                 (UPDATED - +3 rutas)
├── resources/views/
│   ├── settings/index.blade.php   (Configuración)
│   └── dashboard/index.blade.php  (NEW - Dashboard completo)
├── module.json
├── SCHEDULED_TASKS.md             (NEW - Guía de scheduler)
└── composer.json                  (Google Analytics API agregada)
```

---

## 🎓 Ejemplos Completos

### Ejemplo 1: Dashboard Semanal

```php
<?php

namespace App\Http\Controllers;

use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

class WeeklyReportController {
    public function show() {
        $period = Period::last7Days();

        $overview = Analytics::dateRange($period)
            ->metrics(['sessions', 'totalUsers', 'screenPageViews'])
            ->get()
            ->getTotals();

        $topPages = Analytics::fetchMostVisitedPages($period, 5);

        $dailyTrend = Analytics::dateRange($period)
            ->metrics('sessions')
            ->dimensions('date')
            ->get()
            ->getTable();

        return view('weekly-report', compact('overview', 'topPages', 'dailyTrend'));
    }
}
```

### Ejemplo 2: Reporte Personalizado

```php
$report = Analytics::dateRange(Period::thisMonth())
    ->metrics(['sessions', 'screenPageViews', 'bounceRate'])
    ->dimensions(['date', 'browser', 'operatingSystem'])
    ->orderByMetricDesc('sessions')
    ->limit(100)
    ->whereDimension('browser', 'contains', 'Chrome')
    ->get();

echo $report->getTable()->toJson();
```

### Ejemplo 3: Ejecutar Reporte

```bash
# Generar y enviar reporte semanal
php artisan analytics:report --type=weekly --email=team@example.com --queue

# Ver progreso
php artisan queue:work
```

---

## 🚀 Próximas Mejoras

- [ ] Exportar reportes a PDF
- [ ] Envío de reportes por email con HTML
- [ ] Almacenar reportes en base de datos
- [ ] Alertas cuando métricas superan umbrales
- [ ] Webhooks para integración externa
- [ ] Comparación de períodos
- [ ] Gráficos adicionales (heatmap, waterfall)

---

## ✨ Summary

El módulo Analytics ahora proporciona:

✅ **Integración real con Google Analytics GA4**
✅ **Query builder fluent y flexible**
✅ **Dashboard profesional con gráficos**
✅ **Generación automática de reportes**
✅ **API endpoints para datos en JSON**
✅ **Validación y manejo de errores robusto**
✅ **Caché inteligente (24h)**
✅ **Scheduler para automatización**
✅ **Interfaz moderna Bootstrap 5.3**

**¡Listo para producción!** 🎉
