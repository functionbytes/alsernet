# Referencia de Modelos Oracle ERP v2

## Descripción General

Los modelos Oracle ERP v2 representan las tablas de la base de datos Oracle, organizados en namespaces por funcionalidad. Cada modelo incluye documentación de índices y relaciones disponibles.

---

## Convenciones de Modelado

### Conexión a Base de Datos
```php
protected $connection = 'oracle';
```

### Timestamps Personalizados
```php
const CREATED_AT = 'fcreacion';
const UPDATED_AT = 'fmodificacion';
const DELETED_AT = 'fbaja';
```

### Soft Deletes
```php
use SoftDeletes;
```

---

## 1. Modelos Core (V2/Core)

### ProductImport

**Namespace:** `Modules\Erp\Models\V2\Core`
**Tabla:** `product_import`
**Propósito:** Gestión de importaciones de productos

**Atributos Principales:**
```php
protected $fillable = [
    'importable_type',      // Tipo de recurso (Product, Combination)
    'importable_id',        // ID del recurso
    'id_origen',           // ID en sistema origen
    'id_articulo',         // ID artículo ERP
    'unidades_oferta',     // Unidades en oferta
    'etiqueta',           // Etiqueta de importación
    'estado_gestion',     // Estado de gestión
    'activo',             // Activo/Inactivo
    'es_segunda_mano',    // Segunda mano
    'externo_disponibilidad', // Disponibilidad externa
    'codigo_proveedor',   // Código en proveedor
    'precio_costo_proveedor', // Precio de costo
    'tarifa_proveedor',   // Tarifa del proveedor
    'es_arma',           // Es arma
    'es_arma_fogueo',    // Es arma de fogueo
    'es_cartucho',       // Es cartucho
    'ean',              // EAN code
    'upc',              // UPC code
    'categoria',        // Categoría
    'familia',          // Familia de producto
    'subfamilia',       // Subfamilia
    'grupo',           // Grupo de producto
    'last_sync_at',    // Última sincronización
    'sync_pending',    // Sincronización pendiente
    'sync_errors',     // Errores de sincronización
];
```

**Relaciones Morphic:**
```php
public function importable(): MorphTo
{
    // Relación polimórfica con Product o Combination
}
```

**Relaciones Many-to-Many:**
```php
public function tags(): BelongsToMany
{
    // Tags asociados a la importación
}
```

**Índices Disponibles:**
- Búsqueda rápida por `id_articulo`
- Búsqueda por `importable_type` y `importable_id`
- Búsqueda por `codigo_proveedor`

---

### PriceValidation

**Namespace:** `Modules\Erp\Models\V2\Core`
**Tabla:** `price_validations`
**Propósito:** Validación y seguimiento de cambios de precios

**Atributos:**
```php
protected $fillable = [
    'idarticulo',              // ID artículo
    'precio_anterior',         // Precio anterior
    'precio_nuevo',           // Precio nuevo
    'porcentaje_cambio',      // % cambio
    'motivo',                 // Motivo del cambio
    'validado_por',           // Usuario que validó
    'fecha_validacion',       // Fecha de validación
    'estado',                 // Estado
];
```

**Relaciones:**
```php
public function articulo()
{
    // Relación con modelo Articulo
}

public function historial()
{
    // Historial de cambios
}
```

---

### ProductImportTag

**Namespace:** `Modules\Erp\Models\V2\Core`
**Tabla:** `product_import_tags`
**Propósito:** Tags para categorizar importaciones

**Atributos:**
```php
protected $fillable = [
    'product_import_id',    // ID de importación
    'nombre',              // Nombre del tag
    'descripcion',         // Descripción
];
```

---

### ScheduledPriceValidation

**Namespace:** `Modules\Erp\Models\V2\Core`
**Tabla:** `scheduled_price_validations`
**Propósito:** Validaciones de precio programadas

**Atributos:**
```php
protected $fillable = [
    'idarticulo',          // ID artículo
    'precio_minimo',       // Precio mínimo permitido
    'precio_maximo',       // Precio máximo permitido
    'frecuencia',          // Frecuencia de validación
    'proxima_ejecucion',   // Próxima ejecución
    'activo',              // Activo/Inactivo
];
```

---

## 2. Modelos Web (Oracle/Web)

