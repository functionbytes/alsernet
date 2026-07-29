<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

header('Content-Type: text/plain');

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

function CrearReviews($pr, $dbh, $i)
{

    $lang = ''.$pr['language'];
    $idlang = 1;
    if ($lang == 'en') {
        $idlang = 2;

    }

    // Db::getInstance()->Execute("INSERT INTO aalv_lgcomments_storecomments(id_order, id_customer, id_lang, stars, nick, title, comment, answer, active, position, `date`) VALUES (0,0,".$idlang.",".($pr["stars"]*2).",'".$pr["client_name"]."','".$pr["title"]."','".str_replace("'","´",$pr["opinion"])."','".$pr["response_text"]."',".$pr["approved"].",".$i.",'".$pr["date"]."')");
    Db::getInstance()->Execute('INSERT INTO aalv_lgcomments_storecomments(id_order, id_customer, id_lang, stars, nick, title, comment, answer, active, position, `date`) VALUES (0,0,'.$idlang.','.($pr['stars'] * 2).",'".$pr['client_name']."','".$pr['title']."','".pSQL($pr['opinion'])."','".$pr['response_text']."',".$pr['approved'].','.$i.",'".$pr['date']."')");

    /*
                // JLP - 28/07/2022 - Si es un comentario de Google Reseñas lo inserto en la tabla de comentarios de Google
                if (endsWith($pr['client_email'], '@google.com')) {
                    $id_store = 0;
                    if (!strpos($pr['origin'], 'CORUÑA') === false) {
                        $id_store = 8;
                    } elseif (!strpos($pr['origin'], 'DIEGO') === false) {
                        $id_store = 7;
                    } elseif (!strpos($pr['origin'], 'HAYA') === false) {
                        $id_store = 6;
                    }

                    $sql = 'INSERT INTO `'._DB_PREFIX_.'google_reviews_import` (`id_store`, `rating`, `author_name`, `author_url`, `author_email`, `language`, `title`, `review`, `date`, `source`)
                            VALUES ('.$id_store.', '.(float) $pr['stars'].', \''.$pr['client_name'].'\', \'\', \''.$pr['client_email'].'\', \''.$pr['language'].'\', \''.$pr['title'].'\', \''.pSQL($pr['opinion']).'\', \''.$pr['date'].'\', \''.$pr['origin'].'\')';
                    if (Db::getInstance()->execute($sql)) {
                        echo 'Opinión insertado en tabla de opiniones de Google'.PHP_EOL;
                    } else {
                        echo $sql.PHP_EOL;
                        echo 'ERROR. Opinión no insertada en tabla de opiniones de Google'.PHP_EOL;
                    }
                }
      */

}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length > 0 ? substr($haystack, -$length) === $needle : true;
}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_lgcomments_storecomments');
// Db::getInstance()->Execute('truncate table `'._DB_PREFIX_.'google_reviews_import`');

$rows = getdatarows($dbh, 'SELECT * FROM product_reviews where model_id is null order by `date` asc');
$i = 1;
foreach ($rows as $row) {
    CrearReviews($row, $dbh, $i);
    $i = $i + 1;
}

echo 'Proceso acabado';
