# ERP Endpoints & Credentials API Documentation

## Overview

The ERP API provides RESTful endpoints for managing ERP service endpoints and their authentication credentials. All endpoints require authentication via Sanctum.

**Base URL:** `/api/erp/v2`
**Authentication:** Bearer token (Sanctum)

---

## Endpoints Management

### List All Endpoints

```
GET /endpoints
```

**Parameters:**
- `is_active` (boolean, optional) - Filter by active status
- `search` (string, optional) - Search by name, slug, or URL
- `per_page` (integer, optional) - Items per page (default: 15)
- `page` (integer, optional) - Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Stripe API",
      "slug": "stripe-api",
      "url": "https://api.stripe.com/v1",
      "full_url": "https://api.stripe.com/v1",
      "method": "POST",
      "description": "Payment processing",
      "is_active": true,
      "timeout": 30,
      "retry_attempts": 3,
      "content_type": "application/json",
      "rate_limit": null,
      "headers": {"Authorization": "Bearer ..."},
      "query_params": null,
      "credential": {...},
      "credentials_count": 2,
      "logs_count": 42,
      "created_at": "2026-02-24T10:30:00Z",
      "updated_at": "2026-02-24T10:30:00Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### Create Endpoint

```
POST /endpoints
```

**Request Body:**
```json
{
  "name": "Payment Gateway",
  "slug": "payment-gateway",
  "url": "https://api.payment.com/v1",
  "method": "POST",
  "description": "Process payments",
  "timeout": 30,
  "retry_attempts": 3,
  "content_type": "application/json",
  "rate_limit": 100,
  "headers": {
    "X-Custom-Header": "value"
  },
  "query_params": {
    "api_version": "v1"
  },
  "is_active": true
}
```

