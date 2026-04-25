# Análisis Detallado - Plugin Ecommerce de Mercosan (Botble)

## 1. Identificación del Sistema Origen
- **Proyecto**: Mercosan (Botble CMS)
- **Plugin**: `platform/plugins/ecommerce`
- **Versión**: 3.9.1
- **Namespace**: `Botble\Ecommerce\`
- **Arquitectura**: Plugin sobre Botble CMS (no modular Laravel puro)
- **Archivos totales**: ~1,450 archivos

---

## 2. Estructura de Directorios del Plugin Original

```
platform/plugins/ecommerce/
├── config/              # Configuraciones del plugin
├── database/
│   ├── migrations/      # ~140 migraciones
│   └── seeders/         # Seeders de datos base
├── helpers/             # Funciones helper PHP
├── public/              # Assets públicos
├── resources/
│   ├── views/           # Vistas Blade (admin + frontend)
│   ├── js/              # JavaScript
│   ├── css/             # Estilos
│   └── lang/            # Traducciones
├── routes/
│   ├── ajax.php         # Rutas AJAX
│   ├── api.php          # API pública
│   ├── base.php         # Rutas base
│   ├── cart.php         # Carrito
│   ├── compare.php      # Comparar productos
│   ├── customer.php     # Cliente frontend
│   ├── discount.php     # Descuentos
│   ├── invoice.php      # Facturas
│   ├── order.php        # Órdenes
│   ├── product-*.php    # Productos, inventario, precios
│   ├── review.php       # Reseñas
│   ├── setting.php      # Configuraciones
│   ├── shipment.php     # Envíos
│   ├── shipping.php     # Métodos de envío
│   ├── tax.php          # Impuestos
│   └── wishlist.php     # Lista de deseos
└── src/
    ├── AdsTracking/     # Tracking de anuncios
    ├── Cart/            # Sistema de carrito (Contracts, Exceptions)
    ├── Charts/          # Gráficos de reportes
    ├── Commands/        # Comandos Artisan
    ├── Database/Seeders/# Seeders programáticos
    ├── Enums/           # Enumeraciones
    ├── Events/          # Eventos del dominio
    ├── Exceptions/      # Excepciones personalizadas
    ├── Exporters/       # Exportación de datos
    ├── Exports/         # Clases de exportación
    ├── Facades/         # Facades
    ├── Forms/           # Formularios (Botble FormBuilder)
    ├── Http/
    │   ├── Controllers/ # Controladores Web + API
    │   ├── Middleware/  # Middleware
    │   ├── Requests/    # Form Requests
    │   └── Resources/   # API Resources
    ├── Importers/       # Importación de datos
    ├── Imports/         # Clases de importación
    ├── Jobs/            # Jobs en cola
    ├── Listeners/       # Event listeners
    ├── Models/          # Modelos Eloquent (~50 modelos)
    ├── Notifications/   # Notificaciones
    ├── Option/          # Sistema de opciones de producto
    ├── PanelSections/   # Secciones del panel admin
    ├── Providers/       # Service Providers
    ├── Repositories/    # Repositorios (Cache + Eloquent)
    ├── Services/        # Servicios de negocio
    ├── Supports/        # Clases de soporte
    ├── Tables/          # DataTables (Botble TableBuilder)
    ├── Traits/          # Traits reutilizables
    ├── ValueObjects/    # Objetos de valor
    └── Widgets/         # Widgets del dashboard
