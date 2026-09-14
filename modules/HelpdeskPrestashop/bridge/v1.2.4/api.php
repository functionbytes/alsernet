<?php

/**
 * Alsernet Bridge — PrestaShop API endpoint (pull-on-demand)
 *
 * Receives signed POST requests from the Alsernet panel and returns
 * customer data on demand: profile, orders, returns, vouchers, cart,
 * messages, addresses, order detail, and order returns initiation.
 *
 * Auth:
 *   - HMAC-SHA256 of the raw body using ALSERNETBRIDGE_WEBHOOK_SECRET
 *   - Signature in header `X-Alsernet-Signature`
 *   - Action in header `X-Alsernet-Action` (also accepted as body field)
 *   - Write actions additionally REQUIRE `X-Alsernet-Idempotency-Key`
 *     (400 without it) — closes the 5-minute HMAC replay window.
 *
 * Lookup:
 *   - { "lookup": { "email": "...", "external_id": 123 } }
 *
 * Response:
 *   { "ok": true, "data": ... }
 *   { "ok": false, "error": "..." }
 */

require_once dirname(__FILE__).'/../../config/config.inc.php';
require_once dirname(__FILE__).'/lib/AlsernetCache.php';

// Helpers split (refactor 2026)
require_once dirname(__FILE__).'/helpers/log.php';
require_once dirname(__FILE__).'/helpers/validation.php';
require_once dirname(__FILE__).'/helpers/order.php';
require_once dirname(__FILE__).'/helpers/customer.php';
require_once dirname(__FILE__).'/helpers/product.php';
require_once dirname(__FILE__).'/helpers/giftmessage.php';
require_once dirname(__FILE__).'/helpers/voucher.php';

header('Content-Type: application/json; charset=utf-8');

$startedAt = microtime(true);

$rawBody = file_get_contents('php://input');
$secret = (string) Configuration::get('ALSERNETBRIDGE_WEBHOOK_SECRET');
$received = (string) ($_SERVER['HTTP_X_ALSERNET_SIGNATURE'] ?? '');

// Item 1: Anti-replay — validate timestamp within 5-minute skew window
$timestamp = (int) ($_SERVER['HTTP_X_ALSERNET_TIMESTAMP'] ?? 0);
$now = time();
$skew = abs($now - $timestamp);
$maxSkew = 300; // 5 minutes
$legacyMode = (bool) Configuration::get('ALSERNETBRIDGE_LEGACY_HMAC');

if ($timestamp === 0 || $skew > $maxSkew) {
    // When LEGACY_HMAC flag is active and timestamp validation fails, fall through
    // to legacy HMAC check so deployments can roll out both sides independently.
    if (! $legacyMode || $timestamp !== 0) {
        alsernet_send_json(['ok' => false, 'error' => 'invalid or expired timestamp'], 401);
        exit;
    }
}

// Validate HMAC: new format = hash_hmac(sha256, "$timestamp:$body", $secret)
// Legacy format (no timestamp) = hash_hmac(sha256, $body, $secret)
$validSignature = false;

if ($timestamp !== 0 && $skew <= $maxSkew) {
    $expected = hash_hmac('sha256', $timestamp.':'.$rawBody, $secret);
    $validSignature = $secret && $received && hash_equals($expected, $received);
}

if (! $validSignature && $legacyMode) {
    // Try legacy signature as backwards-compat fallback during rolling deploy
    $expectedLegacy = hash_hmac('sha256', $rawBody, $secret);
    $validSignature = $secret && $received && hash_equals($expectedLegacy, $received);
    if ($validSignature) {
        PrestaShopLogger::addLog('Alsernet API: legacy HMAC accepted — update Laravel caller.', 2);
    }
}

if (! $validSignature) {
    alsernet_send_json(['ok' => false, 'error' => 'invalid signature'], 401);
    exit;
}

// Rate limiting: max 60 requests per minute per IP (checked after HMAC to avoid unauth DB hits)
$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$rateCount = (int) Db::getInstance()->getValue(
    'SELECT COUNT(*) FROM `'._DB_PREFIX_.'alsernetbridge_api_log`
     WHERE ip = "'.pSQL($clientIp).'"
       AND date_add >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)'
);

if ($rateCount > 60) {
    alsernet_send_json(['ok' => false, 'error' => 'rate limit exceeded'], 429);
    alsernet_log_api('rate_limit', null, null, $clientIp, 429, $startedAt, 'rate limit exceeded');
    exit;
}

$payload = json_decode($rawBody, true) ?: [];
$action = (string) ($_SERVER['HTTP_X_ALSERNET_ACTION'] ?? $payload['action'] ?? '');
$lookup = (array) ($payload['lookup'] ?? []);
$email = isset($lookup['email']) ? trim((string) $lookup['email']) : null;
$externalId = isset($lookup['external_id']) ? (int) $lookup['external_id'] : null;

