# Analytics Module Integration Guide

## Overview

El módulo de Analytics del proyecto Mercosan es un sistema completo y profesional de integración con Google Analytics GA4 (versión Beta API v1beta). Implementa una arquitectura modular con traits reutilizables y proporciona un dashboard con widgets interactivos.

**Versión del plugin Mercosan**: 2.1.6
**Requiere**: Botble CMS 7.3.0+
**API**: Google Analytics Data API v1beta

---

## Arquitectura y Componentes Principales

### 1. Core Analytics Engine (`src/Analytics.php`)

```php
class Analytics extends AnalyticsAbstract implements AnalyticsContract
```

**Características:**
- Usa Google Analytics Data API v1beta (cliente oficial de Google)
- Almacena credenciales en archivo JSON en almacenamiento local
- Implementa 9 traits para diferentes funcionalidades

**Métodos principales:**
```php
// Query genérico
Analytics::performQuery(Period, string|array $metrics, string|array $dimensions)
  → Collection

// Queries predefinidas
Analytics::fetchMostVisitedPages(Period, int $maxResults = 20) → Collection
Analytics::fetchTopReferrers(Period, int $maxResults = 20) → Collection
Analytics::fetchTopBrowsers(Period, int $maxResults = 10) → Collection

// Obtener cliente Google
getClient(): BetaAnalyticsDataClient
```

### 2. Period Management (`src/Period.php`)

```php
// Crear periodos
Period::create(CarbonInterface $start, CarbonInterface $end)
Period::days(7)        // Últimos 7 días
Period::months(12)     // Últimos 12 meses
Period::years(1)       // Último año
```

Validación automática: startDate no puede ser después de endDate

### 3. Controladores HTTP

#### AnalyticsController (`src/Http/Controllers/AnalyticsController.php`)
Endpoints para widgets del dashboard:
- `getGeneral()` - Estadísticas generales + mapa mundial + gráficos
- `getTopVisitPages()` - Páginas más visitadas
- `getTopBrowser()` - Navegadores más usados
- `getTopReferrer()` - Referrers/fuentes de tráfico

#### AnalyticsSettingController (`src/Http/Controllers/Settings/AnalyticsSettingController.php`)
- Interfaz de configuración
- Valida credenciales JSON
- Almacena Property ID y credenciales

### 4. Traits Reutilizables (src/Traits/)

| Trait | Función |
|-------|---------|
| `DateRangeTrait` | Manejo de rangos de fechas |
| `MetricTrait` | Agregar métricas a la query |
| `DimensionTrait` | Agregar dimensiones (pageTitle, browser, etc.) |
| `MetricAggregationTrait` | Agregaciones de métricas |
| `FilterByMetricTrait` | Filtrar por métricas |
| `FilterByDimensionTrait` | Filtrar por dimensiones |
| `OrderByMetricTrait` | Ordenar por métricas |
| `OrderByDimensionTrait` | Ordenar por dimensiones |
| `RowOperationTrait` | Límites y offsets |
| `ResponseTrait` | Formatear respuestas |

### 5. Fluent Query Builder

```php
Analytics::dateRange($period)
    ->metrics(['sessions', 'totalUsers', 'screenPageViews'])
    ->dimensions(['pageTitle', 'fullPageUrl'])
    ->orderByMetricDesc('screenPageViews')
    ->limit(20)
    ->get()
    →table;
```

---

## Métricas Disponibles (GA4)

Las métricas principales en GA4 incluyen:
- `sessions` - Sesiones totales
- `totalUsers` - Usuarios únicos
- `screenPageViews` - Vistas de página
- `bounceRate` - Tasa de rebote
- `userEngagementDuration` - Duración del engagement
- `conversionEvents` - Eventos de conversión

[Ver listado completo en Google Analytics API docs]

## Dimensiones Disponibles (GA4)

- `date` - Fecha (YYYYMMDD)
- `yearMonth` - Año-mes (YYYYMM)
- `hour` - Hora del día (0-23)
- `pageTitle` - Título de la página
- `fullPageUrl` - URL completa
- `pagePath` - Ruta de la página
- `browser` - Navegador usado
- `operatingSystem` - Sistema operativo
- `countryIsoCode` - Código ISO del país
- `sessionSource` - Fuente de sesión

[Ver listado completo en Google Analytics API docs]

---

## Configuración y Setup

