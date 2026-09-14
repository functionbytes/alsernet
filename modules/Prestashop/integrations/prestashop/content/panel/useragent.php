<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

echo $_SERVER['HTTP_USER_AGENT'];

echo ' entra: '.Context::isCordovaApp();
