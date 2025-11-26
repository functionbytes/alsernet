<?php
/**
 * Page Cache Ultimate, Page Cache standard and Speed pack are powered by Jpresta (jpresta . com)
 *
 * @author    Jpresta
 * @copyright Jpresta
 * @license   See the license of this module in file LICENSE.txt, thank you.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ConfiguratorProductModuleFrontControllerOverride extends ConfiguratorProductModuleFrontController
{
    /**
     * @return string
     */
    public static function getJprestaModelObjectClassName()
    {
        return 'Product';
    }

    /**
     * @return int|null
     */
    public function getJprestaModelObjectId()
    {
        $id_product = (int)Tools::getValue('id_product');
        if ($id_product && ($postObj = new Product($id_product)) && Validate::isLoadedObject($postObj)) {
            return $id_product;
        }
        return null;
    }

}
