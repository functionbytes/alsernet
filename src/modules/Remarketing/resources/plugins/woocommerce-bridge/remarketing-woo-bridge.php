<?php
/**
 * Plugin Name: Remarketing — Woo Bridge
 * Description: Envía eventos de carrito (add_to_cart, abandono, conversión) y page_views al endpoint de Remarketing para integración con email automation.
 * Version: 1.0.0
 * Author: Alsernet
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: MIT
 *
 * SETUP:
 *   1. Subir esta carpeta a wp-content/plugins/.
 *   2. Activar desde Plugins.
 *   3. Ir a Ajustes → Remarketing Bridge y configurar:
 *        - Endpoint URL: https://app.example.com/r/webhooks/woocommerce/{store_token}
 *        - Pixel URL:    https://app.example.com/remarketing/pixel.js
 *        - Store token:  copiar de la pantalla "Tiendas → Detalle" del panel.
 */
if (! defined('ABSPATH')) {
    exit;
}

class Remarketing_Woo_Bridge
{
    const OPTION_KEY = 'remarketing_bridge_settings';

    const VERSION = '1.0.0';

    public static function init(): void
    {
        $instance = new self;

        // Admin
        add_action('admin_menu', [$instance, 'register_settings_page']);
        add_action('admin_init', [$instance, 'register_settings']);

        // Pixel snippet en frontend
        add_action('wp_head', [$instance, 'inject_pixel_snippet']);

        // Identify automático cuando hay sesión
        add_action('wp_footer', [$instance, 'inject_identify_snippet']);

        // WooCommerce hooks
        add_action('woocommerce_add_to_cart', [$instance, 'on_add_to_cart'], 10, 6);
        add_action('woocommerce_cart_updated', [$instance, 'on_cart_updated']);
        add_action('woocommerce_checkout_order_processed', [$instance, 'on_checkout_processed'], 10, 3);
        add_action('woocommerce_thankyou', [$instance, 'on_purchase'], 10, 1);
    }

    /* ===== Admin settings ===== */

    public function register_settings_page(): void
    {
        add_options_page(
            'Remarketing Bridge',
            'Remarketing Bridge',
            'manage_options',
            'remarketing-bridge',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting('remarketing_bridge_group', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => function ($value) {
                return [
                    'endpoint' => esc_url_raw($value['endpoint'] ?? ''),
                    'pixel_url' => esc_url_raw($value['pixel_url'] ?? ''),
                    'store_token' => sanitize_text_field($value['store_token'] ?? ''),
                    'shared_secret' => sanitize_text_field($value['shared_secret'] ?? ''),
                ];
            },
        ]);
    }