### WProducto

**Namespace:** `Modules\Erp\Models\V2\Oracle\Web`
**Tabla:** `w_producto`
**Propósito:** Gestión de productos en tienda web

**Atributos Principales:**
```php
protected $fillable = [
    'activo',                    // Producto activo en web
    'precio',                   // Precio de venta
    'referencia',              // Referencia única
    'imagen',                  // URL de imagen
    'id_modelo',               // ID modelo asociado
    'precio_anterior',         // Precio anterior
    'vendible',               // Marcado como vendible
    'texto_no_vendible',      // Texto si no vendible
    'microprecio',            // Microprecio
    'texto_no_vendible_en',   // Texto no vendible en inglés
    'precio_sin_iva',         // Precio sin IVA
    'precio_anterior_sin_iva', // Precio anterior sin IVA
    'unidades_oferta',        // Unidades en oferta
    'imagen_seo',             // Imagen para SEO
    'estado',                 // Estado
    'idarticulo',             // ID artículo ERP
];
```

**Índices Disponibles:**
```
IDX_REFERENCIA (NONUNIQUE)
  - Tipo: NORMAL
  - Columnas: REFERENCIA

IDX_WPRODUCTO_WMODELO (NONUNIQUE)
  - Tipo: NORMAL
  - Columnas: ID_MODELO

IDX_W_PRODUCTO_ARTICULO (NONUNIQUE)
  - Tipo: NORMAL
  - Columnas: IDARTICULO

PK_W_PRODUCTO (UNIQUE)
  - Tipo: PRIMARY KEY
  - Columnas: ID
```

**Timestamps Personalizados:**
```php
const CREATED_AT = 'fcreacion';
const UPDATED_AT = 'fmodificacion';
const DELETED_AT = 'fbaja';
```

---

### WModelo

**Namespace:** `Modules\Erp\Models\V2\Oracle\Web`
**Tabla:** `w_modelo`
**Propósito:** Modelos de productos en web

**Atributos:**
```php
protected $fillable = [
    'nombre',          // Nombre del modelo
    'descripcion',     // Descripción
    'idmarca',        // ID marca
    'imagen_principal', // Imagen principal
    'posicion',       // Posición en web
    'activo',         // Activo/Inactivo
];
```

---

### WCaracteristicasProd

**Namespace:** `Modules\Erp\Models\V2\Oracle\Web`
**Tabla:** `w_caracteristicas_prod`
**Propósito:** Características técnicas de productos

**Atributos:**
```php
protected $fillable = [
    'id_producto',     // ID producto web
    'nombre_caracteristica', // Nombre
    'valor',          // Valor de característica
    'posicion',       // Orden de visualización
];
```

---

### WDescuentosRelacionados

**Namespace:** `Modules\Erp\Models\V2\Oracle\Web`
**Tabla:** `w_descuentos_relacionados`
**Propósito:** Descuentos por compra de productos relacionados

**Atributos:**
```php
protected $fillable = [
    'id_producto_principal',    // Producto principal
    'id_producto_relacionado',  // Producto relacionado
    'porcentaje_descuento',    // % descuento
    'cantidad_minima',         // Cantidad mínima
    'vigente_desde',           // Vigente desde
    'vigente_hasta',           // Vigente hasta
];
```

---

### WDescuentosRelacionValor

**Namespace:** `Modules\Erp\Models\V2\Oracle\Web`
**Tabla:** `w_descuentos_relacion_valor`
**Propósito:** Descuentos por rango de valor

**Atributos:**
```php
protected $fillable = [
    'valor_minimo',    // Valor mínimo de compra
    'valor_maximo',    // Valor máximo de compra
    'porcentaje_descuento', // % descuento aplicado
    'estado',          // Activo/Inactivo
];
```

---

### WTiendas

**Namespace:** `Modules\Erp\Models\V2\Oracle\Web`
**Tabla:** `w_tiendas`
**Propósito:** Información de tiendas físicas

**Atributos:**
```php
protected $fillable = [
    'nombre',          // Nombre tienda
    'direccion',       // Dirección
    'ciudad',         // Ciudad
    'codigo_postal',  // Código postal
    'telefono',       // Teléfono
    'email',          // Email
    'latitud',        // Coordenada latitud
    'longitud',       // Coordenada longitud
    'horario',        // Horario de atención
    'activa',         // Tienda activa
];
```

