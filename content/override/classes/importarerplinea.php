<?php

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';


/*
function translategoogle($text,$idiomasrc,$idiomadest){

    $apiKey = 'disabled_api_key';
    $url = 'https://www.googleapis.com/language/translate/v2?key='.$apiKey.'&q='.rawurlencode($text).'&source='.$idiomasrc.'&target='.$idiomadest;
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($handle); 
    $responseDecoded = json_decode($response, true);
    curl_close($handle);
    return "".$responseDecoded['data']['translations'][0]['translatedText'];

}
*/

function safeName($sString) {
  $sReturn = $sString;

    
  $sReturn = strtr($sReturn,"()!$'?: ,&+-/.","");

  $a = ['','','','','','','','¥','µ','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ'];
  $b = ['S','O','Z','s','o','z','Y','Y','u','A','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','o','n','o','o','o','o','o','o','u','u','u','u','y','y'];
  $sReturn = str_replace($a, $b, $sReturn);
  $sReturn = trim(preg_replace('/\s+/',' ',preg_replace('/[^a-zA-Z0-9\s]/','',strtolower($sReturn))));

  $sReturn = str_replace(' ','_',$sReturn);

  return $sReturn;
}


function escomunrec($id)
    {

        $padre = Db::getInstance()->getValue("SELECT id_parent FROM "._DB_PREFIX_."category WHERE id_category=".$id);
        $escomun = "". Db::getInstance()->getValue("SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat= ".$id);

        if ($escomun==""){

            if ($padre<=2){
                return false;
            }    
            else{
                return escomunrec($padre);    
            }
            
        }
        else{
            return true;
        }
       

    }



function escomun($id_nav){

    $existecomun = "". Db::getInstance()->getValue("SELECT id FROM aalv_categorias_comunes WHERE id_nav=".$id_nav);

   
    if ($existecomun!=""){
        return true;
    }
    else{
        return false;
    }



}



function ExistePathCategory($producto,  $id_nav){

 
   if (!escomun($id_nav) && ($id_nav!=0) ){


     $elemento = Db::getInstance()->getValue("SELECT id_origen FROM aalv_category_import WHERE id_nav=". $id_nav);
     $id_padre = Db::getInstance()->getValue("SELECT id_padre FROM aalv_category_import WHERE id_nav=". $id_nav);
     $id_cat = Db::getInstance()->getValue("SELECT id_cat FROM aalv_category_import WHERE id_nav=". $id_nav);
           

     //ver si existe id_cat  y producto en  category_import

     if ("".$id_cat!=""){
       $existe = "".Db::getInstance()->getValue("select id_category from aalv_category_product where id_category=".$id_cat." and id_product=".$producto); 

       if ($existe!=""){
          return ExistePathCategory($producto, (int)$id_padre);
       }
       else{
         return false;
       }
     }
     else{
      return false;
     }
  }
  else{
    return true;
  }

}




function get_mime_type($filename) {
    $idx = explode( '.', $filename );
    $count_explode = count($idx);
    $idx = strtolower($idx[$count_explode-1]);

    $mimet = array( 
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
    );

    if (isset( $mimet[$idx] )) {
     return $mimet[$idx];
    } else {
     return 'application/octet-stream';
    }
 }


function rutaftp($imagename){

    $ruta = "/";

    $primera = substr($imagename, 0, 1);
    $segunda = substr($imagename, 1, 1);

    return $ruta.$primera."/".$segunda."/".$imagename;


}


function download($imagename){

    $local_file = __DIR__."/backups/".$imagename;
    $server_file = rutaftp($imagename);

    // set up basic connection
    $ftp = ftp_connect("213.134.40.117");

    // login with username and password
    $login_result = ftp_login($ftp, "ftpaddisprod", "Jul.39871");

    // try to download $server_file and save to $local_file
    $ret = ftp_get($ftp, $local_file, $server_file, FTP_BINARY);

    // close the connection
    ftp_close($ftp);

    return $ret;

}



function procesarcombinaciones($idproduct){
   
        //ver si tiene combinaciones

        $productattributes = Db::getInstance()->ExecuteS("SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product=".$idproduct);

        if ($productattributes){


            $idproductatributeminimo=0;
            $preciominimo=999999;
            $numcambios=0; 

            $arprecios=[];

            foreach($productattributes as $productattributeitem){

                $noestaborrada = "".Db::getInstance()->getValue("SELECT id_tot_switch_attribute_disabled FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute=".$productattributeitem["id_product_attribute"]);

                if ($noestaborrada == ""){

                    //ver si tiene stock
                    $stock = StockAvailable::getQuantityAvailableByProduct($idproduct, $productattributeitem["id_product_attribute"] );

                    if ($stock>0){
                        //coger precio  
                        $specific_price ="";
                        $miprecio = Product::priceCalculation(1,$idproduct,$productattributeitem["id_product_attribute"],0,0,"",0,0,1,true,6,0,false,false,$specific_price,false,0,true,0,0,0);

                        $miprecio = $miprecio - $specific_price["reduction"];

                        $miprecio = round($miprecio,6);
                        
                        $miprecio =  ((int)($miprecio*100))/100; 
                        
                        if (!in_array($miprecio, $arprecios)){
                            $arprecios[]=$miprecio;
                        }
                        
                        
                        if ($miprecio<$preciominimo) {

                            $preciominimo=$miprecio;
                            $idproductatributeminimo = $productattributeitem["id_product_attribute"];
                            $numcambios=$numcambios+1; 
                        }

                    }

                }

            }    

            if ($idproductatributeminimo!=0){
                //hacer $idproductatributeminimo la combinacion por defecto
                
                $product = new Product($idproduct);
                $product->deleteDefaultAttributes();
                $product->setDefaultAttribute($idproductatributeminimo);


            }


            if (count($arprecios)>1) {
                //atributo desde
                $idfeaturedesde=Feature::addFeatureImport("Poner desde");
                $idfeaturedesdevalue = crearFeatureValue($idfeaturedesde, "1",0);
                if ($idfeaturedesdevalue!=0){
                    Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product=".$idproduct);
                    $product = new Product($idproduct);
                    $product->addFeatureProductImport( $idproduct, $idfeaturedesde, $idfeaturedesdevalue);    
                }
            }
            else{
                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product=".$idproduct);
            }

        }

   

}


function crearFeatureValue($idFeature, $value, $custom){


    $idFeatureValue = Db::getInstance()->getValue('
                SELECT fv.`id_feature_value`
                FROM ' . _DB_PREFIX_ . 'feature_value fv
                LEFT JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl ON (fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = 1)
                WHERE `value` = \'' . pSQL($value) . '\'
                AND fv.`id_feature` = ' . (int) $idFeature . '
                AND fv.`custom` = '.$custom.'
                GROUP BY fv.`id_feature_value`');
        if ($idFeatureValue) {
            return (int) $idFeatureValue;
        }
    else
    {

        $feature_value = new FeatureValue();
            $feature_value->id_feature = (int) $idFeature;
            $feature_value->custom = $custom;
            $feature_value->value = array_fill_keys(Language::getIDs(false), $value);

            $feature_value->add();

            return (int) $feature_value->id;

    }
}


function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true)
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int) $id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int) $id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int) $id_entity;
                break;
            case 'stores':
                $path = _PS_STORE_IMG_DIR_ . (int) $id_entity;
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
            $parced_url['path'] = '/' . implode('/', $parts);
        }
        if (isset($parced_url['query'])) {
            $query_parts = array();
            parse_str($parced_url['query'], $query_parts);
            $parced_url['query'] = http_build_query($query_parts);
        }
        if (!function_exists('http_build_url')) {
            require_once _PS_TOOL_DIR_ . 'http_build_url/http_build_url.php';
        }


        $url = http_build_url('', $parced_url);

        echo $url;

        $orig_tmpfile = $tmpfile;
        if (Tools::copy($url, $tmpfile)) {
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
                @unlink($tmpfile);
                return false;
            }
            $tgt_width = $tgt_height = 0;
            $src_width = $src_height = 0;
            $error = 0;
            ImageManager::resize($tmpfile, $path . '.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5, $src_width, $src_height);
            $images_types = ImageType::getImagesTypes($entity, true);
            if ($regenerate) {
                $previous_path = null;
                $path_infos = array();
                $path_infos[] = array($tgt_width, $tgt_height, $path . '.jpg');
                foreach ($images_types as $image_type) {
                    $tmpfile = get_best_path($image_type['width'], $image_type['height'], $path_infos);
                    if (ImageManager::resize(
                        $tmpfile,
                        $path . '-' . stripslashes($image_type['name']) . '.jpg',
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
                            $path_infos[] = array($tgt_width, $tgt_height, $path . '-' . stripslashes($image_type['name']) . '.jpg');
                        }
                        if ($entity == 'products') {
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
        } else {
            @unlink($orig_tmpfile);
            //echo "FALSE";
            return false;
        }
        unlink($orig_tmpfile);
        //echo $orig_tmpfile;
        return true;
    }



    function get_best_path($tgt_width, $tgt_height, $path_infos)
    {
        $path_infos = array_reverse($path_infos);
        $path = '';
        foreach ($path_infos as $path_info) {
            list($width, $height, $path) = $path_info;
            if ($width >= $tgt_width && $height >= $tgt_height) {
                return $path;
            }
        }
        return $path;
    }


function crearimagenes($idproduct, $url){

    try{

        //$image_arr = [];

        //$image_arr[] = $url; 

        $image_arr = explode(',', $url);

                
        $product = new Product($idproduct);

        if (count($product->getImages(1)) == 0) { //no tiene imagenes


            $cover = true;

                    foreach ($image_arr as $image_val) {

                        download($image_val);

                        $image = new Image();
                        $image->id_product = $product->id;
                        $image->position = Image::getHighestPosition($product->id) + 1;
                        $image->cover = $cover;
                        if (($image->validateFields(false, true)) === true &&
                            ($image->validateFieldsLang(false, true)) === true && $image->add())
                        {
                            $image->associateTo([1,2,3]);
                            if (!copyImg($product->id, $image->id, __DIR__."/backups/".$image_val, 'products', true))
                            {
                                $image->delete();
                                echo "pasa....1";
                            }
                            else{
                                    if (!file_exists(_PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format)) {
                                          $image->delete();
                                          echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                                    }   
                            }

                            unlink(__DIR__."/backups/".$image_val);

                        }

                        if ($cover)
                            {
                             $cover = false;
                            }

                 }



        }





    } catch (Exception $e) {


            $d = new DateTime();

            $stdout = fopen(dirname(__FILE__).'/importerp.txt', 'a');
            fwrite($stdout, $d->format("Y-m-d\TH:i:sP")." --- error ".$e->getMessage());    
            fwrite($stdout, "\n"); 
            fwrite($stdout, "producto ".$idproduct); 
            fwrite($stdout, "\n"); 
            fclose($stdout);    

    }



}


function padreorigen($catim, $lista){
    if ($catim["id_padre"]==0){
        return in_array($catim["id_origen"],$lista);
    }
    else{

        $catrec = Db::getInstance()->getRow("SELECT * FROM aalv_category_import WHERE id_nav=". $catim['id_padre']);
        
        if (in_array($catim["id_origen"],$lista)){
            return padreorigen($catrec, $lista);
        }   
        else{
            return false;   
        } 


        
    }
}

function ProcesarPerfilesNav($data, $fila, $tipo){

    if ($tipo<=2){

        echo "llega";
        
        if (!$data){
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }


        $idmodelo=$data["id_modelo"];
        $idvalor=$data["id_valor"];
        $principal=$data["principal"];

        $idprodps="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idmodelo);  

        
        if ($idprodps!="") { 

        
            $catimport = Db::getInstance()->ExecuteS("SELECT * FROM aalv_category_import WHERE id_origen=". $data['id_valor']);

                
            foreach($catimport as $catim){
                $idcatps = $catim["id_cat"];    
                $idnav = $catim["id_nav"];    


                $existe= "".Db::getInstance()->getValue("SELECT id_category FROM aalv_category_product WHERE id_category = ".$idcatps." and id_product=".$idprodps);

                if ($existe!=""){
                    //update, no hacer nada ya que está, pero mirar si cambia principal

                        if (ExistePathCategory($idprodps, $idnav)){
                            if ($principal){
                                //Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                //Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                if (escomunrec($idcatps)){

                                    $cat = new Category($idcatps);
                                    if ($cat->sport==5){

                                       Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                       Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                        
                                    }



                                }
                                else{
                                    Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                    Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                    
                                }


                            }    
    
                        }
                }   
                else{
                    echo "llega";
                    
                        Db::getInstance()->Execute("INSERT INTO aalv_category_product(id_category, id_product, position) VALUES (".$idcatps.",".$idprodps.",0)");
                        
                        if (ExistePathCategory($idprodps, $idnav)){
                            

                            if ($principal){

                                //Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                //Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);

                                if (escomunrec($idcatps)){

                                    $cat = new Category($idcatps);
                                    if ($cat->sport==5){

                                       Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                       Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                    }

                                }
                                else{
                                    Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                    Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                                    
                                }



                            }    
                        }

                }   

                $product = new Product($idprodps);

                $product->id_category_default = Db::getInstance()->getValue("select id_category_default from aalv_product WHERE id_product=".$idprodps);
                $product->update();


                Db::getInstance()->Execute("REPLACE INTO aalv_category_product_import(id_category, id_product, fila) VALUES (".$idcatps.",".$idprodps.",". $data['id'].")");
            }   

            return 1;     

        }    

    }
    else{

        $idprodps="".Db::getInstance()->getValue("SELECT id_product FROM aalv_category_product_import WHERE fila=".$fila);  
        $idcatpsrows=Db::getInstance()->ExecuteS("SELECT id_category FROM aalv_category_product_import WHERE fila=".$fila);  

        if ($idcatpsrows){

            foreach($idcatpsrows as $idcatpsrow){
                $idcatps = $idcatpsrow["id_category"];
                Db::getInstance()->Execute("DELETE FROM aalv_category_product where id_category=".$idcatps." and id_product=".$idprodps);
            }

            $product = new Product($idprodps);
            $product->update();

        }
        return  1;


    }    
        
        
}

function ProcesarAyudasMod($data, $fila, $tipo){




        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }



            $idmodelo=$data["id_modelo"];
            $idayuda=$data["id_ayuda"];
    
            $idprodps="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idmodelo);  
            $idayudaps="".Db::getInstance()->getValue("SELECT id FROM aalv_ayudas WHERE idorigen=".$idayuda);   
            if (($idprodps!="") && ($idayudaps!="")){
                Db::getInstance()->Execute("DELETE FROM aalv_ayudas_prod WHERE id_product=".$idprodps." and id_ayuda=".$idayudaps);
                Db::getInstance()->Execute("INSERT INTO aalv_ayudas_prod(id_product, id_ayuda, orden, fila) VALUES (".$idprodps.",".$idayudaps.",0,".$fila.")");
                return 1;
            }   
            else{
                return 1;
            }   
        }
        else{
            //borraddo
            Db::getInstance()->Execute("DELETE FROM aalv_ayudas_prod WHERE fila=".$fila);
            return 1;
        }

        
        
}




function ProcesarTipoProducto($data, $idprodps){

    $tipoproducto = gettipoproducto($idprodps);

    if ($tipoproducto==0) {

        if ($data["es_arma"]==1){
            Db::getInstance()->Execute("INSERT INTO aalv_feature_product(id_feature, id_product, id_feature_value) VALUES (5,".$idprodps.",10100)");
            return;
        }
        if ($data["es_arma_fogueo"]==1){
            Db::getInstance()->Execute("INSERT INTO aalv_feature_product(id_feature, id_product, id_feature_value) VALUES (5,".$idprodps.",10102)");
            return;
        }
         if ($data["es_cartucho"]==1){
            Db::getInstance()->Execute("INSERT INTO aalv_feature_product(id_feature, id_product, id_feature_value) VALUES (5,".$idprodps.",10101)");
            return;
        }

    }

    if ($tipoproducto==10100) { //es arma ya el producto

        if ($data["es_arma"]==0){

            //ver si todos tienen es_arma a cero, y si es así, borrar el tipo
            if (consultartotal( $idprodps, "es_arma")==0){
                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=5 and id_product=".$idprodps." and id_feature_value=10100");
                ProcesarTipoProducto($data, $idprodps);
            }
        }

    }

    if ($tipoproducto==10101) { //es cartucho

        if ($data["es_cartucho"]==0){
            if (consultartotal( $idprodps, "es_cartucho")==0){
                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=5 and id_product=".$idprodps." and id_feature_value=10101");
                ProcesarTipoProducto($data, $idprodps);
            }
            
        }
    }

    if ($tipoproducto==10102) { //es arma fogueo

        if ($data["es_arma_fogueo"]==0){
            if (consultartotal( $idprodps, "es_arma_fogueo")==0){
                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=5 and id_product=".$idprodps." and id_feature_value=10102");
                ProcesarTipoProducto($data, $idprodps);
            }
            
        }
    }

}

function consultartotal($idprodps, $campo){

    $total = Db::getInstance()->getValue("SELECT ifnull(sum(".$campo."),0) FROM aalv_combinaciones_import where id_product_attribute in (SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product=".$idprodps.")");
    $totalp = "".Db::getInstance()->getValue("SELECT ".$campo." FROM aalv_combinacionunica_import WHERE id_product=".$idprodps);
    if ($totalp!="") {
        $total = $total+(int)$totalp;
    }
    
    return $total;

}

function gettipoproducto($idprodpresta){

    if ("".$idprodpresta==""){
        return 0;
    }
    else{

        $id_feature_value = "".Db::getInstance()->getValue("SELECT id_feature_value FROM aalv_feature_product WHERE id_feature=5 and id_product=".$idprodpresta);

        if ($id_feature_value==""){
            return 0;
        }
        else{
            return (int)$id_feature_value;    
        }
        


    }


}


function procesarCFSG($idproduct){

   
        $categoria=0;
        $familia=0;
        $subfamilia=0;
        $grupo=0;
     
        $essegundamano=0;

        
        $productattribute = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product=".$idproduct);

        if ($productattribute!=""){
        
             $categoria=Db::getInstance()->getValue("SELECT categoria FROM aalv_combinaciones_import WHERE id_product_attribute=".$productattribute);
             $familia=Db::getInstance()->getValue("SELECT familia FROM aalv_combinaciones_import WHERE id_product_attribute=".$productattribute);
             $subfamilia=Db::getInstance()->getValue("SELECT subfamilia FROM aalv_combinaciones_import WHERE id_product_attribute=".$productattribute);
             $grupo=Db::getInstance()->getValue("SELECT grupo FROM aalv_combinaciones_import WHERE id_product_attribute=".$productattribute);
             $essegundamano=Db::getInstance()->getValue("SELECT es_segunda_mano FROM aalv_combinaciones_import WHERE id_product_attribute=".$productattribute);
            
        }
        else{
        
             $categoria=Db::getInstance()->getValue("SELECT categoria FROM aalv_combinacionunica_import WHERE id_product=".$idproduct);
             $familia=Db::getInstance()->getValue("SELECT familia FROM aalv_combinacionunica_import WHERE id_product=".$idproduct);
             $subfamilia=Db::getInstance()->getValue("SELECT subfamilia FROM aalv_combinacionunica_import WHERE id_product=".$idproduct);
             $grupo=Db::getInstance()->getValue("SELECT grupo FROM aalv_combinacionunica_import WHERE id_product=".$idproduct);
             $essegundamano=Db::getInstance()->getValue("SELECT es_segunda_mano FROM aalv_combinacionunica_import WHERE id_product=".$idproduct);
                             

        }    

        


        $product = new Product($idproduct);
    
        Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=11 and id_product=".$idproduct);    
        if ($categoria!=0){
            $idfeaturevalue = crearFeatureValue(11, $categoria, 1);    
            if ($idfeaturevalue!=0){
                
                $product->addFeatureProductImport( $idproduct, 11, $idfeaturevalue);    
            }
        }   

        Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=12 and id_product=".$idproduct);    
        if ($familia!=0){
            $idfeaturevalue = crearFeatureValue(12, $familia, 1);    
            if ($idfeaturevalue!=0){
                
                $product->addFeatureProductImport( $idproduct, 12, $idfeaturevalue);    
            }
        }   
    
        Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=13 and id_product=".$idproduct);    
        if ($subfamilia!=0){
            $idfeaturevalue = crearFeatureValue(13, $subfamilia, 1);    
            if ($idfeaturevalue!=0){
                
                $product->addFeatureProductImport( $idproduct, 13, $idfeaturevalue);    
            }
        }   
                
        Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=14 and id_product=".$idproduct);        
        if ($grupo!=0){
            $idfeaturevalue = crearFeatureValue(14, $grupo, 1);    
            if ($idfeaturevalue!=0){
                
                $product->addFeatureProductImport( $idproduct, 14, $idfeaturevalue);    
            }
        }   

        if ($essegundamano==0){
            $product->show_condition=false;
            $product->condition="new";
        }
        else{
            $product->show_condition=true;
            $product->condition="used";
        }
        $product->update();

}



