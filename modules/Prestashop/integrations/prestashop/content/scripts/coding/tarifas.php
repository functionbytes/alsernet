<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../../config/config.inc.php';
include '/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php';

$dbcon = connectBD();

// Buscamos todos los articulos de la web, que esten activos y que no tengan la etiqueta OCULTO WEB

// Buscamos todos los articulos de la web, que esten activos y que no tengan la etiqueta OCULTO WEB
/*$sql = Db::getInstance()->ExecuteS("SELECT id_articulo FROM aalv_combinaciones_import WHERE estado_gestion != 0 AND etiqueta NOT LIKE '%OCULTO WEB%'
                                    UNION
                                    SELECT id_articulo FROM aalv_combinacionunica_import  WHERE estado_gestion != 0 AND etiqueta NOT LIKE '%OCULTO WEB%'");*/

$sql = Db::getInstance()->ExecuteS("SELECT id_articulo FROM aalv_combinaciones_import WHERE estado_gestion != 0 AND etiqueta NOT LIKE '%OCULTO WEB%' AND id_product_attribute IN (SELECT id_product_attribute from aalv_product_attribute where id_product = 2371)");

// $sql = Db::getInstance()->ExecuteS("select id_articulo from aalv_combinaciones_import aci WHERE estado_gestion != 0 AND etiqueta NOT LIKE '%OCULTO WEB%' order by id_product_attribute DESC");