---

### WProductosMasVendidos

**Namespace:** `Modules\Erp\Models\V2\Oracle\Web`
**Tabla:** `w_productos_mas_vendidos`
**Propósito:** Ranking de productos más vendidos

**Atributos:**
```php
protected $fillable = [
    'id_producto',          // ID producto
    'numero_ventas',        // Número de ventas
    'valor_ventas_total',   // Valor total ventas
    'posicion',            // Posición en ranking
    'periodo',             // Período (mensual, trimestral)
    'fecha_calculo',       // Fecha de cálculo
];
```

---

## 3. Modelos Articulo (Oracle/Articulo)

### Articulo

**Namespace:** `Modules\Erp\Models\V2\Oracle\Articulo\Articulo`
**Tabla:** `articulo`
**Propósito:** Catálogo maestro de artículos

**Atributos Principales:**
```php
protected $fillable = [
    'codigo',              // Código único
    'codbar',             // Código de barras
    'descripcion',        // Descripción
    'idfamilia',          // ID familia
    'idsubfamilia',       // ID subfamilia
    'idmarca',            // ID marca
    'idmodelo',           // ID modelo
    'preciomedio',        // Precio medio
    'peso',               // Peso (kg)
    'volumen',            // Volumen (m3)
    'estado',             // Activo/Inactivo
    'es_arma',            // Es arma
    'es_cartucho',        // Es cartucho
    'es_arma_fogueo',     // Es arma de fogueo
    'unidades_oferta',    // Unidades en oferta
    'ean',                // Código EAN
    'upc',                // Código UPC
];
```

**Relaciones Disponibles:**
```php
public function familia()           // Familia artículo
public function subfamilia()        // Subfamilia
public function marca()             // Marca
public function modelo()            // Modelo
public function stock()             // Stock por almacén
public function precios()           // Precios históricos
public function imagenes()          // Imágenes asociadas
public function caracteristicas()   // Características
public function categorias()        // Categorías web
```

**Índices Disponibles:**
- `IDX_ARTICULO_CODIGO` - Búsqueda por código
- `IDX_ARTICULO_CODBAR` - Búsqueda por código barras
- `IDX_ARTICULO_FAMILIA` - Búsqueda por familia
- `IDX_ARTICULO_MARCA` - Búsqueda por marca
- `IDX_ARTICULO_ESTADO` - Filtrado por estado

---

### Stock

**Namespace:** `Modules\Erp\Models\V2\Oracle\Articulo\Stock`
**Tabla:** `stock` / `existencias`
**Propósito:** Disponibilidad por almacén

**Atributos:**
```php
protected $fillable = [
    'idarticulo',           // ID artículo
    'idalma',              // ID almacén
    'cantidad',            // Cantidad disponible
    'cantidad_reservada',  // Cantidad reservada
    'cantidad_minima',     // Cantidad mínima
    'cantidad_maxima',     // Cantidad máxima
    'rotacion',            // Código de rotación
    'costo_medio',         // Costo medio
];
```

**Relaciones:**
```php
public function articulo()
public function almacen()
```

---

## 4. Modelos Cliente (Oracle/Cliente)

### Cliente

**Namespace:** `Modules\Erp\Models\V2\Oracle\Cliente\Cliente`
**Tabla:** `cliente`
**Propósito:** Maestro de clientes

**Atributos Principales:**
```php
protected $fillable = [
    'codigo',              // Código cliente
    'nombre',             // Nombre comercial
    'nombre_facturacion', // Nombre para facturación
    'tipo_cliente',       // Tipo (mayorista, detallista, etc.)
    'nif',               // NIF/CIF
    'email',             // Email principal
    'telefono',          // Teléfono
    'movil',             // Móvil
    'direccion',         // Dirección
    'ciudad',            // Ciudad
    'codigo_postal',     // Código postal
    'provincia',         // Provincia
    'pais',              // País
    'limite_credito',    // Límite de crédito
    'saldo_deuda',       // Saldo actual
    'tipo_cliente',      // Tipo cliente
    'lopd_consentimiento', // LOPD consentimiento
    'lopd_fecha',        // Fecha LOPD
    'estado',            // Activo/Inactivo
];
```

