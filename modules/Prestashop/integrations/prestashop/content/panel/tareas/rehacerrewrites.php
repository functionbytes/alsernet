<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function safeName($sString)
{
    $sReturn = $sString;

    $sReturn = strtr($sReturn, "()!$'?: ,&+-/.", '');

    $a = ['', '', '', '', '', '', '', '¥', 'µ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
    $b = ['S', 'O', 'Z', 's', 'o', 'z', 'Y', 'Y', 'u', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];
    $sReturn = str_replace($a, $b, $sReturn);
    $sReturn = trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($sReturn))));

    $sReturn = str_replace(' ', '_', $sReturn);

    return $sReturn;
}

$categories = Db::getInstance()->ExecuteS('select * from aalv_category_lang where id_category>2');

foreach ($categories as $categoryitem) {

    $newlink = safeName($categoryitem['name']);

    Db::getInstance()->Execute("UPDATE aalv_category_lang SET link_rewrite='".$newlink."' WHERE id_category=".$categoryitem['id_category'].' and id_shop=1 and id_lang='.$categoryitem['id_lang']);
    // echo "<br/>"."UPDATE aalv_category_lang SET link_rewrite='".$newlink."' WHERE id_category=".$categoryitem["id_category"]." and id_shop=1 and id_lang=".$categoryitem["id_lang"].";";

}

echo 'acaba';
