# Email Endpoints API - Guía Completa

## 📋 Descripción General

El sistema de **Email Endpoints** permite que aplicaciones externas (PrestaShop, Shopify, etc) envíen solicitudes a tu servidor para disparar envíos de correos automáticos. Los endpoints son totalmente configurables mediante un panel de administración.

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│         APLICACIÓN EXTERNA (PrestaShop, Shopify)            │
│              Envía JSON al endpoint                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ POST /api/email-endpoints/{slug}/send
                     │ Body: { customer_email: "...", ... }
                     │
┌────────────────────▼────────────────────────────────────────┐
│          API EmailEndpointController                        │
│  - Valida token                                             │
│  - Verifica variables requeridas                            │
│  - Crea log de request                                      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ Dispatch SendEndpointEmailJob
                     │
┌────────────────────▼────────────────────────────────────────┐
│        SendEndpointEmailJob (Queue)                         │
│  - Mapea variables JSON → template variables                │
│  - Reemplaza variables en plantilla                         │
│  - Envía correo                                             │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
                  Usuario
```

## 📊 Configuración del Endpoint - Panel Admin

### Paso 1: Crear un Endpoint

1. Ir a `/settings/mailers/endpoints`
2. Click en "Crear Endpoint"
3. Completar formulario:
   - **Nombre**: "PrestaShop Password Reset"
   - **Slug**: `prestashop_password_reset` (único, se usa en URL)
   - **Fuente**: `prestashop`
   - **Tipo**: `password_reset`
   - **Descripción**: Opcional
   - **Plantilla**: Seleccionar la plantilla a usar
   - **Idioma**: Seleccionar idioma por defecto

### Paso 2: Definir Variables Esperadas

En la sección "Variables Esperadas":

```json
[
  "customer_email",
  "customer_name",
  "reset_link",
  "company_name"
]
```

### Paso 3: Marcar Variables Requeridas

Las variables marcadas aquí darán error si no vienen en el JSON:

```json
[
  "customer_email",
  "reset_link"
]
```

### Paso 4: Mapear Variables

Si tu JSON tiene diferentes nombres, mapéalos:

```json
{
  "email": "user.email",
  "name": "user.name",
  "resetUrl": "security.reset_link",
  "business": "merchant.company_name"
}
```

Esto permite:
- `user.email` del JSON → `{email}` en plantilla
- `user.name` del JSON → `{name}` en plantilla
- `security.reset_link` del JSON → `{resetUrl}` en plantilla

### Paso 5: Obtener API Token

Al guardar, se genera automáticamente un **API Token único**.

Puede regenerarse en cualquier momento con el botón "Regenerar Token".

## 🔌 Usar el Endpoint desde PrestaShop

### Ejemplo: PrestaShop envía request de recuperación de contraseña

```php
<?php
// En tu hook/módulo PrestaShop

$endpoint_slug = 'prestashop_password_reset';
$api_url = 'https://tudominio.test/api/email-endpoints/' . $endpoint_slug . '/send';
$api_token = 'abc123xyz789...'; // Token del panel

$customer_data = [
    'customer_email' => 'usuario@example.com',
    'customer_name' => 'Juan Pérez',
    'reset_link' => 'https://tutienda.test/reset-password?token=xyz123',
    'company_name' => 'Mi Tienda'
];

$response = wp_remote_post($api_url, [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
        'X-API-Token' => $api_token  // Token en header
    ],
    'body' => json_encode($customer_data)
]);

if (is_wp_error($response)) {
    error_log('Email endpoint error: ' . $response->get_error_message());
} else {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($body['success']) {
        error_log('Email queued: ' . $body['log_id']);
    } else {
        error_log('Email failed: ' . $body['message']);
    }
}
```

### Usando cURL:

```bash
curl -X POST https://tudominio.test/api/email-endpoints/prestashop_password_reset/send \
  -H "Content-Type: application/json" \
  -H "X-API-Token: abc123xyz789..." \
  -d '{
    "customer_email": "usuario@example.com",
    "customer_name": "Juan Pérez",
    "reset_link": "https://tutienda.test/reset-password?token=xyz123",
    "company_name": "Mi Tienda"
  }'
