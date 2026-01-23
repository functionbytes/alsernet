# API Gestión ERP - Consulta de Pedidos

**Guía práctica Laravel para consultar pedidos desde el ERP Gestión**

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

## Endpoint: GET /pedido-cliente/

Consulta información detallada de pedidos de clientes.

### Opciones de Consulta

| Opción | Parámetros | Descripción |
|--------|------------|-------------|
| Por serie y número | `serie` + `npedidocli` | Obtener pedido específico |
| Por cliente | `idcliente` | Todos los pedidos de un cliente |
| Por origen | `identificadororigen` | Pedido por ID externo |

---

## Consulta por Serie y Número

### Request
```
GET /pedido-cliente/?serie={serie}&npedidocli={numero}
```

### Parámetros

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `serie` | string | Sí | Serie del pedido (ej: "2025") |
| `npedidocli` | int | Sí | Número de pedido |

### Ejemplo de URL
```
http://223.1.1.8:8080/api-gestion/pedido-cliente/?npedidocli=61550&serie=2025
```

### Respuesta XML

```xml
<?xml version="1.0" encoding="utf-8"?>
<response>
  <resource>
    <idpedidocli>2157752</idpedidocli>
    <fpedido>2025-11-27</fpedido>
    <npedidocli>61550</npedidocli>
    <identificadororigen>795451</identificadororigen>
    <total_con_impuestos>124.99</total_con_impuestos>

    <lineas_pedido_cliente>
      <resource>
        <total_con_impuestos>124.99</total_con_impuestos>
        <unidades>1.0</unidades>
        <articulo>
          <descripcion>BOTAS CHIRUCA TORCAZ 01 GORE-TEX Talla 43</descripcion>
          <idarticulo>100202534</idarticulo>
          <codigo>CW301166-43</codigo>
        </articulo>
        <idcatalogo>10</idcatalogo>
      </resource>
    </lineas_pedido_cliente>

    <almacen>
      <descripcion>POCOMACO</descripcion>
      <idalmacen>1</idalmacen>
    </almacen>

    <envio>
      <provincia></provincia>
      <coste>0.0</coste>
      <calle>CALLE LOS SITIOS</calle>
      <num></num>
      <localidad>MARBELLA</localidad>
      <pais>ESPAÑA</pais>
      <cp>29601</cp>
      <telefono>688902822</telefono>
    </envio>

    <forma_pago_pedido_cliente>
      <resource>
        <idformapago>27</idformapago>
        <importe>124.99</importe>
      </resource>
    </forma_pago_pedido_cliente>

    <serie>
      <descripcorta>2025</descripcorta>
    </serie>

    <incidencia_pedido_cliente></incidencia_pedido_cliente>

    <estado>
      <descripcion>Servido</descripcion>
      <idestado>7</idestado>
    </estado>

    <cliente>
      <cif>78982005M</cif>
      <fcreacion>2025-11-27</fcreacion>
      <apellidos>ESPADA VEGA</apellidos>
      <idtarjeta>100411666</idtarjeta>
      <idcategoria_cliente>1</idcategoria_cliente>
      <nombre>MARIO</nombre>
      <idcliente>101552349</idcliente>
      <email>Marioespadavega@hotmail.es</email>
    </cliente>
  </resource>
</response>
```

---

## Estructura de la Respuesta

### Cabecera del Pedido

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `idpedidocli` | int | ID interno del pedido |
| `fpedido` | date | Fecha del pedido (YYYY-MM-DD) |
| `npedidocli` | int | Número de pedido |
| `identificadororigen` | int | ID externo/origen |
| `total_con_impuestos` | decimal | Total con IVA |

### Estado del Pedido

| ID Estado | Descripción |
|-----------|-------------|
| 0 | Anulado |
| 1 | Pendiente |
| 3 | En preparación |
| 5 | Enviado |
| 7 | Servido |

### Datos del Cliente

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `idcliente` | int | ID del cliente |
| `nombre` | string | Nombre |
| `apellidos` | string | Apellidos |
| `cif` | string | CIF/DNI |
| `email` | string | Email |
| `idtarjeta` | int | ID tarjeta fidelización |
| `idcategoria_cliente` | int | Categoría del cliente |
| `fcreacion` | date | Fecha de alta |

### Líneas del Pedido

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `articulo.idarticulo` | int | ID del artículo |
| `articulo.codigo` | string | Código/SKU |
| `articulo.descripcion` | string | Descripción del producto |
| `unidades` | decimal | Cantidad |
| `total_con_impuestos` | decimal | Subtotal con IVA |
| `idcatalogo` | int | ID del catálogo |

### Datos de Envío

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `calle` | string | Dirección |
| `num` | string | Número |
| `localidad` | string | Ciudad |
| `provincia` | string | Provincia |
| `cp` | string | Código postal |
| `pais` | string | País |
| `telefono` | string | Teléfono contacto |
| `coste` | decimal | Coste del envío |

