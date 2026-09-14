<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

deleteEts_ctf_contact_message('aalv_ets_ctf_contact_message_shop', 'date_add', 30, true);
deleteEts_ctf_contact_message('aalv_ets_ctf_contact_message', 'date_add', 30);

/******************************* Mover datos a la tabla Historico *******************************/
moveToHistoric('aalv_integracion_transacciones', 'fecha_confirmacion', 30);
moveToHistoric('aalv_integracion_cambios', 'fecha_confirmacion', 30);
moveToHistoric('aalv_specific_price', 'to', 0);
moveToHistoric('aalv_tarifa_cabecera_import', 'ffin', 0);
moveToHistoric('aalv_log', 'date_add', 30);
moveToHistoric('aalv_ybc_blog_log_view', 'datetime_added', 30);

/******************************* Vasiar tablas completas *******************************/
deleteHistoric('aalv_guest');
deleteHistoric('aalv_connections');
deleteHistoricPrice('aalv_specific_price_import');

/******************************* Vasiar tablas despues de 365 dias *******************************/
deleteFinal('aalv_integracion_transacciones_historico', 'fecha_confirmacion', 365);
deleteFinal('aalv_integracion_cambios_historico', 'fecha_confirmacion', 365);
deleteFinal('aalv_log_historico', 'date_add', 365);
deleteFinal('aalv_ybc_blog_log_view_historico', 'datetime_added', 365);
deleteFinal('aalv_specific_price_historico', 'to', 365);
deleteFinal('aalv_tarifa_cabecera_import_historico', 'ffin', 365);

echo 'Listo';

function moveToHistoric($table, $dateField, $days, $limit = 6000000)
{
    $query = 'INSERT INTO alv_historico.`'.$table.'_historico`
                    SELECT
                        *
                    FROM
                        `'.$table.'`
                    WHERE
                        `'.$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)
                        AND `'.$dateField.'` != "0000-00-00 00:00:00"
                    LIMIT '.$limit;

    $stmt = Db::getInstance()->execute($query);
    // dump($stmt,Db::getInstance()->getMsgError());
    if ($stmt) {

        $queryDelete = 'DELETE FROM
                                    '.$table.'
                                WHERE
                                    `'.$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)
                                    AND `'.$dateField.'` != "0000-00-00 00:00:00"
                                LIMIT '.$limit;
        $stmtDelete = Db::getInstance()->execute($queryDelete);
        // dump($stmtDelete,Db::getInstance()->getMsgError());
    }
}

function deleteHistoric($table, $limit = 6000000)
{
    $que = 'INSERT INTO alv_historico.`'.$table.'_historico`
                    SELECT
                        *
                    FROM
                        `'.$table.'`
                    LIMIT '.$limit;

    $stm = Db::getInstance()->execute($que);

    if ($stm) {
        $queDelete = 'DELETE FROM '.$table.' LIMIT '.$limit;
        $stmtDete = Db::getInstance()->execute($queDelete);
    }
}

function deleteFinal($table, $dateField, $days)
{
    $queryDelete = ' DELETE FROM
                            alv_historico.'.$table.'
                        WHERE
                            `'.$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)
                            AND `'.$dateField.'` != "0000-00-00 00:00:00"';
    $stmtDelete = Db::getInstance()->execute($queryDelete);
    // dump($stmtDelete,Db::getInstance()->getMsgError());
}

function deleteEts_ctf_contact_message($table, $dateField, $days, $shop = false)
{
    $query = 'INSERT INTO alv_historico.`'.$table.'_historico`';

    if ($shop) {
        $query .= ' SELECT
                        *
                    FROM
                        `'.$table.'` aeccms
                    WHERE
                        aeccms.id_contact_message IN (	SELECT
                                                            aeccm.id_contact_message
                                                        FROM
                                                            `aalv_ets_ctf_contact_message` aeccm
                                                        WHERE
                                                            aeccm.`'.$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY))';
    } else {
        $query .= ' SELECT
                        *
                    FROM
                        `'.$table.'`
                    WHERE
                        `'.$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)
                        AND `'.$dateField.'` != "0000-00-00 00:00:00"';
    }

    $stmt = Db::getInstance()->execute($query);
    if ($stmt) {
        if ($shop) {
            $queryDelete = 'DELETE
                            FROM
                                `'.$table.'`
                             WHERE
                                id_contact_message IN (	SELECT
                                                            aalv_ets_ctf_contact_message.id_contact_message
                                                        FROM
                                                            `aalv_ets_ctf_contact_message`
                                                        WHERE
                                                            aalv_ets_ctf_contact_message.`'.$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY))';
        } else {
            // BUSCAMOS LOS ARCHIVOS ADJUNTOS Y LOS VAMOS BORRANDO
            $archivos = Db::getInstance()->ExecuteS('SELECT
                                                        attachments
                                                    FROM
                                                        `'.$table."`
                                                    WHERE
                                                        attachments != ''
                                                        AND `".$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)
                                                        AND `'.$dateField."` != '0000-00-00 00:00:00'");
            $nn = 0;
            foreach ($archivos as $value) {
                $separamos = explode(',', $value['attachments']);
                foreach ($separamos as $val) {
                    $archivo = dirname(__FILE__).'/../modules/ets_contactform7/views/img/etscf7_upload/'.$val;
                    try {
                        if (file_exists($archivo)) {
                            if (unlink($archivo)) {
                                echo 'El archivo fue borrado exitosamente. - ';
                                echo $archivo.'<br>';
                                $nn++;
                            } else {
                                throw new Exception('Error al borrar el archivo.');
                            }
                        }
                    } catch (Exception $e) {
                        echo 'Se produjo un error: '.$e->getMessage();
                        exit();
                    }
                    if ($nn == 50) {
                        exit();
                    }
                }
            }

            $queryDelete = 'DELETE FROM
                                    '.$table.'
                                WHERE
                                    `'.$dateField.'` < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)
                                    AND `'.$dateField.'` != "0000-00-00 00:00:00"';
        }
        // var_dump($queryDelete);die();
        $stmtDelete = Db::getInstance()->execute($queryDelete);
    }
}

function deleteHistoricPrice($table)
{
    Db::getInstance()->execute('DELETE from '.$table.' where id_tarifa_cabecera in (select atcih.id_tarifa_cabecera from alv_historico.aalv_tarifa_cabecera_import_historico atcih )');
    Db::getInstance()->execute('DELETE from '.$table.' where id_specific_price in (select asph.id_specific_price from alv_historico.aalv_specific_price_historico asph  )');
}
