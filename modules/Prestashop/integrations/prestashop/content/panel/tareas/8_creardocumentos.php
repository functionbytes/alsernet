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

function CrearDocumento($doc, $dbh)
{

    $id_attachment = ''.Db::getInstance()->getValue('SELECT id_attachment FROM aalv_attachment_import WHERE id_origen='.$doc['id']);

    if ($id_attachment == '') {

        $url = 'http://docs.a-alvarez.com/'.trim($doc['contenido']);
        Tools::copy($url, _PS_UPLOAD_DIR_.trim($doc['contenido']));

        // Db::getInstance()->Execute("INSERT INTO aalv_ayudas(titulo, texto, enlace, activo, idorigen) VALUES ('".$titulo."','".$texto."','".$enlace."',".$activo.",".$ayuda["id"].")");

        $attach = new AttachmentCore;
        $attach->name[1] = substr($doc['titulo'], 0, 32);
        $attach->description[1] = $doc['idioma'];
        $attach->mime = get_mime_type(trim($doc['contenido']));
        $attach->file_name = trim($doc['contenido']);
        $attach->file_size = filesize(_PS_UPLOAD_DIR_.trim($doc['contenido']));
        $uniqid = sha1(microtime());
        Tools::copy(_PS_UPLOAD_DIR_.trim($doc['contenido']), _PS_DOWNLOAD_DIR_.$uniqid);
        unlink(_PS_UPLOAD_DIR_.trim($doc['contenido']));
        $attach->file = $uniqid;
        $attach->add();

        $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$doc['id_modelo']);
        if ($idproduct == '') {
            $idproduct = '0';
        } else {
            $attach->attachProduct($idproduct);
        }

        Db::getInstance()->Execute('INSERT INTO aalv_attachment_import(id_attachment, id_origen, id_product) VALUES ('.$attach->id.','.$doc['id'].','.$idproduct.')');

    }

    usleep(250);

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarezps_migracion_db';
    $dbh = new PDO($dsn, 'alvarezps_migracion_dbu', 'eyb54%X45');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_attachment_lang');
Db::getInstance()->Execute('truncate table aalv_attachment');
Db::getInstance()->Execute('truncate table aalv_product_attachment');
Db::getInstance()->Execute('truncate table aalv_attachment_import');

$rows = getdatarows($dbh, 'SELECT id,titulo, contenido, idioma, id_modelo FROM modelo_documentos');
foreach ($rows as $row) {
    CrearDocumento($row, $dbh);
}

echo 'Proceso acabado';
