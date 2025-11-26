<?php
/**
 * CheckoutValidationService - Servicio centralizado de validaciones
 *
 * Refactorización para eliminar código duplicado y mejorar performance
 * Versión: 2.1.0
 * Fecha: 2025-08-12
 */

class CheckoutValidationService
{
    // Constantes de tipos de error estandarizados
    const ERROR_NO_ADDRESSES_REGISTERED = 'no_addresses_registered';
    const ERROR_MISSING_INVOICE_ADDRESS = 'missing_invoice_address';
    const ERROR_MISSING_VAT = 'missing_vat';
    const ERROR_BLOCKED_PRODUCTS = 'blocked';
    const ERROR_PRODUCT_AVAILABILITY = 'product_availability';
    const ERROR_MISSING_DELIVERY_ADDRESS = 'missing_delivery_address';

    // Cache interno para optimización
    private static $validationCache = [];
    private static $cacheTimeout = 30; // 30 segundos

    /**
     * Valida el checkout completo - método principal
     *
     * @param Cart $cart
     * @param Context $context
     * @param string $iso Language iso code
     * @return array Array con resultado de validación
     */
    public static function validateCheckout($cart, $context, $iso = null)
    {
        $result = self::performValidation($cart, $context);

        $response = $result === null ?
            ['hasError' => false, 'data' => null] :
            ['hasError' => true, 'data' => $result];

        return $response;
    }

    /**
     * Realiza las validaciones en orden de prioridad
     */
    private static function performValidation($cart, $context)
    {
        // 0. Verificar si el cliente tiene direcciones registradas (máxima prioridad)
        $addressExistValidation = self::validateAddressesExist($cart, $context);
        if ($addressExistValidation !== null) {
            return $addressExistValidation;
        }

        // 1. Verificar productos bloqueados (prioridad alta)
        $blockedValidation = self::validateBlockedProducts($cart, $context);

        if ($blockedValidation !== null) {
            return $blockedValidation;
        }

        // 2. Verificar dirección de entrega
        $deliveryValidation = self::validateDeliveryAddress($cart, $context);
        if ($deliveryValidation !== null) {
            return $deliveryValidation;
        }

        // 3. Verificar dirección de facturación
        $invoiceValidation = self::validateInvoiceAddress($cart, $context);
        if ($invoiceValidation !== null) {
            return $invoiceValidation;
        }

        // 4. Verificar VAT number
        $vatValidation = self::validateVATNumber($cart, $context);
        if ($vatValidation !== null) {
            return $vatValidation;
        }

        // 5. Verificar disponibilidad de productos
        $availabilityValidation = self::validateProductAvailability($cart, $context);
        if ($availabilityValidation !== null) {
            return $availabilityValidation;
        }

        return null; // Todo OK
    }

    /**
     * Valida si el cliente tiene direcciones registradas
     */
    public static function validateAddressesExist($cart, $context)
    {
        $customer = $context->customer;
        if (!$customer->id) {
            return null; // Usuario no logueado, no validar
        }

        // Obtener todas las direcciones del cliente
        $addresses = $customer->getAddresses($context->language->id);

        // Si no tiene ninguna dirección registrada
        if (empty($addresses)) {
            return [
                'type' => self::ERROR_NO_ADDRESSES_REGISTERED
                // No necesitamos modal_html porque usamos modal estático
            ];
        }

        return null;
    }

    /**
     * Valida productos bloqueados
     */
    public static function validateBlockedProducts($cart, $context)
    {
        $blockedProducts = self::getBlockedProducts($cart, $context->language->id);

        if (!empty($blockedProducts)) {
            return [
                'type' => self::ERROR_BLOCKED_PRODUCTS,
                'blocked_products' => $blockedProducts,
                'modal_html' => self::generateBlockedModal($blockedProducts)
            ];
        }

        return null;
    }

    /**
     * Valida dirección de entrega
     */
    public static function validateDeliveryAddress($cart, $context)
    {
        $customer = $context->customer;
        if (!$customer->id) {
            return null; // Usuario no logueado
        }

        $deliveryAddress = new Address($cart->id_address_delivery);

        if (!$deliveryAddress->id || $deliveryAddress->deleted) {
            return [
                'type' => self::ERROR_MISSING_DELIVERY_ADDRESS,
                'modal_html' => self::generateMissingDeliveryModal()
            ];
        }

        return null;
    }

