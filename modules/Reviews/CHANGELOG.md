# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto sigue [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-20

Versión inicial del módulo Reviews con integración completa a Google Business Profile API.

### Added

#### Core Features
- Integración completa con Google Business Profile API (v4 y v1)
- Autenticación OAuth 2.0 con refresh automático de tokens
- Sincronización automática de reseñas cada 15 minutos
- Panel de moderación de reseñas
- Sistema de respuestas con workflow (draft → approved → published)
- Plantillas de respuesta reutilizables con variables dinámicas
- Exportación de reseñas a CSV con filtros
- API RESTful con Sanctum para acceso programático

#### Database
- Tabla `review_google_connections` - Conexiones OAuth
- Tabla `review_google_locations` - Ubicaciones de negocio
- Tabla `reviews` - Reseñas sincronizadas
- Tabla `review_moderations` - Configuración de visibilidad
- Tabla `review_replies` - Respuestas a reseñas
- Tabla `review_reply_templates` - Templates de respuestas

#### Models & Relationships
- `Review` model con scopes (rating, withComment, visible, featured, etc)
- `ReviewGoogleConnection` model con encriptación de tokens
- `ReviewGoogleLocation` model con sync tracking
- `ReviewModeration` model para control de visibilidad
- `ReviewReply` model con workflow status
- `ReviewReplyTemplate` model para plantillas
- Todas las relaciones con type hints y eager loading

#### Services
- `GoogleAuthService` - Manejo de OAuth y refresh de tokens
- `GoogleReviewService` - Sincronización y publicación de reseñas
- `GoogleLocationService` - Gestión de ubicaciones
- `GoogleAccountService` - Información de cuentas Google
- `ReviewModerationService` - Moderación de reseñas
- `ReviewReplyService` - Gestión de respuestas
- `ReviewExportService` - Exportación a CSV

#### Jobs
- `SyncGoogleReviewsJob` - Sincronización de reseñas (queue)
- `SyncGoogleLocationsJob` - Sincronización de ubicaciones
- `PublishReviewReplyJob` - Publicación de respuestas
- `DeleteReviewReplyJob` - Eliminación de respuestas

#### Controllers
- `ReviewController` - Gestión de reseñas
- `ReviewReplyController` - Gestión de respuestas
- `ReviewTemplateController` - Gestión de templates
- `Api/ReviewController` - Endpoints API
- `Settings/GoogleConnectionController` - Conexiones OAuth
- `Settings/GoogleLocationController` - Ubicaciones
- `Settings/ReviewSettingsController` - Configuración general

#### Routes
- Web routes: `/reviews`, `/reviews/templates`, `/settings/reviews`
- API routes: `/api/reviews`, `/api/reviews/stats`
- OAuth callback: `/settings/reviews/google/callback`

#### Artisan Commands
- `reviews:install` - Instalación y setup inicial
- `reviews:sync` - Sincronización manual de reseñas
- `reviews:cleanup-expired` - Limpiar tokens expirados
- `reviews:report` - Generar reportes
- `reviews:prune` - Eliminar reseñas antiguas

#### Permissions (Spatie Permission)
- `reviews.connections.*` - 5 permisos para conexiones
- `reviews.locations.*` - 3 permisos para ubicaciones
- `reviews.reviews.*` - 3 permisos para reseñas
- `reviews.replies.*` - 6 permisos para respuestas
- `reviews.templates.*` - 4 permisos para templates
- `reviews.settings.edit` - Permiso de configuración

#### Testing
- 158 total tests
- 8 Feature test files
- 7 Unit test files
- Test coverage para:
  - Modelos y relaciones
  - Servicios de Google API
  - OAuth flow y token refresh
  - Sincronización de reseñas
  - Moderación y filtrado
  - Respuestas y templates
  - Exportación a CSV
  - Policies de autorización
  - Enums y helpers

#### Configuration
- `config/general.php` - Configuración general (sync interval, auto-publish, etc)
- `config/google.php` - Credenciales y endpoints de Google
- `config/permissions.php` - Definición de permisos

#### Events
- `ReviewSynced` - Disparado cuando se sincroniza una reseña
- `ReviewReplied` - Disparado cuando se crea una respuesta