**Response (201):**
```json
{
  "message": "Endpoint creado correctamente",
  "data": {
    "id": 1,
    "name": "Payment Gateway",
    ...
  }
}
```

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid",
  "errors": {
    "url": ["The url must be a valid URL"],
    "method": ["The method must be GET, POST, PUT, PATCH, or DELETE"]
  }
}
```

---

### Get Endpoint

```
GET /endpoints/{id}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Stripe API",
    ...
  },
  "statistics": {
    "success_rate": 98.5,
    "average_execution_time": 245,
    "last_execution": {
      "id": 42,
      "status_code": 200,
      "success": true,
      "execution_time": 180,
      "created_at": "2026-02-24T15:22:00Z"
    },
    "total_calls": 100
  }
}
```

---

### Update Endpoint

```
PUT /endpoints/{id}
```

**Request Body:** Same as Create

**Response (200):**
```json
{
  "message": "Endpoint actualizado correctamente",
  "data": {...}
}
```

---

### Delete Endpoint

```
DELETE /endpoints/{id}
```

**Response (200):**
```json
{
  "message": "Endpoint eliminado correctamente"
}
```

---

### Toggle Endpoint Active Status

```
POST /endpoints/{id}/toggle
```

**Response (200):**
```json
{
  "message": "Estado actualizado correctamente",
  "data": {
    "id": 1,
    "is_active": false
  }
}
```

---

### Test Endpoint Connection

```
POST /endpoints/{id}/test
```

**Response (200 - Success):**
```json
{
  "success": true,
  "status_code": 200,
  "execution_time": 245,
  "response": {
    "object": "customer",
    "id": "cus_12345"
  }
}
```

**Response (500 - Failure):**
```json
{
  "success": false,
  "error": "Connection timeout",
  "execution_time": 5000
}
```

---

### Get Endpoint Logs

```
GET /endpoints/{id}/logs
```

**Parameters:**
- `per_page` (integer, optional) - Items per page (default: 20)
- `page` (integer, optional) - Page number

**Response (200):**
```json
{
  "endpoint": {
    "id": 1,
    "name": "Stripe API"
  },
  "data": [
    {
      "id": 42,
      "endpoint_id": 1,
      "user_id": 5,
      "method": "POST",
      "url": "https://api.stripe.com/v1/charges",
      "status_code": 200,
      "execution_time": 245,
      "success": true,
      "error_message": null,
      "ip_address": "192.168.1.1",
      "created_at": "2026-02-24T15:22:00Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### Clear Endpoint Logs

```
DELETE /endpoints/{id}/logs
```

**Response (200):**
```json
{
  "message": "42 logs eliminados correctamente",
  "deleted_count": 42
}
```

---

### Get Endpoint Statistics

```
GET /endpoints/{id}/statistics
```

**Response (200):**
```json
{
  "endpoint": {
    "id": 1,
    "name": "Stripe API",
    "url": "https://api.stripe.com",
    "method": "POST",
    "is_active": true
  },
  "statistics": {
    "total_calls": 250,
    "success_rate": 96.8,
    "average_execution_time": 312,
    "last_execution": {...},
    "success_count": 242,
    "failed_count": 8,
    "average_response_time_by_status": {
      "1": 295,
      "0": 580
    }
  }
}
```

---

## Credentials Management

### List Credentials for Endpoint

```
GET /endpoints/{endpoint_id}/credentials
```

**Response (200):**
```json
{
  "endpoint": {
    "id": 1,
    "name": "Stripe API",
    "slug": "stripe-api",
    "url": "https://api.stripe.com/v1"
  },
  "data": [
    {
      "id": 1,
      "name": "Production Key",
      "description": "Live Stripe API key",
      "auth_type": "bearer",
      "api_key_header": null,
      "custom_headers": null,
      "is_active": true,
      "is_expired": false,
      "expires_at": null,
      "last_used_at": "2026-02-24T14:30:00Z",
      "created_at": "2026-02-20T10:00:00Z"
    }
  ]
}
```

---

### Create Credential

```
POST /endpoints/{endpoint_id}/credentials
```

**For Basic Auth:**
```json
{
  "auth_type": "basic",
  "name": "Admin Credentials",
  "description": "Admin user for API",
  "username": "admin@example.com",
  "password": "secure_password",
  "is_active": true
}
```

**For Bearer Token:**
```json
{
  "auth_type": "bearer",
  "name": "API Token",
  "description": "Bearer token for authentication",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_at": "2026-12-31",
  "is_active": true
}
```

**For API Key:**
```json
{
  "auth_type": "api_key",
  "name": "Stripe Key",
  "description": "Stripe API key",
  "api_key": "sk_test_51234567890",
  "api_key_header": "X-API-Key",
  "is_active": true
}
```

**For Custom Headers:**
```json
{
  "auth_type": "custom",
  "name": "Custom Auth",
  "custom_headers": {
    "Authorization": "Custom custom-token",
    "X-API-Version": "v2"
  },
  "is_active": true
}
```

**For No Auth:**
```json
{
  "auth_type": "none",
  "name": "Public Endpoint",
  "is_active": true
}
```

**Response (201):**
```json
{
  "message": "Credencial creada correctamente",
  "data": {
    "id": 1,
    "auth_type": "bearer",
    "is_active": true,
    ...
  }
}
```

---

### Get Credential

```
GET /endpoints/{endpoint_id}/credentials/{credential_id}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "API Key",
    "description": "Main API key",
    "auth_type": "api_key",
    "is_active": true,
    "is_expired": false,
    "last_used_at": "2026-02-24T14:30:00Z"
  }
}
```

---

### Update Credential

```
PUT /endpoints/{endpoint_id}/credentials/{credential_id}
```

**Request Body:** Same fields as Create (all optional)

**Note:** Sensitive fields (`password`, `token`, `api_key`) are only updated if provided in the request.

**Response (200):**
```json
{
  "message": "Credencial actualizada correctamente",
  "data": {...}
}
```

---

### Delete Credential

```
DELETE /endpoints/{endpoint_id}/credentials/{credential_id}
```

**Response (200):**
```json
{
  "message": "Credencial eliminada correctamente"
}
```

---

### Toggle Credential Active Status

```
POST /endpoints/{endpoint_id}/credentials/{credential_id}/toggle
```

**Note:** Activating a credential automatically deactivates all other credentials for the same endpoint.

**Response (200):**
```json
{
  "message": "Estado actualizado correctamente",
  "data": {
    "id": 1,
    "is_active": true
  }
}
```

---

### Rotate Credential

```
POST /endpoints/{endpoint_id}/credentials/{credential_id}/rotate
```

**Description:** Creates a new credential by duplicating the current one. The new credential is inactive and ready for configuration.

**Response (201):**
```json
{
  "message": "Nueva credencial creada. Actualice los valores y actívela cuando esté lista.",
  "data": {
    "id": 2,
    "auth_type": "bearer",
    "name": "API Token",
    "is_active": false,
    "created_at": "2026-02-24T16:45:00Z"
  }
}
```

---

## Authentication

All API endpoints require a valid Sanctum token. Include the token in the `Authorization` header:

```
Authorization: Bearer {token}
```

To obtain a token:
1. Create a user account
2. Call the login endpoint to get a token
3. Include the token in subsequent API requests

---

## Error Responses

### Unauthorized (401)
```json
{
  "message": "Unauthenticated"
}
```

### Unprocessable Entity (422)
```json
{
  "message": "The given data was invalid",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### Not Found (404)
```json
{
  "message": "Not found"
}
```

### Server Error (500)
```json
{
  "message": "Server error",
  "error": "Detailed error message"
}
```

---

## Rate Limiting

API endpoints are rate-limited to prevent abuse:
- Standard endpoints: 60 requests per minute
- Broadcast/broadcast authentication: 60 requests per minute

---

## Best Practices

1. **Always use HTTPS** - Credentials are transmitted in the request body
2. **Rotate credentials regularly** - Use the rotate endpoint to create new credentials
3. **Monitor execution times** - Use statistics endpoint to identify slow endpoints
4. **Handle retries** - Implement exponential backoff for failed requests
5. **Store tokens securely** - Never expose tokens in logs or client-side code

---

## Examples

### List all active endpoints

```bash
curl -X GET "https://api.example.com/api/erp/v2/endpoints?is_active=true" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Create a new endpoint

```bash
curl -X POST "https://api.example.com/api/erp/v2/endpoints" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Payment API",
    "url": "https://api.payment.com/v1",
    "method": "POST",
    "timeout": 30
  }'
```

### Test endpoint connection

```bash
curl -X POST "https://api.example.com/api/erp/v2/endpoints/1/test" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Create bearer token credential

```bash
curl -X POST "https://api.example.com/api/erp/v2/endpoints/1/credentials" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "auth_type": "bearer",
    "token": "your_api_token_here",
    "is_active": true
  }'
```

---

## Testing

Run the API tests:

```bash
docker exec manager-app php artisan test modules/Erp/tests/Feature/Api/
```

---

## Support

For issues or questions about the ERP API, contact the development team.