### 1. Variables de Entorno (.env)
```env
ANALYTICS_CACHE_TIME=1440          # Minutos de caché (default: 24h)
ANALYTICS_ENABLE_DASHBOARD_WIDGETS=true
```

### 2. Database Settings (app.settings)

| Setting | Descripción |
|---------|-------------|
| `analytics_property_id` | Property ID de GA4 (e.g., "123456789") |
| `analytics_service_account_credentials` | JSON con credenciales de servicio |
| `analytics_dashboard_widgets` | Habilitar widgets en dashboard (0/1) |

### 3. Credenciales Google

El archivo `analytics-credentials.json` se almacena en:
```
storage/app/analytics-credentials.json
```

**Estructura de credenciales:**
```json
{
    "type": "service_account",
    "project_id": "...",
    "private_key_id": "...",
    "private_key": "-----BEGIN RSA PRIVATE KEY-----...",
    "client_email": "...",
    "client_id": "...",
    "auth_uri": "https://accounts.google.com/o/oauth2/auth",
    "token_uri": "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url": "...",
    "client_x509_cert_url": "..."
}
```

### 4. Permisos

```php
// config/permissions.php
'analytics' => [
    'backups',
    'general',
    'page',
    'browser',
    'referrer',
]
```

---

## Vistas y Widgets

### Widget Layout (resources/views/widgets/)

```
widgets/
├── general.blade.php        # Dashboard general + mapa mundial
├── page.blade.php           # Top visited pages
├── browser.blade.php        # Top browsers
├── referrer.blade.php       # Top referrers
├── empty-state.blade.php    # Estado cuando no hay configuración
└── upload-button.blade.php  # Botón para subir credenciales JSON
```

**Componentes usados:**
- x-core::card - Tarjetas de contenido
- x-core::icon - Iconos Tabler (ti ti-*)
- ApexCharts - Gráficos interactivos
- Chart.js - Gráficos secundarios
- JVectorMap - Mapa mundial interactivo

### Datos de las Vistas

**General Widget** recibe:
- `$chartStats` - Collection con datos para gráficos
- `$countryStats` - Array con datos por país
- `$sessions` - Total de sesiones
- `$totalUsers` - Total de usuarios
- `$screenPageViews` - Total de vistas
- `$bounceRate` - Tasa de rebote

---

## Flujo de Integración en Dashboard

```
1. Event: RenderingDashboardWidgets
   ↓
2. HookServiceProvider escucha el evento
   ↓
3. registerScripts() carga librerías (ApexCharts, JVectorMap)
   ↓
4. addAnalyticsWidgets() registra 4 widgets:
   - widget_analytics_general
   - widget_analytics_page
   - widget_analytics_browser
   - widget_analytics_referrer
   ↓
5. Cada widget llama a AnalyticsController vía AJAX
   ↓
6. Controller obtiene datos de Google Analytics
   ↓
7. Renderiza vista y retorna HTML al widget
```

---

## Manejo de Errores

### InvalidConfiguration Exception

```php
// Credenciales no válidas
throw InvalidConfiguration::credentialsIsNotValid();

// Property ID no válido
throw InvalidConfiguration::invalidPropertyId();
```

### InvalidPeriod Exception

```php
// Start date no puede ser después de end date
throw InvalidPeriod::startDateCannotBeAfterEndDate($start, $end);
```

**Manejo en controllers:**
```php
catch (InvalidConfiguration $exception) {
    return $this->handleInvalidConfigException($exception);
    // Retorna vista empty-state con mensaje de error
}
```

---

## Caché

- **Duración**: Configurable via `ANALYTICS_CACHE_TIME` (default: 1440 minutos = 24h)
- **Store**: `file` (configurable en config/general.php)
- **Clave**: Automática basada en la query

El caché previene llamadas excesivas a Google Analytics API.

---

## Form Builder (AnalyticsSettingForm)

Usa Botble CMS FormBuilder con campos:
- `analytics_dashboard_widgets` (OnOffField) - Habilitar/deshabilitar widgets
- `analytics_property_id` (TextField) - ID de propiedad GA4
- `analytics_service_account_credentials` (CodeEditorField) - JSON de credenciales
- `upload_account_json_file` (HtmlField) - Upload directo de archivo JSON

**Validación**: `AnalyticsSettingRequest`

---

## Estructura de Rutas

