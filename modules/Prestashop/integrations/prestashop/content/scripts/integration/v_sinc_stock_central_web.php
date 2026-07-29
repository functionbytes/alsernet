<?php

class v_sinc_stock_central_webClass
{
    public function Procesar_v_sinc_stock_central_web($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idarticulo = $data['idarticulo'];
            $unidades = (int) $data['unidades'];
            $unidades_pocomaco = (int) $data['unidades_almacen_principal'];
            $idproductattribute = '0';

            // buscar
            $idproductattribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo='.$idarticulo);

            if ($idproductattribute != '') {
                $result = Db::getInstance()->ExecuteS('SELECT (SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattribute.') id_product,
                                                                id_product_attribute, externo_disponibilidad
                                                        FROM aalv_combinaciones_import
                                                        WHERE id_product_attribute='.$idproductattribute);
                // $idproduct = $result[0]['id_product'];
            } else {
                $result = Db::getInstance()->ExecuteS('SELECT id_product, externo_disponibilidad
                                                        FROM aalv_combinacionunica_import
                                                        WHERE id_articulo = '.$idarticulo);
                // $idproduct = $result[0]['id_product'];
                $idproductattribute = '0';
            }

            if (count($result) == 0) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] llega stock de articulo '.$idarticulo.' que no está creado. NUEVO IF');

                return 1;
            }

            $idproduct = $result[0]['id_product'];

            if ($idproduct != '') {

                $existe = ''.Db::getInstance()->getValue('select id from aalv_repositorio_stock where id_product='.$idproduct.' and id_product_attribute='.$idproductattribute);

                if ($existe == '') {
                    Db::getInstance()->Execute('INSERT INTO aalv_repositorio_stock
                        (id_product, id_product_attribute, quantity, quantity_pocomaco)
                        VALUES
                        ('.$idproduct.','.$idproductattribute.','.$unidades.', '.$unidades_pocomaco.')');
                } else {
                    Db::getInstance()->Execute('UPDATE aalv_repositorio_stock SET
                                                                                        quantity='.$unidades.',
                                                                                        quantity_pocomaco = '.$unidades_pocomaco.'
                                                                                    WHERE id='.$existe);
                }

                auxiliares::procesarcombinaciones($idproduct);

                Product::alsernetNewVisibilidad($data['idarticulo']);
            } else {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] llega stock de articulo '.$idarticulo.' que no está creado.');

                return 1;
            }
        } else {
            throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] llega stock de articulo '.$idarticulo.' que no está creado.');

            return 1;
        }

        return 1;
    }
}
