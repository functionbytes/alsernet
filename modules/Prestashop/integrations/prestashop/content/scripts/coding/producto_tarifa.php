<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
include ('/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php');

die();
$dbcon = connectBD();

// Buscamos todos los articulos de la web, que esten activos y que no tengan la etiqueta OCULTO WEB
// $sql = Db::getInstance()->ExecuteS("SELECT id_articulo FROM aalv_combinaciones_import WHERE estado_gestion != 0 AND etiqueta NOT LIKE '%OCULTO WEB%'
//                                     UNION
//                                     SELECT id_articulo FROM aalv_combinacionunica_import  WHERE estado_gestion != 0 AND etiqueta NOT LIKE '%OCULTO WEB%'");

$sql = ['id_articulo' => ]

$n = 0;
$tol = count($sql);
$res = 0;
dump('Inicia agregar tarifas a producto que no la tienen.');
foreach ($sql as $value) {

    $repetidos_combinaciones = Db::getInstance()->ExecuteS("SELECT
                                                api.id_articulo,
                                                COUNT(*) AS cantidad
                                            FROM
                                                aalv_combinaciones_import api
                                            where
                                                id_articulo = " . $value['id_articulo'] . "
                                            GROUP BY api.id_articulo
                                            HAVING COUNT(*) > 1");


    if ($repetidos_combinaciones) {

        $datos_buscar_eliminar = Db::getInstance()->ExecuteS("select GROUP_CONCAT(aci.id_product_attribute ORDER BY aci.id_product_attribute DESC) AS id_product_attribute from aalv_combinaciones_import aci
        left join aalv_product_attribute apa on apa.id_product_attribute = aci.id_product_attribute
        where apa.id_product_attribute is not null and id_articulo =  " . $value['id_articulo']);

        $explo = explode(',', $datos_buscar_eliminar[0]['id_product_attribute']);
        $cantidad = Db::getInstance()->ExecuteS("select * from aalv_product_attribute apa where id_product_attribute in (".$datos_buscar_eliminar[0]['id_product_attribute'].") GROUP BY id_product ");
        if (count($explo) == 2) {
            if(count($cantidad) == 1){
                dump("eliminar 2.1 ");
                $combination_obj = new Combination($explo[0]);
                $combination_obj->delete();
            }else if(count($cantidad) > 1){
                dump("MAS DE UNO 2.1");
                dump($value['id_articulo']);
                dump($cantidad);
                dump($datos_buscar_eliminar);
                dump($explo[0]);
                die();
            }

        }else if (count($explo) == 3) {

            if(count($cantidad) == 1){
                dump("eliminar 3.1 ");
                for ($i=0; $i <2 ; $i++) {
                    $combination_obj = new Combination($explo[$i]);
                    $combination_obj->delete();
                }
            }else if(count($cantidad) > 1){
                dump("MAS DE UNO 3.1");
                dump($value['id_articulo']);
                dump($cantidad);
                dump($datos_buscar_eliminar);
                dump($explo[0]);
                die();
            }

        }else if (count($explo) == 3) {
            dump("SON MAS QUE TRES");
            dump($value['id_articulo']);
            dump($cantidad);
            dump($datos_buscar_eliminar);
            dump($explo[0]);
            die();
        }else if (count($explo) == 4) {

            if(count($cantidad) == 1){
                dump("eliminar 4.1 ");
                for ($i=0; $i <3 ; $i++) {
                    $combination_obj = new Combination($explo[$i]);
                    $combination_obj->delete();
                }
            }else if(count($cantidad) > 1){
                dump("MAS DE UNO 4.1");
                dump($value['id_articulo']);
                dump($cantidad);
                dump($datos_buscar_eliminar);
                dump($explo[0]);
                die();
            }
        }else if (count($explo) > 4) {
            dump("SON MAS QUE 5");
            dump($value['id_articulo']);
            dump($cantidad);
            dump($datos_buscar_eliminar);
            dump($explo[0]);
            die();
        }else{
            Db::getInstance()->execute("DELETE FROM aalv_combinaciones_import where id_product_attribute != ".$datos_buscar_eliminar[0]['id_product_attribute']." and id_articulo = ".$value['id_articulo']);
        }
    }
    $repetidos_combinacionunica = Db::getInstance()->ExecuteS("SELECT
                                                                        api.id_articulo,
                                                                        COUNT(*) AS cantidad
                                                                    FROM
                                                                        aalv_combinacionunica_import api
                                                                    where
                                                                        id_articulo = " . $value['id_articulo'] . "
                                                                    GROUP BY api.id_articulo
                                                                    HAVING COUNT(*) > 1");
    if ($repetidos_combinacionunica) {
        dump("REVISAR PORQUE EXISTE DOS ID_ARTICULOS combinacionunica => " . $value['id_articulo']);
        die();
    }

    //Buscamos las tarifas en la web Antigua
    $sql_antigua    = " select
                            tc.*
                        from
                            tarifa_cabecera as tc
                        where
                            tc.idarticulo = " . $value['id_articulo'] . "
                            AND tc.finicio <= NOW()
                            AND (tc.ffin IS NULL OR tc.ffin >= NOW())
                            AND tc.estado = 1";

    $result_antigua = mysqli_query($dbcon, $sql_antigua);

    while ($re_antigua = mysqli_fetch_array($result_antigua, MYSQLI_ASSOC)) {

        // Revisamos si los id tarifas los tenemos en PrestaShop
        $buscar = Db::getInstance()->ExecuteS("select * from aalv_specific_price_import aspi
                                                left join aalv_specific_price asp on asp.id_specific_price = aspi.id_specific_price
                                                left join aalv_tarifa_cabecera_import atci on aspi.id_tarifa_cabecera = atci.id_tarifa_cabecera
                                                WHERE
                                                aspi.id_tarifa_cabecera = " . $re_antigua['idtarifa_cabecera']);
        if (!$buscar) {
            // No existe, se tiene que crear
            ProcesarTarifaCabecera($re_antigua, null, 1);
            $data2 = mysqli_query($dbcon, "select * from tarifa_linea where idtarifa_cabecera = " . $re_antigua['idtarifa_cabecera']);
            $re2 = mysqli_fetch_array($data2);

            ProcesarTarifaLinea($re2, $re2['idtarifa_linea'], 1);
        }
    }
    echo ".";
    $n++;
    if ($n == 100) {
        $res = $res + 100;
        echo " => " . $tol . " / " . $res;
        echo "\n";
        $n = 0;
    }
}
echo "\n";
dump('Fin de agregar tarifas a producto que no la tienen.');
die();