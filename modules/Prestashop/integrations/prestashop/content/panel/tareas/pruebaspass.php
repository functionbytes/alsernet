<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
// include _PS_ADMIN_DIR_.'/../init.php';

function decrypt($cadena, $clave)
{
    // $td = mcrypt_module_open (MCRYPT_DES, "", MCRYPT_MODE_ECB   , "/usr/lib/libmcrypt");
    $td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB);
    $iv_size = mcrypt_enc_get_iv_size($td);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

    if (strlen($clave) > 8) {
        $clave = substr($clave, 0, 8);
    }

    @mcrypt_generic_init($td, $clave, $iv);
    $len = strlen($cadena);
    $newdata = '';
    for ($i = 0; $i < $len; $i += 2) {
        $newdata .= pack('C', hexdec(substr($cadena, $i, 2)));
    }
    $data = mdecrypt_generic($td, $newdata);

    return $data;
}

echo decrypt('a716533f9b3b2447a13fb12999bf76c5', 'fgr_491-f6t');
