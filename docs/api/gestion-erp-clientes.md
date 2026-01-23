# API Gestión ERP - Consulta de Clientes

**Guía práctica Laravel para consultar clientes desde el ERP Gestión**

---

## Configuración Base

### URL Base
```
http://223.1.1.8:8080/api-gestion/
```

### Formato de Respuesta
- **Content-Type**: `application/xml`
- **Encoding**: UTF-8

---

## Endpoint: GET /cliente/

Consulta información detallada de clientes incluyendo datos LOPD, catálogos suscritos y cuotas.

### Opciones de Búsqueda

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `idcliente_gestion` | int | ID interno del cliente |
| `idclienteweb` | int | ID del cliente en web |
| `email` | string | Email del cliente |
| `dni` | string | DNI/CIF del cliente |
| `telefono1` | string | Teléfono principal |
| `apellidos` | string | Apellidos (búsqueda parcial) |
| `fnacimiento` | date | Fecha de nacimiento |
| `faceptacion_lopd_desde` | date | LOPD aceptada desde fecha |
| `faceptacion_lopd_hasta` | date | LOPD aceptada hasta fecha |
| `fbaja_desde` | date | Dado de baja desde fecha |
| `fbaja_hasta` | date | Dado de baja hasta fecha |

**Nota**: Usar al menos un parámetro de búsqueda.

---

## Consulta por Email

### Request
```
GET /cliente/?email={email}
```

### Ejemplo de URL
```
http://223.1.1.8:8080/api-gestion/cliente/?email=Marioespadavega@hotmail.es
```

### Respuesta XML

```xml
<?xml version="1.0" encoding="utf-8"?>
<response>
  <idcliente>101552349</idcliente>
  <nombre>MARIO</nombre>
  <apellidos>ESPADA VEGA</apellidos>
  <cif>78982005M</cif>
  <email>Marioespadavega@hotmail.es</email>
  <codigo_internet>923039</codigo_internet>
  <idtarjeta>100411666</idtarjeta>
  <idcategoria_cliente>1</idcategoria_cliente>
  <ididioma>2</ididioma>

  <fcreacion>2025-11-27</fcreacion>
  <fbaja></fbaja>

  <!-- Datos LOPD -->
  <faceptacion_lopd>2025-11-27</faceptacion_lopd>
  <no_informacion_comercial_lopd>0</no_informacion_comercial_lopd>
  <no_datos_a_terceros_lopd>0</no_datos_a_terceros_lopd>
  <tiene_interes_legitimo_lopd></tiene_interes_legitimo_lopd>

  <!-- Catálogos suscritos -->
  <cliente_catalogo>
    <resource>
      <idcatalogo>10</idcatalogo>
      <fsuscripcion>2025-11-27</fsuscripcion>
      <estado>1</estado>
    </resource>
  </cliente_catalogo>

  <!-- Cuotas/Servicios -->
  <cliente_cuota></cliente_cuota>

  <!-- Estadísticas -->
  <cantidad_albaranes>1</cantidad_albaranes>
</response>
```

---

## Consulta por ID de Cliente

### Request
```
GET /cliente/?idcliente_gestion={id}
```

### Ejemplo de URL
```
http://223.1.1.8:8080/api-gestion/cliente/?idcliente_gestion=101552349
```

---

## Consulta por DNI/CIF

### Request
```
GET /cliente/?dni={dni}
```

### Ejemplo de URL
```
http://223.1.1.8:8080/api-gestion/cliente/?dni=78982005M
```

---

## Estructura de la Respuesta

### Datos Principales

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `idcliente` | int | ID interno del cliente |
| `nombre` | string | Nombre |
| `apellidos` | string | Apellidos |
| `cif` | string | DNI/CIF |
| `email` | string | Email |
| `codigo_internet` | string | Código web del cliente |
| `idtarjeta` | int | ID tarjeta fidelización |
| `idcategoria_cliente` | int | Categoría del cliente |
| `ididioma` | int | ID del idioma preferido |

### Idiomas

| ID | Idioma |
|----|--------|
| 1 | Español |
| 2 | Inglés |
| 3 | Francés |
| 4 | Portugués |

### Fechas

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `fcreacion` | date | Fecha de alta |
| `fbaja` | date | Fecha de baja (null si activo) |

### Datos LOPD (Protección de Datos)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `faceptacion_lopd` | date | Fecha aceptación LOPD |
| `no_informacion_comercial_lopd` | int | 1=No quiere info comercial |
| `no_datos_a_terceros_lopd` | int | 1=No compartir datos |
| `tiene_interes_legitimo_lopd` | int | 1=Tiene interés legítimo |

