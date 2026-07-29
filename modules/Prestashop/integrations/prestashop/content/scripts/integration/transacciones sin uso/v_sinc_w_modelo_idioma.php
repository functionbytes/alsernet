<?php

class v_sinc_w_modelo_idiomaClass
{
    public function Procesar_v_sinc_w_modelo_idioma($data, $fila, $tipo)
    {
        try {
            // Iniciar la transacción manualmente
            Db::getInstance()->execute('START TRANSACTION');
            if ($tipo <= 2) {

                if (! $data) {
                    // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                    return 1;
                }

                $idorigen = $data['id_modelo'];
                $idproductps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idorigen);
                $nombre = strip_tags($data['nombre']);

                $descripcion = base64_decode($data['descripcion']);
                $descripcion = utf8_encode($descripcion);

                $idioma = $data['idioma'];
                $codidioma = getidioma($idioma);

                if ($idproductps != '') {

                    $product = new Product((int) $idproductps);
                    $product->name[$codidioma] = $nombre;
                    if (trim($descripcion) != '') {
                        $product->description[$codidioma] = $descripcion;
                    } else {
                        $product->description[$codidioma] = $product->description[1];
                    }
                    $product->link_rewrite[$codidioma] = Tools::link_rewrite($nombre);
                    $product->update();
                }

                return 1;
            } else {

                return 1;
            }
            // Confirmar la transacción manualmente
            Db::getInstance()->execute('COMMIT');

            return 1;
        } catch (Exception $e) {
            // Revertir la transacción manualmente en caso de error
            Db::getInstance()->execute('ROLLBACK');

            echo 'Error: '.$e->getMessage();
        }
    }
}
