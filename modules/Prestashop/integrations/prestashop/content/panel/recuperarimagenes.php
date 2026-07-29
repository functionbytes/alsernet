<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

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

    // echo $url;

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

function crearimagenes($idproduct, $url)
{

    try {

        // $image_arr = [];

        // $image_arr[] = $url;

        $image_arr = explode(',', $url);

        $product = new Product($idproduct);

        if (count($product->getImages(1)) == 0) { // no tiene imagenes

            $cover = true;

            foreach ($image_arr as $image_val) {

                download($image_val);

                $image = new Image;
                $image->id_product = $product->id;
                $image->position = Image::getHighestPosition($product->id) + 1;
                $image->cover = $cover;
                if (($image->validateFields(false, true)) === true &&
                    ($image->validateFieldsLang(false, true)) === true && $image->add()) {
                    $image->associateTo([1, 2, 3]);
                    if (! copyImg($product->id, $image->id, __DIR__.'/backups/'.$image_val, 'inventaries', true)) {
                        $image->delete();
                        // echo "pasa....1";
                    } else {
                        if (! file_exists(_PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format)) {
                            $image->delete();
                            // echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                        }
                    }
                    // echo "llega imagen";
                    unlink(__DIR__.'/backups/'.$image_val);

                }

                if ($cover) {
                    $cover = false;
                }

            }

        }

    } catch (Exception $e) {

        $d = new DateTime;

        $stdout = fopen(dirname(__FILE__).'/importerp.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, 'producto '.$idproduct);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

function ProcesarProducto($data, $fila, $tipo)
{

    if ((! $data) && ($tipo <= 2)) {

        return 1;
    }

    $idproducterp = $data['id'];
    $idmodelo = $data['id_modelo'];

    // {"id":100021893,"activo":0,"precio":"450.0002","referencia":"BU3MPL7928","imagen":"","id_modelo":100008420,"precio_anterior":0,"vendible":1,"texto_no_vendible":"","microprecio":0,"texto_no_vendible_en":"","precio_sin_iva":371.9,"precio_anterior_sin_iva":0,"unidades_oferta":0,"imagen_seo":"","etiqueta":"","idarticulo":300002072,"estado":0,"es_lote":0,"mostrarlotes":0,"es_servicio_cuota":0,"es_segunda_mano":1,"es_arma":1,"es_arma_fogueo":0,"es_cartucho":0,"ean13":"","upc":"","externo":0,"externo_disponibilidad":0,"codigo_proveedor":"BU3MPL7928","precio_costo_proveedor":300,"tarifa_proveedor":null}

    // echo $idproducterp." ".$idmodelo."<br/>";

    // ver si existe el modelo (producto prestashop)
    $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);

    $idproductattributeps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$idproducterp.' and id_product_attribute in (select id_product_attribute from aalv_product_attribute where id_product = '.$idprodps.')');

    $idprodpssinatributo = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen='.$idproducterp);

    if (($idproductattributeps != '') || ($idprodpssinatributo != '')) {
        $tipo = 2;
    }

    if ($tipo <= 2) {

        if ($idproductattributeps != '') {

            $existe = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);

            if ($existe != '') {

                $proddelattr = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);
                if ($proddelattr != '') {
                    crearimagenproducto($proddelattr, $data['imagen_seo'], $idproductattributeps, $data['id']);
                }
            }
        }

    }

    return 1;

}

function ProcesarModelo($data, $fila, $tipo)
{
    // google translate

    if ($tipo <= 2) {

        if (! $data) {

            return 1;
        }

        $idmodelo = $data['id'];
        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);

        if ($idprodps != '') {
            // update
            $imagesurls = $data['imagen_seo'];
            crearimagenmodelo($idprodps, $imagesurls);
            // crearimagenes($product->id, $imagesurls);

        }

        return 1;
    } else {

        return 1;

    }
}

function ProcesarValores($valores)
{

    $tabla = $valores['tabla'];

    // echo "Pocesando ". $tabla. " fila ". $valores["fila"] . " tipo ". $valores["tipo"];

    switch ($tabla) {

        case 'v_sinc_w_modelo':
            return ProcesarModelo($valores['data'], $valores['fila'], $valores['tipo']);
            break;
        case 'v_sinc_w_producto':
            return ProcesarProducto($valores['data'], $valores['fila'], $valores['tipo']);
            break;

        case 'v_sinc_w_producto_imagen':
            return ProcesarProductoImagen($valores['data'], $valores['fila'], $valores['tipo']);
            break;

        case 'v_sinc_w_modelo_imagen':
            return ProcesarModeloImagen($valores['data'], $valores['fila'], $valores['tipo']);
            break;

    }

    return 0;
}

