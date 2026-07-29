<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

// 384944, 'Oleksii', 'Levchenko', 'Levchenko', '', '640174838', '', 'markisofi@gmail.com', '1979-06-25 00:00:00', 'Y4232479X', 'dni', 'masculino', '1152fdaf64275b6b', '$2y$10$0ZWcWc6Id38aW/nmaynLv.mWJKP5LJXVMmvXbQZU8Rqx/rrA50mry', 1, 'es', '2021-04-30 21:07:05', , 1
// 384966, 'alba', 'guardiola', 'vilaseca', '34', '608574367', '', 'albaguvil@gmail.com', '1976-02-12 00:00:00', '39368649D', 'dni', 'femenino', '07e5be21b38e2353eff483f817f20626', '', 1, 'es', '2021-05-01 13:35:40', , 1

// 163181, 'MODESTO', 'MUGUERZA', 'ARAMBURU', '', '943170083', '943031184', 'modes888@yahoo.es', '1948-12-03 00:00:00', '15336757N', 'dni', 'masculino', 'db28d54a58b9a852', '$2y$10$HFRQV.26244xJvkLT2QzcuQL7yTHUozVhKJBRswsm4sjS4EkTHOXK', 1, 'es', '2009-03-14 08:45:26', , 0
// header('Content-Type: application/xml; charset=utf-8');

// echo recuperardatosclienteerp("Y4232479X", "Levchenko", "markisofi@gmail.com", "640174838");

// echo recuperardatosclienteerp("15336757N", "MUGUERZA", "modes888@yahoo.es", "943170083");

// echo AlvarezERP::recuperarcatalogosclienteerp(AlvarezERP::recuperaridclienteerp(163181));

// echo AlvarezERP::recuperaridclienteerp(163181);

// echo AlvarezERP::savelopd("modes888@yahoo.es", "2022-03-01T09:43:00",0,0);;

// 'F49C6', 'laso2012@gmail.com', 61612, 'FRANCISCO', 'GARCIA', 101747474, '2020-10-25 09:46:34', , ''

// echo AlvarezERP::recuperaridarticulo(Tools::getValue("id"));
// echo AlvarezERP::recuperarstockcentral(AlvarezERP::recuperaridarticulo(Tools::getValue("id")));
// echo AlvarezERP::consultabono("101747474", "F49C6", 200, "web");

// echo AlvarezERP::consultavalecompra("140366");

// echo AlvarezERP::actualizarvalecompra("54031", "0", "prueba");

// public static function crearvalecompra($importe, $tipo, $idalmacen, $observaciones, $tiene_codigo_comprobacion, $id_vale_original, $id_vale_anterior){
// echo AlvarezERP::crearvalecompra(22.0, 1, 1,"","","","","");

// echo AlvarezERP::mandarpedido(Tools::getValue("id"));
// echo AlvarezERP::tienetarifaplana(Tools::getValue("id"));

// echo AlvarezERP::recuperardatosclienteerp("29157616X", "", "pferrando@addis.es", "666555777");

// dump(AlvarezERP::recuperarpedido("1894912", "2022"));
// dump(AlvarezERP::recuperarpedido("27864", "2021"));

// dump(AlvarezERP::recuperarpedidoporid(60));

// echo AlvarezERP::recuperaridarticulo(Tools::getValue("id"));

// echo AlvarezERP::recuperaridclienteerp(384943);

// encarna 384943

// echo AlvarezERP::savelopd("egarcia@addis.es", "2022-03-24T09:19:00",1,0);

// echo AlvarezERP::recuperarcatalogosclienteerp(AlvarezERP::recuperaridclienteerp(384943));
// echo AlvarezERP::recuperardatosclienteerpporidweb(384943);

dump(AlvarezERP::recuperarpedido(63181, 2021));
