<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

// Traemos todos los pedidos del ultimo mes
$datos = Db::getInstance()->ExecuteS('select id_order from aalv_orders WHERE id_order not in (783136,781727) and date_add >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) order by id_order DESC');
// $datos = Db::getInstance()->ExecuteS("select id_order from aalv_orders WHERE id_order = 785579 order by id_order DESC");

$options = [
    'http' => [
        'method' => 'GET',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
    ],
];

$context = stream_context_create($options);

foreach ($datos as $value) {
    // Buscamos los datos de Gestion
    $contentconfirm = @file_get_contents('http://127.0.0.1:58002/api-gestion/pedido-cliente-hist/?identificadororigen='.$value['id_order'], false, $context);

    if ($contentconfirm === false) {
        continue;
    }

    $jsonconfirmData = simplexml_load_string($contentconfirm);
    $datos = json_encode($jsonconfirmData);
    $array = json_decode($datos, true);
    // dump($array);die();
    if (isset($array['resource']) && count($array['resource']) == 0) {
        continue;
    }

    if (! isset($array['resource'][0])) {
        $array['resource'] = [0 => $array['resource']];
    }

    foreach ($array['resource'] as $key => $val) {

        $ps_estado = 'NN';
        switch ($val['estado']) {
            case 0: // ESTADO GESTIÓN ANULADO - Gestion
                $ps_estado = 6; // Pedido Cancelado - PS
                break;
            case 1: // ESTADO GESTIÓN EN CREACIÓN - Gestion
            case 2: // ESTADO GESTIÓN REVISIÓN TRANSPORTISTA - Gestion
            case 3: // ESTADO GESTIÓN ACEPTACIÓN FINANCIERA
            case 4: // ESTADO GESTIÓN PENDIENTE DE MERCANCÍA - Gestion
            case 8: // ESTADO GESTIÓN INCIDENCIA - Gestion
            case 9: // ESTADO GESTIÓN ACEPTACIÓN FINANCIERA RESERVANDO
                $ps_estado = 27; // Pedido en ERP (Para el usuario cambia el nombre a "Preparación en curso") - PS
                break;
            case 5: // ESTADO GESTIÓN LISTO PARA SERVIR
            case 6: // ESTADO GESTIÓN SIRVIÉNDOSE
                $ps_estado = 59; // Sirviéndose
                break;
            case 7: // ESTADO GESTIÓN SERVIDO
                $ps_estado = 4; // Enviado - PS // Codigo traking
                break;
            case 10: // ESTADO GESTIÓN SERVIDO PARCIALMENTE
                $ps_estado = 60; // Codigo traking
                break;

            default:
                $ps_estado = 'NN';
                break;
        }

        if ($ps_estado == 'NN') {
            // $data = ['{message}' => "El estado nuevo es => ".$estado];
            // Mail::Send(
            //     1,
            //     'integracion',
            //     "Estados de Gestion que no existen en PS",
            //     $data,
            //     "alvarez@alsernet.es",
            //     Configuration::get('PS_SHOP_NAME'),
            //     'desarrollotest@a-alvarez.net',
            //     'desarrollotest',
            //     [],
            //     null,
            //     _PS_MAIL_DIR_,
            //     false,
            //     1
            // );
            dump('aaaa');
            exit();
            // continue;
        } elseif ($ps_estado == 27) {
            // No registramos los estado 27 en PS porque ya estan cargados.
            continue;
        }

        // Sacamos la fecha para modificarla
        $fecha2 = new DateTime($val['fecha']);

        // Sumar 5 minutos a la fecha2
        $fecha2->modify('+5 minutes');

        // Buscamos si el estado ya esta guardado
        $id_history = Db::getInstance()->ExecuteS('SELECT id_order_state FROM aalv_order_history WHERE id_order = '.$value['id_order']." and date_add = '".$fecha2->format('Y-m-d H:i:s')."' and id_order_state = ".$ps_estado);

        // Verificamos que el estado no existe.
        if (count($id_history) == 0) {
            $id_historyv2 = Db::getInstance()->ExecuteS('select id_order_state,date_add from aalv_order_history where id_order = '.$value['id_order'].' order by date_add DESC limit 1');

            if ((int) $id_historyv2[0]['id_order_state'] == $ps_estado) {
                // Puede ocurrir que el estado "Sirviéndose" este mas de 1 vez consecutiva, se agrega este parametro para no agregar repetidos
                continue;
            } elseif (($ps_estado != 4 && $ps_estado != 60) && $id_historyv2[0]['date_add'] > $fecha2->format('Y-m-d H:i:s')) {
                continue;
            }

            // Buscamos los que tienen esta de seguimiento
            if ($ps_estado == 4 || $ps_estado == 60) {

                // Buscamos los datos de Gestion
                $conte = @file_get_contents('http://127.0.0.1:58002/api-gestion/pedido-cliente-tracking/?identificadororigen='.$value['id_order'], false, $context);

                if ($conte === false) {
                    // echo $value['id_order'];
                    // echo "\n\n";
                    // dump('Revisar porque no existe en tracking ');
                    // die();
                    continue;
                }

                $json = simplexml_load_string($conte);
                $info = json_encode($json);
                $arr = json_decode($info, true);

                if (isset($arr['resource'][0])) {
                    foreach ($arr['resource'] as $vall) {
                        if (date('Y-m-d', strtotime($val['fecha'])) == date('Y-m-d', strtotime($vall['fenvio']))) {
                            $arr['resource'] = $vall;
                        }
                    }
                }

                // if($arr['resource']['idtransportista'] == 100000223){
                //     continue;
                // }

                $nuevo_transportista = false;

                // Agregamos en la tabla auxiliar, los datos del transportista.
                switch ($arr['resource']['idtransportista']) {
                    case 100000283: // INPOST
                        $url = 'https://www.inpost.es/seguimiento-de-paquete/?ens=E1AALVAA&exp='.$arr['resource']['codtracking'];
                        break;

                    case 4: // SEUR
                        $url = 'https://www.seur.com/miseur/mis-envios/?lang=es&code='.$arr['resource']['codtracking'];
                        break;

                    case 100000045: // MRW
                        $url = 'https://www.mrw.es/seguimiento/envio-historico.asp?Inter=false&envio='.$arr['resource']['codtracking'];
                        break;

                    case 100000264: // ONTIME
                        $url = 'https://alina.ontime.es/ords/r/ontime/portalcliente999/url-seguimiento-consignatario?p_param1='.$arr['resource']['codtracking'].'&p_param2='.$arr['resource']['cp'];
                        break;

                    case 21: // CORREOEXPRESS
                        $url = 'https://s.correosexpress.com/SeguimientoSinCP/search?request_locale=es_ES&shippingNumber='.$arr['resource']['codtracking'];
                        break;

                    case 100000244: // RECOGIDA EN TIENDA - DIEGO DE LEON
                    case 17: // ENTREGA DIRECTA DEL PROVEEDOR
                    case 100000304: // ENVIO POR E-MAIL
                    case 100000245: // RECOGIDA EN TIENDA - CAPITAN HAYA
                    case 10: // RECOGIDA EN TIENDA - CORUÑA
                    case 24: // AGENCIA MADRID
                    case 2: // Correos
                    case 100000001: // Nacex
                    case 100000164: // CorreoExpress Standard
                        $url = '#';
                        break;

                        // case 100000223: // DBSCHENKER
                        //     $url = '#';
                        //     continue;
                        //     break;

                    default:
                        dump($val);
                        dump($arr);
                        dump($value['id_order']);
                        $nuevo_transportista = true;
                        break;
                }

                if ($nuevo_transportista) {
                    continue;
                }

                // Buscamos si ya existe el registro
                // Insertamos los datos en la tabla
                $resultado = Db::getInstance()->execute('INSERT INTO aalv_alsernet_orders_tracking VALUES (null, '.$value['id_order'].",'".$url."','".$arr['resource']['codtracking']."','".$arr['resource']['fenvio']."','".$arr['resource']['idtransportista']."')");

                if ($resultado) {
                    // Si sale todo bien, recien agregamos el estado
                    // Si existe numero de Tracking agregamos el estado
                    $order = new Order((int) $value['id_order']);

                    // Solo si aún no hay albarán
                    if ((int) $order->delivery_number === 0) {
                        // Genera número de albarán y fecha y los guarda en ps_orders
                        $order->setDelivery();

                    }
                    $order->current_state = (int) $ps_estado;
                    $order->save();

                    $history = new OrderHistory;
                    $history->id_order = (int) $order->id;
                    $history->id_employee = 24;
                    $history->id_order_state = (int) $ps_estado;
                    $history->save();
                    // Db::getInstance()->executeS("UPDATE aalv_orders SET current_state = " . $ps_estado . ", delivery_date = NOW() WHERE id_order = " . $value['id_order'] . ";");
                    // Db::getInstance()->execute("INSERT INTO aalv_order_history (id_employee, id_order, id_order_state, date_add) VALUES (24, " . $value['id_order'] . ", " . $ps_estado . ", '".$fecha2->format('Y-m-d H:i:s')."');");
                }
            } else {
                // Sino lo agregamos el estado
                // $order = new Order((int)$value['id_order']);

                // $order->current_state = (int)$ps_estado;
                // $order->save();

                // $history = new OrderHistory();
                // $history->id_order = (int)$order->id;
                // $history->id_employee = 24;
                // $history->id_order_state = (int)$ps_estado;
                // $history->save();
                Db::getInstance()->executeS('UPDATE aalv_orders SET current_state = '.$ps_estado.' WHERE id_order = '.$value['id_order'].';');
                Db::getInstance()->execute('INSERT INTO aalv_order_history (id_employee, id_order, id_order_state, date_add) VALUES (24, '.$value['id_order'].', '.$ps_estado.", '".$fecha2->format('Y-m-d H:i:s')."');");
            }
        }
    }
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
