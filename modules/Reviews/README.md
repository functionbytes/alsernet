# Reviews Module

Integración completa con Google Business Profile API para la gestión de reseñas con soporte para moderación, respuestas automatizadas y templates.

[![Module Version](https://img.shields.io/badge/version-1.0.0-blue)]()
[![Tests](https://img.shields.io/badge/tests-158-brightgreen)]()
[![License](https://img.shields.io/badge/license-MIT-green)]()

## Características

- **OAuth 2.0 Integration**: Autenticación segura con Google Business Profile
- **Sincronización Automática**: Actualización cada 15 minutos vía scheduler
- **Panel de Moderación**: Visibilidad, destacadas, filtros y tags
- **Sistema de Respuestas**: Workflow draft → approved → published
- **Templates Dinámicos**: Respuestas reutilizables con variables personalizadas
- **Exportación**: Descarga de reseñas a CSV
- **API RESTful**: Endpoints con Sanctum para acceso programático
- **Activity Logging**: Auditoría completa con Spatie Activity Log
- **Multi-tenant Ready**: Soporte para múltiples ubicaciones y conexiones
- **Rate Limiting**: Protección contra límites de Google API
- **Encriptación**: Tokens OAuth seguros en base de datos

## Requisitos

- Laravel 12+
- PHP 8.4+
- MariaDB
- Redis (para colas y caché)
- Credenciales de Google Cloud Console

### Extensiones PHP Requeridas

```bash
php-openssl    # Para encriptación de tokens
php-curl       # Para requests a Google API
php-json       # Para manejo de JSON
```

## Instalación

### 1. Instalar Dependencias

```bash
composer require google/apiclient:^2.15
```

### 2. Ejecutar Migraciones

```bash
php artisan migrate
```

Las migraciones crearán las siguientes tablas:

- `review_google_connections` - Conexiones OAuth con Google
- `review_google_locations` - Ubicaciones de negocio sincronizadas
- `reviews` - Reseñas descargadas de Google
- `review_moderations` - Configuración de visibilidad y destacadas
- `review_replies` - Respuestas a reseñas
- `review_reply_templates` - Templates reutilizables

### 3. Ejecutar Seeders (Opcional)

```bash
php artisan db:seed --class=Modules\\Reviews\\Database\\Seeders\\ReviewsDatabaseSeeder
```

Esto crea permisos base con Spatie Permission.

### 4. Habilitar Módulo

```bash
php artisan module:enable Reviews
```

### 5. Publicar Configuración (Opcional)

```bash
php artisan vendor:publish --tag=reviews-config
```

Archivos publicados:

- `config/reviews/general.php` - Configuración general del módulo
- `config/reviews/google.php` - Credenciales de Google

## Configuración

### Variables de Entorno

Agregar a `.env`:

```env
# Google OAuth Credentials
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret

# Configuración de sincronización
REVIEWS_SYNC_INTERVAL=15                  # Minutos entre sincronizaciones
REVIEWS_AUTO_PUBLISH=false                # Auto-publicar respuestas aprobadas
REVIEWS_DEFAULT_VISIBLE=true              # Reseñas visibles por defecto
```

### Obtener Credenciales de Google

Ver guía detallada en [OAUTH_SETUP.md](docs/OAUTH_SETUP.md).

Resumen rápido:

1. Ir a [Google Cloud Console](https://console.cloud.google.com)
2. Crear proyecto nuevo
3. Habilitar APIs:
   - My Business Account Management
   - Business Information
   - My Business
4. Crear credenciales OAuth 2.0 (Desktop App)
5. Agregar URL de callback: `https://tu-dominio.com/settings/reviews/google/callback`
6. Copiar Client ID y Secret a `.env`

## Uso

### Panel de Control

Acceder a `/settings/reviews` para:

- **Conexiones**: Conectar/desconectar cuentas Google
- **Ubicaciones**: Seleccionar ubicaciones para sincronizar
- **Configuración**: Ajustar parámetros de moderación y respuestas

### Gestionar Reseñas

En `/reviews`:

1. **Listar**: Ver todas las reseñas con filtros
2. **Ver Detalle**: Detalles completos de una reseña
3. **Moderar**: Cambiar visibilidad, destacar, agregar tags
4. **Responder**: Crear respuesta (draft) y publicar a Google
5. **Exportar**: Descargar reseñas a CSV

### Templates de Respuesta

En `/reviews/templates`:

1. Crear respuesta reutilizable
2. Usar variables dinámicas: `{{reviewer_name}}`, `{{rating}}`
3. Aplicar a reseñas seleccionadas

Ejemplo:

```
Gracias {{reviewer_name}} por tu reseña de {{rating}} estrellas.
Nos alegra haber podido servirte.
```

### Cola de Trabajos

Iniciar worker para sincronización automática:

```bash
# Desarrollo
php artisan queue:listen --queue=google-sync

# Producción con Supervisor
php artisan queue:work --queue=google-sync --daemon
```

## Artisan Comandos

### reviews:install

Instalación inicial del módulo (crea permisos, ejecuta seeders).

```bash
php artisan reviews:install
```

### reviews:sync

Sincronización manual de reseñas desde Google.

```bash
# Sincronizar todas las ubicaciones activas
php artisan reviews:sync

# Sincronizar una conexión específica
php artisan reviews:sync --connection=1

# Sincronizar una ubicación específica
php artisan reviews:sync --location=5

# Forzar sincronización incluso si no pasó tiempo
php artisan reviews:sync --force
```

### reviews:cleanup-expired

Limpiar conexiones OAuth expiradas y renovar tokens.

```bash
php artisan reviews:cleanup-expired
```

Se ejecuta automáticamente cada 15 minutos si hay conexiones expiradas.

### reviews:report

Generar reporte de reseñas (JSON o CSV).

```bash
php artisan reviews:report --format=json --output=reports/
php artisan reviews:report --format=csv --days=30
```

### reviews:prune

Eliminar reseñas antiguas (más de 90 días por defecto).

```bash
php artisan reviews:prune --days=180
```

## API RESTful

### Autenticación

Usar token de Sanctum:

```bash
Authorization: Bearer your-sanctum-token
```

### Endpoints

#### GET /api/reviews

Listar reseñas con filtros.

```bash
curl -H "Authorization: Bearer token" \
  "https://tu-dominio.com/api/reviews?location_id=1&rating=5"
```

Parámetros de query:

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| location_id | int | Filtrar por ubicación |
| rating | int | Filtrar por calificación (1-5) |
| has_comment | bool | Solo reseñas con comentario |
| has_reply | bool | Solo reseñas respondidas |
| is_visible | bool | Solo visibles en widgets |
| date_from | date | Fecha mínima (Y-m-d) |
| date_to | date | Fecha máxima (Y-m-d) |

Respuesta:

```json
{
  "data": [
    {
      "id": 1,
      "reviewer_name": "Juan Pérez",
      "star_rating": 5,
      "comment": "Excelente servicio",
      "review_time": "2026-02-20T10:30:00Z",
      "google_reply_text": null,
      "is_visible": true,
      "is_featured": false
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 15,
    "current_page": 1
  }
}
```

#### GET /api/reviews/stats

Estadísticas de reseñas.

```bash
curl -H "Authorization: Bearer token" \
  "https://tu-dominio.com/api/reviews/stats"
```

Respuesta:

```json
{
  "total": 150,
  "recent_30_days": 45,
  "average_rating": 4.5,
  "with_comment": 120,
  "unanswered": 25,
  "by_rating": {
    "5": 95,
    "4": 35,
    "3": 15,
    "2": 3,
    "1": 2
  }
}
```

#### GET /api/reviews/{id}

Detalles de una reseña.

```bash
curl -H "Authorization: Bearer token" \
  "https://tu-dominio.com/api/reviews/1"
```

Respuesta:

```json
{
  "data": {
    "id": 1,
    "location_id": 1,
    "reviewer_name": "Juan Pérez",
    "reviewer_photo_url": "https://...",
    "star_rating": 5,
    "comment": "Excelente servicio",
    "review_time": "2026-02-20T10:30:00Z",
    "google_reply_text": null,
    "is_visible": true,
    "is_featured": false,
    "replies": [
      {
        "id": 1,
        "reply_text": "Gracias Juan",
        "status": "published",
        "created_at": "2026-02-20T12:00:00Z"
      }
    ]
  }
}
```

## Permisos

El módulo registra automáticamente los siguientes permisos (Spatie Permission):

### Conexiones de Google

- `reviews.connections.view` - Ver conexiones
- `reviews.connections.create` - Crear conexiones
- `reviews.connections.edit` - Editar conexiones
- `reviews.connections.delete` - Eliminar conexiones
- `reviews.connections.revoke` - Revocar acceso

### Ubicaciones

- `reviews.locations.view` - Ver ubicaciones
- `reviews.locations.sync` - Sincronizar ubicaciones
- `reviews.locations.edit` - Editar configuración

### Reseñas

- `reviews.reviews.view` - Ver reseñas
- `reviews.reviews.export` - Exportar a CSV
- `reviews.moderate` - Moderar visibilidad/destacadas

### Respuestas

- `reviews.replies.view` - Ver respuestas
- `reviews.replies.create` - Crear respuestas
- `reviews.replies.edit` - Editar respuestas
- `reviews.replies.delete` - Eliminar respuestas
- `reviews.replies.approve` - Aprobar respuestas
- `reviews.replies.publish` - Publicar a Google

### Templates

- `reviews.templates.view` - Ver templates
- `reviews.templates.create` - Crear templates
- `reviews.templates.edit` - Editar templates
- `reviews.templates.delete` - Eliminar templates

### Configuración

- `reviews.settings.edit` - Editar configuración

### Asignar Permisos a Roles

```php
use Spatie\Permission\Models\Role;

$admin = Role::findByName('admin');
$admin->givePermissionTo('reviews.*');

$moderator = Role::findByName('moderator');
$moderator->givePermissionTo([
    'reviews.reviews.view',
    'reviews.reviews.export',
    'reviews.moderate',
    'reviews.replies.view',
    'reviews.replies.create',
    'reviews.replies.approve',
]);
```

## Estructura de Carpetas

```
modules/Reviews/
├── app/
│   ├── Console/Commands/           # Comandos Artisan
│   ├── Enums/                      # Enums (ReviewRating, ConnectionStatus, etc)
│   ├── Events/                     # Eventos (ReviewSynced, etc)
│   ├── Http/
│   │   ├── Controllers/            # Controladores web
│   │   ├── Controllers/Api/        # Controladores API
│   │   ├── Controllers/Settings/   # Controladores de configuración
│   │   └── Requests/               # Form Requests y validación
│   ├── Jobs/                       # Queue jobs (SyncGoogleReviewsJob, etc)
│   ├── Models/                     # Modelos Eloquent
│   ├── Policies/                   # Policies de autorización
│   └── Services/                   # Servicios de negocio
├── config/
│   ├── general.php                 # Configuración general
│   ├── google.php                  # Credenciales de Google
│   └── permissions.php             # Definición de permisos
├── database/
│   ├── factories/                  # Factories para testing
│   ├── migrations/                 # Migraciones de BD
│   └── seeders/                    # Seeders
├── resources/views/
│   ├── reviews/                    # Vistas de gestión
│   └── settings/                   # Vistas de configuración
├── routes/
│   ├── web.php                     # Rutas web
│   └── api.php                     # Rutas API
├── tests/
│   ├── Feature/                    # Tests funcionales
│   └── Unit/                       # Tests unitarios
└── README.md
```

## Flujo de Sincronización

```
Scheduler (cada 15 min)
    ↓
ReviewGoogleLocation::active()->needingSync()
    ↓
SyncGoogleReviewsJob (dispatched)
    ↓
GoogleReviewService::syncReviews()
    ↓
Refresh token (si expirado)
    ↓
Fetch reviews from Google API
    ↓
updateOrCreate en tabla reviews
    ↓
Crear ReviewModeration (si no existe)
    ↓
Disparar evento ReviewSynced
    ↓
Activity log
```

## Flujo de Respuesta

```
Usuario crea respuesta en UI
    ↓
Crear ReviewReply (status: draft)
    ↓
Moderador approves
    ↓
PublishReviewReplyJob dispatched
    ↓
GoogleReviewService::publishReply()
    ↓
Refresh token (si expirado)
    ↓
PUT /reviews/{id}/reply Google API
    ↓
Actualizar Review.google_reply_text
    ↓
Cambiar ReviewReply status a published
    ↓
Activity log
```

## Testing

Ejecutar todos los tests:

```bash
php artisan test modules/Reviews/tests
```

Ejecutar solo tests unitarios:

```bash
php artisan test modules/Reviews/tests/Unit
```

Ejecutar solo tests funcionales:

```bash
php artisan test modules/Reviews/tests/Feature
```

Ejecutar test específico:

```bash
php artisan test modules/Reviews/tests/Feature/ReviewSyncTest.php
```

Con reporte de cobertura:

```bash
php artisan test modules/Reviews/tests --coverage
```

El módulo incluye 158 tests que cubren:

- Modelos y relaciones
- Servicios de Google API
- OAuth flow
- Sincronización de reseñas
- Moderación
- Respuestas y templates
- Exportación
- Policies de autorización
- Enums y helpers

## Troubleshooting

Ver guía completa en [TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md).

### Problema: "Invalid OAuth state"

**Causa**: Token de sesión expirado o CSRF inválido

**Solución**:

```bash
# Limpiar sesión
php artisan cache:clear
php artisan session:table    # Si no existe tabla sessions

# Verificar config
php artisan tinker
> config('session.driver')
> config('session.lifetime')
```

### Problema: "Token expired"

**Causa**: Token OAuth de Google expiró

**Solución**:

```bash
# Renovar tokens
php artisan reviews:cleanup-expired

# O crear nuevo comando personalizado
php artisan tinker
> \Modules\Reviews\Models\ReviewGoogleConnection::all()->each->refreshTokenIfNeeded()
```

### Problema: "Rate limit exceeded"

**Causa**: Demasiados requests a Google API

**Solución**:

```env
# Reducir frecuencia
REVIEWS_SYNC_INTERVAL=30

# Reducir cantidad de requests
REVIEWS_RATE_LIMIT_PER_MINUTE=30
```

### Problema: "Insufficient permissions"

**Causa**: Scopes insuficientes en OAuth

**Solución**:

1. Ir a Google Cloud Console
2. Revocar consentimiento de app
3. Reconectar cuenta Google
4. Aceptar todos los permisos

### Problema: "Location not verified"

**Causa**: Ubicación no verificada en Google Business Profile

**Solución**:

1. Verificar ubicación en [Google Business Profile](https://www.google.com/business)
2. Completar verificación por SMS/correo
3. Esperar a que Google sincronice (hasta 48 horas)

### Problema: "Reviews no se sincronizan"

**Causa**: Queue worker no está corriendo

**Solución**:

```bash
# Verificar queue
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# Iniciar queue worker
php artisan queue:work --queue=google-sync

# Para Supervisor (producción)
cat > /etc/supervisor/conf.d/reviews-queue.conf << EOF
[program:reviews-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=google-sync --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/reviews-queue.log
EOF
supervisorctl reread
supervisorctl update
supervisorctl start reviews-queue:*
```

## Arquitectura

Ver diagrama completo en [ARCHITECTURE.md](docs/ARCHITECTURE.md).

### Componentes Principales

- **GoogleAuthService**: Manejo de OAuth y tokens
- **GoogleReviewService**: Sincronización y publicación de reseñas
- **GoogleLocationService**: Gestión de ubicaciones
- **GoogleAccountService**: Información de cuentas Google
- **ReviewModerationService**: Moderación de reseñas
- **ReviewReplyService**: Gestión de respuestas
- **ReviewExportService**: Exportación a CSV

### Base de Datos

Relaciones:

```
User → ReviewGoogleConnection (1:Many)
ReviewGoogleConnection → ReviewGoogleLocation (1:Many)
ReviewGoogleLocation → Review (1:Many)
Review → ReviewModeration (1:1)
Review → ReviewReply (1:Many)
ReviewReplyTemplate → ReviewReply (1:Many)
User → ReviewReplyTemplate (1:Many)
```

## Desarrollo

Ver guía en [DEVELOPMENT.md](docs/DEVELOPMENT.md) para:

- Estructura del código
- Agregar servicios nuevos
- Agregar jobs
- Agregar filtros
- Extender modelos
- Patrones de código
- Contribución

## Changelog

Ver [CHANGELOG.md](CHANGELOG.md) para historial completo de versiones.

## Soporte

- **Docs**: Ver carpeta `docs/`
- **Tests**: Ver carpeta `tests/`
- **Issues**: Reportar en repositorio

## Licencia

MIT License - Copyright (c) 2026
