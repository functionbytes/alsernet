# API Endpoints - Módulo Documents

**Fecha:** 2025-12-28
**Versión:** 1.0
**Base URL:** `/api/documents`
**Autenticación:** JWT Bearer Token

---

## 📋 Índice

- [Autenticación](#autenticación)
- [Documentos](#documentos)
- [Tipos de Documentos](#tipos-de-documentos)
- [Estados](#estados)
- [Validación](#validación)
- [Códigos de Respuesta](#códigos-de-respuesta)
- [Ejemplos](#ejemplos)

---

## 🔐 Autenticación

Todos los endpoints requieren autenticación mediante JWT token.

### Headers Requeridos

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Obtener Token

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Respuesta:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

## 📄 Documentos

### Listar Documentos

```http
GET /api/documents
```

**Query Parameters:**
- `page` (int) - Número de página (default: 1)
- `per_page` (int) - Items por página (default: 15, max: 100)
- `status` (string) - Filtrar por estado
- `type` (string) - Filtrar por tipo
- `search` (string) - Búsqueda por nombre o referencia
- `sort` (string) - Campo para ordenar (default: created_at)
- `order` (string) - Dirección de orden: asc|desc (default: desc)

**Respuesta 200:**
```json
{
  "data": [
    {
      "id": "01HQRS...",
      "reference": "DOC-2025-001",
      "name": "Factura Proveedor",
      "type": {
        "id": "01HQRS...",
        "name": "Factura",
        "key": "invoice"
      },
      "status": {
        "id": "01HQRS...",
        "name": "Pendiente",
        "key": "pending",
        "color": "#FEC90F"
      },
      "file_path": "/documents/2025/12/doc-001.pdf",
      "file_size": 245680,
      "uploaded_at": "2025-12-28T10:30:00Z",
      "created_at": "2025-12-28T10:30:00Z",
      "updated_at": "2025-12-28T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 67
  },
  "links": {
    "first": "/api/documents?page=1",
    "last": "/api/documents?page=5",
    "prev": null,
    "next": "/api/documents?page=2"
  }
}
```

---

### Obtener Documento

```http
GET /api/documents/{id}
```

**Path Parameters:**
- `id` (string, required) - ULID del documento

**Respuesta 200:**
```json
{
  "data": {
    "id": "01HQRS...",
    "reference": "DOC-2025-001",
    "name": "Factura Proveedor",
    "description": "Factura del proveedor ABC por servicios",
    "type": {
      "id": "01HQRS...",
      "name": "Factura",
      "key": "invoice"
    },
    "status": {
      "id": "01HQRS...",
      "name": "Pendiente",
      "key": "pending",
      "color": "#FEC90F"
    },
    "file_path": "/documents/2025/12/doc-001.pdf",
    "file_name": "factura-abc-diciembre.pdf",
    "file_size": 245680,
    "mime_type": "application/pdf",
    "uploaded_by": {
      "id": "01HQRS...",
      "name": "Juan Pérez",
      "email": "juan@example.com"
    },
    "assigned_to": {
      "id": "01HQRS...",
      "name": "María García",
      "email": "maria@example.com"
    },
    "metadata": {
      "invoice_number": "INV-2025-123",
      "amount": 1500.00,
      "currency": "EUR"
    },
    "validation_history": [
      {
        "stage": "Revisión Inicial",
        "action": "approved",
        "user": "María García",
        "notes": "Todo correcto",
        "created_at": "2025-12-28T11:00:00Z"
      }
    ],
    "uploaded_at": "2025-12-28T10:30:00Z",
    "created_at": "2025-12-28T10:30:00Z",
    "updated_at": "2025-12-28T11:00:00Z"
  }
}
```

---

### Crear Documento

```http
POST /api/documents
Content-Type: multipart/form-data
```

**Form Data:**
- `file` (file, required) - Archivo del documento
- `name` (string, required) - Nombre del documento
- `type_id` (string, required) - ULID del tipo de documento
- `description` (string, optional) - Descripción
- `metadata` (json, optional) - Metadatos adicionales
- `assigned_to` (string, optional) - ULID del usuario asignado

**Respuesta 201:**
```json
{
  "data": {
    "id": "01HQRS...",
    "reference": "DOC-2025-002",
    "name": "Albarán Entrega",
    "type": {
      "id": "01HQRS...",
      "name": "Albarán"
    },
    "status": {
      "id": "01HQRS...",
      "name": "Pendiente",
      "key": "pending"
    },
    "file_path": "/documents/2025/12/doc-002.pdf",
    "created_at": "2025-12-28T12:00:00Z"
  },
  "message": "Documento creado exitosamente"
}
```

**Errores Comunes:**
- 422: Validación fallida
- 413: Archivo muy grande
- 415: Tipo de archivo no soportado

---

### Actualizar Documento

```http
PUT /api/documents/{id}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Nuevo nombre del documento",
  "description": "Nueva descripción",
  "assigned_to": "01HQRS...",
  "metadata": {
    "custom_field": "value"
  }
}
```

**Respuesta 200:**
```json
{
  "data": {
    "id": "01HQRS...",
    "name": "Nuevo nombre del documento",
    "updated_at": "2025-12-28T12:30:00Z"
  },
  "message": "Documento actualizado exitosamente"
}
```

---

### Eliminar Documento

```http
DELETE /api/documents/{id}
```

**Respuesta 200:**
```json
{
  "message": "Documento eliminado exitosamente"
}
```

**Errores:**
- 403: Sin permisos para eliminar
- 404: Documento no encontrado
- 409: No se puede eliminar (referencias existentes)

---

### Descargar Documento

```http
GET /api/documents/{id}/download
```

**Respuesta 200:**
- Content-Type: application/pdf (o el tipo correspondiente)
- Content-Disposition: attachment; filename="documento.pdf"
- Binary file data

---

## 📋 Tipos de Documentos

### Listar Tipos

```http
GET /api/documents/types
```

**Respuesta 200:**
```json
{
  "data": [
    {
      "id": "01HQRS...",
      "name": "Factura",
      "key": "invoice",
      "description": "Facturas de proveedores",
      "validation_stages": [
        {
          "order": 1,
          "name": "Revisión Administrativa",
          "required": true
        },
        {
          "order": 2,
          "name": "Aprobación Gerencia",
          "required": true
        }
      ],
      "active": true
    }
  ]
}
```

---

## 🔄 Estados

### Listar Estados

```http
GET /api/documents/statuses
```

**Respuesta 200:**
```json
{
  "data": [
    {
      "id": "01HQRS...",
      "name": "Pendiente",
      "key": "pending",
      "description": "Documento pendiente de revisión",
      "color": "#FEC90F",
      "is_initial": true,
      "is_final": false,
      "order": 1
    },
    {
      "id": "01HQRS...",
      "name": "Aprobado",
      "key": "approved",
      "description": "Documento aprobado",
      "color": "#13C672",
      "is_initial": false,
      "is_final": true,
      "order": 10
    }
  ]
}
```

---

## ✅ Validación

### Aprobar Etapa de Validación

```http
POST /api/documents/{id}/validate/approve
Content-Type: application/json
```

**Request Body:**
```json
{
  "stage": "Revisión Administrativa",
  "notes": "Documento verificado, todo correcto"
}
```

**Respuesta 200:**
```json
{
  "data": {
    "id": "01HQRS...",
    "status": {
      "name": "En Revisión",
      "key": "in_review"
    },
    "current_stage": "Aprobación Gerencia",
    "validation_progress": 50
  },
  "message": "Etapa aprobada exitosamente"
}
```

---

### Rechazar Etapa de Validación

```http
POST /api/documents/{id}/validate/reject
Content-Type: application/json
```

**Request Body:**
```json
{
  "stage": "Revisión Administrativa",
  "reason": "Falta información del proveedor",
  "notes": "Solicitar datos fiscales actualizados"
}
```

**Respuesta 200:**
```json
{
  "data": {
    "id": "01HQRS...",
    "status": {
      "name": "Rechazado",
      "key": "rejected"
    },
    "rejection_reason": "Falta información del proveedor"
  },
  "message": "Documento rechazado"
}
```

---

## 🔔 Códigos de Respuesta

### Códigos HTTP

| Código | Significado | Descripción |
|--------|-------------|-------------|
| 200 | OK | Solicitud exitosa |
| 201 | Created | Recurso creado exitosamente |
| 204 | No Content | Solicitud exitosa sin contenido |
| 400 | Bad Request | Solicitud malformada |
| 401 | Unauthorized | Token inválido o expirado |
| 403 | Forbidden | Sin permisos para esta acción |
| 404 | Not Found | Recurso no encontrado |
| 409 | Conflict | Conflicto con estado actual |
| 413 | Payload Too Large | Archivo muy grande |
| 415 | Unsupported Media Type | Tipo de archivo no soportado |
| 422 | Unprocessable Entity | Validación fallida |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Internal Server Error | Error del servidor |

### Formato de Errores

```json
{
  "message": "Descripción del error",
  "errors": {
    "field_name": [
      "El campo es requerido",
      "El campo debe ser un email válido"
    ]
  }
}
```

---

## 💡 Ejemplos

### Ejemplo Completo: Subir y Validar Documento

#### 1. Autenticarse
```bash
curl -X POST https://api.example.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

**Respuesta:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer"
}
```

#### 2. Subir Documento
```bash
curl -X POST https://api.example.com/api/documents \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -F "file=@factura.pdf" \
  -F "name=Factura Diciembre 2025" \
  -F "type_id=01HQRS..." \
  -F "description=Factura de servicios mensuales"
```

**Respuesta:**
```json
{
  "data": {
    "id": "01HQRS123...",
    "reference": "DOC-2025-003",
    "name": "Factura Diciembre 2025"
  }
}
```

#### 3. Consultar Documento
```bash
curl -X GET https://api.example.com/api/documents/01HQRS123... \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

#### 4. Aprobar Primera Etapa
```bash
curl -X POST https://api.example.com/api/documents/01HQRS123.../validate/approve \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "stage": "Revisión Administrativa",
    "notes": "Documento correcto"
  }'
```

#### 5. Aprobar Segunda Etapa
```bash
curl -X POST https://api.example.com/api/documents/01HQRS123.../validate/approve \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "stage": "Aprobación Gerencia",
    "notes": "Aprobado para pago"
  }'
```

#### 6. Descargar Documento
```bash
curl -X GET https://api.example.com/api/documents/01HQRS123.../download \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -o factura-descargada.pdf
```

---

### Ejemplo con JavaScript (Axios)

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://api.example.com/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// 1. Login
const login = async () => {
  const response = await api.post('/auth/login', {
    email: 'admin@example.com',
    password: 'password'
  });
  return response.data.access_token;
};

// 2. Set token
const token = await login();
api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

// 3. Upload document
const uploadDocument = async (file) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('name', 'Factura Diciembre');
  formData.append('type_id', '01HQRS...');

  const response = await api.post('/documents', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });

  return response.data.data;
};

// 4. Get documents list
const getDocuments = async (filters = {}) => {
  const response = await api.get('/documents', { params: filters });
  return response.data;
};

// 5. Approve validation stage
const approveStage = async (documentId, stage, notes) => {
  const response = await api.post(`/documents/${documentId}/validate/approve`, {
    stage,
    notes
  });
  return response.data;
};
```

---

### Ejemplo con PHP (Laravel HTTP Client)

```php
use Illuminate\Support\Facades\Http;

// 1. Login
$response = Http::post('https://api.example.com/api/auth/login', [
    'email' => 'admin@example.com',
    'password' => 'password'
]);

$token = $response->json('access_token');

// 2. Create authenticated client
$client = Http::withToken($token)
    ->acceptJson();

// 3. Upload document
$response = $client->attach(
    'file', file_get_contents('factura.pdf'), 'factura.pdf'
)->post('https://api.example.com/api/documents', [
    'name' => 'Factura Diciembre',
    'type_id' => '01HQRS...',
    'description' => 'Factura mensual'
]);

$document = $response->json('data');

// 4. Get documents
$documents = $client->get('https://api.example.com/api/documents', [
    'status' => 'pending',
    'per_page' => 20
])->json();

// 5. Approve stage
$client->post("https://api.example.com/api/documents/{$document['id']}/validate/approve", [
    'stage' => 'Revisión Administrativa',
    'notes' => 'Aprobado'
]);
```

---

## 📚 Documentación Relacionada

- [Guía de Testing](./testing.md) - Tests de API
- [Autenticación JWT](./authentication.md) - Detalles de autenticación
- [Sistema de Permisos](../architecture/permissions.md) - Control de acceso
- [Workflow de Validación](../architecture/workflow.md) - Flujo de validación

---

## 🔄 Rate Limiting

La API implementa rate limiting para prevenir abuso:

- **Autenticados**: 60 requests/minuto
- **No autenticados**: 10 requests/minuto

Headers de respuesta:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1640995200
```

Cuando se excede el límite:
```json
{
  "message": "Too Many Attempts.",
  "retry_after": 42
}
```

---

## 📝 Notas

- Todos los IDs son ULIDs (Universal Lexicographically Sortable Identifiers)
- Las fechas están en formato ISO 8601 (UTC)
- Los archivos tienen límite de 10MB por defecto
- Formatos soportados: PDF, JPG, PNG, DOCX, XLSX

---

**Última actualización:** 2025-12-28
**Versión de API:** 1.0