### Catálogos Suscritos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `idcatalogo` | int | ID del catálogo |
| `fsuscripcion` | date | Fecha de suscripción |
| `estado` | int | 1=Activo, 0=Inactivo |

### Cuotas/Servicios

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `fcontratacion` | date | Fecha contratación |
| `ffinservicio` | date | Fecha fin servicio |
| `articulo.idarticulo` | int | ID del servicio |
| `articulo.codigo` | string | Código del servicio |
| `articulo.descripcion` | string | Descripción |
| `estado` | int | Estado del servicio |

### Estadísticas

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `cantidad_albaranes` | int | Número de albaranes/envíos |

---

## Implementación Laravel

### Service Class

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class GestionErpClienteService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.gestion_erp.base_url');
        $this->timeout = config('services.gestion_erp.timeout');
    }

    /**
     * Buscar cliente por email
     *
     * @param string $email
     * @return array|null
     */
    public function getByEmail(string $email): ?array
    {
        return $this->getCliente(['email' => $email]);
    }

    /**
     * Buscar cliente por ID
     *
     * @param int $idcliente
     * @return array|null
     */
    public function getById(int $idcliente): ?array
    {
        return $this->getCliente(['idcliente_gestion' => $idcliente]);
    }

    /**
     * Buscar cliente por DNI/CIF
     *
     * @param string $dni
     * @return array|null
     */
    public function getByDni(string $dni): ?array
    {
        return $this->getCliente(['dni' => $dni]);
    }

    /**
     * Buscar cliente por teléfono
     *
     * @param string $telefono
     * @return array|null
     */
    public function getByTelefono(string $telefono): ?array
    {
        return $this->getCliente(['telefono1' => $telefono]);
    }

    /**
     * Buscar clientes por apellidos
     *
     * @param string $apellidos
     * @return array
     */
    public function searchByApellidos(string $apellidos): array
    {
        return $this->getClienteMultiple(['apellidos' => $apellidos]);
    }

    /**
     * Buscar clientes que aceptaron LOPD en rango de fechas
     *
     * @param string $desde Fecha inicio (YYYY-MM-DD)
     * @param string $hasta Fecha fin (YYYY-MM-DD)
     * @return array
     */
    public function getByLopdDateRange(string $desde, string $hasta): array
    {
        return $this->getClienteMultiple([
            'faceptacion_lopd_desde' => $desde,
            'faceptacion_lopd_hasta' => $hasta,
        ]);
    }

    /**
     * Consulta genérica de cliente
     */
    protected function getCliente(array $params): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/cliente/", $params);

            if ($response->failed()) {
                Log::error('Error consultando cliente ERP', [
                    'params' => $params,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $this->parseXmlResponse($response->body());

        } catch (\Exception $e) {
            Log::error('Excepción consultando cliente ERP', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Consulta que puede devolver múltiples clientes
     */
    protected function getClienteMultiple(array $params): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/cliente/", $params);

            if ($response->failed()) {
                return [];
            }

            // Puede ser un cliente o una lista
            $body = $response->body();

            if (str_contains($body, '<resource>')) {
                return $this->parseXmlResponseMultiple($body);
            }

            $cliente = $this->parseXmlResponse($body);
            return $cliente ? [$cliente] : [];

        } catch (\Exception $e) {
            Log::error('Excepción consultando clientes ERP', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Parsear respuesta XML a array
     */
    protected function parseXmlResponse(string $xml): ?array
    {
        try {
            $xmlObject = new SimpleXMLElement($xml);

            // Si es respuesta directa (sin resource wrapper)
            if (isset($xmlObject->idcliente)) {
                return $this->xmlToClienteArray($xmlObject);
            }

            // Si tiene resource wrapper
            if (isset($xmlObject->resource)) {
                return $this->xmlToClienteArray($xmlObject->resource);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error parseando XML cliente', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parsear respuesta con múltiples clientes
     */
    protected function parseXmlResponseMultiple(string $xml): array
    {
        try {
            $xmlObject = new SimpleXMLElement($xml);
            $results = [];

            foreach ($xmlObject->resource as $resource) {
                $results[] = $this->xmlToClienteArray($resource);
            }

            return $results;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Convertir XML de cliente a array
     */
    protected function xmlToClienteArray(SimpleXMLElement $xml): array
    {
        return [
            'idcliente' => (int) $xml->idcliente,
            'nombre' => (string) $xml->nombre,
            'apellidos' => (string) $xml->apellidos,
            'nombre_completo' => trim((string) $xml->nombre . ' ' . (string) $xml->apellidos),
            'cif' => (string) $xml->cif,
            'email' => (string) $xml->email,
            'codigo_internet' => (string) $xml->codigo_internet,
            'idtarjeta' => (int) $xml->idtarjeta ?: null,
            'idcategoria_cliente' => (int) $xml->idcategoria_cliente,
            'ididioma' => (int) $xml->ididioma,

            'fechas' => [
                'fcreacion' => (string) $xml->fcreacion ?: null,
                'fbaja' => (string) $xml->fbaja ?: null,
            ],

            'lopd' => [
                'faceptacion' => (string) $xml->faceptacion_lopd ?: null,
                'no_info_comercial' => (bool) (int) $xml->no_informacion_comercial_lopd,
                'no_datos_terceros' => (bool) (int) $xml->no_datos_a_terceros_lopd,
                'interes_legitimo' => (bool) (int) $xml->tiene_interes_legitimo_lopd,
            ],

            'catalogos' => $this->parseCatalogos($xml->cliente_catalogo),
            'cuotas' => $this->parseCuotas($xml->cliente_cuota),

            'estadisticas' => [
                'cantidad_albaranes' => (int) $xml->cantidad_albaranes,
            ],

            'activo' => empty((string) $xml->fbaja),
        ];
    }

    /**
     * Parsear catálogos suscritos
     */
    protected function parseCatalogos(SimpleXMLElement $catalogos): array
    {
        $result = [];

        if (!$catalogos || !$catalogos->resource) {
            return $result;
        }

        foreach ($catalogos->resource as $cat) {
            $result[] = [
                'idcatalogo' => (int) $cat->idcatalogo,
                'fsuscripcion' => (string) $cat->fsuscripcion,
                'estado' => (int) $cat->estado,
                'activo' => (int) $cat->estado === 1,
            ];
        }

        return $result;
    }

    /**
     * Parsear cuotas/servicios
     */
    protected function parseCuotas(SimpleXMLElement $cuotas): array
    {
        $result = [];

        if (!$cuotas || !$cuotas->resource) {
            return $result;
        }

        foreach ($cuotas->resource as $cuota) {
            $result[] = [
                'fcontratacion' => (string) $cuota->fcontratacion,
                'ffinservicio' => (string) $cuota->ffinservicio,
                'articulo' => [
                    'idarticulo' => (int) $cuota->articulo->idarticulo,
                    'codigo' => (string) $cuota->articulo->codigo,
                    'descripcion' => (string) $cuota->articulo->descripcion,
                ],
                'estado' => (int) $cuota->estado,
            ];
        }

        return $result;
    }

    /**
     * Verificar si cliente tiene LOPD aceptada
     */
    public function hasValidLopd(int $idcliente): bool
    {
        $cliente = $this->getById($idcliente);

        if (!$cliente) {
            return false;
        }

        return !empty($cliente['lopd']['faceptacion']);
    }

    /**
     * Verificar si cliente acepta comunicaciones comerciales
     */
    public function acceptsMarketing(int $idcliente): bool
    {
        $cliente = $this->getById($idcliente);

        if (!$cliente) {
            return false;
        }

        return !$cliente['lopd']['no_info_comercial'];
    }
}
```

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\GestionErpClienteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClienteErpController extends Controller
{
    public function __construct(
        protected GestionErpClienteService $clienteService
    ) {}

    /**
     * Buscar cliente por email
     *
     * GET /api/erp/clientes/email/{email}
     */
    public function byEmail(string $email): JsonResponse
    {
        $cliente = $this->clienteService->getByEmail($email);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cliente,
        ]);
    }

    /**
     * Buscar cliente por ID
     *
     * GET /api/erp/clientes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $cliente = $this->clienteService->getById($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cliente,
        ]);
    }

    /**
     * Buscar cliente por DNI/CIF
     *
     * GET /api/erp/clientes/dni/{dni}
     */
    public function byDni(string $dni): JsonResponse
    {
        $cliente = $this->clienteService->getByDni($dni);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cliente,
        ]);
    }

    /**
     * Buscar clientes por apellidos
     *
     * GET /api/erp/clientes/buscar?apellidos={apellidos}
     */
    public function search(Request $request): JsonResponse
    {
        $apellidos = $request->input('apellidos');

        if (!$apellidos) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetro apellidos requerido',
            ], 400);
        }

        $clientes = $this->clienteService->searchByApellidos($apellidos);

        return response()->json([
            'success' => true,
            'data' => $clientes,
            'total' => count($clientes),
        ]);
    }

    /**
     * Verificar estado LOPD de cliente
     *
     * GET /api/erp/clientes/{id}/lopd
     */
    public function checkLopd(int $id): JsonResponse
    {
        $cliente = $this->clienteService->getById($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'idcliente' => $cliente['idcliente'],
                'lopd_valida' => !empty($cliente['lopd']['faceptacion']),
                'acepta_marketing' => !$cliente['lopd']['no_info_comercial'],
                'acepta_terceros' => !$cliente['lopd']['no_datos_terceros'],
                'lopd' => $cliente['lopd'],
            ],
        ]);
    }
}
```

### Rutas (routes/api.php)

```php
use App\Http\Controllers\ClienteErpController;

Route::prefix('erp/clientes')->group(function () {
    Route::get('buscar', [ClienteErpController::class, 'search']);
    Route::get('email/{email}', [ClienteErpController::class, 'byEmail']);
    Route::get('dni/{dni}', [ClienteErpController::class, 'byDni']);
    Route::get('{id}', [ClienteErpController::class, 'show']);
    Route::get('{id}/lopd', [ClienteErpController::class, 'checkLopd']);
});
```

---

## Uso Rápido

### Consulta directa con Http Facade

```php
use Illuminate\Support\Facades\Http;

// Por email
$response = Http::get('http://223.1.1.8:8080/api-gestion/cliente/', [
    'email' => 'cliente@example.com',
]);

$xml = simplexml_load_string($response->body());

echo "Cliente: " . $xml->nombre . " " . $xml->apellidos;
echo "ID: " . $xml->idcliente;
echo "LOPD aceptada: " . $xml->faceptacion_lopd;
```

### Usando el Service

```php
use App\Services\GestionErpClienteService;

$service = app(GestionErpClienteService::class);

// Por email
$cliente = $service->getByEmail('cliente@example.com');

// Por ID
$cliente = $service->getById(101552349);

// Por DNI
$cliente = $service->getByDni('78982005M');

// Verificar LOPD
if ($service->hasValidLopd(101552349)) {
    // Cliente puede hacer pedidos
}

// Verificar marketing
if ($service->acceptsMarketing(101552349)) {
    // Enviar comunicaciones comerciales
}
```

---

## Validación LOPD

### Verificar antes de crear pedido

```php
public function crearPedido(Request $request)
{
    $clienteService = app(GestionErpClienteService::class);
    $idcliente = $request->input('idcliente');

    // Verificar LOPD
    if (!$clienteService->hasValidLopd($idcliente)) {
        return response()->json([
            'success' => false,
            'error' => 'Cliente debe aceptar LOPD antes de realizar pedidos',
            'code' => 'LOPD_REQUIRED',
        ], 422);
    }

    // Continuar con creación del pedido...
}
```

---

## Manejo de Errores

### Códigos HTTP

| Código | Significado |
|--------|-------------|
| 200 | OK - Cliente encontrado |
| 404 | Cliente no existe |
| 400 | Parámetros inválidos |
| 408 | Servidor ocupado (reintentar) |
| 500 | Error interno del servidor |

### Errores Específicos

| Código | Mensaje |
|--------|---------|
| 20401 | Email duplicado |
| 20402 | CIF duplicado |
| 20403 | Cliente debe tener al menos 1 catálogo |
| 20404 | Cliente debe aceptar LOPD |

---

## Notas Importantes

1. **LOPD Obligatorio**: Cliente debe tener `faceptacion_lopd` para crear pedidos
2. **Catálogo Requerido**: Cliente debe estar suscrito a al menos 1 catálogo
3. **Email Único**: El email debe ser único en el sistema
4. **CIF Único**: El CIF/DNI debe ser único
5. **Formato Respuesta**: Siempre XML

---

## Campos LOPD Explicados

| Campo | Valor | Significado |
|-------|-------|-------------|
| `no_informacion_comercial_lopd` | 0 | **SÍ** quiere info comercial |
| `no_informacion_comercial_lopd` | 1 | **NO** quiere info comercial |
| `no_datos_a_terceros_lopd` | 0 | **SÍ** permite compartir datos |
| `no_datos_a_terceros_lopd` | 1 | **NO** permite compartir datos |

**Importante**: Los campos usan lógica negativa (0 = sí permite, 1 = no permite).

---

**Última actualización**: Diciembre 2025
**Versión**: 1.0
