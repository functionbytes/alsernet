<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// SELECT data  FROM `aalv_integracion_cambios` WHERE `tabla` LIKE '%perfiles_nav%' AND `data` LIKE '%100048423%' and data like '%"principal":true%';

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/restaurarprincipal2.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function get_mime_type($filename)
{
    $idx = explode('.', $filename);
    $count_explode = count($idx);
    $idx = strtolower($idx[$count_explode - 1]);

    $mimet = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    if (isset($mimet[$idx])) {
        return $mimet[$idx];
    } else {
        return 'application/octet-stream';
    }
}

function rutaftp($imagename)
{

    $ruta = '/';

    $primera = substr($imagename, 0, 1);
    $segunda = substr($imagename, 1, 1);

    return $ruta.$primera.'/'.$segunda.'/'.$imagename;

}

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

    echo $url;

    $orig_tmpfile = $tmpfile;
    if (Tools::copy($url, $tmpfile)) {
        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (! ImageManager::checkImageMemoryLimit($tmpfile)) {
            @unlink($tmpfile);

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

        // echo "FALSE";
        return false;
    }
    unlink($orig_tmpfile);

    // echo $orig_tmpfile;
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

function download($imagename)
{

    $local_file = __DIR__.'/backups/'.$imagename;
    $server_file = rutaftp($imagename);

    // set up basic connection

    // try to download $server_file and save to $local_file
    $ret = ftp_get($ftp, $local_file, $server_file, FTP_BINARY);

    // close the connection
    ftp_close($ftp);

    return $ret;

}

function crearimagenmodelo($idprodps, $filename)
{

    if ($filename == '') {
        return;
    }

    $existe = ''.Db::getInstance()->getValue('SELECT id_image FROM aalv_image_import WHERE id_product='.$idprodps." and filename='".$filename."'");

    if ($existe == '') {

        if (download($filename)) {

            $image = new Image;
            $image->id_product = $idprodps;
            $image->position = 0;
            $image->cover = true;

            if (($image->validateFields(false, true)) === true &&
                ($image->validateFieldsLang(false, true)) === true && $image->add()) {

                if (! copyImg($idprodps, $image->id, __DIR__.'/backups/'.$filename, 'inventaries', true)) {
                    $image->delete();
                    // echo "pasa....1";
                } else {
                    if (! file_exists(_PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format)) {
                        $image->delete();
                        // echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                    }
                }
                // echo "llega imagen";
                unlink(__DIR__.'/backups/'.$filename);

                Db::getInstance()->Execute('INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES ('.$image->id.','.$idprodps.",'".$filename."',0,0,0,0)");

            }

        } else {
            // foto no existe en el ftp

        }

    } else {
        $image = new Image($existe);
        $image->cover = true;
        $image->update();

    }

}

echo 'empieza';
$tiempo_inicial = microtime(true);

$rowsp = Db::getInstance()->ExecuteS('SELECT id_product, id_modelo FROM aalv_product_import WHERE id_product>=55053');

$i = 0;
foreach ($rowsp as $prod) {

    $producto = $prod['id_product'];
    $modelo = $prod['id_modelo'];

    // sacar de cada modelo su cat principal

    $datos = ''.Db::getInstance()->getValue("SELECT data  FROM aalv_integracion_cambios WHERE tabla = 'v_sinc_w_modelo' AND data LIKE '%".$modelo."%'");

    if ($datos != '') {

        $valores = json_decode($datos, true);
        $imagen_seo = $valores['imagen_seo'];
        echo '<br/>'.$imagen_seo.' '.$producto;

        crearimagenmodelo($producto, $imagen_seo);

        // if ($i>=10)  die();
    }

}

$tiempo_final = microtime(true);

echo 'acaba '.($tiempo_final - $tiempo_inicial);
