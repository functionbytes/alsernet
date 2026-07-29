<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

$id_shop = 1;
$id_lang = 1;

/*$tallas = ['10XL', '9XL', 'ba', '8XL', '7XL', '6XL', 'bbbb', '5XL', '4XL', '3XL', 'ab', 'XXL', 'XL', '10XS', '9XS', '8XS', '7XS', '6XS', '5XS', '4XS', '3XS', 'XXS', 'XS', 'L', 'M', 'S', 'bbba', 'aaa'];
dump($tallas);
usort($tallas, 'sortAttributes');
dump($tallas);
die();*/

$sql = 'SELECT * 
        FROM `'._DB_PREFIX_.'attribute_group` ag 
        INNER JOIN `'._DB_PREFIX_.'attribute_group_shop` ags ON ags.`id_attribute_group`=ag.`id_attribute_group` AND ags.`id_shop`='.$id_shop.' 
        INNER JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON agl.`id_attribute_group`=ag.`id_attribute_group` AND agl.`id_lang`='.$id_lang.' 
        ORDER BY ag.`position`, ag.`id_attribute_group`';
$atributte_groups = DB::getInstance()->executeS($sql);
foreach ($atributte_groups as $attribute_group) {
    $sql = 'SELECT * 
            FROM `'._DB_PREFIX_.'attribute` a 
            INNER JOIN `'._DB_PREFIX_.'attribute_shop` ats ON ats.`id_attribute`=a.`id_attribute` AND ats.`id_shop`='.$id_shop.' 
            INNER JOIN `'._DB_PREFIX_.'attribute_lang` al ON al.`id_attribute`=a.`id_attribute` AND al.`id_lang`='.$id_lang.' 
            WHERE a.`id_attribute_group`='.(int) $attribute_group['id_attribute_group'].' 
            ORDER BY a.`position`, a.`id_attribute`';
    $attributes = DB::getInstance()->executeS($sql);
    $attribute_group['attributes'] = $attributes;
    // dump($attribute_group);

    // ordenar combinaciones
    usort($attribute_group['attributes'], 'sortAttributes');
    // dump($attribute_group);

    // actualizar posición
    $count = 1;
    foreach ($attribute_group['attributes'] as $attribute) {
        echo 'Actualizando posición del id_attribute '.$attribute['id_attribute'].' a '.(int) $count.' ...<br>';
        $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET `position`='.(int) $count.' WHERE `id_attribute`='.$attribute['id_attribute'];
        DB::getInstance()->execute($sql);

        $count += 1;
    }

    // break;
}

function sortAttributes($a, $b)
{
    $tallas_pattern = [
        '10XS',
        '9XS',
        '8XS',
        '7XS',
        '6XS',
        '5XS',
        '4XS',
        '3XS',
        '2XS',
        '1XS',
        'XXXXXXXXXXS',
        'XXXXXXXXXS',
        'XXXXXXXXS',
        'XXXXXXXS',
        'XXXXXXS',
        'XXXXXS',
        'XXXXS',
        'XXXS',
        'XXS',
        'XS',
        'S',
        'M',
        'L',
        'XL',
        'XXL',
        'XXXL',
        'XXXXL',
        'XXXXXL',
        'XXXXXXL',
        'XXXXXXXL',
        'XXXXXXXXL',
        'XXXXXXXXXL',
        'XXXXXXXXXXL',
        '1XL',
        '2XL',
        '3XL',
        '4XL',
        '5XL',
        '6XL',
        '7XL',
        '8XL',
        '9XL',
        '10XL',
    ];

    /*if (in_array(trim($a), $tallas_pattern) && in_array(trim($b), $tallas_pattern)) {
        return (getOrderValue(trim($a['name'])) > getOrderValue(trim($b)) ? 1 : -1);
    } elseif (in_array(trim($a), $tallas_pattern) && !in_array(trim($b), $tallas_pattern)) {
        return -1;
    } elseif (!in_array(trim($a), $tallas_pattern) && in_array(trim($b), $tallas_pattern)) {
        return 1;
    } else {
        if (intval(trim($a)) > intval(trim($b))) {
            return 1;
        } elseif (intval(trim($a)) < intval(trim($b))) {
            return -1;
        } else {
            return (trim($a) > trim($b) ? 1 : -1);
        }
    }*/

    if (in_array(trim($a['name']), $tallas_pattern) && in_array(trim($b['name']), $tallas_pattern)) {
        return getOrderValue(trim($a['name'])) > getOrderValue(trim($b['name'])) ? 1 : -1;
    } elseif (in_array(trim($a['name']), $tallas_pattern) && ! in_array(trim($b['name']), $tallas_pattern)) {
        return -1;
    } elseif (! in_array(trim($a['name']), $tallas_pattern) && in_array(trim($b['name']), $tallas_pattern)) {
        return 1;
    } else {
        if (intval(trim($a['name'])) > intval(trim($b['name']))) {
            return 1;
        } elseif (intval(trim($a['name'])) < intval(trim($b['name']))) {
            return -1;
        } else {
            return trim($a['name']) > trim($b['name']) ? 1 : -1;
        }
    }
}

