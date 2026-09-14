<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
// include _PS_ADMIN_DIR_.'/../init.php';

function rutaftp($imagename)
{

    $ruta = '/productimages/web/images/';

    $primera = substr($imagename, 0, 1);
    $segunda = substr($imagename, 1, 1);

    return $ruta.$primera.'/'.$segunda.'/'.$imagename;

}

function download($imagename)
{

    $local_file = $imagename;
    $server_file = rutaftp($imagename);

    // set up basic connection
    $ftp = ftp_connect('www.devel.a-alvarez.com');

    // login with username and password
    $login_result = ftp_login($ftp, 'ftpaddis', 'Mar.893124');

    // try to download $server_file and save to $local_file
    if (ftp_get($ftp, $local_file, $server_file, FTP_BINARY)) {
        echo "Successfully written to $local_file\n";
    } else {
        echo "There was a problem\n";
    }

    // close the connection
    ftp_close($ftp);

}

download('bolsa-Mares-cruise-dry-roller-140.jpg');
