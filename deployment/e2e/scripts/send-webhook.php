<?php

/**
 * E2E helper — fires a webhook from the bridge to the panel using the REAL
 * module code (AlsernetWebhookSender::sendToRemarketing), so the signed
 * request exercises the same cURL + HMAC + circuit-breaker path as production.
 *
 * Runs inside the `bridge` container:
 *   docker compose exec -T bridge php /e2e/send-webhook.php order.created
 *
 * Exit code: 0 if the panel answered 2xx, 1 otherwise.
 */

require '/var/www/html/config/config.inc.php';
require '/var/www/html/modules/alsernetbridge/lib/AlsernetWebhookSender.php';

$event = isset($argv[1]) ? (string) $argv[1] : 'order.created';
$data = isset($argv[2])
    ? (json_decode($argv[2], true) ?: [])
    : [
        'order_id' => 1001,
        'customer_id' => 1,
        'reference' => 'E2E000001',
        'email' => 'test@test.com',
        'total' => 94.99,
        'currency' => 'EUR',
    ];

$url = Configuration::get('ALSERNETBRIDGE_REMARKETING_WEBHOOK_URL');
fwrite(STDERR, "-> sendToRemarketing event={$event} url={$url}\n");

$ok = AlsernetWebhookSender::sendToRemarketing($event, $data);

echo $ok ? "webhook delivered (2xx)\n" : "webhook FAILED\n";
exit($ok ? 0 : 1);
