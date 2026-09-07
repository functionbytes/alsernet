<?php

/**
 * alsernetbridge — Log & response helpers
 *
 * Utility functions for API logging, TTL resolution, and JSON output.
 */

if (! defined('_PS_VERSION_')) {
    exit;
}

/**
 * Set HTTP status, optionally gzip the body, and echo it.
 * Replaces bare echo json_encode() calls throughout this file.
 *
 * Compression is applied only when:
 *   - The PHP gzencode() function is available
 *   - The client advertises Accept-Encoding: gzip
 *   - The serialised body is larger than 1 KB
 *
 * @param mixed $data       Data to JSON-encode
 * @param int   $statusCode HTTP status (default 200)
 * @return void
 */
function alsernet_send_json($data, $statusCode = 200)
{
    http_response_code($statusCode);
    $body   = json_encode($data);
    $accept = (string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '');

    if (
        function_exists('gzencode')
        && strpos($accept, 'gzip') !== false
        && strlen($body) > 1024
    ) {
        header('Content-Encoding: gzip');
        echo gzencode($body, 6);
    } else {
        echo $body;
    }
}

/**
 * Inserts a row into the API audit log table.
 *
 * @param string      $action     Action name dispatched
 * @param int|null    $idCustomer Resolved PS customer ID (or null)
 * @param string|null $email      Lookup email from payload
 * @param string      $ip         Client IP address
 * @param int         $statusCode HTTP status code returned
 * @param float       $startedAt  microtime(true) captured before processing
 * @param string|null $error      Optional error message
 */
function alsernet_log_api(
    string $action,
    ?int $idCustomer,
    ?string $email,
    string $ip,
    int $statusCode,
    float $startedAt,
    ?string $error = null
): void {
    try {
        Db::getInstance()->insert('alsernetbridge_api_log', [
            'action'      => pSQL(mb_substr($action, 0, 64)),
            'id_customer' => $idCustomer ? (int) $idCustomer : null,
            'email'       => $email       ? pSQL(mb_substr($email, 0, 255)) : null,
            'ip'          => pSQL(mb_substr($ip, 0, 45)),
            'status_code' => (int) $statusCode,
            'error'       => $error       ? pSQL(mb_substr($error, 0, 255)) : null,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'date_add'    => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        // Logging must never break the API response — silently swallow
        PrestaShopLogger::addLog('Alsernet API log insert failed: ' . $e->getMessage(), 2);
    }
}

/**
 * Returns the TTL in seconds for a given action, or 0 for write actions
 * that must never be cached.
 *
 * @param string $action
 * @return int
 */
function alsernet_ttl_for_action($action)
{
    static $ttls = [
        'customer.profile'          => 600,
        'customer.orders'           => 120,
        'customer.cart'             => 60,
        'customer.helpdesk_context' => 180,
        'customer.addresses'        => 600,
        'customer.vouchers'         => 300,
        'customer.returns'          => 300,
        'customer.messages'         => 60,
        'order.detail'              => 120,
        'order.states'              => 3600,
        // batch_context is never cached at batch level;
        // individual emails are cached inside the helper
        'customer.batch_context'    => 0,

        // Bonos: TTL corto. El dato cambia en cuanto alguien canjea, y el panel
        // lo sincroniza por lotes, no en cada carga de pantalla.
        'voucher.redemptions'       => 60,
        'voucher.status'            => 30,
    ];

    return isset($ttls[$action]) ? (int) $ttls[$action] : 0;
}
