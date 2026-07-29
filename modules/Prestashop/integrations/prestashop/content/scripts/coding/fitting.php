<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include(dirname(__FILE__) . '/../../config/config.inc.php');

// ID del producto
$id_product = 56764;

$product = new Product($id_product, true, Context::getContext()->language->id);
if (!Validate::isLoadedObject($product)) {
    die('Producto no encontrado.');
}

echo "<h2>Eliminando combinaciones con fecha pasada del producto: {$product->name}</h2>\n";

$combinations = $product->getAttributeCombinations(Context::getContext()->language->id);

$combinationData = [];

foreach ($combinations as $comb) {
    $id_product_attribute = $comb['id_product_attribute'];

    if (!isset($combinationData[$id_product_attribute])) {
        $combinationData[$id_product_attribute] = [
            'id' => $id_product_attribute,
            'attributes' => [],
            'reference' => $comb['reference'],
            'price' => $comb['price'],
            'quantity' => $comb['quantity'],
        ];
    }

    $combinationData[$id_product_attribute]['attributes'][] = $comb['group_name'] . ': ' . $comb['attribute_name'];
}

$hoy = new DateTime();
$hoy->setTime(0, 0, 0);
$totalEliminadas = 0;
$mensaje = '';

foreach ($combinationData as $comb) {
    $fecha_detectada = null;

    foreach ($comb['attributes'] as $attr) {
        if (strpos($attr, 'Fecha:') === 0) {
            if (preg_match('/Fecha:\s+(?:\w+\s+)?(\d{2}\/\d{2}\/\d{4})/u', $attr, $matches)) {
                $fecha_detectada = DateTime::createFromFormat('d/m/Y', $matches[1]);
            }
        }
    }

    if ($fecha_detectada) {
        $fecha_detectada->setTime(0, 0, 0);

        if ($fecha_detectada < $hoy) {
            // Eliminar usando la clase Combination
            $combination = new Combination((int)$comb['id']);
            if (Validate::isLoadedObject($combination) && $combination->delete()) {
                $mensaje .= "✅ Combinación ID {$comb['id']} eliminada (fecha: {$fecha_detectada->format('d/m/Y')})<br>";
                $totalEliminadas++;
            } else {
                $mensaje .= "❌ Error al eliminar combinación ID {$comb['id']}<br>";
            }
        }
    }
}

if($mensaje != ''){
    $dest = [];
    $dest[] = "alvarez@alsernet.es";
    $dest[] = "anacup@a-alvarez.com";

    $data=['{message}'=>$mensaje];
    Mail::Send(    1,
                'integracion',
                "Fitting - Eliminacion de días",
                $data,
                $dest,
                Configuration::get('PS_SHOP_NAME'),
                'desarrollotest@a-alvarez.com',
                'desarrollotest',
                [],
                null,
                _PS_MAIL_DIR_,
                false,
                1
            );
}

