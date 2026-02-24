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

- **Límite**: 60 requests por minuto por usuario
- **Header de respuesta**: `X-RateLimit-*`

Si se excede el límite:

```
HTTP 429 Too Many Requests
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
    "total": 150,
    "per_page": 15,
    "current_page": 1,
    "last_page": 10,
    "from": 1,
    "to": 15
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
  "message": "Unauthorized",
  "status": 401
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

### GET /reviews/export

Exportar reseñas a CSV.

**Autenticación**: Requerida

**Permisos**: `reviews.reviews.export`

**Request**:

```bash
GET /api/reviews/export?location_id=1&rating=5&date_from=2026-01-01
```

**Query Parameters**: Iguales que GET /reviews

**Response (200)**:

```
Content-Type: text/csv
Content-Disposition: attachment; filename="reviews-2026-02-20.csv"

id,reviewer_name,star_rating,comment,review_time,has_reply,is_visible
1,Juan Pérez,5,Excelente servicio,2026-02-20T10:30:00Z,true,true
2,María García,4,Buena atención,2026-02-19T14:20:00Z,false,true
```

---

## Response Codes

| Código | Significado | Ejemplo |
|--------|-------------|---------|
| 200 | OK | GET exitoso |
| 201 | Created | Recurso creado |
| 204 | No Content | DELETE exitoso |
| 400 | Bad Request | Parámetro inválido |
| 401 | Unauthorized | Token faltante/inválido |
| 403 | Forbidden | Sin permiso |
| 404 | Not Found | Recurso no existe |
| 422 | Unprocessable Entity | Validación fallida |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Server Error | Error interno |

## Error Responses

### Validación (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "reply_text": [
      "The reply text is required."
    ],
    "star_rating": [
      "The star rating must be between 1 and 5."
    ]
  }
}
```

### Sin Permiso (403)

```json
{
  "message": "This action is unauthorized.",
  "status": 403
}
```

### No Encontrado (404)

```json
{
  "message": "No query results for model [Modules\\Reviews\\Models\\Review] 1",
  "status": 404
}
```

### Rate Limit (429)

```json
{
  "message": "Too Many Requests",
  "status": 429,
  "retry_after": 60
}
```

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

## Changelog

Ver [API_CHANGELOG.md](API_CHANGELOG.md) para cambios de versión.
