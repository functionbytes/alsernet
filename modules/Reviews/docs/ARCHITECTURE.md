# Architecture - Reviews Module

Descripción detallada de la arquitectura del módulo Reviews.

## Visión General

El módulo Reviews proporciona una integración completa con Google Business Profile API, permitiendo sincronizar reseñas, moderar su visibilidad y responder a ellas de forma automatizada.

### Componentes Principales

```
┌─────────────────────────────────────────────────────────────┐
│                      Laravel Application                    │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           Reviews Module (Modular)                   │   │
│  │                                                      │   │
│  │  ┌─────────────────┐      ┌─────────────────┐      │   │
│  │  │   Controllers   │      │   Services      │      │   │
│  │  │                 │      │                 │      │   │
│  │  │ ReviewController│─────▶│GoogleAuthService│      │   │
│  │  │ ReplyController │      │GoogleReviewServ.│      │   │
│  │  │ SettingsControl.│      │LocationService  │      │   │
│  │  └─────────────────┘      └─────────────────┘      │   │
│  │           △                        △                │   │
│  │           │                        │                │   │
│  │  ┌────────┴────────┐     ┌────────┴────────┐       │   │
│  │  │     Models      │     │      Jobs       │       │   │
│  │  │                 │     │                 │       │   │
│  │  │ Review          │     │ SyncGoogle...Job│       │   │
│  │  │ ReviewReply     │────▶│ PublishReply...│       │   │
│  │  │ Connection      │     │ DeleteReply...J│       │   │
│  │  │ Location        │     │ SyncLocations..│       │   │
│  │  │ Moderation      │     └────────────────┘       │   │
│  │  │ Template        │                               │   │
│  │  └─────────────────┘                               │   │
│  │                                                      │   │
│  └──────────────────────────────────────────────────────┘   │
│           │                            │                     │
│           ▼                            ▼                     │
│  ┌──────────────────┐      ┌──────────────────┐            │
│  │   Database       │      │   Cache/Queue    │            │
│  │  (MariaDB)       │      │  (Redis)         │            │
│  │                  │      │                  │            │
│  │ Tables:          │      │ Queue: google... │            │
│  │ - reviews        │      │ Cache: reviews..│            │
│  │ - connections    │      │                  │            │
│  │ - locations      │      └──────────────────┘            │
│  │ - replies        │                                      │
│  │ - templates      │                                      │
│  │ - moderations    │                                      │
│  └──────────────────┘                                      │
│                                                               │
└─────────────────────────────────────────────────────────────┘
           │                              │
           └──────────────┬───────────────┘
                          ▼
            ┌───────────────────────────┐
            │   Google Business API     │
            │   ┌─────────────────────┐ │
            │   │ Reviews API (v4)    │ │
            │   │ Business Info API   │ │
            │   │ Account Mgmt API    │ │
            │   └─────────────────────┘ │
            └───────────────────────────┘
```

## Capa de Datos

### Base de Datos Schema

```
review_google_connections
├── id (PK)
├── user_id (FK: users)
├── name
├── google_account_id (UNIQUE)
├── google_email
├── access_token (ENCRYPTED)
├── refresh_token (ENCRYPTED)
├── token_expires_at
├── scopes (JSON)
├── status (ENUM)
├── last_error
├── connected_at
├── revoked_at
├── timestamps, softDeletes

review_google_locations
├── id (PK)
├── connection_id (FK: review_google_connections)
├── name
├── google_location_id (UNIQUE)
├── address
├── phone
├── website
├── is_active (BOOLEAN)
├── last_synced_at
├── sync_failed_count
├── error_message
├── timestamps

reviews
├── id (PK)
├── location_id (FK: review_google_locations)
├── google_review_id (UNIQUE)
├── reviewer_name
├── reviewer_photo_url
├── star_rating (ENUM: 1-5)
├── comment (TEXT, NULLABLE)
├── review_time
├── update_time
├── google_reply_text (NULLABLE)
├── google_reply_time (NULLABLE)
├── raw_json (JSON)
├── synced_at
├── timestamps

review_moderations
├── id (PK)
├── review_id (FK: reviews, UNIQUE)
├── is_visible (BOOLEAN)
├── is_featured (BOOLEAN)
├── tags (JSON)
├── notes (TEXT)
├── timestamps

review_replies
├── id (PK)
├── review_id (FK: reviews)
├── created_by_id (FK: users)
├── approved_by_id (FK: users, NULLABLE)
├── reply_text (TEXT)
├── status (ENUM)
├── approved_at (NULLABLE)
├── published_at (NULLABLE)
├── error_message
├── timestamps

review_reply_templates
├── id (PK)
├── user_id (FK: users)
├── name
├── description
├── template_text (TEXT)
├── is_default (BOOLEAN)
├── usage_count
├── timestamps
```