    /**
     * Valida dirección de facturación
     */
    public static function validateInvoiceAddress($cart, $context)
    {
        // Verificar PS_INVOICE por separado
        $psInvoiceEnabled = (bool)Configuration::get('PS_INVOICE');

        // Usar need_invoice del carrito si está disponible, sino calcularlo
        if (isset($cart->need_invoice) && $cart->need_invoice !== null) {
            $needInvoice = (bool)$cart->need_invoice;
        } else {
            // Verificar condiciones específicas de negocio para need_invoice
            $needInvoice = self::checkNeedInvoiceByProductType($cart)
                || self::checkNeedInvoiceByOrderTotal($cart);
        }

        // DEBUG: Log para verificar condiciones
        error_log('CheckoutValidationService::validateInvoiceAddress - PS_INVOICE: ' . ($psInvoiceEnabled ? 'true' : 'false') . ', need_invoice: ' . ($needInvoice ? 'true' : 'false') . ', id_address_invoice: ' . ($cart->id_address_invoice ?? 'null'));

        // Solo validar si PS_INVOICE está habilitado O si need_invoice es true
        if (!$psInvoiceEnabled && !$needInvoice) {
            error_log('CheckoutValidationService::validateInvoiceAddress - SKIPPED: Neither PS_INVOICE nor need_invoice is true');
            return null;
        }

        $customer = $context->customer;
        if (!$customer->id) {
            error_log('CheckoutValidationService::validateInvoiceAddress - SKIPPED: Customer not logged');
            return null; // Usuario no logueado
        }

        // Verificar primero si el cart tiene id_address_invoice
        if (!isset($cart->id_address_invoice) || !$cart->id_address_invoice || $cart->id_address_invoice == 0) {
            // DEBUG: Log para verificar que se está ejecutando
            error_log('CheckoutValidationService: ERROR_MISSING_INVOICE_ADDRESS triggered - id_address_invoice: ' . ($cart->id_address_invoice ?? 'null'));

            return [
                'type' => self::ERROR_MISSING_INVOICE_ADDRESS,
                'modal_html' => self::generateMissingInvoiceModal()
            ];
        }

        // Verificar si la dirección existe y no está eliminada
        $invoiceAddress = new Address($cart->id_address_invoice);

        if (!$invoiceAddress->id || $invoiceAddress->deleted) {
            return [
                'type' => self::ERROR_MISSING_INVOICE_ADDRESS,
                'modal_html' => self::generateMissingInvoiceModal()
            ];
        }

        return null;
    }

    /**
     * Valida VAT number
     */
    public static function validateVATNumber($cart, $context)
    {
        // Verificar PS_INVOICE por separado
        $psInvoiceEnabled = (bool)Configuration::get('PS_INVOICE');

        // Usar need_invoice del carrito si está disponible, sino calcularlo
        if (isset($cart->need_invoice) && $cart->need_invoice !== null) {
            $needInvoice = (bool)$cart->need_invoice;
        } else {
            // Verificar condiciones específicas de negocio para need_invoice
            $needInvoice = self::checkNeedInvoiceByProductType($cart)
                || self::checkNeedInvoiceByOrderTotal($cart);
        }

        // Solo validar si PS_INVOICE está habilitado O si need_invoice es true
        if (!$psInvoiceEnabled && !$needInvoice) {
            return null;
        }

        // Verificar primero si el cart tiene id_address_invoice
        if (!isset($cart->id_address_invoice) || !$cart->id_address_invoice || $cart->id_address_invoice == 0) {
            return [
                'type' => self::ERROR_MISSING_INVOICE_ADDRESS,
                'modal_html' => self::generateMissingInvoiceModal()
            ];
        }

        $invoiceAddress = new Address($cart->id_address_invoice);

        // Verificar si la dirección existe y no está eliminada
        if (!$invoiceAddress->id || $invoiceAddress->deleted) {
            // Si la dirección no existe, redirigir al error de dirección de facturación faltante
            return [
                'type' => self::ERROR_MISSING_INVOICE_ADDRESS,
                'modal_html' => self::generateMissingInvoiceModal()
            ];
        }

        if (empty($invoiceAddress->vat_number)) {
            return [
                'type' => self::ERROR_MISSING_VAT,
                'current_address' => [
                    'id' => $invoiceAddress->id,
                    'alias' => $invoiceAddress->alias,
                    'address1' => $invoiceAddress->address1,
                    'postcode' => $invoiceAddress->postcode,
                    'city' => $invoiceAddress->city,
                    'firstname' => $invoiceAddress->firstname,
                    'lastname' => $invoiceAddress->lastname
                ],
                'modal_html' => self::generateMissingVATModal($invoiceAddress)
            ];
        }

        return null;
    }