function ProcesarProducto($data, $fila, $tipo){

        echo "<br/>llega 0";

        if ((!$data) && ($tipo<=2)){
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            echo "<br/>sale";
            echo $data;
            return 1;
        }

        try{    

        echo "<br/>llega 1";        

        $idproducterp = $data["id"];
        $idmodelo =  $data["id_modelo"];

        $categoria =  $data["categoria"];
        $categoriaarray = explode("|", $categoria);
        if (count($categoriaarray)>1){
                $categoria = $categoriaarray[0];
        }    

        $familia =  $data["familia"];
        $familiaarray = explode("|", $familia);
        if (count($familiaarray)>1){
                $familia = $familiaarray[0];
        }    
    
        $subfamilia =  $data["subfamilia"];
        $subfamiliaarray = explode("|", $subfamilia);
        if (count($subfamiliaarray)>1){
                $subfamilia = $subfamiliaarray[0];
        }        


        $grupo =  $data["grupo"];
        $grupoarray = explode("|", $grupo);
        if (count($grupoarray)>1){
                $grupo = $grupoarray[0];
        }  


        if (!$categoria) $categoria=0;
        if (!$familia) $familia=0;
        if (!$subfamilia) $subfamilia=0;
        if (!$grupo) $grupo=0;
        
        
        $esarma = $data["es_arma"];
        $esarmafogueo = $data["es_arma_fogueo"];
        $escartucho = $data["es_cartucho"];
        $tarifaproveedor = $data["tarifa_proveedor"];

        if (!$esarma) $esarma=0;
        if (!$esarmafogueo) $esarmafogueo=0;
        if (!$escartucho) $escartucho=0;
        if (!$tarifaproveedor) $tarifaproveedor=0;


        echo "<br/>llega 2";            

        //{"id":100021893,"activo":0,"precio":"450.0002","referencia":"BU3MPL7928","imagen":"","id_modelo":100008420,"precio_anterior":0,"vendible":1,"texto_no_vendible":"","microprecio":0,"texto_no_vendible_en":"","precio_sin_iva":371.9,"precio_anterior_sin_iva":0,"unidades_oferta":0,"imagen_seo":"","etiqueta":"","idarticulo":300002072,"estado":0,"es_lote":0,"mostrarlotes":0,"es_servicio_cuota":0,"es_segunda_mano":1,"es_arma":1,"es_arma_fogueo":0,"es_cartucho":0,"ean13":"","upc":"","externo":0,"externo_disponibilidad":0,"codigo_proveedor":"BU3MPL7928","precio_costo_proveedor":300,"tarifa_proveedor":null}    

        echo $idproducterp." ".$idmodelo."<br/>";

        //ver si existe el modelo (producto prestashop)
        $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idmodelo);


        //ver si existe el producto (posible combinacion prestashop)
        //$idproductattributeps = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen=".$idproducterp);


        $idproductattributeps = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen=".$idproducterp." and id_product_attribute in (select id_product_attribute from aalv_product_attribute where id_product = ".$idprodps.")");



        $idprodpssinatributo = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen=".$idproducterp);

        if ($idprodpssinatributo!=$idprodps){ //tema producto cambia de modelo
                $idprodpssinatributo="";
                $tipo=1;
        }    
        

        echo $idprodps." ".$idproductattributeps." ".$idprodpssinatributo."<br/>";

        if ($idproductattributeps!=""){

          


           Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=".$data["unidades_oferta"].",etiqueta='".$data["etiqueta"]."',estado_gestion=".$data["estado"].",es_segunda_mano=".$data["es_segunda_mano"].",externo_disponibilidad=".$data["externo_disponibilidad"].",codigo_proveedor='".$data["codigo_proveedor"]."',precio_costo_proveedor=".$data["precio_costo_proveedor"].",tarifa_proveedor=".$tarifaproveedor.",es_arma=".$esarma.",es_arma_fogueo=".$esarmafogueo.",es_cartucho=".$escartucho.",categoria=".$categoria.",familia=".$familia.",subfamilia=".$subfamilia.",grupo=".$grupo.",id_articulo=".$data["idarticulo"]." WHERE id_product_attribute=".$idproductattributeps);

        }    

        if ($idprodpssinatributo!=""){
            
          


            Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import set unidades_oferta=".$data["unidades_oferta"].",etiqueta='".$data["etiqueta"]."',estado_gestion=".$data["estado"].",es_segunda_mano=".$data["es_segunda_mano"].",externo_disponibilidad=".$data["externo_disponibilidad"].",codigo_proveedor='".$data["codigo_proveedor"]."',precio_costo_proveedor=".$data["precio_costo_proveedor"].",tarifa_proveedor=".$tarifaproveedor.",es_arma=".$esarma.",es_arma_fogueo=".$esarmafogueo.",es_cartucho=".$escartucho.",categoria=".$categoria.",familia=".$familia.",subfamilia=".$subfamilia.",grupo=".$grupo.",id_articulo=".$data["idarticulo"]." WHERE id_product=".$idprodpssinatributo);
            
        }      


        if (($idproductattributeps!="") || ($idprodpssinatributo!="")){
            $tipo = 2;
        }



        if ($tipo<=2){


                

                if ($tipo==1){
                    //crear?
                    //ver si existe el prod presta. si no existe, es que no se ha pasado antes el modelo

                    if ($idprodps!=""){


                        //tenemos que ver si ya hay en combinacionunica alfo para el modelo    

                        $existencombinaciones =  Db::getInstance()->getValue("SELECT count(*) FROM aalv_product_attribute WHERE id_product=".$idprodps); 

                        if ($existencombinaciones!=0){

                            $product = new Product($idprodps);

                            //procesar perfiles
                            $rowperfiles = Db::getInstance()->ExecuteS("SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto=".$idproducterp." and activo=1 order by orden");

                            $idattributes = [];
                            foreach($rowperfiles as $row){

                                $idattr = "".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$row["id_valor"]);
                                if ($idattr!=""){
                                    $idattributes[]=(int)$idattr;
                                }                                
                            }  


                            $ean13="";
                            if (Validate::isEan13("".$data["ean13"])){
                                $ean13=$data["ean13"];
                            }
                            $upc="";
                            if (Validate::isUpc("".$data["upc"])){
                                $upc=$data["upc"];
                            }


                            $idProductAttribute = $product->addCombinationEntity(
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            $data["referencia"],
                            $product->id_supplier,
                            $ean13,
                            0,
                            null,
                            $upc,
                            1,
                            [1],
                            null,
                            '',
                            null,
                            false,
                            null);

                            $combination = new Combination((int) $idProductAttribute);
                            $combination->setAttributes($idattributes);
                            //$combination->default_on=true;

                            $combination->update();

                            Db::getInstance()->Execute("INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES (".$idProductAttribute.",".$data["id"].",".$data["idarticulo"].")");

                            Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=".$data["unidades_oferta"].",etiqueta='".$data["etiqueta"]."',estado_gestion=".$data["estado"].",es_segunda_mano=".$data["es_segunda_mano"].",externo_disponibilidad=".$data["externo_disponibilidad"].",codigo_proveedor='".$data["codigo_proveedor"]."',precio_costo_proveedor=".$data["precio_costo_proveedor"].",tarifa_proveedor=".$tarifaproveedor.",es_arma=".$esarma.",es_arma_fogueo=".$esarmafogueo.",es_cartucho=".$escartucho.",categoria=".$categoria.",familia=".$familia.",subfamilia=".$subfamilia.",grupo=".$grupo." WHERE id_product_attribute=".$idProductAttribute);


                            if (("".$data["activo"]=="0") || ("".$data["estado"]=="0")) {
                                    Db::getInstance()->Execute("INSERT INTO aalv_tot_switch_attribute_disabled( id_product_attribute, id_shop) VALUES (".$idProductAttribute.",1)");
                                    $combination = new Combination((int) $idProductAttribute);
                                    $combination->default_on=false;
                                    $combination->update();
                            }
                            else{
                                   Db::getInstance()->Execute("DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute=".$idProductAttribute." AND  id_shop=1");    
                            }

                            if ("".$data["unidades_oferta"]!="0"){
                                $product->unity = "".$data["unidades_oferta"];
                                
                            }
                            $product->update();
                            //tema armas. Aquí ya existen

                            try{
                                crearimagenproducto($idprodps, $data["imagen_seo"], $idProductAttribute, $data["id"]);
                            } catch (Exception $e) {

                            }    

                        }  
                        else{

                             //ver si ya existe algo en combinacionunica

                             $existeunica="".Db::getInstance()->getValue("SELECT id_origen FROM aalv_combinacionunica_import WHERE id_product=".$idprodps);   

                             if ($existeunica==""){
                                //crearlo como unica, al ser la primera

                                $product = new Product($idprodps);
                                $product->reference = $data["referencia"];

                                if (Validate::isEan13("".$data["ean13"])){
                                    $product->ean13 = $data["ean13"];    
                                }
                                
                                if (Validate::isUpc("".$data["upc"])){
                                    $product->upc = $data["upc"];    
                                }
                                

                                if ("".$data["activo"]=="0"){
                                    $product->active=false;   
                                }
                                else{
                                    $product->active=true;      
                                }
    
        

                                Db::getInstance()->Execute("INSERT INTO aalv_combinacionunica_import(id_product, id_origen, id_articulo) VALUES (".$idprodps.",".$data["id"].",".$data["idarticulo"].")");

                                Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import set unidades_oferta=".$data["unidades_oferta"].",etiqueta='".$data["etiqueta"]."',estado_gestion=".$data["estado"].",es_segunda_mano=".$data["es_segunda_mano"].",externo_disponibilidad=".$data["externo_disponibilidad"].",codigo_proveedor='".$data["codigo_proveedor"]."',precio_costo_proveedor=".$data["precio_costo_proveedor"].",tarifa_proveedor=".$tarifaproveedor.",es_arma=".$data["es_arma"].",es_arma_fogueo=".$data["es_arma_fogueo"].",es_cartucho=".$data["es_cartucho"].",categoria=".$categoria.",familia=".$familia.",subfamilia=".$subfamilia.",grupo=".$grupo." WHERE id_product=".$idprodps);


                                if ("".$data["unidades_oferta"]!="0"){
                                    $product->unity = "".$data["unidades_oferta"];
                                }
                                $product->update();


                                //arama lo que venga

                             }
                             else{
                                //borrar de combinacionunica y pasarla a combinacion normal


                                //la que llega
                                $product = new Product($idprodps);


                                 //procesar perfiles
                                $rowperfiles = Db::getInstance()->ExecuteS("SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto=".$idproducterp." and activo=1 order by orden");

                                $idattributes = [];
                                foreach($rowperfiles as $row){

                                    $idattr = "".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$row["id_valor"]);
                                    if ($idattr!=""){
                                        $idattributes[]=(int)$idattr;
                                    }                                
                                }  


                                $ean13="";
                                if (Validate::isEan13("".$data["ean13"])){
                                    $ean13=$data["ean13"];
                                }
                                $upc="";
                                if (Validate::isUpc("".$data["upc"])){
                                    $upc=$data["upc"];
                                }



                                $idProductAttribute = $product->addCombinationEntity(
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                $data["referencia"],
                                $product->id_supplier,
                                $ean13,
                                0,
                                null,
                                $upc,
                                1,
                                [1],
                                null,
                                '',
                                null,
                                false,
                                null);

                                $combination = new Combination((int) $idProductAttribute);
                                $combination->setAttributes($idattributes);
                                //$combination->default_on=true;

                                $combination->update();


                                Db::getInstance()->Execute("INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES (".$idProductAttribute.",".$data["id"].",".$data["idarticulo"].")");

                                Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=".$data["unidades_oferta"].",etiqueta='".$data["etiqueta"]."',estado_gestion=".$data["estado"].",es_segunda_mano=".$data["es_segunda_mano"].",externo_disponibilidad=".$data["externo_disponibilidad"].",codigo_proveedor='".$data["codigo_proveedor"]."',precio_costo_proveedor=".$data["precio_costo_proveedor"].",tarifa_proveedor=".$tarifaproveedor.",es_arma=".$data["es_arma"].",es_arma_fogueo=".$data["es_arma_fogueo"].",es_cartucho=".$data["es_cartucho"].",categoria=".$categoria.",familia=".$familia.",subfamilia=".$subfamilia.",grupo=".$grupo." WHERE id_product_attribute=".$idProductAttribute);


                                if (("".$data["activo"]=="0") || ("".$data["estado"]=="0")){
                                        Db::getInstance()->Execute("INSERT INTO aalv_tot_switch_attribute_disabled( id_product_attribute, id_shop) VALUES (".$idProductAttribute.",1)");
                                        $combination = new Combination((int) $idProductAttribute);
                                        $combination->default_on=false;
                                        $combination->update();
                                }
                                else{
                                    Db::getInstance()->Execute("DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute=".$idProductAttribute." AND  id_shop=1");
                                }    



                                if ("".$data["unidades_oferta"]!="0"){
                                    $product->unity = "".$data["unidades_oferta"];
                                    $product->update();
                                }    


                                try{    
                                crearimagenproducto($idprodps, $data["imagen_seo"], $idProductAttribute, $data["id"]);
                                } catch (Exception $e) {

                                }    

                                //la anterior que estaba en unica, recuperarla y pasarla a combinacion

                                $rowpasacomb = Db::getInstance()->getRow("SELECT * FROM aalv_combinacionunica_import WHERE id_product=".$idprodps);

                                $product = new Product($idprodps);


                                 //procesar perfiles
                                $rowperfiles = Db::getInstance()->ExecuteS("SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto=".$rowpasacomb["id_origen"]." and activo=1 order by orden");

                                $idattributes = [];
                                foreach($rowperfiles as $row){

                                    $idattr = "".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$row["id_valor"]);
                                    if ($idattr!=""){
                                        $idattributes[]=(int)$idattr;
                                    }                                
                                }  



                                $idProductAttribute = $product->addCombinationEntity(
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                $product->reference,
                                $product->id_supplier,
                                $product->ean13,
                                0,
                                null,
                                $product->upc,
                                1,
                                [1],
                                null,
                                '',
                                null,
                                false,
                                null);

                                $combination = new Combination((int) $idProductAttribute);


                                $combination->setAttributes($idattributes);
                                //$combination->default_on=true;

                                $combination->update();

                                Db::getInstance()->Execute("INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES (".$idProductAttribute.",".$rowpasacomb["id_origen"].",".$rowpasacomb["id_articulo"].")");

                                Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=".$rowpasacomb["unidades_oferta"].",etiqueta='".$rowpasacomb["etiqueta"]."',estado_gestion=".$rowpasacomb["estado_gestion"].",es_segunda_mano=".$rowpasacomb["es_segunda_mano"].",externo_disponibilidad=".$rowpasacomb["externo_disponibilidad"].",codigo_proveedor='".$rowpasacomb["codigo_proveedor"]."',precio_costo_proveedor=".$rowpasacomb["precio_costo_proveedor"].",tarifa_proveedor=".$tarifaproveedor.",es_arma=".$rowpasacomb["es_arma"].",es_arma_fogueo=".$rowpasacomb["es_arma_fogueo"].",es_cartucho=".$rowpasacomb["es_cartucho"].",categoria=".$rowpasacomb["categoria"].",familia=".$rowpasacomb["familia"].",subfamilia=".$rowpasacomb["subfamilia"].",grupo=".$rowpasacomb["grupo"]." WHERE id_product_attribute=".$idProductAttribute);


                                //pasar repositorio stock 
                                Db::getInstance()->Execute("UPDATE aalv_repositorio_stock SET id_product_attribute=".$idProductAttribute." where id_product=".$idprodps." and id_product_attribute=0");     


                                Db::getInstance()->Execute("delete from aalv_combinacionunica_import where id_product=".$idprodps);    
                                

                                try{
                                $fotosdelmodelo = Db::getInstance()->ExecuteS("SELECT * FROM aalv_image_import WHERE id_product=".$idprodps." and producto=0");

                                foreach($fotosdelmodelo as $foto){
                                    //crearimagenproducto($idprodps, $foto["filename"], $idProductAttribute, $data["id"]);
                                }
                                
                                } catch (Exception $e) {

                                }    




                             }   



                        }    






                    }    
                    else{
                        //producto erp sin modelo  


                        sendmail2("Viene producto ".$idproducterp. " antes de crear el modelo ".$idmodelo);       


                        return 1;    

                    }    



                }
                else{
                        
                    
                                    
                    if ($idprodpssinatributo!=""){    

                       $proddelattr = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product WHERE id_product=".$idprodpssinatributo);         
                       if ($proddelattr==""){
                                return 1;
                       }     



                        $product = new Product($idprodpssinatributo);

                        

                        $product->reference = $data["referencia"];
                        
                        if (Validate::isEan13("".$data["ean13"])){
                            $product->ean13 = $data["ean13"];

                        }
                        
                        if (Validate::isUpc("".$data["upc"])){
                            $product->upc = $data["upc"];    
                        }
                        

                        if ("".$data["activo"]=="0"){
                             $product->active=false;   
                        }

                        if ("".$data["unidades_oferta"]!="0"){
                            $product->unity = "".$data["unidades_oferta"];
                        }    

                        

                        $product->update();    

                        

                    }        
                    
    
                    if ($idproductattributeps!=""){

                       $proddelattr = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps);         
                       if ($proddelattr==""){
                                return 1;
                       } 
                       
                       $existe = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps);

                       if ($existe!=""){

                            //procesar perfiles
                            $rowperfiles = Db::getInstance()->ExecuteS("SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto=".$idproducterp." and activo=1 order by orden");

                            $idattributes = [];
                            foreach($rowperfiles as $row){

                                $idattr = "".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$row["id_valor"]);
                                if ($idattr!=""){
                                    $idattributes[]=(int)$idattr;
                                }                                
                            }  




                            $combination = new Combination((int) $idproductattributeps);


                            $combination->reference = $data["referencia"];
                            
                            if (Validate::isEan13("".$data["ean13"])){
                                $combination->ean13 = $data["ean13"];    
                            }

                            if (Validate::isUpc("".$data["upc"])){
                                $combination->upc = $data["upc"];         
                            }


                            if (count($idattributes)>0){
                                    $combination->setAttributes($idattributes);
                            }

                            $combination->update();     

                            if (("".$data["activo"]=="0") || ("".$data["estado"]=="0")){
                                Db::getInstance()->Execute("DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute=".$idproductattributeps." AND  id_shop=1");    
                                Db::getInstance()->Execute("INSERT INTO aalv_tot_switch_attribute_disabled (id_product_attribute, id_shop) VALUES (".$idproductattributeps.",1)");
                                $combination = new Combination((int) $idproductattributeps);
                                $combination->default_on=false;
                                $combination->update();          
                            }
                            else{
                                Db::getInstance()->Execute("DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute=".$idproductattributeps." AND  id_shop=1");  
                            }


                            $proddelattr = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps);    

                            if ($proddelattr!=""){
                                $product = new Product($proddelattr);
                                $product->update(); 

                                try{
                                crearimagenproducto($proddelattr, $data["imagen_seo"], $idproductattributeps, $data["id"]);
                                } catch (Exception $e) {

                                }    

                            }    





                        }    
                    }

                






                }


                ProcesarTipoProducto($data, $idprodps);

                procesarcombinaciones($idprodps);

                procesarCFSG($idprodps);    


        }
        else{
                //borrado desactivacion


                $idproducterp = $fila;
                $idproductattributeps = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen=".$idproducterp);

                $idprodpssinatributo = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen=".$idproducterp);
  

                if ($idprodpssinatributo!=""){
                    $product = new Product($idprodpssinatributo);
                    $product->active=false;   
                    $product->update();
                }                       

                if ($idproductattributeps!=""){
                    
                    //desactivar la combinacion

                    //if ("".$data["activo"]=="0"){
                            Db::getInstance()->Execute("DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute=".$idproductattributeps." AND  id_shop=1");    
                            Db::getInstance()->Execute("INSERT INTO aalv_tot_switch_attribute_disabled (id_product_attribute, id_shop) VALUES (".$idproductattributeps.",1)");
                            $combination = new Combination((int) $idproductattributeps);
                            $combination->default_on=false;
                            $combination->update();          
                    //}

                    $proddelattr = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps);    

                    if ($proddelattr!=""){
                            $product = new Product($proddelattr);
                            $product->update(); 

                            procesarcombinaciones($proddelattr);

                            procesarCFSG($proddelattr); 
                    }    




                }                      




        }    



        } catch (Exception $e) {

                            $d = new DateTime();
                            $stdout = fopen(dirname(__FILE__).'/integracionerroresproduct.txt', 'a');
                            fwrite($stdout, $d->format("Y-m-d\TH:i:sP")." --- error ".$e->getMessage());    
                            fwrite($stdout, "\n"); 
                            fwrite($stdout, " --- error integracion ".$fila." ".$tipo);    
                            fwrite($stdout, "\n"); 
                            fclose($stdout);    


                            return 1;
            }       



        return  1;    
        





}


