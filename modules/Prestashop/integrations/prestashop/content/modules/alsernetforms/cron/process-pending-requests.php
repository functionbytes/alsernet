<?php
/**
 * Cron script para procesar peticiones pendientes
 *
 * Este script debe ejecutarse periódicamente (recomendado: cada 5-15 minutos)
 * para procesar peticiones que quedaron pendientes debido a indisponibilidad del servidor.
 *
 * CONFIGURACIÓN CRON:
 * */5 * * * * /usr/bin/php /path/to/prestashop/modules/alsernetforms/cron/process-pending-requests.php >> /var/log/prestashop-pending-requests.log 2>&1
 *
 * O desde la configuración de PrestaShop (CronJobs module):
 * URL: https://tu-tienda.com/modules/alsernetforms/cron/process-pending-requests.php?secure_key=TU_CLAVE_SEGURA
 */

// Ruta al archivo config de PrestaShop
$configPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.inc.php';

if (!file_exists($configPath)) {
    die('Error: No se pudo encontrar config.inc.php');
}

require_once($configPath);
require_once(dirname(dirname(__FILE__)) . '/classes/PendingRequestsProcessor.php');

// Verificación de seguridad mediante token
define('SECURE_KEY', Configuration::get('ALSERNETFORMS_CRON_SECURE_KEY', 'change_this_secure_key'));

// Si se ejecuta vía web, verificar el token de seguridad
if (php_sapi_name() !== 'cli') {
    $providedKey = Tools::getValue('secure_key');
    if ($providedKey !== SECURE_KEY) {
        header('HTTP/1.1 403 Forbidden');
        die('Access denied: Invalid secure key');
    }
}

// Log inicio
$startTime = microtime(true);
echo '[' . date('Y-m-d H:i:s') . '] Starting pending requests processor...' . PHP_EOL;

try {
    // Crear instancia del procesador
    $processor = new PendingRequestsProcessor();

    // Mostrar estadísticas antes del procesamiento
    echo '[' . date('Y-m-d H:i:s') . '] Getting pending requests stats...' . PHP_EOL;
    $pendingStats = $processor->getPendingStats();

    if (!empty($pendingStats)) {
        echo '[' . date('Y-m-d H:i:s') . '] Pending requests by type and status:' . PHP_EOL;
        foreach ($pendingStats as $stat) {
            echo sprintf(
                '  - %s [%s]: %d requests (oldest: %s, max retries: %d)' . PHP_EOL,
                $stat['endpoint_type'],
                $stat['status'],
                $stat['count'],
                $stat['oldest'],
                $stat['max_retries']
            );
        }
    } else {
        echo '[' . date('Y-m-d H:i:s') . '] No pending requests found.' . PHP_EOL;
    }

    // Procesar peticiones pendientes
    echo '[' . date('Y-m-d H:i:s') . '] Processing pending requests...' . PHP_EOL;
    $stats = $processor->process();

    // Mostrar resultados
    echo '[' . date('Y-m-d H:i:s') . '] Processing complete!' . PHP_EOL;
    echo sprintf(
        '  - Processed: %d' . PHP_EOL .
        '  - Successful: %d' . PHP_EOL .
        '  - Failed: %d' . PHP_EOL .
        '  - Skipped: %d' . PHP_EOL,
        $stats['processed'],
        $stats['successful'],
        $stats['failed'],
        $stats['skipped']
    );

    // Mostrar errores si los hay
    if (!empty($stats['errors'])) {
        echo '[' . date('Y-m-d H:i:s') . '] Errors encountered:' . PHP_EOL;
        foreach ($stats['errors'] as $error) {
            echo sprintf(
                '  - Request ID %d: %s' . PHP_EOL,
                $error['request_id'],
                $error['error']
            );
        }
    }

    // Limpiar peticiones antiguas (opcional, se puede comentar si no se desea)
    echo '[' . date('Y-m-d H:i:s') . '] Cleaning up old requests (30+ days)...' . PHP_EOL;
    $cleanedCount = $processor->cleanupOldRequests(30);
    echo '[' . date('Y-m-d H:i:s') . '] Cleaned up ' . $cleanedCount . ' old requests.' . PHP_EOL;

} catch (Exception $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . PHP_EOL;
    echo '[' . date('Y-m-d H:i:s') . '] Stack trace:' . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

// Log fin
$executionTime = round(microtime(true) - $startTime, 2);
echo '[' . date('Y-m-d H:i:s') . '] Finished in ' . $executionTime . ' seconds.' . PHP_EOL;
echo str_repeat('-', 80) . PHP_EOL;

exit(0);