// Item 2: Idempotency key check for write actions
require_once dirname(__FILE__).'/lib/AlsernetIdempotency.php';

$writeActions = ['customer.add_message', 'order.add_note', 'order.start_return', 'order.change_status', 'order.set_tracking', 'order.set_address', 'order.send_email', 'order.flag_for_erp_send', 'customer.fix_anonymous_profile'];
$idempotencyKey = (string) ($_SERVER['HTTP_X_ALSERNET_IDEMPOTENCY_KEY'] ?? '');
$isWriteAction = in_array($action, $writeActions, true);

// Write actions REQUIRE an idempotency key: without it a request replayed
// inside the 5-minute HMAC timestamp window would re-execute the mutation.
if ($isWriteAction && $idempotencyKey === '') {
    alsernet_send_json(['ok' => false, 'error' => 'X-Alsernet-Idempotency-Key header is required for write actions'], 400);
    alsernet_log_api($action, null, $email, $clientIp, 400, $startedAt, 'missing idempotency key');
    exit;
}

if ($isWriteAction && $idempotencyKey !== '') {
    $stored = AlsernetIdempotency::get($idempotencyKey);
    if ($stored) {
        http_response_code($stored['status_code']);
        echo $stored['body'];
        $idCustomerForLog = null; // not resolved yet at this point
        alsernet_log_api($action, $idCustomerForLog, $email, $clientIp, $stored['status_code'], $startedAt);
        exit;
    }
}