    /**
     * Valida disponibilidad de productos
     */
    public static function validateProductAvailability($cart, $context)
    {
        $products = $cart->getProducts(true);

        foreach ($products as $product) {
            $productObj = new Product($product['id_product']);

            if (!$productObj->checkQty($product['cart_quantity'])) {
                return [
                    'type' => self::ERROR_PRODUCT_AVAILABILITY,
                    'product' => $product,
                    'modal_html' => self::generateAvailabilityModal($product)
                ];
            }
        }

        return null;
    }

    /**
     * Obtiene productos bloqueados
     */
    public static function getBlockedProducts($cart, $languageId)
    {
        $products = $cart->getProducts(true);
        $blockedProducts = [];

        foreach ($products as $product) {
            $productObj = new Product($product['id_product'], false, $languageId);

            if (method_exists($productObj, 'isBlocked') && $productObj->isBlocked($product['id_product'])) {
                // Obtener imagen del producto usando la estructura estándar de PrestaShop
                $images = $productObj->getImages($languageId);
                $defaultImage = false; // Usar false en lugar de null para Smarty

                if (!empty($images)) {
                    $firstImage = reset($images);
                    // Construir path de imagen similar a como lo hace PrestaShop
                    $imageId = $firstImage['id_image'];
                    $imagePath = implode('/', str_split($imageId));

                    $defaultImage = [
                        'legend' => $firstImage['legend'] ?? $product['name'],
                        'medium' => [
                            'url' => '/img/p/' . $imagePath . '/' . $imageId . '-medium_default.jpg'
                        ]
                    ];
                }

                // Obtener atributos del producto si existen
                $attributes = '';
                $attributesSmall = '';
                if ($product['id_product_attribute'] && $product['id_product_attribute'] > 0) {
                    $attributes = $product['attributes'] ?? '';
                    $attributesSmall = $product['attributes_small'] ?? $attributes;
                }

                // Calcular información de descuentos - validar valores numéricos
                $currentPrice = is_numeric($product['price']) ? (float)$product['price'] : 0.0;
                $regularPrice = 0.0;

                if (isset($product['price_without_reduction']) && is_numeric($product['price_without_reduction'])) {
                    $regularPrice = (float)$product['price_without_reduction'];
                } else {
                    $regularPrice = $currentPrice;
                }

                $hasDiscount = $regularPrice > $currentPrice && $regularPrice > 0;

                $discountType = 'amount'; // Por defecto descuento en cantidad
                $discountPercentage = 0.0;
                $discountAmount = 0.0;

                if ($hasDiscount && $regularPrice > 0) {
                    $discountAmount = $regularPrice - $currentPrice;
                    $discountPercentage = round(($discountAmount / $regularPrice) * 100, 2);

                    // Determinar si es un descuento porcentual o de cantidad
                    // Si el descuento es un número redondo de porcentaje, mostrar como porcentaje
                    if ($discountPercentage == round($discountPercentage)) {
                        $discountType = 'percentage';
                    }
                }

                $blockedProducts[] = [
                    'id_product' => $product['id_product'],
                    'id_product_attribute' => $product['id_product_attribute'],
                    'name' => $product['name'],
                    'reference' => $product['reference'] ?? '',
                    'cart_quantity' => isset($product['cart_quantity']) ? (int)$product['cart_quantity'] : 0,
                    'price' => $currentPrice > 0 ? Tools::displayPrice($currentPrice) : Tools::displayPrice(0),
                    'price_raw' => $currentPrice,
                    'default_image' => $defaultImage,
                    'attributes' => $attributes,
                    'attributes_small' => $attributesSmall,
                    'has_discount' => $hasDiscount,
                    'regular_price' => $regularPrice > 0 ? Tools::displayPrice($regularPrice) : Tools::displayPrice(0),
                    'regular_price_raw' => $regularPrice,
                    'discount_type' => $discountType,
                    'discount_percentage' => $discountPercentage,
                    'discount_percentage_absolute' => abs($discountPercentage),
                    'discount_amount' => $discountAmount,
                    'discount_to_display' => $discountAmount > 0 ? Tools::displayPrice($discountAmount) : Tools::displayPrice(0)
                ];
            }
        }

        return $blockedProducts;
    }

