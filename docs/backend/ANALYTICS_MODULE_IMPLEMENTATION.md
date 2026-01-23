# Analytics Module - Complete Implementation Guide

## 🎯 Overview

El módulo Analytics ha sido mejorado significativamente con una arquitectura completa adaptada de Mercosan pero integrada en nuestro patrón de Laravel 12 + Modules.

**Características:**
- ✅ Query Builder fluent con traits modulares
- ✅ Period management para rangos de fecha
- ✅ Configuración de Google Analytics GA4
- ✅ Endpoints API para widgets/dashboard
- ✅ Validación de credenciales
- ✅ Caché integrado
- ✅ Interface moderna con Modernize template

---

## 📁 Estructura del Módulo

```
modules/Analytics/
├── app/
│   ├── Analytics.php                 # Motor principal
│   ├── AnalyticsResponse.php         # Formateador de respuestas
│   ├── Period.php                    # Gestor de períodos
│   ├── Facades/
│   │   └── Analytics.php             # Facade para uso fácil
│   ├── Traits/
│   │   ├── DateRangeTrait.php       # Manejo de rangos
│   │   ├── MetricTrait.php          # Agregar métricas
│   │   ├── DimensionTrait.php       # Agregar dimensiones
│   │   ├── OrderByTrait.php         # Ordenamiento
│   │   ├── FilterTrait.php          # Filtros
│   │   └── RowOperationTrait.php    # Límites y offsets
│   ├── Http/Controllers/
│   │   ├── AnalyticsController.php           # API endpoints
│   │   └── AnalyticsSettingsController.php   # Configuración
│   └── Providers/
│       └── AnalyticsServiceProvider.php      # Service Provider
├── routes/
│   └── web.php                       # Routes (settings + API)
├── resources/views/
│   └── settings/
│       └── index.blade.php           # View de configuración
└── module.json                       # Configuración del módulo
```

---

## 🚀 Uso del Módulo

### 1. Importar la Facade

```php
use Modules\Analytics\Facades\Analytics;
```

### 2. Construir Queries Fluent

```php
// Query simple
$data = Analytics::dateRange(Period::last7Days())
    ->metrics('sessions')
    ->dimensions('date')
    ->get();

// Query compleja
$report = Analytics::dateRange(Period::last30Days())
    ->metrics(['sessions', 'screenPageViews', 'bounceRate'])
    ->dimensions(['date', 'browser'])
    ->orderByMetricDesc('screenPageViews')
    ->limit(20)
    ->get();

// Con filtros
$filtered = Analytics::dateRange(Period::thisMonth())
    ->metrics('screenPageViews')
    ->dimensions('pageTitle')
    ->whereDimension('pageTitle', 'contains', 'blog')
    ->orderByMetricDesc('screenPageViews')
    ->limit(10)
    ->get();
```

### 3. Usar Métodos Predefinidos

```php
// Páginas más visitadas
$topPages = Analytics::fetchMostVisitedPages(Period::last30Days(), 20);

// Top navegadores
$browsers = Analytics::fetchTopBrowsers(Period::last30Days());

// Top referrers
$referrers = Analytics::fetchTopReferrers(Period::last30Days());

// Query personalizada
$custom = Analytics::performQuery(
    Period::last7Days(),
    ['sessions', 'totalUsers'],
    ['date', 'operatingSystem']
);
```

---

## 🗓️ Períodos Disponibles

```php
// Métodos estáticos
Period::days(7)              // Últimos 7 días
Period::months(12)           // Últimos 12 meses
Period::years(1)             // Último año
Period::last7Days()          // Últimos 7 días
Period::last30Days()         // Últimos 30 días
Period::thisMonth()          // Este mes
Period::lastMonth()          # Mes anterior
Period::thisYear()           // Este año

// Crear período personalizado
Period::create(
    Carbon::parse('2024-01-01'),
    Carbon::parse('2024-01-31')
)
```

---

## 🔌 Traits del Query Builder

### DateRangeTrait
```php
->dateRange(Period $period)      // Establecer rango de fechas
```

