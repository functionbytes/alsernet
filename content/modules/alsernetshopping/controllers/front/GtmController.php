<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/BaseController.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
class GtmController extends BaseController
{

    public $module;
    public $context;
    public $language;
    public $lang;
    public $iso;
    public $present;
    public $currency;


    public function __construct()
    {
        $this->context = Context::getContext();
        $this->customer = $this->context->customer;
        $this->cart = $this->context->cart;
        $this->iso = $this->context->language->iso_code;
        $this->lang = Language::getIdByIso($this->iso);
        $this->present = (new CartPresenter($this->context))->present($this->cart, false);
        $this->module = Module::getInstanceByName("alsernetshopping");
        $this->currency = $this->context->currency ?: Currency::getCurrencyInstance(
            (int) Configuration::get('PS_CURRENCY_DEFAULT')
        );
        parent::__construct();
    }

    public function init()
    {

        $eventType = Tools::getValue('type');
        $paymentType = Tools::getValue('payment_type', '');
        $shippingTier = Tools::getValue('shipping_tier', '');
        $transactionId = Tools::getValue('transaction_id', '');
        $page = Tools::getValue('transaction_id', '');
        $value = (float)Tools::getValue('value', 0);

        $customer = $this->customer;
        $context = $this->context;
        $cart = $this->cart;
        $currency = $this->currency;
        $iso = $this->language->iso_code;
        $isLogged = (bool) $context->customer->isLogged();
        $cartPresent = $this->present;

        $products = $cartPresent['products'] ?? [];
        $subtotals = $cartPresent['subtotals'] ?? [];
        $totals = $cartPresent['totals'] ?? [];
        $labels = $cartPresent['labels'] ?? [];
        $vouchers = $cartPresent['vouchers'] ?? [];
        $discounts = $cartPresent['discounts'] ?? [];
        $totalValue = $totals['total']['amount'] ?? 0;
        $totalTax = $totals['total_tax']['amount'] ?? 0;
        $totalShipping = $totals['total_shipping']['amount'] ?? 0;
        $totalDiscounts = 0;

        if (!empty($discounts)) {
            foreach ($discounts as $discount) {
                $totalDiscounts += abs($discount['value'] ?? 0);
            }
        }

        $gtmItems = [];

        foreach ($products as $product) {

            $productObj = new Product($product['id_product'], false, $context->language->id);
            $manufacturer = new Manufacturer($productObj->id_manufacturer, $context->language->id);
            $category = new Category($productObj->id_category_default, $context->language->id);

            $originalPrice = (float)$product['regular_price'];
            $currentPrice = (float)$product['price2'];
            $itemDiscount = max(0, $originalPrice - $currentPrice);


            $gtmItems[] = [
                'item_id' => (string)$product['id_product'],
                'item_unique_id' => (string)($product['id_product_attribute'] > 0 ?
                    $product['id_product'] . '-' . $product['id_product_attribute'] :
                    $product['id_product']),
                'item_name' => $product['name'],
                'item_brand' => $manufacturer->name ?? '',
                'item_category' => $category->name ?? '',
                'item_variant' => $this->getProductVariant($product),
                'item_variant2' => $this->getProductSecondaryVariant($product),
                'item_list_name' => $this->getItemListName($context),
                'item_list_id' => $this->getItemListName($context),
                'price' => $currentPrice,
                'discount' => $itemDiscount,
                'quantity' => (int)$product['quantity']
            ];
        }

        $userId = $isLogged ? (string)$customer->id : '';
        $userType = $isLogged ? 'registrado' : 'guest';

        // Get current addresses if available
        $currentDeliveryAddress = null;
        $currentInvoiceAddress = null;

        if (!empty($cart->id_address_delivery)) {
            $deliveryAddress = new Address($cart->id_address_delivery);
            if ($deliveryAddress->id) {
                $currentDeliveryAddress = $cart->id_address_delivery;
            }
        }

        if (!empty($cart->id_address_invoice)) {
            $invoiceAddress = new Address($cart->id_address_invoice);
            if ($invoiceAddress->id) {
                $currentInvoiceAddress = $cart->id_address_invoice;
            }
        }

        $finalShippingTier = $shippingTier;

        if (empty($finalShippingTier) && !empty($cart->id_carrier)) {
            $carrier = new Carrier($cart->id_carrier, $context->language->id);
            if ($carrier->id) {
                $finalShippingTier = $carrier->name;
                if (strpos(strtolower($finalShippingTier), 'domicilio') !== false) {
                    $finalShippingTier = 'Envío a domicilio';
                } elseif (strpos(strtolower($finalShippingTier), 'correos') !== false) {
                    $finalShippingTier = 'Correos';
                }
            }
        }

        $checkoutStep = $this->getCurrentCheckoutStep();

        $gtmData = [
            'cartData' => [
                'currency' => $currency->iso_code,
                'total_value' => $totalValue,
                'tax' => $totalTax,
                'shipping' => $totalShipping,
                'total_discounts' => $totalDiscounts,
                'transaction_id' => $transactionId ?: '', // Usar parámetro si viene
                'affiliation' => Configuration::get('PS_SHOP_NAME'),
                'items' => $gtmItems
            ],
            'customerData' => [
                'user_id' => $userId,
                'user_type' => $userType,
                'country' => $context->country->iso_code,
                'page_type' => $page,
                'checkout_step' => $checkoutStep,
                'payment_type' => $paymentType, // Usar parámetro que viene
                'shipping_tier' => $finalShippingTier,
                'current_delivery_address' => $currentDeliveryAddress,
                'current_invoice_address' => $currentInvoiceAddress
            ]
        ];

        $response = [
            'status' => 'success',
            'authentication' => $isLogged,
            'gtmData' => $gtmData,
            'cartItems' => count($gtmItems),
            'totalValue' => $totalValue,
            'currency' => $currency->iso_code,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s')
        ];

        if ($eventType) {
            $response['eventType'] = $eventType;
            $response['eventOptions'] = $this->getEventOptions($eventType, [
                'payment_type' => $paymentType,
                'shipping_tier' => $finalShippingTier,
                'transaction_id' => $transactionId,
                'value' => $value ?: $totalValue
            ]);
        }


        return $response;
    }