```

## 🔐 Seguridad

### Validación de Token

El token se envía en el header `X-API-Token`:

```
X-API-Token: tu_token_secreto
```

Si es incorrecto:
```json
{
  "success": false,
  "message": "Invalid API token",
  "status": 401
}
```

### Variables Requeridas

Si faltan variables marcadas como requeridas:

```json
{
  "success": false,
  "message": "Missing required variables: customer_email, reset_link",
  "missing_variables": ["customer_email", "reset_link"],
  "status": 422
}
```

## 📋 Formatos de Respuesta

### Éxito (202 Accepted)

```json
{
  "success": true,
  "message": "Email queued for sending",
  "log_id": 123,
  "endpoint": "prestashop_password_reset"
}
```

### Fallo (400/422/401/404)

```json
{
  "success": false,
  "message": "Descripción del error",
  "status": 400
}
```

## 📊 Endpoints Disponibles

### 1. Enviar Email

```
POST /api/email-endpoints/{slug}/send
Headers: X-API-Token: token
Body: { variables... }
Response: 202 Accepted
```

### 2. Obtener Info del Endpoint

```
GET /api/email-endpoints/{slug}/info
Response: 200 OK
{
  "slug": "prestashop_password_reset",
  "name": "PrestaShop Password Reset",
  "type": "password_reset",
  "expected_variables": ["customer_email", "..."],
  "required_variables": ["customer_email", "..."],
  "template": { "subject": "...", "preview": "..." }
}
```

### 3. Ver Estadísticas

```
GET /api/email-endpoints/{slug}/status
Response: 200 OK
{
  "slug": "prestashop_password_reset",
  "total_requests": 150,
  "successful_emails": 148,
  "failed_emails": 2,
  "success_rate": 98.67,
  "last_request_at": "2025-12-11T15:30:00Z"
}
```

## 🎯 Casos de Uso

### Caso 1: Confirmación de Orden

**Endpoint Slug**: `prestashop_order_confirmation`

**Variables Esperadas**:
```json
[
  "order_id",
  "customer_email",
  "customer_name",
  "order_total",
  "order_items",
  "order_date"
]
```

**Plantilla**:
```html
<p>Hola {customer_name},</p>
<p>Tu pedido #{order_id} de ${order_total} ha sido recibido.</p>
<p>Fecha: {order_date}</p>
<p>Artículos: {order_items}</p>
```

### Caso 2: Rechazo de Pago

**Endpoint Slug**: `prestashop_payment_rejected`

**Variables Esperadas**:
```json
[
  "order_id",
  "customer_email",
  "customer_name",
  "payment_method",
  "rejection_reason"
]
```

### Caso 3: Recuperación de Contraseña

**Endpoint Slug**: `prestashop_password_reset`

**Variables Esperadas**:
```json
[
  "customer_email",
  "customer_name",
  "reset_link",
  "reset_expires_in"
]
```

## 🔍 Panel de Administración

### Ver Logs de Requests

En cada endpoint, puedes ver:
- Fecha y hora del request
- Estado: `pending`, `processing`, `success`, `failed`
- Correo destinatario
- Mensaje de error (si falló)
- Datos enviados (JSON)

### Regenerar Token

Si el token se ha comprometido:
1. Ir a editar el endpoint
2. Click en "Regenerar Token"
3. El nuevo token está listo para usar
4. El token anterior deja de funcionar

## ⚙️ Configuración de Queue

Para que funcione correctamente, asegúrate de tener configurado un driver de queue:

```bash
# En .env
QUEUE_CONNECTION=redis
# o
QUEUE_CONNECTION=database
# o
QUEUE_CONNECTION=sync (para desarrollo)
```

Ejecutar el worker:

```bash
php artisan queue:work
```

## 📚 Base de Datos

### email_endpoints
```
id              - ID único
name            - Nombre amigable
slug            - Identificador único para URL
source          - Fuente (prestashop, shopify, etc)
type            - Tipo de correo
description     - Descripción
email_template_id - ID de plantilla asociada
lang_id         - ID de idioma
expected_variables - JSON array de variables esperadas
required_variables - JSON array de variables obligatorias
variable_mappings - JSON object de mapeos
is_active       - Habilitado/deshabilitado
api_token       - Token de seguridad
requests_count  - Total de requests recibidos
last_request_at - Última solicitud
```

### email_endpoint_logs
```
id              - ID único
email_endpoint_id - FK a endpoints
payload         - JSON de datos recibidos
status          - pending/processing/success/failed
error_message   - Mensaje de error (si aplica)
recipient_email - Correo destinatario
email_subject   - Asunto del correo
sent_at         - Fecha de envío
job_id          - ID del job en queue
```

## 🚀 Próximas Mejoras

- [ ] Webhooks para confirmar envíos
- [ ] Rate limiting por endpoint
- [ ] Reintento automático de correos fallidos
- [ ] Plantillas dinámicas sin mapeos
- [ ] Tests automáticos de endpoints
- [ ] Dashboard con gráficas de éxito

---

**Última actualización**: 2025-12-11