try {
    $idCustomer = alsernet_resolve_customer($email, $externalId);

    $orderId = (int) ($payload['order_id'] ?? 0);

    // Cache lookup — write operations are never cached (TTL = 0)
    $cacheTtl = alsernet_ttl_for_action($action);
    $cacheKey = null;
    $stampedeLock = false;

    // Acciones que no van sobre un cliente concreto (bonos, gift message):
    // se cachean igual, pero bajo un prefijo propio. Con la condicion antigua
    // ($idCustomer obligatorio) no se cacheaban nunca; y guardarlas bajo
    // 'cust:0' seria peor, porque ese prefijo no lo invalida ningun hook y la
    // entrada se quedaria colgada hasta que caducara sola.
    $isCustomerScoped = (bool) $idCustomer;

    if ($cacheTtl > 0 && ($idCustomer || strpos($action, 'voucher.') === 0)) {
        $cacheVersion = (string) Configuration::get('ALSERNETBRIDGE_CACHE_VERSION') ?: '1';
        $idShop = (int) Context::getContext()->shop->id;

        // Incluir hash de params relevantes para que distintos limit/offset/from/to/status
        // no colisionen en el mismo cacheKey (legacy vs paginado, filtros de fecha, etc.).
        $cacheParams = [];
        foreach (['limit', 'offset', 'from', 'to', 'status', 'emails', 'codes', 'name_like'] as $k) {
            if (isset($payload[$k])) {
                $cacheParams[$k] = $payload[$k];
            }
        }
        $paramsHash = empty($cacheParams) ? '' : ':p'.substr(md5(json_encode($cacheParams)), 0, 8);

        $cacheKey = 'ab:v'.$cacheVersion.':s'.$idShop.':'.$action.':'.(int) $idCustomer.':'.$orderId.$paramsHash;
        $cached = AlsernetCache::get($cacheKey);

        if ($cached !== null) {
            alsernet_send_json(['ok' => true, 'data' => $cached]);
            alsernet_log_api($action, $idCustomer, $email, $clientIp, 200, $startedAt);
            exit;
        }

        // Item 5: Stampede prevention — try to acquire lock before regenerating
        $stampedeLock = AlsernetCache::acquireLock($cacheKey, 10);
        if ($stampedeLock) {
            // Re-check: another request may have populated the cache while we waited
            $cached = AlsernetCache::get($cacheKey);
            if ($cached !== null) {
                AlsernetCache::releaseLock($stampedeLock);
                alsernet_send_json(['ok' => true, 'data' => $cached]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 200, $startedAt);
                exit;
            }
        }
        // Lock not acquired = another request is already regenerating; proceed without caching
    }

    switch ($action) {
        case 'customer.profile':
            $result = alsernet_customer_profile($idCustomer);
            break;

        case 'customer.orders':
            // Detect if the caller explicitly requested pagination/filtering.
            // Any of these keys in the payload triggers the new paginated format.
            $hasPaginationOpts = isset($payload['limit'])
                || isset($payload['offset'])
                || isset($payload['from'])
                || isset($payload['to'])
                || isset($payload['status']);
            $ordersOpts = $hasPaginationOpts
                ? [
                    'limit' => isset($payload['limit']) ? (int) $payload['limit'] : 10,
                    'offset' => isset($payload['offset']) ? (int) $payload['offset'] : 0,
                    'from' => isset($payload['from']) ? (string) $payload['from'] : null,
                    'to' => isset($payload['to']) ? (string) $payload['to'] : null,
                    'status' => isset($payload['status']) ? (int) $payload['status'] : null,
                ]
                : [];
            $result = alsernet_customer_orders($idCustomer, $ordersOpts);
            break;

        case 'customer.returns':
            $result = alsernet_customer_returns($idCustomer);
            break;

        case 'customer.vouchers':
            $result = alsernet_customer_vouchers($idCustomer);
            break;

        case 'customer.cart':
            $result = alsernet_customer_cart($idCustomer);
            break;

        case 'customer.messages':
            $result = alsernet_customer_messages($idCustomer);
            break;

        case 'customer.addresses':
            $result = alsernet_customer_addresses($idCustomer);
            break;

        case 'customer.helpdesk_context':
            $result = alsernet_customer_helpdesk_context($idCustomer);
            break;

        case 'customer.batch_context':
            $emails = (array) ($payload['emails'] ?? []);
            $result = alsernet_customer_batch_context($emails);
            break;

        case 'customer.add_message':
            $err = alsernet_validate_payload($payload, [
                'message' => 'string:1:5000',
                'agent_name' => 'string:1:100',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_customer_add_message($idCustomer, $payload);
            break;

        case 'order.detail':
            // Endurecimiento de propiedad: si se pidió un lookup por
            // email/external_id pero NO resolvió a un cliente, no se devuelve el
            // pedido. Evita que, pasando un email inexistente en PrestaShop, se
            // pueda leer el detalle (PII: nombre, dirección, teléfono, pagos) de
            // un pedido arbitrario por su id. Sin lookup se mantiene el
            // comportamiento previo (llamadas admin/legacy sin cliente).
            if (($email || $externalId) && ! $idCustomer) {
                $result = null;
                break;
            }
            // Búsqueda por reference cuando no se manda order_id — mismo patrón
            // que product.get (product_id O reference).
            $orderIdForDetail = $orderId ?: alsernet_order_resolve_id_by_reference((string) ($payload['reference'] ?? ''));
            $result = alsernet_order_detail($orderIdForDetail, $idCustomer);
            break;

        case 'order.states':
            $result = ['states' => alsernet_order_states()];
            break;

        case 'order.search':
            $result = ['orders' => alsernet_order_search($payload)];
            break;

        case 'order.documents':
            if (($email || $externalId) && ! $idCustomer) {
                $result = null;
                break;
            }
            $result = alsernet_order_documents($orderId, $idCustomer);
            break;

        case 'order.set_address':
            $err = alsernet_validate_payload($payload, [
                'order_id' => 'int>0',
                'address_id' => 'int>0',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_order_set_address($orderId, $payload, $idCustomer);
            break;

        case 'order.send_email':
            $err = alsernet_validate_payload($payload, [
                'order_id' => 'int>0',
                'type' => 'string:1:40',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_order_send_email($orderId, $payload, $idCustomer);
            break;

        case 'order.add_note':
            $err = alsernet_validate_payload($payload, [
                'note' => 'string:1:5000',
                'agent_name' => 'string:1:100',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            // Igual que order.detail / change_status: si se envió un lookup que
            // NO resolvió a un cliente, se rechaza (no se puede anotar un pedido
            // arbitrario pasando un email que no existe en PrestaShop).
            if (($email || $externalId) && ! $idCustomer) {
                $result = null;
                break;
            }
            $result = alsernet_order_add_note($orderId, $payload, $idCustomer);
            break;

        case 'order.start_return':
            $err = alsernet_validate_payload($payload, [
                'order_id' => 'int>0',
                'items' => 'array:1',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_order_start_return(
                $orderId,
                (array) ($payload['items'] ?? []),
                $idCustomer
            );
            break;

        case 'order.change_status':
            $err = alsernet_validate_payload($payload, [
                'order_id' => 'int>0',
                'state_id' => 'int>0',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_order_change_status($orderId, $payload, $idCustomer);
            break;

        case 'order.set_tracking':
            $err = alsernet_validate_payload($payload, [
                'order_id' => 'int>0',
                'tracking_number' => 'string:1:64',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_order_set_tracking($orderId, $payload, $idCustomer);
            break;

        case 'order.flag_for_erp_send':
            $err = alsernet_validate_payload($payload, [
                'order_id' => 'int>0',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_order_flag_for_erp_send($payload);
            break;

        case 'customer.fix_anonymous_profile':
            $err = alsernet_validate_payload($payload, [
                'customer_id' => 'int>0',
                'firstname' => 'string:1:255',
                'lastname' => 'string:1:255',
                'email' => 'string:1:255',
            ]);
            if ($err) {
                if ($stampedeLock) {
                    AlsernetCache::releaseLock($stampedeLock);
                }
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => $err]);
                alsernet_log_api($action, $idCustomer, $email, $clientIp, 422, $startedAt);
                exit;
            }
            $result = alsernet_customer_fix_anonymous_profile($payload);
            break;

        case 'product.get':
            $result = alsernet_product_get($payload);
            break;

        case 'product.search':
            $products = alsernet_product_search($payload);
            $result = ['products' => $products];
            break;

        case 'product.categories':
            $result = ['categories' => alsernet_product_categories($payload)];
            break;

        case 'product.price_detail':
            $result = alsernet_product_price_detail($payload);
            break;

        case 'specific_price.list':
            $result = ['items' => alsernet_specific_price_list($payload)];
            break;

        case 'giftmessage.orders_with_message':
            $result = ['orders' => alsernet_giftmessage_orders_with_message()];
            break;

        case 'giftmessage.search_by_gestion':
            $gestionIds = array_values(array_filter(array_map('strval', (array) ($payload['gestion_ids'] ?? [])), function ($v) {
                return $v !== '';
            }));
            $result = ['orders' => alsernet_giftmessage_search_by_gestion($gestionIds)];
            break;

        case 'voucher.redemptions':
            // Lectura sobre un rango de fechas, sin cliente: el bono de Gestion
            // se crea con id_customer = 0, asi que no hay a quien resolver.
            $result = alsernet_voucher_redemptions([
                'from' => isset($payload['from']) ? (string) $payload['from'] : null,
                'to' => isset($payload['to']) ? (string) $payload['to'] : null,
                'name_like' => isset($payload['name_like']) ? (string) $payload['name_like'] : null,
                'codes' => isset($payload['codes']) ? (array) $payload['codes'] : null,
                'limit' => isset($payload['limit']) ? (int) $payload['limit'] : 200,
                'offset' => isset($payload['offset']) ? (int) $payload['offset'] : 0,
            ]);
            break;

        case 'voucher.status':
            $result = alsernet_voucher_status((array) ($payload['codes'] ?? []));
            break;

        default:
            $result = null;
    }

    if ($result === null) {
        if ($stampedeLock) {
            AlsernetCache::releaseLock($stampedeLock);
        }
        alsernet_send_json(['ok' => false, 'error' => 'unknown action or customer not found'], 404);
        alsernet_log_api($action, $idCustomer, $email, $clientIp, 404, $startedAt);
        exit;
    }

    // Store in cache before returning (only cacheable read actions with a resolved customer).
    //
    // Prefix MUST be the short 'cust:<id>' — it is what the module hooks
    // invalidate via AlsernetCache::forgetByPrefix('cust:<id>') on order/cart/
    // customer changes, and forgetByPrefix matches the md5 of the EXACT prefix
    // string (no prefix scan). Version/shop scoping lives in $cacheKey itself
    // ('ab:v{ver}:s{shop}:...'), so bumping ALSERNETBRIDGE_CACHE_VERSION still
    // invalidates by key change; the prefix index only drives hook invalidation.
    if ($cacheKey !== null && $stampedeLock) {
        AlsernetCache::putWithPrefix(
            $isCustomerScoped ? 'cust:'.(int) $idCustomer : 'voucher:',
            $cacheKey,
            $result,
            $cacheTtl
        );
        AlsernetCache::releaseLock($stampedeLock);
    } elseif ($cacheKey !== null && ! $stampedeLock) {
        // Lock was not acquired (another request is populating) — skip storing to avoid double-write
    }

    $responseBody = json_encode(['ok' => true, 'data' => $result]);

    // Item 2: Persist idempotency for write actions so replay works
    if ($isWriteAction && $idempotencyKey !== '') {
        AlsernetIdempotency::put($idempotencyKey, $action, 200, $responseBody);
    }

    alsernet_send_json(['ok' => true, 'data' => $result]);
    alsernet_log_api($action, $idCustomer, $email, $clientIp, 200, $startedAt);
} catch (Throwable $e) {
    if (isset($stampedeLock) && $stampedeLock) {
        AlsernetCache::releaseLock($stampedeLock);
    }
    PrestaShopLogger::addLog('Alsernet API error: '.$e->getMessage(), 3);
    alsernet_send_json(['ok' => false, 'error' => 'internal error'], 500);
    alsernet_log_api($action ?? '', $idCustomer ?? null, $email ?? null, $clientIp, 500, $startedAt, $e->getMessage());
}

// Functions moved to helpers/ — loaded via require_once above.
