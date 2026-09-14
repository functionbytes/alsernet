<?php

class v_sinc_w_perfiles_navClass
{
    public function Procesar_v_sinc_w_perfiles_nav($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idmodelo = $data['id_modelo'];
            $idvalor = $data['id_valor'];
            $principal = ! empty($data['principal']) ? 1 : 0;

            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);

            if ($idprodps != '') {

                $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$data['id_valor']);

                foreach ($catimport as $catim) {
                    $idcatps = $catim['id_cat'];
                    $idnav = $catim['id_nav'];

                    $existe = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category_product WHERE id_category = '.$idcatps.' and id_product='.$idprodps);

                    if ($existe == '') {
                        Db::getInstance()->Execute('INSERT INTO aalv_category_product(id_category, id_product, position, principal) VALUES ('.$idcatps.','.$idprodps.',0,'.$principal.')');
                        $product = new Product($idprodps);
                        $product->update();
                    }

                    if ($principal) {
                        $product = new Product($idprodps);
                        $product->id_category_default = $idcatps;
                        $product->update();

                        Db::getInstance()->Execute('UPDATE aalv_category_product SET principal = 0 WHERE id_product = '.$idprodps);
                        Db::getInstance()->Execute('UPDATE aalv_category_product SET principal = 1 WHERE id_product = '.$idprodps.' and id_category = '.$idcatps);
                    }

                    Db::getInstance()->Execute('REPLACE INTO aalv_category_product_import(id_category, id_product, fila) VALUES ('.$idcatps.','.$idprodps.','.$data['id'].')');
                }

                return 1;
            } else {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] No existe producto en para agregarle la categoria '.$fila);

                return 1;
            }
        } else {

            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_category_product_import WHERE fila='.$fila);
            $idcatpsrows = Db::getInstance()->ExecuteS('SELECT id_category FROM aalv_category_product_import WHERE fila='.$fila);

            if ($idcatpsrows) {

                foreach ($idcatpsrows as $idcatpsrow) {
                    $idcatps = $idcatpsrow['id_category'];
                    Db::getInstance()->Execute('DELETE FROM aalv_category_product where id_category='.$idcatps.' and id_product='.$idprodps);
                }

                $product = new Product($idprodps);
                $product->update();
            }

            return 1;
        }

        return 1;
    }
}
