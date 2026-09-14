<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

use PrestaShop\PrestaShop\Adapter\ServiceLocator;

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

function recuperapassword($data)
{

    $data2 = 'pw='.$data;
    $url = 'https://alvarez2.addisnetwork.es/panel/pruebaspass.php';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function CrearCliente($row, $dbh)
{

    try {

        if (''.$row['email'] != '') {

            $customerid = $row['id'];

            $customerexists = Customer::customerIdExistsStatic($customerid);

            if ($customerexists) {
                $customer = new Customer((int) $customerid);

                $crypto = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Crypto\\Hashing');
                $passwordoriginal = recuperapassword($row['password']);
                $password = $crypto->hash($passwordoriginal);
                $customer->passwd = $password;
                $customer->update(false);
                echo $customerid.' '.$passwordoriginal.' ';
            }

        } else {

            $d = new DateTime;
            $stdout = fopen(dirname(__FILE__).'/passerrores.txt', 'a');
            fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error email vacio ');
            fwrite($stdout, "\n");
            fwrite($stdout, ' --- datos '.$row[0]);
            fwrite($stdout, "\n");
            fclose($stdout);

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/passerrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$row[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dsn = 'mysql:host=195.55.36.104;dbname=tienda';
    $dbh = new PDO($dsn, 'tiendalvad', 'Nov.299909');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, "SELECT * FROM usuarios where email<>'' order by id limit ".((int) Tools::getValue('id')).',1');
foreach ($rows as $row) {
    CrearCliente($row, $dbh);
}
