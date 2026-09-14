<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function rutaftp($imagename)
{

    $ruta = '/';

    $primera = substr($imagename, 0, 1);
    $segunda = substr($imagename, 1, 1);

    return $ruta.$primera.'/'.$segunda.'/'.$imagename;

}

function download($imagename)
{

    $local_file = __DIR__.'/backups/'.$imagename;
    $server_file = rutaftp($imagename);

    echo $local_file.'<br/>';
    echo $server_file.'<br/>';

    dump($login_result);

    // try to download $server_file and save to $local_file
    dump(ftp_get($ftp, $local_file, $server_file, FTP_BINARY));

    // close the connection
    ftp_close($ftp);

}

download('12-MORADO-chaqueta-trx2-shell-wm-pro-trango-morado.jpg');
echo 'acaba';
