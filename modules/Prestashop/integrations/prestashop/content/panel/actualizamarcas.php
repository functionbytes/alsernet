<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

// <add key="aDSNMYSQL" value="DRIVER={MySQL ODBC 3.51 Driver};SERVER=82.223.36.198;DATABASE=psaddis_lacasadelosaromas;UID=psaddis_aromas;PWD=1@p.i5HS1y;OPTION=3;" />

function copyImg($id_entity, $id_image = null, $url = '', $entity = 'inventaries', $regenerate = true)
{
    $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
    $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
    switch ($entity) {
        default:
        case 'inventaries':
            $image_obj = new Image($id_image);
            $path = $image_obj->getPathForCreation();
            break;
        case 'categories':
            $path = _PS_CAT_IMG_DIR_.(int) $id_entity;
            break;
        case 'manufacturers':
            $path = _PS_MANU_IMG_DIR_.(int) $id_entity;
            break;
        case 'suppliers':
            $path = _PS_SUPP_IMG_DIR_.(int) $id_entity;
            break;
        case 'stores':
            $path = _PS_STORE_IMG_DIR_.(int) $id_entity;
            break;
    }
    $url = urldecode(trim($url));
    $parced_url = parse_url($url);
    if (isset($parced_url['path'])) {
        $uri = ltrim($parced_url['path'], '/');
        $parts = explode('/', $uri);
        foreach ($parts as &$part) {
            $part = rawurlencode($part);
        }
        unset($part);
        $parced_url['path'] = '/'.implode('/', $parts);
    }
    if (isset($parced_url['query'])) {
        $query_parts = [];
        parse_str($parced_url['query'], $query_parts);
        $parced_url['query'] = http_build_query($query_parts);
    }
    if (! function_exists('http_build_url')) {
        require_once _PS_TOOL_DIR_.'http_build_url/http_build_url.php';
    }

    $url = http_build_url('', $parced_url);

    echo $url.'<br/>';

    $orig_tmpfile = $tmpfile;
    if (Tools::copy($url, $tmpfile)) {
        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (! ImageManager::checkImageMemoryLimit($tmpfile)) {
            @unlink($tmpfile);
            echo 'FALSE 0<br/>';

            return false;
        }
        $tgt_width = $tgt_height = 0;
        $src_width = $src_height = 0;
        $error = 0;
        ImageManager::resize($tmpfile, $path.'.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5, $src_width, $src_height);
        $images_types = ImageType::getImagesTypes($entity, true);
        if ($regenerate) {
            $previous_path = null;
            $path_infos = [];
            $path_infos[] = [$tgt_width, $tgt_height, $path.'.jpg'];
            foreach ($images_types as $image_type) {
                $tmpfile = get_best_path($image_type['width'], $image_type['height'], $path_infos);
                if (ImageManager::resize(
                    $tmpfile,
                    $path.'-'.stripslashes($image_type['name']).'.jpg',
                    $image_type['width'],
                    $image_type['height'],
                    'jpg',
                    false,
                    $error,
                    $tgt_width,
                    $tgt_height,
                    5,
                    $src_width,
                    $src_height
                )) {
                    // the last image should not be added in the candidate list if it's bigger than the original image
                    if ($tgt_width <= $src_width && $tgt_height <= $src_height) {
                        $path_infos[] = [$tgt_width, $tgt_height, $path.'-'.stripslashes($image_type['name']).'.jpg'];
                    }
                    if ($entity == 'inventaries') {
                        if (is_file(_PS_TMP_IMG_DIR_.'product_mini_'.(int) $id_entity.'.jpg')) {
                            unlink(_PS_TMP_IMG_DIR_.'product_mini_'.(int) $id_entity.'.jpg');
                        }
                        if (is_file(_PS_TMP_IMG_DIR_.'product_mini_'.(int) $id_entity.'_'.(int) Context::getContext()->shop->id.'.jpg')) {
                            unlink(_PS_TMP_IMG_DIR_.'product_mini_'.(int) $id_entity.'_'.(int) Context::getContext()->shop->id.'.jpg');
                        }
                    }
                }
                if (in_array($image_type['id_image_type'], $watermark_types)) {
                    Hook::exec('actionWatermark', ['id_image' => $id_image, 'id_product' => $id_entity]);
                }
            }
        }
    } else {
        @unlink($orig_tmpfile);
        echo 'FALSE<br>';

        return false;
    }
    unlink($orig_tmpfile);
    echo 'TRUE<br>';

    return true;
}

function get_best_path($tgt_width, $tgt_height, $path_infos)
{
    $path_infos = array_reverse($path_infos);
    $path = '';
    foreach ($path_infos as $path_info) {
        [$width, $height, $path] = $path_info;
        if ($width >= $tgt_width && $height >= $tgt_height) {
            return $path;
        }
    }

    return $path;
}

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

function CrearMarcaDeporte($idmarca, $deporte, $dbh, $dbh2)
{

    // $idmarca = $datos['id_marca'];
    // $deporte = $datos['deporte'];

    $buscarenseo = '/'.getfieldvalue($dbh, 'select safe_name from marcas where id='.$idmarca);
    $shortdesc = ''.getfieldvalue($dbh2, "SELECT texto_superior FROM textos_marcas where url='".$buscarenseo."'");
    $desc = ''.getfieldvalue($dbh2, "SELECT texto_inferior FROM textos_marcas where url='".$buscarenseo."'");

    $shortdesc = str_replace('https://www.a-alvarez.com', '', $shortdesc);
    $desc = str_replace('https://www.a-alvarez.com', '', $desc);

    $categoriadeporte = Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$deporte);

    if (''.$categoriadeporte != '') {

        $existe = ''.Db::getInstance()->getValue('select id from aalv_manufacturer_deporte where id_manufacturer='.$idmarca.' and id_category_deporte='.$categoriadeporte);

        if ($existe == '') {
            $sql = 'insert INTO aalv_manufacturer_deporte(id_manufacturer, id_category_deporte, destacado, orden, tiene_productos) VALUES ('.$idmarca.','.$categoriadeporte.',0,0,0)';
            Db::getInstance()->Execute($sql);
            $idmd = Db::getInstance()->Insert_ID();
            $sql = 'insert INTO aalv_manufacturer_deporte_lang(id, id_lang, texto_superior, texto_inferior) VALUES ('.$idmd.",1,'".$shortdesc."','".$desc."')";
            Db::getInstance()->Execute($sql);
        }

    } else {
        echo 'no existe '.$idmarca.' '.$deporte;
    }

}