### Índices Principales

```
reviews
├── (location_id)
├── (star_rating)
├── (review_time DESC)
├── (google_review_id)

review_google_connections
├── (user_id)
├── (google_account_id)
├── (status)

review_google_locations
├── (connection_id)
├── (is_active)

review_replies
├── (review_id)
├── (status)
├── (created_by_id)
```

## Capa de Modelos (Eloquent)

### Relaciones

```
User
  └─ has many ReviewGoogleConnection
     └─ has many ReviewGoogleLocation
        └─ has many Review
           ├─ has one ReviewModeration
           └─ has many ReviewReply
              └─ belongs to User (created_by)

ReviewReplyTemplate
  └─ belongs to User

Review
  ├─ belongs to ReviewGoogleLocation
  ├─ has one ReviewModeration
  ├─ has many ReviewReply
```

### Scopes Disponibles

```php
Review::query()
  ->rating(ReviewRating::FIVE)         // Por calificación
  ->withComment()                      // Solo con comentario
  ->withoutComment()                   // Solo sin comentario
  ->withGoogleReply()                  // Solo respondidas
  ->withoutGoogleReply()               // Solo sin respuesta
  ->recent(30)                         // Últimos N días
  ->visible()                          // Solo visibles
  ->featured()                         // Solo destacadas
```

### Casts

```php
Review
├── star_rating → ReviewRating (enum)
├── review_time → datetime
├── raw_json → array

ReviewGoogleConnection
├── access_token → encrypted
├── refresh_token → encrypted
├── token_expires_at → datetime
├── scopes → array
├── status → ConnectionStatus (enum)
└── connected_at → datetime

ReviewReply
└── status → ReplyStatus (enum)
```

## Capa de Servicios

### GoogleAuthService

Responsabilidades:
- Generar URL de autorización OAuth
- Intercambiar código por tokens
- Refrescar tokens expirados
- Revocar tokens
- Obtener info del usuario

Flujo:

```
getAuthUrl(state)
    ↓
Devuelve URL para Google OAuth
    ↓
Usuario hace login en Google
    ↓
handleCallback(code)
    ↓
Intercambia código por tokens
    ↓
Retorna access_token, refresh_token, expires_in
    ↓
Guardar encriptado en BD
    ↓
refreshTokenIfNeeded(connection)
    ↓
Si expired → POST /token con refresh_token
    ↓
Actualizar access_token y token_expires_at
```

### GoogleReviewService

Responsabilidades:
- Sincronizar reseñas desde Google API
- Publicar respuestas a Google
- Eliminar respuestas de Google

Métodos principales:

```
syncReviews(location)
├── Refresh token si expirado
├── Fetch reviews de Google API
├── Actualizar/crear reviews en BD
├── Crear moderations si no existen
├── Disparar ReviewSynced event
└── Logging

publishReply(reply)
├── Cargar review con location.connection
├── Refresh token si expirado
├── PUT /reviews/{id}/reply con texto
├── Actualizar Review.google_reply_text
├── Cambiar reply status a published
└── Activity log

deleteReply(review)
├── DELETE /reviews/{id}/reply
├── Actualizar Review.google_reply_text = null
└── Activity log
```

### ReviewModerationService

Responsabilidades:
- Actualizar configuración de visibilidad
- Destacar/desatacar reseñas
- Agregar/remover tags

### ReviewReplyService

Responsabilidades:
- Crear respuestas (draft)
- Aprobar respuestas
- Publicar respuestas
- Eliminar respuestas

### ReviewExportService

Responsabilidades:
- Exportar reseñas a CSV
- Aplicar filtros
- Formatear datos para CSV

## Capa de Jobs (Queue)

### SyncGoogleReviewsJob