    /**
     * Genera HTML para modal de productos bloqueados
     */
    private static function generateBlockedModal($blockedProducts)
    {
        $html = '<div class="blocked-products-list">';

        foreach ($blockedProducts as $product) {
            $html .= sprintf(
                '<div class="blocked-product-item" data-product-id="%d" data-product-attr-id="%d">
                    <div class="product-info">
                        <strong>%s</strong>
                        <div class="product-details">
                            <span>Ref: %s</span> | 
                            <span>Qty: %d</span> | 
                            <span>€%s</span>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-danger delete-blocked-product">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>',
                $product['id_product'],
                $product['id_product_attribute'],
                $product['name'],
                $product['reference'],
                $product['cart_quantity'],
                number_format($product['price_raw'], 2)
            );
        }

        $html .= '</div>';
        return $html;
    }


    /**
     * Genera HTML para modal de dirección de entrega faltante
     */
    private static function generateMissingDeliveryModal()
    {
        return '<div class="missing-address-content">
            <p>Necesitas seleccionar una dirección de entrega para continuar.</p>
            <div class="modal-actions">
                <button class="btn btn-primary" onclick="window.location.href=\'/direcciones\'">
                    Seleccionar Dirección de Entrega
                </button>
            </div>
        </div>';
    }

    /**
     * Genera HTML para modal de dirección de facturación faltante
     */
    private static function generateMissingInvoiceModal()
    {
        return '<div class="missing-address-content">
            <h4>Dirección de Facturación Requerida</h4>
            <p><strong>Debes agregar una nueva dirección de facturación para continuar con tu pedido.</strong></p>
            <div class="modal-actions">
                <button class="btn btn-primary add-invoice-address-btn" data-type="invoice">
                    <i class="fa fa-plus"></i> Agregar Nueva Dirección de Facturación
                </button>
            </div>
        </div>';
    }

    /**
     * Genera HTML para modal de VAT faltante
     */
    private static function generateMissingVATModal($address)
    {
        return sprintf(
            '<div class="missing-vat-content">
                <p>La dirección seleccionada requiere un número de VAT.</p>
                <div class="current-address">
                    <strong>%s %s</strong><br>
                    <small>%s</small>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-secondary change-address-btn">Cambiar Dirección</button>
                    <button class="btn btn-primary edit-vat-btn" data-address-id="%d">Editar VAT</button>
                </div>
            </div>',
            $address->firstname,
            $address->lastname,
            $address->alias,
            $address->id
        );
    }

    /**
     * Genera HTML para modal de disponibilidad
     */
    private static function generateAvailabilityModal($product)
    {
        return sprintf(
            '<div class="availability-content">
                <p>El producto "%s" no tiene suficiente stock disponible.</p>
                <div class="product-info">
                    <strong>Cantidad solicitada:</strong> %d<br>
                    <strong>Stock disponible:</strong> %d
                </div>
            </div>',
            $product['name'],
            $product['cart_quantity'],
            Product::getQuantity($product['id_product'])
        );
    }

    /**
     * Limpia el cache de validaciones
     */
    public static function clearCache()
    {
        self::$validationCache = [];
    }

    /**
     * Genera clave de cache
     */
    private static function getCacheKey($cart, $context)
    {
        return md5(
            $cart->id .
            $cart->id_address_delivery .
            $cart->id_address_invoice .
            $context->customer->id .
            serialize($cart->getProducts())
        );
    }

    /**
     * Verifica si el cache es válido
     */
    private static function isCacheValid($cacheKey)
    {
        if (!isset(self::$validationCache[$cacheKey])) {
            return false;
        }

        $cacheData = self::$validationCache[$cacheKey];
        return (time() - $cacheData['timestamp']) < self::$cacheTimeout;
    }

    // Métodos adicionales requeridos por el controlador

    /**
     * Verifica si necesita dirección de facturación
     */
    public static function needsInvoiceAddress($cart)
    {
        // Verificar PS_INVOICE por separado
        $psInvoiceEnabled = (bool)Configuration::get('PS_INVOICE');

        // Usar need_invoice del carrito si está disponible, sino calcularlo
        if (isset($cart->need_invoice) && $cart->need_invoice !== null) {
            $needInvoice = (bool)$cart->need_invoice;
        } else {
            // Verificar condiciones específicas de negocio para need_invoice
            $needInvoice = self::checkNeedInvoiceByProductType($cart)
                || self::checkNeedInvoiceByOrderTotal($cart);
        }

        // Solo validar si PS_INVOICE está habilitado O si need_invoice es true
        if (!$psInvoiceEnabled && !$needInvoice) {
            return false;
        }

        // Verificar si no hay dirección de facturación asignada
        if (!$cart->id_address_invoice || $cart->id_address_invoice == 0) {
            return true;
        }

        // Verificar si la dirección existe y no está eliminada
        $invoiceAddress = new Address($cart->id_address_invoice);
        return !$invoiceAddress->id || $invoiceAddress->deleted;
    }