function CrearMarca($datos, $dbh)
{

    // SELECT id, id_padre, elemento, orden, url FROM navegacion order by id_padre, orden limit 10

    $nombre = $datos['nombre'];
    $logo = $datos['logo'];

    $marcaExists = Manufacturer::manufacturerExists($datos['id']);
    if ($marcaExists) {
        $marca = new Manufacturer($datos['id']);
    } else {
        $marca = new Manufacturer;
    }

    $marca->force_id = true;
    $marca->id = $datos['id'];
    $marca->name = str_replace('#', '', $nombre);
    $marca->active = true;
    if ($datos['safe_name'] != '') {
        $marca->link_rewrite = Tools::link_rewrite($datos['safe_name']);
    } else {
        $marca->link_rewrite = Tools::link_rewrite($marca->name);
    }

    if ($marcaExists) {
        $marca->update();
    } else {
        $marca->add();
    }

    if (! empty($logo)) {
        $img_url = 'https://www.a-alvarez.com/productsimages/'.$logo.'/logo';
        copyImg($marca->id, null, $img_url, 'manufacturers');
    }

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT id, nombre, safe_name, logo, posicion, deporte, tiene_productos_activos FROM marcas where id>=605 order by 1');
foreach ($rows as $row) {
    CrearMarca($row, $dbh);
}

echo 'Proceso acabado';
