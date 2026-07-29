<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include(dirname(__FILE__) . '/../../config/config.inc.php');
die();
// --- Consulta de pedidos (30 días) con alias seguros ---
$sql = "
SELECT
  ao.id_order,
  aosl.name AS estado_presta,
  ao.current_state,
  ac.name AS transportista
FROM aalv_orders ao
LEFT JOIN aalv_order_state_lang aosl ON ao.current_state = aosl.id_order_state
LEFT JOIN aalv_carrier ac ON ac.id_carrier = ao.id_carrier
WHERE ao.date_add >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  AND aosl.id_lang = 1
  AND ao.id_customer != 892894
  AND ao.current_state NOT IN (4,57,75,76,6,63,26,41,74,78,7,56,77,72,71,61,55,39)
  and ac.name != ''
ORDER BY ao.id_order ASC
";

$orders = Db::getInstance()->ExecuteS($sql);


// Contexto HTTP (puedes agregar auth/headers si hace falta)
$headers = [
    "Accept: application/xml",
];
$context = stream_context_create([
    "http" => [
        "method" => "GET",
        "header" => implode("\r\n", $headers),
        "timeout" => 10,
        "ignore_errors" => true,
    ],
]);

function http_get_silent(string $url, $context)
{
    $content = @file_get_contents($url, false, $context);
    if ($content === false) return [false, null];
    return [true, $content];
}

/**
 * Devuelve:
 *  - ok: bool si se pudo parsear XML
 *  - has7: bool si existe un <estado>7</estado>
 *  - lastEstadoNum: int|null último estado por fecha (de la API)
 */
function analizarEstadosXML(string $xmlRaw): array
{
    $xmlRaw = trim($xmlRaw);
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlRaw);
    if ($xml === false) {
        return ['ok' => false, 'has7' => false, 'lastEstadoNum' => null];
    }
    $has7 = false;
    $lastEstadoNum = null;
    $lastFecha = null;

    foreach ($xml->resource as $res) {
        $estado = isset($res->estado) ? (int)$res->estado : null;
        $fecha  = isset($res->fecha)  ? (string)$res->fecha : null;

        if ($estado === 7) $has7 = true;

        if ($fecha !== null) {
            $ts = strtotime($fecha);
            if ($ts !== false && ($lastFecha === null || $ts >= $lastFecha)) {
                $lastFecha = $ts;
                $lastEstadoNum = $estado;
            }
        }
    }
    return ['ok' => true, 'has7' => $has7, 'lastEstadoNum' => $lastEstadoNum];
}

function evaluarTracking(?string $raw): string
{
    if ($raw === null || $raw === '') return 'NO';
    if (stripos($raw, 'Not Found') !== false) return 'NO';
    return 'SI';
}