function ProcesarModelo($data, $fila, $tipo){
// google translate

    if ($tipo<=2){

        if (!$data){
           //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }


        $idmodelo =  $data["id"];
        $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idmodelo);

        $nombre = trim(strip_tags($data["nombre"]));
        $descripcion = base64_decode($data["descripcion"]);
        $descripcion = utf8_encode($descripcion);

        $descripciondestacado = base64_decode($data["descripcion_destacado"]);
        $descripciondestacado = utf8_encode($descripciondestacado);

        //echo "<br/>$descripciondestacado<br/>";
        //echo "<br/>nombre: ".$nombre;

        $sufixtitle = " | Comprar online | Alvarez";

        $sufixdescription = [];
        $sufixdescription[1]=" al mejor precio en Deportes Álvarez. Compra online tu equipo de caza en Álvarez y ✅ no te pierdas nuestras ofertas ✅";    
        $sufixdescription[2]=" at the best price at Alvarez's Sports. Purchase online your hunting en Álvarez y ✅ don't miss our offers ✅";    
        $sufixdescription[3]=" au meilleur prix à Álvarez Sports. Achetez votre équipe chasse en ligne à Álvarez et ✅ ne manquez pas nos offres ✅";
        $sufixdescription[4]=" ao melhor preço em Desportos Álvarez. Compre online o seu equipamento de caca em Álvarez e ✅ não perca as nossas ofertas ✅";
        $sufixdescription[5]=" zum besten Preis in Álvarez Sports. Kaufen Sie Ihr Team von jagd online in Álvarez und ✅ verpassen Sie nicht unsere Angebote ✅";
        $sufixdescription[6]=" al mejor precio en Deportes Álvarez. Compra online tu equipo de caza en Álvarez y ✅ no te pierdas nuestras ofertas ✅";

    

        if ($idprodps!=""){
            //update
            $product=new Product((int)$idprodps);

            $actualizarnombre=($product->name[1] != $nombre);
            $actualizardescripcion=($product->description[1] != $descripcion);



            $product->name[1] = $nombre;
            $product->description[1] = $descripcion;
            $product->link_rewrite[1] = Tools::link_rewrite($product->name[1]);

            $product->meta_title[1] = $nombre.$sufixtitle;    
            $product->meta_description[1] = $nombre.$sufixdescription[1];    
                      


            if ($tipo==1){
                $product->name[2] = $nombre;
                $product->description[2] = $descripcion;
                $product->link_rewrite[2] = Tools::link_rewrite($product->name[2]);
                $product->meta_title[2] = $nombre.$sufixtitle;    
                $product->meta_description[2] = $nombre.$sufixdescription[2];    

                $product->name[3] = $nombre;
                $product->description[3] = $descripcion;
                $product->link_rewrite[3] = Tools::link_rewrite($product->name[3]);
                $product->meta_title[3] = $nombre.$sufixtitle;    
                $product->meta_description[3] = $nombre.$sufixdescription[3];

                $product->name[4] = $nombre;
                $product->description[4] = $descripcion;
                $product->link_rewrite[4] = Tools::link_rewrite($product->name[4]);
                $product->meta_title[4] = $nombre.$sufixtitle;    
                $product->meta_description[4] = $nombre.$sufixdescription[4];

                $product->name[5] = $nombre;
                $product->description[5] = $descripcion;
                $product->link_rewrite[5] = Tools::link_rewrite($product->name[5]);
                $product->meta_title[5] = $nombre.$sufixtitle;    
                $product->meta_description[5] = $nombre.$sufixdescription[5];

                $product->name[6] = $nombre;
                $product->description[6] = $descripcion;
                $product->link_rewrite[6] = Tools::link_rewrite($product->name[6]);
                $product->meta_title[6] = $nombre.$sufixtitle;
                $product->meta_description[6] = $nombre.$sufixdescription[6];
            }    



            //crear marca si no existe
            $marcaExists = Manufacturer::manufacturerExists($data['id_marca']);
            if ($marcaExists) {
                
            } else {

                if ("".$data['id_marca']!=""){
                    $marca = New Manufacturer();
                    $marca->force_id = true;
                    $marca->id = $data['id_marca'];
                    $marca->name = $data['id_marca']; 
                    $marca->active = true; 
                    $marca->add();
                }
            }    

            

            if ("".$data['id_marca']!=""){
                $product->id_manufacturer = $data['id_marca'];
            }
            /* comentado google translate
            if ($actualizarnombre){
                $product->name[2] = translategoogle($nombre,"es","en");
                $product->link_rewrite[2] = Tools::link_rewrite($product->name[2]);

                $product->name[3] = translategoogle($nombre,"es","fr");
                $product->link_rewrite[3] = Tools::link_rewrite($product->name[3]);

                $product->name[4] = translategoogle($nombre,"es","pt");
                $product->link_rewrite[4] = Tools::link_rewrite($product->name[4]);

                $product->name[5] = translategoogle($nombre,"es","de");
                $product->link_rewrite[5] = Tools::link_rewrite($product->name[5]);
            }

            if ($actualizardescripcion){
                
                $product->description[2] = translategoogle($descripcion,"es","en");
                $product->description[3] = translategoogle($descripcion,"es","fr");
                $product->description[4] = translategoogle($descripcion,"es","pt");
                $product->description[5] = translategoogle($descripcion,"es","de");
                
            }
            */
            //update ver si traducciones están vacias

            
            if ("".$data["activo"]=="0"){
                $product->active=false;
            }    
            else{
                $product->active=true;
            }



            $product->update();       

            //borrarlas dos de abajo
            //$imagesurls = $data['imagen_seo'];
            //crearimagenes($product->id, $imagesurls);

            $idfeatureestadosegundamano=Feature::addFeatureImport("Estado segunda mano");
            $idfeatureprecioconsultar=Feature::addFeatureImport("Precio a consultar");
            $idfeatureventatelefono=Feature::addFeatureImport("Venta teléfono");
            $idfeaturetextoproductosnovendibles=Feature::addFeatureImport("Texto productos no vendibles");
            
                        
            $idfeatureestadosegundamanovalue=0;
            $idfeatureprecioconsultarvalue=0;
            $idfeatureventatelefonovalue=0;
            $idfeaturetextoproductosnovendiblesvalue=0;

            if (strip_tags($descripciondestacado)!=""){



                $descripciondestacado = substr(strip_tags($descripciondestacado),0,4095);
                if (strpos($descripciondestacado, ">")){
                    $descripciondestacado = strip_tags("<".$descripciondestacado);
                }


                $idfeatureestadosegundamanovalue = crearFeatureValue($idfeatureestadosegundamano, $descripciondestacado,1);


            }
            if ($idfeatureestadosegundamanovalue!=0){
                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=".$idfeatureestadosegundamano." and id_product=".$product->id);
                $product->addFeatureProductImport( $product->id, $idfeatureestadosegundamano, $idfeatureestadosegundamanovalue);
            }


            if (("".$data['precio_consultar_ficha'])==""){
                $preconsul = "0";
            }
            else{
                $preconsul =  $data['precio_consultar_ficha'];  
            }

            $idfeatureprecioconsultarvalue = crearFeatureValue($idfeatureprecioconsultar, $preconsul,0);
            if ($idfeatureprecioconsultarvalue!=0){
                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=".$idfeatureprecioconsultar." and id_product=".$product->id);
                $product->addFeatureProductImport( $product->id, $idfeatureprecioconsultar, $idfeatureprecioconsultarvalue);
            }

            if ("".$data['venta_telefono']==""){
                $data['venta_telefono']=0;
            }


            $idfeatureventatelefonovalue = crearFeatureValue($idfeatureventatelefono, $data['venta_telefono'],0);
            if ($idfeatureventatelefonovalue!=0){
                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=".$idfeatureventatelefono." and id_product=".$product->id);
                $product->addFeatureProductImport( $product->id, $idfeatureventatelefono, $idfeatureventatelefonovalue);
            }

            if ($data['texto_productos_no_vendibles']=="solo_tienda"){

                $idfeaturetextoproductosnovendiblesvalue = crearFeatureValue($idfeaturetextoproductosnovendibles, $data['texto_productos_no_vendibles'],0);
                if ($idfeaturetextoproductosnovendiblesvalue!=0){
                    Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=".$idfeaturetextoproductosnovendibles." and id_product=".$product->id);
                    $product->addFeatureProductImport( $product->id, $idfeaturetextoproductosnovendibles, $idfeaturetextoproductosnovendiblesvalue);
                }


            }


            if (($data['texto_productos_no_vendibles']!="") || ($data['precio_consultar_ficha']==1) || ($data['venta_telefono']==1)) {
                $product->available_for_order=false;
                $product->show_price=false;
                $product->update();
            }
            else{
                $product->available_for_order=true;
                $product->show_price=true;
                $product->update();
            }




            procesarcombinaciones($product->id);

            $imagesurls = $data['imagen_seo'];

            
            crearimagenmodelo($product->id, $imagesurls);


        }
        else{
            //crear
            $product=new Product();
            $product->name[1] = $nombre;
            $product->description[1] = $descripcion;
            $product->link_rewrite[1] = Tools::link_rewrite($product->name[1]);
            $product->meta_title[1] = $nombre.$sufixtitle;    
            $product->meta_description[1] = $nombre.$sufixdescription[1];

            $product->name[2] = $nombre;
            $product->description[2] = $descripcion;
            $product->link_rewrite[2] = Tools::link_rewrite($product->name[2]);
            $product->meta_title[2] = $nombre.$sufixtitle;    
            $product->meta_description[2] = $nombre.$sufixdescription[2];

            $product->name[3] = $nombre;
            $product->description[3] = $descripcion;
            $product->link_rewrite[3] = Tools::link_rewrite($product->name[3]);
            $product->meta_title[3] = $nombre.$sufixtitle;    
            $product->meta_description[3] = $nombre.$sufixdescription[3];

            $product->name[4] = $nombre;
            $product->description[4] = $descripcion;
            $product->link_rewrite[4] = Tools::link_rewrite($product->name[4]);
            $product->meta_title[4] = $nombre.$sufixtitle;    
            $product->meta_description[4] = $nombre.$sufixdescription[4];

            $product->name[5] = $nombre;
            $product->description[5] = $descripcion;
            $product->link_rewrite[5] = Tools::link_rewrite($product->name[5]);    
            $product->meta_title[5] = $nombre.$sufixtitle;    
            $product->meta_description[5] = $nombre.$sufixdescription[5];    


            /*

            solo castellano por ser creacion y posible venida datos modelo_idiomas

            $product->name[2] = translategoogle($nombre,"es","en");
            $product->description[2] = translategoogle($descripcion,"es","en");
            $product->link_rewrite[2] = Tools::link_rewrite($product->name[2]);

            $product->name[3] = translategoogle($nombre,"es","fr");
            $product->description[3] = translategoogle($descripcion,"es","fr");
            $product->link_rewrite[3] = Tools::link_rewrite($product->name[3]);

            $product->name[4] = translategoogle($nombre,"es","pt");
            $product->description[4] = translategoogle($descripcion,"es","pt");
            $product->link_rewrite[4] = Tools::link_rewrite($product->name[4]);

            $product->name[5] = translategoogle($nombre,"es","de");
            $product->description[5] = translategoogle($descripcion,"es","de");
            $product->link_rewrite[5] = Tools::link_rewrite($product->name[5]);
            */



            $product->id_tax_rules_group = 7;
            
            $product->id_category_default = 2;
            $product->id_manufacturer = $data['id_marca'];

            if ("".$data["activo"]=="0"){
                $product->active=false;
            }    
            else{
                $product->active=true;
            }




            $product->add();
            $categories[] = 2;
            $product->addToCategories($categories); 


            $sql="INSERT INTO aalv_product_import(id_product, id_modelo) VALUES (".$product->id.",".$idmodelo .")";
            Db::getInstance()->Execute($sql); 



            //$product->deleteFeatures();
            $idfeatureestadosegundamano=Feature::addFeatureImport("Estado segunda mano");
            $idfeatureprecioconsultar=Feature::addFeatureImport("Precio a consultar");
            $idfeatureventatelefono="".Feature::addFeatureImport("Venta teléfono");

           

            $idfeaturetextoproductosnovendibles=Feature::addFeatureImport("Texto productos no vendibles");
            
                        
            $idfeatureestadosegundamanovalue=0;
            $idfeatureprecioconsultarvalue=0;
            $idfeatureventatelefonovalue=0;
            $idfeaturetextoproductosnovendiblesvalue=0;


            if (strip_tags($descripciondestacado)!=""){



                $descripciondestacado = substr(strip_tags($descripciondestacado),0,4095);
                if (strpos($descripciondestacado, ">")){
                    $descripciondestacado = strip_tags("<".$descripciondestacado);
                }


                $idfeatureestadosegundamanovalue = crearFeatureValue($idfeatureestadosegundamano, $descripciondestacado,1);


            }
            if ($idfeatureestadosegundamanovalue!=0)
                $product->addFeatureProductImport( $product->id, $idfeatureestadosegundamano, $idfeatureestadosegundamanovalue);



             if (("".$data['precio_consultar_ficha'])==""){
                $preconsul = "0";
            }
            else{
                $preconsul =  $data['precio_consultar_ficha'];  
            }



            $idfeatureprecioconsultarvalue = crearFeatureValue($idfeatureprecioconsultar, $preconsul,0);
            if ($idfeatureprecioconsultarvalue!=0)
                $product->addFeatureProductImport( $product->id, $idfeatureprecioconsultar, $idfeatureprecioconsultarvalue);

            if ("".$data['venta_telefono']==""){
                $data['venta_telefono']=0;
            }
    

            $idfeatureventatelefonovalue = crearFeatureValue($idfeatureventatelefono, $data['venta_telefono'],0);
            if ($idfeatureventatelefonovalue!=0)
                $product->addFeatureProductImport( $product->id, $idfeatureventatelefono, $idfeatureventatelefonovalue);


            if ($data['texto_productos_no_vendibles']=="solo_tienda"){

                $idfeaturetextoproductosnovendiblesvalue = crearFeatureValue($idfeaturetextoproductosnovendibles, $data['texto_productos_no_vendibles'],0);
                if ($idfeaturetextoproductosnovendiblesvalue!=0){
                    Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=".$idfeaturetextoproductosnovendibles." and id_product=".$product->id);
                    $product->addFeatureProductImport( $product->id, $idfeaturetextoproductosnovendibles, $idfeaturetextoproductosnovendiblesvalue);
                }


            }

            procesarcombinaciones($product->id);


            $imagesurls = $data['imagen_seo'];

            crearimagenmodelo($product->id, $imagesurls);
            //crearimagenes($product->id, $imagesurls);



        }
        
        
        return 1;   
    }
    else{
        //borrado? mejor desactivar?
        $idmodelo =  $fila;
        $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idmodelo);
        if ($idprodps!=""){
            $product=new Product((int)$idprodps);
            $product->active = false;
            $product->update();
        }
        return 1;

    }   
}