function ProcesarProductoImagen($data, $fila, $tipo)
{

    $modelo = 0;
    $idorigen = $data['id'];
    $producto = $data['id_producto'];
    $filename = $data['path_imagen'];
    $posicion = $data['orden'];

    $idprodattrps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$producto.' and id_product_attribute in (select id_product_attribute from aalv_product_attribute)');

    $idprodps = '';
    if ($idprodattrps != '') {
        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idprodattrps);
    }

    // $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$modelo);

    if ($idprodps != '') {
        if ($tipo <= 2) {

            // ver si existe la imagen en la tabla image_import
            $existe = ''.Db::getInstance()->getValue('SELECT id_image FROM aalv_image_import WHERE id_product='.$idprodps." and filename='".$filename."'");

            if ($existe != '') {

                $image = new Image($existe);
                $image->position = (int) $posicion;
                $image->update();

                // añadirla a la combinación
                Db::getInstance()->execute('REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES ('.$idprodattrps.','.$image->id.')');

            } else {

                if (download($filename)) {

                    $image = new Image;
                    $image->id_product = $idprodps;
                    $image->position = (int) $posicion;

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

                        Db::getInstance()->Execute('INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES ('.$image->id.','.$idprodps.",'".$filename."',".$idorigen.','.$modelo.','.$producto.','.(int) $posicion.')');

                        // añadirla a la combinación
                        Db::getInstance()->execute('REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES ('.$idprodattrps.','.$image->id.')');

                    }

                } else {
                    // foto no existe en el ftp

                }

            }

        }
    } else {
        if ($tipo == 3) {

            // recuperar del origen
            /*
                        $idimage = "" & Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_origen=".$fila);

                        if ($idimage!=""){
                            $image = new Image($idimage);
                            $image->delete();
                        }
                        */
        }

    }

    return 1;

}

function crearimagenproducto($idprodps, $filename, $idattr, $producto)
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

                Db::getInstance()->Execute('INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES ('.$image->id.','.$idprodps.",'".$filename."',0,0,".$producto.',0)');

                // añadirla a la combinación
                Db::getInstance()->execute('REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES ('.$idattr.','.$image->id.')');

            }

        } else {
            // foto no existe en el ftp

        }

    } else {

        Db::getInstance()->execute('REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES ('.$idattr.','.$existe.')');

    }

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

        echo "<br>idimg....$existe";

        $image = new Image($existe);
        echo '<br>prod....'.$image->id_product;
        $image->cover = true;
        $image->update();
    }

}

function ProcesarModeloImagen($data, $fila, $tipo)
{

    $modelo = $data['id_modelo'];
    $idorigen = $data['id'];
    $producto = 0;
    $filename = $data['path_imagen'];
    $posicion = $data['orden'];

    $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$modelo);

    if ($idprodps != '') {
        if ($tipo <= 2) {

            // ver si existe la imagen en la tabla image_import
            $existe = ''.Db::getInstance()->getValue('SELECT id_image FROM aalv_image_import WHERE id_product='.$idprodps." and filename='".$filename."'");

            if ($existe != '') {

                $image = new Image($existe);
                $image->position = (int) $posicion;
                $image->update();

            } else {

                if (download($filename)) {

                    $image = new Image;
                    $image->id_product = $idprodps;
                    $image->position = (int) $posicion;

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

                        Db::getInstance()->Execute('INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES ('.$image->id.','.$idprodps.",'".$filename."',".$idorigen.','.$modelo.','.$producto.','.(int) $posicion.')');

                    }

                } else {
                    // foto no existe en el ftp

                }

            }

        }
    } else {
        if ($tipo == 3) {

            // recuperar del origen
            /*
                        $idimage = "" & Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_origen=".$fila);

                        if ($idimage!=""){
                            $image = new Image($idimage);
                            $image->delete();
                        }
                        */
        }

    }

    return 1;
}

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/logimagenes.txt', 'a');
    fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' '.$message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

// $filas = Db::getInstance()->ExecuteS("SELECT * FROM aalv_integracion_cambios WHERE tabla = 'v_sinc_w_modelo' and fila=100047459 and tipo=2");

// $filas = Db::getInstance()->ExecuteS("SELECT * FROM aalv_integracion_cambios WHERE id=144362");

// $filas = Db::getInstance()->ExecuteS("SELECT *  FROM `aalv_integracion_cambios` WHERE (tabla ='v_sinc_w_modelo' or tabla='v_sinc_w_producto' or tabla='v_sinc_w_modelo_imagen' or tabla='v_sinc_w_producto_imagen') and fecha_confirmacion>='2022-07-15 00:00:00' ORDER BY `id` ASC");

$d = new DateTime;
echo $d->format("Y-m-d\TH:i:sP");

$i = Tools::getValue('i');

$filas = Db::getInstance()->ExecuteS("SELECT *  FROM `aalv_integracion_cambios` WHERE (tabla ='v_sinc_w_modelo' or tabla='v_sinc_w_producto' or tabla='v_sinc_w_modelo_imagen' or tabla='v_sinc_w_producto_imagen') and fecha_confirmacion>='2023-01-31 10:00:00' ORDER BY `id` ASC limit ".($i * 25000).', 25000');

foreach ($filas as $fila) {

    try {

        $fila['data'] = json_decode($fila['data'], true);

        ProcesarValores($fila);

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/recuperaimagenes.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage().' linea :'.$e->getLine().' idcambio: '.$fila['id']);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

// }

$d = new DateTime;
echo $d->format("Y-m-d\TH:i:sP");
