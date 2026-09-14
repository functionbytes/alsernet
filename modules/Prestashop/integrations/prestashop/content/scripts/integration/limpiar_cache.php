<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

require_once dirname(__DIR__).'/integration/auxiliares.php';
$auxiliares = new auxiliares;

// Detiene la ejecución durante 2 segundos
echo "Inicio => Se espera 2 segundos\n";
sleep(2);

$procesando = Db::getInstance()->getRow('SELECT * FROM aalv_bandera_integracion WHERE id = 2');
$nn = 0;
if ($procesando['activo'] == 0) {
    Db::getInstance()->Execute('UPDATE aalv_bandera_integracion set activo=1, fecha = NOW() WHERE id = 2');

    $sql = Db::getInstance()->executeS('SELECT id_product FROM aalv_alsernet_cache_producto group by id_product ');

    foreach ($sql as $value) {

        // Usamos cURL para hacer la solicitud HTTP
        $ch = curl_init('https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$value['id_product']);

        // Configuraciones de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Para devolver la respuesta
        curl_setopt($ch, CURLOPT_HEADER, true); // Necesitamos los encabezados para obtener el código de estado
        curl_setopt($ch, CURLOPT_NOBODY, false); // Para que no se ignore el cuerpo de la respuesta

        // Ejecutar la solicitud
        $response = curl_exec($ch);

        // Obtener el código de estado HTTP
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Verificamos si el código de estado es 200 (OK)
        if ($http_code == 200) {
            echo '('.$nn.") La solicitud fue exitosa. Código de estado: 200\n";
            Db::getInstance()->Execute('DELETE FROM aalv_alsernet_cache_producto WHERE id_product ='.$value['id_product']);
            // echo "DELETE FROM aalv_alsernet_cache_producto WHERE id_product =".$value['id_product']."\n";
        } else {
            echo "La solicitud a falló. Código de estado: $http_code\n";
            echo 'id producto => '.$value['id_product']."\n";
        }

        // Cerrar la conexión cURL
        curl_close($ch);

        // Espera de 5 segundos después de cada solicitud
        echo "Se espera 3 segundos\n";

        sleep(3);
        $nn++;
        if ($nn == 150) {
            break;
        }
    }

    Db::getInstance()->Execute('UPDATE aalv_bandera_integracion set activo=0, fecha=NOW() WHERE id=2');
} else {
    echo "\nSe esta procesando.\n";
}
echo "\n\n\n";