#### Enums
- `ReviewRating` - Calificaciones (ONE, TWO, THREE, FOUR, FIVE)
- `ConnectionStatus` - Estados de conexión (pending, active, expired, revoked, error)
- `ReplyStatus` - Estados de respuesta (draft, approved, published, rejected)

#### Views
- Reviews listing con DataTables
- Review detail view
- Reply management interface
- Template management interface
- Settings panels para conexiones y ubicaciones

#### Documentation
- README.md - Guía completa del módulo
- docs/OAUTH_SETUP.md - Guía paso a paso de OAuth
- docs/DEVELOPMENT.md - Guía para desarrolladores
- docs/API.md - Documentación de API endpoints
- docs/TROUBLESHOOTING.md - Solución de problemas
- docs/ARCHITECTURE.md - Diagrama de arquitectura

#### Activity Logging
- Auditoría de todas las acciones (Spatie Activity Log)
- Logging de cambios en reseñas, respuestas, conexiones
- Tracking de sincronizaciones
- Tracking de publicaciones a Google

#### Security
- Tokens OAuth encriptados en BD
- CSRF protection en todas las rutas
- SSL verification en HTTP requests
- IDOR protection via policies
- Input validation en todos los endpoints
- Output sanitization en responses
- Rate limiting en API

#### Additional
- Modulation via nwidart/laravel-modules
- Factories para testing
- Seeders para datos iniciales
- Migrations versionadas
- Type hints en todo el código
- Eloquent casting (encrypted, array, enum)

### Changed

- N/A (versión inicial)

### Deprecated

- N/A

### Removed

- N/A

### Fixed

- N/A

### Security

- Tokens OAuth encriptados con clave de aplicación
- Validación de CSRF en OAuth callback
- Rate limiting en API (60 requests/min)
- Validación de entrada en todos los endpoints
- Sanitización de salida en respuestas JSON

## Roadmap (Futuro)

### v1.1.0 (Q2 2026)
- [ ] Notificaciones en tiempo real vía WebSocket
- [ ] Operaciones en lote (moderar múltiples reseñas)
- [ ] Dashboard con gráficos de tendencias
- [ ] Integración con Slack/Teams

### v1.2.0 (Q3 2026)
- [ ] Sincronización con Yelp
- [ ] Sincronización con Facebook
- [ ] Sincronización con TripAdvisor
- [ ] Templates avanzadas con lógica condicional

### v2.0.0 (Q4 2026)
- [ ] UI redesign con Tailwind/Alpine
- [ ] Progressive Web App (PWA)
- [ ] Mobile app nativa
- [ ] ML para análisis de sentimiento

## Compatibilidad

- Laravel: 12.x
- PHP: 8.4+
- MySQL: 5.7+ / MariaDB 10.2+
- Redis: 5.0+ (opcional, para caché y queues)

## Dependencias

```
google/apiclient: ^2.15
spatie/laravel-permission: ^6.0
spatie/laravel-activitylog: ^4.0
maatwebsite/excel: ^3.1
league/csv: ^9.8
```

## Notas de Versión

### v1.0.0

Primera versión estable con todas las características core implementadas y testeadas.

**Requisitos previos**:
1. Google Cloud Console project con APIs habilitadas
2. OAuth 2.0 credentials creadas
3. Laravel 12+ con base de datos configurada
4. Redis (recomendado para queue worker)

**Instalación rápida**:
```bash
composer require google/apiclient:^2.15
php artisan migrate
php artisan reviews:install
```

**Breaking Changes**: Ninguno (versión inicial)

**Upgrade Path**: N/A (versión inicial)

**Deprecations**: Ninguno (versión inicial)

## Contributors

- Equipo de desarrollo del proyecto Alsernet

## License

MIT License - Copyright (c) 2026

## Support

Para soporte y reportar bugs:
- Ver documentación en carpeta `docs/`
- Revisar logs en `storage/logs/laravel.log`
- Ejecutar tests: `php artisan test modules/Reviews/tests`
- Usar Tinker para debugging: `php artisan tinker`

---

**Última actualización**: 2026-02-20