```

---

## 3. Tablas de Base de Datos (Migraciones Principales)

### 3.1 Catálogo de Productos
| Tabla | Descripción |
|-------|-------------|
| `ec_products` | Productos principales (físicos, digitales, variaciones, agrupados) |
| `ec_product_categories` | Categorías de productos (árbol jerárquico) |
| `ec_product_categorizables` | Relación polimórfica categorías-productos |
| `ec_brands` | Marcas de productos |
| `ec_product_tags` | Etiquetas de productos |
| `ec_product_tag_product` | Relación tags-productos |
| `ec_product_collections` | Colecciones de productos |
| `ec_product_collection_products` | Relación colecciones-productos |
| `ec_product_labels` | Etiquetas visuales ("Nuevo", "Oferta", etc.) |
| `ec_product_label_products` | Relación etiquetas-productos |
| `ec_product_attribute_sets` | Conjuntos de atributos (Color, Talla) |
| `ec_product_attributes` | Atributos individuales (Rojo, XL) |
| `ec_product_with_attribute_set` | Relación productos-conjuntos |
| `ec_product_variations` | Variaciones de productos configurables |
| `ec_product_variation_items` | Items de variación (atributo + variación) |
| `ec_grouped_products` | Productos agrupados |
| `ec_product_views` | Vistas/contadores de productos |
| `ec_product_files` | Archivos digitales descargables |

### 3.2 Opciones de Producto
| Tabla | Descripción |
|-------|-------------|
| `ec_options` | Opciones personalizadas por producto |
| `ec_global_options` | Opciones globales reutilizables |
| `ec_option_value` | Valores de opciones |
| `ec_global_option_value` | Valores globales de opciones |

### 3.3 Especificaciones Técnicas
| Tabla | Descripción |
|-------|-------------|
| `ec_specification_groups` | Grupos de especificaciones |
| `ec_specification_attributes` | Atributos de especificación |
| `ec_specification_tables` | Tablas de especificación |
| `ec_specification_table_group` | Relación tablas-grupos |
| `ec_product_specification_attribute` | Relación productos-especificaciones |

### 3.4 Clientes
| Tabla | Descripción |
|-------|-------------|
| `ec_customers` | Clientes (autenticables) |
| `ec_customer_addresses` | Direcciones de clientes |
| `ec_customer_recently_viewed_products` | Productos vistos recientemente |
| `ec_customer_used_coupons` | Cupones usados por cliente |
| `ec_customer_deletion_requests` | Solicitudes de eliminación GDPR |
| `ec_wish_lists` | Listas de deseos |
| `ec_shared_wishlists` | Wishlists compartidas |

### 3.5 Carrito
| Tabla | Descripción |
|-------|-------------|
| `ec_cart` | Carritos persistentes |

### 3.6 Órdenes
| Tabla | Descripción |
|-------|-------------|
| `ec_orders` | Órdenes de compra |
| `ec_order_product` | Productos dentro de una orden |
| `ec_order_addresses` | Direcciones de envío/facturación de la orden |
| `ec_order_histories` | Historial de cambios de orden |
| `ec_order_referrals` | Datos de referral/UTM |
| `ec_order_tax_information` | Información fiscal de la orden |
| `ec_order_returns` | Devoluciones de orden |
| `ec_order_return_items` | Items de devolución |
| `ec_order_return_histories` | Historial de devoluciones |

### 3.7 Envíos
| Tabla | Descripción |
|-------|-------------|
| `ec_shipments` | Envíos/Shipments |
| `ec_shipment_histories` | Historial de envíos |
| `ec_shipping` | Métodos de envío |
| `ec_shipping_rules` | Reglas de envío |
| `ec_shipping_rule_items` | Items de reglas por ubicación |
| `ec_store_locators` | Ubicaciones de tienda |

### 3.8 Facturación
| Tabla | Descripción |
|-------|-------------|
| `ec_invoices` | Facturas |
| `ec_invoice_items` | Items de factura |

### 3.9 Impuestos
| Tabla | Descripción |
|-------|-------------|
| `ec_taxes` | Impuestos/Tasas |
| `ec_tax_rules` | Reglas de impuesto por ubicación |
| `ec_tax_products` | Relación productos-impuestos |

### 3.10 Descuentos y Promociones
| Tabla | Descripción |
|-------|-------------|
| `ec_discounts` | Descuentos/Cupones/Promociones |
| `ec_discount_products` | Productos incluidos en descuento |
| `ec_discount_product_categories` | Categorías incluidas |
| `ec_discount_product_collections` | Colecciones incluidas |
| `ec_discount_customers` | Clientes con descuento |
| `ec_customer_used_coupons` | Cupones utilizados |
| `ec_discount_excluded_products` | Productos excluidos |
| `ec_discount_excluded_product_categories` | Categorías excluidas |

### 3.11 Ventas Flash
| Tabla | Descripción |
|-------|-------------|
| `ec_flash_sales` | Ventas flash |
| `ec_flash_sale_products` | Productos en venta flash |

### 3.12 Reseñas
| Tabla | Descripción |
|-------|-------------|
| `ec_reviews` | Reseñas de productos |
| `ec_review_replies` | Respuestas a reseñas |

### 3.13 Monedas
| Tabla | Descripción |
|-------|-------------|
| `ec_currencies` | Monedas y tasas de cambio |

### 3.14 Traducciones
| Tabla | Descripción |
|-------|-------------|
| `ec_products_translations` | Traducciones de productos |
| `ec_product_categories_translations` | Traducciones de categorías |
| `ec_product_attributes_translations` | Traducciones de atributos |
| `ec_product_attribute_sets_translations` | Traducciones de conjuntos |
| `ec_brands_translations` | Traducciones de marcas |
| `ec_product_collections_translations` | Traducciones de colecciones |
| `ec_product_labels_translations` | Traducciones de etiquetas |
| `ec_product_tags_translations` | Traducciones de tags |
| `ec_taxes_translations` | Traducciones de impuestos |
| `ec_options_translations` | Traducciones de opciones |
| `ec_global_options_translations` | Traducciones de opciones globales |
| `ec_option_value_translations` | Traducciones de valores |
| `ec_global_option_value_translations` | Traducciones de valores globales |
| `ec_specification_attributes_translations` | Traducciones de especificaciones |

---

## 4. Modelos Principales (~50 modelos)

### Core del Catálogo
- `Product` - Producto con variantes, precios, stock, imágenes, SEO
- `ProductCategory` - Categoría jerárquica (árbol)
- `Brand` - Marca
- `ProductTag` - Etiqueta
- `ProductCollection` - Colección curada
- `ProductLabel` - Etiqueta visual
- `ProductAttributeSet` - Conjunto de atributos (Color, Talla)
- `ProductAttribute` - Atributo (Rojo, XL)
- `ProductVariation` - Variación de producto configurable
- `ProductVariationItem` - Item que une atributo a variación
- `GroupedProduct` - Producto agrupado
- `ProductFile` - Archivo digital descargable
- `Option` / `GlobalOption` - Opciones personalizadas
- `OptionValue` / `GlobalOptionValue` - Valores de opciones
- `SpecificationGroup` / `SpecificationAttribute` / `SpecificationTable` - Especificaciones técnicas

### Clientes y Usuarios
- `Customer` - Cliente autenticable (con orders, addresses, wishlist)
- `Address` / `OrderAddress` - Direcciones
- `CustomerDeletionRequest` - Solicitud GDPR

### Carrito y Wishlist
- `Cart` - Carrito persistente en DB
- `Wishlist` - Lista de deseos
- `SharedWishlist` - Wishlist compartida por código

### Órdenes
- `Order` - Orden de compra con estados, pagos, envíos
- `OrderProduct` - Línea de producto en orden
- `OrderHistory` - Historial de cambios
- `OrderReferral` - Datos UTM/referral
- `OrderTaxInformation` - Info fiscal
- `OrderReturn` / `OrderReturnItem` / `OrderReturnHistory` - Devoluciones

### Envíos
- `Shipment` - Envío físico con tracking
- `ShipmentHistory` - Historial de tracking
- `Shipping` - Método de envío
- `ShippingRule` - Regla de precio de envío
- `ShippingRuleItem` - Ajuste por ubicación
- `StoreLocator` - Ubicación física de tienda

### Facturación
- `Invoice` - Factura generada
- `InvoiceItem` - Línea de factura

### Impuestos
- `Tax` - Tasa de impuesto
- `TaxRule` - Regla por ubicación

### Descuentos
- `Discount` - Cupón/Promoción con tipos (fijo, porcentaje, envío gratis)
- `DiscountProduct` - Producto incluido
- `DiscountCustomer` - Cliente asignado

### Ventas Flash
- `FlashSale` - Venta flash con fecha límite
- Relación many-to-many con products vía `ec_flash_sale_products`

### Reseñas
- `Review` - Reseña con estrellas, imágenes, estado
- `ReviewReply` - Respuesta del admin

### Otros
- `Currency` - Moneda con tasa de cambio

---

## 5. Controladores y Funcionalidades

### Admin (Panel de Control)
| Controlador | Funcionalidad |
|-------------|---------------|
| `ProductController` | CRUD productos, duplicar, variaciones, imágenes |
| `ProductCategoryController` | CRUD categorías (árbol jerárquico) |
| `BrandController` | CRUD marcas |
| `ProductTagController` | CRUD tags |
| `ProductCollectionController` | CRUD colecciones |
| `ProductLabelController` | CRUD etiquetas visuales |
| `ProductAttributeSetsController` | CRUD conjuntos de atributos |
| `ProductOptionController` | CRUD opciones globales |
| `ProductInventoryController` | Gestión de inventario/stock |
| `ProductPriceController` | Gestión masiva de precios |
| `CustomerController` | CRUD clientes, direcciones, verificación email |
| `OrderController` | CRUD órdenes, crear orden manual, confirmar, cancelar, envíos |
| `ShipmentController` | Gestión de envíos y tracking |
| `InvoiceController` | CRUD facturas, generar PDF |
| `DiscountController` | CRUD descuentos/cupones, generar códigos masivos |
| `FlashSaleController` | CRUD ventas flash |
| `ReviewController` | Moderación de reseñas |
| `ReviewReplyController` | Respuestas a reseñas |
| `TaxController` / `TaxRuleController` | CRUD impuestos y reglas |
| `ShippingMethodController` / `ShippingRuleItemController` | Configuración de envíos |
| `StoreLocatorController` | Ubicaciones de tienda |
| `OrderReturnController` | Gestión de devoluciones |
| `ReportController` | Reportes de ventas, productos top, órdenes recientes |
| `Import/Export*Controller` | Importación/exportación masiva de productos |
| `Setting*Controller` | ~20 controladores de configuración (general, checkout, envío, factura, moneda, etc.) |

### Frontend (Tienda Pública)
| Controlador | Funcionalidad |
|-------------|---------------|
| `PublicProductController` | Listado, detalle, filtros, variaciones, productos relacionados |
| `PublicCartController` | Carrito de compras (añadir, actualizar, eliminar, vaciar) |
| `PublicCheckoutController` | Proceso de checkout completo (info, pago, éxito) |
| `WishlistController` | Lista de deseos |
| `CompareController` | Comparar productos |
| `ReviewController` (front) | Enviar reseñas |
| `OrderTrackingController` | Rastrear orden |
| `DownloadController` | Descargar productos digitales |
| `AccountDeletionController` | Solicitud GDPR |
| `PublicController` (customer) | Perfil, direcciones, órdenes, devoluciones |
| `LoginController` / `RegisterController` / `ForgotPasswordController` | Autenticación de clientes |

### API
- Endpoints REST para: productos, categorías, carrito, checkout, órdenes, cliente, wishlist, comparar, reseñas

---

## 6. Servicios de Negocio Principales

| Servicio | Propósito |
|----------|-----------|
| `CreatePaymentForOrderService` | Crear pago para orden |
| `UpdateDefaultProductService` | Actualizar producto default de variación |
| `HandleApplyCouponService` | Aplicar cupón |
| `HandleRemoveCouponService` | Remover cupón |
| `HandleShippingFeeService` | Calcular costo de envío |
| `HandleCheckoutOrderData` | Procesar datos de checkout |
| `TaxCalculatorService` | Calcular impuestos |
| `ProductCrossSalePriceService` | Precios de venta cruzada |
| `DuplicateProductService` | Duplicar producto |
| `StoreCurrenciesService` | Gestionar monedas |
| `ExchangeRateInterface` | Tasas de cambio |
| `GetProductService` | Obtener productos filtrados |
| `CreateShipmentService` | Crear envío |
| `EcommerceHelper` / `OrderHelper` / `InvoiceHelper` / `CartHelper` | Helpers globales |

---

## 7. Características Avanzadas

### Tipos de Producto
1. **Simple**: Producto básico con stock
2. **Configurable**: Producto con variaciones (talla, color)
3. **Digital**: Producto descargable con archivos y licencias
4. **Grouped/Agrupado**: Conjunto de productos vendidos juntos

### Gestión de Stock
- Stock por producto y por variación
- Alertas de stock bajo
- Permitir checkout sin stock (configurable)
- Gestión de almacén (storehouse)
- Inventario masivo (import/export)

### Checkout
- Guest checkout (sin registro)
- Checkout con registro/login
- Múltiples direcciones
- Selección de método de envío en tiempo real
- Aplicación de cupones
- Cálculo de impuestos por ubicación
- Campos condicionales configurables
- Fecha/hora de entrega
- Subida de archivos (proof)

### Pagos
- Integración con múltiples gateways (integrado con plugin Payment)
- Pago contra entrega (COD)
- Fee de pago configurable

### Envíos
- Múltiples métodos de envío
- Reglas por peso, precio, cantidad
- Ajustes por país/estado/ciudad/código postal
- Tracking de envíos
- Confirmación de entrega por cliente (token)
- Etiquetas de envío imprimibles

### Impuestos
- Tasas de impuesto múltiples
- Reglas por ubicación geográfica
- Prioridad de reglas
- Inclusión/exclusión de impuestos en precios

### Marketing
- Cupones de descuento (fijo, porcentaje, envío gratis)
- Ventas flash con contador
- Productos relacionados (cross-sale, up-sale)
- Productos vistos recientemente
- Wishlist y wishlist compartida
- Comparar productos
- Abandoned cart emails
- Referral/UTM tracking

### SEO
- URLs amigables (slug) para productos, categorías, marcas
- Meta títulos y descripciones
- Schema.org para productos
- Sitemap de productos

### Multilingüe
- Traducciones para productos, categorías, atributos, marcas, colecciones, etiquetas, impuestos, opciones, especificaciones
- Soporte completo de i18n

### GDPR
- Solicitud de eliminación de cuenta
- Anonimización de datos
- Token de confirmación

---

## 8. Enums Identificados

| Enum | Valores |
|------|---------|
| `CustomerStatusEnum` | activated, locked, pending |
| `OrderStatusEnum` | pending, processing, shipping, completing, completed, canceling, canceled |
| `ShippingStatusEnum` | not_approved, approved, picking, delay_picking, picked, not_picked, delivering, delivered, not_delivered, canceled, aborted, returned |
| `ShippingCodStatusEnum` | pending, completed |
| `ShippingMethodEnum` | default |
| `ShippingRuleTypeEnum` | base_on_price, base_on_weight, base_on_quantity |
| `InvoiceStatusEnum` | pending, paid |
| `ProductTypeEnum` | physical, digital |
| `StockStatusEnum` | in_stock, out_of_stock, on_backorder |
| `DiscountTypeEnum` | promotion, coupon |
| `DiscountTargetEnum` | all_products, specific_product, product_variation, product_collection, product_category, customer, customer_group |
| `OrderReturnReasonEnum` | defective, wrong_product, wrong_size, other |
| `OrderReturnHistoryActionEnum` | created, updated, cancelled |
| `OrderAddressTypeEnum` | shipping_address, billing_address |
| `OrderCancellationReasonEnum` | customer_cancelled, order_placed_by_mistake, delivery_time_too_long, product_no_longer_needed, other |
| `DeletionRequestStatusEnum` | waiting_for_confirmation, confirmed, processing, completed |
| `SpecificationAttributeFieldType` | text, textarea, select, checkbox, radio, switch, file, image, date, datetime, time, color, icon, number, email, url, password, range, repeater |

---

## 9. Eventos y Listeners

| Evento | Descripción |
|--------|-------------|
| `OrderPlacedEvent` | Orden creada |
| `OrderPaidEvent` | Orden pagada |
| `OrderCompletedEvent` | Orden completada |
| `OrderCancelledEvent` | Orden cancelada |
| `OrderReturnedEvent` | Orden devuelta |
| `ProductQuantityUpdatedEvent` | Stock actualizado |
| `ProductViewedEvent` | Producto visto |
| `CouponAppliedEvent` | Cupón aplicado |
| `ShippingStatusChangedEvent` | Estado de envío cambiado |
| `CustomerRegisteredEvent` | Cliente registrado |

---

## 10. Jobs en Cola

| Job | Función |
|-----|---------|
| `CreateInvoiceJob` | Generar factura |
| `SendOrderConfirmationEmailJob` | Email de confirmación |
| `SendShippingConfirmationEmailJob` | Email de envío |
| `SendAbandonedCartEmailJob` | Email carrito abandonado |
| `UpdateCurrencyRatesJob` | Actualizar tasas de cambio |
| `ImportProductsJob` | Importación masiva |
| `ExportProductsJob` | Exportación masiva |

---

## 11. Notificaciones

| Notificación | Canal |
|--------------|-------|
| `ConfirmEmailNotification` | Email |
| `ResetPasswordNotification` | Email |
| `OrderCreatedNotification` | Email + Database |
| `OrderStatusChangedNotification` | Email + Database |
| `ShippingStatusChangedNotification` | Email + Database |
| `InvoiceCreatedNotification` | Email |
| `ProductLowStockNotification` | Email |
| `NewOrderNotification` | Email (admin) |

---

## 12. Widgets del Dashboard

| Widget | Métrica |
|--------|---------|
| `RevenueWidget` | Ingresos totales |
| `OrdersWidget` | Órdenes del período |
| `ProductsWidget` | Productos vendidos |
| `CustomersWidget` | Nuevos clientes |
| `TopSellingProductsWidget` | Productos más vendidos |
| `RecentOrdersWidget` | Órdenes recientes |
| `TrendingProductsWidget` | Productos en tendencia |

---

## 13. Helpers Principales

| Helper | Función |
|--------|---------|
| `format_price()` | Formatear moneda |
| `get_ecommerce_setting()` | Obtener config |
| `is_plugin_active()` | Verificar plugin activo |
| `product_thumb_image()` | Miniatura de producto |
| `get_product_attributes()` | Atributos de producto |
| `get_cart_count()` | Cantidad en carrito |
| `get_order_status()` | Estado de orden formateado |
| `get_shipping_methods()` | Métodos disponibles |
| `get_payment_methods()` | Métodos de pago |

---

## 14. Decisiones de Arquitectura para el Módulo SYSTEM

### Alcance MVP (Fase 1)
Dado la enorme extensión del plugin original (~1450 archivos), el módulo `Ecommerce` en SYSTEM se implementará por fases:

**Fase 1 - Core Ecommerce:**
1. **Catálogo**: Productos, Categorías, Marcas, Tags, Colecciones
2. **Clientes**: Gestión de clientes y direcciones
3. **Carrito**: Carrito de compras (sesión + DB)
4. **Órdenes**: Órdenes completas con estados
5. **Checkout**: Proceso de checkout básico
6. **Descuentos**: Cupones de descuento
7. **Envíos**: Métodos de envío básicos
8. **Impuestos**: Tasas de impuesto simples
9. **Reseñas**: Reseñas de productos
10. **Facturas**: Generación básica de facturas

### Patrón a Seguir
- Basado en el módulo `Blog` de SYSTEM
- Usar `nwidart/laravel-modules` v12
- Namespace: `Modules\Ecommerce\`
- Alias: `ecommerce`
- Rutas admin: `panel/ecommerce/...`
- Rutas públicas: `tienda/...` o `shop/...`
- Bootstrap 5.3 + jQuery + DevExpress DataGrid
- Font Awesome 6 icons
- Spatie Permissions
- Form Requests para validación
- Services para lógica de negocio
- Eloquent con relaciones tipadas
- Factories y seeders para testing

### Estructura del Módulo
```
modules/Ecommerce/
├── module.json
├── composer.json
├── config/config.php
├── app/
│   ├── Providers/EcommerceServiceProvider.php
│   ├── Models/ (15-20 modelos iniciales)
│   ├── Http/Controllers/ (Web + API)
│   ├── Http/Requests/
│   ├── Http/Resources/
│   ├── Services/
│   ├── Policies/
│   ├── Events/
│   ├── Listeners/
│   ├── Jobs/
│   └── Notifications/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/EcommercePermissionsSeeder.php
├── resources/
│   ├── views/ (admin + public)
│   ├── js/
│   └── lang/es/messages.php
├── routes/
│   ├── web.php
│   └── api.php
└── tests/
    ├── Feature/
    └── Unit/
