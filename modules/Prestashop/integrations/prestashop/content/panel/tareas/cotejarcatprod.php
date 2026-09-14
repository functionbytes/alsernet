<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function escomunrec($id)
{

    $padre = Db::getInstance()->getValue('SELECT id_parent FROM '._DB_PREFIX_.'category WHERE id_category='.$id);
    $escomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat= '.$id);

    if ($escomun == '') {

        if ($padre <= 2) {
            return false;
        } else {
            return escomunrec($padre);
        }

    } else {
        return true;
    }

}

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/cotejarprod.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function escomun2($id_cat)
{

    $existecomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat='.$id_cat);

    if ($existecomun != '') {
        return true;
    } else {
        return false;
    }

}

function ExistePathCategory2($producto, $id_cat)
{

    $id_padre = Db::getInstance()->getValue('SELECT id_parent FROM aalv_category WHERE id_category='.$id_cat);

    // if (($id_cat>2) && (!escomun2($id_cat)) && ($id_cat!=2821)  && ($id_cat!=2820)) {

    if (($id_padre > 2) && (! escomun2($id_padre)) && ($id_padre != 2821) && ($id_padre != 2820)) {

        // $id_padre = Db::getInstance()->getValue("SELECT id_parent FROM aalv_category WHERE id_category=". $id_cat);
        $existe = ''.Db::getInstance()->getValue('select id_category from aalv_category_product where id_category='.$id_cat.' and id_product='.$producto);

        if ($existe != '') {
            return ExistePathCategory2($producto, (int) $id_padre);
        } else {
            return false;
        }
    } else {
        return true;
    }

}

/*

$rows = Db::getInstance()->ExecuteS("select * from aalv_category_product where id_product=".$idproducto);
        foreach($rows as $row){
          $id_categoryps = $row["id_category"];
          if (!ExistePathCategory2($idproducto, $id_categoryps)){
              if (!escomunrec($id_categoryps)){
                //Db::getInstance()->Execute("delete from aalv_category_product where id_category=".$id_categoryps. " and id_product=".$idproducto);
              //Db::getInstance()->Execute("delete from aalv_category_product_import where id_category=".$id_categoryps. " and id_product=".$idproducto);
             }
          }
        }

*/

function ExisteTodo($idprod, $idcat, $aData)
{

    $rows = Db::getInstance()->ExecuteS('SELECT id_category, id_product FROM aalv_category_product WHERE id_product='.$idprod);
    foreach ($aData as $aRow) {
        $idcatexcel = $aRow['A'];
        $idprodexcel = $aRow['B'];
        if ($idprodexcel == $idprod) {
            $rows[] = ['id_category' => $idcatexcel, 'id_product' => $idprodexcel];
        }
    }

    return $rows;

}

function ExisteEnExcelnew($idprod, $idcat, $rows)
{

    // coger padre y ver si existe en la excel
    $id_padre = Db::getInstance()->getValue('SELECT id_parent FROM aalv_category WHERE id_category='.$idcat);
    // buscar $id_padre y $idprod

    if (($id_padre > 2) && (! escomun2($id_padre)) && ($id_padre != 2821) && ($id_padre != 2820)) {

        foreach ($rows as $row) {

            $idcatexcel = $row['id_category'];
            $idprodexcel = $row['id_product'];

            if (($idcatexcel == $id_padre) && ($idprodexcel == $idprod)) {
                return ExisteEnExcelnew($idprod, $id_padre, $rows);
            }
        }

    } else {
        return true;
    }
}

function ExisteEnExcel($idprod, $idcat, $aData)
{

    // coger padre y ver si existe en la excel
    $id_padre = Db::getInstance()->getValue('SELECT id_parent FROM aalv_category WHERE id_category='.$idcat);
    // buscar $id_padre y $idprod

    if (($id_padre > 2) && (! escomun2($id_padre)) && ($id_padre != 2821) && ($id_padre != 2820)) {

        foreach ($aData as $aRow) {

            $idcatexcel = $aRow['A'];
            $idprodexcel = $aRow['B'];

            if (($idcatexcel == $id_padre) && ($idprodexcel == $idprod)) {
                return ExisteEnExcel($idprod, $id_padre, $aData);
            }
        }

    } else {
        return true;
    }
}

echo 'empieza';

$oSpreadsheet = IOFactory::load(__DIR__.'/tmp/cotejercatprod.xlsx');
// echo "empieza 1";
$aData = $oSpreadsheet->getActiveSheet()->toArray(null, true, true, true);
// dump($aData);
// echo "empieza 2";
$i = 1;
foreach ($aData as $aRow) {

    if ($i != 1) {

        $idcat = $aRow['A'];
        $idprod = $aRow['B'];

        $rows = ExisteTodo($idprod, $idcat, $aData);

        if (ExisteEnExcelnew($idprod, $idcat, $rows)) {
            echo '<br/>Pasa existe bd';
            addlog('INSERT INTO `aalv_category_product`(`id_category`, `id_product`, `position`) VALUES ('.$idcat.','.$idprod.',0);');

        } else {
            echo '<br/>No existe';
        }

        echo " idcat $idcat idprod $idprod";

    }
    $i = 2;
}

echo '<br/>acaba';
