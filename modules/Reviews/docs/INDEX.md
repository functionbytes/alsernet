# Documentation Index - Reviews Module

Índice completo de documentación para el módulo Reviews.

## Para Empezar

1. **[README.md](../README.md)** ⭐ START HERE
   - Overview del módulo
   - Características principales
   - Instalación rápida
   - Uso básico

2. **[OAUTH_SETUP.md](OAUTH_SETUP.md)** 🔐 OAuth Configuration
   - Crear proyecto en Google Cloud
   - Habilitar APIs
   - Obtener credenciales
   - Configurar en Laravel

## Para Usuarios/Administradores

### Configuración

- **[OAUTH_SETUP.md](OAUTH_SETUP.md)** - Configuración de OAuth paso a paso
- **[README.md](../README.md#configuración)** - Sección de Configuración

### Operación Diaria

- **[README.md](../README.md#uso)** - Cómo usar el módulo
- **[README.md](../README.md#artisan-comandos)** - Comandos disponibles

### Solución de Problemas

- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** ❓ Problemas comunes y soluciones
  - Errores de OAuth
  - Problemas de sincronización
  - Problemas de respuestas
  - Problemas de datos
  - Debugging avanzado

## Para Desarrolladores

### Arquitectura y Diseño

- **[ARCHITECTURE.md](ARCHITECTURE.md)** 🏗️ Visión general de la arquitectura
  - Componentes principales
  - Capa de datos y modelos
  - Capa de servicios
  - Capa de controllers
  - Diagramas de flujo

- **[DEVELOPMENT.md](DEVELOPMENT.md)** 👨‍💻 Guía de desarrollo
  - Estructura del código
  - Patrones de código
  - Cómo agregar características
  - Testing guidelines
  - Debugging

### API Documentation

- **[API.md](API.md)** 📡 Documentación completa de API
  - Autenticación
  - Endpoints disponibles
  - Request/Response examples
  - Error codes
  - Rate limiting
  - Ejemplos con cURL y JavaScript

## Recursos

### Por Tipo de Tarea

| Tarea | Recurso |
|-------|---------|
| Instalar el módulo | [README.md](../README.md#instalación) |
| Configurar OAuth | [OAUTH_SETUP.md](OAUTH_SETUP.md) |
| Usar el panel admin | [README.md](../README.md#panel-de-control) |
| Escribir API client | [API.md](API.md) |
| Extender funcionalidad | [DEVELOPMENT.md](DEVELOPMENT.md) |
| Resolver problemas | [TROUBLESHOOTING.md](TROUBLESHOOTING.md) |
| Entender arquitectura | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Ver historial | [../CHANGELOG.md](../CHANGELOG.md) |

### Por Rol

#### Admin/DevOps
1. [README.md](../README.md) - Overview
2. [OAUTH_SETUP.md](OAUTH_SETUP.md) - Configuración OAuth
3. [README.md](../README.md#artisan-comandos) - Comandos
4. [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Troubleshooting

#### Moderador de Reseñas
1. [README.md](../README.md#gestionar-reseñas) - Cómo moderar
2. [README.md](../README.md#templates-de-respuesta) - Templates
3. [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - FAQs

#### Backend Developer
1. [ARCHITECTURE.md](ARCHITECTURE.md) - Arquitectura
2. [DEVELOPMENT.md](DEVELOPMENT.md) - Desarrollo
3. [API.md](API.md) - Endpoints
4. [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Debugging

#### Frontend Developer / Third-party Integration
1. [API.md](API.md) - API completa
2. [README.md](../README.md#api-restful) - Overview API

## Referencia Rápida

### Instalación (3 pasos)

```bash
composer require google/apiclient:^2.15
php artisan migrate
php artisan reviews:install
```

### Variables de Entorno

```env
GOOGLE_CLIENT_ID=xxx
GOOGLE_CLIENT_SECRET=xxx
REVIEWS_SYNC_INTERVAL=15
REVIEWS_AUTO_PUBLISH=false
```

### Rutas Principales

```
Panel Admin:     /settings/reviews
Reseñas:        /reviews
Templates:      /reviews/templates
API:            /api/reviews
```

### Comandos Artisan

```bash
php artisan reviews:sync                  # Sincronizar
php artisan reviews:cleanup-expired       # Limpiar tokens
php artisan reviews:prune --days=180      # Eliminar antiguos
php artisan reviews:report                # Generar reporte
```

### Permisos Base

```
reviews.reviews.view        # Ver reseñas
reviews.moderate            # Moderar
reviews.replies.create      # Responder
reviews.replies.approve     # Aprobar respuestas
reviews.replies.publish     # Publicar
```

## Herramientas Útiles

### Debugging

```bash
# Tinker
php artisan tinker
> Review::first()
> ReviewGoogleConnection::find(1)->refreshTokenIfNeeded()

# Logs
tail -f storage/logs/laravel.log

# Queue
php artisan queue:failed
php artisan queue:work --queue=google-sync
```

### Testing

```bash
# Todos los tests
php artisan test modules/Reviews/tests

# Tests específicos
php artisan test modules/Reviews/tests/Feature/ReviewSyncTest.php

# Con cobertura
php artisan test modules/Reviews/tests --coverage
```

## FAQ Rápidas

### ¿Cómo conecto Google?
→ Ver [OAUTH_SETUP.md](OAUTH_SETUP.md)

### ¿Cómo sincronizo reseñas?
→ Ver [README.md](../README.md#cola-de-trabajos)

### ¿Qué hacer si no syncronizan?
→ Ver [TROUBLESHOOTING.md](TROUBLESHOOTING.md#reviews-no-se-sincronizan)

### ¿Cómo uso la API?
→ Ver [API.md](API.md)

### ¿Cómo extiendo el módulo?
→ Ver [DEVELOPMENT.md](DEVELOPMENT.md)

## Diagrama de Navegación

```
START
  │
  ├─→ Necesito INSTALAR
  │    └─→ [README.md#instalación]
  │    └─→ [OAUTH_SETUP.md]
  │
  ├─→ Necesito USAR (admin)
  │    └─→ [README.md#uso]
  │    └─→ [README.md#artisan-comandos]
  │
  ├─→ Necesito RESOLVER PROBLEMA
  │    └─→ [TROUBLESHOOTING.md]
  │
  ├─→ Necesito INTEGRAR API
  │    └─→ [API.md]
  │
  ├─→ Necesito ENTENDER CÓDIGO
  │    └─→ [ARCHITECTURE.md]
  │    └─→ [DEVELOPMENT.md]
  │
  └─→ Necesito HISTORIAL
       └─→ [CHANGELOG.md]
```

## Versiones

- **Última versión**: 1.0.0
- **Fecha**: 2026-02-20
- **Status**: Stable
- **Laravel**: 12.x
- **PHP**: 8.4+

## Contacto y Soporte

- **Bugs**: Reportar con logs completos
- **Feature Requests**: Documentar en issue
- **Security**: Contactar admin privadamente

## Actualizaciones Documentación

- ✅ README.md - Completado
- ✅ OAUTH_SETUP.md - Completado
- ✅ DEVELOPMENT.md - Completado
- ✅ API.md - Completado
- ✅ TROUBLESHOOTING.md - Completado
- ✅ ARCHITECTURE.md - Completado
- ✅ CHANGELOG.md - Completado
- ✅ INDEX.md - Completado

## Próximas Mejoras Documentadas

- [ ] Video tutorials
- [ ] API sandbox/testing
- [ ] Más ejemplos de código
- [ ] Diagrams mejorados
- [ ] Guía de migración para futuros updates

---

**Última actualización**: 2026-02-20

Para empezar: Ir a [README.md](../README.md)
