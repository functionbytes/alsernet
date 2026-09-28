<?php

namespace Modules\HelpdeskLivechat\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Events\WidgetSessionUpdated;
use Modules\HelpdeskLivechat\Jobs\ResolveWidgetSessionGeoJob;
use Modules\HelpdeskLivechat\Models\WidgetPageView;
use Modules\HelpdeskLivechat\Models\WidgetSession;

class WidgetSessionService
{
    private const HEARTBEAT_COOLDOWN_DEFAULT_SECONDS = 5;

    /**
     * Process a heartbeat from the widget JS.
     * Creates or updates the session, appends a page view if URL changed.
     *
     * Uses a Redis gate to skip DB reads/writes when the session is within the
     * cooldown window and the URL has not changed. The gate stores
     * `url|session_id` so the response can be built without touching the DB.
     */
    public function heartbeat(string $token, string $url, ?string $title, Request $request): WidgetSession
    {
        $cooldown = $this->resolveCooldownSeconds();
        $cacheKey = 'helpdesklivechat:hb:'.$token;

        $product = $this->extractProduct($request);
        $commerce = $this->extractCommerce($request);

        // Huella de lo que el agente ve en vivo (producto, cesta, vistos): si
        // cambia sin cambiar la URL (p. ej. añade al carrito) hay que persistir
        // y emitir, así que el atajo solo vale cuando URL y huella coinciden.
        $fingerprint = md5((string) json_encode([$product, $commerce]));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            [$cachedUrl, $cachedSessionId, $cachedFingerprint] = explode('|', $cached, 3) + [null, null, null];

            if ($cachedUrl === $url && $cachedSessionId !== null && $cachedFingerprint === $fingerprint) {
                // Fast-path: same URL and context within cooldown — return a minimal stub without hitting the DB.
                $stub = new WidgetSession(['session_token' => $token]);
                $stub->id = (int) $cachedSessionId;

                return $stub;
            }
        }

        $session = WidgetSession::where('session_token', $token)->first();

        if (! $session) {
            $session = $this->createSession($token, $url, $title, $request, $product, $commerce);
        } else {
            $this->updateSession($session, $url, $title, $request, $product, $commerce);
        }

        // Refresh the Redis gate with current URL, session id and context fingerprint.
        Cache::put($cacheKey, $url.'|'.$session->id.'|'.$fingerprint, $cooldown);

        $conversationId = Cache::get('helpdesklivechat:session_conv:'.$token);
        if ($conversationId) {
            WidgetSessionUpdated::dispatch($session, (int) $conversationId);
        }