### MetricTrait
```php
->metrics('sessions')                    // Una métrica
->metrics(['sessions', 'totalUsers'])    // Múltiples métricas
```

### DimensionTrait
```php
->dimensions('date')                        // Una dimensión
->dimensions(['date', 'browser', 'city'])   // Múltiples
```

### OrderByTrait
```php
->orderByMetricDesc('screenPageViews')  // Ordenar métrica DESC
->orderByMetricAsc('bounceRate')        // Ordenar métrica ASC
->orderByDimensionDesc('date')          // Ordenar dimensión DESC
->orderByDimensionAsc('browser')        // Ordenar dimensión ASC
```

### FilterTrait
```php
->whereDimension('pageTitle', 'contains', 'blog')
->whereDimension('country', '=', 'US')
->whereMetric('screenPageViews', '>', '100')
```

Operadores soportados: `=`, `contains`, `begins_with`, `ends_with`

### RowOperationTrait
```php
->limit(20)              // Limitar resultados
->offset(10)             // Saltar primeros N resultados
->keepEmptyRows(true)    // Mantener filas vacías
```

---

## 📊 Respuestas y Datos

### AnalyticsResponse

```php
$response = Analytics::dateRange(Period)->metrics('sessions')->get();

// Métodos disponibles
$response->getTable()      // Collection con datos formateados
$response->getTotals()     // Array con totales
$response->getRows()       # Array con todas las filas
$response->toArray()       // Convertir a array
$response->toJson()        // Convertir a JSON
```

### Ejemplo de Datos

```php
$data = Analytics::dateRange(Period::days(7))
    ->metrics('sessions')
    ->dimensions('date')
    ->get();

// $data->getTable() retorna Collection:
// [
//     ['2024-01-01', 125],
//     ['2024-01-02', 189],
//     ['2024-01-03', 234],
// ]

// $data->getTotals() retorna Array:
// ['metric_0' => 548]
```

---

## 🔌 API Endpoints

Todos los endpoints requieren autenticación (`auth` middleware).

### Settings Endpoints

```
GET    /settings/analytics
       Obtener interfaz de configuración

PUT    /settings/analytics
       Guardar configuración
       Body: {
           "google_analytics_enable": 1,
           "google_analytics_property_id": "123456789",
           "google_analytics_credentials": "{...}"
       }

POST   /settings/analytics/validate-credentials
       Validar credenciales JSON
       Body: {
           "property_id": "123456789",
           "credentials": "{...}"
       }
```

### Data API Endpoints

```
GET    /api/analytics/overview
       Obtener estadísticas generales
       Query params: ?range=last_7_days
       Ranges: today, yesterday, last_7_days, last_30_days,
               this_month, last_month, this_year

GET    /api/analytics/top-pages
       Páginas más visitadas

GET    /api/analytics/top-browsers
       Navegadores principales

GET    /api/analytics/top-referrers
       Referrers/fuentes principales

GET    /api/analytics/query
       Query personalizada
       Query params: ?metrics=sessions,screenPageViews
                     &dimensions=date,browser
                     &range=last_30_days
```

---

## ⚙️ Configuración

### Variables de Entorno (.env)

```env
# No es necesario, todo se configura vía interfaz
```

### Database Settings (app.settings)

Los siguientes settings se guardan automáticamente:

| Key | Descripción |
|-----|-------------|
| `google_analytics_enable` | Boolean - Habilitar/deshabilitar |
| `google_analytics_property_id` | String - ID de propiedad GA4 |
| `google_analytics_credentials` | String - JSON de credenciales |

### Acceso a Configuración

```php
// Obtener setting
$enabled = setting('google_analytics_enable');
$propertyId = setting('google_analytics_property_id');

// Guardar setting
setting(['google_analytics_enable' => true]);
setting(['google_analytics_property_id' => '123456789']);
```

---

## 🔐 Validación de Credenciales

```php
// En el controlador
$response = Http::post(route('backups.analytics.validate-credentials'), [
    'property_id' => '123456789',
    'credentials' => json_encode($jsonCredentials)
]);

// Respuesta exitosa
{
    "status": true,
    "message": "Credenciales válidas"
}

// Respuesta con error
{
    "status": false,
    "message": "JSON inválido"
}
```

