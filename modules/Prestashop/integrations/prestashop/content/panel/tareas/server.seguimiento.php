<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$myServer = new XMLRPCServer;
$myServer->registerStaticMethod(new String('WebAlvarez'), new String('insertDatos'));

$myServer->request(new String($HTTP_RAW_POST_DATA));

echo $myServer->getResponse(new String('XML'))->toString();

class WebAlvarez
{
    public static function convertDate($date)
    {

        $hora = substr($date, 9, 8);
        $fecha = substr($date, 0, 4).'/'.substr($date, 4, 2).'/'.substr($date, 6, 2);
        $date = $fecha.' '.$hora;

        return $date;
    }

    public static function insertDatos($parameters)
    {

        // $headers = array("Content-type: text/plain; charset=utf-8;");
        // mail('jabalde@alsernet.es', 'Parameters seguimiento pedidos', print_r($parameters, true),implode("\r\n", $headers));

        $args = $parameters['args'][0];
        $args_lp = array_values($args['Lineas_Pedido']);
        $importe_array = [];
        $args_fp = [];
        if (is_array($args['FormaPago']) and count($args['FormaPago']) > 0) {
            $args_fp = $args['FormaPago'];
            $importe_array = array_values($args_fp);
        }

        $args_e = [];
        if (is_array($args['Envio']) and count($args['Envio']) > 0) {
            $array = array_values($args['Envio']);
            $args_e = $array[0];
        }

        $args_i = [];
        if (is_array($args['Incidencias']) and count($args['Incidencias']) > 0) {
            $array_i = array_values($args['Incidencias']);
            $args_i = $array_i[0];
        }

        $args_tlf = array_values($args['Telefonos']);

        $campos = new Vector;
        $regs = new Vector;

        $campos->add('id_gestion');
        $campos->add('serie');
        $campos->add('estado_gestion');
        // $campos->add("fecha_insercion");
        $campos->add('fecha_pedido');
        $campos->add('origen');
        $campos->add('id_cliente_gestion');
        $campos->add('nombre');
        $campos->add('apellido1');
        // $campos->add("apellido2"); // Se unifican los apellidos en un solo campo (en Gestion, 20220322)
        $campos->add('fecha_cambio_estado');

        $numero_pedido = explode('/', $args['ID_Pedido']->scalar);

        // $headers = array("Content-type: text/plain; charset=utf-8;");
        // mail('jabalde@alsernet.es', 'Consulta seguimiento pedidos ('.$args["ID_Pedido"]->scalar.')', $args["ID_Pedido"]->scalar, implode("\r\n", $headers));

        $regs->add($numero_pedido[1]);
        $regs->add($numero_pedido[0]);
        $regs->add($args['Estado']);
        // $regs->add("now()");

        $regs->add(self::convertDate($args['fecha_Ped']->scalar));
        $regs->add($args['Origen_Ped']);
        $regs->add($args['id_cliente']);

        $regs->add(addslashes($args['NombreCli']->scalar));
        $regs->add(addslashes($args['Apellidos']->scalar)); // Se unifican los apellidos en un solo campo (en Gestion, 20220322)
        // $regs->add(addslashes($args["Apellido2"]->scalar)); // Se unifican los apellidos en un solo campo (en Gestion, 20220322)
        $regs->add(self::convertDate($args['F_Cambio_Estado']->scalar));

        if (! empty($importe_array[0]['Importe'])) {
            $campos->add('total');
            $regs->add($importe_array[0]['Importe']);
        }

        if (! empty($args['Portes'])) {
            $campos->add('portes');
            $regs->add($args['Portes']);
        }

        if ($args['Origen_Ped'] == '4') {
            $campos->add('id_internet');
            $regs->add(addslashes($args['Descrip_Origen']->scalar));

            /*if (!empty($args["Descrip_Origen"]->scalar)){
                if (ereg("/", $args["Descrip_Origen"]->scalar)) $mundogar = explode("/", $args["Descrip_Origen"]->scalar);
                elseif (ereg("-", $args["Descrip_Origen"]->scalar)) $mundogar = explode("-", $args["Descrip_Origen"]->scalar);
                elseif (ereg(",", $args["Descrip_Origen"]->scalar)) $mundogar = explode(",", $args["Descrip_Origen"]->scalar);
                else $mundogar = array($args["Descrip_Origen"]->scalar);
            }else{
                $mundogar = array();
            }*/
        }

        switch ($args['Estado']) {
            case '1':
            case '2':
            case '5':
            case '6':
                $campos->add('estado_web');
                $regs->add('2');
                $estado_mundogar = '2';
                break;
            case '0':
                $campos->add('estado_web');
                $campos->add('fecha_anulacion');

                $regs->add('6');
                $regs->add(self::convertDate($args['F_Cambio_Estado']->scalar));
                $estado_mundogar = '6';
                break;
            case '7':
                $campos->add('estado_web');
                $campos->add('fecha_servido');
                $campos->add('transportista');
                $campos->add('telefono_transportista');
                $campos->add('referencia_transportista');

                if (empty($args_e)) {
                    $regs->add('1');
                    $regs->add('now()');
                    $regs->add('');
                    $regs->add('');
                    $regs->add('');
                    $estado_mundogar = '1';
                } else {
                    $regs->add('1');
                    $regs->add(self::convertDate($args_e['FEnvio']->scalar));
                    $regs->add(addslashes($args_e['Transportista']));
                    $regs->add(addslashes($args_e['Telefono']->scalar));
                    $regs->add(addslashes($args_e['Ref_Envio']->scalar));
                    $estado_mundogar = '1';
                }

                break;
            case '8':
                $tipos_incidencias_contemplados = ['1', '2', '3', '4'];
                if (in_array($args_i['Tipo'], $tipos_incidencias_contemplados)) {

                    switch ($args_i['Tipo']) {
                        case '1':
                        case '3':
                            $campos->add('estado_web');
                            $regs->add('5');
                            $campos->add('id_incidencia');
                            $regs->add($args_i['Tipo']);
                            $estado_mundogar = '5';
                            break;
                        case '2':
                        case '4':
                            $campos->add('estado_web');
                            $regs->add('4');
                            $campos->add('id_incidencia');
                            $regs->add($args_i['Tipo']);
                            $estado_mundogar = '4';
                            break;
                    }

                } else {
                    // mail("alvarez@alsernet.es", "Pedido en Incidencia", "Pedido n� ".$args["ID_Pedido"]->scalar." en Incidencia tipo ".$args_i["Tipo"]);
                    return ['status' => 'OK'];
                }
                break;
            case '3':

                $formas_pago_no_validas = ['5', '7', '10', '11', '12', '16', '21', '24', '25'];
                $formas_pago_tarjeta = ['2', '8', '9', '13', '14', '15', '17'];
                foreach ($args_fp as $key => $pago) {
                    // if (!in_array($pago["FPago"], $formas_pago_no_validas)) $valores_pago[] = $pago["FPago"];
                    $valores_pago[] = $pago['FPago'];
                }
                $forma_pago = array_unique($valores_pago);
                $forma_pago_definitiva = $forma_pago[0];
                if (count($forma_pago) > 1) {
                    $campos->add('estado_web');
                    $regs->add('3');
                    foreach ($forma_pago as $fpago) {
                        if (in_array($fpago, $formas_pago_tarjeta)) {
                            $forma_pago_definitiva = $fpago;
                            break;
                        }
                    }
                    $campos->add('tipo_pago');
                    $regs->add($forma_pago_definitiva);
                    $estado_mundogar = '3';
                } else {

                    switch ($forma_pago_definitiva) {
                        case '18':
                        case '22':
                            $campos->add('estado_web');
                            $regs->add('6');
                            $campos->add('fecha_anulacion');
                            $regs->add(self::convertDate($args['F_Cambio_Estado']->scalar));
                            $estado_mundogar = '6';
                            break;
                        case '23':
                            $campos->add('estado_web');
                            $campos->add('fecha_servido');
                            $campos->add('transportista');
                            $campos->add('telefono_transportista');
                            $campos->add('referencia_transportista');

                            $regs->add('1');
                            $regs->add(self::convertDate($args_e['FEnvio']->scalar));
                            $regs->add(addslashes($args_e['Transportista']));
                            $regs->add(addslashes($args_e['Telefono']->scalar));
                            $regs->add(addslashes($args_e['Ref_Envio']->scalar));
                            $estado_mundogar = '1';
                            break;
                        case '27':
                            $campos->add('estado_web');
                            $regs->add('4');
                            $estado_mundogar = '4';
                            break;
                        default:
                            $campos->add('estado_web');
                            $regs->add('3');
                            $campos->add('tipo_pago');
                            $regs->add($forma_pago[0]);
                            $estado_mundogar = '3';
                            break;
                    }
                }

                break;

            case '4':
                $campos->add('estado_web');
                $regs->add('4');
                $estado_mundogar = '4';
                break;

            case '10':
                $campos->add('estado_web');
                $regs->add('10');
                break;
        }

        $consulta_borrado_tlf = 'DELETE FROM seguimiento_telefonos WHERE id_cliente_gestion = '.$args['id_cliente'];
        Db::getInstance()->execute($consulta_borrado_tlf);

        foreach ($args_tlf as $telefonos) {
            foreach (array_values($telefonos) as $telefono) {
                $consulta_tlf = "INSERT INTO seguimiento_telefonos (id_cliente_gestion, telefono) VALUES ('".$args['id_cliente']."', '".$telefono->scalar."')";
                Db::getInstance()->execute($consulta_tlf);
            }
        }

        $consulta = 'INSERT INTO seguimiento_pedidos (fecha_insercion, '.$campos->implode(new String(', '))->toString().") VALUES (now(), '".$regs->implode(new String("', '"))->toString()."')";

        $query = Db::getInstance()->execute($consulta);

        $consulta_borrado = 'DELETE FROM seguimiento_lineas_pedido WHERE id_pedido_gestion = '.$numero_pedido[1].' and serie = '.$numero_pedido[0];
        Db::getInstance()->execute($consulta_borrado);

        /*if ($args["Origen_Ped"] == "4" and !empty($mundogar)){
            foreach ($mundogar as $id_pedido_mundogar){
                if (ereg("^[0-9]*$", trim($id_pedido_mundogar))){
                    $consulta_estado_mundogar = new String("UPDATE pedidos_puntos SET estado = ".$estado_mundogar." WHERE id_pedido = ".$id_pedido_mundogar);
                    $db->execute($consulta_estado_mundogar);
                }
            }
        }*/

        foreach ($args_lp as $lineas) {

            $descripcion = $lineas['Descripcion']->scalar;

            if (preg_match("/^([0-9]{2}\/[0-9]{2}(\/[0-9]{2})?)(.*)$/", $descripcion, $temp)) {
                $fecha_entrada = $temp[1];
                $nombre_producto = trim($temp[3]);
            } elseif (preg_match("/^([0-9]{2}\/[0-9]{2}(\/[0-9]{4})?)(.*)$/", $descripcion, $temp)) {
                $fecha_entrada = $temp[1];
                $nombre_producto = trim($temp[3]);
            } elseif (preg_match("/^(S\/PLAZO)(.*)$/", $descripcion, $temp)) {
                $fecha_entrada = 'El fabricante no dispone de plazo de entrega.';
                $nombre_producto = trim($temp[2]);
            } else {

                $date = substr($args['fecha_Ped']->scalar, 0, 4).'/'.substr($args['fecha_Ped']->scalar, 4, 2).'/'.substr($args['fecha_Ped']->scalar, 6, 2);
                $fecha_entrada = date('d/m', strtotime($date.' + 7 days'));
                $nombre_producto = $descripcion;
            }
            $nombre_producto = addslashes($nombre_producto);
            $subtotal = str_replace(',', '.', $lineas['SubTotal']);

            if ($lineas['Stock'] > 0) {
                $reservado = 1;
            } else {
                $reservado = 0;
            }

            $consulta_insert = 'INSERT INTO seguimiento_lineas_pedido (id_pedido_gestion, referencia, producto, unidades, fecha_entrada, stock, subtotal, serie) VALUES ('.
            "'".$numero_pedido[1]."', '".$lineas['Referencia']->scalar."', '".$nombre_producto."', '".$lineas['Unidades']."', '".$fecha_entrada."', '".$reservado."', '".$subtotal."', '".$numero_pedido[0]."')";

            // $headers = array("Content-type: text/plain; charset=utf-8;");
            Db::getInstance()->execute($consulta_insert);

            // mail('jabalde@alsernet.es', 'Consulta seguimiento lineas pedidos ('.$args["ID_Pedido"]->scalar.' - '.$numero_pedido[0]."/".$numero_pedido[1].')', $consulta_insert->toString(),implode("\r\n", $headers));

        }

        return ['status' => 'OK'];
    }
}
