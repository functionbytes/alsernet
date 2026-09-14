<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
// include _PS_ADMIN_DIR_.'/../init.php';

/*

email:   alvareztest@addis.es
pass:     Testing2202

imap.serviciodecorreo.es
SSL 993

*/

use PhpOffice\PhpSpreadsheet\IOFactory;

const PATTERN_ENVIALIA = '/.*\@envialia\.com.*/i';
const PATTERN_CORREOSEXPRESS = '/.*\@correosexpress\.com.*/i';

// const PATTERN_CORREOSEXPRESS = '/.*\@addis\.es.*/i';

const PATTERN_TOURLINEEXPRESS = '/.*\@tourlineexpress\.com.*/i';

const PATTERN_ORDER_REFERENCE = '/^AC\/([0-9]*)\/([0-9]*)$/';
const PATTERN_ENVIALIA_ORDER_REFERENCE = '/^651([0-9]*)$/';

const MAIL_BOX_PROCESSEDBOX = 'INBOX/PROCESSEDBOX';
const MAIL_BOX_NOTPROCESSEDBOX = 'INBOX/NOTPROCESSEDBOX';
const MAIL_BOX_NOTIFICATIONS = 'INBOX/NOTIFICATIONS';
const MAIL_BOX_UNKNOWN = 'INBOX/UNKNOWN';

const ENTREGADO = 1;
const DEVUELTO = 9;
const EN_CURSO = 2;

function moveMail($rInBox, $idMsg, $sFolder)
{
    if (empty($idMsg)) {
        return false;
    }
    if (empty($sFolder)) {
        return false;
    }
    if (empty($rInBox)) {
        return false;
    }
    imap_mail_move($rInBox, $idMsg, $sFolder);

    return true;
}

function saveAttachments($rInBox, $idMsg)
{

    global $sTmpPath;

    $oStructure = imap_fetchstructure($rInBox, $idMsg);
    $aAttachments = [];
    $iCount = count($oStructure->parts);
    if (isset($oStructure->parts) && $iCount) {
        for ($i = 0; $i < $iCount; $i++) {
            $aAttachments[$i] = [
                'is_attachment' => false,
                'filename' => '',
                'attachment' => ''];

            if ($oStructure->parts[$i]->ifdparameters) {
                foreach ($oStructure->parts[$i]->dparameters as $object) {
                    if (strtolower($object->attribute) == 'filename') {
                        $aAttachments[$i]['is_attachment'] = true;
                        $aAttachments[$i]['filename'] = $object->value;
                    }
                }
            }

            if ($oStructure->parts[$i]->ifparameters) {
                foreach ($oStructure->parts[$i]->parameters as $object) {
                    if (strtolower($object->attribute) == 'name') {
                        $aAttachments[$i]['is_attachment'] = true;
                        $aAttachments[$i]['filename'] = $object->value;
                    }
                }
            }

            if (! empty($aAttachments[$i]['is_attachment'])) {
                $aAttachments[$i]['attachment'] = imap_fetchbody($rInBox, $idMsg, $i + 1);
                if ($oStructure->parts[$i]->encoding == 3) { // 3 = BASE64
                    $aAttachments[$i]['attachment'] = base64_decode($aAttachments[$i]['attachment']);
                } elseif ($oStructure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                    $aAttachments[$i]['attachment'] = quoted_printable_decode($aAttachments[$i]['attachment']);
                }
            }
        }
    }

    $saved_files = [];
    if (count($aAttachments) != 0) {
        foreach ($aAttachments as $at) {
            if ($at['is_attachment'] == 1) {
                file_put_contents($sTmpPath.$at['filename'], $at['attachment']);
                $saved_files[] = $at['filename'];
            }
        }

    }

    return $saved_files;
}

function getStateFromCorreosExpress($state)
{

    $return = '';

    switch ($state) {
        case '9937': $return = ENTREGADO;
            break;
        case '9936': $return = DEVUELTO;
            break;
        default: $return = EN_CURSO;
            break;
    }

    return $return;
}

function getStateFromEnvialia($state)
{
    $return = '';

    switch ($state) {
        case '4': $return = ENTREGADO;
            break;
        case '5': $return = DEVUELTO;
            break;
        case '13': $return = DEVUELTO;
            break;
        default: $return = EN_CURSO;
            break;
    }

    return $return;
}

