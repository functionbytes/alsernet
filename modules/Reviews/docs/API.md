# API Documentation - Reviews

Referencia completa de endpoints RESTful para gestión de reseñas.

## Autenticación

Todos los endpoints requieren un token de Sanctum válido.

### Header Requerido

```
Authorization: Bearer {token}
```

Obtener token de Sanctum:

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

Respuesta:

```json
{
  "token": "abcdef123456..."
}
```

## Rate Limiting

La API implementa rate limiting basado en roles:

| Tipo de Usuario | Límite | Identificación |
|----------------|--------|----------------|
| Admin (super-admin, administrative, manager) | 1000 requests/hora | User ID |
| Usuarios autenticados | 100 requests/hora | User ID |
| Usuarios no autenticados (guest) | 20 requests/hora | IP Address |

### Headers de Rate Limit

Todas las respuestas incluyen:

```
X-API-Version: 1.0
X-Request-ID: 550e8400-e29b-41d4-a716-446655440000
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1709299200
Cache-Control: no-cache, no-store, must-revalidate
```

### Cuando se Excede el Límite (429)

```json
{
  "success": false,
  "message": "Too many requests. Please slow down.",
  "errors": {
    "rate_limit": "You have exceeded the rate limit. Please try again later."
  },
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "retry_after": 3600
}
```

Con headers adicionales:
```
HTTP/1.1 429 Too Many Requests
Retry-After: 3600
X-RateLimit-Reset: 1709299200
```

## Base URL

```
https://tu-dominio.com/api/reviews
```

## Endpoints

### GET /reviews