```
Triggered by: Scheduler (cada 15 min) o manualmente
Queue: google-sync
Tries: 3
Backoff: 60 segundos

Workflow:
  ReviewGoogleLocation
    ↓
  SyncGoogleReviewsJob::dispatch()
    ↓
  GoogleReviewService::syncReviews()
    ↓
  Actualizar BD
    ↓
  Si success → completado
  Si error → retry (hasta 3 veces)
  Si fail permanente → log error
```

### PublishReviewReplyJob

```
Triggered by: Controller cuando user click "Publish"
Queue: default
Tries: 3

Workflow:
  ReviewReply (approved)
    ↓
  PublishReviewReplyJob::dispatch()
    ↓
  GoogleReviewService::publishReply()
    ↓
  PUT Google API
    ↓
  Si success → status=published
  Si error → log y notificar user
```

## Capa de Controllers

### ReviewController

```
GET /reviews
  └─ Mostrar listado con stats

GET /reviews/data
  └─ AJAX para DataTable

GET /reviews/{id}
  └─ Mostrar detalles

PATCH /reviews/{id}/moderate
  └─ Actualizar moderación

GET /reviews/export
  └─ Descargar CSV
```

### ReviewReplyController

```
POST /reviews/{id}/replies
  └─ Crear respuesta (draft)

PATCH /reviews/{id}/replies/{id}
  └─ Editar respuesta (solo draft)

POST /reviews/{id}/replies/{id}/publish
  └─ Publicar a Google

DELETE /reviews/{id}/replies/{id}
  └─ Eliminar respuesta
```

### GoogleConnectionController

```
GET /settings/reviews/connections
  └─ Listar conexiones

POST /settings/reviews/connections
  └─ Crear nueva conexión

GET /settings/reviews/oauth/callback
  └─ OAuth callback de Google
```

### GoogleLocationController

```
GET /settings/reviews/locations
  └─ Listar ubicaciones

POST /settings/reviews/locations/{id}/sync
  └─ Sincronizar ubicación
```

## Capa de Autorización (Policies)

### ReviewPolicy

```
view()
  ├─ can('reviews.reviews.view')

create()
  └─ false (no se crean directamente)

update()
  ├─ can('reviews.moderate')

delete()
  └─ false (no se eliminan)
```

### ReviewReplyPolicy

```
create()
  └─ can('reviews.replies.create')

approve()
  └─ can('reviews.replies.approve')

publish()
  └─ can('reviews.replies.publish')

delete()
  └─ can('reviews.replies.delete')
```

## Enums

```
ReviewRating
├── ONE (1 star)
├── TWO (2 stars)
├── THREE (3 stars)
├── FOUR (4 stars)
└── FIVE (5 stars)

ConnectionStatus
├── pending (Esperando confirmación)
├── active (Conectado y funcional)
├── expired (Token expirado, error)
├── revoked (Revocado por usuario)
└── error (Error durante operación)

ReplyStatus
├── draft (Borrador, no publicado)
├── approved (Aprobado, listo para publicar)
├── published (Publicado en Google)
└── rejected (Rechazado)
```

## Events

### ReviewSynced

Disparado cuando una reseña se sincroniza exitosamente.

```php
event(new ReviewSynced($review));

// En listeners:
// - NotifyNewReviewListener
// - UpdateLocationStatsListener
```

### ReviewReplied

Disparado cuando se crea una respuesta.

```php
event(new ReviewReplied($reply));
```

## Seguridad

### Encriptación de Tokens

```
Model::make()
  └─ casts: [
      'access_token' => 'encrypted',
      'refresh_token' => 'encrypted',
    ]
  └─ Usa APP_KEY de Laravel
  └─ Automático en save() y load()
```

### CSRF Protection

```
Middleware: web
  ├─ Verifica token CSRF en POST/PATCH/DELETE
  ├─ Incluido automáticamente en forms
  └─ AJAX: header X-CSRF-TOKEN
```

### Autorización

```
Policy + Gate
  ├─ Cada controller usa authorizeResource()
  ├─ Policies checkan permisos
  └─ Spatie Permission integrado
```

### SQL Injection

```
Query Builder
  ├─ Bound parameters automáticos
  ├─ where('column', $value)
  └─ Raw queries evitadas
```

