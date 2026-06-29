<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API REST de la plataforma Ecommerce y Helpdesk. Endpoints públicos y autenticados con Sanctum.',
    title: 'System API',
    contact: new OA\Contact(email: 'info@example.com'),
    license: new OA\License(name: 'Proprietary')
)]
#[OA\Server(
    url: 'https://system.test',
    description: 'Local development'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
#[OA\Tag(name: 'Productos', description: 'Catálogo de productos')]
#[OA\Tag(name: 'Carrito', description: 'Operaciones de carrito')]
#[OA\Tag(name: 'Órdenes', description: 'Gestión de órdenes (autenticado)')]
#[OA\Tag(name: 'Cliente', description: 'Perfil del cliente (autenticado)')]
#[OA\Tag(name: 'Wishlist', description: 'Lista de deseos (autenticado)')]
#[OA\Tag(name: 'Locations', description: 'Países, estados, ciudades')]
#[OA\Tag(name: 'HelpdeskErp', description: 'Endpoints de contexto ERP via manager Oracle')]
#[OA\Tag(name: 'HelpdeskPrestashop', description: 'Endpoints de contexto PrestaShop via alsernetbridge')]
#[OA\Tag(name: 'HelpdeskWebhooks', description: 'Receivers de webhooks externos')]
class OpenApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/products",
     *     summary="Listar productos",
     *     tags={"Productos"},
     *
     *     @OA\Parameter(name="q", in="query", description="Búsqueda por nombre o SKU", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items por página", @OA\Schema(type="integer", default=12)),
     *
     *     @OA\Response(response=200, description="Listado de productos paginado")
     * )
     */
    public function listProducts(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/products/suggestions",
     *     summary="Autocomplete de productos",
     *     tags={"Productos"},
     *
     *     @OA\Parameter(name="q", in="query", required=true, description="Texto a buscar (min 2 chars)", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Lista de sugerencias hasta 8 productos")
     * )
     */
    public function productSuggestions(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/cart",
     *     summary="Obtener carrito actual",
     *     tags={"Carrito"},
     *
     *     @OA\Response(response=200, description="Items del carrito")
     * )
     */
    public function getCart(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/cart/count",
     *     summary="Contador de items en carrito",
     *     tags={"Carrito"},
     *
     *     @OA\Response(response=200, description="{count: int}")
     * )
     */
    public function cartCount(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/ecommerce/cart",
     *     summary="Agregar producto al carrito",
     *     tags={"Carrito"},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"product_id"},
     *
     *         @OA\Property(property="product_id", type="integer"),
     *         @OA\Property(property="qty", type="integer", default=1)
     *     )),
     *
     *     @OA\Response(response=200, description="Producto agregado")
     * )
     */
    public function addToCart(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/orders",
     *     summary="Listar órdenes del cliente autenticado",
     *     tags={"Órdenes"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Listado de órdenes")
     * )
     */
    public function listOrders(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/ecommerce/orders",
     *     summary="Crear orden (idempotente)",
     *     tags={"Órdenes"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="Idempotency-Key", in="header", description="Clave única para idempotencia", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=201, description="Orden creada"),
     *     @OA\Response(response=422, description="Validación")
     * )
     */
    public function createOrder(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/profile",
     *     summary="Perfil del cliente",
     *     tags={"Cliente"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Datos del cliente")
     * )
     */
    public function getProfile(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/wishlist",
     *     summary="Lista de deseos del cliente",
     *     tags={"Wishlist"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Productos en wishlist")
     * )
     */
    public function getWishlist(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce/countries",
     *     summary="Listar países",
     *     tags={"Locations"},
     *
     *     @OA\Response(response=200, description="Lista de países activos")
     * )
     */
    public function listCountries(): void {}
}
