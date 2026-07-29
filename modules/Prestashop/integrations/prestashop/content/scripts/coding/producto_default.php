<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../../config/config.inc.php';

function setDefaultCombinationWithLowestPriceInStock($id_product)
{
    // Obtener el objeto del producto
    $product = new Product($id_product);

    // Obtener la combinación actual Por defecto
    $currentDefaultCombination = $product->getDefaultAttribute($id_product);

    // Obtener las combinaciones del producto
    $combinations = $product->getAttributeCombinations();

    $lowestCombination = null;
    $lowestPrice = PHP_INT_MAX;

    foreach ($combinations as $combination) {
        // Verificar si la combinación tiene stock
        if (StockAvailable::getQuantityAvailableByProduct($id_product, $combination['id_product_attribute']) > 0) {
            $combinationPrice = $product->getPrice(true, $combination['id_product_attribute']);

            // Comparar el precio de la combinación actual con el menor encontrado
            if ($combinationPrice < $lowestPrice) {
                $lowestPrice = $combinationPrice;
                $lowestCombination = $combination;
            }
        }
    }

    if ($lowestCombination) {
        $combination = new Combination((int) $lowestCombination['id_product_attribute']);
        // Validar si la combinación más barata ya es la predeterminada
        if ($product->reference != '') {
            if ($currentDefaultCombination == $lowestCombination['id_product_attribute']) {
                return null;
            }
        }
        $product->deleteDefaultAttributes();
        // Establecer la combinación con el precio más bajo y con stock como predeterminada
        $product->setDefaultAttribute($lowestCombination['id_product_attribute']);
        $product->reference = $combination->reference;
        $product->update();
        Product::updateDefaultAttribute($id_product);
        Db::getInstance()->Execute("INSERT INTO aalv_alsernet_cache_producto values (NULL, $id_product)");

        // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $id_product);
        return 'Combinación Por defecto actualizada ID: '.$lowestCombination['id_product_attribute'].' =>PRODUCT '.$id_product;
    } else {
        return null; // No se encontró ninguna combinación con stock
    }
}

$sql = Db::getInstance()->ExecuteS('SELECT
                                        apa.id_product
                                    FROM
                                        aalv_product ap
                                        LEFT JOIN aalv_product_attribute apa ON apa.id_product = ap.id_product
                                    WHERE
                                        ap.active = true
                                        AND apa.id_product IS NOT NULL
                                        and apa.id_product != 0
                                    GROUP BY apa.id_product
                                    ORDER BY apa.id_product DESC');

$datos = '';
foreach ($sql as $value) {

    $lote = Db::getInstance()->getValue('SELECT id_ps_product FROM aalv_alsernet_lotes_copia awbp WHERE active = 0 AND id_ps_product = '.$value['id_product']);

    if ($lote) {
        // echo "ES LOTE\n";
        continue;
    }

    if ($value['id_product'] == 56764) {
        // ES Fitting
        continue;
    }

    // Ejemplo de uso
    $defaultCombination = setDefaultCombinationWithLowestPriceInStock($value['id_product']);

    if ($defaultCombination) {
        echo $defaultCombination."\n";
        $datos .= $defaultCombination.'<br>';
    } else {
        Product::updateDefaultAttribute($value['id_product']);
        echo "No se encontró ninguna combinación con stock.\n";
    }

}

if ($datos != '') {
    sendmailPruebas($datos);
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

function sendmailPruebas($mensaje)
{

    $dest = [];
    $dest[] = 'alvarez@alsernet.es';

    $data = ['{message}' => $mensaje];

    Mail::Send(
        1,
        'integracion',
        'Productos por Default',
        $data,
        $dest,
        Configuration::get('PS_SHOP_NAME'),
        'desarrollotest@a-alvarez.net',
        'desarrollotest',
        [],
        null,
        _PS_MAIL_DIR_,
        false,
        1
    );
}
