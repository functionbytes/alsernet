<?php

class v_sinc_tarifa_lineaClass
{
    public function Procesar_v_sinc_tarifa_linea($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                // throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Dato nulo en data para fila " . $fila);
                return 1;
            }

            if ($data['estado'] == false) {

                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);

                if ($specificprice != '') {
                    $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                    if ($specificprice != '') {
                        $idp = Db::getInstance()->getValue('select id_product from  aalv_specific_price WHERE id_specific_price='.$specificprice);
                        Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                        auxiliares::procesarcombinaciones($idp);
                    }
                }

                Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);

                return 1;
            }

            // ver si existe ya esa linea
            $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);
            if ($specificprice != '') {
                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                if ($specificprice != '') {
                    $tipo = 2;
                } else {
                    $tipo = 1;
                }
            } else {
                $tipo = 1;
            }

            if ($tipo == 2) { // update

                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);
                if ($specificprice != '') {

                    $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);

                    if ($specificprice != '') {

                        if ($data['baseimp_anterior']) {

                            if ($data['baseimp_anterior'] > $data['baseimp']) {

                                // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                    $miprice = round($data['baseimp_anterior'], 6);
                                    $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }
                        } else {
                            $miprice = round($data['baseimp'], 6);
                            $mireduction = 0;
                        }

                        $sql = 'UPDATE aalv_specific_price SET price='.$miprice.',from_quantity='.$data['udesde'].',reduction='.$mireduction.",reduction_tax=0,reduction_type='amount' WHERE id_specific_price=".$specificprice;
                        Db::getInstance()->Execute($sql);

                        $midproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                        if ($midproduct != '') {
                            auxiliares::procesarcombinaciones($midproduct);
                        }
                    } else {
                        // como si fuera tipo 1
                        // insert, esperemos que hayan enviado antes la cabecera
                        // ver si existe cabecera
                        $idtarifa_cabecera = $data['idtarifa_cabecera'];

                        $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                        if ($existecabecera != '') {
                            $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                        }

                        if ($existecabecera != '') {

                            $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $idprodattr = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $finicio = ''.Db::getInstance()->getValue('SELECT `from` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $ffin = ''.Db::getInstance()->getValue('SELECT `to` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $country = ''.Db::getInstance()->getValue('SELECT id_country FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);

                            if ($data['baseimp_anterior']) {

                                if ($data['baseimp_anterior'] > $data['baseimp']) {

                                    // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                        $miprice = round($data['baseimp_anterior'], 6);
                                        $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                    } else {
                                        $miprice = round($data['baseimp'], 6);
                                        $mireduction = 0;
                                    }
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }

                            $specific_price = new SpecificPrice;
                            $specific_price->id_product = $idproduct;
                            $specific_price->id_country = $country;
                            $specific_price->id_product_attribute = $idprodattr;
                            $specific_price->price = $miprice; // Establece el nuevo precio específico
                            $specific_price->from_quantity = round($data['udesde'], 2); // Cantidad mínima para aplicar el precio
                            $specific_price->reduction = $mireduction; // Valor de la reducción
                            $specific_price->from = $finicio; // Fecha de inicio de la validez
                            $specific_price->to = $ffin; // Fecha de fin de la validez

                            $specific_price->id_shop = 0;
                            $specific_price->id_currency = 0;
                            $specific_price->id_group = 0;
                            $specific_price->id_customer = 0;
                            $specific_price->reduction_tax = 0; // Definir si la reducción aplica impuestos (1 = sí)
                            $specific_price->reduction_type = 'amount'; // Tipo de reducción ('amount' o 'percentage')

                            // Guardar el nuevo precio específico y la reducción
                            if ($specific_price->add()) {
                                $idnewsp = $specific_price->id;

                                if (! Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')')) {
                                    dump('REVISAR ['.__LINE__.']');
                                    exit();
                                }

                                auxiliares::procesarcombinaciones($idproduct);

                                $productact = new Product((int) $idproduct);
                                $productact->update();
                            } else {
                                dump('['.__LINE__.'] ACA');
                                exit();
                                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Error al insertar el precio, revisar si ya esta agregado id_tarifa_linea => '.$fila.' -- '.Db::getInstance()->getMsgError());

                                return 1;
                            }
                        } else {
                            // coger de nueva tabla auxiliar

                            $existeauxiliar = ''.Db::getInstance()->getValue('SELECT id_tarifa_cabecera FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                            if ($existeauxiliar != '') {

                                $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $idprodattr = Db::getInstance()->getValue('SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $finicio = Db::getInstance()->getValue('SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $ffin = Db::getInstance()->getValue('SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $country = Db::getInstance()->getValue('SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                                if ($data['baseimp_anterior']) {

                                    if ($data['baseimp_anterior'] > $data['baseimp']) {

                                        // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                        if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                            $miprice = round($data['baseimp_anterior'], 6);
                                            $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                        } else {
                                            $miprice = round($data['baseimp'], 6);
                                            $mireduction = 0;
                                        }
                                    } else {
                                        $miprice = round($data['baseimp'], 6);
                                        $mireduction = 0;
                                    }
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }

                                // Comprobar existencia
                                $exists = SpecificPrice::exists(
                                    $idproduct,
                                    $idprodattr,
                                    0,
                                    0,
                                    $country,
                                    0,
                                    0,
                                    round($data['udesde'], 2),
                                    $finicio,
                                    $ffin
                                );

                                if ($exists) {
                                    $specific_price = new SpecificPrice($exists);
                                } else {
                                    $specific_price = new SpecificPrice;
                                }

                                // $specific_price = new SpecificPrice();
                                $specific_price->id_product = $idproduct;
                                $specific_price->id_country = $country;
                                $specific_price->id_product_attribute = $idprodattr;
                                $specific_price->price = $miprice; // Establece el nuevo precio específico
                                $specific_price->from_quantity = round($data['udesde'], 2); // Cantidad mínima para aplicar el precio
                                $specific_price->reduction = $mireduction; // Valor de la reducción
                                $specific_price->from = $finicio; // Fecha de inicio de la validez
                                $specific_price->to = $ffin; // Fecha de fin de la validez

                                $specific_price->id_shop = 0;
                                $specific_price->id_currency = 0;
                                $specific_price->id_group = 0;
                                $specific_price->id_customer = 0;
                                $specific_price->reduction_tax = 0; // Definir si la reducción aplica impuestos (1 = sí)
                                $specific_price->reduction_type = 'amount'; // Tipo de reducción ('amount' o 'percentage')

                                if ($exists) {
                                    if ($specific_price->save()) {
                                        // Revisamos si existe el la tarifa cabecera y datos en aalv_specific_price_import
                                        $buscar = Db::getInstance()->getValue('select * from aalv_specific_price_import where id_specific_price = '.$exists.' and id_tarifa_cabecera = '.$data['idtarifa_cabecera'].' and id_tarifa_linea = '.$data['idtarifa_linea']);
                                        if (! $buscar) {

                                            if (! Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$exists.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')')) {
                                                Db::getInstance()->Execute('UPDATE aalv_specific_price_import set id_tarifa_cabecera = '.$data['idtarifa_cabecera'].', id_tarifa_linea='.$data['idtarifa_linea'].' where id_specific_price = '.$exists);
                                            }

                                            auxiliares::procesarcombinaciones($idproduct);

                                            $productact = new Product((int) $idproduct);
                                            $productact->update();
                                        }
                                    } else {
                                        dump('Error al actualizar el SpecificPrice.');
                                        exit();
                                    }
                                } else {
                                    if ($specific_price->add()) {
                                        $idnewsp = $specific_price->id;
                                        if (! Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')')) {
                                            dump('REVISAR ['.__LINE__.']');
                                            exit();
                                        }

                                        auxiliares::procesarcombinaciones($idproduct);
                                        $productact = new Product((int) $idproduct);
                                        $productact->update();
                                    } else {
                                        dump('['.__LINE__.'] ACA');
                                        exit();
                                        throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Error al insertar el precio, revisar si ya esta agregado id_tarifa_linea => '.$fila);

                                        return 1;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // insert, esperemos que hayan enviado antes la cabecera
                // ver si existe cabecera
                $idtarifa_cabecera = $data['idtarifa_cabecera'];

                $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                if ($existecabecera != '') {
                    $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                }

                if ($existecabecera != '') {

                    $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $idprodattr = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $finicio = ''.Db::getInstance()->getValue('SELECT `from` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $ffin = ''.Db::getInstance()->getValue('SELECT `to` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $country = ''.Db::getInstance()->getValue('SELECT id_country FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);

                    if ($data['baseimp_anterior']) {

                        if ($data['baseimp_anterior'] > $data['baseimp']) {

                            // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                            if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                $miprice = round($data['baseimp_anterior'], 6);
                                $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }
                        } else {
                            $miprice = round($data['baseimp'], 6);
                            $mireduction = 0;
                        }
                    } else {
                        $miprice = round($data['baseimp'], 6);
                        $mireduction = 0;
                    }

                    $specific_price = new SpecificPrice;
                    $specific_price->id_product = $idproduct;
                    $specific_price->id_country = $country;
                    $specific_price->id_product_attribute = $idprodattr;
                    $specific_price->price = $miprice; // Establece el nuevo precio específico
                    $specific_price->from_quantity = round($data['udesde'], 2); // Cantidad mínima para aplicar el precio
                    $specific_price->reduction = $mireduction; // Valor de la reducción
                    $specific_price->from = $finicio; // Fecha de inicio de la validez
                    $specific_price->to = $ffin; // Fecha de fin de la validez

                    $specific_price->id_shop = 0;
                    $specific_price->id_currency = 0;
                    $specific_price->id_group = 0;
                    $specific_price->id_customer = 0;
                    $specific_price->reduction_tax = 0; // Definir si la reducción aplica impuestos (1 = sí)
                    $specific_price->reduction_type = 'amount'; // Tipo de reducción ('amount' o 'percentage')
                    // dump($specifkic_price);die();
                    // Guardar el nuevo precio específico y la reducción
                    if ($specific_price->add()) {

                        $idnewsp = $specific_price->id;
                        if (! Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')')) {
                            dump('REVISAR ['.__LINE__.']');
                            exit();
                        }

                        auxiliares::procesarcombinaciones($idproduct);

                        $productact = new Product((int) $idproduct);
                        $productact->update();
                    } else {
                        dump(Db::getInstance()->getMsgError());
                        dump($idproduct);
                        dump($idprodattr);
                        dump('['.__LINE__.'] ACA');
                        exit();
                        throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Error al insertar el precio, revisar si ya esta agregado id_tarifa_linea => '.$fila);

                        return 1;
                    }
                } else {
                    // coger de nueva tabla auxiliar

                    $existeauxiliar = ''.Db::getInstance()->getValue('SELECT id_tarifa_cabecera FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                    if ($existeauxiliar != '') {

                        $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $idprodattr = Db::getInstance()->getValue('SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $finicio = Db::getInstance()->getValue('SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $ffin = Db::getInstance()->getValue('SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $country = Db::getInstance()->getValue('SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                        if ($data['baseimp_anterior']) {

                            if ($data['baseimp_anterior'] > $data['baseimp']) {

                                // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                    $miprice = round($data['baseimp_anterior'], 6);
                                    $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }
                        } else {
                            $miprice = round($data['baseimp'], 6);
                            $mireduction = 0;
                        }

                        Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                        $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`,
                        `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`,
                        `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES
                        (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                        if (! Db::getInstance()->Execute($sql)) {
                            $repetido = Db::getInstance()->ExecuteS('select id_specific_price from aalv_specific_price where id_product = '.$idproduct.' and id_product_attribute = '.$idprodattr.' and id_country = '.$country." and `from` = '".$finicio."' and `to` = '".$ffin."'");
                            if (count($repetido) > 1) {
                                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] REVISAR EL INSERT '.$sql);

                                return 1;
                            }
                            Db::getInstance()->Execute('UPDATE aalv_specific_price SET `price` = '.$miprice.', `from_quantity` = '.$data['udesde'].', `reduction` = '.$mireduction.' WHERE id_specific_price = '.$repetido[0]['id_specific_price']);
                            $idnewsp = $repetido[0]['id_specific_price'];
                        } else {
                            $idnewsp = Db::getInstance()->Insert_ID();
                        }

                        Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                        auxiliares::procesarcombinaciones($idproduct);

                        $productact = new Product((int) $idproduct);
                        $productact->update();

                        // // Comprobar existencia
                        // $exists = SpecificPrice::exists(
                        //     $idproduct,
                        //     $idprodattr,
                        //     0,
                        //     0,
                        //     $country,
                        //     0,
                        //     0,
                        //     round($data["udesde"], 2),
                        //     $finicio,
                        //     $ffin
                        // );

                        // if ($exists) {
                        //     $specific_price = new SpecificPrice($exists);
                        // } else {
                        //     $specific_price = new SpecificPrice();
                        // }

                        // // $specific_price = new SpecificPrice();
                        // $specific_price->id_product = $idproduct;
                        // $specific_price->id_country = $country;
                        // $specific_price->id_product_attribute = $idprodattr;
                        // $specific_price->price = $miprice; // Establece el nuevo precio específico
                        // $specific_price->from_quantity = round($data["udesde"], 2); // Cantidad mínima para aplicar el precio
                        // $specific_price->reduction = $mireduction; // Valor de la reducción
                        // $specific_price->from = $finicio; // Fecha de inicio de la validez
                        // $specific_price->to = $ffin; // Fecha de fin de la validez

                        // $specific_price->id_shop = 0;
                        // $specific_price->id_currency = 0;
                        // $specific_price->id_group = 0;
                        // $specific_price->id_customer = 0;
                        // $specific_price->reduction_tax = 0; // Definir si la reducción aplica impuestos (1 = sí)
                        // $specific_price->reduction_type = 'amount'; // Tipo de reducción ('amount' o 'percentage')

                        // if ($exists) {
                        //     if ($specific_price->save()) {
                        //         // Revisamos si existe el la tarifa cabecera y datos en aalv_specific_price_import
                        //         $buscar = Db::getInstance()->getValue("select * from aalv_specific_price_import where id_specific_price = " . $exists . " and id_tarifa_cabecera = " . $data["idtarifa_cabecera"] . " and id_tarifa_linea = " . $data["idtarifa_linea"]);
                        //         if (!$buscar) {

                        //             if(!Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (" . $exists . "," . $data["idtarifa_cabecera"] . "," . $data["idtarifa_linea"] . ")")){
                        //                 Db::getInstance()->Execute("UPDATE aalv_specific_price_import set id_tarifa_cabecera = ".$data["idtarifa_cabecera"].", id_tarifa_linea=".$data["idtarifa_linea"]." where id_specific_price = ".$exists);
                        //             }

                        //             auxiliares::procesarcombinaciones($idproduct);

                        //             $productact = new Product((int)$idproduct);
                        //             $productact->update();
                        //         }
                        //     } else {
                        //         dump("Error al actualizar el SpecificPrice.");
                        //         die();
                        //     }
                        // } else {
                        //     if ($specific_price->add()) {

                        //         $idnewsp = $specific_price->id;
                        //         if(!Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (" . $idnewsp . "," . $data["idtarifa_cabecera"] . "," . $data["idtarifa_linea"] . ")")){
                        //             dump("REVISAR [" . __LINE__ . "]");die();
                        //         }
                        //         auxiliares::procesarcombinaciones($idproduct);

                        //         $productact = new Product((int)$idproduct);
                        //         $productact->update();
                        //     } else {
                        //         dump(Db::getInstance()->getMsgError());
                        //         dump($idproduct);
                        //         dump($idprodattr);
                        //         dump("[" . __LINE__ . "] ACA");
                        //         die();
                        //         // throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Error al insertar el precio, revisar si ya esta agregado id_tarifa_linea => " . $fila);
                        //         auxiliares::sendmail("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Error al insertar el precio, revisar si ya esta agregado id_tarifa_linea => " . $fila);
                        //         return 1;
                        //     }
                        // }

                    }
                }
            }
        } else { // borrado

            $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);
            if ($specificprice != '') {
                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                if ($specificprice != '') {

                    $idp = Db::getInstance()->getValue('select id_product from  aalv_specific_price WHERE id_specific_price='.$specificprice);
                    Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                    auxiliares::procesarcombinaciones($idp);
                }
            }
            Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);
        }

        return 1;
    }
}