function ProcesarTarifaCabecera($data, $fila, $tipo){

    //{"idtarifa_cabecera":101536741,"estado":true,"idarticulo":100227795,"idalmacen":null,"idregpais":1,"idimppais_fecha":3,"porc_iva":"21.000","tarifa_base":true,"tarifa_calculada":false,"importe_exento":"0.0000","finicio":"2019-08-31T00:00:00","ffin":"2020-02-05T23:59:59","idtarifa_cabecera_tcalculo":2,"idproducto":null}

try{

    if ($tipo<=2){


        if (!$data){
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }


        dump($data);






        echo "llega cabecera<br/>";

        $idtarifa_cabecera = $data["idtarifa_cabecera"];
        $idarticulo = $data["idarticulo"];

        $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo=".$idarticulo);
        $idprodattrps =  "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo=".$idarticulo);

        if ($idprodps =="") {
            if ($idprodattrps!=""){
                $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idprodattrps);
            }   
        }
        else{
            $idprodattrps = "0";    
        }



        if ($data["estado"]==false){

            $specificprices=Db::getInstance()->ExecuteS("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$idtarifa_cabecera);
            foreach($specificprices as $idsp){
                //$sp = new SpecificPrice((int)$idsp);
                //$sp->delete();
                
                Db::getInstance()->Execute("DELETE FROM aalv_specific_price WHERE id_specific_price=".$idsp["id_specific_price"]);
            }
            echo "<br>Borrarprecio</br>";
            //por si no borra, borrar con misma fecha inicio y fin 

            if ($data["idregpais"]==2){
                $codpais = 15; //portugal
            }
            else{
                $codpais = 0;
            }   

            $finicio = str_replace("T", " ", $data["finicio"]);
            $ffin = str_replace("T", " ", $data["ffin"]);

            if ("".$finicio==""){
                $finicio =  "0000-00-00 00:00:00";
            }

            if ("".$ffin==""){
                $ffin =  "0000-00-00 00:00:00";
            }

            $sql="DELETE FROM aalv_specific_price where id_country=".$codpais." and `from`='".$finicio."' and `to`='".$ffin."' and id_product=".$idprodps." and id_product_attribute=".$idprodattrps;

            echo "<br/>".$sql."<br/>";

            Db::getInstance()->Execute($sql);




            if ($idprodps !="") {
                procesarcombinaciones($idprodps);
            }
            Db::getInstance()->Execute("DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$idtarifa_cabecera);

            return 1;
        }









        $piva = (int)$data["porc_iva"];    

        $porciva = 7;        
        if ($piva == 21) {
            $porciva = 7; 
        }
        if ($piva == 10) {
            $porciva = 2; 
        }    
        if ($piva == 4) {
            $porciva = 3; 
        }    
        if ($piva == 0) {
            $porciva = 0; 
        }    

        if ($data["idregpais"]==1){
           //Db::getInstance()->getValue("update aalv_product_shop set id_tax_rules_group = ".$porciva." where id_product=".$idprodps);    
           // Db::getInstance()->getValue("update aalv_product set id_tax_rules_group = ".$porciva." where id_product=".$idprodps);  

            if ($idprodps !="") {
                //$prod = new Product($idprodps);

                //if ($prod->id_tax_rules_group!=$porciva){
                //    $prod->id_tax_rules_group = $porciva;
                //    $prod->update();
                //} 

                $id_tax_rules_group = Db::getInstance()->getValue("select id_tax_rules_group from aalv_product_shop where id_product=".$idprodps);
                if ($id_tax_rules_group!=$porciva){
                    Db::getInstance()->getValue("update aalv_product_shop set id_tax_rules_group = ".$porciva." where id_product=".$idprodps);    
                    Db::getInstance()->getValue("update aalv_product set id_tax_rules_group = ".$porciva." where id_product=".$idprodps);  
                }

            }

        }




        //ver si existe ya esa cabecera
        $specificprice="".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$idtarifa_cabecera);
        echo "SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$idtarifa_cabecera;
        echo "existe: ".$specificprice;
        if ($specificprice!=""){
                $specificprice = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                if ($specificprice!=""){
                    $tipo=2;
                }
        }
        else{
            $tipo=1;
        }       

        echo "tipo: ".$tipo;


        if ($tipo==2){//update

            if ($idprodps!="") {
                //ver si existe precio específico para la tarifa cabecera
                $specificprices=Db::getInstance()->ExecuteS("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$idtarifa_cabecera);

                foreach($specificprices as $idsp){


                    $existesp = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$idsp["id_specific_price"]);

                    if ($existesp!=""){

                        if ($data["idregpais"]==2){
                            $codpais = 15; //portugal
                        }
                        else{
                            $codpais = 0;
                        }   

                        $finicio = str_replace("T", " ", $data["finicio"]);
                        $ffin = str_replace("T", " ", $data["ffin"]);

                        if ("".$finicio==""){
                            $finicio =  "0000-00-00 00:00:00";
                        }

                        if ("".$ffin==""){
                            $ffin =  "0000-00-00 00:00:00";
                        }

                        $sql="UPDATE aalv_specific_price SET id_country=".$codpais.",`from`='".$finicio."',`to`='".$ffin."' WHERE id_specific_price=".$idsp["id_specific_price"];
                        
                        echo $sql;

                        Db::getInstance()->Execute($sql);

                        /*
                        $sp = new SpecificPrice((int)$idsp["id_specific_price"]);
                        


                        $sp->id_product = $idprodps;
                        $sp->id_product_attribute = $idprodattrps;
                        $sp->id_shop = 0;
                        $sp->id_group = 0;
                        $sp->id_customer = 0;
                        $sp->id_shop_group = 0;
                        $sp->id_currency = 0;    


                        if ($data["idregpais"]==2){
                            $sp->id_country = 15; //portugal
                        }
                        else{
                            $sp->id_country = 0;
                        }   


                        $finicio = str_replace("T", " ", $data["finicio"]);
                        $ffin = str_replace("T", " ", $data["ffin"]);

                        if ("".$finicio==""){
                            $finicio =  "0000-00-00 00:00:00";
                        }

                        if ("".$ffin==""){
                            $ffin =  "0000-00-00 00:00:00";
                        }

                        
                        $sp->from = $finicio;
                        $sp->to = $ffin;
                        
                        $sp->update();
                        */



                    }
                }


            }
            else{
                //correo de creacion de tarifa sin producto
                sendmail2("Viene tarifa cabecera ".$idtarifa_cabecera." antes de la creacion del articulo de id ".$idarticulo); 
            }

        }
        else{//insert ¿que hacemos? no tenemos el precio, que va en las lineas ¿tabla auxiliar?

            if ($idprodps!="") {    
            echo "llega cabecera 2<br/>";
            $pais=0;
            if ($data["idregpais"]==2){
                $pais = 15; //portugal
            }
            $finicio = str_replace("T", " ", $data["finicio"]);
            $ffin = str_replace("T", " ", $data["ffin"]);

            if ("".$finicio==""){
                $finicio =  "0000-00-00 00:00:00";
            }

            if ("".$ffin==""){
                $ffin =  "0000-00-00 00:00:00";
            }

            echo "llega cabecera 3<br/>";

            $sql="REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute) VALUES (".$idtarifa_cabecera.",".$pais.",'".$finicio."','".$ffin."',".$idprodps.",".$idprodattrps.")";
            echo $sql."<br/>";
            $res=Db::getInstance()->Execute("REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute) VALUES (".$idtarifa_cabecera.",".$pais.",'".$finicio."','".$ffin."',".$idprodps.",".$idprodattrps.")");
            echo "llega cabecera 4<br/>";
            echo $res;
            }    
            else{
                //correo de creacion de tarifa sin producto
                sendmail2("Viene tarifa cabecera ".$idtarifa_cabecera." antes de la creacion del articulo de id ".$idarticulo); 
            }
        }

        if ($idprodps !="") {
            procesarcombinaciones($idprodps);
        }

        
        return 1;   

    }
    else{
        //borrado
        $specificprices=Db::getInstance()->ExecuteS("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$fila);
        foreach($specificprices as $idsp){
            //$sp = new SpecificPrice((int)$idsp);
            //$idp = $sp->id_product;
            //$sp->delete();
            $idp = Db::getInstance()->getValue("select id_product from  aalv_specific_price WHERE id_specific_price=".$idsp["id_specific_price"]);
            Db::getInstance()->Execute("DELETE FROM aalv_specific_price WHERE id_specific_price=".$idsp["id_specific_price"]);
        }

        if ($idp !="") {
            procesarcombinaciones($idp);
        }
        Db::getInstance()->Execute("DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$fila);
        return 1;
    }   

 } catch (Exception $e) {
                return 1;
 }   

}


function ProcesarTarifaLinea($data, $fila, $tipo){

    //{"idtarifa_linea":101539304,"estado":true,"idtarifa_cabecera":101536741,"udesde":"1.0000","baseimp":"1.6529","pvp":"2.0000","pvp_exento":"1.9500","dto":"0.00","mostrar_dto":false,"motivo_dto":"","pvp_anterior":null,"baseimp_anterior":null,"pvp_exento_anterior":null,"genera_puntos_fid":true,"aplicar_ofertas":true}

try{
        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            dump($data);

            if ($data["estado"]==false){

                $specificprice="".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea=".$fila);

                if ($specificprice!=""){
                    $specificprice = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                    if ($specificprice!=""){
                        //$sp = new SpecificPrice((int)$specificprice);
                        //$idp = $sp->id_product;
                        //$sp->delete();
                        $idp = Db::getInstance()->getValue("select id_product from  aalv_specific_price WHERE id_specific_price=".$specificprice);
                        Db::getInstance()->Execute("DELETE FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                        procesarcombinaciones($idp);
                    }
                }

                Db::getInstance()->Execute("DELETE FROM aalv_specific_price_import WHERE id_tarifa_linea=".$fila);






                return 1;

            }


            //ver si existe ya esa linea
            $specificprice="".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea=".$fila);
            if ($specificprice!=""){
                    $specificprice = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                    if ($specificprice!=""){
                        $tipo=2;
                    }                    
            }
            else{
                $tipo=1;
            }            





            if ($tipo==2){//update

                $specificprice="".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea=".$fila);
                if ($specificprice!=""){


                     
                    $specificprice = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                    

                    if ($specificprice!=""){

                        /*

                        $sp = new SpecificPrice((int)$specificprice);
                        
                        $sp->id_shop = 0;
                        $sp->id_group = 0;
                        $sp->id_customer = 0;
                        $sp->id_shop_group = 0;
                        $sp->id_currency = 0;  

                        if ($data["baseimp_anterior"]){

                            if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                    if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                        $sp->price = round($data["baseimp_anterior"],6);
                                        $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                    }
                                    else{
                                        $sp->price = round($data["baseimp"],6);
                                        $sp->reduction = 0;
                                    }
                            }
                            else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                            }
                        }
                        else{
                            $sp->price = round($data["baseimp"],6);
                            $sp->reduction = 0;
                        }
                        
                        $sp->from_quantity = (int)$data["udesde"];
                        
                        $sp->reduction_tax = 0;
                        $sp->reduction_type = "amount";
                        
                        $sp->update();

                        */

                        if ($data["baseimp_anterior"]){

                            if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                    //if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) ||  ((1-($data["baseimp"]/$data["baseimp_anterior"]))>0.1)) {    
                                        $miprice = round($data["baseimp_anterior"],6);
                                        $mireduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                    }
                                    else{
                                        $miprice = round($data["baseimp"],6);
                                        $mireduction = 0;
                                    }
                            }
                            else{
                                    $miprice = round($data["baseimp"],6);
                                    $mireduction = 0;
                            }
                        }
                        else{
                            $miprice = round($data["baseimp"],6);
                            $mireduction = 0;
                        }


                        $sql="UPDATE aalv_specific_price SET price=".$miprice.",from_quantity=".$data["udesde"].",reduction=".$mireduction.",reduction_tax=0,reduction_type='amount' WHERE id_specific_price=".$specificprice;
                        Db::getInstance()->Execute($sql);

                        $midproduct = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                        if ($midproduct!=""){
                            procesarcombinaciones($midproduct);    
                        }

                        




                    }
                    else{
                        //como si fuera tipo 1


                            //insert, esperemos que hayan enviado antes la cabecera

                        //ver si existe cabecera
                        $idtarifa_cabecera = $data["idtarifa_cabecera"];

                        $existecabecera = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$idtarifa_cabecera);

                        if ($existecabecera!=""){
                            $existecabecera = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                        }

                        if ($existecabecera!=""){

                            $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                            $idprodattr="".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                            $finicio="".Db::getInstance()->getValue("SELECT `from` FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                            $ffin="".Db::getInstance()->getValue("SELECT `to` FROM aalv_specific_price WHERE id_specific_price=".$existecabecera); 
                            $country="".Db::getInstance()->getValue("SELECT id_country FROM aalv_specific_price WHERE id_specific_price=".$existecabecera); 

                            if ($data["baseimp_anterior"]){

                                if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                        //if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                        if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) ||  ((1-($data["baseimp"]/$data["baseimp_anterior"]))>0.1)) {    
                                            $miprice = round($data["baseimp_anterior"],6);
                                            $mireduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                        }
                                        else{
                                            $miprice = round($data["baseimp"],6);
                                            $mireduction = 0;
                                        }
                                }
                                else{
                                        $miprice = round($data["baseimp"],6);
                                        $mireduction = 0;
                                }
                            }
                            else{
                                $miprice = round($data["baseimp"],6);
                                $mireduction = 0;
                            }
                    

                            $sql = "INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,".$idproduct.",0,0,0,".$country.",0,0,".$idprodattr.",".$miprice.",".$data["udesde"].",".$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                            Db::getInstance()->Execute($sql);

                            $idnewsp = Db::getInstance()->Insert_ID();


                            Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$idnewsp.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");

                            procesarcombinaciones($idproduct);


                            $productact = new Product((int)$idproduct);
                            $productact->update();



                            /*

                            //coger lo que manda en cabecera: producto, idattr, pais, inicio,fin
                            $sp = new SpecificPrice((int)$existecabecera);
                            $idproduct=$sp->id_product;
                            $idprodattr=$sp->id_product_attribute;
                            $finicio=$sp->from;
                            $ffin=$sp->to;   
                            $country=$sp->id_country;

                            $sp = new SpecificPrice(); 
                            $sp->id_shop = 0;
                            $sp->id_group = 0;
                            $sp->id_customer = 0;
                            $sp->id_shop_group = 0;
                            $sp->id_currency = 0;  
                        
                    
                    
                            $sp->id_product=$idproduct;
                            $sp->id_product_attribute=$idprodattr;
                            $sp->from=$finicio;
                            $sp->to=$ffin;   
                            $sp->id_country=$country;


                            if ($data["baseimp_anterior"]){

                                if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                        if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                            $sp->price = round($data["baseimp_anterior"],6);
                                            $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                        }
                                        else{
                                            $sp->price = round($data["baseimp"],6);
                                            $sp->reduction = 0;
                                        }
                                }
                                else{
                                        $sp->price = round($data["baseimp"],6);
                                        $sp->reduction = 0;
                                }
                            }
                            else{
                                $sp->price = round($data["baseimp"],6);
                                $sp->reduction = 0;
                            }
                    
                    
                            $sp->from_quantity = (int)$data["udesde"];
                            
                            $sp->reduction_tax = 0;
                            $sp->reduction_type = "amount";
                            $sp->add();
                            

                            Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");

                            procesarcombinaciones($sp->id_product);
                            */
                        }
                        else{
                            //coger de nueva tabla auxiliar

                            $existeauxiliar = "".Db::getInstance()->getValue("SELECT id_tarifa_cabecera FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);

                            if ($existeauxiliar!=""){


                                $idproduct=Db::getInstance()->getValue("SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $idprodattr=Db::getInstance()->getValue("SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $finicio=Db::getInstance()->getValue("SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $ffin=Db::getInstance()->getValue("SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]); 
                                $country=Db::getInstance()->getValue("SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]); 

                                if ($data["baseimp_anterior"]){

                                    if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                            //if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                            if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) ||  ((1-($data["baseimp"]/$data["baseimp_anterior"]))>0.1)) {            
                                                $miprice = round($data["baseimp_anterior"],6);
                                                $mireduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                            }
                                            else{
                                                $miprice = round($data["baseimp"],6);
                                                $mireduction = 0;
                                            }
                                    }
                                    else{
                                            $miprice = round($data["baseimp"],6);
                                            $mireduction = 0;
                                    }
                                }
                                else{
                                    $miprice = round($data["baseimp"],6);
                                    $mireduction = 0;
                                }
                        

                                $sql = "INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,".$idproduct.",0,0,0,".$country.",0,0,".$idprodattr.",".$miprice.",".$data["udesde"].",".$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                                Db::getInstance()->Execute($sql);

                                $idnewsp = Db::getInstance()->Insert_ID();
                                Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$idnewsp.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");


                                procesarcombinaciones($idproduct);
                                $productact = new Product((int)$idproduct);
                                $productact->update();


                                /*
                                $sp = new SpecificPrice(); 
                                $sp->id_shop = 0;
                                $sp->id_group = 0;
                                $sp->id_customer = 0;
                                $sp->id_shop_group = 0;
                                $sp->id_currency = 0;  
                                



                                $sp->id_product=Db::getInstance()->getValue("SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $sp->id_product_attribute=Db::getInstance()->getValue("SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $sp->from=Db::getInstance()->getValue("SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $sp->to=Db::getInstance()->getValue("SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);   
                                $sp->id_country=Db::getInstance()->getValue("SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);   


                                if ($data["baseimp_anterior"]){

                                    if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                        if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                            $sp->price = round($data["baseimp_anterior"],6);
                                            $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                        }
                                        else{
                                            $sp->price = round($data["baseimp"],6);
                                            $sp->reduction = 0;
                                        }
                                    }
                                    else{
                                            $sp->price = round($data["baseimp"],6);
                                            $sp->reduction = 0;
                                    }
                                }
                                else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                                }
                                
                                
                                $sp->from_quantity = (int)$data["udesde"];
                                
                                $sp->reduction_tax = 0;
                                $sp->reduction_type = "amount";
                                $sp->add();


                                Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");


                                procesarcombinaciones($sp->id_product);

                                */

                            }
                        }    




















                        }


                    
                }        

            }
            else 
            { 
                //insert, esperemos que hayan enviado antes la cabecera

                //ver si existe cabecera
                $idtarifa_cabecera = $data["idtarifa_cabecera"];

                $existecabecera = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera=".$idtarifa_cabecera);

                if ($existecabecera!=""){
                    $existecabecera = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                }

                if ($existecabecera!=""){

                    $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                    $idprodattr="".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                    $finicio="".Db::getInstance()->getValue("SELECT `from` FROM aalv_specific_price WHERE id_specific_price=".$existecabecera);
                    $ffin="".Db::getInstance()->getValue("SELECT `to` FROM aalv_specific_price WHERE id_specific_price=".$existecabecera); 
                    $country="".Db::getInstance()->getValue("SELECT id_country FROM aalv_specific_price WHERE id_specific_price=".$existecabecera); 

                    if ($data["baseimp_anterior"]){

                        if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                //if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) ||  ((1-($data["baseimp"]/$data["baseimp_anterior"]))>0.1)) {        
                                    $miprice = round($data["baseimp_anterior"],6);
                                    $mireduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                }
                                else{
                                    $miprice = round($data["baseimp"],6);
                                    $mireduction = 0;
                                }
                        }
                        else{
                                $miprice = round($data["baseimp"],6);
                                $mireduction = 0;
                        }
                    }
                    else{
                        $miprice = round($data["baseimp"],6);
                        $mireduction = 0;
                    }
            

                    $sql = "INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,".$idproduct.",0,0,0,".$country.",0,0,".$idprodattr.",".$miprice.",".$data["udesde"].",".$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                    Db::getInstance()->Execute($sql);

                    $idnewsp = Db::getInstance()->Insert_ID();


                    Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$idnewsp.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");

                    procesarcombinaciones($idproduct);

                    $productact = new Product((int)$idproduct);
                    $productact->update();




                    /*

                    //coger lo que manda en cabecera: producto, idattr, pais, inicio,fin
                    $sp = new SpecificPrice((int)$existecabecera);
                    $idproduct=$sp->id_product;
                    $idprodattr=$sp->id_product_attribute;
                    $finicio=$sp->from;
                    $ffin=$sp->to;   
                    $country=$sp->id_country;

                    $sp = new SpecificPrice(); 
                    $sp->id_shop = 0;
                    $sp->id_group = 0;
                    $sp->id_customer = 0;
                    $sp->id_shop_group = 0;
                    $sp->id_currency = 0;  
                
                    
                    
                    $sp->id_product=$idproduct;
                    $sp->id_product_attribute=$idprodattr;
                    $sp->from=$finicio;
                    $sp->to=$ffin;   
                    $sp->id_country=$country;


                    if ($data["baseimp_anterior"]){

                        if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    $sp->price = round($data["baseimp_anterior"],6);
                                    $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                }
                                else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                                }
                        }
                        else{
                                $sp->price = round($data["baseimp"],6);
                                $sp->reduction = 0;
                        }
                    }
                    else{
                        $sp->price = round($data["baseimp"],6);
                        $sp->reduction = 0;
                    }
                    
                    
                    $sp->from_quantity = (int)$data["udesde"];
                    
                    $sp->reduction_tax = 0;
                    $sp->reduction_type = "amount";
                    $sp->add();


                    Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");
                    procesarcombinaciones($sp->id_product);

                    */


                }
                else{
                    //coger de nueva tabla auxiliar

                    $existeauxiliar = "".Db::getInstance()->getValue("SELECT id_tarifa_cabecera FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);

                    if ($existeauxiliar!=""){

                        $idproduct=Db::getInstance()->getValue("SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $idprodattr=Db::getInstance()->getValue("SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $finicio=Db::getInstance()->getValue("SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $ffin=Db::getInstance()->getValue("SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]); 
                        $country=Db::getInstance()->getValue("SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]); 

                        if ($data["baseimp_anterior"]){

                            if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                    //if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) ||  ((1-($data["baseimp"]/$data["baseimp_anterior"]))>0.1)) {        
                                        $miprice = round($data["baseimp_anterior"],6);
                                        $mireduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                    }
                                    else{
                                        $miprice = round($data["baseimp"],6);
                                        $mireduction = 0;
                                    }
                            }
                            else{
                                    $miprice = round($data["baseimp"],6);
                                    $mireduction = 0;
                            }
                        }
                        else{
                            $miprice = round($data["baseimp"],6);
                            $mireduction = 0;
                        }
                

                        $sql = "INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,".$idproduct.",0,0,0,".$country.",0,0,".$idprodattr.",".$miprice.",".$data["udesde"].",".$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                        Db::getInstance()->Execute($sql);

                        $idnewsp = Db::getInstance()->Insert_ID();
                        Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$idnewsp.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");


                        procesarcombinaciones($idproduct);

                        $productact = new Product((int)$idproduct);
                        $productact->update();




                        /*
                        $sp = new SpecificPrice(); 
                        $sp->id_shop = 0;
                        $sp->id_group = 0;
                        $sp->id_customer = 0;
                        $sp->id_shop_group = 0;
                        $sp->id_currency = 0;  
                        
                        $sp->id_product=Db::getInstance()->getValue("SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $sp->id_product_attribute=Db::getInstance()->getValue("SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $sp->from=Db::getInstance()->getValue("SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $sp->to=Db::getInstance()->getValue("SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);   
                        $sp->id_country=Db::getInstance()->getValue("SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);   


                        if ($data["baseimp_anterior"]){

                            if ($data["baseimp_anterior"]>$data["baseimp"]){      

                                if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    $sp->price = round($data["baseimp_anterior"],6);
                                    $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6); 
                                }
                                else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                                }
                            }
                            else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                            }
                        }
                        else{
                            $sp->price = round($data["baseimp"],6);
                            $sp->reduction = 0;
                        }
                        
                        
                        $sp->from_quantity = (int)$data["udesde"];
                        
                        $sp->reduction_tax = 0;
                        $sp->reduction_type = "amount";
                        $sp->add();


                        Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");


                        procesarcombinaciones($sp->id_product);
                        
                        */


                    }




                }




            }   



        }
        else{//borrado

            $specificprice="".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea=".$fila);
            if ($specificprice!=""){
                $specificprice = "".Db::getInstance()->getValue("SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                if ($specificprice!=""){
                    //$sp = new SpecificPrice((int)$specificprice);
                    //$idp = $sp->id_product;
                    //$sp->delete();
                    //procesarcombinaciones($idp);
                    $idp = Db::getInstance()->getValue("select id_product from  aalv_specific_price WHERE id_specific_price=".$specificprice);
                    Db::getInstance()->Execute("DELETE FROM aalv_specific_price WHERE id_specific_price=".$specificprice);
                    procesarcombinaciones($idp);
                }    
            }
            Db::getInstance()->Execute("DELETE FROM aalv_specific_price_import WHERE id_tarifa_linea=".$fila);

        }   

        
        return 1;   
} catch (Exception $e) {
                return 1;
 }   

}


function ProcesarModDocumento($doc, $fila, $tipo){


    if ($tipo<=2){

        if (!$doc){
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }


        $id_attachment="".Db::getInstance()->getValue("SELECT id_attachment FROM aalv_attachment_import WHERE id_origen=".$doc["id"]);

        if ($id_attachment==""){

            $url= "http://docs.a-alvarez.com/".trim($doc["contenido"]);
            Tools::copy($url, _PS_UPLOAD_DIR_.trim($doc["contenido"]));

            //Db::getInstance()->Execute("INSERT INTO aalv_ayudas(titulo, texto, enlace, activo, idorigen) VALUES ('".$titulo."','".$texto."','".$enlace."',".$activo.",".$ayuda["id"].")");
            
            $attach = new AttachmentCore();
            $attach->name[1]=substr($doc["titulo"],0,32);
            $attach->description[1]=$doc["idioma"];
            $attach->mime=get_mime_type(trim($doc["contenido"]));
            $attach->file_name=trim($doc["contenido"]);
            $attach->file_size=filesize(_PS_UPLOAD_DIR_.trim($doc["contenido"]));
            $uniqid=sha1(microtime());
            Tools::copy(_PS_UPLOAD_DIR_.trim($doc["contenido"]),_PS_DOWNLOAD_DIR_.$uniqid);
            unlink(_PS_UPLOAD_DIR_.trim($doc["contenido"]));
            $attach->file=$uniqid;
            $attach->add();

            $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$doc["id_modelo"]);
            if ($idproduct==""){
                $idproduct="0";
            }
            else{
                $attach->attachProduct($idproduct);
            }


            Db::getInstance()->Execute("INSERT INTO aalv_attachment_import(id_attachment, id_origen, id_product) VALUES (".$attach->id.",".$doc["id"].",".$idproduct.")");

        }
        else{
            $attach = new AttachmentCore((int)$id_attachment);
            $attach->name[1]=substr($doc["titulo"],0,32);
            $attach->description[1]=$doc["idioma"];
            $attach->mime=get_mime_type(trim($doc["contenido"]));
            $attach->file_name=trim($doc["contenido"]);
            $attach->file_size=filesize(_PS_UPLOAD_DIR_.trim($doc["contenido"]));
            $uniqid=sha1(microtime());
            Tools::copy(_PS_UPLOAD_DIR_.trim($doc["contenido"]),_PS_DOWNLOAD_DIR_.$uniqid);
            unlink(_PS_UPLOAD_DIR_.trim($doc["contenido"]));
            $attach->file=$uniqid;
            $attach->update();
            $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$doc["id_modelo"]);
            if ($idproduct==""){
                $idproduct="0";
            }
            else{
                $attach->attachProduct($idproduct);
            }

            Db::getInstance()->Execute("update aalv_attachment_import set id_product=".$idproduct." where id_attachment=".$attach->id);

        }

        
           
    }
    else{
        //borrado? mejor desactivar?
        Db::getInstance()->Execute("delete from aalv_attachment_import where id_origen=".$fila);
    }   

    return 1;
}

function ProcesarAyudas($data, $fila, $tipo){


        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            
            $idayuda=$data["id"];
            $idayudaps="".Db::getInstance()->getValue("SELECT id FROM aalv_ayudas WHERE idorigen=".$idayuda);   
            if ($idayudaps!=""){

                $descripcion = base64_decode($data["texto"]);
                $descripcion = utf8_encode($descripcion);

                $tactivo=$data["activo"];
                $activo = "1";
                if ($tactivo=="no"){
                    $activo = "0";
                }

                Db::getInstance()->Execute("UPDATE aalv_ayudas SET titulo='".$data["titulo"]."',texto='".$descripcion."',enlace='".$data["enlace"]."',activo=".$activo." WHERE id=".$idayudaps);               
                
            }   
            else{
                
                $descripcion = base64_decode($data["texto"]);
                $descripcion = utf8_encode($descripcion);

                $tactivo=$data["activo"];
                $activo = "1";
                if ($tactivo=="no"){
                    $activo = "0";
                }

                Db::getInstance()->Execute("INSERT INTO aalv_ayudas(titulo, texto, enlace, activo, idorigen) VALUES ('".$data["titulo"]."','".$descripcion."','".$data["enlace"]."',".$activo.",".$idayuda.")");      



            }   
        }
        else{
            //borraddo
            Db::getInstance()->Execute("DELETE FROM aalv_ayudas WHERE idorigen=".$fila);
        }

        return 1;
        
}


