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
class HelperTreeCategories extends HelperTreeCategoriesCore
{
   

    private function fillTree(&$categories, $rootCategoryId)
    {
        $tree = [];
        $rootCategoryId = (int) $rootCategoryId;

        foreach ($categories[$rootCategoryId] as $category) {
            $categoryId = (int) $category['id_category'];

            if ($categoryId != 2821 || ($categoryId == 2821 && isset($_GET['configure']) && $_GET['configure'] == 'retailrocket') ) {
                $tree[$categoryId] = $category;

                if (Category::hasChildren($categoryId, $this->getLang(), false, $this->getShop()->id)) {
                    $categoryChildren = Category::getChildren(
                        $categoryId,
                        $this->getLang(),
                        false,
                        $this->getShop()->id
                    );

                    foreach ($categoryChildren as $child) {
                        $childId = (int) $child['id_category'];

                        if (!array_key_exists('children', $tree[$categoryId])) {
                            $tree[$categoryId]['children'] = [$childId => $child];
                        } else {
                            $tree[$categoryId]['children'][$childId] = $child;
                        }

                        $categories[$childId] = [$child];
                    }

                    foreach ($tree[$categoryId]['children'] as $childId => $child) {
                        $subtree = $this->fillTree($categories, $childId);

                        foreach ($subtree as $subcategoryId => $subcategory) {
                            $tree[$categoryId]['children'][$subcategoryId] = $subcategory;
                        }
                    }
                }
            }
        }

        return $tree;
    }

}