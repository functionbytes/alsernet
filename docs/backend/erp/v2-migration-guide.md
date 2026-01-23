# API v2 - Guía de Migración desde V1 a V2

## Resumen Ejecutivo

La migración de API v1 a v2 introduce una arquitectura moderna basada en Eloquent ORM con soporte dual para acceso directo a base de datos. Esta guía proporciona pasos detallados para migrar consumidores de API desde v1 a v2.

---

## 1. Cambios Principales en la Arquitectura

### v1 (Legado)
- Endpoints POST sin estandarización RESTful
- Respuestas personalizadas sin formato consistente
- Camel case en parámetros
- Manejo manual de conexiones a base de datos

### v2 (Moderno)
- Endpoints RESTful (GET, POST, PUT, DELETE)
- Respuestas JSON estandarizadas
- Validación centralizada
- Dos modos de acceso: Eloquent ORM y Direct SQL

---

## 2. Estructura de Nuevos Endpoints

### Nomenclatura
```
/api/erp/v2/{modo}/{recurso}
```

**Parámetros:**
- `{modo}`: `eloquent` (ORM) o `direct` (SQL directo)
- `{recurso}`: Entidad del ERP (clientes, articulos, albaranes, etc.)

### Recursos Disponibles

| Recurso | Métodos | Descripción |
|---------|---------|-------------|
| `/clientes` | GET, POST, PUT | Gestión de clientes |
| `/articulos` | GET | Catálogo de artículos |
| `/albaranes` | GET | Albaranes de venta |
| `/bonos` | GET | Bonos y descuentos |
| `/stock` | GET | Disponibilidad de stock |
| `/vales` | GET | Gestión de vales |
| `/pedidos` | GET | Pedidos de clientes |
| `/catalogo` | GET | Catálogos de clientes |

---

## 3. Comparativa de Endpoints: v1 vs v2

### Obtener Datos de Cliente

**v1 (POST):**
```bash
POST /api/erp/recuperarclienteerp
Content-Type: application/json

{
  "idcliente": 12345
}
```

**v2 (GET):**
```bash
GET /api/erp/v2/eloquent/clientes?id=12345
Authorization: Bearer {token}
```

### Crear/Actualizar Cliente

**v1 (POST):**
```bash
POST /api/erp/guardardatosclienteerp
Content-Type: application/json

{
  "idcliente": 12345,
  "nombre": "Empresa ABC",
  "email": "contacto@empresa.com"
}
```

**v2 (POST/PUT):**
```bash
POST /api/erp/v2/eloquent/clientes
Content-Type: application/json
Authorization: Bearer {token}

{
  "nombre": "Empresa ABC",
  "email": "contacto@empresa.com"
}

# O actualizar con PUT
PUT /api/erp/v2/eloquent/clientes
{
  "idcliente": 12345,
  "nombre": "Empresa ABC Actualizada"
}
```

---

## 4. Parámetros de Filtrado y Búsqueda

### Paginación

```bash
GET /api/erp/v2/eloquent/articulos?per_page=50&page=1
```

**Parámetros:**
- `per_page`: Registros por página (default: 50, máximo: 500)
- `page`: Número de página (default: 1)

### Búsqueda General

```bash
GET /api/erp/v2/eloquent/articulos?search=codigo_articulo
```

**Busca en:**
- Código
- Código de barras
- Descripción

### Filtros Específicos

```bash
GET /api/erp/v2/eloquent/articulos?estado=1&idmarca=5&idmodelo=10
```

### Ordenamiento

```bash
GET /api/erp/v2/eloquent/articulos?sort_by=codigo&sort_order=asc
```

**Campos disponibles:**
- `codigo`
- `descripcion`
- `preciomedio`
- `estado`
- `idarticulo`

---

## 5. Formato de Respuestas

