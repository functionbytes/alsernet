# Guía de API para Consultar Órdenes y Llenar Documentos

## 📋 Nuevos Endpoints

Se han agregado 2 nuevos endpoints para consultar datos de órdenes y llenar documentos automáticamente:

### 1️⃣ **GET `/api/documents/order-data/{order_id}`**

Obtiene los datos de una orden y su cliente sin llenar el documento.

**Uso:** Consultar datos antes de crear un documento.

#### Request:
```bash
GET /api/documents/order-data/123
```

#### Response Success:
```json
{
    "status": "success",
    "message": "Order data retrieved successfully.",
    "data": {
        "order_id": 123,
        "order_reference": "ABC123",
        "order_total": 99.99,
        "order_date": "2025-11-24 10:30:00",
        "order_cart_id": 456,
        "customer_id": 789,
        "customer_firstname": "Juan",
        "customer_lastname": "Pérez",
        "customer_email": "juan@example.com",
        "customer_dni": "12345678A",
        "customer_company": "Empresa S.L."
    }
}
```

#### Response Error:
```json
{
    "status": "failed",
    "message": "Order not found in Prestashop."
}
```

---

### 2️⃣ **POST `/api/documents/fill-order-data`**

Llena automáticamente los datos desnormalizados de un documento usando los datos de la orden.

**Uso:** Después de crear un documento, rellenarlo con datos de la orden.

#### Request:
```json
{
    "uid": "document-uid-123",
    "order_id": 123
}
```

#### Response Success:
```json
{
    "status": "success",
    "message": "Document filled with order data successfully.",
    "data": {
        "uid": "document-uid-123",
        "order_reference": "ABC123",
        "customer_name": "Juan Pérez",
        "customer_email": "juan@example.com"
    }
}
```

#### Response Error:
```json
{
    "status": "failed",
    "message": "Document not found."
}
```

---

## 🔄 Flujo de Trabajo Recomendado

### Opción 1: Crear y llenar documento (Recomendado)

```
1. Crear documento:
   POST /api/documents/
   {
       "action": "request",
       "order": 123,
       "customer": 789,
       "cart": 456,
       "type": "general"
   }
   → Response: { "uid": "abc-123" }

2. Llenar con datos de orden:
   POST /api/documents/fill-order-data
   {
       "uid": "abc-123",
       "order_id": 123
   }
   → Response: { "status": "success", "data": {...} }

3. Subir documento:
   POST /api/documents/
   {
       "action": "upload",
       "uid": "abc-123",
       "file": <archivo>,
       "source": "api"
   }
   → Response: { "status": "success" }
```

### Opción 2: Verificar datos antes de crear

```
1. Consultar datos de la orden:
   GET /api/documents/order-data/123
   → Response: { "data": {...} }

2. Si los datos están OK, crear documento:
   POST /api/documents/
   {
       "action": "request",
       "order": 123,
       "customer": 789,
       "cart": 456,
       "type": "general"
   }
   → Response: { "uid": "abc-123" }

3. Llenar documento:
   POST /api/documents/fill-order-data
   {
       "uid": "abc-123",
       "order_id": 123
   }
```

---

## 📝 Ejemplos con cURL

### Obtener datos de orden:

```bash
curl -X GET "http://localhost/api/documents/order-data/123" \
  -H "Accept: application/json"
```

### Llenar documento con datos:

```bash
curl -X POST "http://localhost/api/documents/fill-order-data" \
  -H "Content-Type: application/json" \
  -d '{
    "uid": "document-uid-123",
    "order_id": 123
  }'
```

---

## 🔧 Ejemplos con PHP

### Obtener datos de orden:

```php
<?php
$orderId = 123;

$client = new \GuzzleHttp\Client();
$response = $client->get("http://localhost/api/documents/order-data/{$orderId}");

$data = json_decode($response->getBody(), true);

if ($data['status'] === 'success') {
    $orderData = $data['data'];

    echo "Cliente: " . $orderData['customer_firstname'] . " " . $orderData['customer_lastname'];
    echo "Email: " . $orderData['customer_email'];
    echo "Orden: " . $orderData['order_reference'];
}
?>
```