function ProcesarTagTemporal($data, $fila, $tipo){


        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }


            //{"idarticulo":100227415,"codigo":"G1KJ00001-39","etiqueta":"COMPARADOR, COMPARADOR_PORTUGAL, GOOGLE_ES, GOOGLE_PT"}

            $idarticulo=$data["idarticulo"];
            Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import SET etiqueta='".$data["etiqueta"]."' WHERE id_articulo=".$idarticulo);
            Db::getInstance()->Execute("UPDATE aalv_combinaciones_import SET etiqueta='".$data["etiqueta"]."' WHERE id_articulo=".$idarticulo);


            return 1;       
        }
        else{
            return 1;
        }
        
        
        
}


function ProcesarModeloIdioma($data, $fila, $tipo){


//{"id":73836,"id_modelo":14041,"nombre":"CZ CONVERSION KIT FOR .22 GAUGE","descripcion":"","destacar_nombre":"","descripcion_destacado":"","idioma":"en","imagen":"","seo_title":"","seo_metadescriptions":""}


        if ($tipo<=2){
            
            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }


            $idmodelo=$data["id_modelo"];
            
            $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idmodelo);

            $nombre = strip_tags($data["nombre"]);
            $descripcion = base64_decode($data["descripcion"]);
            $descripcion = utf8_encode($descripcion);

            echo "<br/>nombre id: ".$nombre;

            $sufixtitle = " | Comprar online | Alvarez";

            $sufixdescription = [];
            $sufixdescription[1]=" al mejor precio en Deportes Álvarez. Compra online tu equipo de caza en Álvarez y ✅ no te pierdas nuestras ofertas ✅";    
            $sufixdescription[2]=" at the best price at Alvarez's Sports. Purchase online your hunting en Álvarez y ✅ don't miss our offers ✅";    
            $sufixdescription[3]=" au meilleur prix à Álvarez Sports. Achetez votre équipe chasse en ligne à Álvarez et ✅ ne manquez pas nos offres ✅";
            $sufixdescription[4]=" ao melhor preço em Desportos Álvarez. Compre online o seu equipamento de caca em Álvarez e ✅ não perca as nossas ofertas ✅";
            $sufixdescription[5]=" zum besten Preis in Álvarez Sports. Kaufen Sie Ihr Team von jagd online in Álvarez und ✅ verpassen Sie nicht unsere Angebote ✅";


            if ($idprodps!=""){
                //update
                $product=new Product((int)$idprodps);

                $idioma=$data["idioma"];
                $codidioma=getidioma($idioma);
                
                $product->name[$codidioma] = $nombre;
                $product->description[$codidioma] = $descripcion;
                $product->link_rewrite[$codidioma] = Tools::link_rewrite($product->name[$codidioma]);
                
                $product->meta_title[$codidioma] = $nombre.$sufixtitle;    
                $product->meta_description[$codidioma] = $nombre.$sufixdescription[$codidioma];    
                    



                $product->update();       


            }



            return 1;       
        }
        else{
            return 1;
        }
        
        
        
}


function ProcesarValoresNavIdioma($data, $fila, $tipo){


//{"id":100006012,"id_valor":100006187,"nombre":"Gel Hidroalco\u00f3lico","idioma":"pt","seo_title":"","seo_metadescriptions":"","seo_texto_superior":"","seo_texto_inferior":""}


        if ($tipo<=2){
            
            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }



            $idorigen=$data["id_valor"];
            
            $idcatsps = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_origen=".$idorigen);

            $nombre = $data["nombre"];
            $idioma=$data["idioma"];
            $codidioma=getidioma($idioma);

            foreach($idcatsps as $cat){
                $category=new Category((int)$cat["id_cat"]);
                $category->name[$codidioma] = $nombre;
                $category->link_rewrite[$codidioma] = safeName($category->name[$codidioma]);
                $category->update(); 
            }

            return 1;       
        }
        else{
            return 1;
        }
        
        
        
}

function ProcesarValoresNav($data, $fila, $tipo){


//{"id":37,"nombre":"Jugador de Golf","seo_title":"","seo_metadescriptions":"","seo_texto_superior":"","seo_texto_inferior":""}


        if ($tipo<=2){
            
            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }



            $idorigen=$data["id"];
            $idcatsps = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_origen=".$idorigen);
            $nombre = $data["nombre"];
            $codidioma=1;

            foreach($idcatsps as $cat){
                $category=new Category((int)$cat["id_cat"]);
                $category->name[$codidioma] = $nombre;
                $category->link_rewrite[$codidioma] = safeName($category->name[$codidioma]);
                $category->update(); 
            }

            $existe = "".Db::getInstance()->getValue("select id_origen from aalv_valores_nav_import where id_origen=".$data["id"]);
            if ($existe==""){
                    Db::getInstance()->ExecuteS("INSERT INTO aalv_valores_nav_import(id_origen, nombre) VALUES (".$data["id"].",'".$data["nombre"]."')");
            }    
            else{
                    Db::getInstance()->ExecuteS("UPDATE aalv_valores_nav_import SET nombre='".$data["nombre"]."' WHERE id_origen=".$existe);        
            }    


            return 1;       
        }
        else{
            return 1;
        }
        
}

function ProcesarValoresProd($data, $fila, $tipo){


//{"id":100010136,"nombre":"3 Palos","id_caracteristica":12}


        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }


            
            if ($data){
                $idorigen=$data["id"];

                $idattributeps="".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$idorigen);

                if ($idattributeps!=""){

                    $attribute = new Attribute((int)$idattributeps);
                    $attribute->name[1] = $data['nombre'];
                    $attribute->update();

                }
                else{
                    $idcaracteristica=$data["id_caracteristica"];
                    $idattributegroupps="".Db::getInstance()->getValue("SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen=".$idcaracteristica);

                    if ($idattributegroupps!=""){

                        $attribute= new Attribute();
                        $attribute->id_attribute_group=$idattributegroupps;
                        $attribute->name[1] = $data['nombre'];
                        $attribute->name[2] = $data['nombre'];
                        $attribute->name[3] = $data['nombre'];
                        $attribute->name[4] = $data['nombre'];
                        $attribute->name[5] = $data['nombre'];
                        $attribute->add();
            
                        Db::getInstance()->Execute("INSERT INTO aalv_attribute_import(id_attribute, id_origen, grupo) VALUES (".$attribute->id.",".$idorigen.",'')");
                    }


                }
            }

            return 1;       
        }
        else{
            return 1;
        }
        
}



function getCategorypath($id_category) {
        $category_path = '';

        if ((int) $id_category != (int) Configuration::get('PS_HOME_CATEGORY') && (int) $id_category != 0) {
            $sql = 'SELECT c.`id_category`, c.`id_parent`, cl.`name`, c.`active` 
                    FROM `'._DB_PREFIX_.'category` c 
                    INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON cs.`id_category`=c.`id_category` AND cs.`id_shop`='.Context::getContext()->shop->id.' 
                    INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON cl.`id_category`=c.`id_category` AND cl.`id_shop`='.Context::getContext()->shop->id.' AND cl.`id_lang`='.Context::getContext()->language->id.' 
                    WHERE c.`id_category`='.(int) $id_category;
            $category = Db::getInstance()->getRow($sql);
            if ($category) {
                if ((int) $category['id_parent'] != (int) Configuration::get('PS_HOME_CATEGORY') && (int) $category['id_parent'] != 0 && (int) $category['active'] == 1) {
                    $category_path .= getCategorypath($category['id_parent']);
                }

                $category_path .= '('.$category['id_category'].') '.$category['name'].' / ';
            }
        }

        return $category_path;
    }


function ProcesarValoresNavegacion($data, $fila, $tipo){


//{"id":100004957,"id_padre":342,"elemento":100003336,"orden":null,"url":"","imagen":"","descripcion":""}

        if ($tipo<=2){
            

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }



            $idnav=$data["id"];
            $idpadre=$data["id_padre"];
            $elemento=$data["elemento"];



            //ver si existe idnav en la tabla

            $catps="".Db::getInstance()->getValue("SELECT id_cat FROM aalv_category_import WHERE id_nav=".$idnav);            
            if ($catps==""){

                //crear categoria: problema si el elemento no existe. Por ahora ver en la tabla category_import
                $catidparanombreps="".Db::getInstance()->getValue("SELECT id_cat FROM aalv_category_import WHERE id_origen=".$elemento);

                if ($catidparanombreps!=""){
                    $catparanombre=new Category((int)$catidparanombreps);




                    $catpadres = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_nav=".$idpadre); 

                    foreach ($catpadres as $catpadreitem) {

                        $category = new Category();
                        $category->id_parent = $catpadreitem["id_cat"];
                        
                        $category->active = 1;
            
                        $category->id_shop_default = 1;
                        $category->name=$catparanombre->name;
            
                        $category->meta_title = $catparanombre->meta_title;
            
                        $category->link_rewrite = $catparanombre->link_rewrite;
           
                        $category->add();
                        $category->addGroupsIfNoExist(1);
                        $category->addGroupsIfNoExist(2);
                        $category->addGroupsIfNoExist(3);
                        $orden = "".$data['orden'];
                        if ($orden==""){
                            $orden="0";
                        } 

                        $sql="INSERT INTO aalv_category_import(id_cat, id_origen, id_padre, url, orden, id_nav) VALUES (".$category->id.",".$data['elemento'].",".$data['id_padre'].",'".$data['url']."',".$orden.",".$idnav.")";
                        Db::getInstance()->Execute($sql); 

                        //avisar creacion categoria por tema google merchant  

                            $category_path = getCategorypath($category->id_parent);

                            $category_path .= '('.$category->id.') '.$category->name[1];

                            // send mail
                            $mail_to = Configuration::get('GMC_EMAIL_TO');
                            if (!$mail_to || empty($mail_to)) {
                                    $mail_to = Configuration::get('PS_SHOP_EMAIL'); //'desarrollo@addis.es';
                            }

                            $dest = [];
                            $dest[] = $mail_to;
                            $dest[] = "pferrando@addis.es";
    


                            $datamail = [
                            '{category_path}' => $category_path,
                            ];

                            $mail_template = 'new_category_add';

                            Mail::Send(
                                Context::getContext()->language->id,
                                $mail_template,
                                'Creación de nueva categoría #'.$category->id,
                                $datamail,
                                $dest,
                                null,
                                null,
                                null,
                                null,
                                null,
                                _PS_MODULE_DIR_.'gmerchantcenter/mails/',
                                false,              
                                Context::getContext()->shop->id
                            );     







                    }    



                }
                else{
                    //recogerla desde valores_nav

                    $nombrecat = "".Db::getInstance()->getValue("select nombre from  aalv_valores_nav_import where id_origen=".$elemento);    

                    if ($nombrecat!=""){    


                        $catpadres = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_nav=".$idpadre); 

                        foreach ($catpadres as $catpadreitem) {

                            $category = new Category();
                            $category->id_parent = $catpadreitem["id_cat"];
                            
                            $category->active = 1;
                
                            $category->id_shop_default = 1;
                            $category->name[1]=$nombrecat;
                
                            //$category->meta_title = $catparanombre->meta_title;
                
                            //$category->link_rewrite = $catparanombre->link_rewrite;
                            $category->link_rewrite[1] = safeName($category->name[1]);
               
                            $category->add();
                            $category->addGroupsIfNoExist(1);
                            $category->addGroupsIfNoExist(2);
                            $category->addGroupsIfNoExist(3);
                            $orden = "".$data['orden'];
                            if ($orden==""){
                                $orden="0";
                            } 

                            $sql="INSERT INTO aalv_category_import(id_cat, id_origen, id_padre, url, orden, id_nav) VALUES (".$category->id.",".$data['elemento'].",".$data['id_padre'].",'".$data['url']."',".$orden.",".$idnav.")";
                            Db::getInstance()->Execute($sql); 
                            

                           //avisar creacion categoria por tema google merchant  

                            $category_path = getCategorypath($category->id_parent);

                            $category_path .= '('.$category->id.') '.$category->name[1];

                            // send mail
                            $mail_to = Configuration::get('GMC_EMAIL_TO');
                            if (!$mail_to || empty($mail_to)) {
                                    $mail_to = Configuration::get('PS_SHOP_EMAIL'); //'desarrollo@addis.es';
                            }

                            $dest = [];
                            $dest[] = $mail_to;
                            $dest[] = "pferrando@addis.es";
    


                            $datamail = [
                            '{category_path}' => $category_path,
                            ];

                            $mail_template = 'new_category_add';

                            Mail::Send(
                                Context::getContext()->language->id,
                                $mail_template,
                                'Creación de nueva categoría #'.$category->id,
                                $datamail,
                                $dest,
                                null,
                                null,
                                null,
                                null,
                                null,
                                _PS_MODULE_DIR_.'gmerchantcenter/mails/',
                                false,              
                                Context::getContext()->shop->id
                            );     
















                        }    

                        
                    }    


                }


            }
            else{


                if ($idpadre!=0){


                    $idcats = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_nav=".$data['id']); 


                    if (count($idcats)==1){
                        foreach ($idcats as $idcatsitem) {


                            //recuperar padre
                            $category = new Category((int)$idcatsitem["id_cat"]);
                            $actualparent = $category->id_parent;
                            $idnavactualparent = "".Db::getInstance()->getValue("SELECT id_nav FROM aalv_category_import WHERE id_cat=". $actualparent);   

                            if ($idnavactualparent!=""){

                                if ($idnavactualparent!=$idpadre){

                                    
                                    $catpadre="".Db::getInstance()->getValue("SELECT id_cat FROM aalv_category_import WHERE id_nav=".$idpadre);  
                            

                                    if ($catpadre!=""){
                                        
                                        $category->id_parent = $catpadre;
                                        $category->update();
                                        $id="".Db::getInstance()->getValue("SELECT id FROM aalv_category_import WHERE id_nav=".$idnav);  
                                        if ($id!=""){    
                                            Db::getInstance()->Execute("UPDATE aalv_category_import SET id_padre=".$idpadre." WHERE id=".$id);
                                            Db::getInstance()->Execute("UPDATE aalv_category_import SET id_origen=".$elemento." WHERE id=".$id);
                                        }

                                    }

                                    
                                
                                }


                            }

                        }  


                    }   







                    
                }   
            }

            
               
            return 1;       
        }
        else{
            return 1;
        }
        
}


function ProcesarValoresCaracterProd($data, $fila, $tipo){


//{"id":100002072,"nombre":"Aumentos Visor Filtro"}


        if ($tipo<=2){
            
            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }


            $idorigen=$data["id"];
            $idattrgroupps = "".Db::getInstance()->getValue("SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen=".$idorigen);
            $nombre = $data["nombre"];
            
            if ($idattrgroupps==""){

                $attributegroup= new AttributeGroup();
                $attributegroup->name[1] = $nombre;
                $attributegroup->public_name[1] = substr($nombre,0,64);
                $attributegroup->group_type = "select";

                $attributegroup->add();
                $id_attribute_group = $attributegroup->id;

                Db::getInstance()->Execute("INSERT INTO aalv_attribute_group_import(id_attribute_group, id_origen, in_google_shopping) VALUES (".$id_attribute_group.",".$idorigen.",'')");
            }           
            else{

                $attributegroup= new AttributeGroup((int)$idattrgroupps);
                $attributegroup->name[1] = $nombre;
                $attributegroup->public_name[1] = substr($nombre,0,64);
                $attributegroup->update();

            }

            return 1;       
        }
        else{

            $idorigen=$data["id"];
            $idattrgroupps = "".Db::getInstance()->getValue("SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen=".$idorigen);
            if ($idattrgroupps!=""){
                $attributegroup= new AttributeGroup((int)$idattrgroupps);
                $attributegroup->delete();
                Db::getInstance()->Execute("delete from aalv_attribute_group_import where id_attribute_group=".$idattrgroupps);

            }    

            return 1;
        }
        
}


function ProcesarValoresCaracterProdIdioma($data, $fila, $tipo){


//{"id":100000345,"nombre":"Aumentos Visor Filtro","idioma":"pt","id_caracteristica":100002072}


        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            
            $idorigen=$data["id_caracteristica"];
            $idattrgroupps = "".Db::getInstance()->getValue("SELECT id_attribute_group FROM aalv_attribute_group_import WHERE id_origen=".$idorigen);
            $nombre = $data["nombre"];

            $idioma=$data["idioma"];
            $codidioma=getidioma($idioma);

            if ($idattrgroupps!=""){

                $attributegroup= new AttributeGroup((int)$idattrgroupps);
                $attributegroup->name[$codidioma] = $nombre;
                $attributegroup->public_name[$codidioma] = substr($nombre,0,64);
                $attributegroup->update();

            }

            return 1;       
        }
        else{

           
            return 1;
        }
        
}

function ProcesarValoresModeloIdioma($data, $fila, $tipo){


//{"id":100035526,"id_modelo":100035294,"nombre":"Ligadura Anky Polo (4 unidades)","descripcion":"PHA+QXMgPGI+bGlnYWR1cmFzIEFua3kgUG9sbyBDcmlzdGFsICg0IHVuaWRhZGVzKTwvYj4gc+NvIG11aXRvIGNvbmZvcnThdmVpcyBwYXJhIG8gY2F2YWxvIGRldmlkbyDgIHN1YSBjb21wb3Np5+NvIGVtIHZlbG8gZGUgYWx0YSBxdWFsaWRhZGUuIEFqdXN0YW0tc2UgY29tIGZlY2hvIGRlIHZlbGNybyBmb3J0ZXMgZSBsYXJnb3MuIEFsZ3VtYXMgY29yZXMgY29tIHBlZHJhcyBkZWNvcmF0aXZhcy4gUGFjayBkZSA0IHVuaWRhZGVzLjwvcD48cD48Yj5Db21wb3Np5+NvOjwvYj48L3A+PHVsPjxsaT4xMDAlIFBvbGnpc3Rlci48L2xpPjwvdWw+PGJyPg==","destacar_nombre":"","descripcion_destacado":"","idioma":"pt","imagen":"","seo_title":"","seo_metadescriptions":""}


        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            
            $idorigen=$data["id_modelo"];
            $idproductps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idorigen);
            $nombre =  strip_tags($data["nombre"]);

            $descripcion = base64_decode($data["descripcion"]);
            $descripcion = utf8_encode($descripcion);

            $idioma=$data["idioma"];
            $codidioma=getidioma($idioma);
            
            if ($idproductps!=""){

                $product= new Product((int)$idproductps);
                $product->name[$codidioma] = $nombre;
                $product->description[$codidioma] = $descripcion;
                $product->link_rewrite[$codidioma] = Tools::link_rewrite($nombre);
                $product->update();

            }

            return 1;       
        }
        else{

           
            return 1;
        }
        
}