Listar reseñas con filtros y paginación.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.view`

**Request**:

```bash
GET /api/reviews?location_id=1&rating=5&page=1&per_page=15
```

**Query Parameters**:

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| location_id | integer | No | Filtrar por ubicación | 1 |
| rating | integer | No | Filtrar por calificación (1-5) | 5 |
| has_comment | boolean | No | Solo reseñas con comentario | true |
| has_reply | boolean | No | Solo reseñas respondidas | true |
| is_visible | boolean | No | Solo reseñas visibles | true |
| is_featured | boolean | No | Solo reseñas destacadas | true |
| search | string | No | Buscar en nombre/comentario | "excelente" |
| date_from | date | No | Fecha mínima (Y-m-d) | 2026-01-01 |
| date_to | date | No | Fecha máxima (Y-m-d) | 2026-02-28 |
| sort_by | string | No | Campo para ordenar | review_time |
| sort_order | string | No | Orden (asc/desc) | desc |
| page | integer | No | Número de página | 1 |
| per_page | integer | No | Items por página (max 100) | 15 |

**Response (200)**:

```json
{
  "success": true,
  "message": "Reviews retrieved successfully",
  "data": [
    {
      "id": 1,
      "location_id": 1,
      "reviewer_name": "Juan Pérez",
      "reviewer_photo_url": "https://...",
      "star_rating": 5,
      "comment": "Excelente servicio, muy recomendado",
      "review_time": "2026-02-20T10:30:00Z",
      "google_reply_text": "Gracias por tu reseña, Juan",
      "google_reply_time": "2026-02-20T14:00:00Z",
      "is_visible": true,
      "is_featured": false,
      "location": {
        "id": 1,
        "name": "Oficina Principal"
      },
      "replies_count": 1,
      "created_at": "2026-02-20T10:35:00Z",
      "updated_at": "2026-02-20T14:05:00Z"
    }
  ],
  "meta": {
    "pagination": {
      "total": 150,
      "count": 15,
      "per_page": 15,
      "current_page": 1,
      "total_pages": 10,
      "has_more_pages": true
    }
  },
  "links": {
    "first": "https://.../api/reviews?page=1",
    "last": "https://.../api/reviews?page=10",
    "prev": null,
    "next": "https://.../api/reviews?page=2"
  }
}
```

**Errores**:

```json
{
  "success": false,
  "message": "Unauthorized",
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

---

### GET /reviews/{id}

Obtener detalles de una reseña.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.view`

**Request**:

```bash
GET /api/reviews/1
```

**Response (200)**:

```json
{
  "success": true,
  "message": "Review retrieved successfully",
  "data": {
    "id": 1,
    "location_id": 1,
    "google_review_id": "reviews/1234567890",
    "reviewer_name": "Juan Pérez",
    "reviewer_photo_url": "https://...",
    "star_rating": 5,
    "comment": "Excelente servicio, muy recomendado. Volveré pronto.",
    "review_time": "2026-02-20T10:30:00Z",
    "update_time": "2026-02-21T08:00:00Z",
    "google_reply_text": "Gracias por tu reseña, Juan",
    "google_reply_time": "2026-02-20T14:00:00Z",
    "is_visible": true,
    "is_featured": false,
    "synced_at": "2026-02-20T10:35:00Z",
    "location": {
      "id": 1,
      "name": "Oficina Principal",
      "google_location_id": "accounts/123/locations/456"
    },
    "moderation": {
      "id": 1,
      "is_visible": true,
      "is_featured": false,
      "tags": ["excelente", "recomendado"],
      "notes": "Cliente frecuente"
    },
    "replies": [
      {
        "id": 1,
        "reply_text": "Gracias por tu reseña, Juan",
        "status": "published",
        "created_by_id": 2,
        "created_by_name": "Admin User",
        "approved_by_id": 1,
        "approved_by_name": "Admin",
        "published_at": "2026-02-20T14:00:00Z",
        "created_at": "2026-02-20T13:30:00Z",
        "updated_at": "2026-02-20T14:00:00Z"
      }
    ],
    "created_at": "2026-02-20T10:35:00Z",
    "updated_at": "2026-02-21T08:05:00Z"
  }
}
```

**Errores**:

- `404 Not Found`: Reseña no existe
- `403 Forbidden`: Sin permiso para ver reseña

---

### GET /reviews/stats

Obtener estadísticas generales de reseñas.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.view`

**Request**:

```bash
GET /api/reviews/stats?location_id=1&days=30
```

**Query Parameters**:

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| location_id | integer | No | Limitar a una ubicación |
| days | integer | No | Últimos N días (default 30) |

**Response (200)**:

```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total": 150,
    "recent_30_days": 45,
    "average_rating": 4.53,
    "with_comment": 120,
    "with_reply": 98,
    "unanswered": 52,
    "visible": 145,
    "hidden": 5,
    "featured": 12,
    "by_rating": {
      "1": 2,
      "2": 3,
      "3": 15,
      "4": 35,
      "5": 95
    },
    "distribution_by_month": {
      "2026-01": 20,
      "2026-02": 45
    },
    "avg_rating_trend": {
      "this_month": 4.55,
      "last_month": 4.42,
      "change_percent": 2.9
    },
    "response_time_avg_hours": 8.5,
    "locations_summary": [
      {
        "id": 1,
        "name": "Oficina Principal",
        "total_reviews": 100,
        "avg_rating": 4.6,
        "pending_replies": 5
      }
    ]
  }
}
```

---

### POST /reviews/{id}/moderate

Actualizar configuración de moderación de una reseña.

**Autenticación**: Requerida

**Permisos**: `reviews.moderate`

**Request**:

```bash
POST /api/reviews/1/moderate \
  -H "Content-Type: application/json" \
  -d '{
    "is_visible": false,
    "is_featured": true,
    "tags": ["negativa", "resolvable"]
  }'
```

**Body**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| is_visible | boolean | No | Mostrar en widgets |
| is_featured | boolean | No | Destacar reseña |
| tags | array | No | Tags customizados |

**Response (200)**:

```json
{
  "success": true,
  "message": "Moderación actualizada correctamente",
  "data": {
    "id": 1,
    "review_id": 1,
    "is_visible": false,
    "is_featured": true,
    "tags": ["negativa", "resolvable"],
    "updated_at": "2026-02-20T15:00:00Z"
  }
}
```

**Errores**:

- `422 Unprocessable Entity`: Validación fallida
- `403 Forbidden`: Sin permiso para moderar

---

### POST /reviews/{id}/replies

Crear una respuesta a una reseña.

**Autenticación**: Requerida

**Permisos**: `reviews.replies.create`

**Request**:

```bash
POST /api/reviews/1/replies \
  -H "Content-Type: application/json" \
  -d '{
    "reply_text": "Gracias por tu reseña, Juan. Esperamos verte pronto.",
    "template_id": 3
  }'
```

**Body**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| reply_text | string | Sí (si no template_id) | Texto de respuesta |
| template_id | integer | No | ID de template (optional) |

**Response (201)**:

```json
{
  "success": true,
  "message": "Respuesta creada",
  "data": {
    "id": 1,
    "review_id": 1,
    "reply_text": "Gracias por tu reseña, Juan...",
    "status": "draft",
    "created_by_id": 2,
    "approved_by_id": null,
    "created_at": "2026-02-20T15:30:00Z",
    "updated_at": "2026-02-20T15:30:00Z"
  }
}
```

---

### PATCH /reviews/{id}/replies/{reply_id}

Actualizar una respuesta (solo en estado draft).

**Autenticación**: Requerida

**Permisos**: `reviews.replies.edit`

**Request**:

```bash
PATCH /api/reviews/1/replies/1 \
  -H "Content-Type: application/json" \
  -d '{
    "reply_text": "Texto actualizado"
  }'
```

**Response (200)**:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "reply_text": "Texto actualizado",
    "status": "draft",
    "updated_at": "2026-02-20T15:45:00Z"
  }
}
```

---

### POST /reviews/{id}/replies/{reply_id}/approve

Aprobar una respuesta para publicación.

**Autenticación**: Requerida

**Permisos**: `reviews.replies.approve`

**Request**:

```bash
POST /api/reviews/1/replies/1/approve
```

**Response (200)**:

```json
{
  "success": true,
  "message": "Respuesta aprobada",
  "data": {
    "id": 1,
    "status": "approved",
    "approved_by_id": 1,
    "approved_at": "2026-02-20T15:50:00Z"
  }
}
```

---

### POST /reviews/{id}/replies/{reply_id}/publish

Publicar una respuesta aprobada a Google.

**Autenticación**: Requerida

**Permisos**: `reviews.replies.publish`

**Request**:

```bash
POST /api/reviews/1/replies/1/publish
```

**Response (200)**:

```json
{
  "success": true,
  "message": "Respuesta publicada en Google",
  "data": {
    "id": 1,
    "status": "published",
    "published_at": "2026-02-20T15:55:00Z"
  }
}
```

**Errores**:

- `409 Conflict`: Respuesta no está en estado approved
- `422`: Validación fallida (falta texto, etc)

---

### DELETE /reviews/{id}/replies/{reply_id}

Eliminar una respuesta.

**Autenticación**: Requerida

**Permisos**: `reviews.replies.delete`

**Request**:

```bash
DELETE /api/reviews/1/replies/1
```

**Response (204)**:

```
No Content
```

---

### POST /reviews/export

Exportar reseñas de forma asincrónica.

**Descripción**: Inicia un proceso de exportación asincrónica. La exportación se procesa en background y se notifica al usuario cuando está lista.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.export`

