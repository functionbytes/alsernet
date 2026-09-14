<?php

class v_sinc_w_ayudas_modClass
{
    public function Procesar_v_sinc_w_ayudas_mod($data, $fila, $tipo)
    {
        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idmodelo = $data['id_modelo'];
            $idayuda = $data['id_ayuda'];

            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);
            $idayudaps = ''.Db::getInstance()->getValue('SELECT id FROM aalv_ayudas WHERE idorigen='.$idayuda);
            if (($idprodps != '') && ($idayudaps != '')) {
                Db::getInstance()->Execute('DELETE FROM aalv_ayudas_prod WHERE id_product='.$idprodps.' and id_ayuda='.$idayudaps);
                Db::getInstance()->Execute('INSERT INTO aalv_ayudas_prod(id_product, id_ayuda, orden, fila) VALUES ('.$idprodps.','.$idayudaps.',0,'.$fila.')');

                return 1;
            } else {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] No existe producto '.$fila);

                return 1;
            }
        } else {
            // borraddo
            Db::getInstance()->Execute('DELETE FROM aalv_ayudas_prod WHERE fila='.$fila);

            return 1;
        }
    }
}