function ProcesarPerfilesProd($data, $fila, $tipo){

        if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            $orden = $data["orden"];

            if (!$orden) $orden=0;
 
            if ($tipo==1){
                
               Db::getInstance()->Execute("INSERT INTO aalv_perfiles_prod_import(id, id_producto, id_valor, id_modelo, orden, activo) VALUES (".$data["id"].",".$data["id_producto"].",".$data["id_valor"].",".$data["id_modelo"].",".$orden.",1)");
            }
            else{
                Db::getInstance()->Execute("UPDATE aalv_perfiles_prod_import SET id_producto=".$data["id_producto"].",id_valor=".$data["id_valor"].",id_modelo=".$data["id_modelo"].",orden=".$orden." WHERE  id=".$data["id"]);
            }
            
        }
        else{
            Db::getInstance()->Execute("UPDATE aalv_perfiles_prod_import SET activo=0 WHERE id=".$fila);
            $idproducterp =  Db::getInstance()->getValue("SELECT id_producto FROM aalv_perfiles_prod_import where id=".$fila);  
             


            $idproductattributeps = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen=".$idproducterp);
            if ($idproductattributeps!=""){
                    
                    if (("".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps))!=""){

                        $rowperfiles = Db::getInstance()->ExecuteS("SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto=".$idproducterp." and activo=1 order by orden");

                        $idattributes = [];
                        foreach($rowperfiles as $row){

                            $idattr = "".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$row["id_valor"]);
                            if ($idattr!=""){
                                $idattributes[]=(int)$idattr;
                            }                                
                        }  


                        $combination = new Combination((int) $idproductattributeps);
                        $combination->setAttributes($idattributes);
                        $combination->update();

                        $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps);

                        procesarcombinaciones($idprodps);
                }





            }
            return 1;    

        }
        

        

        
        $idproductattributeps = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen=".$data["id_producto"]);

        if ($idproductattributeps!=""){

            if (("".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps))!=""){

                $rowperfiles = Db::getInstance()->ExecuteS("SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto=".$data["id_producto"]." and activo=1 order by orden");

                $idattributes = [];
                foreach($rowperfiles as $row){

                    $idattr = "".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$row["id_valor"]);
                    if ($idattr!=""){
                        $idattributes[]=(int)$idattr;
                    }                                
                }  


                $combination = new Combination((int) $idproductattributeps);
                $combination->setAttributes($idattributes);
                $combination->update();

                $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattributeps);

                procesarcombinaciones($idprodps);
            }

        }
        else{
                //ver si está en combinacion_unica
                $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen=".$data["id_producto"]);

                if ($idprodps != ""){
                   //si  tiene atributos, se debe pasar de combinación unica a combinacio y crear el product attribute correspondiente             

                    $rowperfiles = Db::getInstance()->ExecuteS("SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto=".$data["id_producto"]." and activo=1 order by orden");

                    $idattributes = [];
                    foreach($rowperfiles as $row){

                        $idattr = "".Db::getInstance()->getValue("SELECT id_attribute FROM aalv_attribute_import WHERE id_origen=".$row["id_valor"]);
                        if ($idattr!=""){
                                $idattributes[]=(int)$idattr;
                        }                                
                    }       

                    if (count($idattributes)>0){

                        //si que tiene caracteristicas, pasarlo de combinación unica a combinacion

                        $rowpasacomb = Db::getInstance()->getRow("SELECT * FROM aalv_combinacionunica_import WHERE id_product=".$idprodps);

                        $product = new Product($idprodps);
                        $idProductAttribute = $product->addCombinationEntity(
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                $product->reference,
                                $product->id_supplier,
                                $product->ean13,
                                0,
                                null,
                                $product->upc,
                                1,
                                [1],
                                null,
                                '',
                                null,
                                false,
                                null);

                        $combination = new Combination((int) $idProductAttribute);
                        $combination->setAttributes($idattributes);
                        $combination->update();

                        Db::getInstance()->Execute("INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES (".$idProductAttribute.",".$rowpasacomb["id_origen"].",".$rowpasacomb["id_articulo"].")");

                        Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=".$rowpasacomb["unidades_oferta"].",etiqueta='".$rowpasacomb["etiqueta"]."',estado_gestion=".$rowpasacomb["estado_gestion"].",es_segunda_mano=".$rowpasacomb["es_segunda_mano"].",externo_disponibilidad=".$rowpasacomb["externo_disponibilidad"].",codigo_proveedor='".$rowpasacomb["codigo_proveedor"]."',precio_costo_proveedor=".$rowpasacomb["precio_costo_proveedor"].",tarifa_proveedor=".$rowpasacomb["tarifa_proveedor"].",es_arma=".$rowpasacomb["es_arma"].",es_arma_fogueo=".$rowpasacomb["es_arma_fogueo"].",es_cartucho=".$rowpasacomb["es_cartucho"].",categoria=".$rowpasacomb["categoria"].",familia=".$rowpasacomb["familia"].",subfamilia=".$rowpasacomb["subfamilia"].",grupo=".$rowpasacomb["grupo"]." WHERE id_product_attribute=".$idProductAttribute);


                                //pasar repositorio stock 
                        Db::getInstance()->Execute("UPDATE aalv_repositorio_stock SET id_product_attribute=".$idProductAttribute." where id_product=".$idprodps." and id_product_attribute=0");     

                        $stock = "".Db::getInstance()->getValue("select quantity from aalv_repositorio_stock where id_product=".$idprodps." and id_product_attribute=".$idProductAttribute);
                        if ($stock=="") $stock="0";
                        StockAvailable::setQuantity($idprodps, $idProductAttribute, (int)$stock, 1);




                        Db::getInstance()->Execute("delete from aalv_combinacionunica_import where id_product=".$idprodps);    
                                        

                        procesarcombinaciones($idprodps);
                        procesarCFSG($idprodps);    
    




                    }    





                }





        }    
        







        return 1;

        
}




function getidioma($idioma){


    $codidioma=1;

    if ($idioma=="en"){
        $codidioma=2;
    }

    if ($idioma=="fr"){
        $codidioma=3;
    }

    if ($idioma=="pt"){
        $codidioma=4;
    }

    if ($idioma=="de"){
        $codidioma=5;
    }

    if ($idioma=="it"){
        $codidioma=6;
    }

    return $codidioma;

}



function ProcesarStockCentralWeb($data, $fila, $tipo){

    //no tiene sentido tipo 3 (borrado)
    if ($tipo<=2){

        if (!$data){
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }


        $idarticulo = $data["idarticulo"];

        $unidades = (int)$data["unidades"];

        //if ($unidades<0){
        //    $unidades=0;
        //}

        //buscar 
        $idproductattribute = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo=".$idarticulo);

        if ($idproductattribute!=""){
            $idproduct ="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idproductattribute);            
        }
        else
        {
            $idproduct = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo=".$idarticulo);
            $idproductattribute = "0";
        }

        if ($idproduct!="" ){


            StockAvailable::setQuantity((int)$idproduct, (int)$idproductattribute, $unidades, 1);

            $existe="".Db::getInstance()->getValue("select id from aalv_repositorio_stock where id_product=".$idproduct. " and id_product_attribute=".$idproductattribute); 

            if ($existe==""){
                    Db::getInstance()->Execute("INSERT INTO aalv_repositorio_stock(id_product, id_product_attribute, quantity) VALUES (".$idproduct.",".$idproductattribute.",".$unidades.")");
            }   
            else{
                     Db::getInstance()->Execute("UPDATE aalv_repositorio_stock SET quantity=".$unidades." WHERE id=".$existe);
            }   

            $product = new Product((int)$idproduct);
            $product->update();
            procesarcombinaciones($idproduct);    


            //ver si el producto pertenece a un lote    
            $sql = "SELECT id_ps_product FROM aalv_wk_bundle_product where id_wk_bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map where id_wk_bundle_section in (SELECT id_wk_bundle_section  FROM aalv_wk_bundle_sub_product WHERE id_product = ".$idproduct."))";    

            $posiblelotes = Db::getInstance()->ExecuteS($sql);

            if ($posiblelotes){
                foreach($posiblelotes as $prolote){
                    $product = new Product((int)$prolote["id_ps_product"]);
                    $product->update();
                }
            }


        }
        else{
            sendmail2("llega stock de articulo ".$idarticulo." que no está creado");
        }    
           
        

    }

    return 1;
}

function getpaisps($idorigen){

    $idreturn=0;

    if ($idorigen==53) $idreturn=40;
    if ($idorigen==125) $idreturn=215;
    if ($idorigen==155) $idreturn=229;
    if ($idorigen==94) $idreturn=43;
    if ($idorigen==93) $idreturn=42;
    if ($idorigen==56) $idreturn=228;
    if ($idorigen==65) $idreturn=45;
    if ($idorigen==133) $idreturn=41;
    if ($idorigen==77) $idreturn=44;
    if ($idorigen==13) $idreturn=2;
    if ($idorigen==135) $idreturn=24;
    if ($idorigen==95) $idreturn=46;
    if ($idorigen==66) $idreturn=47;
    if ($idorigen==57) $idreturn=231;
    if ($idorigen==98) $idreturn=51;
    if ($idorigen==162) $idreturn=50;
    if ($idorigen==14) $idreturn=3;
    if ($idorigen==168) $idreturn=60;
    if ($idorigen==42) $idreturn=233;
    if ($idorigen==163) $idreturn=49;
    if ($idorigen==169) $idreturn=62;
    if ($idorigen==164) $idreturn=54;
    if ($idorigen==96) $idreturn=55;
    if ($idorigen==167) $idreturn=59;
    if ($idorigen==80) $idreturn=34;
    if ($idorigen==76) $idreturn=58;
    if ($idorigen==97) $idreturn=48;
    if ($idorigen==170) $idreturn=56;
    if ($idorigen==166) $idreturn=57;
    if ($idorigen==62) $idreturn=52;
    if ($idorigen==69) $idreturn=53;
    if ($idorigen==91) $idreturn=4;
    if ($idorigen==215) $idreturn=71;
    if ($idorigen==213) $idreturn=66;
    if ($idorigen==214) $idreturn=72;
    if ($idorigen==39) $idreturn=19;
    if ($idorigen==127) $idreturn=32;
    if ($idorigen==82) $idreturn=68;
    if ($idorigen==126) $idreturn=64;
    if ($idorigen==136) $idreturn=5;
    if ($idorigen==79) $idreturn=69;
    if ($idorigen==70) $idreturn=73;
    if ($idorigen==110) $idreturn=75;
    if ($idorigen==156) $idreturn=65;
    if ($idorigen==15) $idreturn=76;
    if ($idorigen==34) $idreturn=16;
    if ($idorigen==12) $idreturn=1;
    if ($idorigen==236) $idreturn=77;
    if ($idorigen==16) $idreturn=20;
    if ($idorigen==99) $idreturn=78;
    if ($idorigen==92) $idreturn=79;
    if ($idorigen==123) $idreturn=38;
    if ($idorigen==84) $idreturn=81;
    if ($idorigen==19) $idreturn=86;
    if ($idorigen==124) $idreturn=82;
    if ($idorigen==177) $idreturn=85;
    if ($idorigen==1) $idreturn=6;
    if ($idorigen==10) $idreturn=244;
    if ($idorigen==9) $idreturn=243;
    if ($idorigen==8) $idreturn=242;
    if ($idorigen==178) $idreturn=87;
    if ($idorigen==20) $idreturn=7;
    if ($idorigen==179) $idreturn=90;
    if ($idorigen==203) $idreturn=145;
    if ($idorigen==21) $idreturn=8;
    if ($idorigen==180) $idreturn=91;
    if ($idorigen==33) $idreturn=17;
    if ($idorigen==183) $idreturn=95;
    if ($idorigen==67) $idreturn=93;
    if ($idorigen==88) $idreturn=238;
    if ($idorigen==37) $idreturn=101;
    if ($idorigen==182) $idreturn=94;
    if ($idorigen==112) $idreturn=97;
    if ($idorigen==181) $idreturn=92;
    if ($idorigen==138) $idreturn=102;
    if ($idorigen==101) $idreturn=98;
    if ($idorigen==184) $idreturn=84;
    if ($idorigen==22) $idreturn=9;
    if ($idorigen==72) $idreturn=100;
    if ($idorigen==185) $idreturn=103;
    if ($idorigen==85) $idreturn=104;
    if ($idorigen==117) $idreturn=22;
    if ($idorigen==73) $idreturn=107;
    if ($idorigen==58) $idreturn=74;
    if ($idorigen==113) $idreturn=105;
    if ($idorigen==186) $idreturn=142;
    if ($idorigen==118) $idreturn=110;
    if ($idorigen==24) $idreturn=26;
    if ($idorigen==140) $idreturn=29;
    if ($idorigen==139) $idreturn=109;
    if ($idorigen==187) $idreturn=112;
    if ($idorigen==188) $idreturn=111;
    if ($idorigen==49) $idreturn=108;
    if ($idorigen==25) $idreturn=10;
    if ($idorigen==38) $idreturn=115;
    if ($idorigen==114) $idreturn=114;
    if ($idorigen==141) $idreturn=116;
    if ($idorigen==119) $idreturn=11;
    if ($idorigen==142) $idreturn=118;
    if ($idorigen==192) $idreturn=122;
    if ($idorigen==171) $idreturn=63;
    if ($idorigen==193) $idreturn=119;
    if ($idorigen==174) $idreturn=70;
    if ($idorigen==106) $idreturn=178;
    if ($idorigen==175) $idreturn=120;
    if ($idorigen==176) $idreturn=28;
    if ($idorigen==128) $idreturn=121;
    if ($idorigen==100) $idreturn=234;
    if ($idorigen==191) $idreturn=117;
    if ($idorigen==194) $idreturn=123;
    if ($idorigen==143) $idreturn=125;
    if ($idorigen==107) $idreturn=179;
    if ($idorigen==45) $idreturn=129;
    if ($idorigen==223) $idreturn=195;
    if ($idorigen==196) $idreturn=127;
    if ($idorigen==195) $idreturn=126;
    if ($idorigen==27) $idreturn=130;
    if ($idorigen==28) $idreturn=12;
    if ($idorigen==26) $idreturn=124;
    if ($idorigen==197) $idreturn=128;
    if ($idorigen==129) $idreturn=151;
    if ($idorigen==48) $idreturn=147;
    if ($idorigen==63) $idreturn=146;
    if ($idorigen==59) $idreturn=149;
    if ($idorigen==144) $idreturn=133;
    if ($idorigen==189) $idreturn=139;
    if ($idorigen==60) $idreturn=132;
    if ($idorigen==201) $idreturn=137;
    if ($idorigen==165) $idreturn=61;
    if ($idorigen==204) $idreturn=148;
    if ($idorigen==105) $idreturn=140;
    if ($idorigen==145) $idreturn=141;
    if ($idorigen==29) $idreturn=138;
    if ($idorigen==202) $idreturn=35;
    if ($idorigen==200) $idreturn=136;
    if ($idorigen==199) $idreturn=134;
    if ($idorigen==89) $idreturn=144;
    if ($idorigen==198) $idreturn=135;
    if ($idorigen==157) $idreturn=152;
    if ($idorigen==205) $idreturn=153;
    if ($idorigen==208) $idreturn=158;
    if ($idorigen==158) $idreturn=31;
    if ($idorigen==74) $idreturn=157;
    if ($idorigen==30) $idreturn=13;
    if ($idorigen==159) $idreturn=23;
    if ($idorigen==207) $idreturn=155;
    if ($idorigen==206) $idreturn=154;
    if ($idorigen==146) $idreturn=27;
    if ($idorigen==209) $idreturn=162;
    if ($idorigen==75) $idreturn=166;
    if ($idorigen==78) $idreturn=169;
    if ($idorigen==212) $idreturn=167;
    if ($idorigen==137) $idreturn=170;
    if ($idorigen==210) $idreturn=163;
    if ($idorigen==31) $idreturn=14;
    if ($idorigen==160) $idreturn=181;
    if ($idorigen==111) $idreturn=172;
    if ($idorigen==11) $idreturn=15;
    if ($idorigen==36) $idreturn=245;
    if ($idorigen==211) $idreturn=164;
    if ($idorigen==83) $idreturn=168;
    if ($idorigen==172) $idreturn=173;
    if ($idorigen==40) $idreturn=36;
    if ($idorigen==61) $idreturn=188;
    if ($idorigen==44) $idreturn=175;
    if ($idorigen==216) $idreturn=176;
    if ($idorigen==122) $idreturn=186;
    if ($idorigen==190) $idreturn=192;
    if ($idorigen==219) $idreturn=189;
    if ($idorigen==225) $idreturn=196;
    if ($idorigen==35) $idreturn=18;
    if ($idorigen==120) $idreturn=25;
    if ($idorigen==18) $idreturn=191;
    if ($idorigen==17) $idreturn=37;
    if ($idorigen==220) $idreturn=190;
    if ($idorigen==46) $idreturn=184;
    if ($idorigen==147) $idreturn=187;
    if ($idorigen==222) $idreturn=193;
    if ($idorigen==87) $idreturn=197;
    if ($idorigen==218) $idreturn=185;
    if ($idorigen==71) $idreturn=83;
    if ($idorigen==221) $idreturn=200;
    if ($idorigen==224) $idreturn=199;
    if ($idorigen==173) $idreturn=67;
    if ($idorigen==230) $idreturn=33;
    if ($idorigen==153) $idreturn=204;
    if ($idorigen==228) $idreturn=202;
    if ($idorigen==229) $idreturn=80;
    if ($idorigen==231) $idreturn=210;
    if ($idorigen==132) $idreturn=208;
    if ($idorigen==154) $idreturn=206;
    if ($idorigen==43) $idreturn=209;
    if ($idorigen==109) $idreturn=207;
    if ($idorigen==232) $idreturn=212;
    if ($idorigen==121) $idreturn=201;
    if ($idorigen==227) $idreturn=203;
    if ($idorigen==64) $idreturn=214;
    if ($idorigen==148) $idreturn=213;
    if ($idorigen==90) $idreturn=21;
    if ($idorigen==86) $idreturn=216;
    if ($idorigen==233) $idreturn=217;
    if ($idorigen==47) $idreturn=106;
    if ($idorigen==108) $idreturn=182;
    if ($idorigen==81) $idreturn=219;
    if ($idorigen==104) $idreturn=221;
    if ($idorigen==102) $idreturn=222;
    if ($idorigen==149) $idreturn=220;
    if ($idorigen==234) $idreturn=218;
    if ($idorigen==217) $idreturn=183;
    if ($idorigen==235) $idreturn=225;
    if ($idorigen==131) $idreturn=30;
    if ($idorigen==150) $idreturn=226;
    if ($idorigen==151) $idreturn=227;

    

    return $idreturn;

    

}

function esportestandard($porte){

    $portestandard= array();

    $portestandard[]="A";
    $portestandard[]="CM1";
    $portestandard[]="B";
    $portestandard[]="AA";
    $portestandard[]="ALEMANIA STANDARD";
    $portestandard[]="ALEMANIA  EXPRESS";
    $portestandard[]="AUSTRIA STANDARD";
    $portestandard[]="AUSTRIA EXPRESS";
    $portestandard[]="BELGICA STANDARD";
    $portestandard[]="BELGICA EXPRESS";
    $portestandard[]="CHIPRE STANDARD";
    $portestandard[]="CHIPRE  EXPRESS";
    $portestandard[]="DINAMARCA STANDARD";
    $portestandard[]="DINAMARCA  EXPRESS";
    $portestandard[]="ESLOVAQUIA STANDARD";
    $portestandard[]="ESLOVAQUIA  EXPRESS";
    $portestandard[]="ESLOVENIA STANDARD";
    $portestandard[]="ESLOVENIA  EXPRESS";
    $portestandard[]="ESTONIA STANDARD";
    $portestandard[]="ESTONIA  EXPRESS";
    $portestandard[]="FINLANDIA STANDARD";
    $portestandard[]="FINLANDIA  EXPRESS";
    $portestandard[]="FRANCIA STANDARD";
    $portestandard[]="FRANCIA EXPRESS";
    $portestandard[]="GRECIA STANDARD";
    $portestandard[]="GRECIA  EXPRESS";
    $portestandard[]="HUNGRIA STANDARD";
    $portestandard[]="HUNGRIA  EXPRESS";
    $portestandard[]="IRLANDA STANDARD";
    $portestandard[]="IRLANDA  EXPRESS";
    $portestandard[]="ITALIA STANDARD";
    $portestandard[]="ITALIA EXPRESS";
    $portestandard[]="LETONIA STANDARD";
    $portestandard[]="LETONIA  EXPRESS";
    $portestandard[]="LITUANIA STANDARD";
    $portestandard[]="LITUANIA  EXPRESS";
    $portestandard[]="LUXEMBURGO STANDARD";
    $portestandard[]="LUXEMBURGO  EXPRESS";
    $portestandard[]="MALTA STANDARD";
    $portestandard[]="MALTA  EXPRESS";
    $portestandard[]="HOLANDA STANDARD";
    $portestandard[]="HOLANDA  EXPRESS";
    $portestandard[]="POLONIA STANDARD";
    $portestandard[]="POLONIA  EXPRESS";
    $portestandard[]="REINO UNIDO STANDARD";
    $portestandard[]="REINO UNIDO  EXPRESS";
    $portestandard[]="REP. CHECA STANDARD";
    $portestandard[]="REP. CHECA  EXPRESS";
    $portestandard[]="SUECIA STANDARD";
    $portestandard[]="SUECIA  EXPRESS";
    $portestandard[]="GUERNSEY STANDARD";
    $portestandard[]="JERSEY STANDARD";
    $portestandard[]="SUIZA STANDARD";
    $portestandard[]="SUIZA EXPRESS";
    $portestandard[]="RUMANIA STANDARD";
    $portestandard[]="RUMANIA EXPRESS";
    $portestandard[]="NORUEGA STANDARD";
    $portestandard[]="NORUEGA EXPRESS";
    $portestandard[]="BULGARIA STANDARD";
    $portestandard[]="BULGARIA  EXPRESS";
    $portestandard[]="TURQUIA STANDARD";
    $portestandard[]="TURQUIA EXPRESS";
    $portestandard[]="RUSIA EXPRESS";
    $portestandard[]="LIECHTESTEIN EXPRESS";
    $portestandard[]="MONACO EXPRESS";
    $portestandard[]="ISLANDIA EXPRESS";
    $portestandard[]="CROACIA EXPRESS";
    $portestandard[]="ALBANIA EXPRESS";
    $portestandard[]="BOSNIA EXPRESS";
    $portestandard[]="CROACIA EXPRESS";
    $portestandard[]="MONTENEGRO EXPRESS";
    $portestandard[]="MACEDONIA EXPRESS";
    $portestandard[]="SERVIA EXPRESS";
    $portestandard[]="BIELORRUSIA EXPRESS";
    $portestandard[]="MOLDAVIA EXPRESS";
    $portestandard[]="UCRANIA EXPRESS";
    $portestandard[]="ARMENIA EXPRESS";
    $portestandard[]="GEORGIA EXPRESS";
    $portestandard[]="COSTA RICA EXPRESS";
    $portestandard[]="EL SALVADOR EXPRESS";
    $portestandard[]="GUATEMALA EXPRESS";
    $portestandard[]="HONDURAS EXPRESS";
    $portestandard[]="NICARAGUA EXPRESS";
    $portestandard[]="PANAMA EXPRESS";
    $portestandard[]="BRASIL EXPRESS";
    $portestandard[]="ARGENTINA EXPRESS";
    $portestandard[]="PERU EXPRESS";
    $portestandard[]="COLOMBIA EXPRESS";
    $portestandard[]="BOLIVIA EXPRESS";
    $portestandard[]="VENEZUELA EXPRESS";
    $portestandard[]="CHILE EXPRESS";
    $portestandard[]="PARAGUAY EXPRESS";
    $portestandard[]="ECUADOR EXPRESS";
    $portestandard[]="URUGUAY EXPRESS";
    $portestandard[]="MEXICO EXPRESS";
    $portestandard[]="ESTADOS UNIDOS EXPRESS";
    $portestandard[]="CANADA EXPRESS";
    $portestandard[]="REP. DOMINICANA EXPRESS";
    $portestandard[]="CUBA EXPRESS";
    $portestandard[]="PUERTO RICO EXPRESS";
    $portestandard[]="JAMAICA EXPRESS";
    $portestandard[]="CORCEGA STANDARD";
    $portestandard[]="IRLANDA DEL NORTE STANDARD";
    $portestandard[]="HONG KONG";
    $portestandard[]="INDONESIA EXPRESS";
    $portestandard[]="JAPON EXPRESS";
    $portestandard[]="SINGAPUR EXPRESS";
    $portestandard[]="TAIWAN EXPRESS";
    $portestandard[]="ARABIA SAUDI EXPRESS";
    $portestandard[]="ARGELIA EXPRESS";
    $portestandard[]="EGIPTO EXPRESS";
    $portestandard[]="EMIRATOS ARABES UNIDOS EXPRESS";
    $portestandard[]="CAMERUN EXPRESS";
    $portestandard[]="COSTA DE MARFIL EXPRESS";
    $portestandard[]="KUWAIT EXPRESS";
    $portestandard[]="MARRUECOS EXPRESS";
    $portestandard[]="QATAR EXPRESS";
    $portestandard[]="SURAFRICA EXPRESS";
    $portestandard[]="TUNEZ EXPRESS";
    $portestandard[]="ANGOLA EXPRESS";
    $portestandard[]="ARMENIA EXPRESS";
    $portestandard[]="AUSTRALIA EXPRESS";
    $portestandard[]="CHINA EXPRESS";
    $portestandard[]="FILIPINAS EXPRESS";
    $portestandard[]="GUINEA EXPRESS";
    $portestandard[]="INDIA EXPRESS";
    $portestandard[]="ISRAEL EXPRESS";
    $portestandard[]="JORDANIA EXPRESS";
    $portestandard[]="KENIA EXPRESS";
    $portestandard[]="LIBANO EXPRESS";
    $portestandard[]="MADAGASCAR EXPRESS";
    $portestandard[]="MAURITANIA EXPRESS";
    $portestandard[]="NUEVA ZELANDA EXPRESS";
    $portestandard[]="SENEGAL EXPRESS";
    $portestandard[]="UGANDA EXPRESS";
    $portestandard[]="VIETNAM EXPRESS";
    $portestandard[]="ZAMBIA EXPRESS";
    $portestandard[]="ZIMBAWE EXPRESS";


    return in_array($porte, $portestandard);




}


