<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function recalcularposhermanas($cathermanas, $cat)
{

    foreach ($cathermanas as $cathermana) {
        if ($cathermana['active'] == 0) {
            Db::getInstance()->execute('UPDATE aalv_category SET position=9999 WHERE id_category='.$cathermana['id_category']);
        }
    }

    $cathermanas = Db::getInstance()->executeS('select * from aalv_category where id_parent='.$cat.' order by position');

    $i = 0;
    foreach ($cathermanas as $cathermana) {
        Db::getInstance()->execute('UPDATE aalv_category SET position='.$i.' WHERE id_category='.$cathermana['id_category']);
        Db::getInstance()->execute('UPDATE aalv_category_shop SET position='.$i.' WHERE id_category='.$cathermana['id_category']);
        $i = $i + 1;
    }

    foreach ($cathermanas as $cathermana) {
        $cat2 = $cathermana['id_category'];
        $cathermanas2 = Db::getInstance()->executeS('select * from aalv_category where id_parent='.$cat2);
        recalcularposhermanas($cathermanas2, $cat2);
    }

}

$catevento = 59963;

$catdeportesevento = Db::getInstance()->executeS('select id_category from aalv_category where id_parent='.$catevento);

foreach ($catdeportesevento as $catprin) {

    $cat = $catprin['id_category'];

    $cathermanas = Db::getInstance()->executeS('select * from aalv_category where id_parent='.$cat);

    recalcularposhermanas($cathermanas, $cat);

}

echo 'acaba';