**Relaciones:**
```php
public function direcciones()        // Direcciones de envío
public function contactos()          // Personas de contacto
public function pedidos()            // Pedidos asociados
public function facturas()           // Facturas
public function albaranes()          // Albaranes
public function catalogo()           // Catálogo personalizado
public function bonos()              // Bonos disponibles
public function limitesCredito()     // Límites de crédito
```

**Índices Disponibles:**
- `IDX_CLIENTE_CODIGO` - Búsqueda por código
- `IDX_CLIENTE_NIF` - Búsqueda por NIF/CIF
- `IDX_CLIENTE_EMAIL` - Búsqueda por email
- `IDX_CLIENTE_ESTADO` - Filtrado por estado

---

### Contacto

**Namespace:** `Modules\Erp\Models\V2\Oracle\Cliente\Contacto`
**Tabla:** `contacto`
**Propósito:** Personas de contacto de clientes

**Atributos:**
```php
protected $fillable = [
    'idcliente',    // ID cliente
    'nombre',      // Nombre contacto
    'cargo',       // Cargo
    'email',       // Email
    'telefono',    // Teléfono
    'movil',       // Móvil
    'activo',      // Activo/Inactivo
];
```

---

## 5. Modelos Documentos (Oracle/Documento)

### PedidoCliente

**Namespace:** `Modules\Erp\Models\V2\Oracle\Documento\PedidoCliente`
**Tabla:** `pedidocli`
**Propósito:** Pedidos de clientes

**Atributos Principales:**
```php
protected $fillable = [
    'codigo',                  // Código pedido
    'idcliente',              // ID cliente
    'fecha',                  // Fecha pedido
    'fecha_entrega_solicitada', // Fecha entrega solicitada
    'fecha_entrega_prevista', // Fecha entrega prevista
    'total',                  // Total documento
    'total_iva',              // Total IVA
    'total_neto',             // Total neto
    'porcentaje_descuento',   // % descuento
    'estado',                 // Estado
    'numero_lineas',          // Número de líneas
    'observaciones',          // Observaciones
    'almacen_salida',         // Almacén de salida
];
```

**Relaciones:**
```php
public function cliente()
public function lineas()           // Líneas de pedido
public function albaranes()        // Albaranes generados
public function facturas()         // Facturas
```

---

### Albarani

**Namespace:** `Modules\Erp\Models\V2\Oracle\Documento\Albarani`
**Tabla:** `albarani`
**Propósito:** Albaranes de venta

**Atributos:**
```php
protected $fillable = [
    'codigo',          // Código albarán
    'idcliente',      // ID cliente
    'fecha',          // Fecha albarán
    'total',          // Total
    'total_iva',      // Total IVA
    'total_neto',     // Total neto
    'estado',         // Estado
    'numero_bultos',  // Número de bultos
    'almacen',        // Almacén origen
];
```

**Relaciones:**
```php
public function cliente()
public function lineas()
public function pedidos()  // Pedidos origen
public function factura()  // Factura si existe
```

---

## 6. Modelos Mlog (Oracle/Mlog)

**Descripción:** Los modelos Mlog representan cambios replicados desde Oracle a través de Materialized View Logs. Se usan para sincronización y replicación de datos.

### Estructura General

```php
namespace Modules\Erp\Models\V2\Oracle\Mlog;

class Mlog{EntityName} extends Model
{
    protected $connection = 'oracle';
    protected $table = 'mlog${TABLE_NAME}';
    public $timestamps = false;

    // Campos de cambio de Mlog
    protected $fillable = [
        'row_id',      // ID original
        'changeseq#',  // Secuencia de cambio
        'operation$',  // Tipo de operación (I/U/D)
    ];
}
```

**Ejemplos de Mlog disponibles:**
- `MlogArticulo` - Cambios en artículos
- `MlogCliente` - Cambios en clientes
- `MlogProveedor` - Cambios en proveedores
- `MlogPedidocliCentral` - Cambios en pedidos
- `MlogAlbarani` - Cambios en albaranes

**Operaciones Registradas:**
- `I` = Insert (Nuevo)
- `U` = Update (Modificado)
- `D` = Delete (Eliminado)