**Request**:

```bash
POST /api/reviews/export \
  -H "Content-Type: application/json" \
  -d '{
    "location_id": 1,
    "rating": 5,
    "date_from": "2026-01-01",
    "date_to": "2026-02-20",
    "has_comment": true,
    "has_reply": false,
    "is_visible": true,
    "format": "csv"
  }'
```

**Body Parameters**:

| Parámetro | Tipo | Requerido | Descripción | Valores |
|-----------|------|-----------|-------------|--------|
| location_id | integer | No | Filtrar por ubicación | ID de ubicación |
| rating | integer | No | Filtrar por calificación | 1-5 |
| has_comment | boolean | No | Solo reseñas con comentario | true/false |
| has_reply | boolean | No | Solo reseñas respondidas | true/false |
| is_visible | boolean | No | Solo reseñas visibles | true/false |
| date_from | date | No | Fecha mínima | Y-m-d |
| date_to | date | No | Fecha máxima | Y-m-d |
| format | string | No | Formato de exportación | csv (por defecto) |

**Response (202 Accepted)**:

```json
{
  "success": true,
  "message": "La exportación se está procesando. Recibirás una notificación cuando esté lista.",
  "export_id": "export_abc123def456"
}
```

**Flujo Asincrónico**:

1. Usuario solicita exportación (POST)
2. Sistema devuelve 202 Accepted inmediatamente
3. Job `ExportReviewsJob` se ejecuta en background
4. Archivo CSV se genera en `storage/app/exports/`
5. Usuario recibe notificación (email o dashboard) cuando esté listo
6. Usuario descarga archivo