```php
// API Endpoints (para widgets)
/admin/analytics/general          → getGeneral()
/admin/analytics/page             → getTopVisitPages()
/admin/analytics/browser          → getTopBrowser()
/admin/analytics/referrer         → getTopReferrer()

// Settings
GET  /admin/settings/analytics    → Formulario de configuración
PUT  /admin/settings/analytics    → Actualizar configuración
POST /admin/settings/analytics/json → Upload de JSON
```

---

## Recursos Recomendados para la Integración en Manager

### 1. **Copiar Arquitectura Core**
   - Trait-based query builder pattern
   - Fluent interface para construir queries
   - Period class para date range management
   - Exception handling structure

### 2. **Adaptar AnalyticsController**
   ```php
   // Para Manager (Laravel 12 modular):
   modules/Analytics/app/Http/Controllers/AnalyticsController.php
   ```
   - Métodos: getGeneral(), getTopVisitPages(), getTopBrowser(), getTopReferrer()
   - Response format: JSON + HTML views

### 3. **Replicar Dashboard Integration**
   - HookServiceProvider pattern
   - DashboardWidgetInstance para registrar widgets
   - AJAX endpoints para cargas asincrónicas
   - Renderizado de views desde controlador

### 4. **Views y Visualización**
   - General widget con gráficos + mapa mundial
   - Cards para estadísticas principales
   - Tablas para datos detallados
   - Usar ApexCharts o Chart.js

### 5. **Configuración Settings**
   - FormBuilder similar a AnalyticsSettingForm
   - Fields: Property ID, Credenciales JSON, Toggle Widgets
   - Validación de credenciales
   - Upload de archivo JSON

### 6. **Permisos**
   ```php
   'analytics' => [
       'backups',
       'general',
       'page',
       'browser',
       'referrer',
   ]
   ```

---

## Consideraciones para Manager Project

### Diferencias con Manager Laravel 12

| Aspecto | Mercosan (Botble) | Manager (Laravel 12 + Modules) |
|--------|------------------|--------------------------------|
| Plugin vs Module | Plugin system | Laravel Modules (nwidart) |
| Service Provider | Botble ServiceProvider | Laravel ServiceProvider |
| Dashboard | Botble Dashboard | Custom dashboard/widgets |
| FormBuilder | Botble Form | FormRequest + Blade |
| Traits | 9 traits separados | Consolidar si es necesario |
| Caching | Configurable en config/ | Redis (disponible en project) |

### Mejoras Potenciales

1. **Cache Mejorado**: Usar Redis en lugar de file storage
2. **Event Broadcasting**: Usar Laravel Reverb para updates en tiempo real
3. **Queue Jobs**: Procesar queries pesadas en background
4. **Activity Logging**: Registrar cambios en configuración con Spatie ActivityLog
5. **Data Export**: Exportar datos con Maatwebsite/Excel
6. **Scheduled Reports**: Jobs diarios/semanales con Laravel Scheduler

### Dependencias Necesarias

```json
{
  "google/analytics-data": "^2.0",
  "laravel/framework": "^12.0",
  "nwidart/laravel-modules": "^11.0"
}
```

---

## Ejemplo de Uso en Module Analytics

```php
// modules/Analytics/app/Http/Controllers/AnalyticsController.php

use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

class AnalyticsController extends Controller {

    public function dashboard() {
        try {
            $period = Period::days(30);

            $stats = Analytics::performQuery(
                $period,
                ['sessions', 'totalUsers', 'screenPageViews'],
                'date'
            );

            $topPages = Analytics::fetchMostVisitedPages($period, 10);

            return view('analytics::dashboard', compact('stats', 'topPages'));

        } catch (InvalidConfiguration $e) {
            return redirect()->route('backups.analytics.index')
                ->with('error', 'Analytics not configured');
        }
    }
}
```

---

## Conclusión

El módulo de Analytics de Mercosan proporciona una implementación profesional y completa de Google Analytics GA4 con:

✅ Arquitectura modular reutilizable
✅ Trait-based query builder patrón
✅ Integración con dashboard de Botble
✅ Manejo robusto de errores
✅ Caché configurable
✅ Múltiples opciones de visualización

**Para Manager**: Adaptar la arquitectura core (Analytics + Period + Traits + Controllers) y replicar el sistema de widgets del dashboard, pero usando patrones nativos de Laravel 12 y nwidart/modules.