        return $session;
    }

    /**
     * @param  array{cart?: array<string, mixed>|null, viewed_products?: list<array<string, mixed>>}  $commerce
     */
    private function createSession(string $token, string $url, ?string $title, Request $request, ?array $product = null, array $commerce = []): WidgetSession
    {
        $ip = $request->ip();

        $session = WidgetSession::create([
            'session_token' => $token,
            'current_url' => $url,
            'referrer' => $request->header('Referer'),
            'device' => $this->extractDevice($request),
            'ip_address' => $ip,
            // country_code se resuelve en background: el primer geoip() por IP
            // puede pegar a un proveedor remoto y colgar el arranque de sesión.
            'country_code' => null,
            'current_product' => $product,
            'cart_snapshot' => $commerce['cart'] ?? null,
            'cart_updated_at' => array_key_exists('cart', $commerce) ? now() : null,
            'viewed_products' => $commerce['viewed_products'] ?? null,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        WidgetPageView::create([
            'session_id' => $session->id,
            'url' => $url,
            'title' => $title,
            'viewed_at' => now(),
        ]);

        if ($ip !== null && $this->isPublicIp($ip)) {
            ResolveWidgetSessionGeoJob::dispatch($session->id, $ip)->afterCommit();
        }

        return $session;
    }

    /**
     * @param  array{cart?: array<string, mixed>|null, viewed_products?: list<array<string, mixed>>}  $commerce
     */
    private function updateSession(WidgetSession $session, string $url, ?string $title, Request $request, ?array $product = null, array $commerce = []): void
    {
        $urlChanged = $session->current_url !== $url;
        $lastActivity = $session->last_activity_at;
        $cooldown = $this->resolveCooldownSeconds();
        $cooldownPassed = $lastActivity === null
            || $lastActivity->diffInSeconds(now()) >= $cooldown;

        $session->last_activity_at = now();

        if ($urlChanged) {
            $session->current_url = $url;
        }

        // El producto actual se actualiza cuando el heartbeat lo trae; salir de
        // una ficha (product ausente en un cambio de URL) lo limpia.
        if ($product !== null) {
            $session->current_product = $product;
        } elseif ($urlChanged) {
            $session->current_product = null;
        }

        // Cesta: solo cuando el latido la trae (ausente = el widget aún no la
        // leyó; null = el visitante no tiene cesta y sí se guarda).
        if (array_key_exists('cart', $commerce) && $commerce['cart'] !== $session->cart_snapshot) {
            $session->cart_snapshot = $commerce['cart'];
            $session->cart_updated_at = now();
        }

        if (array_key_exists('viewed_products', $commerce)) {
            $session->viewed_products = $commerce['viewed_products'];
        }

        // Refresh device info if it was missing (e.g. session bootstrapped without a real UA).
        $device = $session->device ?? [];
        if (empty($device['browser']) || $device['browser'] === 'Unknown') {
            $session->device = $this->extractDevice($request);
        }

        $session->saveQuietly();

        if ($urlChanged || $cooldownPassed) {
            WidgetPageView::create([
                'session_id' => $session->id,
                'url' => $url,
                'title' => $title,
                'viewed_at' => now(),
            ]);
        }
    }

    private function resolveCooldownSeconds(): int
    {
        return Cache::remember(
            'helpdesklivechat:heartbeat_cooldown',
            now()->addMinutes(5),
            fn (): int => (int) (Setting::get('livechat.tracking.heartbeat_cooldown_seconds')
                ?? self::HEARTBEAT_COOLDOWN_DEFAULT_SECONDS)
        );
    }

    /**
     * Normaliza el producto que el visitante está viendo, reportado por el
     * snippet en el heartbeat. Devuelve null si no hay id de producto válido.
     *
     * @return array<string, mixed>|null
     */
    private function extractProduct(Request $request): ?array
    {
        $product = $request->input('product');
        if (! is_array($product) || empty($product['id'])) {
            return null;
        }

        return [
            'id' => (string) $product['id'],
            'id_product_attribute' => isset($product['id_product_attribute']) ? (int) $product['id_product_attribute'] : null,
            'title' => isset($product['title']) ? (string) $product['title'] : null,
            'image_url' => $this->safeUrl($product['image_url'] ?? null),
            'url' => $this->safeUrl($product['url'] ?? null),
            'price' => isset($product['price']) ? (float) $product['price'] : null,
            'currency' => isset($product['currency']) ? (string) $product['currency'] : null,
        ];
    }

    /**
     * Cesta en vivo y productos vistos que reporta el widget. Solo incluye
     * cada clave si el latido la trae, para distinguir "sin cesta" (null) de
     * "cesta aún no leída" (clave ausente).
     *
     * @return array{cart?: array<string, mixed>|null, viewed_products?: list<array<string, mixed>>}
     */
    private function extractCommerce(Request $request): array
    {
        $out = [];

        if ($request->has('cart')) {
            $cart = $request->input('cart');
            $out['cart'] = is_array($cart) && ! empty($cart['id']) ? [
                'id' => (int) $cart['id'],
                'products_count' => (int) ($cart['products_count'] ?? 0),
                'total' => isset($cart['total']) ? round((float) $cart['total'], 2) : null,
                'total_products' => isset($cart['total_products']) ? round((float) $cart['total_products'], 2) : null,
                'currency' => isset($cart['currency']) ? (string) $cart['currency'] : null,
                'customer_logged' => (bool) ($cart['customer_logged'] ?? false),
                'lines' => array_values(array_map(fn (array $line): array => [
                    'id_product' => (int) $line['id_product'],
                    'id_product_attribute' => (int) ($line['id_product_attribute'] ?? 0),
                    'name' => isset($line['name']) ? (string) $line['name'] : null,
                    'attributes' => isset($line['attributes']) ? (string) $line['attributes'] : null,
                    'reference' => isset($line['reference']) ? (string) $line['reference'] : null,
                    'qty' => (int) $line['qty'],
                    'price' => isset($line['price']) ? (float) $line['price'] : null,
                    'total' => isset($line['total']) ? (float) $line['total'] : null,
                    'image_url' => $this->safeUrl($line['image_url'] ?? null),
                    'url' => $this->safeUrl($line['url'] ?? null),
                ], array_filter((array) ($cart['lines'] ?? []), 'is_array'))),
            ] : null;
        }

        if ($request->has('viewed_products')) {
            $out['viewed_products'] = array_values(array_map(fn (array $v): array => [
                'id' => (string) $v['id'],
                'title' => isset($v['title']) ? (string) $v['title'] : null,
                'image_url' => $this->safeUrl($v['image_url'] ?? null),
                'url' => $this->safeUrl($v['url'] ?? null),
                'price' => isset($v['price']) ? (float) $v['price'] : null,
                'currency' => isset($v['currency']) ? (string) $v['currency'] : null,
                'viewed_at' => isset($v['viewed_at']) ? (string) $v['viewed_at'] : null,
            ], array_slice(array_filter((array) $request->input('viewed_products', []), 'is_array'), 0, 20)));
        }

        return $out;
    }

    /**
     * Solo http(s) o relativas al protocolo: el panel pinta estas URLs como
     * enlaces e imágenes y no debe aceptar javascript: ni data:.
     */
    private function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        return preg_match('#^(https?:)?//#i', $url) === 1 ? $url : null;
    }

    private function extractDevice(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';

        return [
            'user_agent' => $userAgent,
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOs($userAgent),
        ];
    }

    private function detectBrowser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Chrome') && ! str_contains($ua, 'Edg') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome') => 'Safari',
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'MSIE') || str_contains($ua, 'Trident') => 'IE',
            default => 'Unknown',
        };
    }

    private function detectOs(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') && ! str_contains($ua, 'Android') => 'Linux',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            default => 'Unknown',
        };
    }

    /**
     * Resolves a public IP to an ISO country code, cached 24h per IP. Invoked
     * from ResolveWidgetSessionGeoJob so the (potentially remote) geoip lookup
     * never blocks the widget's session bootstrap. Returns null for private IPs.
     */
    public function resolveCountryForIp(string $ip): ?string
    {
        if (! $this->isPublicIp($ip)) {
            return null;
        }

        // Cache by IP for 24h to avoid hitting the geoip database on every heartbeat.
        return Cache::remember(
            'helpdesklivechat:geoip:'.md5($ip),
            now()->addDay(),
            function () use ($ip): ?string {
                try {
                    $location = @geoip($ip);

                    return $location?->iso_code ?: null;
                } catch (\Throwable) {
                    return null;
                }
            }
        );
    }

    private function isPublicIp(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
