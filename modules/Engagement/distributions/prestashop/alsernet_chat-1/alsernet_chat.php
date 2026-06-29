<?php

/**
 * Alsernet Live Chat — PrestaShop module
 *
 * Loads the Alsernet engagement SDK on the storefront with the configured
 * website token. Auto-syncs cart, customer and product data so the SDK
 * adapter can read them via the standard PrestaShop globals.
 */
if (! defined('_PS_VERSION_')) {
    exit;
}

class Alsernet_chat extends Module
{
    public function __construct()
    {
        $this->name = 'alsernet_chat';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Alsernet Live Chat');
        $this->description = $this->l('Live chat, scoring, triggers y personalización del SDK Alsernet.');
        $this->confirmUninstall = $this->l('¿Desinstalar el módulo Alsernet Chat?');
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayFooter')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('actionCartUpdateQuantityBefore')
            && $this->registerHook('actionDeleteGDPRCustomer')
            && $this->registerHook('actionExportGDPRData')
            && Configuration::updateValue('ALSERNET_CHAT_API_URL', '')
            && Configuration::updateValue('ALSERNET_CHAT_TOKEN', '')
            && Configuration::updateValue('ALSERNET_CHAT_INTEGRATION_ID', '')
            && Configuration::updateValue('ALSERNET_CHAT_WEBHOOK_SECRET', '')
            && Configuration::updateValue('ALSERNET_CHAT_CATALOG_SYNC', '0');
    }

    public function uninstall(): bool
    {
        return parent::uninstall()
            && Configuration::deleteByName('ALSERNET_CHAT_API_URL')
            && Configuration::deleteByName('ALSERNET_CHAT_TOKEN')
            && Configuration::deleteByName('ALSERNET_CHAT_INTEGRATION_ID')
            && Configuration::deleteByName('ALSERNET_CHAT_WEBHOOK_SECRET')
            && Configuration::deleteByName('ALSERNET_CHAT_CATALOG_SYNC');
    }