### Llenar documento:

```php
<?php
$client = new \GuzzleHttp\Client();

$response = $client->post("http://localhost/api/documents/fill-order-data", [
    'json' => [
        'uid' => 'document-uid-123',
        'order_id' => 123
    ]
]);

$data = json_decode($response->getBody(), true);

if ($data['status'] === 'success') {
    echo "Documento llenado: " . $data['data']['customer_name'];
}
?>
```

---

## 📊 Datos que se Llenan

Cuando llamas a `/api/documents/fill-order-data`, se llenan automáticamente:

### Datos de la Orden:
- `order_reference` - Referencia de la orden (ej: ABC123)
- `order_id` - ID de la orden en Prestashop
- `order_date` - Fecha de creación de la orden
- `order_total` - Monto total de la orden

### Datos del Cliente:
- `customer_firstname` - Nombre del cliente
- `customer_lastname` - Apellido del cliente
- `customer_email` - Email del cliente
- `customer_dni` - DNI/NIE del cliente
- `customer_company` - Empresa del cliente

---

## ⚡ Ventajas de este Flujo

✅ **Datos actualizados** desde Prestashop en tiempo real
✅ **Sin duplicación** de datos en la aplicación
✅ **Automático** con una sola llamada
✅ **Validación** de orden y cliente
✅ **Sin JOINs costosos** en búsquedas posteriores

---

## 🔍 Casos de Uso

### 1. Cliente sube documento por API
```
POST /api/documents/ → Crear documento
POST /api/documents/fill-order-data → Llenar con datos
POST /api/documents/ → Subir archivo
```

### 2. Consultar si orden existe
```
GET /api/documents/order-data/123 → Verificar existencia
→ Si existe, proceder a crear documento
```

### 3. Actualizar datos de documento existente
```
POST /api/documents/fill-order-data → Actualizar datos
→ Sobrescribe datos desnormalizados con los actuales
```

---

## 📋 Validaciones

### getOrderData():
- `order_id` requerido (integer)
- Orden debe existir en Prestashop
- Cliente asociado debe existir

### fillDocumentWithOrderData():
- `uid` requerido (string)
- `order_id` requerido (integer)
- Documento debe existir
- Orden debe existir en Prestashop
- Cliente asociado debe existir

---

## ✅ Diferencias

### Antes (Sin funciones):
```php
// Necesitabas hacer JOINs costosos
$doc = Document::with('order', 'customer')->find($id);
$doc->customer_firstname = $doc->customer->firstname; // Manual
```

### Después (Con funciones):
```php
// Automático en una llamada
POST /api/documents/fill-order-data
{
    "uid": "doc-123",
    "order_id": 123
}
// ✅ Todos los datos se llenan automáticamente
```

---

## 🚀 Integración Recomendada

En tu flujo de crear documento:

```php
// 1. Crear documento
$document = Document::create([
    'order_id' => $request->order_id,
    'customer_id' => $request->customer_id,
    'cart_id' => $request->cart_id,
    'type' => $request->type,
]);

// 2. Llenar con datos de orden (automático)
$order = Order::find($request->order_id);
$customer = $order->customer;

$document->update([
    'order_reference' => $order->reference,
    'order_date' => $order->date_add,
    'order_total' => $order->total_paid,
    'customer_firstname' => $customer->firstname,
    'customer_lastname' => $customer->lastname,
    'customer_email' => $customer->email,
    'customer_dni' => $customer->siret,
    'customer_company' => $customer->company,
]);

return response()->json(['uid' => $document->uid], 200);
```

O más simple, usando el endpoint:

```php
// 1. Crear
POST /api/documents/ → { "uid": "abc" }

// 2. Llenar (automático)
POST /api/documents/fill-order-data → { "uid": "abc", "order_id": 123 }

// ✅ Listo!
```

---

## 📞 Resumen de Funciones

| Función | Método | Parámetros | Retorna |
|---------|--------|-----------|---------|
| `getOrderData()` | GET | order_id | Datos de orden y cliente |
| `fillDocumentWithOrderData()` | POST | uid, order_id | Confirmación + datos llenados |

---

¡Listo para usar! 🚀