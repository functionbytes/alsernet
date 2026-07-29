<?php

class v_sinc_tarifa_cabeceraClass
{
    public function Procesar_v_sinc_tarifa_cabecera($data, $fila, $tipo)
    {
        if ($tipo <= 2) {
            if (! $data) {
                // throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Dato nulo en data para fila " . $fila);
                return 1;
            }

            $idtarifa_cabecera = $data['idtarifa_cabecera'];
            $idarticulo = $data['idarticulo'];

            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo='.$idarticulo);
            $idprodattrps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo='.$idarticulo);

            if ($idprodps == '') {
                if ($idprodattrps != '') {
                    $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idprodattrps);
                }
            } else {
                $idprodattrps = '0';
            }

            if ($data['estado'] == false) {

                $specificprices = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
                foreach ($specificprices as $idsp) {
                    Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);
                }

                $codpais = auxiliares::getPaisPrestashop($data);

                $finicio = str_replace('T', ' ', $data['finicio']);
                $ffin = str_replace('T', ' ', $data['ffin']);

                if (''.$finicio == '') {
                    $finicio = '0000-00-00 00:00:00';
                }

                if (''.$ffin == '') {
                    $ffin = '0000-00-00 00:00:00';
                }

                $sql = 'DELETE FROM aalv_specific_price where id_country='.$codpais." and `from`='".$finicio."' and `to`='".$ffin."' and id_product=".$idprodps.' and id_product_attribute='.$idprodattrps;

                Db::getInstance()->Execute($sql);

                if ($idprodps != '') {
                    auxiliares::procesarcombinaciones($idprodps);
                }
                Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                return 1;
            }

            $piva = (int) $data['porc_iva'];

            $porciva = auxiliares::getIdIva();

            if ($piva == '10.000') {
                $porciva = auxiliares::getIdIva(10);
            }
            if ($piva == '4.000') {
                $porciva = auxiliares::getIdIva(4);
            }
            if ($piva == 0 && $data['idregpais'] != 1) {
                $porciva = 0;
            }

            if ($data['idregpais'] == 1 && $data['idttarifa'] == 1 && ! $data['codigo_iso_pais']) { // Cuidado que con las tarifas de otros países también se envía idregpais=1

                if ($idprodps != '') {
                    $id_tax_rules_group = Db::getInstance()->getValue('select id_tax_rules_group from aalv_product_shop where id_product='.$idprodps);
                    if ($id_tax_rules_group != $porciva) {
                        $product = new Product((int) $idprodps);
                        $product->id_tax_rules_group = $porciva;
                        $product->update();
                        // Db::getInstance()->getValue("update aalv_product_shop set id_tax_rules_group = " . $porciva . " where id_product=" . $idprodps);
                        // Db::getInstance()->getValue("update aalv_product set id_tax_rules_group = " . $porciva . " where id_product=" . $idprodps);
                    }
                }
            }

            // ver si existe ya esa cabecera
            $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
            if ($specificprice != '') {
                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                if ($specificprice != '') {
                    $tipo = 2;
                }
            } else {
                $tipo = 1;
            }

