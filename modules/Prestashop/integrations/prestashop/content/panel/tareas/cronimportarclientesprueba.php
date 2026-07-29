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

function cleanCharsFromCustomerName($name)
{
    $name_return = $name;

    // 0-9!<>,;?=+()@#"°{}_$%:
    $name_return = str_replace('0', '', $name_return);
    $name_return = str_replace('1', '', $name_return);
    $name_return = str_replace('2', '', $name_return);
    $name_return = str_replace('3', '', $name_return);
    $name_return = str_replace('4', '', $name_return);
    $name_return = str_replace('5', '', $name_return);
    $name_return = str_replace('6', '', $name_return);
    $name_return = str_replace('7', '', $name_return);
    $name_return = str_replace('8', '', $name_return);
    $name_return = str_replace('9', '', $name_return);
    $name_return = str_replace('!', '', $name_return);
    $name_return = str_replace('<', '', $name_return);
    $name_return = str_replace('>', '', $name_return);
    $name_return = str_replace(',', '', $name_return);
    $name_return = str_replace(';', '', $name_return);
    $name_return = str_replace('?', '', $name_return);
    $name_return = str_replace('=', '', $name_return);
    $name_return = str_replace('+', '', $name_return);
    $name_return = str_replace('(', '', $name_return);
    $name_return = str_replace(')', '', $name_return);
    $name_return = str_replace('@', '', $name_return);
    $name_return = str_replace('#', '', $name_return);
    $name_return = str_replace('"', '', $name_return);
    $name_return = str_replace('°', '', $name_return);
    $name_return = str_replace('{', '', $name_return);
    $name_return = str_replace('}', '', $name_return);
    $name_return = str_replace('_', '', $name_return);
    $name_return = str_replace('$', '', $name_return);
    $name_return = str_replace('%', '', $name_return);
    $name_return = str_replace('*', '', $name_return);
    $name_return = str_replace(':', '', $name_return);
    $name_return = str_replace('.', '', $name_return);
    $name_return = str_replace('/', '', $name_return);

    return $name_return;
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

    echo '<br/>'.$url.'?'.$data2;

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
            } else {
                $customer = new Customer;
            }

            $groups = [];
            $groups[] = 3;

            $customer->force_id = true;
            $customer->id = (int) $customerid;

            $customer->email = $row['email'];
            $customer->lastname = cleanCharsFromCustomerName($row['apellido1'].' '.$row['apellido2']);
            $customer->firstname = cleanCharsFromCustomerName($row['nombre']);

            $crypto = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Crypto\\Hashing');
            $passwordoriginal = recuperapassword($row['password']);

            echo '<br/>'.$passwordoriginal.' '.$row['email'];

            $password = $crypto->hash($passwordoriginal);
            $customer->passwd = $password;

            $customer->id_default_group = 3;

            if ((''.$row['fecha_nacimiento'] != '') && (''.$row['fecha_nacimiento'] != '1000-01-01 00:00:00')) {
                $customer->birthday = str_replace(' 00:00:00', '', $row['fecha_nacimiento']);
            }

            if (''.$row['fecha_alta'] != '') {
                $customer->date_add = $row['fecha_alta'];
            }

            if ($row['registrado'] == 1) {
                $customer->active = true;
            } else {
                $customer->active = false;
            }

            $sexo = ''.$row['sexo'];
            if ($sexo == 'femenino') {
                $customer->id_gender = 1;
            } else {
                $customer->id_gender = 0;
            }

            $idioma = ''.$row['idioma'];

            if ($idioma == 'es') {
                $customer->id_lang = 1;
            }
            if ($idioma == 'en') {
                $customer->id_lang = 2;
            }
            if ($idioma == 'fr') {
                $customer->id_lang = 3;
            }
            if ($idioma == 'pt') {
                $customer->id_lang = 4;
            }
            if ($idioma == 'de') {
                $customer->id_lang = 5;
            }

            if ($customerexists) {
                $customer->update(false);
            } else {
                $customer->add(false);
            }

            $customer->updateGroup($groups);

        } else {

            $d = new DateTime;
            $stdout = fopen(dirname(__FILE__).'/updateclienteserrores.txt', 'a');
            fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error email vacio ');
            fwrite($stdout, "\n");
            fwrite($stdout, ' --- datos '.$row[0]);
            fwrite($stdout, "\n");
            fclose($stdout);

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/updateclienteserrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$row[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

// $rows = getdatarows($dbh,"SELECT * FROM usuarios where email<>'' and fecha_modif>='2022-06-23 00:00:00' order by id limit ". ((int)Tools::getValue("id")) .",1");

$fecha = Db::getInstance()->getValue('SELECT fecha_update FROM aaalv_cron_customer');

echo '<br/>'.getfieldvalue($dbh, "SELECT count(*) FROM usuarios where email<>'' and fecha_modif>='".$fecha."'").'<br/>';

exit();

$rows = getdatarows($dbh, "SELECT * FROM usuarios where email<>'' and fecha_modif>='".$fecha."' order by id");
foreach ($rows as $row) {
    CrearCliente($row, $dbh);
}

// Db::getInstance()->Execute("delete from aaalv_cron_customer");
// Db::getInstance()->Execute("INSERT INTO aaalv_cron_customer(fecha_update) VALUES (DATE_SUB(NOW(), INTERVAL 1 DAY))");

echo '<br/>acaba';
