<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// header("Content-Type: text/plain");

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/logprodeventos.txt', 'a');
    fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' '.$message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function verdatos($id, $nombre)
{

    $sql = "SELECT count(*) FROM aalv_category_product a inner join aalv_product b on a.id_product=b.id_product and b.visibility='both'  where id_category=";
    $dato = Db::getInstance()->getValue($sql.$id);
    echo '<br/>'.$nombre.' '.$dato;
    addlog($nombre.' '.$dato);

}

verdatos(59964, 'Caza');
verdatos(59987, 'Aventura');
verdatos(59997, 'Pesca');
verdatos(60003, 'Nautica');
verdatos(60027, 'Hipica');
verdatos(60161, 'Golf');
verdatos(60164, 'Buceo');
verdatos(60347, 'Esqui');
verdatos(60675, 'Padel');

// 59964,59987,59997,60003,60161,60164,60347,60675