// --- Construcción de filas ---
$rows = [];
foreach ($orders as $value) {
    $idOrder = (int)$value['id_order'];
    $estadoPresta = (string)$value['estado_presta'];
    $transportista = (string)($value['transportista'] ?? '');

    $urlEstados  = "http://127.0.0.1:58002/api-gestion/pedido-cliente-hist/?identificadororigen=" . $idOrder;
    $urlTracking = "http://127.0.0.1:58002/api-gestion/pedido-cliente-tracking/?identificadororigen=" . $idOrder;

    // 1) Llamada a historial de estados
    [$okEstados, $xmlEstados] = http_get_silent($urlEstados, $context);

    if (!$okEstados || $xmlEstados === null) {
        // No responde: estado=no, tracking=no (Estado y Transportista se muestran igual)
        $rows[] = [
            'id' => $idOrder,
            'estadoApi' => 'no',         // num de la API
            'estadoPresta' => $estadoPresta,
            'transportista' => $transportista,
            'tracking' => 'no',
        ];
        continue;
    }

    $analisis = analizarEstadosXML($xmlEstados);
    if (!$analisis['ok']) {
        $rows[] = [
            'id' => $idOrder,
            'estadoApi' => 'no',
            'estadoPresta' => $estadoPresta,
            'transportista' => $transportista,
            'tracking' => 'no',
        ];
        continue;
    }

    $estadoApiNum = $analisis['lastEstadoNum']; // puede ser null si no hay fechas válidas
    $estadoApi = $estadoApiNum !== null ? $estadoApiNum : '—';

    switch ($estadoApi) {
        case 0: // ESTADO GESTIÓN ANULADO - Gestion
            $estadoApi = 'ANULADO';
            break;
        case 1: // ESTADO GESTIÓN EN CREACIÓN - Gestion
            $estadoApi = 'EN CREACIÓN';
            break;
        case 2: // ESTADO GESTIÓN REVISIÓN TRANSPORTISTA - Gestion
            $estadoApi = 'REVISIÓN TRANSPORTISTA';
            break;
        case 3: // ESTADO GESTIÓN ACEPTACIÓN FINANCIERA
            $estadoApi = 'ACEPTACIÓN FINANCIERA';
            break;
        case 4: // ESTADO GESTIÓN PENDIENTE DE MERCANCÍA - Gestion
            $estadoApi = 'PENDIENTE DE MERCANCÍA';
            break;
        case 8: // ESTADO GESTIÓN INCIDENCIA - Gestion
            $estadoApi = 'INCIDENCIA';
            break;
        case 9: // ESTADO GESTIÓN ACEPTACIÓN FINANCIERA RESERVANDO
            $estadoApi = 'ACEPTACIÓN FINANCIERA RESERVANDO';
            break;
        case 5: // ESTADO GESTIÓN LISTO PARA SERVIR
            $estadoApi = 'LISTO PARA SERVIR';
            break;
        case 6: // ESTADO GESTIÓN SIRVIÉNDOSE
            $estadoApi = 'SIRVIÉNDOSE';
            break;
        case 7: // ESTADO GESTIÓN SERVIDO
            $estadoApi = 'SERVIDO';
            break;
        case 10: // ESTADO GESTIÓN SERVIDO PARCIALMENTE
            $estadoApi = 'SERVIDO PARCIALMENTE';
            break;

        default:
            $estadoApi = $estadoApi;
            break;
    }

    // 2) Tracking solo si hay estado 7 en la API
    $tracking = 'NO';
    if ($analisis['has7']) {
        [$okTrack, $rawTrack] = http_get_silent($urlTracking, $context);
        $tracking = ($okTrack && $rawTrack !== null) ? evaluarTracking($rawTrack) : 'NO';
    } else {
        $tracking = 'NO';
    }

    $rows[] = [
        'id' => $idOrder,
        'estadoApi' => $estadoApi,
        'estadoPresta' => $estadoPresta,
        'transportista' => $transportista,
        'tracking' => $tracking,
    ];
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estado de pedidos</title>
    <!-- Opcional: Bootstrap 4.5.2 para estilos rápidos -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body class="p-3">
    <div class="container-fluid">
        <h1 class="h4 mb-3">Estado de pedidos (últimos 30 días)</h1>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>Id_orden</th>
                        <th>estado</th> <!-- Último estado num de la API -->
                        <th>Estado</th> <!-- aosl.name (PrestaShop) -->
                        <th>Transportista</th> <!-- ac.name -->
                        <th>tracking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td><?= htmlspecialchars($r['estadoApi']) ?></td>
                            <td><?= htmlspecialchars($r['estadoPresta']) ?></td>
                            <td><?= htmlspecialchars($r['transportista']) ?></td>
                            <td>
                                <?php
                                $val = strtolower((string)$r['tracking']);
                                if ($val === 'si') {
                                    echo '<span class="badge badge-success">SI - Maxi</span>';
                                } elseif ($val === 'no') {
                                    echo '<span class="badge badge-secondary">NO - Maxi</span>';
                                } else {
                                    echo htmlspecialchars($r['tracking']);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0">
            * <strong>estado</strong>: último estado numérico reportado por la API (según fecha).
            * <strong>Estado</strong>: estado actual del pedido en PrestaShop.
            * El tracking se consulta solo si la API reporta al menos un estado <code>7</code>.
            * Si la API de estados no responde, se muestra <code>estado = no</code> y <code>tracking = no</code>.
        </p>
    </div>
</body>

</html>