function ProcesarPortesProducto($data, $fila, $tipo){


    //{"id":125088226,"referencia":"H306991-C40","id_producto":100140388,"codigo":"IRLANDA STANDARD","id_pais":24}

    if ($tipo<=2){

        if (!$data){
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }


        if (!esportestandard($data["codigo"])){

            $referencia=$data["referencia"]; 


            /*    
            $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product WHERE reference='".$referencia."'");       
            $idproductattribute="0";

            if ($idproduct==""){
                $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE reference='".$referencia."'"); 
                $idproductattribute="".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_product_attribute WHERE reference='".$referencia."'"); 
            }
            */

            $idproductattribute="".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_product_attribute WHERE reference='".$referencia."'"); 
            if ($idproductattribute=="") {
                    $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product WHERE reference='".$referencia."'");      
                    $idproductattribute="0";    
            }
            else{
                    $idproduct="".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE reference='".$referencia."'"); 
            }



            if ($idproduct!=""){
               
                $idpaisps=getpaisps($data["id_pais"]);

                $existe = "".Db::getInstance()->getValue("select id from aalv_portes_producto where id_origen=".$data["id"]);   

                if ($existe!=""){

                    Db::getInstance()->Execute("UPDATE aalv_portes_producto set referencia='".$referencia."', codigo='".$data["codigo"]."' ,id_pais_origen=".$data["id_pais"]." ,id_pais=".$idpaisps." ,id_product=".$idproduct." ,id_product_attribute=".$idproductattribute." where id=".$existe);
       

                }
                else{
                     Db::getInstance()->Execute("INSERT INTO aalv_portes_producto(id_origen,referencia,codigo,id_pais_origen,id_pais,id_product,id_product_attribute) VALUES (".$data["id"].",'".$referencia."','".$data["codigo"]."',".$data["id_pais"].",".$idpaisps.",".$idproduct.",".$idproductattribute.")");
       
                }

                 
            }
               

        }
        

    }

    return 1;


}


function ProcesarModVideo($data, $fila, $tipo){


    if ((!$data) && ($tipo<=2)) {
        //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
        return 1;
    }



    $idioma=$data["idioma"];
    $codidioma=getidioma($idioma);

    $idvideo = str_replace("https://www.youtube.com/embed/", "", $data["contenido"]);
    $idvideo = str_replace("https://youtu.be/", "", $idvideo);
    $idvideo = trim($idvideo);

    if ($tipo<=2){

        $idvideops = "".Db::getInstance()->getValue("select a.id_productvideo from aalv_video_import a inner join aalv_product_videos b on a.id_productvideo=b.id_productvideo and b.id_lang=".$codidioma." where id_origen=".$data["id"]);
        if ($idvideops!=""){
            //ya existe, ver si el modelo coincide con el producto del video
            $prodps = "".Db::getInstance()->getValue("select id_product from aalv_product_videos where id_productvideo=".$idvideops);
            $prodfrommodel = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$data["id_modelo"]);

            if ($prodps==$prodfrommodel){
                //hacer update del video
                Db::getInstance()->Execute("UPDATE aalv_product_videos SET id_video='".$idvideo."', video_url='".$data["contenido"]."', position=".$data["orden"]." WHERE id_productvideo=".$idvideops);
            }
            else{

                //discrepancia
            }


        }
        else{
            //creacion

            $idproduct = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$data["id_modelo"]);

            if ($idproduct!=""){

                Db::getInstance()->Execute("INSERT INTO aalv_product_videos(id_product, id_video, title, provider, video_url, position, id_lang, id_shop) VALUES (".$idproduct.",'".$idvideo."','','youtube','".$data["contenido"]."',".$data["orden"].",".$codidioma.",1)");
                $idproductvideo= (int)Db::getInstance()->Insert_ID();
                if ($idproductvideo!=0)  Db::getInstance()->Execute("INSERT INTO aalv_video_import(id_productvideo, id_origen) VALUES (".$idproductvideo.",".$data["id"].")");


            }


           


        }


    }
    else{

        //borrado
        $idvideops = Db::getInstance()->ExecuteS("select id_productvideo from aalv_video_import where id_origen=".$fila);
        foreach($idvideops as $row){
            Db::getInstance()->Execute("delete from aalv_product_videos where id_productvideo=".$row["id_productvideo"]);
        }
        Db::getInstance()->Execute("delete from aalv_video_import where id_origen=".$fila);


    }

    return 1;
}