    /**
     * Equivalent to JS toNum function - formats number with specified decimals
     */
    private function toNum($value, $decimals = 2)
    {
        return round((float)$value, $decimals);
    }

    /**
     * Prepare GTM purchase event data for order confirmation
     */
    public function prepareOrderPurchaseData($order)
    {
        $customer = $this->context->customer;
        $currency = $this->context->currency;

        // Get delivery address
        $deliveryAddress = new Address($order->id_address_delivery);
        $deliveryCountry = new Country($deliveryAddress->id_country, $this->context->language->id);

        // Get order products with detailed info
        $orderProducts = $order->getProducts();
        $gtmItems = [];

        foreach ($orderProducts as $product) {
            $productObj = new Product($product['product_id'], false, $this->context->language->id);
            $category = new Category($productObj->id_category_default, $this->context->language->id);
            $manufacturer = new Manufacturer($productObj->id_manufacturer, $this->context->language->id);

            $gtmItems[] = [
                'item_id' => $product['product_reference'] ?: $product['product_id'],
                'item_name' => $product['product_name'],
                'category' => $category->name ?: 'General',
                'item_brand' => $manufacturer->name ?: '',
                'quantity' => (int)$product['product_quantity'],
                'price' => $this->toNum($product['unit_price_tax_incl'], 0)  // Using toNum with 0 decimals like JS
            ];
        }

        // Get carrier info
        $carrier = new Carrier($order->id_carrier, $this->context->language->id);
        $shippingTier = $carrier->name ?: 'Standard';

        // Get payment method
        $paymentMethod = $order->payment ?: 'Unknown';

        // Calculate totals with toNum formatting
        $totalValue = $this->toNum($order->total_paid_tax_incl, 2);
        $totalTax = $this->toNum($order->total_paid_tax_incl - $order->total_paid_tax_excl, 0);  // Using toNum with 0 decimals for tax
        $totalShipping = $this->toNum($order->total_shipping_tax_incl, 2);

        return [
            'event' => 'purchase',
            'user_id' => $customer->isLogged() ? (string)$customer->id : 'guest',
            'user_type' => $customer->isLogged() ? 'registered' : 'guest',
            'country' => $deliveryCountry->iso_code ?: 'ES',
            'page_type' => 'checkout',
            'checkout_step' => 'completed',
            'payment_type' => $paymentMethod,
            'shipping_tier' => $shippingTier,
            'ecommerce' => [
                'transaction_id' => (string)$order->id,
                'affiliation' => Configuration::get('PS_SHOP_NAME') ?: 'Store',
                'value' => $totalValue,
                'tax' => $totalTax,
                'shipping' => $totalShipping,
                'currency' => $currency->iso_code,
                'items' => $gtmItems
            ]
        ];
    }