### Rate Limiting

```
API Middleware: throttle:60,1
  └─ 60 requests por minuto por usuario
```

## Performance

### Eager Loading

```php
// ❌ Malo (N+1)
$reviews = Review::all();
foreach ($reviews as $review) {
    echo $review->location->name;  // Query por cada review
}

// ✅ Bueno
$reviews = Review::with('location', 'moderation')
    ->get();
foreach ($reviews as $review) {
    echo $review->location->name;  // Sin queries adicionales
}
```

### Indexing

Índices en columnas más consultadas:
- `reviews(location_id, review_time)`
- `review_google_connections(user_id, status)`
- `review_google_locations(connection_id, is_active)`

### Caching (Redis)

```
Cache keys:
├── reviews:location:{id}:count
├── reviews:location:{id}:avg_rating
├── reviews:stats:30days
└── templates:user:{id}
```

## Testing

### Estrategia de Testing

```
Unit Tests (45%)
├── Models
├── Enums
└── Services

Feature Tests (55%)
├── OAuth flow
├── Sincronización
├── CRUD operations
├── Autorización
└── API endpoints
```

### Factories

```php
Review::factory()
    ->for(ReviewGoogleLocation::factory())
    ->state('featured')
    ->count(10)
    ->create();
```

## Deployment

### Producción

```
1. Instalar dependencias
   composer install --optimize-autoloader

2. Ejecutar migraciones
   php artisan migrate --force

3. Cache calidez
   php artisan config:cache
   php artisan route:cache

4. Queue worker (Supervisor)
   [program:reviews-queue]
   command=php artisan queue:work --queue=google-sync

5. Scheduler
   * * * * * php artisan schedule:run

6. Logs y Monitoring
   tail -f storage/logs/laravel.log
```

## Monitoreo

### Key Metrics

```
- Reviews synced per day
- Average sync time
- Failed jobs count
- API response times
- Token refresh success rate
- Published replies count
```

### Logging

```
Application logs → storage/logs/laravel.log
Activity logs → activity_log table (Spatie)
Queue logs → storage/logs/queue.log
Google API errors → logs con stack trace
```

## Diagrama de Flujo Completo

### Sincronización de Reseñas

```
[Scheduler] triggers every 15 minutes
    ↓
ReviewGoogleLocation::active()->needingSync()
    ↓
SyncGoogleReviewsJob::dispatch($location) × N
    ↓
Queue Worker receives job
    ↓
GoogleAuthService::refreshTokenIfNeeded()
    ├─ Check if token_expires_at < now()
    ├─ If expired: POST /token with refresh_token
    └─ Update access_token in DB
    ↓
GoogleReviewService::fetchReviews()
    ├─ GET /locations/{id}/reviews
    └─ Parse response
    ↓
DB::transaction()
    ├─ Review::updateOrCreate() for each review
    ├─ Create ReviewModeration if not exists
    └─ ReviewGoogleLocation::markAsSynced()
    ↓
event(ReviewSynced($review))
    ├─ Trigger event listeners
    └─ Activity logging
    ↓
Job completed ✅
```

### Publicación de Respuesta

```
[User] clicks "Publish"
    ↓
ReviewReply::update(['status' => 'approved'])
    ↓
PublishReviewReplyJob::dispatch($reply)
    ↓
GoogleAuthService::refreshTokenIfNeeded()
    ↓
GoogleReviewService::publishReply()
    ├─ PUT /reviews/{id}/reply
    ├─ { "comment": "reply text" }
    └─ Parse response
    ↓
Review::update(['google_reply_text' => $text])
    ↓
ReviewReply::update(['status' => 'published'])
    ↓
activity() logging
    ↓
Response to user ✅
```

## Extensión Futura

### Agregar Nueva Plataforma (Yelp, Facebook)

1. Crear nuevo servicio: `YelpReviewService`
2. Extender `Review` model con plataforma
3. Nuevas migraciones para Yelp tables
4. Nuevos jobs para sincronización
5. Nuevos controllers y routes
6. Nuevos permisos

### Agregar Notificaciones

1. Crear `ReviewNotificationService`
2. Crear Notification classes
3. Implementar en event listeners
4. Agregar channel: mail, slack, sms, etc

---

**Última actualización**: 2026-02-20
