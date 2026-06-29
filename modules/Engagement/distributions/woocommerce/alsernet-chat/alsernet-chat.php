<?php
/**
 * Plugin Name: Alsernet Live Chat
 * Plugin URI: https://alsernet.com
 * Description: Live chat, scoring, triggers y personalización con el SDK de Alsernet. Compatible con WooCommerce.
 * Version: 1.0.0
 * Author: Alsernet
 * License: MIT
 * Text Domain: alsernet-chat
 * Requires at least: 5.5
 * Requires PHP: 7.4
 */
if (! defined('ABSPATH')) {
    exit;
}

final class AlsernetChat
{
    private const OPTION_API_URL = 'alsernet_chat_api_url';

    private const OPTION_TOKEN = 'alsernet_chat_token';

    private const OPTION_INTEGRATION_ID = 'alsernet_chat_integration_id';

    private const OPTION_WEBHOOK_SECRET = 'alsernet_chat_webhook_secret';

    public static function boot(): void
    {
        $instance = new self;
        add_action('admin_menu', [$instance, 'registerSettingsPage']);
        add_action('admin_init', [$instance, 'registerSettings']);
        add_action('wp_enqueue_scripts', [$instance, 'enqueueSdk']);
        add_action('wp_head', [$instance, 'printContextBootstrap']);

        // WooCommerce server-side webhooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_thankyou', [$instance, 'sendOrderWebhook'], 20);
            add_action('user_register', [$instance, 'sendCustomerWebhook'], 20);
        }

