<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

globalProduct();

globalTypeProduct($_POST['data']);

globalProduct(false);


/**
 * globalTypeProduct - Función para actualizar la tabla de productos globales según los datos proporcionados.
 *
 * @param array  $data   Arreglo de valores de características de productos para ser procesados.
 * @param string $FROM   Nombre de la tabla de base de datos de la que se eliminarán los registros existentes.
 * @param string $WHERE  Nombre del campo que se utilizará en la cláusula WHERE para buscar valores existentes.
 * @return void
 */
function globalTypeProduct($data, $FROM = 'alsernet_exclude_product_inpost', $WHERE = 'id_feature_value')
{
    $datos = array();

    // Elimina los registros existentes de la tabla
    Db::getInstance()->executeS("DELETE from " . _DB_PREFIX_ . $FROM . "");

    // Itera a través de los datos proporcionados
    for ($i = 0; $i < count($data); $i++) {
        // Consulta si ya existe una fila con el valor proporcionado en la tabla
        $existingRow = Db::getInstance()->getRow("  SELECT
                                                        *
                                                    FROM
                                                        `" . _DB_PREFIX_ . $FROM . "`
                                                    WHERE
                                                        " . $WHERE . " = '" . pSQL($data[$i]) . "'");

        // Si no existe una fila con el valor proporcionado, agrégalo a los datos a insertar
        if (!$existingRow) {
            $datos[] = [$WHERE => $data[$i]];
        }
    }

    // Si hay nuevos datos para insertar, realiza la inserción
    if (count($datos) != 0) {
        $result = Db::getInstance()->insert($FROM, $datos);
    }
}


/**
 * globalProduct - Función para actualizar el peso de productos globales según los parámetros proporcionados.
 *
 * @param bool $reset Si se establece en true, los productos tendrán un peso de 0; de lo contrario, tendrán un peso de 30.
 * @return void
 */
function globalProduct($reset = true)
{
    // Consulta la base de datos para obtener los IDs de productos asociados a características excluidas
    $db = Db::getInstance()->executeS("SELECT
                                            prod.id_product
                                        FROM
                                            "._DB_PREFIX_."alsernet_exclude_product_inpost type
                                            LEFT JOIN "._DB_PREFIX_."feature_product prod ON prod.id_feature_value = type.id_feature_value");

    $datos = '';

    // Itera a través de los resultados de la consulta y construye una cadena de IDs de producto
    for ($i = 0; $i < count($db); $i++) {
        $datos .= $db[$i]['id_product'] . ',';
    }

    $longitud = strlen($datos);

    // Elimina la coma final de la cadena de datos si está presente
    $ultimoParametro = substr($datos, $longitud - 1);

    if ($ultimoParametro == ',') {
        $datos = substr($datos, 0, -1);
    }

    // Actualiza el peso de los productos según el valor de reset proporcionado
    if ($reset) {
        Db::getInstance()->update('product', ['weight' => 0], 'id_product IN (' . $datos . ')');
    } else {
        Db::getInstance()->update('product', ['weight' => 30], 'id_product IN (' . $datos . ')');
    }
}


?>