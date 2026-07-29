<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
die();
/**
 * PRODUCTOS SIMPLES
 *
 */
$sql_productos_simples = "SELECT aci2.id_product FROM aalv_combinacionunica_import aci2
                        LEFT JOIN aalv_product ap on ap.id_product = aci2.id_product
                        WHERE aci2.etiqueta LIKE '%BF24%'
                        AND ap.reference IN ('H312362-B','H312356-FB','H312356-CB','H312355-FB','H312355-CB','H312366-B','H312365-B','H312363-T',
                            'H312363-B','H312359-FT','H312359-CT','H312358-FT','H312358-CT','H312357-FT','H312357-CT','H312370-T','H312369-T','H312361-FT',
                            'H312361-FB','H312361-CT','H312361-CB','H312360-FT','H312360-FB','H312360-CT','H312360-CB','H312368-T','H312368-B','H312367-T','H312367-B',
                            'H312372-FT','H312372-CT','H312371-FT','H312371-CT','H312373-FT','H312373-CT','H312374-FT','H312374-CT','H312378-B70','H312378-B65','H312378-B60',
                            'H312376-B70','H312376-B65','H312376-B60','H312376-B55','H312376-B50','H312384-B80','H312384-B70','H312383-T145','H312383-T160','H312383-B160','H312383-B145',
                            'H312382-T135','H312382-T130','H312382-T125','H312382-T120','H312382-T110','H312381-T140','H312381-T135','H312381-T130','H312381-T125','H312381-T120','H312381-T115',
                            'H312381-T110','H312380-T140','H312380-T135','H312380-T130','H312380-T125','H312380-T120','H312380-T115','H312380-T110','H312380-T105','H320705','H107223-2','H320257-KD',
                            'H106813-MS','H20041N-80','H320259','H102481-M','H100932','H100933','H100934','H100935','H100936','H100930','H100931','H100928','H100926','H100927','H100929','H220465',
                            'H220464','H100524N-C','H100520-N','H100523N-F','H100519R-C','H32010706','H20041N-70','H100102N-F','H300746T-125','H320258','H320257','H320256','H320255','H320254','H320253',
                            'H320252','H320251')";


$productos_simples = Db::getInstance()->ExecuteS($sql_productos_simples);
echo 'PRODUCTOS SIMPLES TOTAL  => ' . count($productos_simples) . "\n";

foreach ($productos_simples as $producto) {
    $query_update_productos_simples = "UPDATE aalv_combinacionunica_import
    SET etiqueta = TRIM(REPLACE(etiqueta, 'BF24,', ''))
    WHERE id_product = " . $producto['id_product'];

    Db::getInstance()->ExecuteS($query_update_productos_simples);
    echo 'PROCESADO SIMPLE => ' . (int)$producto['id_product'] . "\n";
    peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $producto['id_product']);
}


/**
 * PRODUCTOS COMBINACION
 *
 */
$sql_productos_combinaciones = "SELECT apa.id_product,apa.id_product_attribute FROM aalv_combinaciones_import aci
                                LEFT  JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                WHERE aci.etiqueta LIKE '%BF24%'
                                AND apa.reference IN (
                                        'H312362-B','H312356-FB','H312356-CB','H312355-FB','H312355-CB','H312366-B','H312365-B','H312363-T',
                                        'H312363-B','H312359-FT','H312359-CT','H312358-FT','H312358-CT','H312357-FT','H312357-CT','H312370-T','H312369-T','H312361-FT',
                                        'H312361-FB','H312361-CT','H312361-CB','H312360-FT','H312360-FB','H312360-CT','H312360-CB','H312368-T','H312368-B','H312367-T','H312367-B',
                                        'H312372-FT','H312372-CT','H312371-FT','H312371-CT','H312373-FT','H312373-CT','H312374-FT','H312374-CT','H312378-B70','H312378-B65','H312378-B60',
                                        'H312376-B70','H312376-B65','H312376-B60','H312376-B55','H312376-B50','H312384-B80','H312384-B70','H312383-T145','H312383-T160','H312383-B160','H312383-B145',
                                        'H312382-T135','H312382-T130','H312382-T125','H312382-T120','H312382-T110','H312381-T140','H312381-T135','H312381-T130','H312381-T125','H312381-T120','H312381-T115',
                                        'H312381-T110','H312380-T140','H312380-T135','H312380-T130','H312380-T125','H312380-T120','H312380-T115','H312380-T110','H312380-T105','H320705','H107223-2','H320257-KD',
                                        'H106813-MS','H20041N-80','H320259','H102481-M','H100932','H100933','H100934','H100935','H100936','H100930','H100931','H100928','H100926','H100927','H100929','H220465',
                                        'H220464','H100524N-C','H100520-N','H100523N-F','H100519R-C','H32010706','H20041N-70','H100102N-F','H300746T-125','H320258','H320257','H320256','H320255','H320254','H320253',
                                        'H320252','H320251')";

$productos_combinaciones = Db::getInstance()->ExecuteS($sql_productos_combinaciones);
echo 'PRODUCTOS COMBINACIONES TOTAL  => ' . count($productos_combinaciones) . "\n";
foreach ($productos_combinaciones as $producto) {
    $query_update_productos_combinacion = "UPDATE aalv_combinaciones_import
    SET etiqueta = TRIM(REPLACE(etiqueta, 'BF24,', ''))
    WHERE id_product_attribute = " . $producto['id_product_attribute'];

    Db::getInstance()->ExecuteS($query_update_productos_combinacion);
    echo 'PROCESADO COMBINACION => ' . (int)$producto['id_product'] . "\n";
    peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" .  $producto['id_product']);
}



function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}