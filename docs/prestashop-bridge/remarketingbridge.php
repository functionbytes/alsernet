<?php

/**
 * Remarketing Bridge for PrestaShop 1.7 / 8.x
 *
 * Envía webhooks en tiempo real a tu panel de remarketing/helpdesk
 * cuando ocurren eventos de ecommerce: nuevas órdenes, carritos actualizados,
 * cambios de estado, y registros de clientes.
 *
 * Firma: HMAC-SHA256 (header X-Remarketing-Signature)
 */
if (! defined('_PS_VERSION_')) {
    exit;
}

class RemarketingBridge extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'remarketingbridge';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Inoqualab';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Remarketing Bridge');
        $this->description = $this->l('Envía webhooks de órdenes, carritos y clientes a tu panel de remarketing en tiempo real.');
        $this->confirmUninstall = $this->l('¿Seguro? Se dejarán de enviar eventos.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionOrderHistoryUpdateAfter')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('actionCustomerAccountUpdate')
            && $this->registerHook('actionDeleteGDPRCustomer')
            && Configuration::updateValue('REMARKETING_WEBHOOK_URL', '')
            && Configuration::updateValue('REMARKETING_STORE_TOKEN', '')
            && Configuration::updateValue('REMARKETING_API_SECRET', '');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('REMARKETING_WEBHOOK_URL')
            && Configuration::deleteByName('REMARKETING_STORE_TOKEN')
            && Configuration::deleteByName('REMARKETING_API_SECRET');
    }

    public function getContent()
    {
        if (((bool) Tools::isSubmit('submitRemarketingBridgeModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'REMARKETING_WEBHOOK_URL' => Configuration::get('REMARKETING_WEBHOOK_URL'),
            'REMARKETING_STORE_TOKEN' => Configuration::get('REMARKETING_STORE_TOKEN'),
            'REMARKETING_API_SECRET' => Configuration::get('REMARKETING_API_SECRET'),
        ]);

        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
    }

    protected function postProcess()
    {
        $url = Tools::getValue('REMARKETING_WEBHOOK_URL');
        $token = Tools::getValue('REMARKETING_STORE_TOKEN');
        $secret = Tools::getValue('REMARKETING_API_SECRET');

        if (! empty($url) && ! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->_errors[] = $this->l('La URL del webhook no es válida.');

            return;
        }

        Configuration::updateValue('REMARKETING_WEBHOOK_URL', trim($url));
        Configuration::updateValue('REMARKETING_STORE_TOKEN', trim($token));
        Configuration::updateValue('REMARKETING_API_SECRET', trim($secret));

        $this->_confirmations[] = $this->l('Configuración guardada.');
    }

    /* ============================================================
     * Hooks
     * ============================================================ */

    public function hookActionValidateOrder(array $params)
    {
        $order = $params['order'];
        $customer = $params['customer'];
        $cart = $params['cart'];
        $currency = $params['currency'];

        $items = [];
        foreach ($order->getProducts() as $product) {
            $items[] = [
                'product_id' => (string) $product['product_id'],
                'product_name' => $product['product_name'],
                'product_reference' => $product['product_reference'] ?? null,
                'product_quantity' => (int) $product['product_quantity'],
                'unit_price_tax_incl' => (float) $product['unit_price_tax_incl'],
                'product_price' => (float) $product['product_price'],
            ];
        }

        $this->sendWebhook('order.validated', [
            'order_id' => (string) $order->id,
            'reference' => $order->reference,
            'customer_id' => (string) $customer->id,
            'email' => $customer->email,
            'first_name' => $customer->firstname,
            'last_name' => $customer->lastname,
            'total_paid' => (float) $order->total_paid,
            'total_paid_tax_incl' => (float) $order->total_paid_tax_incl,
            'total_paid_tax_excl' => (float) $order->total_paid_tax_excl,
            'total_products' => (float) $order->total_products,
            'total_discounts_tax_incl' => (float) $order->total_discounts_tax_incl,
            'total_shipping_tax_incl' => (float) $order->total_shipping_tax_incl,
            'currency' => $currency->iso_code,
            'current_state' => (string) $order->current_state,
            'payment' => $order->payment,
            'module' => $order->module,
            'date_add' => $order->date_add,
            'items' => $items,
            'occurred_at' => date('c'),
        ]);
    }

    public function hookActionCartSave(array $params)
    {
        $cart = $params['cart'];
        if (! $cart || ! $cart->id) {
            return;
        }

        // Evitar spam: solo enviar si el carrito tiene productos y no se ha enviado recientemente
        $cacheKey = 'remarketingbridge_cart_'.$cart->id;
        $lastSent = (int) Cache::retrieve($cacheKey);
        if ($lastSent && (time() - $lastSent) < 60) {
            return;
        }

        $products = $cart->getProducts(true);
        if (empty($products)) {
            return;
        }

        $items = [];
        $total = 0;
        foreach ($products as $p) {
            $items[] = [
                'id_product' => (string) $p['id_product'],
                'name' => $p['name'],
                'reference' => $p['reference'] ?? null,
                'quantity' => (int) $p['cart_quantity'],
                'unit_price' => (float) $p['price_wt'],
            ];
            $total += (float) $p['total_wt'];
        }

        $customer = new Customer($cart->id_customer);
        $email = Validate::isLoadedObject($customer) ? $customer->email : null;

        $this->sendWebhook('cart.updated', [
            'cart_id' => (string) $cart->id,
            'customer_id' => $cart->id_customer ? (string) $cart->id_customer : null,
            'email' => $email,
            'items' => $items,
            'total' => $total,
            'currency' => Context::getContext()->currency->iso_code ?? 'EUR',
            'occurred_at' => date('c'),
        ]);

        Cache::store($cacheKey, time(), 120);
    }

    public function hookActionOrderHistoryUpdateAfter(array $params)
    {
        $orderHistory = $params['order_history'];
        $order = new Order($orderHistory->id_order);

        if (! Validate::isLoadedObject($order)) {
            return;
        }

        $customer = new Customer($order->id_customer);

        $this->sendWebhook('order.updated', [
            'order_id' => (string) $order->id,
            'reference' => $order->reference,
            'customer_id' => (string) $customer->id,
            'email' => $customer->email,
            'current_state' => (string) $order->current_state,
            'total_paid' => (float) $order->total_paid,
            'occurred_at' => date('c'),
        ]);
    }

    public function hookActionCustomerAccountAdd(array $params)
    {
        $customer = $params['newCustomer'];
        $this->sendWebhook('customer.created', [
            'id' => (string) $customer->id,
            'email' => $customer->email,
            'first_name' => $customer->firstname,
            'last_name' => $customer->lastname,
            'newsletter' => (bool) $customer->newsletter,
            'optin' => (bool) $customer->optin,
            'occurred_at' => date('c'),
        ]);
    }

    public function hookActionCustomerAccountUpdate(array $params)
    {
        $customer = $params['customer'];
        $this->sendWebhook('customer.updated', [
            'id' => (string) $customer->id,
            'email' => $customer->email,
            'first_name' => $customer->firstname,
            'last_name' => $customer->lastname,
            'newsletter' => (bool) $customer->newsletter,
            'optin' => (bool) $customer->optin,
            'occurred_at' => date('c'),
        ]);
    }

    public function hookActionDeleteGDPRCustomer(array $params)
    {
        $this->sendWebhook('customer.deleted', [
            'email' => $params['email'] ?? null,
            'occurred_at' => date('c'),
        ]);
    }

    /* ============================================================
     * Webhook sender
     * ============================================================ */

    protected function sendWebhook(string $topic, array $payload): void
    {
        $url = Configuration::get('REMARKETING_WEBHOOK_URL');
        $token = Configuration::get('REMARKETING_STORE_TOKEN');
        $secret = Configuration::get('REMARKETING_API_SECRET');

        if (empty($url) || empty($token) || empty($secret)) {
            return;
        }

        $url = rtrim($url, '/').'/'.$token;
        $json = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $json, $secret, true));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Remarketing-Topic: '.$topic,
            'X-Remarketing-Signature: '.$signature,
            'User-Agent: RemarketingBridge/1.0 (PrestaShop)',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        curl_exec($ch);
        curl_close($ch);
    }
}