function processCorreosExpressMail($rInBox, $oMsg)
{
    global $sTmpPath;

    $bReturn = false;
    $bAttachments = false;
    // echo 1;
    // Buscar los ficheros adjuntos
    $aAttachments = saveAttachments($rInBox, $oMsg->msgno);
    // dump($aAttachments);
    // echo 2;
    if (! empty($aAttachments)) {
        foreach ($aAttachments as $file) {
            $sFile = $sTmpPath.$file;
            $fileType = end(explode('.', $file));

            if (is_file($sFile)) { // && in_array($fileType, ws_Chrono::FILE_TYPES)) {

                // echo 3;

                $oSpreadsheet = IOFactory::load($sFile);
                $aData = $oSpreadsheet->getActiveSheet()->toArray(null, true, true, true);

                foreach ($aData as $aRow) {
                    if (! empty($aRow['E']) && preg_match(PATTERN_ORDER_REFERENCE, $aRow['E'])) {
                        if (getStateFromCorreosExpress($aRow['R']) == ENTREGADO && $aRow['D'] != '999999999') { // Los pedidos entregados (NO DEVOLUCIONES) se guardan tambien en otro sitio
                            $numero_pedido = $aRow['E'];
                            $numero_envio = $aRow['A'];
                            $fecha = DateTime::createFromFormat('d/m/Y H:i', $aRow['T'])->format('Y-m-d H:i:s');
                            $transportista = 'chrono'; // Se deja con el nombre viejo por Retrocompatibilidad
                            $remitente = $aRow['D'];

                            Db::getInstance()->Execute("INSERT INTO log_transportista(numero_pedido, numero_envio, fecha, transportista, remitente) VALUES ('".$numero_pedido."','".$numero_envio."','".$fecha."','".$transportista."','".$remitente."')");

                            // 20230727 - Si tenemos el ID del pedido Web, se cambia el estado del pedido a "Entregado"
                            $id_pedido_web = Db::getInstance()->getValue("SELECT id_internet FROM seguimiento_pedidos WHERE referencia_transportista = '".$numero_pedido."';");
                            if (! empty($id_pedido_web)) {
                                Db::getInstance()->Execute('UPDATE aalv_orders SET current_state = '._PSALV_ORDER_STATUS_DELIVERED_.' WHERE id_order = '.$id_pedido_web.';');
                                Db::getInstance()->Execute('INSERT INTO aalv_order_history(id_employee, id_order, id_order_state, date_add) VALUES ('._PSALV_EMPLOYEE_ERP_.', '.$id_pedido_web.', '._PSALV_ORDER_STATUS_DELIVERED_.", '".$fecha."');");
                            }
                        }

                        $numero_pedido = $aRow['E'];
                        $numero_envio = $aRow['A'];
                        $fecha = DateTime::createFromFormat('d/m/Y H:i', $aRow['T'])->format('Y-m-d H:i:s');
                        $transportista = 'chrono'; // Se deja con el nombre viejo por Retrocompatibilidad
                        $remitente = $aRow['D'];
                        $estado = getStateFromCorreosExpress($aRow['R']);
                        $estado_descripcion = $aRow['S'];
                        Db::getInstance()->Execute("INSERT INTO log_transportista_tracking(numero_pedido, numero_envio, fecha, transportista, remitente, estado, estado_descripcion) VALUES ('".$numero_pedido."','".$numero_envio."','".$fecha."','".$transportista."','".$remitente."',".$estado.",'".$estado_descripcion."')");

                        $bReturn = true;
                    }
                }

                // Borrar los ficheros procesados
                unlink($sFile);
            }
        }
    }

    if ($bReturn) {
        moveMail($rInBox, $oMsg->msgno, MAIL_BOX_PROCESSEDBOX);
    } elseif (! $bAttachments) {
        moveMail($rInBox, $oMsg->msgno, MAIL_BOX_NOTPROCESSEDBOX);
    }

    return $bReturn;
}

function vercorreo($rInBox)
{

    $oImapCheck = imap_check($rInBox);
    echo $oImapCheck->Nmsgs.' mensajes';

    if ($oImapCheck->Nmsgs > 0) {
        $aMessages = imap_fetch_overview($rInBox, "1:{$oImapCheck->Nmsgs}", 0);

        foreach ($aMessages as $oMessage) {
            echo '#'.$oMessage->msgno.' - '.date('d-m-Y H:i:s', strtotime($oMessage->date)).': ';

            // dump($oMessage);
            // Tratar cada uno de los eMails

            if (preg_match(PATTERN_CORREOSEXPRESS, $oMessage->from)) {

                echo processCorreosExpressMail($rInBox, $oMessage).' '.'CorreosExpress';
            } elseif (preg_match(PATTERN_ENVIALIA, $oMessage->from)) {
                echo processEnvialiaMail($oMessage).' '.'Envialia';
            } elseif (preg_match(PATTERN_TOURLINEEXPRESS, $oMessage->from)) {
                echo processTourlineMail($oMessage).' '.'TourlineExpress';
            } else {
                moveMail($rInBox, $oMessage->msgno, MAIL_BOX_UNKNOWN);
                echo 'Remitente no encontrado';
            }

        }
    }

}

// transalvaddis@a-alvarez.com
// usuario=alvarez-transalvaddis
// clave=May.403605

$sTmpPath = __DIR__.'/tmp/';
// $rInBox = imap_open("{imap.serviciodecorreo.es:993/imap/ssl/novalidate-cert}INBOX", "alvareztest@addis.es", "Testing2202");
// $this->rInBox = imap_open("{correo.alsernet.es:143/novalidate-cert}INBOX", "alsernet-transportesalv", "Feb.900919") or die("no se puede conectar: " . imap_last_error());
// $rInBox = imap_open("{correo.alsernet.es:143/novalidate-cert}INBOX", "alvarez-transalvaddis", "May.403605");
$rInBox = imap_open('{correoclientes.alsernet.es:993/ssl/novalidate-cert}INBOX', 'a-alvarez-transalvaddis', 'Jun.036271');
vercorreo($rInBox);
imap_close($rInBox, CL_EXPUNGE);