        // Catalog sync via WP-Cron (every 6 hours)
        add_action('alsernet_chat_catalog_sync', [$instance, 'syncCatalog']);
        if (! wp_next_scheduled('alsernet_chat_catalog_sync')) {
            wp_schedule_event(time(), 'twicedaily', 'alsernet_chat_catalog_sync');
        }
    }

    public function syncCatalog(): void
    {
        if (! function_exists('wc_get_products')) {
            return;
        }

        $apiUrl = trim((string) get_option(self::OPTION_API_URL));
        $token = trim((string) get_option(self::OPTION_TOKEN));
        if (! $apiUrl || ! $token) {
            return;
        }

        $products = wc_get_products([
            'limit' => 250,
            'status' => 'publish',
        ]);

        if (empty($products)) {
            return;
        }

        $payload = [
            'products' => array_map(function ($p) {
                $cats = wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']);

                return [
                    'productId' => (string) $p->get_id(),
                    'name' => $p->get_name(),
                    'description' => mb_substr(wp_strip_all_tags($p->get_short_description()), 0, 1000),
                    'price' => (float) $p->get_price(),
                    'currency' => get_woocommerce_currency(),
                    'url' => get_permalink($p->get_id()),
                    'imageUrl' => wp_get_attachment_url($p->get_image_id()),
                    'category' => is_array($cats) && count($cats) ? $cats[0] : null,
                ];
            }, $products),
        ];

        wp_remote_post(
            rtrim($apiUrl, '/').'/eng/api/sdk/catalog/sync',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Website-Token' => $token,
                ],
                'body' => wp_json_encode($payload),
                'timeout' => 30,
                'blocking' => false,
            ]
        );
    }

    /**
     * Admin menu under Settings.
     */
    public function registerSettingsPage(): void
    {
        add_options_page(
            'Alsernet Chat',
            'Alsernet Chat',
            'manage_options',
            'alsernet-chat',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('alsernet_chat', self::OPTION_API_URL);
        register_setting('alsernet_chat', self::OPTION_TOKEN);
        register_setting('alsernet_chat', self::OPTION_INTEGRATION_ID);
        register_setting('alsernet_chat', self::OPTION_WEBHOOK_SECRET);
    }

    public function renderSettingsPage(): void
    {
        ?>
        <div class="wrap">
            <h1>Alsernet Chat</h1>
            <form method="post" action="options.php">
                <?php settings_fields('alsernet_chat'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>URL de la API</label></th>
                        <td>
                            <input type="url" name="<?php echo esc_attr(self::OPTION_API_URL); ?>"
                                   value="<?php echo esc_attr(get_option(self::OPTION_API_URL)); ?>"
                                   class="regular-text" placeholder="https://panel.alsernet.com" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Website token</label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr(self::OPTION_TOKEN); ?>"
                                   value="<?php echo esc_attr(get_option(self::OPTION_TOKEN)); ?>"
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Integration ID (webhooks)</label></th>
                        <td>
                            <input type="number" name="<?php echo esc_attr(self::OPTION_INTEGRATION_ID); ?>"
                                   value="<?php echo esc_attr(get_option(self::OPTION_INTEGRATION_ID)); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Webhook secret</label></th>
                        <td>
                            <input type="password" name="<?php echo esc_attr(self::OPTION_WEBHOOK_SECRET); ?>"
                                   value="<?php echo esc_attr(get_option(self::OPTION_WEBHOOK_SECRET)); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Enqueue the SDK script with `defer`.
     */
    public function enqueueSdk(): void
    {
        $apiUrl = trim((string) get_option(self::OPTION_API_URL));
        $token = trim((string) get_option(self::OPTION_TOKEN));

        if (! $apiUrl || ! $token) {
            return;
        }

        wp_enqueue_script(
            'alsernet-chat-sdk',
            rtrim($apiUrl, '/').'/build-engagement/sdk.js',
            [],
            '1.0.0',
            true
        );
    }

    /**
     * Print the inline init script + customer/cart/product bootstrap as
     * `window.__alsernet_woo` so the WooCommerce adapter can read it.
     */
    public function printContextBootstrap(): void
    {
        $apiUrl = trim((string) get_option(self::OPTION_API_URL));
        $token = trim((string) get_option(self::OPTION_TOKEN));

        if (! $apiUrl || ! $token) {
            return;
        }

        $bootstrap = [
            'cart' => $this->buildCartSnapshot(),
            'customer' => $this->buildCustomerSnapshot(),
            'product' => $this->buildProductSnapshot(),
        ];

        $apiUrlEsc = esc_url(rtrim($apiUrl, '/'));
        $tokenEsc = esc_js($token);
        $payload = wp_json_encode($bootstrap);

        echo "<script>\n";
        echo "window.__alsernet_woo = {$payload};\n";
        echo "(function(w,d){w.chat=w.chat||function(){(w.chat.q=w.chat.q||[]).push(arguments);};})(window,document);\n";
        echo "window.chat('init', { token: '{$tokenEsc}', apiUrl: '{$apiUrlEsc}', consent: true });\n";
        echo "</script>\n";
    }

    private function buildCartSnapshot(): ?array
    {
        if (! function_exists('WC') || ! WC()->cart) {
            return null;
        }

        $cart = WC()->cart;
        $items = [];
        foreach ($cart->get_cart() as $item) {
            $product = $item['data'];
            $items[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'quantity' => (int) $item['quantity'],
                'prices' => ['price' => (string) ($product->get_price() * 100)],
            ];
        }

        return [
            'items' => $items,
            'items_count' => $cart->get_cart_contents_count(),
            'totals' => [
                'total_price' => (string) ($cart->get_total('raw') * 100),
                'currency_code' => get_woocommerce_currency(),
            ],
        ];
    }

    private function buildCustomerSnapshot(): ?array
    {
        if (! is_user_logged_in()) {
            return null;
        }

        $user = wp_get_current_user();

        return [
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ];
    }

    private function buildProductSnapshot(): ?array
    {
        if (! function_exists('is_product') || ! is_product()) {
            return null;
        }

        global $product;
        if (! $product) {
            return null;
        }

        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => (string) $product->get_price(),
            'category' => wc_get_product_category_list($product->get_id()),
        ];
    }

    public function sendOrderWebhook(int $orderId): void
    {
        if (! function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($orderId);
        if (! $order) {
            return;
        }

        $payload = [
            'id' => $order->get_id(),
            'total' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'billing' => [
                'email' => $order->get_billing_email(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
            ],
        ];

        $this->sendWebhook('order.created', $payload);
    }

    public function sendCustomerWebhook(int $userId): void
    {
        $user = get_userdata($userId);
        if (! $user) {
            return;
        }

        $this->sendWebhook('customer.created', [
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]);
    }

    private function sendWebhook(string $topic, array $payload): void
    {
        $apiUrl = trim((string) get_option(self::OPTION_API_URL));
        $integrationId = (int) get_option(self::OPTION_INTEGRATION_ID);
        $secret = (string) get_option(self::OPTION_WEBHOOK_SECRET);

        if (! $apiUrl || ! $integrationId || ! $secret) {
            return;
        }

        $body = wp_json_encode($payload);
        $signature = hash_hmac('sha256', $body, $secret);

        wp_remote_post(
            rtrim($apiUrl, '/').'/eng/api/sdk/webhook/woocommerce/'.$integrationId,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-WC-Webhook-Signature' => $signature,
                    'X-WC-Webhook-Topic' => $topic,
                ],
                'body' => $body,
                'timeout' => 5,
                'blocking' => false,
            ]
        );
    }
}

AlsernetChat::boot();