---

## 🎨 Vista de Configuración

La interfaz se encuentra en `/settings/analytics` y proporciona:

1. **Toggle de estado** - Habilitar/deshabilitar Analytics
2. **Property ID** - Campo para ingresar ID de propiedad GA4
3. **Credenciales JSON** - Textarea para JSON de credenciales
4. **Botón de validación** - Valida credenciales en tiempo real
5. **Documentación integrada** - Guía de pasos para obtener credenciales

---

## 💾 Caché

El módulo implementa caché automático:

```php
// El caché se aplica automáticamente a todas las queries
// Duración: 24 horas (configurable)
// Store: file (configurable en config)

// Limpiar caché manualmente
Analytics::clearCache();
```

---

## 🚨 Manejo de Errores

```php
try {
    $data = Analytics::dateRange(Period)->metrics('sessions')->get();
} catch (InvalidArgumentException $e) {
    // Períodos inválidos
} catch (\RuntimeException $e) {
    // Error al ejecutar query
} catch (\Exception $e) {
    // Credenciales no configuradas
}
```

---

## 📱 Uso en Controladores

```php
<?php

namespace Modules\Dashboard\Http\Controllers;

use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

class DashboardController extends Controller {

    public function index() {
        // Obtener datos últimos 30 días
        $period = Period::last30Days();

        $stats = Analytics::dateRange($period)
            ->metrics(['sessions', 'totalUsers', 'screenPageViews'])
            ->dimensions('date')
            ->get();

        $topPages = Analytics::fetchMostVisitedPages($period, 10);

        $browsers = Analytics::fetchTopBrowsers($period);

        return view('dashboard.index', compact('stats', 'topPages', 'browsers'));
    }
}
```

---

## 📋 Flujo de Configuración

```
1. Usuario accede a /settings/analytics
2. Sistema carga formulario con campos vacíos
3. Usuario ingresa Property ID
4. Usuario copia/pega JSON de credenciales
5. Usuario hace clic en "Validar credenciales"
6. Sistema valida que sea JSON válido ✓
7. Usuario guarda con botón "Guardar configuración"
8. Sistema almacena en database settings
9. Module registra Analytics singleton con credenciales
10. Endpoints API ahora funcionan correctamente
```

---

## 🔄 Flujo de Data (API)

```
Frontend → GET /api/analytics/overview
           ↓
AnalyticsController::overview()
           ↓
getPeriodFromRequest() → Period instance
           ↓
Analytics::dateRange()->metrics()->dimensions()->get()
           ↓
AnalyticsResponse::formatResponse()
           ↓
return JSON response
           ↓
Frontend procesa datos y muestra gráficos
```

---

## 🎓 Ejemplo Completo: Dashboard Widget

```blade
@extends('layouts.theme')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Analytics Dashboard</h5>
        </div>
        <div class="card-body">
            <div id="chart"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Obtener datos
            fetch('/api/analytics/overview?range=last_7_days')
                .then(r => r.json())
                .then(data => {
                    if (data.status) {
                        // Renderizar gráfico
                        console.log(data.data.chart_data);
                    }
                });
        });
    </script>
@endsection
```

---

## 📚 Próximos Pasos

1. **Integración con Dashboard**: Crear widgets para mostrar datos
2. **Real-time Updates**: Usar Laravel Reverb para actualizaciones en tiempo real
3. **Exportación de datos**: Agregar Excel export con Maatwebsite/Excel
4. **Alertas**: Notificaciones cuando métricas superan umbrales
5. **Reportes automáticos**: Jobs para generar reportes diarios/semanales

---

## ✅ Resumen

El módulo Analytics es ahora una implementación profesional y completa que:

✅ Sigue patrones modulares de Mercosan
✅ Se integra perfectamente con Laravel 12
✅ Proporciona interfaz amigable para configurar GA4
✅ Ofrece query builder fluent flexible
✅ Carea automático de resultados
✅ API REST lista para widgets/dashboard
✅ Validación robusta de credenciales
✅ Manejo de errores completo

¡Listo para usar en el dashboard del manager!