**Ventajas**:

- No bloquea la interfaz de usuario
- Permite exportaciones grandes sin timeout
- Procesamiento en background
- Notificación automática

---

### GET /reviews/export/{export_id}

Verificar estado de una exportación asincrónica.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.export`

**Request**:

```bash
GET /api/reviews/export/export_abc123def456
```

**Response (200)**:

```json
{
  "success": true,
  "export_id": "export_abc123def456",
  "status": "completed",
  "progress": 100,
  "total_reviews": 150,
  "processed_reviews": 150,
  "file_path": "exports/reviews-2026-02-20-abc123.csv",
  "file_size": 25600,
  "created_at": "2026-02-20T10:00:00Z",
  "completed_at": "2026-02-20T10:05:00Z",
  "download_url": "/reviews/export/download/reviews-2026-02-20-abc123.csv"
}
```

**Posibles Estados**:

| Estado | Descripción | Progress |
|--------|-------------|----------|
| pending | Esperando a procesarse | 0 |
| processing | En proceso | 1-99 |
| completed | Listo para descargar | 100 |
| failed | Error durante procesamiento | 0 |

**Response si falló (200)**:

```json
{
  "success": false,
  "export_id": "export_abc123def456",
  "status": "failed",
  "error_message": "No hay reseñas que exportar con los filtros aplicados",
  "created_at": "2026-02-20T10:00:00Z"
}
```

---

### GET /reviews/export/download/{filename}

Descargar archivo de exportación completado.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.export`

**Request**:

```bash
GET /api/reviews/export/download/reviews-2026-02-20-abc123.csv
```

**Response (200)**:

```
Content-Type: text/csv; charset=utf-8
Content-Disposition: attachment; filename="reviews-2026-02-20-abc123.csv"

id,reviewer_name,star_rating,comment,review_time,has_reply,is_visible,location_name
1,Juan Pérez,5,Excelente servicio,2026-02-20T10:30:00Z,true,true,Oficina Principal
2,María García,4,Buena atención,2026-02-19T14:20:00Z,false,true,Sucursal Centro
3,Carlos López,5,Muy recomendado,2026-02-18T09:15:00Z,true,true,Oficina Principal
```

**Columnas del CSV**:

| Columna | Descripción |
|---------|-------------|
| id | ID interno de la reseña |
| reviewer_name | Nombre del revisor |
| star_rating | Calificación (1-5) |
| comment | Comentario de la reseña |
| review_time | Fecha de la reseña |
| has_reply | Si tiene respuesta (true/false) |
| is_visible | Si es visible (true/false) |
| location_name | Nombre de la ubicación |

**Notas**:

- Archivo se elimina automáticamente 24 horas después de ser generado
- Si archivo expiró, obtener error 404
- Descargas múltiples están permitidas

---

### GET /reviews/{id}/suggestions

