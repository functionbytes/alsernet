# API v2 - Referencia Completa de Endpoints

## Base URL
```
https://api.example.com/api/erp/v2
```

---

## 1. CLIENTES (Customers)

### Listar Clientes

#### GET - Eloquent (ORM)
```http
GET /eloquent/clientes
```

**Parámetros Query:**
| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `per_page` | int | 50 | Registros por página (máx 500) |
| `page` | int | 1 | Número de página |
| `search` | string | - | Buscar por código o nombre |
| `codigo` | string | - | Filtrar por código exacto |
| `nombre` | string | - | Filtrar por nombre |
| `estado` | int | - | Filtrar por estado (0/1) |
| `sort_by` | string | id | Campo para ordenar |
| `sort_order` | string | asc | Dirección (asc/desc) |

**Ejemplo:**
```bash
curl -X GET "https://api.example.com/api/erp/v2/eloquent/clientes?per_page=20&page=1&search=ABC" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idcliente": 1001,
      "codigo": "CLI001",
      "nombre": "Empresa ABC SL",
      "email": "info@empresaabc.com",
      "telefono": "+34 555 123 456",
      "estado": 1,
      "created_at": "2023-06-15T10:00:00Z",
      "updated_at": "2024-01-12T14:30:00Z"
    }
  ],
  "meta": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8
  }
}
```

#### GET - Direct SQL
```http
GET /direct/clientes
```
**Parámetros:** Idénticos a Eloquent
**Uso:** Para máximo rendimiento con grandes volúmenes

---

### Crear Cliente

#### POST - Eloquent
```http
POST /eloquent/clientes
Content-Type: application/json
Authorization: Bearer TOKEN
```

**Body:**
```json
{
  "nombre": "Nueva Empresa SL",
  "codigo": "CLI002",
  "email": "contacto@nuevaempresa.com",
  "telefono": "+34 555 987 654",
  "direccion": "Calle Principal 123",
  "ciudad": "Barcelona",
  "codigo_postal": "08001",
  "pais": "ES",
  "tipo_cliente": 1,
  "limiteCredito": 50000.00
}
```

**Campos Requeridos:**
- `nombre`
- `email`

**Campos Opcionales:**
- `codigo`
- `telefono`
- `direccion`
- `ciudad`
- `codigo_postal`
- `pais`
- `tipo_cliente`
- `limiteCredito`

**Respuesta (201 Created):**
```json
{
  "success": true,
  "message": "Cliente creado exitosamente",
  "data": {
    "idcliente": 1002,
    "nombre": "Nueva Empresa SL",
    "email": "contacto@nuevaempresa.com",
    "estado": 1,
    "created_at": "2024-01-12T15:45:00Z"
  }
}
```

#### POST - Direct
```http
POST /direct/clientes
```
**Parámetros:** Idénticos a Eloquent

---

### Actualizar LOPD Cliente

#### PUT - Eloquent
```http
PUT /eloquent/clientes
Content-Type: application/json
Authorization: Bearer TOKEN
```

**Body:**
```json
{
  "idcliente": 1001,
  "lopd_consentimiento": 1,
  "lopd_fecha": "2024-01-12",
  "lopd_notificacion": "Consentimiento obtenido vía email"
}
```

