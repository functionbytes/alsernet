<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function toGestion($cadena)
{
    $key = 'aK-#s$q_Fs1?b*EE';
    $key .= substr($key, 0, 8);
    $iv = 'w=c@@ZqP';

    return base64_encode(mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $cadena, MCRYPT_MODE_CFB, $iv));
}

echo toGestion('34E41197P1131471C');