Obtener sugerencias de templates para responder una reseña.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.view`

**Descripción**: Retorna templates recomendados basados en el rating y contenido de la reseña.

**Request**:

```bash
GET /api/reviews/1/suggestions
```

**Response (200)**:

```json
{
  "success": true,
  "message": "Reply suggestions generated successfully",
  "data": {
    "review_id": 1,
    "star_rating": 5,
    "has_comment": true,
    "suggestions": [
    {
      "id": 3,
      "name": "Agradecimiento Positivo",
      "body": "Gracias {{reviewer_name}} por tu reseña de {{star_rating}} estrellas. Nos alegra haber podido servirte.",
      "category": "positive",
      "relevance_score": 95,
      "matched_keywords": ["gracias", "positivo"]
    },
    {
      "id": 5,
      "name": "Invitación a Volver",
      "body": "¡Gracias por visitarnos {{reviewer_name}}! Esperamos verte pronto.",
      "category": "positive",
      "relevance_score": 87,
      "matched_keywords": ["visitarnos"]
    }
    ]
  }
}
```

**Variables Disponibles en Templates**:

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `{{reviewer_name}}` | Nombre del revisor | "Juan Pérez" |
| `{{star_rating}}` | Calificación numérica | "5" |
| `{{location_name}}` | Nombre de la ubicación | "Oficina Principal" |

---

## Rate Limiting

Todos los endpoints están protegidos contra abuso con throttling.

### Límites Actuales

| Tipo | Límite | Ventana |
|------|--------|---------|
| API Requests | 60 | 1 minuto |
| Export Jobs | 5 | 1 hora |
| Sync Requests | 10 | 1 hora |

### Rate Limit Headers

Todas las respuestas incluyen headers informando sobre el estado del rate limit:

```
X-RateLimit-Limit: 60        # Límite total de requests
X-RateLimit-Remaining: 58    # Requests restantes en esta ventana
X-RateLimit-Reset: 1645350600  # Timestamp Unix cuando se reinicia el contador
```

### Cuando Se Excede el Límite

Si excedes el límite, recibirás respuesta 429:

```json
{
  "message": "Too Many Requests",
  "status": 429,
  "retry_after": 60
}
```

Con header:
```
Retry-After: 60
```

Esto significa que debes esperar 60 segundos antes de reintentar.

### Recomendaciones

- Implementar backoff exponencial en clientes
- Monitorear header `X-RateLimit-Remaining`
- Cachear resultados localmente para reducir requests
- Para grandes volúmenes, contactar para aumentar límites

---

## Response Headers

### Headers Estándar

Todas las respuestas incluyen:

| Header | Ejemplo | Descripción |
|--------|---------|-------------|
| Content-Type | application/json; charset=utf-8 | Tipo de contenido |
| Cache-Control | no-cache, no-store, must-revalidate | Control de caché |
| X-Content-Type-Options | nosniff | Seguridad contra MIME sniffing |
| X-Frame-Options | DENY | Previene clickjacking |
| X-Request-ID | abc-123-def-456 | ID único de request para debugging |

### Headers de Rate Limiting

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1645350600
```

### Headers de Seguridad

```
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
Access-Control-Allow-Origin: * (en CORS requests)
```

### Headers de Respuesta por Tipo

**200 OK**:
```
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1645350600
```

**202 Accepted (Async Export)**:
```
HTTP/1.1 202 Accepted
Content-Type: application/json; charset=utf-8
Location: /api/reviews/export/export_abc123
X-RateLimit-Remaining: 58
```

**204 No Content (Delete)**:
```
HTTP/1.1 204 No Content
X-RateLimit-Remaining: 58
```

**429 Too Many Requests**:
```
HTTP/1.1 429 Too Many Requests
Retry-After: 60
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1645350660
```

---

## Response Codes

| Código | Significado | Cuándo | Ejemplo |
|--------|-------------|--------|---------|
| 200 | OK | GET exitoso | Ver detalles de reseña |
| 201 | Created | Recurso creado | Crear respuesta |
| 202 | Accepted | Solicitud asincrónica aceptada | Solicitar exportación |
| 204 | No Content | DELETE exitoso | Eliminar respuesta |
| 400 | Bad Request | Parámetro inválido | rating=99 (fuera de 1-5) |
| 401 | Unauthorized | Token faltante/inválido | Sin header Authorization |
| 403 | Forbidden | Sin permiso para recurso | Usuario sin permiso reviews.moderate |
| 404 | Not Found | Recurso no existe | Review ID que no existe |
| 409 | Conflict | Estado inconsistente | Publicar reply no aprobada |
| 422 | Unprocessable Entity | Validación fallida | reply_text vacío |
| 429 | Too Many Requests | Rate limit excedido | 61 requests en 1 minuto |
| 500 | Server Error | Error interno del servidor | Bug en código |
| 503 | Service Unavailable | Servidor no disponible | Mantenimiento |

