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

function CrearVideo($doc, $dbh)
{

    $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$doc['id_modelo']);

    if ($idproduct != '') {

        $idvideo = str_replace('https://www.youtube.com/embed/', '', $doc['contenido']);
        $idvideo = str_replace('https://youtu.be/', '', $idvideo);
        $idvideo = trim($idvideo);

        Db::getInstance()->Execute('INSERT INTO aalv_product_videos(id_product, id_video, title, provider, video_url, position, id_lang, id_shop) VALUES ('.$idproduct.",'".$idvideo."','','youtube','".$doc['contenido']."',".$doc['orden'].',1,1)');
        $idproductvideo = (int) Db::getInstance()->Insert_ID();
        if ($idproductvideo != 0) {
            Db::getInstance()->Execute('INSERT INTO aalv_video_import(id_productvideo, id_origen) VALUES ('.$idproductvideo.','.$doc['id'].')');
        }

        Db::getInstance()->Execute('INSERT INTO aalv_product_videos(id_product, id_video, title, provider, video_url, position, id_lang, id_shop) VALUES ('.$idproduct.",'".$idvideo."','','youtube','".$doc['contenido']."',".$doc['orden'].',2,1)');
        $idproductvideo = (int) Db::getInstance()->Insert_ID();
        if ($idproductvideo != 0) {
            Db::getInstance()->Execute('INSERT INTO aalv_video_import(id_productvideo, id_origen) VALUES ('.$idproductvideo.','.$doc['id'].')');
        }

        Db::getInstance()->Execute('INSERT INTO aalv_product_videos(id_product, id_video, title, provider, video_url, position, id_lang, id_shop) VALUES ('.$idproduct.",'".$idvideo."','','youtube','".$doc['contenido']."',".$doc['orden'].',3,1)');
        $idproductvideo = (int) Db::getInstance()->Insert_ID();
        if ($idproductvideo != 0) {
            Db::getInstance()->Execute('INSERT INTO aalv_video_import(id_productvideo, id_origen) VALUES ('.$idproductvideo.','.$doc['id'].')');
        }

        Db::getInstance()->Execute('INSERT INTO aalv_product_videos(id_product, id_video, title, provider, video_url, position, id_lang, id_shop) VALUES ('.$idproduct.",'".$idvideo."','','youtube','".$doc['contenido']."',".$doc['orden'].',4,1)');
        $idproductvideo = (int) Db::getInstance()->Insert_ID();
        if ($idproductvideo != 0) {
            Db::getInstance()->Execute('INSERT INTO aalv_video_import(id_productvideo, id_origen) VALUES ('.$idproductvideo.','.$doc['id'].')');
        }

        Db::getInstance()->Execute('INSERT INTO aalv_product_videos(id_product, id_video, title, provider, video_url, position, id_lang, id_shop) VALUES ('.$idproduct.",'".$idvideo."','','youtube','".$doc['contenido']."',".$doc['orden'].',5,1)');
        $idproductvideo = (int) Db::getInstance()->Insert_ID();
        if ($idproductvideo != 0) {
            Db::getInstance()->Execute('INSERT INTO aalv_video_import(id_productvideo, id_origen) VALUES ('.$idproductvideo.','.$doc['id'].')');
        }

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_product_videos');
Db::getInstance()->Execute('truncate table aalv_video_import');

$rows = getdatarows($dbh, "SELECT id, id_modelo, contenido, orden, activo FROM modelo_videos where contenido like '%youtu%'");
foreach ($rows as $row) {
    CrearVideo($row, $dbh);
}

echo 'Proceso acabado';
