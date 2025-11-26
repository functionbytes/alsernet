<?php
/**
 * Page Cache Ultimate, Page Cache standard and Speed pack are powered by Jpresta (jpresta . com)
 *
 *    @author    Jpresta
 *    @copyright Jpresta
 *    @license   See the license of this module in file LICENSE.txt, thank you.
 */
if (!defined('_PS_VERSION_')) {exit;}
abstract class ProductListingFrontController extends ProductListingFrontControllerCore
{

    protected function doProductSearch($template, $params = array(), $locale = null)
    {


        if (!Tools::getIsset('page_cache_dynamics_mods')) {
            return parent::doProductSearch($template, $params, $locale);
        }
    }
}