function getOrderValue($nombre)
{
    /*$xtr = null;
    if (strlen($nombre)!=1) {
        $xtr = substr($nombre, 0, strlen($nombre)-1);
        $nombre = substr($nombre, strlen($nombre)-1);
    }
    $val = 0;*/

    switch ($nombre) {
        case '10XS':
        case 'XXXXXXXXXXS':
            $aux = 1;
            break;
        case '9XS':
        case 'XXXXXXXXXS':
            $aux = 2;
            break;
        case '8XS':
        case 'XXXXXXXXS':
            $aux = 3;
            break;
        case '7XS':
        case 'XXXXXXXS':
            $aux = 4;
            break;
        case '6XS':
        case 'XXXXXXS':
            $aux = 5;
            break;
        case '5XS':
        case 'XXXXXS':
            $aux = 6;
            break;
        case '4XS':
        case 'XXXXS':
            $aux = 7;
            break;
        case '3XS':
        case 'XXXS':
            $aux = 8;
            break;
        case '2XS':
        case 'XXS':
            $aux = 9;
            break;
        case '1XS':
        case 'XS':
            $aux = 10;
            break;
        case 'S':
            $aux = 11;
            break;
        case 'M':
            $aux = 12;
            break;
        case 'L':
            $aux = 13;
            break;
        case '1XL':
        case 'XL':
            $aux = 14;
            break;
        case '2XL':
        case 'XXL':
            $aux = 15;
            break;
        case '3XL':
        case 'XXXL':
            $aux = 16;
            break;
        case '4XL':
        case 'XXXXL':
            $aux = 17;
            break;
        case '5XL':
        case 'XXXXXL':
            $aux = 18;
            break;
        case '6XL':
        case 'XXXXXXL':
            $aux = 19;
            break;
        case '7XL':
        case 'XXXXXXXL':
            $aux = 20;
            break;
        case '8XL':
        case 'XXXXXXXXL':
            $aux = 21;
            break;
        case '9XL':
        case 'XXXXXXXXXL':
            $aux = 22;
            break;
        case '10XL':
        case 'XXXXXXXXXXL':
            $aux = 23;
            break;
        default:
            $aux = 999;
    }

    /*switch ($nombre) {
        case '10XS';
            $aux = 1;
            break;
        case '9XS';
            $aux = 2;
            break;
        case '8XS';
            $aux = 3;
            break;
        case '7XS';
            $aux = 4;
            break;
        case '6XS';
            $aux = 5;
            break;
        case '5XS';
            $aux = 6;
            break;
        case '4XS';
            $aux = 7;
            break;
        case '3XS';
            $aux = 8;
            break;
        case 'XXS';
            $aux = 9;
            break;
        case 'XS';
            $aux = 10;
            break;
        case 'S';
            $aux = 11;
            break;
        case 'M';
            $aux = 12;
            break;
        case 'L';
            $aux = 13;
            break;
        case 'XL';
            $aux = 14;
            break;
        case 'XXL';
            $aux = 15;
            break;
        case '3XL';
            $aux = 16;
            break;
        case '4XL';
            $aux = 17;
            break;
        case '5XL';
            $aux = 18;
            break;
        case '6XL';
            $aux = 19;
            break;
        case '7XL';
            $aux = 20;
            break;
        case '8XL';
            $aux = 21;
            break;
        case '9XL';
            $aux = 22;
            break;
        case '10XL';
            $aux = 23;
            break;
    }*/

    /*if ($xtr != null) {
        $pot = substr($xtr, 0, 1);
        $pot = is_numeric(substr($xtr, 0, 1)) ? intval($pot) : strlen($xtr);
        $val *= ($pot+1);
    }*/
    return intval($aux);
}

/*function sortAttributes($a, $b) {
    if (strpos($a['name'],"S") !== false || strpos($a['name'],"M") !== false || strpos($a['name'],"L") !== false) {
        //return ($this->getOrderValue($a['name']) > $this->getOrderValue($b['name']) ? 1 : -1);
        return (getOrderValue($a['name']) > getOrderValue($b['name']) ? 1 : -1);
    } else {
        if (intval($a['name']) > intval($b['name'])) {
            return 1;
        } elseif (intval($a['name']) < intval($b['name'])) {
            return -1;
        } else {
            return ($a['name'] > $b['name'] ? 1 : -1);
        }
    }
}

function getOrderValue($nombre) {
    $xtr = null;
    if (strlen($nombre)!=1) {
        $xtr = substr($nombre, 0, strlen($nombre)-1);
        $nombre = substr($nombre, strlen($nombre)-1);
    }
    $val = 0;
    if ($nombre == 'L') {
        $val = 1;
    } else if ($nombre == 'S') {
        $val = -1;
    }
    if ($xtr != null) {
        $pot = substr($xtr, 0, 1);
        $pot = is_numeric(substr($xtr, 0, 1)) ? intval($pot) : strlen($xtr);
        $val *= ($pot+1);
    }
    return intval($val);
}*/