    public function render_settings_page(): void
    {
        $settings = $this->settings();
        ?>
        <div class="wrap">
            <h1>Remarketing Bridge</h1>
            <form method="post" action="options.php">
                <?php settings_fields('remarketing_bridge_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>Endpoint URL</label></th>
                        <td>
                            <input type="url" name="<?php echo esc_attr(self::OPTION_KEY); ?>[endpoint]"
                                value="<?php echo esc_attr($settings['endpoint']); ?>" class="regular-text"
                                placeholder="https://app.example.com/r/webhooks/woocommerce/{store_token}">
                            <p class="description">URL completa del webhook (incluye el store_token al final).</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Pixel URL</label></th>
                        <td>
                            <input type="url" name="<?php echo esc_attr(self::OPTION_KEY); ?>[pixel_url]"
                                value="<?php echo esc_attr($settings['pixel_url']); ?>" class="regular-text"
                                placeholder="https://app.example.com/remarketing/pixel.js">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Store token</label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[store_token]"
                                value="<?php echo esc_attr($settings['store_token']); ?>" class="regular-text">
                            <p class="description">Token público que va en el snippet del pixel.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Shared secret</label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[shared_secret]"
                                value="<?php echo esc_attr($settings['shared_secret']); ?>" class="regular-text">
                            <p class="description">Secret HMAC-SHA256 para firmar webhooks (api_secret de la tienda en Remarketing).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /* ===== Pixel snippet ===== */

    public function inject_pixel_snippet(): void
    {
        $s = $this->settings();

        if (! $s['pixel_url'] || ! $s['store_token']) {
            return;
        }
        ?>
        <script>
        window._rmkEndpoint = "<?php echo esc_js(home_url('/r/track')); ?>".replace(/\/r\/track$/, "<?php echo esc_js($this->trackEndpointFromConfig($s)); ?>");
        window._rmk = window._rmk || [];
        window._rmk.push(['store', '<?php echo esc_js($s['store_token']); ?>']);
        </script>
        <script src="<?php echo esc_url($s['pixel_url']); ?>" async></script>
        <?php
    }

    public function inject_identify_snippet(): void
    {
        if (! function_exists('is_user_logged_in') || ! is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();

        if (! $user || ! $user->user_email) {
            return;
        }
        ?>
        <script>
        (function(){ if(window._rmk && window._rmk.push){ window._rmk.push(['identify', '<?php echo esc_js($user->user_email); ?>', '<?php echo esc_js($user->display_name ?? ''); ?>']); } })();
        </script>
        <?php
    }

    protected function trackEndpointFromConfig(array $s): string
    {
        $endpoint = $s['endpoint'] ?? '';
        if (! $endpoint) {
            return '/r/track';
        }

        $base = preg_replace('#/r/webhooks/woocommerce/.*$#', '', $endpoint);

        return rtrim($base, '/').'/r/track';
    }

    /* ===== Hooks WooCommerce ===== */

    public function on_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data): void
    {
        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

        $this->fireWebhook('cart.updated', [
            'cart_item_key' => $cart_item_key,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $quantity,
            'product' => $product ? [
                'id' => $product->get_id(),
                'sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'url' => get_permalink($product->get_id()),
            ] : null,
            'cart' => $this->snapshotCart(),
            'customer' => $this->snapshotCustomer(),
        ]);
    }

    public function on_cart_updated(): void
    {
        $this->fireWebhook('cart.updated', [
            'cart' => $this->snapshotCart(),
            'customer' => $this->snapshotCustomer(),
        ]);
    }

    public function on_checkout_processed(int $order_id, array $posted_data, $order): void
    {
        $this->fireWebhook('checkout.created', [
            'order_id' => $order_id,
            'order' => $this->snapshotOrder($order),
            'customer' => $this->snapshotCustomer(),
        ]);
    }

    public function on_purchase(int $order_id): void
    {
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;

        if (! $order) {
            return;
        }

        $this->fireWebhook('order.completed', [
            'order_id' => $order_id,
            'order' => $this->snapshotOrder($order),
            'customer' => $this->snapshotCustomer(),
        ]);
    }

    /* ===== Helpers ===== */

    protected function settings(): array
    {
        $defaults = [
            'endpoint' => '',
            'pixel_url' => '',
            'store_token' => '',
            'shared_secret' => '',
        ];

        return array_merge($defaults, (array) get_option(self::OPTION_KEY, []));
    }

    protected function snapshotCart(): array
    {
        if (! function_exists('WC')) {
            return [];
        }

        $cart = WC()->cart;

        if (! $cart) {
            return [];
        }

        $items = [];

        foreach ($cart->get_cart() as $key => $item) {
            $product = $item['data'] ?? null;
            $items[] = [
                'product_id' => $item['product_id'] ?? null,
                'variation_id' => $item['variation_id'] ?? null,
                'quantity' => $item['quantity'] ?? 0,
                'price' => $product ? $product->get_price() : null,
                'name' => $product ? $product->get_name() : null,
                'image' => $product ? wp_get_attachment_url($product->get_image_id()) : null,
            ];
        }

        return [
            'items' => $items,
            'total' => (float) $cart->get_total('edit'),
            'currency' => get_woocommerce_currency(),
            'item_count' => $cart->get_cart_contents_count(),
            'cart_hash' => $cart->get_cart_hash(),
            'cart_url' => wc_get_cart_url(),
        ];
    }

    protected function snapshotCustomer(): array
    {
        $email = '';
        $first = '';
        $last = '';

        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $user = wp_get_current_user();
            $email = $user->user_email ?? '';
            $first = $user->first_name ?? '';
            $last = $user->last_name ?? '';
        }

        if (function_exists('WC') && WC() && WC()->customer) {
            $customer = WC()->customer;
            $email = $email ?: ($customer->get_billing_email() ?: '');
            $first = $first ?: ($customer->get_billing_first_name() ?: '');
            $last = $last ?: ($customer->get_billing_last_name() ?: '');
        }

        return [
            'email' => $email,
            'first_name' => $first,
            'last_name' => $last,
        ];
    }

    protected function snapshotOrder($order): array
    {
        if (! is_object($order)) {
            return [];
        }

        $items = [];

        foreach ($order->get_items() as $item) {
            $items[] = [
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'subtotal' => (float) $item->get_subtotal(),
                'total' => (float) $item->get_total(),
            ];
        }

        return [
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'total' => (float) $order->get_total(),
            'subtotal' => (float) $order->get_subtotal(),
            'discount' => (float) $order->get_discount_total(),
            'shipping' => (float) $order->get_shipping_total(),
            'tax' => (float) $order->get_total_tax(),
            'currency' => $order->get_currency(),
            'placed_at' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
            'customer' => [
                'email' => $order->get_billing_email(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone(),
                'country' => $order->get_billing_country(),
            ],
            'items' => $items,
        ];
    }

    protected function fireWebhook(string $topic, array $payload): void
    {
        $s = $this->settings();

        if (! $s['endpoint']) {
            return;
        }

        $body = wp_json_encode([
            'topic' => $topic,
            'fired_at' => gmdate('c'),
            'payload' => $payload,
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-WC-Webhook-Topic' => $topic,
            'User-Agent' => 'Remarketing-Woo-Bridge/'.self::VERSION,
        ];

        if (! empty($s['shared_secret'])) {
            $signature = base64_encode(hash_hmac('sha256', $body, $s['shared_secret'], true));
            $headers['X-WC-Webhook-Signature'] = $signature;
        }

        wp_remote_post($s['endpoint'], [
            'method' => 'POST',
            'timeout' => 5,
            'blocking' => false,
            'redirection' => 0,
            'headers' => $headers,
            'body' => $body,
        ]);
    }
}

Remarketing_Woo_Bridge::init();
