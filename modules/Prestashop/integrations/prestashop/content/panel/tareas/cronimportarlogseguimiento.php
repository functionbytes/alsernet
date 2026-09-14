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

function campo($field, $tipo)
{

    $devolver = '';
    if ($field == '') {
        $devolver = 'null';
    } else {
        $devolver = $field;
        if ($tipo == 1) {
            $devolver = "'".$devolver."'";
        }
    }

    return $devolver;

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

/*
$rows = getdatarows($dbh,"SELECT * FROM seguimiento_pedidos where id>4521427 order by id");


foreach($rows as $row){


    $id="".$row["id"];
    $id_gestion=campo("".$row["id_gestion"],0);
    $fecha_insercion=campo("".$row["fecha_insercion"],1);

    $id_cliente_gestion=campo("".$row["id_cliente_gestion"],0);
    $nombre=campo("".$row["nombre"],1);

    $apellido1=campo("".$row["apellido1"],1);
    $apellido2=campo("".$row["apellido2"],1);
    $fecha_servido=campo("".$row["fecha_servido"],1);
    $transportista=campo("".$row["transportista"],1);
    $referencia_transportista=campo("".$row["referencia_transportista"],1);
    $fecha_anulacion=campo("".$row["fecha_anulacion"],1);
    $fecha_cambio_estado=campo("".$row["fecha_cambio_estado"],1);
    $estado_gestion=campo("".$row["estado_gestion"],0);
    $estado_web=campo("".$row["estado_web"],0);
    $origen=campo("".$row["origen"],1);
    $tipo_pago=campo("".$row["tipo_pago"],1);
    $fecha_pedido=campo("".$row["fecha_pedido"],1);
    $id_internet=campo("".$row["id_internet"],0);
    $id_incidencia=campo("".$row["id_incidencia"],0);
    $tarjeta_denegada=campo("".$row["tarjeta_denegada"],0);
    $serie=campo("".$row["serie"],1);
    $total=campo("".$row["total"],0);
    $portes=campo("".$row["portes"],0);
    $telefono_transportista=campo("".$row["telefono_transportista"],1);



    $sql="REPLACE INTO `seguimiento_pedidos`(`id`, `id_gestion`, `fecha_insercion`, `id_cliente_gestion`, `nombre`, `apellido1`, `apellido2`, `fecha_servido`, `transportista`, `referencia_transportista`, `fecha_anulacion`, `fecha_cambio_estado`, `estado_gestion`, `estado_web`, `origen`, `tipo_pago`, `fecha_pedido`, `id_internet`, `id_incidencia`, `tarjeta_denegada`, `serie`, `total`, `portes`, `telefono_transportista`) VALUES (".$id.",".$id_gestion.",".$fecha_insercion.",".$id_cliente_gestion.",".$nombre.",".$apellido1.",".$apellido2.",".$fecha_servido.",".$transportista.",".$referencia_transportista.",".$fecha_anulacion.",".$fecha_cambio_estado.",".$estado_gestion.",".$estado_web.",".$origen.",".$tipo_pago.",".$fecha_pedido.",".$id_internet.",".$id_incidencia.",".$tarjeta_denegada.",".$serie.",".$total.",".$portes.",".$telefono_transportista.")";


    Db::getInstance()->Execute($sql);


}



$rows = getdatarows($dbh,"SELECT * FROM seguimiento_lineas_pedido WHERE  id>5054718 order by id");


foreach($rows as $row){


    $id="".$row["id"];
    $id_pedido_gestion=campo("".$row["id_pedido_gestion"],0);
    $referencia=campo("".$row["referencia"],1);

    $producto=campo("".$row["producto"],1);
    $unidades=campo("".$row["unidades"],0);

    $fecha_entrada=campo("".$row["fecha_entrada"],1);
    $stock=campo("".$row["stock"],0);
    $subtotal=campo("".$row["subtotal"],0);
    $serie=campo("".$row["serie"],1);


    $sql="REPLACE INTO `seguimiento_lineas_pedido`(`id`, `id_pedido_gestion`, `referencia`, `producto`, `unidades`, `fecha_entrada`, `stock`, `subtotal`, `serie`) VALUES (".$id.",".$id_pedido_gestion.",".$referencia.",".$producto.",".$unidades.",".$fecha_entrada.",".$stock.",".$subtotal.",".$serie.")";



    Db::getInstance()->Execute($sql);


}

*/
/*
$rows = getdatarows($dbh,"SELECT * FROM `log_transportista` where id>259529 ORDER BY `id`");


foreach($rows as $row){


    $id="".$row["id"];
    $numero_pedido=campo("".$row["numero_pedido"],1);
    $numero_envio=campo("".$row["numero_envio"],1);

    $fecha=campo("".$row["fecha"],1);
    $transportista=campo("".$row["transportista"],1);

    $remitente=campo("".$row["remitente"],1);



    $sql="INSERT INTO `log_transportista`(`id`, `numero_pedido`, `numero_envio`, `fecha`, `transportista`, `remitente`) VALUES (".$id.",".$numero_pedido.",".$numero_envio.",".$fecha.",".$transportista.",".$remitente.")";



    Db::getInstance()->Execute($sql);


}
*/

/*
$rows = getdatarows($dbh,"SELECT * FROM `log_transportista_envio` where id>113614 ORDER BY `id`");


dump(getfieldvalue($dbh,"SELECT max(id) FROM `log_transportista_envio`"));



foreach($rows as $row){


    $id="".$row["id"];
    $numero_pedido=campo("".$row["numero_pedido"],1);
    $fecha_envio=campo("".$row["fecha_envio"],1);

    $id_modelo=campo("".$row["id_modelo"],0);
    $fecha_opinion=campo("".$row["fecha_opinion"],1);

    $log_transportista_id=campo("".$row["log_transportista_id"],0);



    $sql="REPLACE INTO `log_transportista_envio`(`id`, `numero_pedido`, `fecha_envio`, `id_modelo`, `fecha_opinion`, `log_transportista_id`) VALUES (".$id.",".$numero_pedido.",".$fecha_envio.",".$id_modelo.",".$fecha_opinion.",".$log_transportista_id.")";



    Db::getInstance()->Execute($sql);


}
*/

$rows = getdatarows($dbh, 'SELECT * FROM `log_transportista_tracking` where id>278722 ORDER BY `id`');

foreach ($rows as $row) {

    $id = ''.$row['id'];
    $numero_pedido = campo(''.$row['numero_pedido'], 1);
    $numero_envio = campo(''.$row['numero_envio'], 1);

    $fecha = campo(''.$row['fecha'], 1);
    $transportista = campo(''.$row['transportista'], 1);

    $remitente = campo(''.$row['remitente'], 1);
    $estado = campo(''.$row['estado'], 0);
    $estado_descripcion = campo(''.$row['estado_descripcion'], 1);

    $sql = 'REPLACE INTO `log_transportista_tracking`(`id`, `numero_pedido`, `numero_envio`, `fecha`, `transportista`, `remitente`, `estado`, `estado_descripcion`) VALUES ('.$id.','.$numero_pedido.','.$numero_envio.','.$fecha.','.$transportista.','.$remitente.','.$estado.','.$estado_descripcion.')';

    Db::getInstance()->Execute($sql);

}

echo '<br/>acaba';
