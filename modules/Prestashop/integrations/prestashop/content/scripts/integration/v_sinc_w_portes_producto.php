<?php

class v_sinc_w_portes_productoClass
{
    public function Procesar_v_sinc_w_portes_producto($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            if (! auxiliares::esportestandard($data['codigo'])) {

                $referencia = $data['referencia'];

                $idproductattribute = ''.Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_product_attribute WHERE reference='".$referencia."'");
                if ($idproductattribute == '') {
                    $idproduct = ''.Db::getInstance()->getValue("SELECT id_product FROM aalv_product WHERE reference='".$referencia."'");
                    $idproductattribute = '0';
                } else {
                    $idproduct = ''.Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE reference='".$referencia."'");
                }

                if ($idproduct != '') {

                    $idpaisps = auxiliares::getpaisps($data['id_pais']);

                    $existe = ''.Db::getInstance()->getValue('select id from aalv_portes_producto where id_origen='.$data['id']);

                    if ($existe != '') {

                        Db::getInstance()->Execute("UPDATE aalv_portes_producto set referencia='".$referencia."', codigo='".$data['codigo']."' ,id_pais_origen=".$data['id_pais'].' ,id_pais='.$idpaisps.' ,id_product='.$idproduct.' ,id_product_attribute='.$idproductattribute.' where id='.$existe);
                    } else {
                        Db::getInstance()->Execute('INSERT INTO aalv_portes_producto(id_origen,referencia,codigo,id_pais_origen,id_pais,id_product,id_product_attribute) VALUES ('.$data['id'].",'".$referencia."','".$data['codigo']."',".$data['id_pais'].','.$idpaisps.','.$idproduct.','.$idproductattribute.')');
                    }

                    // Si es PT, se le agrega a AU, FR, DE y IT los mismos portes
                    if ($data['id_pais'] == 11) {
                        $datos_pais = [
                            1, // Alemania
                            2, // Austria
                            8, // Francia
                            10, // Italia
                        ];
                        foreach ($datos_pais as $value) {
                            $existe_pais = Db::getInstance()->getValue("SELECT id FROM aalv_portes_producto WHERE
                                                                        referencia='".$referencia."'
                                                                        AND codigo='".$data['codigo']."'
                                                                        AND id_pais_origen=".$data['id_pais'].'
                                                                        AND id_pais='.$value.'
                                                                        AND id_product='.$idproduct.'
                                                                        AND id_product_attribute='.$idproductattribute);

                            if ($existe_pais == false) {
                                // INSERTAR
                                Db::getInstance()->Execute('INSERT INTO aalv_portes_producto(id_origen,referencia,codigo,id_pais_origen,id_pais,id_product,id_product_attribute) VALUES ('.$data['id'].",'".$referencia."','".$data['codigo']."',".$data['id_pais'].','.$value.','.$idproduct.','.$idproductattribute.')');
                            } else {
                                // UPDATE
                                Db::getInstance()->Execute("UPDATE aalv_portes_producto set referencia='".$referencia."', codigo='".$data['codigo']."' ,id_pais_origen=".$data['id_pais'].' ,id_pais='.$value.' ,id_product='.$idproduct.' ,id_product_attribute='.$idproductattribute.' where id='.$existe_pais);
                            }
                        }
                    }
                } else {
                    throw new Exception('llega referencia '.$referencia.' y no existe producto');
                }
            } else {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] var esportestandard del codigo => '.$data['codigo']);
            }
        } else {
            Db::getInstance()->Execute('DELETE FROM aalv_portes_producto WHERE id_origen = '.$fila);
        }

        return 1;
    }
}
