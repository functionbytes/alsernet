<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
$timer_start = microtime(true);
if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}

if (! defined('PS_ADMIN_DIR')) {
    define('PS_ADMIN_DIR', _PS_ADMIN_DIR_);
}

require _PS_ADMIN_DIR_.'/../config/config.inc.php';
require _PS_ADMIN_DIR_.'/../init.php';

function deleteCategory($id_category)
{
    if (! empty($id_category) || ! is_numeric($id_category)) {
        // borrar categoria
        $category = new Category((int) $id_category);
        $category->delete();
        // dump($id_category);
    } else {
        dump($id_category);
    }
}

function deleteCategoriesSameName($id_parent)
{
    $sql = 'SELECT cl.`name`, COUNT(cl.`name`) AS \'count\', GROUP_CONCAT(CAST(c.`id_category` AS CHAR)) AS \'group_concat\', c.`id_parent` 
            FROM `'._DB_PREFIX_.'category` c 
            INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON cl.`id_category`=c.`id_category` AND cl.`id_shop`=1 AND cl.`id_lang`=1 
            WHERE c.`id_parent`='.pSQL($id_parent).' 
            GROUP BY cl.`name` 
            HAVING COUNT(cl.`name`) > 1 
            ORDER BY cl.`name` ASC';
    $categories_same_name = Db::getInstance()->executeS($sql);

    if ($categories_same_name) {
        dump($categories_same_name);
        // dump($categories_same_name);
        foreach ($categories_same_name as $category_same_name) {
            if ((int) $category_same_name['count'] > 2) {
                dump($category_same_name);

                continue;
            }

            /*$sql = 'SELECT `name` FROM `'._DB_PREFIX_.'category_lang` WHERE `id_category` IN ('.pSQL($category_same_name['group_concat']).') AND `id_shop`=1 AND `id_lang`=1';
            dump(Db::getInstance()->executeS($sql));*/

            $sql = 'SELECT MAX(`id_category`) FROM `'._DB_PREFIX_.'category` WHERE `id_category` IN ('.pSQL($category_same_name['group_concat']).')';
            $max_category = Db::getInstance()->getValue($sql);
            deleteCategory((int) $max_category);
        }
    }

    $sql = 'SELECT id_category FROM `'._DB_PREFIX_.'category` WHERE `id_parent`='.$id_parent.' ORDER BY `id_category` ASC';
    $categories = Db::getInstance()->executeS($sql);
    if ($categories) {
        foreach ($categories as $category_item) {
            deleteCategoriesSameName($category_item['id_category']);
        }
    }
}

/* Eliminar caetegorias hermanas con mismo nombre que cuelguen de la categoria 59963 (ev. rebajas) */
deleteCategoriesSameName(59963);

// deleteCategoriesSameName(60161);