    private function getEventOptions($eventType, $params = [])
    {
        $options = [];

        switch ($eventType) {
            case 'add_payment_info':
                $options['payment_type'] = $params['payment_type'] ?? '';
                break;

            case 'add_shipping_info':
            case 'add_address_info':
                $options['shipping_tier'] = $params['shipping_tier'] ?? '';
                break;

            case 'purchase':
                $options['transaction_id'] = $params['transaction_id'] ?? '';
                $options['value'] = $params['value'] ?? 0;
                break;

            case 'begin_checkout':
                $options['checkout_step'] = '1';
                break;

            case 'add_to_cart':
            case 'remove_from_cart':
                // Opciones específicas para eventos de carrito
                break;
        }

        return $options;
    }
    private function getProductVariant($product)
    {
        if (empty($product['attributes'])) {
            // Fallback común en PrestaShop
            return !empty($product['attributes_small']) && is_string($product['attributes_small'])
                ? $product['attributes_small']
                : '';
        }

        $attrs = $product['attributes'];

        // Caso 1: ya viene como string ("Color: Rojo, Talla: M")
        if (is_string($attrs)) {
            return $attrs;
        }

        // Caso 2: array (puede variar el nombre de las claves entre themes/módulos)
        if (is_array($attrs)) {
            $out = [];
            foreach ($attrs as $attr) {
                if (is_string($attr)) {
                    // A veces vienen como lista de strings
                    $out[] = $attr;
                    continue;
                }
                if (is_array($attr)) {
                    // Claves más habituales
                    $group = $attr['group_name'] ?? $attr['name'] ?? $attr['group'] ?? null;
                    $value = $attr['attribute_name'] ?? $attr['value'] ?? $attr['attribute'] ?? null;

                    if ($group !== null && $value !== null) {
                        $out[] = $group . ': ' . $value;
                        continue;
                    }

                    // Último recurso: aplana par clave=valor legible
                    $flat = [];
                    foreach ($attr as $k => $v) {
                        if (is_scalar($v)) $flat[] = "$k=$v";
                    }
                    if ($flat) $out[] = implode(' ', $flat);
                }
            }

            if (!empty($out)) {
                return implode(', ', $out);
            }
        }

        // Fallback final
        return !empty($product['attributes_small']) && is_string($product['attributes_small'])
            ? $product['attributes_small']
            : '';
    }
    private function getProductSecondaryVariant($product)
    {
        // Reutilizamos la representación textual y cogemos el segundo segmento
        $variantStr = $this->getProductVariant($product);
        if ($variantStr === '') {
            return '';
        }

        // Separa por coma: "Color: Rojo, Talla: M" -> ["Color: Rojo", "Talla: M"]
        $parts = array_map('trim', explode(',', $variantStr));
        return $parts[1] ?? '';
    }
    private function getItemListName($context)
    {
        $controller = $context->controller;

        if ($controller instanceof CartController) {
            return 'Shopping Cart';
        } elseif ($controller instanceof CheckoutController ||
            $controller instanceof OrderController) {
            return 'Checkout';
        } elseif ($controller instanceof ProductController) {
            return 'Product Page';
        } elseif ($controller instanceof CategoryController) {
            $category = new Category($context->controller->getCategory()->id, $context->language->id);
            return 'Category: ' . $category->name;
        }

        return 'General';
    }
    private function getCurrentCheckoutStep()
    {
        $step = Tools::getValue('step');
        $controller = $this->context->controller;

        if ($step) {
            switch ($step) {
                case 'address':
                case 'addresses':
                    return '1';
                case 'delivery':
                case 'shipping':
                    return '2';
                case 'payment':
                    return '3';
                case 'confirmation':
                case 'order':
                    return '4';
            }
        }

        // Determine by controller context
        if ($controller instanceof CartController) {
            return '0'; // Pre-checkout
        } elseif ($controller instanceof CheckoutController ||
            $controller instanceof OrderController) {
            // Try to determine specific step from URL or form data
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($uri, 'address') !== false) return '1';
            if (strpos($uri, 'delivery') !== false || strpos($uri, 'shipping') !== false) return '2';
            if (strpos($uri, 'payment') !== false) return '3';
            if (strpos($uri, 'confirmation') !== false || strpos($uri, 'order') !== false) return '4';

            return '1'; // Default to address step
        }

        return '0';
    }
}