---

## 7. Uso de Modelos en Controladores

### Ejemplo: Obtener Clientes con Relaciones

```php
use Modules\Erp\Models\V2\Oracle\Cliente\Cliente;

// Obtener con relaciones
$clientes = Cliente::with(['direcciones', 'contactos'])
    ->where('estado', 1)
    ->paginate(50);

// Búsqueda
$cliente = Cliente::where('codigo', 'CLI001')
    ->orWhere('nif', '12345678X')
    ->first();

// Con filtros complejos
$clientes = Cliente::query()
    ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
    ->where('estado', 1)
    ->orderBy('nombre')
    ->paginate(50);
```

### Ejemplo: Trabajar con Stock

```php
use Modules\Erp\Models\V2\Oracle\Articulo\Stock;

// Stock disponible por almacén
$stock = Stock::where('idarticulo', 5001)
    ->where('idalma', 'ALM001')
    ->first();

$disponible = $stock?->cantidad ?? 0;

// Stock total artículo
$stock_total = Stock::where('idarticulo', 5001)
    ->sum('cantidad');

// Stock bajo límite
$stock_bajo = Stock::where('cantidad', '<', 'cantidad_minima')
    ->with('articulo')
    ->get();
```

### Ejemplo: Crear Importación

```php
use Modules\Erp\Models\V2\Core\ProductImport;

$import = ProductImport::create([
    'importable_type' => 'Modules\Prestashop\Entities\Product',
    'importable_id' => 123,
    'id_articulo' => 5001,
    'codigo_proveedor' => 'PROV123',
    'precio_costo_proveedor' => 50.00,
    'activo' => 1,
    'estado_gestion' => 0,
    'sync_pending' => true,
]);

// Agregar tags
$import->tags()->sync([1, 2, 3]);
```

---

## 8. Mejores Prácticas

### Eager Loading
```php
// Evitar N+1 queries
$articulos = Articulo::with(['marca', 'modelo', 'familia'])->get();

// No hacer esto:
foreach ($articulos as $articulo) {
    $marca = $articulo->marca->nombre; // Query adicional
}
```

### Caching de Relaciones
```php
$clientes = Cliente::with(['direcciones'])->cache(3600)->get();
```

### Búsquedas Optimizadas
```php
// Usar índices disponibles
$cliente = Cliente::where('codigo', 'CLI001')->first();

// En lugar de:
$cliente = Cliente::where('nombre', 'like', '%ABC%')->first();
```

### Transacciones
```php
use Illuminate\Support\Facades\DB;

DB::connection('oracle')->transaction(function () {
    $cliente = Cliente::create([...]);
    $pedido = PedidoCliente::create([...]);
});
```

---

## 9. Migraciones y Schema

### Crear Modelo con Migración
```bash
php artisan make:model Models/V2/Oracle/TestModel -m
```

### Crear Migración Solo
```bash
php artisan make:migration create_test_table
```

### Factory
```bash
php artisan make:factory ModelNameFactory --model=Models/V2/Oracle/ModelName
```

---

## 10. Testeo de Modelos

```php
use Tests\TestCase;
use Modules\Erp\Models\V2\Oracle\Cliente\Cliente;

class ClienteTest extends TestCase
{
    public function test_can_create_cliente()
    {
        $cliente = Cliente::factory()->create();

        $this->assertDatabaseHas('cliente', [
            'idcliente' => $cliente->idcliente,
        ]);
    }
}
```

---

## Conexión Oracle Configuración

**config/database.php:**
```php
'oracle' => [
    'driver' => 'oracle',
    'host' => env('DB_ORACLE_HOST'),
    'port' => env('DB_ORACLE_PORT', 1521),
    'database' => env('DB_ORACLE_DATABASE'),
    'username' => env('DB_ORACLE_USERNAME'),
    'password' => env('DB_ORACLE_PASSWORD'),
    'charset' => env('DB_ORACLE_CHARSET', 'AL32UTF8'),
    'prefix' => env('DB_ORACLE_PREFIX', ''),
    'prefix_schema' => env('DB_ORACLE_PREFIX_SCHEMA', false),
],
```

---

**Última actualización:** 12 de enero de 2024
**Versión:** 2.0
**Estado:** Completo y Estable
