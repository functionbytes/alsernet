# Analytics Scheduled Tasks

## Programar Reportes Automáticos

Para automatizar la generación de reportes, agrega las siguientes líneas al archivo `routes/console.php` de tu aplicación:

```php
<?php

use Modules\Analytics\Jobs\GenerateAnalyticsReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

// Reportes mensuales el primer día del mes a las 4 AM
Schedule::job(new GenerateAnalyticsReport('monthly'))
    ->monthlyOn(1, '04:00')
    ->name('analytics:monthly-report')
    ->onOneServer();
```

## Ejecutar Reportes Manualmente

```bash
# Reporte diario
php artisan analytics:report --type=daily

# Reporte semanal
php artisan analytics:report --type=weekly

# Reporte mensual
php artisan analytics:report --type=monthly

# Enviar a correo electrónico
php artisan analytics:report --type=daily --email=admin@example.com

# Encolar para procesamiento en background
php artisan analytics:report --type=daily --queue
```

## Verificar Reportes Generados

Los reportes se guardan en: `storage/app/analytics-reports/`

```bash
# Ver reportes disponibles
ls storage/app/analytics-reports/

# Ver contenido de un reporte
cat storage/app/analytics-reports/analytics_report_daily_2024-01-15_14-30-45.json
```

## Estructura del Reporte

Cada reporte incluye:

```json
{
  "type": "daily",
  "generated_at": "2024-01-15T14:30:45+00:00",
  "period": {
    "start": "2024-01-15",
    "end": "2024-01-15"
  },
  "overview": {
    "sessions": 1250,
    "users": 856,
    "pageviews": 3421,
    "bounce_rate": 0.38
  },
  "top_pages": [
    {
      "title": "Home",
      "url": "https://example.com/",
      "views": 450
    }
  ],
  "top_browsers": [
    {
      "name": "Chrome",
      "sessions": 920
    }
  ],
  "top_referrers": [
    {
      "source": "google",
      "views": 680
    }
  ]
}
```

## Monitorear Trabajos

```bash
# Ver trabajos en la cola
php artisan queue:work

# Ver logs de reportes
tail -f storage/logs/laravel.log | grep "analytics"

# Reintentar trabajos fallidos
php artisan queue:retry all
```

## Mejoras Futuras

- [ ] Envío de reportes por email con HTML formateado
- [ ] Exportar a PDF
- [ ] Almacenar en base de datos
- [ ] Webhooks para integrar con servicios externos
- [ ] Alertas cuando métricas superan umbrales
- [ ] Comparación de períodos (semana anterior, mes anterior, etc.)