function ProcesarValores($valores){

    $tabla =  $valores["tabla"];    
    
    echo "Pocesando ". $tabla. " fila ". $valores["fila"] . " tipo ". $valores["tipo"];  

    switch ($tabla) {


        case "v_sinc_w_modelo_idioma":

                return ProcesarValoresModeloIdioma($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;


        case "v_sinc_w_caracter_prod_idioma":
                return ProcesarValoresCaracterProdIdioma($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;


        case "v_sinc_w_caracter_prod":
                return ProcesarValoresCaracterProd($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;



        case "v_sinc_w_navegacion":
                return ProcesarValoresNavegacion($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;



        case "v_sinc_w_valores_prod":
                return ProcesarValoresProd($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;


        case "v_sinc_w_valores_nav":
                return ProcesarValoresNav($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;



        case "v_sinc_w_valores_nav_idioma":
                return ProcesarValoresNavIdioma($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;


        /*        
        case "v_sinc_w_modelo_idioma":
                return ProcesarModeloIdioma($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;
        */


        case "v_sinc_tag_temporal":
                return ProcesarTagTemporal($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;

        case "v_sinc_w_mod_documento":
                return ProcesarModDocumento($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;

        case "v_sinc_w_modelo":
                return ProcesarModelo($valores["data"], $valores["fila"], $valores["tipo"]);    
                break;
        case "v_sinc_w_producto":
                return ProcesarProducto($valores["data"], $valores["fila"], $valores["tipo"]);
                break;          
        case "v_sinc_w_ayudas":         
                return ProcesarAyudas($valores["data"], $valores["fila"], $valores["tipo"]);
                break;        
                
        case "v_sinc_w_ayudas_mod":         
                return ProcesarAyudasMod($valores["data"], $valores["fila"], $valores["tipo"]);
                break;        
        case "v_sinc_w_perfiles_nav":           
                return ProcesarPerfilesNav($valores["data"], $valores["fila"], $valores["tipo"] );
                break;        
        case "v_sinc_tarifa_cabecera":
                return ProcesarTarifaCabecera($valores["data"], $valores["fila"], $valores["tipo"] );
                break;
        case "v_sinc_tarifa_linea": 
                return ProcesarTarifaLinea($valores["data"], $valores["fila"], $valores["tipo"] );
                break;

        case "v_sinc_w_perfiles_prod": 
                return ProcesarPerfilesProd($valores["data"], $valores["fila"], $valores["tipo"] );
                break;
        
         case "v_sinc_stock_central_web":    
                return ProcesarStockCentralWeb($valores["data"], $valores["fila"], $valores["tipo"] );
                break;
         
         case "v_sinc_w_caracter_orden":
                return 1;
                break;

        case "v_sinc_w_portes_producto":
               return ProcesarPortesProducto($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;


        case "v_sinc_w_mod_video":
               return ProcesarModVideo($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       

        //lotes       
        case "v_sinc_tarifalote":
               return ProcesarTarifalote($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
        
        case "v_sinc_lllote":
               return ProcesarLllote($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
               
        case "v_sinc_llote":
               return ProcesarLlote($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
     
        case "v_sinc_lloteidioma":
               return ProcesarLloteidioma($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
               
        case "v_sinc_lote":
               return ProcesarLote($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
        case "v_sinc_w_producto_imagen":
               return ProcesarProductoImagen($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
       
        case "v_sinc_w_modelo_imagen":
               return ProcesarModeloImagen($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
       
        case "v_sinc_w_valores_prod_idioma":
                     return ProcesarValoresProdIdioma($valores["data"], $valores["fila"], $valores["tipo"] ); 
               break;       
         case "v_sinc_w_producto_zona":         
                    return 1; 
               break;  

        case "v_sinc_tbono_promocion":
                return 1;
                break;       

    }
    return 0;
}

function ProcesarValoresProdIdioma($data, $fila, $tipo){

    if ($tipo<=2){

            if (!$data){
                //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }


            
            if ($data){
                return 1;
            }

            return 1;       
        }
        else{
            return 1;
        }


}    


function ProcesarProductoImagen($data, $fila, $tipo){

    $modelo = 0;
    $idorigen = $data["id"];
    $producto = $data["id_producto"];
    $filename = $data["path_imagen"];
    $posicion = $data["orden"];


    $idprodattrps = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen=".$producto." and id_product_attribute in (select id_product_attribute from aalv_product_attribute)");

    $idprodps = "";
    if ( $idprodattrps!=""){
        $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute=".$idprodattrps);        
    }

    $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$modelo);

    if ($idprodps != ""){
        if ($tipo<=2){

            //ver si existe la imagen en la tabla image_import
            $existe = "".Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_product=".$idprodps." and filename='".$filename."'");

            if ($existe!=""){

                $image = new Image($existe);
                $image->position = (int)$posicion;
                $image->update();

                //añadirla a la combinación
                Db::getInstance()->execute("REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES (".$idprodattrps.",". $image->id.")");



            }
            else{

                if (download($filename)){

                    $image = new Image();
                    $image->id_product = $idprodps;
                    $image->position = (int)$posicion;
                        
                    if (($image->validateFields(false, true)) === true &&
                        ($image->validateFieldsLang(false, true)) === true && $image->add())
                    {
                        
                        if (!copyImg($idprodps, $image->id, __DIR__."/backups/".$filename, 'products', true))
                        {
                            $image->delete();
                            //echo "pasa....1";
                        }
                        else{
                                if (!file_exists(_PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format)) {
                                      $image->delete();
                                      //echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                                }   
                        }
                        //echo "llega imagen";    
                        unlink(__DIR__."/backups/".$filename);

                        Db::getInstance()->Execute("INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES (".$image->id.",".$idprodps.",'".$filename."',".$idorigen.",".$modelo.",".$producto.",".(int)$posicion.")");       

                        //añadirla a la combinación
                        Db::getInstance()->execute("REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES (".$idprodattrps.",". $image->id.")");
            

                    }


                }
                else{
                    //foto no existe en el ftp    

                }


            }

        }
    }   
    else{
        if ($tipo==3){

            //recuperar del origen

            $idimage = "" & Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_origen=".$fila);

            if ($idimage!=""){
                $image = new Image($idimage);
                $image->delete();
            }
        }

    }

    return 1;


   
}




function crearimagenproducto($idprodps, $filename, $idattr, $producto){

    if ($filename==""){
        return;
    }

    $existe = "".Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_product=".$idprodps." and filename='".$filename."'");

    if ($existe==""){

        
        if (download($filename)){

            $image = new Image();
            $image->id_product = $idprodps;
            $image->position = 0;
                
            if (($image->validateFields(false, true)) === true &&
                ($image->validateFieldsLang(false, true)) === true && $image->add())
            {
                
                if (!copyImg($idprodps, $image->id, __DIR__."/backups/".$filename, 'products', true))
                {
                    $image->delete();
                    //echo "pasa....1";
                }
                else{
                        if (!file_exists(_PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format)) {
                              $image->delete();
                              //echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                        }   
                }
                //echo "llega imagen";    
                unlink(__DIR__."/backups/".$filename);

                

                Db::getInstance()->Execute("INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES (".$image->id.",".$idprodps.",'".$filename."',0,0,".$producto.",0)");       

                //añadirla a la combinación
                Db::getInstance()->execute("REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES (".$idattr.",". $image->id.")");
            




            }


        }
        else{
            //foto no existe en el ftp    

        }


    }
    else
    {
        $image = new Image($existe);
        $image->update();
        Db::getInstance()->execute("REPLACE INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES (".$idattr.",". $image->id.")");
            

    }




}






function crearimagenmodelo($idprodps, $filename){

    if ($filename==""){
        return;
    }

    $existe = "".Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_product=".$idprodps." and filename='".$filename."'");

    if ($existe==""){

        
        if (download($filename)){

            $image = new Image();
            $image->id_product = $idprodps;
            $image->position = 0;
            $image->cover = true;
                
            if (($image->validateFields(false, true)) === true &&
                ($image->validateFieldsLang(false, true)) === true && $image->add())
            {
                
                if (!copyImg($idprodps, $image->id, __DIR__."/backups/".$filename, 'products', true))
                {
                    $image->delete();
                    //echo "pasa....1";
                }
                else{
                        if (!file_exists(_PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format)) {
                              $image->delete();
                              //echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                        }   
                }
                //echo "llega imagen";    
                unlink(__DIR__."/backups/".$filename);

                Db::getInstance()->Execute("INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES (".$image->id.",".$idprodps.",'".$filename."',0,0,0,0)");       

            }


        }
        else{
            //foto no existe en el ftp    

        }


    }




}



function ProcesarModeloImagen($data, $fila, $tipo){
    

    $modelo = $data["id_modelo"];
    $idorigen = $data["id"];
    $producto = 0;
    $filename = $data["path_imagen"];
    $posicion = $data["orden"];

    $idprodps = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$modelo);

    if ($idprodps != ""){
        if ($tipo<=2){

            //ver si existe la imagen en la tabla image_import
            $existe = "".Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_product=".$idprodps." and filename='".$filename."'");

            if ($existe!=""){

                $image = new Image($existe);
                $image->position = (int)$posicion;
                $image->update();

            }
            else{

                if (download($filename)){

                    $image = new Image();
                    $image->id_product = $idprodps;
                    $image->position = (int)$posicion;
                        
                    if (($image->validateFields(false, true)) === true &&
                        ($image->validateFieldsLang(false, true)) === true && $image->add())
                    {
                        
                        if (!copyImg($idprodps, $image->id, __DIR__."/backups/".$filename, 'products', true))
                        {
                            $image->delete();
                            //echo "pasa....1";
                        }
                        else{
                                if (!file_exists(_PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format)) {
                                      $image->delete();
                                      //echo "pasa....2 "._PS_PROD_IMG_DIR_. $image->getExistingImgPath() . '.' . $image->image_format;
                                }   
                        }
                        //echo "llega imagen";    
                        unlink(__DIR__."/backups/".$filename);

                        Db::getInstance()->Execute("INSERT INTO aalv_image_import(id_image, id_product, filename, id_origen, modelo, producto, posicion) VALUES (".$image->id.",".$idprodps.",'".$filename."',".$idorigen.",".$modelo.",".$producto.",".(int)$posicion.")");       

                    }


                }
                else{
                    //foto no existe en el ftp    

                }


            }

        }
    }   
    else{
        if ($tipo==3){

            //recuperar del origen

            $idimage = "".Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE id_origen=".$fila);

            if ($idimage!=""){
                $image = new Image($idimage);
                $image->delete();
            }
        }

    }

    return 1;
}


function ProcesarTarifalote($data, $fila, $tipo){

    if ($tipo<=2){

        if (!$data) {
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }



        $idtarifalote = "".Db::getInstance()->getValue("SELECT idtarifalote FROM aalv_tarifalote_import where idtarifalote=".$data["idtarifalote"]);
        if ($idtarifalote==""){
            Db::getInstance()->Execute("INSERT INTO aalv_tarifalote_import(idtarifalote, idllote, estado, idttarifa, precio, precio_con_impuestos) VALUES (".$data["idtarifalote"].",".$data["idllote"].",".$data["estado"].",".$data["idregpais"].",".$data["precio"].",'".$data["precio_con_impuestos"]."')");
        }

        //averiguar idproduct
        $idproduct="".Db::getInstance()->getValue("SELECT id_ps_product FROM aalv_wk_bundle_product WHERE id_wk_bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote=".$data["idllote"]."))");
        if ($idproduct!=""){

            $idlote = "".Db::getInstance()->getValue("SELECT idlote FROM aalv_lote_import WHERE bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote=".$data["idllote"]."))");

            if ($idlote != ""){
                $preciotarifa = Db::getInstance()->getValue("SELECT sum(precio) FROM aalv_tarifalote_import where idttarifa=1 and idllote in (select idllote from aalv_llote_import where bundle_section in (select id_wk_bundle_section from aalv_wk_bundle_section_map where id_wk_bundle_product in (select bundle_product from aalv_lote_import where idlote=".$idlote.")))");

                Db::getInstance()->Execute("update aalv_product_shop set id_tax_rules_group = 7, price=".round($preciotarifa,6)." where id_product=".$idproduct);    
                Db::getInstance()->Execute("update aalv_product set id_tax_rules_group = 7, price=".round($preciotarifa,6)." where id_product=".$idproduct);  


                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=6 and id_product=".$idproduct);    
                $idfeaturevalue = crearFeatureValue(6, "1", 0);    
                if ($idfeaturevalue!=0){
                    $product = new Product($idproduct);        
                    $product->addFeatureProductImport($idproduct, 6, $idfeaturevalue);    
                }   

                Db::getInstance()->Execute("REPLACE INTO aalv_appagebuilder_page(id_product, id_category, page, id_shop) VALUES (".$idproduct.",0,'detail1988211104',1)");

            }

        }



    }
    else{
        //borrado
        
        $idllote = "".Db::getInstance()->getValue("SELECT idllote FROM aalv_tarifalote_import where idtarifalote=".$fila);



        $idproduct="".Db::getInstance()->getValue("SELECT id_ps_product FROM aalv_wk_bundle_product WHERE id_wk_bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote=".$idllote."))");
        if ($idproduct!=""){

            $idlote = "".Db::getInstance()->getValue("SELECT idlote FROM aalv_lote_import WHERE bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote=".$idllote."))");

            if ($idlote != ""){

                //borrar antes del cálculo esa tarifa
                Db::getInstance()->Execute("DELETE FROM aalv_tarifalote_import where idtarifalote=".$fila);

                $preciotarifa = Db::getInstance()->getValue("SELECT sum(precio) FROM aalv_tarifalote_import where idttarifa=1 and idllote in (select idllote from aalv_llote_import where bundle_section in (select id_wk_bundle_section from aalv_wk_bundle_section_map where id_wk_bundle_product in (select bundle_product from aalv_lote_import where idlote=".$idlote.")))");

                Db::getInstance()->Execute("update aalv_product_shop set id_tax_rules_group = 7, price=".round($preciotarifa,6)." where id_product=".$idproduct);    
                Db::getInstance()->Execute("update aalv_product set id_tax_rules_group = 7, price=".round($preciotarifa,6)." where id_product=".$idproduct);  


                Db::getInstance()->Execute("DELETE FROM aalv_feature_product WHERE id_feature=6 and id_product=".$idproduct);    
                $idfeaturevalue = crearFeatureValue(6, "1", 0);    
                if ($idfeaturevalue!=0){
                    $product = new Product($idproduct);        
                    $product->addFeatureProductImport($idproduct, 6, $idfeaturevalue);    
                }   

                Db::getInstance()->Execute("REPLACE INTO aalv_appagebuilder_page(id_product, id_category, page, id_shop) VALUES (".$idproduct.",0,'detail1988211104',1)");

            }

        }




    }    
    return 1;

}    

function ProcesarLllote($data, $fila, $tipo){

    if ($tipo<=2){

        if (!$data) {
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }

        $id_bundle_sub_product = "".Db::getInstance()->getValue("SELECT id_bundle_sub_product FROM aalv_lllote_import WHERE idlllote=".$data["idlllote"]);
        if ("".$id_bundle_sub_product==""){
            //crear

            $id_bundle_sub_product = 0;
            $id_bundle_sub_product_attribute = 0;

            $idarticulo=$data["idarticulo"];
            $idproduct = Db::getInstance()->getValue("SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo=".$idarticulo." UNION select id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = ".$idarticulo.")"); 


            //ver si existe seccion producto

            $bundlesection = "".Db::getInstance()->getValue("select bundle_section from aalv_llote_import where idllote=".$data["idllote"]);

            if ($bundlesection != ""){

                $id_bundle_sub_product = "".Db::getInstance()->getValue("SELECT id_bundle_sub_product from aalv_wk_bundle_sub_product where id_wk_bundle_section=".$bundlesection." and id_product=".$idproduct);

                if ($id_bundle_sub_product==""){
                    Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_sub_product(id_wk_bundle_section, id_product, id_shop, active, for_bundle_only) VALUES (".$bundlesection.",".$idproduct.",1,1,0)");
                    $id_bundle_sub_product = Db::getInstance()->Insert_ID();
                }

                $idproductattribute = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = ".$idarticulo); 

                if ($idproductattribute==""){
                    $idproductattribute = "0";
                }

                $defaultattr = "0";
                if ( $idproductattribute != "0"){
                    $defaultattr="".Db::getInstance()->getValue("SELECT default_on FROM aalv_product_attribute where id_product_attribute = ".$idproductattribute); 
                    if ($defaultattr==""){
                        $defaultattr="0";
                    }
                }

                

                Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_sub_product_attribute(id_wk_bundle_section, id_product, id_product_attribute, quantity, normal_stock, default_attr) VALUES (".$bundlesection.",".$idproduct.",".$idproductattribute.",0,0,".$defaultattr.")");


                $id_bundle_sub_product_attribute = Db::getInstance()->Insert_ID(); 

                $unidades = (int)$data["unidades"];
                if ($unidades>1){
                
                    Db::getInstance()->Execute("UPDATE aalv_wk_bundle_sub_product_attribute SET quantity=".$unidades." WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);

                }
                else{
                    Db::getInstance()->Execute("UPDATE aalv_wk_bundle_sub_product_attribute SET quantity=0 WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);
                }




                Db::getInstance()->Execute("INSERT INTO aalv_lllote_import(idlllote, idarticulo, id_bundle_sub_product, id_bundle_sub_product_attribute) VALUES (".$data["idlllote"].",".$idarticulo.",".$id_bundle_sub_product.",".$id_bundle_sub_product_attribute.")");
            }

        }
        else{
            //modificar

            $id_bundle_sub_product_attribute = "".Db::getInstance()->getValue("SELECT id_bundle_sub_product_attribute FROM aalv_lllote_import WHERE idlllote=".$data["idlllote"]);

            $idarticulo=$data["idarticulo"];
            $idproduct = Db::getInstance()->getValue("SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo=".$idarticulo." UNION select id_product from    aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = ".$idarticulo.")"); 

            $idproductattribute = "".Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = ".$idarticulo); 

            if ($idproductattribute==""){
                $idproductattribute = "0";
            }

            $defaultattr = "0";
            if ( $idproductattribute != "0"){
                $defaultattr="".Db::getInstance()->getValue("SELECT default_on FROM aalv_product_attribute where id_product_attribute = ".$idproductattribute); 
                if ($defaultattr==""){
                    $defaultattr="0";
                }
            }

            Db::getInstance()->Execute("UPDATE aalv_wk_bundle_sub_product set id_product=".$idproduct." WHERE id_bundle_sub_product=".$id_bundle_sub_product);

            Db::getInstance()->Execute("UPDATE aalv_wk_bundle_sub_product_attribute set id_product=".$idproduct." , id_product_attribute=".$idproductattribute.", default_attr=".$defaultattr." WHERE id_bundle_sub_product_attribute=".$id_bundle_sub_product_attribute);

            $unidades = (int)$data["unidades"];
            if ($unidades>1){
            
                Db::getInstance()->Execute("UPDATE aalv_wk_bundle_sub_product_attribute SET quantity=".$unidades." WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);

            }



            
            Db::getInstance()->Execute("UPDATE aalv_lllote_import set idarticulo=".$idarticulo." where idlllote=".$data["idlllote"]);



        }    


    }
    else{

        

        //recuperar 
        $id_bundle_sub_product = "".Db::getInstance()->getValue("SELECT id_bundle_sub_product FROM aalv_lllote_import WHERE idlllote=".$fila);
        $id_bundle_sub_product_attribute = "".Db::getInstance()->getValue("SELECT id_bundle_sub_product_attribute FROM aalv_lllote_import WHERE idlllote=".$fila);
        
        //borrar de la tabla 

        if ($id_bundle_sub_product_attribute!=""){

            //coger producto y atributo
            $idprodaux = Db::getInstance()->getValue("SELECT id_product FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);
            $idprodattributeaux = Db::getInstance()->getValue("SELECT id_product_attribute FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);

            $idsectionaux = Db::getInstance()->getValue("SELECT id_wk_bundle_section FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);
            
            Db::getInstance()->Execute("DELETE FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);
            
            //ver si no queda ningún producto
            $existe = Db::getInstance()->getValue("SELECT count(*) FROM aalv_wk_bundle_sub_product_attribute WHERE id_wk_bundle_section = ".$idsectionaux." and id_product= ".$idprodaux);
            if ($existe==0){
                //borrar tambien $id_bundle_sub_product
                if ($id_bundle_sub_product!=""){
                    Db::getInstance()->Execute("DELETE FROM aalv_wk_bundle_sub_product WHERE id_bundle_sub_product=".$id_bundle_sub_product);
                }
            }


        }

        
          

    }
    
    return 1;

}    

function ProcesarLlote($data, $fila, $tipo){

    if ($tipo<=2){

        if (!$data) {
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }


        $bundlesection = "".Db::getInstance()->getValue("select bundle_section from aalv_llote_import where idllote=".$data["idllote"]);

        $idbundleproduct = "".Db::getInstance()->getValue("SELECT bundle_product FROM aalv_lote_import WHERE idlote=".$data["idlote"]);

        if ("".$bundlesection==""){
            //crear la bundle section

            
            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section(min_quantity, choose_quantity, quantity_wise_discount, active, is_required, state, date_add, date_upd) VALUES (1,0,0,".$data["estado"].",1,".$data["estado"].",now(),now())");
            $bundlesection = Db::getInstance()->Insert_ID();

            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES (".$bundlesection.",1,1,'".$data["descripcion"]."')");
            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES (".$bundlesection.",1,2,'".$data["descripcion"]."')");
            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES (".$bundlesection.",1,3,'".$data["descripcion"]."')");
            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES (".$bundlesection.",1,4,'".$data["descripcion"]."')");
            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES (".$bundlesection.",1,5,'".$data["descripcion"]."')");

            
            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section_shop(id_wk_bundle_section, id_shop, date_add, date_upd) VALUES (".$bundlesection.",1,now(),now())");

            Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_section_map(id_wk_bundle_product, id_wk_bundle_section) VALUES (".$idbundleproduct.",".$bundlesection.")");

            Db::getInstance()->Execute("INSERT INTO aalv_llote_import(idllote, bundle_section) VALUES (".$data["idllote"].",".$bundlesection.")");


            

        }
        else{

             Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section SET active=".$data["estado"].", state=".$data["estado"].", date_upd=now() where id_wk_bundle_section=".$bundlesection);

             Db::getInstance()->Execute("REPLACE INTO aalv_wk_bundle_section_map(id_wk_bundle_product, id_wk_bundle_section) VALUES (".$idbundleproduct.",".$bundlesection.")");


             Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section_lang set section_name='".$data["descripcion"]."' where id_wk_bundle_section=".$bundlesection." and id_lang=1");


        }


    }
    else{




    }    


     return 1;

}   

function ProcesarLloteidioma($data, $fila, $tipo){
    if ($tipo<=2){

        if (!$data) {
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }

        //{"idlloteidioma":26930,"idllote":100009087,"ididioma":7,"descripcion":"COLETE 1","idioma_descripcion":"Portugues"}

        
                

         $bundlesection = "".Db::getInstance()->getValue("select bundle_section from aalv_llote_import where idllote=".$data["idllote"]);


        if ($bundlesection!=""){

            $idioma=0;
            $ididioma=$data["ididioma"];
            if ($ididioma==7) $idioma=4;

            if ($idioma!=0){

                Db::getInstance()->Execute("REPLACE INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES (".$bundlesection.",1,".$idioma.",'".$data["descripcion"]."')");
            }
        }

    }
    else{


    }
    return 1;
}   

function ProcesarLote($data, $fila, $tipo){

    if ($tipo<=2){

        if (!$data) {
            //sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }

        $codlote = $data["codlote"];
        $idproduct = "".Db::getInstance()->getValue("SELECT id_product from aalv_product where reference='".$codlote."'");

        if ($idproduct!=""){

            $idbundleproduct = "".Db::getInstance()->getValue("SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product=".$idproduct);
            if ($idbundleproduct==""){
                //crear en aalv_wk_bundle_product
                Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_product(id_ps_product, price_type, tax_type, discount, price, decrease_quantity) VALUES (".$idproduct.",1,1,0,0,1)");
                $idbundleproduct = Db::getInstance()->Insert_ID();
                Db::getInstance()->Execute("INSERT INTO aalv_wk_bundle_product_shop(id_wk_bundle_product, id_shop) VALUES (".$idbundleproduct.",1)");
                Db::getInstance()->Execute("INSERT INTO aalv_lote_import(idlote, bundle_product) VALUES (".$data["idlote"].",".$idbundleproduct.")");
            }
            


        }    


    }
    else{


    }    
     return 1;
}   



function addlog($message){
    $d = new DateTime();
    $stdout = fopen(dirname(__FILE__).'/logintegracion.txt', 'a');
    fwrite($stdout, $d->format("Y-m-d\TH:i:sP")." ".$message);    
    fwrite($stdout, "\n"); 
    fclose($stdout);    
}


function peticionget($url){
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function sendmail2($mensaje){

    
    $dest = [];
    $dest[] = "pferrando@addis.es";
    

    $data=['{message}'=>$mensaje];
    Mail::Send(    1,
                    'integracion',
                    "Integracion",
                    $data,
                    $dest,
                    Configuration::get('PS_SHOP_NAME'),
                    null,
                    null,
                    [],
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    1
                );
}


function sendmail($mensaje){

    
    $dest = [];
    $dest[] = "pferrando@addis.es";
    $dest[] = "jdomingo@addis.es";
    $dest[] = "alvarez@alsernet.es";
    
    


    $data=['{message}'=>$mensaje];
    Mail::Send(    1,
                    'integracion',
                    "Integracion",
                    $data,
                    $dest,
                    Configuration::get('PS_SHOP_NAME'),
                    null,
                    null,
                    [],
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    1
                );
}

function sendmailhora($mensaje){

    
    $minutos = (int)date('i');

    if ($minutos<10){


        $dest = [];
        $dest[] = "pferrando@addis.es";
        //$dest[] = "jdomingo@addis.es";
        //$dest[] = "alvarez@alsernet.es";



        $data=['{message}'=>$mensaje];
        Mail::Send(    1,
                        'integracion',
                        "Integracion",
                        $data,
                        $dest,
                        Configuration::get('PS_SHOP_NAME'),
                        null,
                        null,
                        [],
                        null,
                        _PS_MAIL_DIR_,
                        false,
                        1
                    );

    }
}


function escomun2($id_cat){

    $existecomun = "". Db::getInstance()->getValue("SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat=".$id_cat);

   
    if ($existecomun!=""){
        return true;
    }
    else{
        return false;
    }



}


function ExistePathCategory2($producto,  $id_cat){

 
  if (($id_cat>2) && (!escomun2($id_cat)) && ($id_cat!=2821)  && ($id_cat!=2820)) {
     
   $id_padre = Db::getInstance()->getValue("SELECT id_parent FROM aalv_category WHERE id_category=". $id_cat);
   $existe = "".Db::getInstance()->getValue("select id_category from aalv_category_product where id_category=".$id_cat." and id_product=".$producto); 

   if ($existe!=""){
      return ExistePathCategory2($producto, (int)$id_padre);
   }
   else{
     return false;
   }
  }
  else{
    return true;
  }

}

function ProcesarPrincipal($idmodelo){

    $idproducto = "".Db::getInstance()->getValue("SELECT id_product FROM aalv_product_import WHERE id_modelo=".$idmodelo);
    if ($idproducto!=""){

        $datos = "".Db::getInstance()->getValue("SELECT data  FROM aalv_integracion_cambios WHERE tabla LIKE '%perfiles_nav%' AND data LIKE '%\"id_modelo\":".$idmodelo.",%' and data like '%true%' order by id desc");

        if ($datos!=""){

            $valores = json_decode($datos, true);
     
            
            $catimport = Db::getInstance()->ExecuteS("SELECT * FROM aalv_category_import WHERE id_origen=". $valores['id_valor']);

            $ramasexisten = 0;


            foreach($catimport as $catim){
                $idcatps = $catim["id_cat"];    
                $idnav = $catim["id_nav"];    


                $existe= "".Db::getInstance()->getValue("SELECT id_category FROM aalv_category_product WHERE id_category = ".$idcatps." and id_product=".$idproducto);

                if ($existe!=""){
        
                        if (ExistePathCategory($idproducto, $idnav)){
                            $ramasexisten = $ramasexisten +1;
                        }
                }        

            }


                
            foreach($catimport as $catim){
                $idcatps = $catim["id_cat"];    
                $idnav = $catim["id_nav"];    


                $existe= "".Db::getInstance()->getValue("SELECT id_category FROM aalv_category_product WHERE id_category = ".$idcatps." and id_product=".$idproducto);

                if ($existe!=""){
        
                        if (ExistePathCategory($idproducto, $idnav)){
                            if (escomunrec($idcatps)){

                                $cat = new Category($idcatps);
                                if ($cat->sport==5){
                                    Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$idcatps." where id_product=".$idproducto);
                                    Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$idcatps." where id_product=".$idproducto);
                                  
                                }
                            }
                            else{

                                    if ($ramasexisten>1){
                                        $cat2 = new Category($idcatps);
                                        if ($cat2->sport==5){
                                            Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$idcatps." where id_product=".$idproducto);
                                            Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$idcatps." where id_product=".$idproducto);
                                        }      

                                    }
                                    else{
                                        Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$idcatps." where id_product=".$idproducto);
                                        Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$idcatps." where id_product=".$idproducto);
                                    }



                                    //Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$idcatps." where id_product=".$idproducto);
                                    //Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$idcatps." where id_product=".$idproducto);
                                
                                }
                        }         
                }
            }    






            /*    

            $rowscat = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_origen = ".$caterp);

            if ($rowscat){

                foreach($rowscat as $rowscatitem){

                    $cat = $rowscatitem["id_cat"];

                    if  (ExistePathCategory2($idproducto,  $cat)){
                    


                        if (escomunrec($cat)){

                            $cat2 = new Category($cat);
                            if ($cat2->sport==5){

                                Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$cat." where id_product=".$idproducto);
                                Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$cat." where id_product=".$idproducto);
                                        
                            }
                        }
                        else{


                            if (count($rowscat)>1){
                                $cat2 = new Category($cat);
                                if ($cat2->sport==5){
                                    Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$cat." where id_product=".$idproducto);
                                    Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$cat." where id_product=".$idproducto);
                                }      

                            }
                            else{
                                    Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$cat." where id_product=".$idproducto);
                                    Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$cat." where id_product=".$idproducto);
                            }

                        }    
                        
                    }
          
                }

            }*/
      
        }

        
        //borrado de las no todo path
        $rows = Db::getInstance()->ExecuteS("select * from aalv_category_product where id_product=".$idproducto);
        foreach($rows as $row){
          $id_categoryps = $row["id_category"];
          if (!ExistePathCategory2($idproducto, $id_categoryps)){
              if (!escomunrec($id_categoryps)){
                Db::getInstance()->Execute("delete from aalv_category_product where id_category=".$id_categoryps. " and id_product=".$idproducto);
              //Db::getInstance()->Execute("delete from aalv_category_product_import where id_category=".$id_categoryps. " and id_product=".$idproducto);
             } 
          }
        } 
        
    }    
}



/*$idlineatrans = "3977514,3977516,3977517,3977518,3977519,3977630,3977632,3977633,3977634,3977635,3977749,3977751,3977752,3977753,3977754,3977866,3977868,3977869,3977870,3977871,3977984,3977986,3977987,3977988,3977989,3978113,3978115,3978116,3978117,3978118,3978233,3978235,3978236,3978237,3978238,3978358,3978360,3978361,3978362,3978363,3978482,3978484,3978485,3978486,3978487,3978599,3978601,3978602,3978603,3978604,3978717,3978719,3978720,3978721,3978722,3978833,3978835,3978836,3978837,3978838,3978955,3978957,3978958,3978959,3978960,3979079,3979081,3979082,3979083,3979084,3979225,3979227,3979228,3979229,3979230,3979350,3979352,3979353,3979354,3979355,3979476,3979478,3979479,3979480,3979481,3979595,3979597,3979598,3979599,3979600,3979714,3979716,3979717,3979718,3979719,3979837,3979839,3979840,3979841,3979842,3982583,3982585,3982586,3982587,3982588,3982772,3982774,3982775,3982776,3982777,3983369,3983371,3983372,3983373,3983374,3983588,3983590,3983591,3983592,3983593,3983851,3983853,3983854,3983855,3983856,3984038,3984040,3984041,3984042,3984043,3984246,3984248,3984249,3984250,3984251,3984626,3984628,3984629,3984630,3984631,3985106,3985108,3985109,3985110,3985111,3985233,3985235,3985236,3985237,3985238,3985512,3985514,3985515,3985516,3985517,3985633,3985635,3985636,3985637,3985638,3986306,3986308,3986309,3986310,3986311,3986529,3986531,3986532,3986533,3986534,3986667,3986669,3986670,3986671,3986672,3986790,3986792,3986793,3986794,3986795,3986964,3986966,3986967,3986968,3986969,3987200,3987202,3987203,3987204,3987205,3987443,3987445,3987446,3987447,3987448,3987584,3987586,3987587,3987588,3987589,3987731,3987733,3987734,3987735,3987736,3987857,3987859,3987860,3987861,3987862,4019107,4019109,4019110,4019111,4019112,4019242,4019244,4019245,4019246,4019247,4019378,4019380,4019381,4019382,4019383,4019521,4019523,4019524,4019525,4019526,4019638,4019640,4019641,4019642,4019643,4019843,4019845,4019846,4019847,4019848,4019968,4019970,4019971,4019972,4019973,4020094,4020096,4020097,4020098,4020099,4020209,4020211,4020212,4020213,4020214,4020333,4020335,4020336,4020337,4020338,4020449,4020451,4020452,4020453,4020454,4020566,4020568,4020569,4020570,4020571,4020683,4020685,4020686,4020687,4020688,4020799,4020801,4020802,4020803,4020804,4020922,4020924,4020925,4020926,4020927,4021046,4021048,4021049,4021050,4021051,4021167,4021169,4021170,4021171,4021172,4021282,4021284,4021285,4021286,4021287,4021569,4021571,4021572,4021573,4021574,4021710,4021712,4021713,4021714,4021715,4021825,4021827,4021828,4021829,4021830,4021944,4021946,4021947,4021948,4021949,4022066,4022068,4022069,4022070,4022071,4023185,4023187,4023188,4023189,4023190,4023630,4023632,4023633,4023634,4023635,4023752,4023754,4023755,4023756,4023757,4023867,4023869,4023870,4023871,4023872,4024009,4024011,4024012,4024013,4024014,4024127,4024129,4024130,4024131,4024132,4024424,4024426,4024427,4024428,4024429,4024596,4024598,4024599,4024600,4024601,4024731,4024733,4024734,4024735,4024736,4024851,4024853,4024854,4024855,4024856,4024969,4024971,4024972,4024973,4024974,4025655,4025657,4025658,4025659,4025660";
*/


/*
$sql="SELECT id FROM `aalv_integracion_cambios` WHERE `transaccion` in ('2.41.1161410','2.1.1160930','8.45.1186219','8.28.1185837','3.6.1181036','3.15.1181070','8.41.1186392','8.24.1186148','8.8.1186520','9.31.1179193','8.23.1186326','8.37.1186502','3.16.1180586','3.45.1181006',
'1.27.1021707','2.7.1161426','9.33.1178800','2.26.1161912','2.26.1161634','4.41.1179289','2.19.1161850') order by 1";






$rows = Db::getInstance()->executeS($sql);

$idtrans = [];
foreach($rows as $row){
    $idtrans[]=$row["id"];
}
*/

$idlineatrans="4391547,4391548,4391549,4391560";



$idtrans = explode(",", $idlineatrans);


foreach($idtrans as $i){

//for ($i = 1405484; $i <= 1405530; $i++) {
    
$valoresitem = Db::getInstance()->getRow("select * from aalv_integracion_cambios where id=".$i);

echo $valoresitem["data"];

$valoresitem["data"] = str_replace("\u00c1" , "Á", $valoresitem["data"]);
$valoresitem["data"] = str_replace("\u00d1" , "Ñ", $valoresitem["data"]);
$valoresitem["data"] = str_replace("\u00d3" , "Ó", $valoresitem["data"]);

$valoresitem["fila"] = (int)$valoresitem["fila"];
$valoresitem["tipo"] = (int)$valoresitem["tipo"];
$valoresitem["data"] = json_decode($valoresitem["data"], true);

                        
ProcesarValores($valoresitem);
 
}                        

//ProcesarPrincipal(100033779);

echo "acaba";