### Forma de Pago

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `idformapago` | int | ID método de pago |
| `importe` | decimal | Importe pagado |

---

## Implementación Laravel

### Configuración (.env)

```env
GESTION_ERP_URL=http://223.1.1.8:8080/api-gestion
GESTION_ERP_TIMEOUT=30
```

### Configuración (config/services.php)

```php
'gestion_erp' => [
    'base_url' => env('GESTION_ERP_URL', 'http://223.1.1.8:8080/api-gestion'),
    'timeout' => env('GESTION_ERP_TIMEOUT', 30),
],
```

### Service Class

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class GestionErpService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.gestion_erp.base_url');
        $this->timeout = config('services.gestion_erp.timeout');
    }

    /**
     * Consultar pedido por serie y número
     *
     * @param string $serie Serie del pedido (ej: "2025")
     * @param int $npedidocli Número de pedido
     * @return array|null
     */
    public function getPedido(string $serie, int $npedidocli): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/pedido-cliente/", [
                    'serie' => $serie,
                    'npedidocli' => $npedidocli,
                ]);

            if ($response->failed()) {
                Log::error('Error consultando pedido ERP', [
                    'serie' => $serie,
                    'npedidocli' => $npedidocli,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $this->parseXmlResponse($response->body());

        } catch (\Exception $e) {
            Log::error('Excepción consultando pedido ERP', [
                'serie' => $serie,
                'npedidocli' => $npedidocli,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Consultar pedidos por cliente
     *
     * @param int $idcliente ID del cliente
     * @return array
     */
    public function getPedidosByCliente(int $idcliente): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/pedido-cliente/", [
                    'idcliente' => $idcliente,
                ]);

            if ($response->failed()) {
                Log::error('Error consultando pedidos por cliente', [
                    'idcliente' => $idcliente,
                    'status' => $response->status(),
                ]);
                return [];
            }

            return $this->parseXmlResponseMultiple($response->body());

        } catch (\Exception $e) {
            Log::error('Excepción consultando pedidos por cliente', [
                'idcliente' => $idcliente,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Consultar pedido por identificador de origen
     *
     * @param string $identificadorOrigen ID externo del pedido
     * @return array|null
     */
    public function getPedidoByOrigen(string $identificadorOrigen): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/pedido-cliente/", [
                    'identificadororigen' => $identificadorOrigen,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $this->parseXmlResponse($response->body());

        } catch (\Exception $e) {
            Log::error('Excepción consultando pedido por origen', [
                'identificadororigen' => $identificadorOrigen,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parsear respuesta XML a array
     */
    protected function parseXmlResponse(string $xml): ?array
    {
        try {
            $xmlObject = new SimpleXMLElement($xml);
            $resource = $xmlObject->resource;

            if (!$resource) {
                return null;
            }

            return $this->xmlResourceToArray($resource);

        } catch (\Exception $e) {
            Log::error('Error parseando XML', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parsear respuesta XML con múltiples recursos
     */
    protected function parseXmlResponseMultiple(string $xml): array
    {
        try {
            $xmlObject = new SimpleXMLElement($xml);
            $results = [];

            foreach ($xmlObject->resource as $resource) {
                $results[] = $this->xmlResourceToArray($resource);
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Error parseando XML múltiple', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Convertir recurso XML a array estructurado
     */
    protected function xmlResourceToArray(SimpleXMLElement $resource): array
    {
        return [
            'idpedidocli' => (int) $resource->idpedidocli,
            'fpedido' => (string) $resource->fpedido,
            'npedidocli' => (int) $resource->npedidocli,
            'identificadororigen' => (string) $resource->identificadororigen,
            'total_con_impuestos' => (float) $resource->total_con_impuestos,

            'estado' => [
                'idestado' => (int) $resource->estado->idestado,
                'descripcion' => (string) $resource->estado->descripcion,
            ],

            'cliente' => [
                'idcliente' => (int) $resource->cliente->idcliente,
                'nombre' => (string) $resource->cliente->nombre,
                'apellidos' => (string) $resource->cliente->apellidos,
                'cif' => (string) $resource->cliente->cif,
                'email' => (string) $resource->cliente->email,
                'idtarjeta' => (int) $resource->cliente->idtarjeta,
                'fcreacion' => (string) $resource->cliente->fcreacion,
            ],

            'lineas' => $this->parseLineasPedido($resource->lineas_pedido_cliente),

            'envio' => [
                'calle' => (string) $resource->envio->calle,
                'num' => (string) $resource->envio->num,
                'localidad' => (string) $resource->envio->localidad,
                'provincia' => (string) $resource->envio->provincia,
                'cp' => (string) $resource->envio->cp,
                'pais' => (string) $resource->envio->pais,
                'telefono' => (string) $resource->envio->telefono,
                'coste' => (float) $resource->envio->coste,
            ],

            'forma_pago' => $this->parseFormasPago($resource->forma_pago_pedido_cliente),

            'almacen' => [
                'idalmacen' => (int) $resource->almacen->idalmacen,
                'descripcion' => (string) $resource->almacen->descripcion,
            ],

            'serie' => (string) $resource->serie->descripcorta,
        ];
    }

    /**
     * Parsear líneas del pedido
     */
    protected function parseLineasPedido(SimpleXMLElement $lineas): array
    {
        $result = [];

        foreach ($lineas->resource as $linea) {
            $result[] = [
                'idarticulo' => (int) $linea->articulo->idarticulo,
                'codigo' => (string) $linea->articulo->codigo,
                'descripcion' => (string) $linea->articulo->descripcion,
                'unidades' => (float) $linea->unidades,
                'total_con_impuestos' => (float) $linea->total_con_impuestos,
                'idcatalogo' => (int) $linea->idcatalogo ?: null,
            ];
        }

        return $result;
    }

    /**
     * Parsear formas de pago
     */
    protected function parseFormasPago(SimpleXMLElement $formasPago): array
    {
        $result = [];

        foreach ($formasPago->resource as $pago) {
            $result[] = [
                'idformapago' => (int) $pago->idformapago,
                'importe' => (float) $pago->importe,
            ];
        }

        return $result;
    }
}
```

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\GestionErpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PedidoErpController extends Controller
{
    public function __construct(
        protected GestionErpService $erpService
    ) {}

    /**
     * Consultar pedido por serie y número
     *
     * GET /api/erp/pedidos/{serie}/{numero}
     */
    public function show(string $serie, int $numero): JsonResponse
    {
        $pedido = $this->erpService->getPedido($serie, $numero);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pedido,
        ]);
    }

    /**
     * Consultar pedidos de un cliente
     *
     * GET /api/erp/pedidos/cliente/{idcliente}
     */
    public function byCliente(int $idcliente): JsonResponse
    {
        $pedidos = $this->erpService->getPedidosByCliente($idcliente);

        return response()->json([
            'success' => true,
            'data' => $pedidos,
            'total' => count($pedidos),
        ]);
    }

    /**
     * Consultar pedido por identificador de origen
     *
     * GET /api/erp/pedidos/origen/{identificador}
     */
    public function byOrigen(string $identificador): JsonResponse
    {
        $pedido = $this->erpService->getPedidoByOrigen($identificador);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pedido,
        ]);
    }
}
```

### Rutas (routes/api.php)

```php
use App\Http\Controllers\PedidoErpController;

Route::prefix('erp/pedidos')->group(function () {
    Route::get('{serie}/{numero}', [PedidoErpController::class, 'show']);
    Route::get('cliente/{idcliente}', [PedidoErpController::class, 'byCliente']);
    Route::get('origen/{identificador}', [PedidoErpController::class, 'byOrigen']);
});
```

---

## Uso Rápido

### Consulta directa con Http Facade

```php
use Illuminate\Support\Facades\Http;

// Consultar pedido 61550 de serie 2025
$response = Http::get('http://223.1.1.8:8080/api-gestion/pedido-cliente/', [
    'serie' => '2025',
    'npedidocli' => 61550,
]);

$xml = simplexml_load_string($response->body());
$pedido = $xml->resource;

echo "Cliente: " . $pedido->cliente->nombre . " " . $pedido->cliente->apellidos;
echo "Total: €" . $pedido->total_con_impuestos;
echo "Estado: " . $pedido->estado->descripcion;
```

### Usando el Service

```php
use App\Services\GestionErpService;

$erp = app(GestionErpService::class);

// Por serie y número
$pedido = $erp->getPedido('2025', 61550);

// Por cliente
$pedidos = $erp->getPedidosByCliente(101552349);

// Por origen
$pedido = $erp->getPedidoByOrigen('795451');
```

---

## Manejo de Errores

### Códigos HTTP

| Código | Significado |
|--------|-------------|
| 200 | OK - Pedido encontrado |
| 404 | Pedido no existe |
| 408 | Servidor ocupado (reintentar) |
| 500 | Error interno del servidor |

### Ejemplo de Retry

```php
use Illuminate\Support\Facades\Http;

$response = Http::retry(3, 1000) // 3 intentos, 1 segundo entre cada uno
    ->timeout(30)
    ->get('http://223.1.1.8:8080/api-gestion/pedido-cliente/', [
        'serie' => '2025',
        'npedidocli' => 61550,
    ]);
```

---

## Notas Importantes

1. **Formato de respuesta**: Siempre XML, no JSON
2. **Timeout recomendado**: 30 segundos
3. **Serie**: Generalmente corresponde al año (2024, 2025, etc.)
4. **Estados comunes**: 0=Anulado, 7=Servido

---

**Última actualización**: Diciembre 2025
**Versión**: 1.0
