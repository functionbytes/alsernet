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

// copyImg($manufacturer->id, null, $img_url, 'manufacturers');
function translategoogle($text, $idiomasrc, $idiomadest)
{

    $apiKey = 'disabled_api_key';
    $url = 'https://www.googleapis.com/language/translate/v2?key='.$apiKey.'&q='.rawurlencode($text).'&source='.$idiomasrc.'&target='.$idiomadest;
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($handle);
    $responseDecoded = json_decode($response, true);
    curl_close($handle);

    return ''.$responseDecoded['data']['translations'][0]['translatedText'];

}

function CrearAtributo($datos, $id_attribute_group, $dbh)
{

    try {

        $id_attribute = Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$datos['id']);

        $nombre = $datos['nombre'];
        $nombreen = ''.getfieldvalue($dbh, "SELECT nombre FROM valores_prod_idioma where idioma='en' and id_valor=".$datos['id']);
        if ($nombreen == '') {
            // $nombreen = translategoogle($nombre,"es","en");
            $nombreen = $nombre;
        }
        $nombrede = ''.getfieldvalue($dbh, "SELECT nombre FROM valores_prod_idioma where idioma='de' and id_valor=".$datos['id']);
        if ($nombrede == '') {
            // $nombrede = translategoogle($nombre,"es","de");
            $nombrede = $nombre;
        }
        $nombrefr = ''.getfieldvalue($dbh, "SELECT nombre FROM valores_prod_idioma where idioma='fr' and id_valor=".$datos['id']);
        if ($nombrefr == '') {
            // $nombrefr = translategoogle($nombre,"es","fr");
            $nombrefr = $nombre;
        }
        $nombrept = ''.getfieldvalue($dbh, "SELECT nombre FROM valores_prod_idioma where idioma='pt' and id_valor=".$datos['id']);
        if ($nombrept == '') {
            // $nombrept = translategoogle($nombre,"es","pt");
            $nombrept = $nombre;
        }

        $nombre = str_replace('&gt;', '>', $nombre);
        $nombreen = str_replace('&gt;', '>', $nombreen);
        $nombrede = str_replace('&gt;', '>', $nombrede);
        $nombrefr = str_replace('&gt;', '>', $nombrefr);
        $nombrept = str_replace('&gt;', '>', $nombrept);

        $nombre = str_replace('&lt;', '<', $nombre);
        $nombreen = str_replace('&lt;', '<', $nombreen);
        $nombrede = str_replace('&lt;', '<', $nombrede);
        $nombrefr = str_replace('&lt;', '<', $nombrefr);
        $nombrept = str_replace('&lt;', '<', $nombrept);

        $nombre = str_replace("\ &#39;", "'", $nombre);
        $nombreen = str_replace("\ &#39;", "'", $nombreen);
        $nombrede = str_replace("\ &#39;", "'", $nombrede);
        $nombrefr = str_replace("\ &#39;", "'", $nombrefr);
        $nombrept = str_replace("\ &#39;", "'", $nombrept);

        $nombre = str_replace("\&#39;", "'", $nombre);
        $nombreen = str_replace("\&#39;", "'", $nombreen);
        $nombrede = str_replace("\&#39;", "'", $nombrede);
        $nombrefr = str_replace("\&#39;", "'", $nombrefr);
        $nombrept = str_replace("\&#39;", "'", $nombrept);

        $nombre = str_replace('&#39;', "'", $nombre);
        $nombreen = str_replace('&#39;', "'", $nombreen);
        $nombrede = str_replace('&#39;', "'", $nombrede);
        $nombrefr = str_replace('&#39;', "'", $nombrefr);
        $nombrept = str_replace('&#39;', "'", $nombrept);

        $nombre = str_replace('&amp;', ' and ', $nombre);
        $nombreen = str_replace('&amp;', ' and ', $nombreen);
        $nombrede = str_replace('&amp;', ' and ', $nombrede);
        $nombrefr = str_replace('&amp;', ' and ', $nombrefr);
        $nombrept = str_replace('&amp;', ' and ', $nombrept);

        $nombre = str_replace('&', ' and ', $nombre);
        $nombreen = str_replace('&', ' and ', $nombreen);
        $nombrede = str_replace('&', ' and ', $nombrede);
        $nombrefr = str_replace('&', ' and ', $nombrefr);
        $nombrept = str_replace('&', ' and ', $nombrept);

        $nombre = str_replace('~~POS=HEADCOMP', '', $nombre);
        $nombreen = str_replace('~~POS=HEADCOMP', '', $nombreen);
        $nombrede = str_replace('~~POS=HEADCOMP', '', $nombrede);
        $nombrefr = str_replace('~~POS=HEADCOMP', '', $nombrefr);
        $nombrept = str_replace('~~POS=HEADCOMP', '', $nombrept);

        $nombre = strip_tags($nombre);
        $nombreen = strip_tags($nombreen);
        $nombrede = strip_tags($nombrede);
        $nombrefr = strip_tags($nombrefr);
        $nombrept = strip_tags($nombrept);

        if (''.$id_attribute == '') {

            $attribute = new Attribute;

            $attribute->id_attribute_group = $id_attribute_group;

            $attribute->name[1] = substr($nombre, 0, 128);
            $attribute->name[2] = substr($nombreen, 0, 128);
            $attribute->name[3] = substr($nombrefr, 0, 128);
            $attribute->name[4] = substr($nombrept, 0, 128);
            $attribute->name[5] = substr($nombrede, 0, 128);

            $attribute->add();

            // INSERT INTO `aalv_attribute_import`(`id_attribute`, `id_origen`, `grupo`) VALUES ('[value-1]','[value-2]','[value-3]')
            Db::getInstance()->Execute('INSERT INTO aalv_attribute_import(id_attribute, id_origen, grupo) VALUES ('.$attribute->id.','.$datos['id'].",'".$datos['grupo']."')");

        } else {

            $attribute = new Attribute($id_attribute);

            $attribute->id_attribute_group = $id_attribute_group;

            $attribute->name[1] = substr($nombre, 0, 128);
            $attribute->name[2] = substr($nombreen, 0, 128);
            $attribute->name[3] = substr($nombrefr, 0, 128);
            $attribute->name[4] = substr($nombrept, 0, 128);
            $attribute->name[5] = substr($nombrede, 0, 128);

            $attribute->update();

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(_PS_ADMIN_DIR_.'/atributoserrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

function CrearGrupoAtributo($datos, $dbh)
{

    // INSERT INTO `aalv_attribute_group_import`(`id_attribute_group`, `id_origen`, `in_google_shopping`) VALUES ('[value-1]','[value-2]','[value-3]')
    $id_attribute_group = Db::getInstance()->getValue('SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen='.$datos['id']);

    $nombre = $datos['nombre'];
    $nombreen = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_gestion where idioma='en' and id_caracteristica=".$datos['id']);
    if ($nombreen == '') {
        $nombreen = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_idioma where idioma='en' and id_caracteristica=".$datos['id']);
        if ($nombreen == '') {
            // $nombreen = translategoogle($nombre,"es","en");
            $nombreen = $nombre;
        }
    }

    $nombrede = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_gestion where idioma='de' and id_caracteristica=".$datos['id']);
    if ($nombrede == '') {
        $nombrede = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_idioma where idioma='de' and id_caracteristica=".$datos['id']);
        if ($nombrede == '') {
            // $nombrede = translategoogle($nombre,"es","de");
            $nombrede = $nombre;
        }
    }

    $nombrefr = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_gestion where idioma='fr' and id_caracteristica=".$datos['id']);
    if ($nombrefr == '') {
        $nombrefr = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_idioma where idioma='fr' and id_caracteristica=".$datos['id']);
        if ($nombrefr == '') {
            // $nombrefr = translategoogle($nombre,"es","fr");
            $nombrefr = $nombre;
        }
    }

    $nombrept = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_gestion where idioma='pt' and id_caracteristica=".$datos['id']);
    if ($nombrept == '') {
        $nombrept = ''.getfieldvalue($dbh, "SELECT nombre FROM caracteristicas_prod_idioma where idioma='pt' and id_caracteristica=".$datos['id']);
        if ($nombrept == '') {
            // $nombrept = translategoogle($nombre,"es","pt");
            $nombrept = $nombre;
        }
    }

    $nombre = str_replace('&#39;', "'", $nombre);
    $nombreen = str_replace('&#39;', "'", $nombreen);
    $nombrede = str_replace('&#39;', "'", $nombrede);
    $nombrefr = str_replace('&#39;', "'", $nombrefr);
    $nombrept = str_replace('&#39;', "'", $nombrept);

    $nombre = str_replace('&amp;', ' and ', $nombre);
    $nombreen = str_replace('&amp;', ' and ', $nombreen);
    $nombrede = str_replace('&amp;', ' and ', $nombrede);
    $nombrefr = str_replace('&amp;', ' and ', $nombrefr);
    $nombrept = str_replace('&amp;', ' and ', $nombrept);

    $nombre = str_replace('&', ' and ', $nombre);
    $nombreen = str_replace('&', ' and ', $nombreen);
    $nombrede = str_replace('&', ' and ', $nombrede);
    $nombrefr = str_replace('&', ' and ', $nombrefr);
    $nombrept = str_replace('&', ' and ', $nombrept);

    $nombre = strip_tags($nombre);
    $nombreen = strip_tags($nombreen);
    $nombrede = strip_tags($nombrede);
    $nombrefr = strip_tags($nombrefr);
    $nombrept = strip_tags($nombrept);

    if (''.$id_attribute_group == '') {

        $attributegroup = new AttributeGroup;
        $attributegroup->name[1] = substr($nombre, 0, 128);
        $attributegroup->public_name[1] = substr($nombre, 0, 64);
        $attributegroup->name[2] = substr($nombreen, 0, 128);
        $attributegroup->public_name[2] = substr($nombreen, 0, 64);
        $attributegroup->name[3] = substr($nombrefr, 0, 128);
        $attributegroup->public_name[3] = substr($nombrefr, 0, 64);
        $attributegroup->name[4] = substr($nombrept, 0, 128);
        $attributegroup->public_name[4] = substr($nombrept, 0, 64);
        $attributegroup->name[5] = substr($nombrede, 0, 128);
        $attributegroup->public_name[5] = substr($nombrede, 0, 64);
        $attributegroup->group_type = 'radio';
        $attributegroup->add();
        $id_attribute_group = $attributegroup->id;
        Db::getInstance()->Execute('INSERT INTO aalv_attribute_group_import(id_attribute_group, id_origen, in_google_shopping) VALUES ('.$id_attribute_group.','.$datos['id'].",'".$datos['in_google_shopping']."')");
    } else {
        $attributegroup = new AttributeGroup($id_attribute_group);
        $attributegroup->name[1] = substr($nombre, 0, 128);
        $attributegroup->public_name[1] = substr($nombre, 0, 64);
        $attributegroup->name[2] = substr($nombreen, 0, 128);
        $attributegroup->public_name[2] = substr($nombreen, 0, 64);
        $attributegroup->name[3] = substr($nombrefr, 0, 128);
        $attributegroup->public_name[3] = substr($nombrefr, 0, 64);
        $attributegroup->name[4] = substr($nombrept, 0, 128);
        $attributegroup->public_name[4] = substr($nombrept, 0, 64);
        $attributegroup->name[5] = substr($nombrede, 0, 128);
        $attributegroup->public_name[5] = substr($nombrede, 0, 64);
        $attributegroup->group_type = 'radio';
        $attributegroup->update();
    }

    $atributos = getdatarows($dbh, 'SELECT id, nombre,grupo FROM valores_prod where id_caracteristica='.$datos['id']);
    foreach ($atributos as $rowatributo) {
        CrearAtributo($rowatributo, $id_attribute_group, $dbh);
    }

}

function VerificarAtributo($row, $dbh)
{

    $id_attribute = $row['id_attribute'];
    $origen = $row['id_origen'];
    $existeorigen = ''.getfieldvalue($dbh, 'select id from valores_prod where id='.$origen);
    if ($existeorigen == '') {
        // borrar de la tabla
        Db::getInstance()->Execute('DELETE FROM aalv_attribute_import where id_origen='.$origen);
        $attribute = new Attribute($id_attribute);
        $attribute->delete();

    }

}

function VerificarGAtributo($row, $dbh)
{

    $id_attribute = $row['id_attribute_group'];
    $origen = $row['id_origen'];
    $existeorigen = ''.getfieldvalue($dbh, 'select id from caracteristicas_prod where id='.$origen);
    if ($existeorigen == '') {
        // borrar de la tabla
        Db::getInstance()->Execute('DELETE FROM aalv_attribute_group_import where id_origen='.$origen);
        $attribute = new AttributeGroup($id_attribute);
        $attribute->delete();

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

// Db::getInstance()->Execute("truncate table aalv_attribute_import");
// Db::getInstance()->Execute("truncate table aalv_attribute_group_import");
// Db::getInstance()->Execute("truncate table aalv_attribute");
// Db::getInstance()->Execute("truncate table aalv_attribute_impact");
// Db::getInstance()->Execute("truncate table aalv_attribute_lang");
// Db::getInstance()->Execute("truncate table aalv_attribute_group");
// Db::getInstance()->Execute("truncate table aalv_attribute_group_lang");
// Db::getInstance()->Execute("truncate table aalv_attribute_group_shop");
// Db::getInstance()->Execute("truncate table aalv_attribute_shop");

$rows = getdatarows($dbh, 'SELECT id, nombre, in_google_shopping FROM caracteristicas_prod');
foreach ($rows as $row) {
    CrearGrupoAtributo($row, $dbh);
}

// buscar en valores_prod el valor de origen y si no está borrarlo

$atributos = Db::getInstance()->ExecuteS('SELECT id_attribute, id_origen FROM aalv_attribute_import');
foreach ($atributos as $row) {
    VerificarAtributo($row, $dbh);
}

$gatributos = Db::getInstance()->ExecuteS('SELECT * FROM aalv_attribute_group_import');
foreach ($gatributos as $row) {
    VerificarGAtributo($row, $dbh);
}

echo 'Proceso acabado';