**Campos:**
- `idcliente` (requerido)
- `lopd_consentimiento` (0/1)
- `lopd_fecha` (YYYY-MM-DD)
- `lopd_notificacion` (texto)

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "LOPD actualizado",
  "data": {
    "idcliente": 1001,
    "lopd_consentimiento": 1,
    "lopd_fecha": "2024-01-12"
  }
}
```

#### PUT - Direct
```http
PUT /direct/clientes
```

---

## 2. ARTÍCULOS (Products)

### Listar Artículos

#### GET - Eloquent
```http
GET /eloquent/articulos
```

**Parámetros Query:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `per_page` | int | Registros por página (máx 500) |
| `page` | int | Número de página |
| `search` | string | Código, codbar o descripción |
| `codigo` | string | Código exacto |
| `estado` | int | 0=Inactivo, 1=Activo |
| `idmarca` | int | ID de marca |
| `idmodelo` | int | ID de modelo |
| `idsubfamilia` | int | ID de subfamilia |
| `sort_by` | string | idarticulo, codigo, descripcion, preciomedio |
| `sort_order` | string | asc/desc |

**Ejemplo:**
```bash
curl -X GET "https://api.example.com/api/erp/v2/eloquent/articulos?search=ART&estado=1&per_page=50" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idarticulo": 5001,
      "codigo": "ART001",
      "codbar": "8432156789012",
      "descripcion": "Artículo de ejemplo premium",
      "preciomedio": 99.99,
      "precio_anterior": 120.00,
      "estado": 1,
      "idmarca": 10,
      "idmodelo": 25,
      "idsubfamilia": 100,
      "peso": 1.50,
      "volumen": 0.05,
      "unidades_oferta": 1,
      "es_arma": 0,
      "es_cartucho": 0,
      "ean": "8432156789012",
      "created_at": "2023-06-15T08:00:00Z",
      "updated_at": "2024-01-12T14:20:00Z"
    }
  ],
  "meta": {
    "total": 2500,
    "per_page": 50,
    "current_page": 1,
    "last_page": 50
  }
}
```

#### GET - Direct
```http
GET /direct/articulos
```
**Parámetros:** Idénticos a Eloquent
**Nota:** Recomendado para exportaciones masivas

---

## 3. ALBARANES (Delivery Notes)

### Listar Albaranes

#### GET - Eloquent
```http
GET /eloquent/albaranes
```

**Parámetros Query:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `per_page` | int | Registros por página |
| `page` | int | Número de página |
| `idcliente` | int | Filtrar por cliente |
| `estado` | int | Estado del albarán |
| `fecha_desde` | date | Desde fecha (YYYY-MM-DD) |
| `fecha_hasta` | date | Hasta fecha (YYYY-MM-DD) |
| `almacen` | string | Código de almacén |
| `sort_by` | string | idalb, codigo, fecha |
| `sort_order` | string | asc/desc |

**Ejemplo:**
```bash
curl -X GET "https://api.example.com/api/erp/v2/eloquent/albaranes?idcliente=1001&fecha_desde=2024-01-01&per_page=100" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idalb": 9001,
      "codigo": "ALB001",
      "idcliente": 1001,
      "fecha": "2024-01-12",
      "total": 5500.50,
      "total_iva": 1155.11,
      "total_neto": 4345.39,
      "estado": 1,
      "numero_lineas": 15,
      "almacen": "ALM001",
      "created_at": "2024-01-12T10:00:00Z"
    }
  ],
  "meta": {
    "total": 350,
    "per_page": 100,
    "current_page": 1,
    "last_page": 4
  }
}
```

#### GET - Direct
```http
GET /direct/albaranes
```

---

## 4. BONOS (Discounts/Vouchers)

### Listar Bonos

#### GET - Eloquent
```http
GET /eloquent/bonos
```

**Parámetros Query:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `per_page` | int | Registros por página |
| `page` | int | Número de página |
| `idcliente` | int | Filtrar por cliente |
| `estado` | int | Estado del bono |
| `vigente` | boolean | Solo bonos vigentes |
| `sort_by` | string | idbono, codigo, importe |
| `sort_order` | string | asc/desc |

**Ejemplo:**
```bash
curl -X GET "https://api.example.com/api/erp/v2/eloquent/bonos?idcliente=1001&vigente=1" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idbono": 7001,
      "codigo": "BON001",
      "idcliente": 1001,
      "importe": 500.00,
      "importe_gastado": 150.50,
      "importe_disponible": 349.50,
      "fecha_inicio": "2024-01-01",
      "fecha_vencimiento": "2024-12-31",
      "estado": 1,
      "concepto": "Bono de bienvenida 2024",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### GET - Direct
```http
GET /direct/bonos
```

---

## 5. STOCK (Inventory)

### Obtener Stock

#### GET - Eloquent
```http
GET /eloquent/stock
```

**Parámetros Query:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `per_page` | int | Registros por página |
| `page` | int | Número de página |
| `idarticulo` | int | Filtrar por artículo |
| `almacen` | string | Código de almacén |
| `disponible_min` | int | Stock mínimo |
| `sort_by` | string | idarticulo, almacen, cantidad_disponible |
| `sort_order` | string | asc/desc |

**Ejemplo:**
```bash
curl -X GET "https://api.example.com/api/erp/v2/eloquent/stock?almacen=ALM001&disponible_min=10&per_page=200" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idarticulo": 5001,
      "codigo": "ART001",
      "almacen": "ALM001",
      "cantidad_disponible": 150,
      "cantidad_reservada": 25,
      "cantidad_total": 175,
      "cantidad_minima": 50,
      "cantidad_maxima": 500,
      "ultima_entrada": "2024-01-10T12:30:00Z",
      "ultima_salida": "2024-01-12T09:15:00Z",
      "rotacion": "Alta"
    }
  ],
  "meta": {
    "total": 2500,
    "per_page": 200,
    "current_page": 1,
    "last_page": 13
  }
}
```

#### GET - Direct
```http
GET /direct/stock
```
**Recomendado para sincronizaciones masivas**

---

## 6. VALES (Notes/Credits)

### Listar Vales

#### GET - Eloquent
```http
GET /eloquent/vales
```

