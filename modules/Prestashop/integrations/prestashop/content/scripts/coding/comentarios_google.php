<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
include ('/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php');

$reviews = Db::getInstance()->executeS("select agri.id_google_reviews_import, agri.id_storecomment AS id_storecomment_agri, als.id_storecomment AS id_storecomment_als from aalv_google_reviews_import agri
left join aalv_lgcomments_storecomments als on als.nick = agri.author_name
where
als.`date` = agri.`date`
and agri.review = als.comment
and agri.id_storecomment is null
order by id_google_reviews_import desc");

foreach ($reviews as $value) {
    # code...
    // dump($value);
    DB::getInstance()->execute("UPDATE aalv_google_reviews_import SET id_storecomment = ".$value['id_storecomment_als']." WHERE id_google_reviews_import =".$value['id_google_reviews_import']);
    // die();
}


$dbcon = connectBD();

$google = Db::getInstance()->executeS("select * from aalv_google_reviews_import order by id_google_reviews_import DESC");

foreach ($google as $value) {
    # code...
    $sql_antigua = "select approved from product_reviews where client_email = '".$value['author_email']."' and `date` = '".$value['date']."' and origin = '".$value['source']."'";

    $result_antigua = mysqli_query($dbcon, $sql_antigua);

    $re_antigua = mysqli_fetch_array($result_antigua);

    $lgcomments = Db::getInstance()->executeS("select * from aalv_lgcomments_storecomments where id_storecomment = ".$value['id_storecomment']);

    if($re_antigua['approved'] != $lgcomments[0]['active']){
        dump($sql_antigua);
        dump($re_antigua['approved']);
        dump($lgcomments);
        // die();
        DB::getInstance()->execute("UPDATE aalv_lgcomments_storecomments SET active = ".$re_antigua['approved']." WHERE id_storecomment = ".$value['id_storecomment']);
        // die();
    }

}