### Respuesta Exitosa (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 12345,
    "codigo": "ART001",
    "descripcion": "Producto de ejemplo",
    "precio": 99.99,
    "estado": 1,
    "created_at": "2024-01-12T10:30:00Z",
    "updated_at": "2024-01-12T10:30:00Z"
  },
  "meta": {
    "total": 1,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1
  }
}
```

### Respuesta Paginada

```json
{
  "success": true,
  "data": [
    { "id": 1, "codigo": "ART001", ... },
    { "id": 2, "codigo": "ART002", ... }
  ],
  "meta": {
    "total": 150,
    "per_page": 50,
    "current_page": 1,
    "last_page": 3,
    "from": 1,
    "to": 50
  }
}
```

### Respuesta de Error (4xx/5xx)

```json
{
  "success": false,
  "message": "Recurso no encontrado",
  "errors": {
    "id": ["El ID especificado no existe"]
  },
  "status_code": 404
}
```

---

## 6. Modos de Acceso: Eloquent vs Direct

### Eloquent (ORM)

**Ventajas:**
- Relaciones automáticas
- Validación integrada
- Caching de Eloquent
- Mayor mantenibilidad

**Desventajas:**
- Ligeramente más lento en grandes volúmenes

**Uso:**
```bash
GET /api/erp/v2/eloquent/clientes
GET /api/erp/v2/eloquent/articulos?per_page=100
```

### Direct (SQL Directo)

**Ventajas:**
- Máximo rendimiento
- Ideal para grandes volúmenes de datos
- Queries optimizadas

**Desventajas:**
- Sin relaciones Eloquent automáticas
- Requiere query tuning manual

**Uso:**
```bash
GET /api/erp/v2/direct/clientes
GET /api/erp/v2/direct/articulos?per_page=500
```

**Recomendación:** Usar `direct` para reportes y exportaciones masivas.

---

## 7. Campos de Respuesta por Recurso

### Clientes

```json
{
  "idcliente": 12345,
  "codigo": "CLI001",
  "nombre": "Empresa ABC",
  "email": "contacto@empresa.com",
  "telefono": "+34 555 123 456",
  "direccion": "Calle Principal 123",
  "ciudad": "Madrid",
  "codigo_postal": "28001",
  "pais": "ES",
  "tipo_cliente": 1,
  "limiteCredito": 50000.00,
  "saldoDeuda": 12500.50,
  "estado": 1,
  "lopd_consentimiento": 1,
  "lopd_fecha": "2024-01-12",
  "created_at": "2023-01-12T10:30:00Z",
  "updated_at": "2024-01-12T15:45:00Z"
}
```

### Artículos

```json
{
  "idarticulo": 5678,
  "codigo": "ART001",
  "codbar": "8432156789012",
  "descripcion": "Producto de ejemplo",
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
```

### Albaranes

```json
{
  "idalb": 9001,
  "codigo": "ALB001",
  "idcliente": 12345,
  "fecha": "2024-01-12",
  "total": 5500.50,
  "total_iva": 1155.11,
  "total_neto": 4345.39,
  "estado": 1,
  "numero_lineas": 15,
  "almacen": "ALM001",
  "created_at": "2024-01-12T10:00:00Z"
}
```

### Stock

```json
{
  "idarticulo": 5678,
  "almacen": "ALM001",
  "cantidad_disponible": 150,
  "cantidad_reservada": 25,
  "cantidad_total": 175,
  "ultima_entrada": "2024-01-10T12:30:00Z",
  "ultima_salida": "2024-01-12T09:15:00Z"
}
```

---

## 8. Códigos de Error HTTP

| Código | Significado | Acción |
|--------|------------|--------|
| 200 | OK | Solicitud exitosa |
| 201 | Created | Recurso creado exitosamente |
| 400 | Bad Request | Parámetros inválidos o incompletos |
| 401 | Unauthorized | Falta autenticación o token inválido |
| 403 | Forbidden | Permiso denegado |
| 404 | Not Found | Recurso no encontrado |
| 422 | Unprocessable Entity | Validación fallida |
| 429 | Too Many Requests | Límite de rate-limit excedido |
| 500 | Internal Server Error | Error del servidor |

---

## 9. Autenticación

### Bearer Token (Recomendado)

```bash
GET /api/erp/v2/eloquent/clientes
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Sanctum Token

```bash
GET /api/erp/v2/eloquent/clientes
Authorization: Bearer {sanctum_token}
X-API-Key: {optional}
```

---

## 10. Validación de Datos

### Crear Cliente

**Campos Requeridos:**
- `nombre`: string (máximo 255 caracteres)
- `email`: email válido

**Campos Opcionales:**
- `telefono`: string
- `direccion`: string
- `ciudad`: string
- `codigo_postal`: string
- `pais`: string (código ISO 2)

**Ejemplo de Error de Validación:**
```json
{
  "success": false,
  "message": "Validación fallida",
  "errors": {
    "nombre": ["El campo nombre es requerido"],
    "email": ["El campo email debe ser un email válido"]
  },
  "status_code": 422
}
```

---

## 11. Mantenimiento de Compatibilidad

### v1 Sigue Disponible

La API v1 continuará disponible indefinidamente:

```bash
POST /api/erp/recuperarclienteerp
POST /api/erp/recuperarpedidoscliente
POST /api/erp/guardardatosclienteerp
```

**Plan de Sunsetting:**
- Enero 2024 - Marzo 2025: v1 y v2 disponibles
- Abril 2025: v1 entra en modo "deprecado"
- Enero 2026: v1 será removida

---

## 12. Ejemplos de Migración por Caso de Uso

### Caso 1: Sincronizar Clientes

**v1:**
```bash
for each cliente in database:
  POST /api/erp/recuperardatosclienteerp { idcliente }
  POST /api/erp/guardardatosclienteerp { datos_actualizados }
```

**v2:**
```bash
GET /api/erp/v2/eloquent/clientes?per_page=500
for each cliente in response.data:
  PUT /api/erp/v2/eloquent/clientes { idcliente, datos_actualizados }
```

### Caso 2: Obtener Catálogo de Artículos

**v1:**
```bash
POST /api/erp/recuperarcatalogosclienteerp { idcliente }
```

**v2:**
```bash
GET /api/erp/v2/eloquent/catalogo?idcliente=12345&per_page=100
# Paginar según meta.last_page
GET /api/erp/v2/eloquent/catalogo?idcliente=12345&per_page=100&page=2
```

### Caso 3: Importar Stock en Lote

**v1:**
```bash
# Múltiples requests POST individuales
```

**v2:**
```bash
GET /api/erp/v2/direct/stock?per_page=500
# Usar 'direct' para máximo rendimiento
```

---

## 13. Testing de Migración

### Script de Validación

```bash
#!/bin/bash

BASE_URL="https://api.example.com"
TOKEN="Bearer your_token"

# Test 1: GET Clientes
curl -H "Authorization: $TOKEN" \
  "$BASE_URL/api/erp/v2/eloquent/clientes?per_page=10"

# Test 2: GET Artículos con búsqueda
curl -H "Authorization: $TOKEN" \
  "$BASE_URL/api/erp/v2/eloquent/articulos?search=ART&per_page=10"

# Test 3: POST Cliente
curl -X POST -H "Authorization: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Test","email":"test@example.com"}' \
  "$BASE_URL/api/erp/v2/eloquent/clientes"
```

---

## 14. Performance y Optimización

### Recomendaciones

1. **Usar `per_page=500` para v2/direct**
   ```bash
   GET /api/erp/v2/direct/articulos?per_page=500
   ```

2. **Implementar caché local de respuestas**
   ```bash
   # Cache por 5 minutos
   GET /api/erp/v2/eloquent/articulos
   Cache-Control: public, max-age=300
   ```

3. **Usar Direct en lugar de Eloquent para grandes volúmenes**
   ```bash
   # Para sincronizaciones masivas
   GET /api/erp/v2/direct/stock?per_page=500
   ```

4. **Limitar campos con select (cuando esté disponible)**
   ```bash
   GET /api/erp/v2/eloquent/clientes?fields=id,nombre,email
   ```

---

## 15. Soporte y Debugging

### Logs de API

```bash
tail -f storage/logs/api.log
```

### Headers de Debug

```bash
GET /api/erp/v2/eloquent/clientes
X-Debug: true
```

**Respuesta con información de debug:**
```json
{
  "success": true,
  "data": [...],
  "debug": {
    "query_time": "45ms",
    "cached": false,
    "database": "oracle"
  }
}
```

### Contacto y Soporte

- **Equipo:** API Development Team
- **Email:** api-support@example.com
- **Documentación:** https://docs.example.com/api/v2
- **Issues:** https://github.com/example/api-v2/issues

---

## Checklist de Migración

- [ ] Actualizar URLs de endpoints en cliente
- [ ] Implementar autenticación Bearer Token
- [ ] Ajustar parsing de respuestas JSON
- [ ] Agregar manejo de paginación
- [ ] Implementar reintentos con backoff
- [ ] Actualizar logging/monitoring
- [ ] Probar en ambiente staging
- [ ] Realizar pruebas de carga
- [ ] Deploying en producción
- [ ] Monitorear errores en primeras 24h

---

**Última actualización:** 12 de enero de 2024
**Versión:** 1.0
**Estado:** En Desarrollo