**Parámetros Query:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `per_page` | int | Registros por página |
| `page` | int | Número de página |
| `idcliente` | int | Filtrar por cliente |
| `estado` | int | Estado del vale |
| `tipo` | string | Tipo de vale |
| `sort_by` | string | idvale, codigo, importe |
| `sort_order` | string | asc/desc |

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idvale": 6001,
      "codigo": "VAL001",
      "idcliente": 1001,
      "importe": 1000.00,
      "tipo": "Crédito",
      "estado": 1,
      "fecha_emision": "2024-01-01",
      "fecha_vencimiento": "2024-12-31",
      "concepto": "Vale de cambio",
      "created_at": "2024-01-01T10:00:00Z"
    }
  ],
  "meta": {
    "total": 80,
    "per_page": 50,
    "current_page": 1,
    "last_page": 2
  }
}
```

#### GET - Direct
```http
GET /direct/vales
```

---

## 7. PEDIDOS (Orders)

### Listar Pedidos

#### GET - Eloquent
```http
GET /eloquent/pedidos
```

**Parámetros Query:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `per_page` | int | Registros por página |
| `page` | int | Número de página |
| `idcliente` | int | Filtrar por cliente |
| `estado` | int | Estado del pedido |
| `fecha_desde` | date | Desde fecha (YYYY-MM-DD) |
| `fecha_hasta` | date | Hasta fecha (YYYY-MM-DD) |
| `sort_by` | string | idpedido, codigo, fecha |
| `sort_order` | string | asc/desc |

**Ejemplo:**
```bash
curl -X GET "https://api.example.com/api/erp/v2/eloquent/pedidos?idcliente=1001&estado=1&per_page=50" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idpedido": 8001,
      "codigo": "PED001",
      "idcliente": 1001,
      "fecha": "2024-01-12",
      "fecha_entrega_solicitada": "2024-01-20",
      "total": 8750.25,
      "total_iva": 1837.55,
      "total_neto": 6912.70,
      "estado": 1,
      "numero_lineas": 22,
      "observaciones": "Entrega en horario laboral",
      "created_at": "2024-01-12T09:30:00Z"
    }
  ],
  "meta": {
    "total": 500,
    "per_page": 50,
    "current_page": 1,
    "last_page": 10
  }
}
```

#### GET - Direct
```http
GET /direct/pedidos
```

---

## 8. CATÁLOGO CLIENTE (Customer Catalog)

### Obtener Catálogo

#### GET - Eloquent
```http
GET /eloquent/catalogo
```

**Parámetros Query:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `idcliente` | int | ID del cliente (requerido) |
| `per_page` | int | Registros por página |
| `page` | int | Número de página |
| `tipo` | string | Tipo de catálogo |
| `activo` | int | 0/1 solo activos |
| `sort_by` | string | idarticulo, codigo, precio |
| `sort_order` | string | asc/desc |

**Ejemplo:**
```bash
curl -X GET "https://api.example.com/api/erp/v2/eloquent/catalogo?idcliente=1001&per_page=100" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "idarticulo": 5001,
      "codigo": "ART001",
      "descripcion": "Artículo de ejemplo",
      "precio_cliente": 89.99,
      "precio_lista": 99.99,
      "descuento": 10.00,
      "estado": 1,
      "disponible": 1,
      "categoria": "Electrónica"
    }
  ],
  "meta": {
    "total": 450,
    "per_page": 100,
    "current_page": 1,
    "last_page": 5,
    "cliente": {
      "idcliente": 1001,
      "nombre": "Empresa ABC SL"
    }
  }
}
```

#### GET - Direct
```http
GET /direct/catalogo
```

---

## Códigos de Error

| Código | Mensaje | Solución |
|--------|---------|----------|
| 400 | Bad Request | Verifica parámetros |
| 401 | Unauthorized | Proporciona token válido |
| 403 | Forbidden | Verifica permisos |
| 404 | Not Found | Recurso no existe |
| 422 | Validation Failed | Revisa errors en respuesta |
| 429 | Rate Limit Exceeded | Espera antes de reintentar |
| 500 | Server Error | Contacta soporte |

---

## Headers Recomendados

```http
Authorization: Bearer TOKEN
Content-Type: application/json
Accept: application/json
User-Agent: ClientName/1.0
X-Request-ID: unique-id-for-tracking
```

---

## Rate Limiting

- **Límite:** 1000 requests por minuto
- **Header:** `X-RateLimit-Remaining`
- **Reset:** `X-RateLimit-Reset`

```bash
curl -i https://api.example.com/api/erp/v2/eloquent/clientes \
  -H "Authorization: Bearer TOKEN"

# Response headers:
# X-RateLimit-Limit: 1000
# X-RateLimit-Remaining: 999
# X-RateLimit-Reset: 1705073400
```

---

**Última actualización:** 12 de enero de 2024
**Versión de API:** 2.0
**Estado:** Estable