```

---

## 15. Mapeo de Tablas para SYSTEM

Se simplificarán los nombres eliminando el prefijo `ec_` y usando convenciones del proyecto:

| Original Mercosan | Módulo SYSTEM |
|-------------------|---------------|
| `ec_products` | `ecommerce_products` |
| `ec_product_categories` | `ecommerce_product_categories` |
| `ec_brands` | `ecommerce_brands` |
| `ec_product_tags` | `ecommerce_product_tags` |
| `ec_product_collections` | `ecommerce_collections` |
| `ec_customers` | `ecommerce_customers` |
| `ec_customer_addresses` | `ecommerce_customer_addresses` |
| `ec_orders` | `ecommerce_orders` |
| `ec_order_product` | `ecommerce_order_items` |
| `ec_order_addresses` | `ecommerce_order_addresses` |
| `ec_order_histories` | `ecommerce_order_histories` |
| `ec_cart` | `ecommerce_carts` |
| `ec_discounts` | `ecommerce_discounts` |
| `ec_invoices` | `ecommerce_invoices` |
| `ec_invoice_items` | `ecommerce_invoice_items` |
| `ec_shipments` | `ecommerce_shipments` |
| `ec_shipping` | `ecommerce_shipping_methods` |
| `ec_shipping_rules` | `ecommerce_shipping_rules` |
| `ec_reviews` | `ecommerce_reviews` |
| `ec_taxes` | `ecommerce_taxes` |
| `ec_currencies` | `ecommerce_currencies` |
| `ec_flash_sales` | `ecommerce_flash_sales` |
| `ec_product_attribute_sets` | `ecommerce_attribute_sets` |
| `ec_product_attributes` | `ecommerce_attributes` |
| `ec_product_variations` | `ecommerce_product_variations` |

---

*Documento generado el 2026-04-24*
*Análisis completo del plugin ecommerce de Mercosan para replicación en módulo SYSTEM*
