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

function RellenarProductQuestions($datos, $dbh)
{

    try {

        $id_model = $datos['model_id'];

        $id_product = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$id_model);

        if ($id_product != '') {

            $parent_id = 0;
            $origin_parent_id = ''.$datos['parent_id'];
            if ($origin_parent_id != '') {
                $parent_id = ''.Db::getInstance()->getValue('SELECT id FROM aalv_product_questions_import WHERE id_origen='.$origin_parent_id);
            }

            $response_date = ''.$datos['response_date'];
            if ($response_date == '') {
                $response_date = 'null';
            } else {
                $response_date = "'".$response_date."'";
            }

            $mail_sent = ''.$datos['mail_sent'];
            if ($mail_sent == '') {
                $mail_sent = 'null';
            } else {
                $mail_sent = "'".$mail_sent."'";
            }

            Db::getInstance()->Execute('INSERT INTO aalv_product_questions(product_id, parent_id, question, date_question, client_name, client_email, id_lang, response_text, response_date, approved, removed, mail_sent) VALUES ('.$id_product.','.$parent_id.",'".$datos['question']."','".$datos['date']."','".$datos['client_name']."','".$datos['client_email']."',1,'".$datos['response_text']."',".$response_date.','.$datos['approved'].','.$datos['removed'].','.$mail_sent.')');

            $idnewpq = (int) Db::getInstance()->Insert_ID();

            if ($idnewpq != 0) {
                Db::getInstance()->Execute('INSERT INTO aalv_product_questions_import(id, id_origen) VALUES ('.$idnewpq.','.$datos['id'].')');
            }

        } else {
            $d = new DateTime;
            $stdout = fopen(dirname(__FILE__).'/productquestionserrores.txt', 'a');
            fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error No existe el producto');
            fwrite($stdout, "\n");
            fwrite($stdout, ' --- datos '.$datos[0]);
            fwrite($stdout, "\n");
            fclose($stdout);

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/productquestionserrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_product_questions');
Db::getInstance()->Execute('truncate table aalv_product_questions_import');

$rows = getdatarows($dbh, 'SELECT * FROM product_questions');
foreach ($rows as $row) {
    RellenarProductQuestions($row, $dbh);
}
echo 'Acaba';