    /**
     * Admin configuration form.
     */
    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name)) {
            $apiUrl = trim((string) Tools::getValue('ALSERNET_CHAT_API_URL'));
            $token = trim((string) Tools::getValue('ALSERNET_CHAT_TOKEN'));
            $integrationId = trim((string) Tools::getValue('ALSERNET_CHAT_INTEGRATION_ID'));
            $secret = trim((string) Tools::getValue('ALSERNET_CHAT_WEBHOOK_SECRET'));
            $catalogSync = Tools::getValue('ALSERNET_CHAT_CATALOG_SYNC') ? '1' : '0';

            if ($apiUrl && ! filter_var($apiUrl, FILTER_VALIDATE_URL)) {
                $output .= $this->displayError($this->l('La URL de la API no es válida.'));
            } else {
                Configuration::updateValue('ALSERNET_CHAT_API_URL', $apiUrl);
                Configuration::updateValue('ALSERNET_CHAT_TOKEN', $token);
                Configuration::updateValue('ALSERNET_CHAT_INTEGRATION_ID', $integrationId);
                Configuration::updateValue('ALSERNET_CHAT_WEBHOOK_SECRET', $secret);
                Configuration::updateValue('ALSERNET_CHAT_CATALOG_SYNC', $catalogSync);

                $output .= $this->displayConfirmation($this->l('Configuración guardada.'));
            }
        }

        return $output.$this->renderInfo().$this->renderForm();
    }

    private function renderInfo(): string
    {
        $apiUrl = rtrim((string) Configuration::get('ALSERNET_CHAT_API_URL'), '/');
        $integrationId = (string) Configuration::get('ALSERNET_CHAT_INTEGRATION_ID');
        $cronUrl = $apiUrl ? Tools::getShopDomainSsl(true).__PS_BASE_URI__.'modules/'.$this->name.'/cron.php?secret=SECRET&page=1' : '';
        $webhookUrl = ($apiUrl && $integrationId) ? $apiUrl.'/eng/api/sdk/webhook/prestashop/'.$integrationId : '';

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon icon-info"></i> '.$this->l('URLs y endpoints útiles').'</h3>';
        $html .= '<table class="table">';
        if ($webhookUrl) {
            $html .= '<tr><td><strong>Webhook URL</strong></td><td><code>'.htmlspecialchars($webhookUrl).'</code></td></tr>';
            $html .= '<tr><td><strong>Headers webhook</strong></td><td><code>X-Alsernet-Signature</code> + <code>X-Alsernet-Topic</code></td></tr>';
        }
        if ($cronUrl) {
            $html .= '<tr><td><strong>Cron catalog sync</strong> (cada 6h)</td><td><code>'.htmlspecialchars($cronUrl).'</code></td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    private function renderForm(): string
    {
        $helper = new HelperForm;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->submit_action = 'submit'.$this->name;

        $helper->fields_value['ALSERNET_CHAT_API_URL'] = Configuration::get('ALSERNET_CHAT_API_URL');
        $helper->fields_value['ALSERNET_CHAT_TOKEN'] = Configuration::get('ALSERNET_CHAT_TOKEN');
        $helper->fields_value['ALSERNET_CHAT_INTEGRATION_ID'] = Configuration::get('ALSERNET_CHAT_INTEGRATION_ID');
        $helper->fields_value['ALSERNET_CHAT_WEBHOOK_SECRET'] = Configuration::get('ALSERNET_CHAT_WEBHOOK_SECRET');
        $helper->fields_value['ALSERNET_CHAT_CATALOG_SYNC'] = Configuration::get('ALSERNET_CHAT_CATALOG_SYNC');

        $form = [
            'form' => [
                'legend' => ['title' => $this->l('Alsernet Chat — Configuración')],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('URL de la API'),
                        'name' => 'ALSERNET_CHAT_API_URL',
                        'desc' => $this->l('Ej: https://panel.alsernet.com — sin barra final.'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Website token'),
                        'name' => 'ALSERNET_CHAT_TOKEN',
                        'desc' => $this->l('Token del canal Web del inbox del Helpdesk.'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Integration ID'),
                        'name' => 'ALSERNET_CHAT_INTEGRATION_ID',
                        'desc' => $this->l('ID numérico de la integración PrestaShop creada en el panel (Configuración → Integraciones).'),
                        'required' => false,
                    ],
                    [
                        'type' => 'password',
                        'label' => $this->l('Webhook secret'),
                        'name' => 'ALSERNET_CHAT_WEBHOOK_SECRET',
                        'desc' => $this->l('Secret de 64 caracteres mostrado en el panel al crear la integración.'),
                        'required' => false,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Sincronizar catálogo'),
                        'name' => 'ALSERNET_CHAT_CATALOG_SYNC',
                        'desc' => $this->l('Habilita la sincronización vía cron del catálogo de productos para el motor de recomendaciones.'),
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sí')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                ],
                'submit' => ['title' => $this->l('Guardar')],
            ],
        ];

        return $helper->generateForm([$form]);
    }

    /**
     * Inject SDK loader stub — runs as early as possible.
     * Frontend only — never load SDK on back office.
     */
    public function hookDisplayHeader(): string
    {
        if (! $this->isFrontOffice()) {
            return '';
        }

        $apiUrl = (string) Configuration::get('ALSERNET_CHAT_API_URL');
        $token = (string) Configuration::get('ALSERNET_CHAT_TOKEN');

        if (! $apiUrl || ! $token) {
            return '';
        }

        $apiUrl = rtrim($apiUrl, '/');
        $tokenJs = json_encode($token);
        $apiUrlJs = json_encode($apiUrl);

        return <<<HTML
<script>
(function(w,d){
    w.chat = w.chat || function(){(w.chat.q=w.chat.q||[]).push(arguments);};
})(window,document);
</script>
<script async src="{$apiUrl}/build-engagement/sdk.js"></script>
<script>
window.chat('init', { token: {$tokenJs}, apiUrl: {$apiUrlJs}, consent: true });
</script>
HTML;
    }

    /**
     * True only when rendering frontstore pages (not back office, not API).
     */
    private function isFrontOffice(): bool
    {
        $controller = Context::getContext()->controller ?? null;
        if (! $controller) {
            return false;
        }

        return property_exists($controller, 'controller_type')
            && $controller->controller_type === 'front';
    }

    /**
     * Optional footer hook (kept for future extensions like CTA injection).
     */
    public function hookDisplayFooter(): string
    {
        return '';
    }

    /**
     * Server-side webhook to Alsernet on order validation.
     * Sends order details so the SDK backend can record the purchase even
     * if the customer leaves before client tracking fires.
     */
    public function hookActionValidateOrder(array $params): void
    {
        $apiUrl = (string) Configuration::get('ALSERNET_CHAT_API_URL');
        $integrationId = (string) Configuration::get('ALSERNET_CHAT_INTEGRATION_ID');
        $secret = (string) Configuration::get('ALSERNET_CHAT_WEBHOOK_SECRET');

        if (! $apiUrl || ! $integrationId || ! $secret) {
            return;
        }

        $order = $params['order'];
        $customer = $params['customer'];

        $payload = json_encode([
            'order_id' => (int) $order->id,
            'total' => (float) $order->total_paid,
            'currency' => $order->id_currency,
            'customer' => [
                'id' => (int) $customer->id,
                'email' => (string) $customer->email,
                'firstname' => (string) $customer->firstname,
                'lastname' => (string) $customer->lastname,
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, $secret);
        $url = rtrim($apiUrl, '/').'/eng/api/sdk/webhook/prestashop/'.$integrationId;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Alsernet-Signature: '.$signature,
                'X-Alsernet-Topic: actionValidateOrder',
            ],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function hookActionCustomerAccountAdd(array $params): void
    {
        $customer = $params['newCustomer'] ?? null;
        if (! $customer) {
            return;
        }

        $this->sendWebhook('actionCustomerAccountAdd', [
            'customer' => [
                'id' => (int) $customer->id,
                'email' => (string) $customer->email,
                'firstname' => (string) $customer->firstname,
                'lastname' => (string) $customer->lastname,
            ],
        ]);
    }

    public function hookActionCartUpdateQuantityBefore(array $params): void
    {
        $cart = $params['cart'] ?? null;
        $product = $params['product'] ?? null;
        if (! $cart || ! $product) {
            return;
        }

        $this->sendWebhook('actionCartUpdateQuantityBefore', [
            'cart' => [
                'id' => (int) $cart->id,
                'total' => (float) $cart->getOrderTotal(true, Cart::BOTH),
            ],
            'product' => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'quantity' => (int) ($params['quantity'] ?? 1),
            ],
        ]);
    }

    /**
     * GDPR — Right to erasure: delete all customer data in the panel.
     */
    public function hookActionDeleteGDPRCustomer(array $params): ?string
    {
        $email = $params['email'] ?? null;
        if (! $email) {
            return null;
        }

        $this->sendWebhook('actionDeleteGDPRCustomer', [
            'customer' => ['email' => $email, 'id' => (int) ($params['id'] ?? 0)],
        ]);

        return json_encode([
            'status' => 'sent',
            'email' => $email,
            'deleted_in' => 'Alsernet Engagement',
        ]);
    }

    /**
     * GDPR — Data portability: export all data we have for the customer.
     */
    public function hookActionExportGDPRData(array $params): ?string
    {
        $email = $params['email'] ?? null;
        if (! $email) {
            return null;
        }

        return json_encode([
            'module' => 'alsernet_chat',
            'data_location' => Configuration::get('ALSERNET_CHAT_API_URL').'/eng/api/gdpr/export?email='.urlencode($email),
            'description' => $this->l('Datos de tracking y engagement del cliente. Solicitarlos al panel Alsernet con el email del cliente.'),
        ]);
    }

    /**
     * Shared HMAC-signed webhook sender (non-blocking).
     */
    private function sendWebhook(string $topic, array $payload): void
    {
        $apiUrl = (string) Configuration::get('ALSERNET_CHAT_API_URL');
        $integrationId = (string) Configuration::get('ALSERNET_CHAT_INTEGRATION_ID');
        $secret = (string) Configuration::get('ALSERNET_CHAT_WEBHOOK_SECRET');

        if (! $apiUrl || ! $integrationId || ! $secret) {
            return;
        }

        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $secret);
        $url = rtrim($apiUrl, '/').'/eng/api/sdk/webhook/prestashop/'.$integrationId;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Alsernet-Signature: '.$signature,
                'X-Alsernet-Topic: '.$topic,
            ],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Catalog sync — call this from a cron job to push products to the panel.
     * Suggested cron: every 6h. PrestaShop endpoint:
     *   /modules/alsernet_chat/cron.php?secret=<webhook_secret>&page=1
     */
    public function syncCatalog(int $page = 1, int $perPage = 250): array
    {
        $apiUrl = (string) Configuration::get('ALSERNET_CHAT_API_URL');
        $token = (string) Configuration::get('ALSERNET_CHAT_TOKEN');

        if (! $apiUrl || ! $token) {
            return ['success' => false, 'message' => 'No configurado'];
        }

        $offset = ($page - 1) * $perPage;
        $products = Db::getInstance()->executeS('
            SELECT p.id_product, p.price, pl.name, pl.description_short, pl.link_rewrite,
                   cl.name AS category_name, p.id_category_default
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'category_lang cl ON cl.id_category = p.id_category_default AND cl.id_lang = pl.id_lang
            WHERE p.active = 1 AND pl.id_lang = '.(int) Context::getContext()->language->id.'
            LIMIT '.(int) $offset.', '.(int) $perPage
        );

        if (empty($products)) {
            return ['success' => true, 'data' => ['synced' => 0]];
        }

        $payload = json_encode([
            'products' => array_map(fn ($p) => [
                'productId' => (string) $p['id_product'],
                'name' => $p['name'],
                'description' => mb_substr((string) $p['description_short'], 0, 1000),
                'price' => (float) $p['price'],
                'currency' => Context::getContext()->currency->iso_code,
                'category' => $p['category_name'],
            ], $products),
        ]);

        $ch = curl_init(rtrim($apiUrl, '/').'/eng/api/sdk/catalog/sync');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Website-Token: '.$token,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return ['success' => true, 'response' => $response, 'count' => count($products)];
    }
}
