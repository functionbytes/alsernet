<?php

/**
 * Remarketing Bridge — Plugin PrestaShop 1.7 / 8.x.
 *
 * Inyecta el pixel JS de Remarketing en todas las páginas de la tienda
 * y dispara webhooks firmados HMAC-SHA256 al endpoint Laravel cuando:
 *   - Se actualiza el carrito (`actionCartSave`)
 *   - Se valida un pedido (`actionValidateOrder`)
 *   - Se crea un cliente (`actionCustomerAccountAdd`)
 *   - Se actualiza un producto (`actionProductUpdate`)
 *
 * SETUP:
 *   1. Subir esta carpeta entera (`remarketingbridge/`) a /modules/ del PrestaShop.
 *   2. Back office → Módulos → Buscar "Remarketing Bridge" → Instalar.
 *   3. Configurar → endpoint, store_token, shared_secret.
 *
 * @author  Alsernet
 * @license MIT
 */
if (! defined('_PS_VERSION_')) {
    exit;
}

class RemarketingBridge extends Module
{
    /** Configuration keys. */
    const CFG_ENDPOINT = 'REMARKETING_ENDPOINT';

    const CFG_PIXEL_URL = 'REMARKETING_PIXEL_URL';

    const CFG_STORE_TOKEN = 'REMARKETING_STORE_TOKEN';

    const CFG_SHARED_SECRET = 'REMARKETING_SHARED_SECRET';

    const CFG_TRACK_ENDPOINT = 'REMARKETING_TRACK_ENDPOINT';

    public function __construct()
    {
        $this->name = 'remarketingbridge';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Remarketing Bridge');
        $this->description = $this->l('Conecta esta tienda con la plataforma Remarketing (pixel JS + webhooks firmados HMAC).');
        $this->confirmUninstall = $this->l('¿Seguro que quieres desinstalar Remarketing Bridge?');
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionObjectCustomerUpdateAfter')
            && Configuration::updateValue(self::CFG_ENDPOINT, '')
            && Configuration::updateValue(self::CFG_PIXEL_URL, '')
            && Configuration::updateValue(self::CFG_STORE_TOKEN, '')
            && Configuration::updateValue(self::CFG_SHARED_SECRET, '')
            && Configuration::updateValue(self::CFG_TRACK_ENDPOINT, '');
    }

    public function uninstall(): bool
    {
        return parent::uninstall()
            && Configuration::deleteByName(self::CFG_ENDPOINT)
            && Configuration::deleteByName(self::CFG_PIXEL_URL)
            && Configuration::deleteByName(self::CFG_STORE_TOKEN)
            && Configuration::deleteByName(self::CFG_SHARED_SECRET)
            && Configuration::deleteByName(self::CFG_TRACK_ENDPOINT);
    }