$n = 0;
$tol = count($sql);
$res = 0;
dump('Inicia agregar tarifas a producto que no la tienen.');
foreach ($sql as $value) {

    $repetidos_combinaciones = Db::getInstance()->ExecuteS('SELECT
                                                api.id_articulo,
                                                COUNT(*) AS cantidad
                                            FROM
                                                aalv_combinaciones_import api
                                            where
                                                id_articulo = '.$value['id_articulo'].'
                                            GROUP BY api.id_articulo
                                            HAVING COUNT(*) > 1');

    if ($repetidos_combinaciones) {

        $datos_buscar_eliminar = Db::getInstance()->ExecuteS('select GROUP_CONCAT(aci.id_product_attribute ORDER BY aci.id_product_attribute DESC) AS id_product_attribute from aalv_combinaciones_import aci
        left join aalv_product_attribute apa on apa.id_product_attribute = aci.id_product_attribute
        where apa.id_product_attribute is not null and id_articulo =  '.$value['id_articulo']);

        $explo = explode(',', $datos_buscar_eliminar[0]['id_product_attribute']);
        $cantidad = Db::getInstance()->ExecuteS('select * from aalv_product_attribute apa where id_product_attribute in ('.$datos_buscar_eliminar[0]['id_product_attribute'].') GROUP BY id_product ');
        if (count($explo) == 2) {
            if (count($cantidad) == 1) {
                dump('eliminar 2.1 ');
                $combination_obj = new Combination($explo[0]);
                $combination_obj->delete();
            } elseif (count($cantidad) > 1) {
                dump('MAS DE UNO 2.1');
                dump($value['id_articulo']);
                dump($cantidad);
                dump($datos_buscar_eliminar);
                dump($explo[0]);
                exit();
            }

        } elseif (count($explo) == 3) {

            if (count($cantidad) == 1) {
                dump('eliminar 3.1 ');
                for ($i = 0; $i < 2; $i++) {
                    $combination_obj = new Combination($explo[$i]);
                    $combination_obj->delete();
                }
            } elseif (count($cantidad) > 1) {
                dump('MAS DE UNO 3.1');
                dump($value['id_articulo']);
                dump($cantidad);
                dump($datos_buscar_eliminar);
                dump($explo[0]);
                exit();
            }

        } elseif (count($explo) == 3) {
            dump('SON MAS QUE TRES');
            dump($value['id_articulo']);
            dump($cantidad);
            dump($datos_buscar_eliminar);
            dump($explo[0]);
            exit();
        } elseif (count($explo) == 4) {

            if (count($cantidad) == 1) {
                dump('eliminar 4.1 ');
                for ($i = 0; $i < 3; $i++) {
                    $combination_obj = new Combination($explo[$i]);
                    $combination_obj->delete();
                }
            } elseif (count($cantidad) > 1) {
                dump('MAS DE UNO 4.1');
                dump($value['id_articulo']);
                dump($cantidad);
                dump($datos_buscar_eliminar);
                dump($explo[0]);
                exit();
            }
        } elseif (count($explo) > 4) {
            dump('SON MAS QUE 5');
            dump($value['id_articulo']);
            dump($cantidad);
            dump($datos_buscar_eliminar);
            dump($explo[0]);
            exit();
        } else {
            Db::getInstance()->execute('DELETE FROM aalv_combinaciones_import where id_product_attribute != '.$datos_buscar_eliminar[0]['id_product_attribute'].' and id_articulo = '.$value['id_articulo']);
        }
    }
    $repetidos_combinacionunica = Db::getInstance()->ExecuteS('SELECT
                                                                        api.id_articulo,
                                                                        COUNT(*) AS cantidad
                                                                    FROM
                                                                        aalv_combinacionunica_import api
                                                                    where
                                                                        id_articulo = '.$value['id_articulo'].'
                                                                    GROUP BY api.id_articulo
                                                                    HAVING COUNT(*) > 1');
    if ($repetidos_combinacionunica) {
        dump('REVISAR PORQUE EXISTE DOS ID_ARTICULOS combinacionunica => '.$value['id_articulo']);
        exit();
    }

    // Buscamos las tarifas en la web Antigua
    $sql_antigua = ' select
                            tc.*
                        from
                            tarifa_cabecera as tc
                        where
                            tc.idarticulo = '.$value['id_articulo'].'
                            AND tc.finicio <= NOW()
                            AND (tc.ffin IS NULL OR tc.ffin >= NOW())
                            AND tc.estado = 1';

    $result_antigua = mysqli_query($dbcon, $sql_antigua);

    while ($re_antigua = mysqli_fetch_array($result_antigua, MYSQLI_ASSOC)) {

        // Revisamos si los id tarifas los tenemos en PrestaShop
        $buscar = Db::getInstance()->ExecuteS('select * from aalv_specific_price_import aspi
                                                left join aalv_specific_price asp on asp.id_specific_price = aspi.id_specific_price
                                                left join aalv_tarifa_cabecera_import atci on aspi.id_tarifa_cabecera = atci.id_tarifa_cabecera
                                                WHERE
                                                aspi.id_tarifa_cabecera = '.$re_antigua['idtarifa_cabecera']);
        if (! $buscar) {
            // No existe, se tiene que crear
            ProcesarTarifaCabecera($re_antigua, null, 1);
            $data2 = mysqli_query($dbcon, 'select * from tarifa_linea where idtarifa_cabecera = '.$re_antigua['idtarifa_cabecera']);
            $re2 = mysqli_fetch_array($data2);

            ProcesarTarifaLinea($re2, $re2['idtarifa_linea'], 1);
        }
    }
    echo '.';
    $n++;
    if ($n == 100) {
        $res = $res + 100;
        echo ' => '.$tol.' / '.$res;
        echo "\n";
        $n = 0;
    }
}
echo "\n";
dump('Fin de agregar tarifas a producto que no la tienen.');
exit();
echo "\n";
dump('Listo de Agregar precios a Productos');

// Buscamos todos los precios que no estan finalizados
$precios_sin_finalizar = Db::getInstance()->ExecuteS('select aspi.id_tarifa_cabecera from aalv_specific_price asp
left join aalv_specific_price_import aspi on aspi.id_specific_price = asp.id_specific_price
where `to` is NULL and aspi.id_tarifa_cabecera is not null order by aspi.id_tarifa_cabecera ASC');
$nn = 0;
$total = count($precios_sin_finalizar);
$resta = 0;
foreach ($precios_sin_finalizar as $val) {
    // code...
    // dump($val);die();
    // Buscamos el registro en la web antigua
    $sql_antigua = ' select tc.* from tarifa_cabecera as tc where tc.idtarifa_cabecera = '.$val['id_tarifa_cabecera'];
    $result_antigua = mysqli_query($dbcon, $sql_antigua);
    $re_antigua = mysqli_fetch_array($result_antigua, MYSQLI_ASSOC);
    // dump($re_antigua['ffin']);
    // die();
    if ($re_antigua['ffin'] != null) {
        // Significa que tenemos que editar la informacion
        // No existe, se tiene que crear
        $sqll = Db::getInstance()->ExecuteS('SELECT id_articulo FROM aalv_combinaciones_import WHERE id_articulo = '.$re_antigua['idarticulo'].'
                                    UNION
                                    SELECT id_articulo FROM aalv_combinacionunica_import  WHERE id_articulo = '.$re_antigua['idarticulo']);
        if (count($sqll) == 0) {
            dump('Enviar el id articulo => '.$re_antigua['idarticulo']);

            continue;
        }

        ProcesarTarifaCabecera($re_antigua, null, 2);
        $data2 = mysqli_query($dbcon, 'select * from tarifa_linea where idtarifa_cabecera = '.$val['id_tarifa_cabecera']);
        $re2 = mysqli_fetch_array($data2);

        ProcesarTarifaLinea($re2, $re2['idtarifa_linea'], 2);
        dump($val);
        // dump($re_antigua);
        // dump($re2);
        // die();
    } else {
        echo '.';
        $nn++;
        if ($nn == 100) {
            $resta = $resta + 100;
            echo ' => '.$total.' / '.$resta;
            echo "\n";
            $nn = 0;
        }
    }
}

// ///////////////////////////////////////////////////////////////////////////////////////////////
function ProcesarTarifaCabecera($data, $fila, $tipo)
{
    try {

        if ($tipo <= 2) {

            if (! $data) {
                // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
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

            if ($idprodps == '') {
                // echo "NADA";
                return 1;
                // die();
            }

            if ($data['estado'] == false) {

                $specificprices = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
                foreach ($specificprices as $idsp) {
                    Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);
                }

                $codpais = getPaisPrestashop($data);

                $finicio = str_replace('T', ' ', $data['finicio']);
                $ffin = str_replace('T', ' ', $data['ffin']);

                if (''.$finicio == '') {
                    $finicio = '0000-00-00 00:00:00';
                }

                if (''.$ffin == '') {
                    $ffin = '0000-00-00 00:00:00';
                }

                $sql = 'DELETE FROM aalv_specific_price where id_country='.$codpais." and `from`='".$finicio."' and `to`='".$ffin."' and id_product=".$idprodps.' and id_product_attribute='.$idprodattrps;

                echo "\n".$sql."\n";

                Db::getInstance()->Execute($sql);

                if ($idprodps != '') {
                    procesarcombinaciones($idprodps);
                }
                Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                return 1;
            }

            $piva = (int) $data['porc_iva'];

            $porciva = getIdIva();

            if ($piva == 10) {
                $porciva = 2;
            }
            if ($piva == 4) {
                $porciva = 3;
            }
            if ($piva == 0) {
                $porciva = 0;
            }

            if ($data['idregpais'] == 1) { // Cuidado que con las tarifas de otros países también se envía idregpais=1

                if ($idprodps != '') {

                    $id_tax_rules_group = Db::getInstance()->getValue('select id_tax_rules_group from aalv_product_shop where id_product='.$idprodps);
                    if ($id_tax_rules_group != $porciva) {
                        Db::getInstance()->getValue('update aalv_product_shop set id_tax_rules_group = '.$porciva.' where id_product='.$idprodps);
                        Db::getInstance()->getValue('update aalv_product set id_tax_rules_group = '.$porciva.' where id_product='.$idprodps);
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
                echo 'id => '.$idprodps."\n";
                if ($idprodps != '') {
                    // ver si existe precio específico para la tarifa cabecera
                    $specificprices = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
                    echo 'seeee';
                    foreach ($specificprices as $idsp) {
                        $existesp = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);

                        $codpais = getPaisPrestashop($data);

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
                            // echo $sql;
                            Db::getInstance()->Execute($sql);
                        } else {
                            $sql = 'REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                            VALUES ('.$idtarifa_cabecera.','.$codpais.",'".$finicio."','".$ffin."',".$idprodps.','.$idprodattrps.')';
                            echo $sql;
                            Db::getInstance()->Execute($sql);
                        }
                    }
                }
            } else { // insert ¿que hacemos? no tenemos el precio, que va en las lineas ¿tabla auxiliar?

                if ($idprodps != '') {
                    $pais = getPaisPrestashop($data);

                    $finicio = str_replace('T', ' ', $data['finicio']);
                    $ffin = str_replace('T', ' ', $data['ffin']);

                    if (''.$finicio == '') {
                        $finicio = '0000-00-00 00:00:00';
                    }

                    if (''.$ffin == '') {
                        $ffin = '0000-00-00 00:00:00';
                    }

                    $sql = 'REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                      VALUES ('.$idtarifa_cabecera.','.$pais.",'".$finicio."','".$ffin."',".$idprodps.','.$idprodattrps.')';
                    $res = Db::getInstance()->Execute($sql);
                }
            }

            if ($idprodps != '') {
                procesarcombinaciones($idprodps);
            }

            return 1;
        }
    } catch (Exception $e) {
        return 1;
    }
}

function ProcesarTarifaLinea($data, $fila, $tipo)
{

    try {
        if ($tipo <= 2) {

            if (! $data) {
                return 1;
            }

            if ($data['estado'] == false) {

                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);

                if ($specificprice != '') {
                    $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                    if ($specificprice != '') {
                        $idp = Db::getInstance()->getValue('select id_product from  aalv_specific_price WHERE id_specific_price='.$specificprice);
                        Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                        procesarcombinaciones($idp);
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
                }
            } else {
                $tipo = 1;
            }
            // dump($tipo);
            // dump($specificprice);die();

            if ($tipo == 2) { // update

                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);

                if ($specificprice != '') {

                    $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                    // dump($specificprice);die();
                    if ($specificprice != '') {

                        if ($data['baseimp_anterior']) {

                            if ($data['baseimp_anterior'] > $data['baseimp']) {
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
                            procesarcombinaciones($midproduct);
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

                            $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`)
                                    VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                            Db::getInstance()->Execute($sql);

                            $idnewsp = Db::getInstance()->Insert_ID();

                            Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea)
                                                        VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                            procesarcombinaciones($idproduct);

                            $productact = new Product((int) $idproduct);
                            $productact->update();
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

                                $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                                Db::getInstance()->Execute($sql);

                                $idnewsp = Db::getInstance()->Insert_ID();
                                Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                                procesarcombinaciones($idproduct);
                                $productact = new Product((int) $idproduct);
                                $productact->update();
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

                    $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                    Db::getInstance()->Execute($sql);

                    $idnewsp = Db::getInstance()->Insert_ID();

                    Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                    procesarcombinaciones($idproduct);

                    $productact = new Product((int) $idproduct);
                    $productact->update();
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

                        $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                        Db::getInstance()->Execute($sql);

                        $idnewsp = Db::getInstance()->Insert_ID();
                        Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                        procesarcombinaciones($idproduct);

                        $productact = new Product((int) $idproduct);
                        $productact->update();
                    }
                }
            }
        }

        return 1;
    } catch (Exception $e) {
        return 1;
    }
}

function getPaisPrestashop($data)
{
    // if ($data['codigo_iso_pais']) {
    //     return Db::getInstance()->getValue("SELECT id_country FROM aalv_country WHERE iso_code = '" . $data['codigo_iso_pais'] . "'");
    // } else {
    switch ($data['idregpais']) {
        case 3: // Francia
            return 8;

        case 2: // Portugal
            return 15;

        case 1: // España
        default:
            return 0; // Todos los países
    }
    // }
}

function getIdIva()
{
    return Db::getInstance()->getValue("SELECT id_tax_rules_group FROM aalv_tax_rules_group atrg WHERE active = 1 AND deleted = 0 AND `name` LIKE '%21%'");
}

function procesarcombinaciones($idproduct)
{
    // ver si tiene combinaciones
    $productattributes = Db::getInstance()->ExecuteS('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product='.$idproduct);

    if ($productattributes) {

        $idproductatributeminimo = 0;
        $preciominimo = 999999;
        $numcambios = 0;

        $arprecios = [];

        foreach ($productattributes as $productattributeitem) {

            $noestaborrada = ''.Db::getInstance()->getValue('SELECT id_tot_switch_attribute_disabled FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$productattributeitem['id_product_attribute']);

            if ($noestaborrada == '') {

                // ver si tiene stock
                $stock = StockAvailable::getQuantityAvailableByProduct($idproduct, $productattributeitem['id_product_attribute']);

                if ($stock > 0) {
                    // coger precio
                    $specific_price = '';

                    $miprecio = Product::priceCalculation(1, $idproduct, $productattributeitem['id_product_attribute'], 0, 0, '', 0, 0, 1, true, 6, 0, false, false, $specific_price, false, 0, true, 0, 0, 0);

                    if (is_array($specific_price) && array_key_exists('reduction', $specific_price)) {
                        $miprecio = $miprecio - $specific_price['reduction'];
                    }

                    $miprecio = round($miprecio, 6);

                    $miprecio = ((int) ($miprecio * 100)) / 100;

                    if (! in_array($miprecio, $arprecios)) {
                        $arprecios[] = $miprecio;
                    }

                    if ($miprecio < $preciominimo) {

                        $preciominimo = $miprecio;
                        $idproductatributeminimo = $productattributeitem['id_product_attribute'];
                        $numcambios = $numcambios + 1;
                    }
                }
            }
        }

        if ($idproductatributeminimo != 0) {
            // hacer $idproductatributeminimo la combinacion Por defecto
            $product = new Product($idproduct);
            $product->deleteDefaultAttributes();
            $product->setDefaultAttribute($idproductatributeminimo);
        }
    }
}
