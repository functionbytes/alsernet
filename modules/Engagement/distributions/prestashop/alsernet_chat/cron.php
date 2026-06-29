<?php

/**
 * Alsernet Chat — Catalog sync cron entrypoint.
 *
 * Suggested cron (every 6 hours):
 *   0 *\/6 * * * curl -s -X POST \
 *       -H "X-Alsernet-Cron-Secret: YOUR_SECRET" \
 *       "https://shop.example.com/modules/alsernet_chat/cron.php?page=1"
 *
 * Backwards-compat: also accepts ?secret= in query (deprecated, leaks in logs).
 */

require_once dirname(__FILE__).'/../../config/config.inc.php';
require_once dirname(__FILE__).'/alsernet_chat.php';

$secret = (string) Configuration::get('ALSERNET_CHAT_WEBHOOK_SECRET');

// Prefer header (X-Alsernet-Cron-Secret); fallback to query string for legacy crons
$received = (string) (
    $_SERVER['HTTP_X_ALSERNET_CRON_SECRET']
    ?? Tools::getValue('secret', '')
);

if (! $secret || ! $received || ! hash_equals($secret, $received)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$module = new Alsernet_chat;
$page = max(1, (int) Tools::getValue('page', 1));
$result = $module->syncCatalog($page);

header('Content-Type: application/json');
echo json_encode($result);