    /* ============================================================
     *  Configuración (Back office)
     * ============================================================ */

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name)) {
            $endpoint = trim((string) Tools::getValue(self::CFG_ENDPOINT));
            $pixelUrl = trim((string) Tools::getValue(self::CFG_PIXEL_URL));
            $storeToken = trim((string) Tools::getValue(self::CFG_STORE_TOKEN));
            $sharedSecret = trim((string) Tools::getValue(self::CFG_SHARED_SECRET));
            $trackEndpoint = trim((string) Tools::getValue(self::CFG_TRACK_ENDPOINT));

            if (! $endpoint || ! filter_var($endpoint, FILTER_VALIDATE_URL)) {
                $output .= $this->displayError($this->l('El endpoint debe ser una URL válida.'));
            } else {
                Configuration::updateValue(self::CFG_ENDPOINT, $endpoint);
                Configuration::updateValue(self::CFG_PIXEL_URL, $pixelUrl);
                Configuration::updateValue(self::CFG_STORE_TOKEN, $storeToken);
                Configuration::updateValue(self::CFG_SHARED_SECRET, $sharedSecret);
                Configuration::updateValue(self::CFG_TRACK_ENDPOINT, $trackEndpoint ?: $this->guessTrackEndpoint($endpoint));

                $output .= $this->displayConfirmation($this->l('Configuración guardada.'));
            }
        }

        return $output.$this->renderForm();
    }

    protected function renderForm(): string
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Remarketing Bridge'),
                    'icon' => 'icon-bullhorn',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Endpoint URL'),
                        'name' => self::CFG_ENDPOINT,
                        'desc' => $this->l('URL del webhook (ej: https://app.example.com/r/webhooks/prestashop/{store_token}).'),
                        'required' => true,
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Pixel URL'),
                        'name' => self::CFG_PIXEL_URL,
                        'desc' => $this->l('URL del pixel.js (ej: https://app.example.com/remarketing/pixel.js).'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Track endpoint'),
                        'name' => self::CFG_TRACK_ENDPOINT,
                        'desc' => $this->l('URL del /r/track. Se autodetecta del endpoint si lo dejas vacío.'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Store token'),
                        'name' => self::CFG_STORE_TOKEN,
                        'desc' => $this->l('Token público (se obtiene del panel Remarketing → Tiendas → Detalle).'),
                        'required' => true,
                        'class' => 'fixed-width-xl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Shared secret'),
                        'name' => self::CFG_SHARED_SECRET,
                        'desc' => $this->l('Secret HMAC-SHA256 para firmar webhooks (api_secret de la tienda en Remarketing).'),
                        'required' => true,
                        'class' => 'fixed-width-xl',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = 0;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submit'.$this->name;

        $helper->fields_value[self::CFG_ENDPOINT] = Configuration::get(self::CFG_ENDPOINT);
        $helper->fields_value[self::CFG_PIXEL_URL] = Configuration::get(self::CFG_PIXEL_URL);
        $helper->fields_value[self::CFG_STORE_TOKEN] = Configuration::get(self::CFG_STORE_TOKEN);
        $helper->fields_value[self::CFG_SHARED_SECRET] = Configuration::get(self::CFG_SHARED_SECRET);
        $helper->fields_value[self::CFG_TRACK_ENDPOINT] = Configuration::get(self::CFG_TRACK_ENDPOINT);

        return $helper->generateForm([$fields_form]);
    }

    protected function guessTrackEndpoint(string $endpoint): string
    {
        $base = preg_replace('#/r/webhooks/prestashop/.*$#', '', $endpoint);

        return rtrim($base, '/').'/r/track';
    }

    /* ============================================================
     *  Hook: displayHeader (pixel JS + identify)
     * ============================================================ */

    public function hookDisplayHeader(): string
    {
        $pixelUrl = Configuration::get(self::CFG_PIXEL_URL);
        $storeToken = Configuration::get(self::CFG_STORE_TOKEN);
        $trackEndpoint = Configuration::get(self::CFG_TRACK_ENDPOINT);

        if (! $pixelUrl || ! $storeToken) {
            return '';
        }

        $endpointJs = json_encode($trackEndpoint ?: '/r/track');
        $tokenJs = json_encode($storeToken);
        $pixelUrlEsc = htmlspecialchars($pixelUrl, ENT_QUOTES, 'UTF-8');

        $identify = '';

        if ($this->context->customer && $this->context->customer->isLogged()) {
            $email = json_encode((string) $this->context->customer->email);
            $first = json_encode((string) $this->context->customer->firstname);
            $identify = "_rmk.push(['identify', {$email}, {$first}]);";
        }

        return <<<HTML
<script>
window._rmkEndpoint = {$endpointJs};
window._rmk = window._rmk || [];
window._rmk.push(['store', {$tokenJs}]);
{$identify}
</script>
<script src="{$pixelUrlEsc}" async></script>
HTML;
    }

    /* ============================================================
     *  Hooks: eventos de WooCommerce → webhooks
     * ============================================================ */

    public function hookActionCartSave(array $params): void
    {
        $cart = $params['cart'] ?? $this->context->cart;

        if (! $cart || ! is_object($cart)) {
            return;
        }

        $this->fireWebhook('cart.updated', [
            'cart_id' => (int) $cart->id,
            'id_customer' => (int) $cart->id_customer,
            'cart' => $this->snapshotCart($cart),
            'customer' => $this->snapshotCustomerFromCart($cart),
        ]);
    }

    public function hookActionValidateOrder(array $params): void
    {
        $order = $params['order'] ?? null;
        $customer = $params['customer'] ?? null;
        $cart = $params['cart'] ?? null;

        if (! $order) {
            return;
        }

        $this->fireWebhook('order.validated', [
            'order_id' => (int) $order->id,
            'order' => $this->snapshotOrder($order),
            'customer' => $this->snapshotCustomer($customer),
            'cart_id' => $cart ? (int) $cart->id : null,
        ]);
    }

    public function hookActionCustomerAccountAdd(array $params): void
    {
        $customer = $params['newCustomer'] ?? $params['customer'] ?? null;

        if (! $customer) {
            return;
        }

        $this->fireWebhook('customer.created', [
            'customer_id' => (int) $customer->id,
            'customer' => $this->snapshotCustomer($customer),
        ]);
    }

    public function hookActionObjectCustomerUpdateAfter(array $params): void
    {
        $customer = $params['object'] ?? null;

        if (! $customer || ! is_object($customer)) {
            return;
        }

        $this->fireWebhook('customer.updated', [
            'customer_id' => (int) $customer->id,
            'customer' => $this->snapshotCustomer($customer),
        ]);
    }

    public function hookActionProductUpdate(array $params): void
    {
        $product = $params['product'] ?? null;

        if (! $product) {
            return;
        }

        $this->fireWebhook('product.updated', [
            'product_id' => (int) $product->id,
            'product' => [
                'id' => (int) $product->id,
                'reference' => (string) $product->reference,
                'price' => (float) $product->price,
                'active' => (bool) $product->active,
            ],
        ]);
    }

    /* ============================================================
     *  Snapshots
     * ============================================================ */

    protected function snapshotCart($cart): array
    {
        $items = [];

        if (method_exists($cart, 'getProducts')) {
            foreach ($cart->getProducts() as $row) {
                $items[] = [
                    'product_id' => (int) ($row['id_product'] ?? 0),
                    'product_attribute_id' => (int) ($row['id_product_attribute'] ?? 0),
                    'name' => (string) ($row['name'] ?? ''),
                    'reference' => (string) ($row['reference'] ?? ''),
                    'quantity' => (int) ($row['cart_quantity'] ?? 0),
                    'price' => (float) ($row['price_wt'] ?? $row['price'] ?? 0),
                    'image' => $this->productImageUrl((int) ($row['id_product'] ?? 0)),
                ];
            }
        }

        $total = method_exists($cart, 'getOrderTotal')
            ? (float) $cart->getOrderTotal(true, Cart::BOTH)
            : 0;

        return [
            'id' => (int) $cart->id,
            'items' => $items,
            'total' => $total,
            'currency' => $this->currencyIso((int) $cart->id_currency),
            'item_count' => array_sum(array_column($items, 'quantity')),
            'cart_url' => $this->context->link->getPageLink('cart', null, null, ['action' => 'show']),
        ];
    }

    protected function snapshotOrder($order): array
    {
        $items = [];
        $rows = method_exists($order, 'getProducts') ? $order->getProducts() : [];

        foreach ($rows as $row) {
            $items[] = [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'name' => (string) ($row['product_name'] ?? ''),
                'reference' => (string) ($row['product_reference'] ?? ''),
                'quantity' => (int) ($row['product_quantity'] ?? 0),
                'price' => (float) ($row['unit_price_tax_incl'] ?? 0),
                'total' => (float) ($row['total_price_tax_incl'] ?? 0),
            ];
        }

        return [
            'id' => (int) $order->id,
            'reference' => (string) $order->reference,
            'current_state' => (int) $order->current_state,
            'total' => (float) $order->total_paid_tax_incl,
            'subtotal' => (float) $order->total_products,
            'discount' => (float) $order->total_discounts_tax_incl,
            'shipping' => (float) $order->total_shipping_tax_incl,
            'currency' => $this->currencyIso((int) $order->id_currency),
            'placed_at' => (string) $order->date_add,
            'items' => $items,
        ];
    }

    protected function snapshotCustomer($customer): ?array
    {
        if (! $customer || ! is_object($customer) || ! ($customer->email ?? null)) {
            return null;
        }

        return [
            'id' => (int) $customer->id,
            'email' => (string) $customer->email,
            'first_name' => (string) ($customer->firstname ?? ''),
            'last_name' => (string) ($customer->lastname ?? ''),
            'newsletter' => (bool) ($customer->newsletter ?? false),
            'optin' => (bool) ($customer->optin ?? false),
        ];
    }

    protected function snapshotCustomerFromCart($cart): ?array
    {
        if (empty($cart->id_customer)) {
            return null;
        }

        $customer = new Customer((int) $cart->id_customer);

        return $this->snapshotCustomer($customer);
    }

    protected function currencyIso(int $idCurrency): string
    {
        if ($idCurrency <= 0) {
            return 'EUR';
        }

        $currency = new Currency($idCurrency);

        return (string) ($currency->iso_code ?: 'EUR');
    }

    protected function productImageUrl(int $productId): ?string
    {
        if ($productId <= 0) {
            return null;
        }

        try {
            $cover = Product::getCover($productId);

            if (! $cover) {
                return null;
            }

            return $this->context->link->getImageLink('', (string) $cover['id_image'], 'home_default');
        } catch (Throwable $e) {
            return null;
        }
    }

    /* ============================================================
     *  Webhook dispatcher
     * ============================================================ */

    protected function fireWebhook(string $topic, array $payload): void
    {
        $endpoint = Configuration::get(self::CFG_ENDPOINT);
        $secret = Configuration::get(self::CFG_SHARED_SECRET);

        if (! $endpoint) {
            return;
        }

        $body = json_encode([
            'topic' => $topic,
            'fired_at' => gmdate('c'),
            'payload' => $payload,
        ]);

        $headers = [
            'Content-Type: application/json',
            'X-WC-Webhook-Topic: '.$topic, // compat
            'X-Remarketing-Topic: '.$topic,
            'X-Remarketing-Module-Version: '.$this->version,
            'User-Agent: Remarketing-PrestaShop-Bridge/'.$this->version,
        ];

        if ($secret) {
            $signature = base64_encode(hash_hmac('sha256', (string) $body, $secret, true));
            $headers[] = 'X-Remarketing-Signature: '.$signature;
            // Header alternativo compatible con WooCommerce signature scheme
            $headers[] = 'X-WC-Webhook-Signature: '.$signature;
        }

        $isLocal = $this->isLocalEndpoint($endpoint);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
            // En entornos locales (*.test, localhost, 127.0.0.1) los certificados
            // suelen ser auto-firmados (Herd, Valet, mkcert) y curl no los valida.
            // En producción siempre validar.
            CURLOPT_SSL_VERIFYPEER => ! $isLocal,
            CURLOPT_SSL_VERIFYHOST => $isLocal ? 0 : 2,
        ]);

        // Best-effort: no bloqueamos el flujo del cliente si falla
        @curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            PrestaShopLogger::addLog(
                'RemarketingBridge: webhook error '.$topic.' → '.$err,
                2,
                null,
                'RemarketingBridge'
            );
        }
    }

    protected function isLocalEndpoint(string $endpoint): bool
    {
        $host = parse_url($endpoint, PHP_URL_HOST) ?? '';

        return (bool) preg_match(
            '/^(localhost|127\.0\.0\.1|.+\.(test|local|localhost))$/i',
            $host
        );
    }
}
