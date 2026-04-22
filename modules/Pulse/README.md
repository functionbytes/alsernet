# Pulse

> Laravel Pulse - Application Performance Monitoring

## Proposito

Integra Laravel Pulse en el panel de administracion del sistema. Expone el dashboard de monitoreo de rendimiento de la aplicacion (uso de CPU, memoria, colas, requests lentos, excepciones, usuarios activos) restringido a usuarios autenticados con permisos de administracion.

## Componentes principales

- **Modelos**: Ninguno (Pulse usa sus propias tablas gestionadas por el paquete)
- **Controladores**: `PulseController`
- **Rutas**:
  - `routes/web.php` — `GET /pulse` → dashboard de Pulse (`pulse.dashboard`)
  - `routes/api.php` — endpoints de datos para el dashboard
  - `routes/settings.php` — configuracion de Pulse

## Configuracion

- Archivo: `config/config.php` (basico), `config/pulse.php` (configuracion completa de Laravel Pulse)
- `config/pulse.php` incluye: recorders habilitados, retencion de datos, sampling rates, middleware de acceso
- Variables env relevantes: Las de Redis y DB que Pulse usa para almacenar metricas (configuradas en `.env` del proyecto)

## Acceso al dashboard

El dashboard de Pulse en produccion debe estar protegido. La restriccion se configura en `config/pulse.php` via el middleware o en la definicion de ruta del modulo:

```php
// En PulseController o en routes/web.php
Route::middleware(['auth', 'role:super-settings|administrative'])
    ->get('/pulse', [PulseController::class, 'index'])
    ->name('pulse.dashboard');
```

## Permisos

Convencion: `pulse.view`, `pulse.manage`

(Verificar seeder del modulo para permisos reales asignados.)

## Dependencias

- **Core**: Si
- Otros: `laravel/pulse` (paquete oficial), Redis (almacenamiento de metricas en tiempo real)
