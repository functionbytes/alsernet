<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

include _PS_ADMIN_DIR_.'/../vendor/XMLRPC/class.XMLRPC.php';
include _PS_ADMIN_DIR_.'/../vendor/XMLRPC/class.XMLRPCServer.php';

$myServer = new XMLRPCServer;
$myServer->registerStaticMethod('WebAlvarez', 'obtenerPedidosPendientes');
$myServer->registerStaticMethod('WebAlvarez', 'marcarPedidoAtendido');

$myServer->request($HTTP_RAW_POST_DATA);

// echo $myServer->getResponse(new String("XML"))->toString();

class WebAlvarez
{
    public static function obtenerPedidosPendientes($parameters)
    {

        $query = "
    SELECT
    (select firstname from aalv_address a where a.id_address=o.id_address_invoice) as nombre,
    (select lastname from aalv_address a where a.id_address=o.id_address_invoice) as apellidos,
    (select vat_number from aalv_address a where a.id_address=o.id_address_invoice) as dni,
    (select email from aalv_customer c where c.id_customer=o.id_customer) as email,
    o.id_order as id_pedido_web, o.id_customer as id_usuario_web, o.date_add as fecha_pedido, round(o.total_paid,2) as importe_total
    FROM aalv_orders o
    WHERE o.current_state not in (6,27) and o.id_order in (select id_order from aalv_orders_envio_gestion where id_pedido_gestion='' and error_gestion<>'' and id in (select max(id) from aalv_orders_envio_gestion group by id_order))
	";

        $result = Db::getInstance()->executeS($query);
        $output = [];

        if ($result) {
            return ['numero_pedidos_pendientes' => count($result), 'listado' => $result];

        } else {
            return ['numero_pedidos_pendientes' => 0, 'listado' => $output];
        }

        /*
        if ($result->lenght()->getValue() > 0) {

            while ($current = $result->current()) {
                $output[] = $current->getArray();
                $result->next();
            }
        }

        return (array("numero_pedidos_pendientes"=> $result->lenght()->getValue(),"listado"=>$output));
        */
    }

    public static function marcarPedidoAtendido($parameters)
    {
        global $alvarez_new_db_config;
        $id_pedido_web = $parameters['args'][0]['id_pedido_web'];

        $output = [];

        $query = "
            SELECT id as id_pedido_web, current_state
        	FROM aalv_orders
        	WHERE  id_order = '".$id_pedido_web."'
        	ORDER BY id_order
		";

        $result = Db::getInstance()->executeS($query);

        if ($result) {
            if (count($result) > 0) {

                if ($result[0]['current_state'] == 27) {
                    return ['faultCode' => 1001, 'faultString' => 'El pedido ya se encuentra marcado como atendido'];
                }

            } else {
                return ['faultCode' => 1000, 'faultString' => 'No existe un pedido con el identificador indicado'];

            }

        }

        /*
        if ($result->lenght()->getValue() > 0) {
            $current = $result->current()->getArray();
            if ($current["current_state"] == 27) {
            // if ($current["atendido"] == 1) {
                return (array(
        "faultCode"=>1001,
        "faultString"=>"El pedido ya se encuentra marcado como atendido",
      ));
            }
        } else {
            //No hay pedido con ese id
            return (array(
        "faultCode"=>1000,
        "faultString"=>"No existe un pedido con el identificador indicado",
      ));
        }*/

        $query = "UPDATE aalv_orders SET current_state = 27, note=CONCAT(note, ' .Atendido por Gestión. ') WHERE id_order = ".$id_pedido_web;
        $query1 = 'INSERT INTO aalv_order_history(id_employee, id_order, id_order_state, date_add) VALUES (0,'.$id_pedido_web.',27,now())';

        $result = Db::getInstance()->execute($query);
        $result1 = Db::getInstance()->execute($query1);

        return ['status' => 'OK'];
    }
}