## Error Responses

### Validación (422)

Cuando los datos enviados no pasan validación:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "reply_text": [
      "The reply text is required.",
      "The reply text must be at least 10 characters."
    ],
    "star_rating": [
      "The star rating must be between 1 and 5."
    ]
  }
}
```

**Cómo manejar**:
- Leer array `errors`
- Mostrar mensajes de error al usuario por campo
- Reintentar con datos válidos

---

### Sin Autenticación (401)

Cuando token no es válido o está expirado:

```json
{
  "message": "Unauthenticated.",
  "status": 401
}
```

**Causas**:
- Header `Authorization` faltante
- Token inválido o expirado
- Token revocado

**Solución**: Obtener nuevo token y reintentar

---

### Sin Permiso (403)

Cuando usuario no tiene permiso para la acción:

```json
{
  "message": "This action is unauthorized.",
  "status": 403
}
```

**Causas**:
- Usuario no tiene permiso requerido
- Intentando acceder recurso de otro usuario
- Rol insuficiente

**Solución**: Contactar administrador para asignar permisos

---

### No Encontrado (404)

Cuando el recurso solicitado no existe:

```json
{
  "message": "No query results for model [Modules\\Reviews\\Models\\Review] 1",
  "status": 404
}
```

**Causas**:
- ID de recurso no existe
- Recurso fue eliminado
- URL incorrecta

**Solución**: Verificar ID y reintentar

---

### Conflicto (409)

Cuando la acción es inconsistente con estado actual:

```json
{
  "message": "Conflict: Reply must be in 'approved' status to publish. Current status: draft",
  "status": 409
}
```

**Causas comunes**:
- Intentar publicar respuesta en estado draft (requiere approved)
- Intentar aprobar respuesta ya aprobada
- Intentar eliminar recurso ya eliminado

**Solución**: Verificar estado actual y seguir flujo correcto

---

### Rate Limit (429)

Cuando se excede límite de requests:

```json
{
  "message": "Too Many Requests",
  "status": 429,
  "retry_after": 60
}
```

Con headers:
```
Retry-After: 60
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1645350660
```

**Qué hacer**:
- Esperar número de segundos en `Retry-After`
- Implementar exponential backoff
- Cachear resultados para reducir requests

---

### Error Interno del Servidor (500)

Cuando ocurre error no previsto:

```json
{
  "message": "Internal Server Error",
  "status": 500,
  "request_id": "abc-123-def-456"
}
```

**Qué hacer**:
- Anotar `request_id` para debugging
- Contactar soporte
- Reintentar después de unos segundos
- Revisar logs del servidor

---

## Debugging y Troubleshooting

### Cómo Leer Respuestas de Error

1. Verificar `status` code (200, 401, 422, etc)
2. Leer campo `message` para descripción
3. Si validación (422), revisar array `errors`
4. Usar `request_id` para reportar bugs
5. Revisar `Retry-After` para rate limits

### Tools Útiles

**cURL con debugging**:
```bash
curl -v https://api.example.com/api/reviews \
  -H "Authorization: Bearer token"
```

El flag `-v` muestra headers de respuesta.

**jq para parsear JSON**:
```bash
curl ... | jq '.errors'
```

**Postman**: Importar OpenAPI spec si está disponible

---

## Ejemplos con cURL

### Listar reseñas de 5 estrellas

```bash
curl -X GET "https://tu-dominio.com/api/reviews?rating=5&per_page=10" \
  -H "Authorization: Bearer token_aqui"
```

### Crear respuesta y publicarla

```bash
# 1. Crear respuesta
curl -X POST "https://tu-dominio.com/api/reviews/1/replies" \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{"reply_text": "Gracias por tu reseña"}'

# Respuesta incluye id: 123

# 2. Aprobar respuesta
curl -X POST "https://tu-dominio.com/api/reviews/1/replies/123/approve" \
  -H "Authorization: Bearer token"