            if ($tipo == 2) { // update

                if ($idprodps != '') {
                    // ver si existe precio específico para la tarifa cabecera
                    $specificprices = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                    foreach ($specificprices as $idsp) {

                        $existesp = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);

                        $codpais = auxiliares::getPaisPrestashop($data);

                        $finicio = str_replace('T', ' ', $data['finicio']);
                        $ffin = str_replace('T', ' ', $data['ffin']);

                        if (''.$finicio == '') {
                            $finicio = '0000-00-00 00:00:00';
                        }

                        if (''.$ffin == '') {
                            $ffin = '0000-00-00 00:00:00';
                        }

                        if ($existesp != '') {
                            $sql = 'UPDATE aalv_specific_price SET id_country='.$codpais.",`from`='".$finicio."',`to`='".$ffin."' WHERE id_specific_price=".$idsp['id_specific_price'];
                            Db::getInstance()->Execute($sql);
                            $existe_tarifa_cabecera = ''.Db::getInstance()->getValue('select id_tarifa_cabecera from aalv_tarifa_cabecera_import atci where id_tarifa_cabecera ='.$idtarifa_cabecera);
                            if ($existe_tarifa_cabecera == '') {
                                Db::getInstance()->Execute('INSERT INTO aalv_tarifa_cabecera_import (id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                                VALUES ('.$idtarifa_cabecera.','.$codpais.",'".$finicio."','".$ffin."',".$idprodps.','.$idprodattrps.')');
                                // $sql = "REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                                // VALUES (" . $idtarifa_cabecera . "," . $codpais . ",'" . $finicio . "','" . $ffin . "'," . $idprodps . "," . $idprodattrps . ")";
                                // Db::getInstance()->Execute($sql);
                            } else {
                                Db::getInstance()->Execute('UPDATE aalv_tarifa_cabecera_import SET
                                                            id_country = '.$codpais.",
                                                            finicio='".$finicio."',
                                                            ffin= '".$ffin."',
                                                            id_product=".$idprodps.',
                                                            id_attribute='.$idprodattrps.' WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
                            }
                        } else {
                            $buscar_dato = Db::getInstance()->executeS('select * from aalv_specific_price where id_specific_price = '.$idsp['id_specific_price']);
                            if (count($buscar_dato) == 0) {
                                Db::getInstance()->Execute('delete from aalv_specific_price_import where id_specific_price ='.$idsp['id_specific_price']);
                                //     throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Comprobar este caso nuevo idtarifa_cabecera => " . $idtarifa_cabecera . " del articulo => " . $idarticulo);
                                // return 1;
                            }

                            // $sql = "REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                            //     VALUES (" . $idtarifa_cabecera . "," . $codpais . ",'" . $finicio . "','" . $ffin . "'," . $idprodps . "," . $idprodattrps . ")";
                            // Db::getInstance()->Execute($sql);
                        }
                    }
                } else {
                    // correo de creacion de tarifa sin producto
                    // throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Viene tarifa cabecera " . $idtarifa_cabecera . " antes de la creacion del articulo de id " . $idarticulo);
                    return 1;
                }
            } else { // insert ¿que hacemos? no tenemos el precio, que va en las lineas ¿tabla auxiliar?

                if ($idprodps != '') {
                    $pais = auxiliares::getPaisPrestashop($data);

                    $finicio = str_replace('T', ' ', $data['finicio']);
                    $ffin = str_replace('T', ' ', $data['ffin']);

                    if (''.$finicio == '') {
                        $finicio = '0000-00-00 00:00:00';
                    }

                    if (''.$ffin == '') {
                        $ffin = '0000-00-00 00:00:00';
                    }

                    $specificprice_verificar = Db::getInstance()->ExecuteS('select * from aalv_tarifa_cabecera_import atci where id_tarifa_cabecera = '.$idtarifa_cabecera);
                    if (count($specificprice_verificar) == 0) {
                        Db::getInstance()->Execute('INSERT INTO aalv_tarifa_cabecera_import (id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                        VALUES ('.$idtarifa_cabecera.','.$pais.",'".$finicio."','".$ffin."',".$idprodps.','.$idprodattrps.')');
                    } else {
                        Db::getInstance()->Execute('UPDATE aalv_tarifa_cabecera_import SET
                                                            id_country = '.$pais.",
                                                            finicio='".$finicio."',
                                                            ffin= '".$ffin."',
                                                            id_product=".$idprodps.',
                                                            id_attribute='.$idprodattrps.' WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
                    }

                    // $sql = "REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                    //    VALUES (" . $idtarifa_cabecera . "," . $pais . ",'" . $finicio . "','" . $ffin . "'," . $idprodps . "," . $idprodattrps . ")";
                    // $res = Db::getInstance()->Execute($sql);
                } else {
                    // correo de creacion de tarifa sin producto
                    // throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Viene tarifa cabecera " . $idtarifa_cabecera . " antes de la creacion del articulo de id " . $idarticulo);
                    return 1;
                }
            }

            if ($idprodps != '') {
                auxiliares::procesarcombinaciones($idprodps);
            }

            return 1;
        } else {
            // borrado
            $specificprices = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$fila);
            foreach ($specificprices as $idsp) {
                $idp = Db::getInstance()->getValue('select id_product from  aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);
                Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);
            }

            if (isset($idp)) {
                auxiliares::procesarcombinaciones($idp);
            }
            Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$fila);
            Db::getInstance()->Execute('DELETE FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$fila);

            return 1;
        }

        return 1;
    }
}
