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

use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;

class CategoryTest
{
    public $objectPresenter;

    public $category;

    public function __construct($id_category)
    {
        $this->objectPresenter = new ObjectPresenter;
        $this->category = new Category($id_category, 1);
    }

    public function getTemplateVarCategory()
    {
        $category = $this->objectPresenter->present($this->category);

        return $category;
    }
}

$link = new Link;

echo 'Categoría que funciona #24<br>';
$prueba_categoria = new CategoryTest(24);
$categoryVar = $prueba_categoria->getTemplateVarCategory($category);
dump($categoryVar);
dump($link->getCategoryLink($prueba_categoria->category));

echo 'Categoría que funciona #17007<br>';
$prueba_categoria = new CategoryTest(17007);
$categoryVar = $prueba_categoria->getTemplateVarCategory($category);
dump($categoryVar);
dump($link->getCategoryLink($prueba_categoria->category));