# 3. Publicar a Google
curl -X POST "https://tu-dominio.com/api/reviews/1/replies/123/publish" \
  -H "Authorization: Bearer token"
```

### Exportar reseñas filtradas

```bash
curl -X GET "https://tu-dominio.com/api/reviews/export?location_id=1&date_from=2026-01-01&date_to=2026-02-20" \
  -H "Authorization: Bearer token" \
  --output reviews.csv
```

## Ejemplos con JavaScript/Fetch

### Listar reseñas

```javascript
const token = 'tu-token-aqui';

fetch('https://tu-dominio.com/api/reviews?rating=5', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
  .then(response => response.json())
  .then(data => {
    console.log(data.data);
    data.data.forEach(review => {
      console.log(`${review.reviewer_name}: ${review.star_rating}⭐`);
    });
  });
```

### Crear y publicar respuesta

```javascript
async function replyToReview(reviewId, replyText) {
  const token = 'tu-token-aqui';

  // 1. Crear respuesta
  const createReply = await fetch(
    `https://tu-dominio.com/api/reviews/${reviewId}/replies`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ reply_text: replyText })
    }
  );

  const { data: reply } = await createReply.json();

  // 2. Aprobar
  await fetch(
    `https://tu-dominio.com/api/reviews/${reviewId}/replies/${reply.id}/approve`,
    {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` }
    }
  );

  // 3. Publicar
  await fetch(
    `https://tu-dominio.com/api/reviews/${reviewId}/replies/${reply.id}/publish`,
    {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` }
    }
  );

  console.log('Respuesta publicada');
}
```

## Webhooks (Futuro)

Se planea implementar webhooks para eventos. Manténerse atento a futuras versiones.

## Rate Limiting Headers

Todas las respuestas incluyen headers de rate limiting:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1645345200
```

## Casos de Uso Comunes

### Caso 1: Obtener reseñas 5 estrellas de los últimos 30 días

```bash
curl -X GET "https://tu-dominio.com/api/reviews?rating=5&days=30&per_page=20" \
  -H "Authorization: Bearer token"
```

**Usa para**: Dashboard, reportes, análisis de sentimiento positivo

---

### Caso 2: Encontrar reseñas sin respuesta para responder

```bash
curl -X GET "https://tu-dominio.com/api/reviews?has_reply=false&per_page=10" \
  -H "Authorization: Bearer token"
```

Luego, para cada reseña:

```bash
curl -X GET "https://tu-dominio.com/api/reviews/{id}/suggestions" \
  -H "Authorization: Bearer token"
```

**Usa para**: Cola de respuestas pendientes

---

### Caso 3: Obtener estadísticas para dashboard

```bash
curl -X GET "https://tu-dominio.com/api/reviews/stats?location_id=1&days=90" \
  -H "Authorization: Bearer token" | jq '.'
```

Respuesta contiene:
- Total de reseñas
- Rating promedio
- Distribución por calificación
- Tendencias mes a mes

**Usa para**: KPIs, dashboards, reportes ejecutivos

---

### Caso 4: Flujo completo de respuesta a reseña

**Paso 1**: Crear respuesta en estado draft

```bash
curl -X POST "https://tu-dominio.com/api/reviews/1/replies" \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{"reply_text": "Gracias por tu reseña, Juan"}'

# Respuesta: { "data": { "id": 123, "status": "draft" } }
```

**Paso 2**: Aprobar respuesta

```bash
curl -X POST "https://tu-dominio.com/api/reviews/1/replies/123/approve" \
  -H "Authorization: Bearer token"

# Respuesta: { "status": "approved" }
```

**Paso 3**: Publicar a Google

```bash
curl -X POST "https://tu-dominio.com/api/reviews/1/replies/123/publish" \
  -H "Authorization: Bearer token"

# Respuesta: { "status": "published" }
```

**Usa para**: Automation, chatbots, sistemas de respuesta automática

---

### Caso 5: Exportar reseñas para análisis externo

**Paso 1**: Solicitar exportación

```bash
curl -X POST "https://tu-dominio.com/api/reviews/export" \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{
    "location_id": 1,
    "date_from": "2026-01-01",
    "format": "csv"
  }'

# Respuesta: { "export_id": "export_abc123" }
```

**Paso 2**: Verificar estado (pooling cada 5 segundos)

```bash
curl -X GET "https://tu-dominio.com/api/reviews/export/export_abc123" \
  -H "Authorization: Bearer token"

# Respuesta: { "status": "processing", "progress": 45 }
```

**Paso 3**: Descargar cuando esté completado

```bash
curl -X GET "https://tu-dominio.com/api/reviews/export/download/reviews-2026-01.csv" \
  -H "Authorization: Bearer token" \
  --output reviews.csv
```

**Usa para**: Análisis en Excel, Business Intelligence, archivos históricos

---

### Caso 6: Integración con CRM externo

Sincronizar reseñas a CRM cada 4 horas:

```javascript
async function syncReviewsToCRM() {
  const token = 'tu-token';

  // Obtener reseñas de últimas 4 horas
  const fourHoursAgo = new Date(Date.now() - 4 * 60 * 60 * 1000)
    .toISOString().split('T')[0];

  const response = await fetch(
    `https://tu-dominio.com/api/reviews?date_from=${fourHoursAgo}`,
    { headers: { 'Authorization': `Bearer ${token}` } }
  );

  const { data: reviews } = await response.json();

  // Enviar a CRM
  for (const review of reviews) {
    await fetch('https://crm.example.com/api/reviews', {
      method: 'POST',
      body: JSON.stringify({
        customer_id: review.reviewer_name,
        rating: review.star_rating,
        comment: review.comment,
        date: review.review_time
      })
    });
  }
}

// Ejecutar cada 4 horas
setInterval(syncReviewsToCRM, 4 * 60 * 60 * 1000);
```

**Usa para**: Sincronización automática, integración con terceros

---

## Ejemplos con JavaScript/Fetch Avanzados

### Ejemplo: Clase de Cliente API

```javascript
class ReviewsAPI {
  constructor(token, baseURL = 'https://tu-dominio.com') {
    this.token = token;
    this.baseURL = `${baseURL}/api`;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    const headers = {
      'Authorization': `Bearer ${this.token}`,
      'Content-Type': 'application/json',
      ...options.headers
    };

    const response = await fetch(url, { ...options, headers });

    // Manejar rate limiting
    if (response.status === 429) {
      const retryAfter = response.headers.get('Retry-After');
      throw new Error(`Rate limited. Retry after ${retryAfter}s`);
    }

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }

    return response.json();
  }

  async getReviews(filters = {}) {
    const query = new URLSearchParams(filters);
    return this.request(`/reviews?${query}`);
  }

  async getReview(id) {
    return this.request(`/reviews/${id}`);
  }

  async getStats(filters = {}) {
    const query = new URLSearchParams(filters);
    return this.request(`/reviews/stats?${query}`);
  }

  async getSuggestions(reviewId) {
    return this.request(`/reviews/${reviewId}/suggestions`);
  }

  async createReply(reviewId, text) {
    return this.request(`/reviews/${reviewId}/replies`, {
      method: 'POST',
      body: JSON.stringify({ reply_text: text })
    });
  }

  async approveReply(reviewId, replyId) {
    return this.request(
      `/reviews/${reviewId}/replies/${replyId}/approve`,
      { method: 'POST' }
    );
  }

  async publishReply(reviewId, replyId) {
    return this.request(
      `/reviews/${reviewId}/replies/${replyId}/publish`,
      { method: 'POST' }
    );
  }

  async requestExport(filters = {}) {
    return this.request('/reviews/export', {
      method: 'POST',
      body: JSON.stringify(filters)
    });
  }

  async getExportStatus(exportId) {
    return this.request(`/reviews/export/${exportId}`);
  }
}

// Uso:
const api = new ReviewsAPI('tu-token-aqui');

// Obtener reseñas 5 estrellas
api.getReviews({ rating: 5 })
  .then(data => console.log(data))
  .catch(err => console.error(err));
```

---

## Changelog

Ver [API_CHANGELOG.md](API_CHANGELOG.md) para cambios de versión.