    /**
     * Verifica si necesita factura por tipo de producto
     */
    public static function checkNeedInvoiceByProductType($cart)
    {
        $id_feature_product_type = (int)Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') ?: 0;
        $id_feature_value_product_type_need_invoice = Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_NEED_INVOICE') ?: '';

        $products = $cart->getProducts();

        if (empty($products)) {
            return false;
        }

        $required_invoice_values = array_map('trim', explode(',', $id_feature_value_product_type_need_invoice));

        foreach ($products as $product) {
            if (!empty($product['features'])) {
                foreach ($product['features'] as $feature) {
                    if ($feature['id_feature'] == $id_feature_product_type && in_array($feature['id_feature_value'], $required_invoice_values, true)) {
                        return true;
                    }
                }
            }

            $familia = Product::getGrupoAlvarez($product['id_product'], $product['id_product_attribute'], 0);
            if (in_array($familia, ['100005311', '100005312'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si necesita factura por total del pedido
     */
    public static function checkNeedInvoiceByOrderTotal($cart)
    {
        return $cart->getOrderTotal() >= 3000;
    }

    /**
     * Verifica si necesita DNI por tipo de producto
     */
    public static function checkNeedDNIByProductType($cart)
    {
        $id_feature_value_product_type_need_dni = Configuration::get('DNI_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_NEED_DNI') ?: '';

        $required_dni_values = array_map('trim', explode(',', $id_feature_value_product_type_need_dni));

        $products = $cart->getProducts();

        if (empty($products) || empty($required_dni_values)) {
            return false;
        }

        foreach ($products as $product) {
            if (!empty($product['features'])) {
                foreach ($product['features'] as $feature) {
                    if (in_array($feature['id_feature_value'], $required_dni_values, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Verifica si necesita DNI por categoría
     */
    public static function checkNeedDNIByCategory($cart)
    {
        $category_ids_need_dni = Configuration::get('DNI_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_CATEGORY_NEED_DNI')
            ? array_map('intval', explode(',', Configuration::get('DNI_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_CATEGORY_NEED_DNI')))
            : [];

        $has_additional_categories = [586, 718, 2564, 585, 2565, 180];

        $products = $cart->getProducts();

        if (empty($products) || empty($category_ids_need_dni)) {
            return false;
        }

        foreach ($products as $product) {
            $categories = Product::getProductCategories($product['id_product']);
            if (empty($categories)) {
                continue;
            }

            $has_required_category = (bool)array_intersect($categories, $category_ids_need_dni);
            $has_additional_category = (bool)array_intersect($categories, $has_additional_categories);

            if ($has_required_category && !$has_additional_category) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si necesita DNI por país
     */
    public static function checkNeedDNIByCountry($cart)
    {
        $ue_countries = [1, 2, 3, 233, 16, 76, 74, 20, 37, 191, 6, 243, 244, 242, 86, 7, 8, 9, 142, 26, 10, 124, 130, 12, 138, 13, 14, 245, 15, 36, 18];

        $language_id = Context::getContext()->language->id;

        $language_to_country = [
            1 => 6,
            2 => 17,
            3 => 8,
            4 => 15,
            5 => 1,
        ];

        $id_country = $language_to_country[$language_id] ?? 6;

        $country = new Country($id_country, (int)Context::getContext()->language->id);
        Context::getContext()->country = $country;
        $country_id = Context::getContext()->country->id;

        return in_array($country_id, [242, 243], true) || !in_array($country_id, $ue_countries, true);
    }

    /**
     * Verifica si tiene VAT number válido
     */
    public static function hasValidVATNumber($cart)
    {
        $invoiceAddress = new Address($cart->id_address_invoice);

        // Si la dirección no existe o está eliminada, no es válida
        if (!$invoiceAddress->id || $invoiceAddress->deleted) {
            return false;
        }

        $country = new Country($invoiceAddress->id_country);
        return !$country->need_vat_number || !empty($invoiceAddress->vat_number);
    }

    /**
     * Verifica disponibilidad de productos
     */
    public static function checkProductsAvailability($cart, $context)
    {
        $products = $cart->getProducts(true);
        foreach ($products as $product) {
            $productObj = new Product($product['id_product']);
            if (!$productObj->checkQty($product['cart_quantity'])) {
                return false;
            }
        }
        return true;
    }

}