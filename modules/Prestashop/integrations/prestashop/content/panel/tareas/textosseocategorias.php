<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function getfieldvalue($dbh, $sql)
{
    $rows = $dbh->query($sql);
    foreach ($rows as $row) {
        return $row[0];
    }
}

function getdatarows($dbh, $sql)
{
    return $dbh->query($sql);
}

function addsql($texto, $dep, $idioma)
{
    $stdout = fopen(dirname(__FILE__).'/textoscategoriasseo'.$dep.'-'.$idioma.'.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function safeName($sString)
{
    $sReturn = $sString;

    // $sReturn = strtr($sReturn,"()!$'?: ,&+-/.","");

    $a = ['', '', '', '', '', '', '', '¥', 'µ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
    $b = ['S', 'O', 'Z', 's', 'o', 'z', 'Y', 'Y', 'u', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];
    $sReturn = str_replace($a, $b, $sReturn);
    // $sReturn = trim(preg_replace('/\s+/',' ',preg_replace('/[^a-zA-Z0-9\s]/','',strtolower($sReturn))));

    $sReturn = strtolower($sReturn);

    $sReturn = str_replace('(', '', $sReturn);
    $sReturn = str_replace(')', '', $sReturn);
    $sReturn = str_replace('!', '', $sReturn);
    $sReturn = str_replace('$', '', $sReturn);
    $sReturn = str_replace("'", '', $sReturn);
    $sReturn = str_replace('?', '', $sReturn);
    $sReturn = str_replace(':', '', $sReturn);
    $sReturn = str_replace(',', '', $sReturn);
    $sReturn = str_replace('&', '', $sReturn);
    $sReturn = str_replace('+', '', $sReturn);
    $sReturn = str_replace('-', '', $sReturn);
    $sReturn = str_replace('/', '', $sReturn);
    $sReturn = str_replace('.', '', $sReturn);

    $sReturn = str_replace(' ', '_', $sReturn);

    $sReturn = str_replace('__', '_', $sReturn);

    return $sReturn;
}

function buscarseo($dbh, $url, $category, $deporte, $idiomaalvarez, $idioma, $catorigen)
{

    global $textosarellenar;

    $meta_title = '';
    $meta_desc = '';
    $descripcion = '';
    $h1 = '';

    $encuentra = false;
    $rows = getdatarows($dbh, "SELECT * FROM textos_categorias where url='".$url."' and id_deporte=".$deporte." and idioma='".$idiomaalvarez."'");
    foreach ($rows as $row) {
        $encuentra = true;

        $meta_title = trim($row['title']);
        $meta_desc = trim($row['description']);
        $descripcion = $row['texto_superior'].$row['texto_inferior'];
        $h1 = trim($row['h1']);
        break;

    }

    if ($meta_title == '') {

        if ($catorigen->id != 4) {
            $meta_title = $category->name.' - '.$catorigen->name[$idioma].' - Álvarez Deportes';
        } else {
            $meta_title = $category->name.' - '.$catorigen->name[$idioma].' - Armería Álvarez';
        }
    }

    if ($meta_desc == '') {
        $meta_desc = str_replace('{DEPORTE}', $catorigen->name[$idioma], str_replace('{CAT}', $category->name, $textosarellenar[$idiomaalvarez]));
    }

    if ($h1 == '') {
        $h1 = $category->name;
    }

    /*
    $catupdate = new Category($category->id);

    $catupdate->meta_title[$idioma] = $meta_title;
    $catupdate->meta_description[$idioma] = str_replace("https://www.a-alvarez.com","",$meta_desc);
    $catupdate->description[$idioma] = str_replace("https://www.a-alvarez.com","",$descripcion);
    $catupdate->update();
    */

    // echo "<br/>H1: ".$h1. " Idioma: ".$idioma;

    addsql("UPDATE aalv_category_lang SET description='".pSQL(str_replace('https://www.a-alvarez.com', '', $descripcion), true)."', meta_title='".pSQL($meta_title)."',meta_description='".pSQL(str_replace('https://www.a-alvarez.com', '', $meta_desc), true)."' WHERE id_category=".$category->id.' and id_shop=1 and id_lang='.$idioma.';', $deporte, $idioma);

    addsql('REPLACE INTO aalv_appagebuilder_extracat(id_category, id_shop, id_lang, tituloh1) VALUES ('.$category->id.',1,'.$idioma.",'".str_replace("'", '´', $h1)."');", $deporte, $idioma);

    // Db::getInstance()->Execute("REPLACE INTO aalv_appagebuilder_extracat(id_category, id_shop, id_lang, tituloh1) VALUES (".$category->id.",1,".$idioma.",'".str_replace("'","´",$h1)."')");

}

function RecorrerDep($dbh, $id, $iddeporte)
{

    RecorreDeporte($dbh, $id, 1, 'es', $iddeporte);
    RecorreDeporte($dbh, $id, 2, 'en', $iddeporte);
    RecorreDeporte($dbh, $id, 3, 'fr', $iddeporte);
    RecorreDeporte($dbh, $id, 4, 'pt', $iddeporte);
    RecorreDeporte($dbh, $id, 5, 'de', $iddeporte);

}

function RecorreDeporte($dbh, $id, $idioma, $idiomaalvarez, $iddeporte)
{

    $cat = new Category($id);
    $colecctiocat = $cat->getAllChildren($idioma)->getAll();
    foreach ($colecctiocat as $category) {

        // $enlace = safeName($category->name);

        $link = new Link;
        $enlace = $link->getCategoryLink($category->id, null, $idioma);

        // hacer split

        echo '<br>'.$enlace;

        // buscarseo($dbh,end(explode("/",$enlace)), $category, $iddeporte, $idiomaalvarez, $idioma, $cat);
        buscarseo($dbh, end(explode('/', $enlace)), $category, $iddeporte, $idiomaalvarez, $idioma, $cat);

    }

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$textosarellenar = [];
$textosarellenar['es'] = 'Encuentra todos los productos de {CAT} en la tienda online de {DEPORTE} - Álvarez';
$textosarellenar['pt'] = 'Encontra todos os produtos de {CAT} na loja online de {DEPORTE} - Álvarez';
$textosarellenar['en'] = 'Find all inventaries of {CAT} in online shop of {DEPORTE} - Álvarez';
$textosarellenar['de'] = 'Finden Sie alle Produkte von {CAT} in Online-Shop {DEPORTE} - Álvarez';
$textosarellenar['fr'] = 'Trouve tous les produits de {CAT} dans la boutique online de {DEPORTE} - Álvarez';

RecorrerDep($dbh, 3, 1);
RecorrerDep($dbh, 4, 5);
RecorrerDep($dbh, 5, 6);
RecorrerDep($dbh, 6, 3);
RecorrerDep($dbh, 7, 4);
RecorrerDep($dbh, 8, 2);
RecorrerDep($dbh, 9, 9);
RecorrerDep($dbh, 10, 1395);
RecorrerDep($dbh, 11, 10);

echo 'acaba';
