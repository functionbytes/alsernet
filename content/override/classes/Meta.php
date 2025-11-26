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

/**
 * Class MetaCore.
 */
class Meta extends MetaCore
{
  
    /**
     * Get meta tags.
     *
     * @since 1.5.0
     */
    public static function getMetaTags($idLang, $pageName, $title = '')
    {

        //if (Configuration::get('PS_SHOP_ENABLE')
          //  || IpUtils::checkIp(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP')))) {


        if (Configuration::get('PS_SHOP_ENABLE')) {
            if ($pageName == 'product' && ($idProduct = Tools::getValue('id_product'))) {
                return Meta::getProductMetas($idProduct, $idLang, $pageName);
            } elseif ($pageName == 'category' && ($idCategory = Tools::getValue('id_category'))) {
                return Meta::getCategoryMetas($idCategory, $idLang, $pageName, $title);
            } elseif ($pageName == 'manufacturer' && ($idManufacturer = Tools::getValue('id_manufacturer'))) {
                return Meta::getManufacturerMetas($idManufacturer, $idLang, $pageName);
            } elseif ($pageName == 'supplier' && ($idSupplier = Tools::getValue('id_supplier'))) {
                return Meta::getSupplierMetas($idSupplier, $idLang, $pageName);
            } elseif ($pageName == 'cms' && ($idCms = Tools::getValue('id_cms'))) {
                return Meta::getCmsMetas($idCms, $idLang, $pageName);
            } elseif ($pageName == 'cms' && ($idCmsCategory = Tools::getValue('id_cms_category'))) {
                return Meta::getCmsCategoryMetas($idCmsCategory, $idLang, $pageName);
            } elseif ($pageName == 'newproductssport' && ($deporte = Tools::getValue('deporte'))) {
                return Meta::getNovedadesMetas($deporte, $idLang, $pageName);
            } elseif ($pageName == 'pricesdropsport' && ($deporte = Tools::getValue('deporte'))) {
                return Meta::getOfertasMetas($deporte, $idLang, $pageName);
            } elseif ($pageName == 'bestsalessport' && ($deporte = Tools::getValue('deporte'))) {
                return Meta::getMejoresMetas($deporte, $idLang, $pageName);
            }
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

	public static function getMejoresMetas($deporte, $idLang, $pageName)
    {
        $id_deporte = Db::getInstance()->getValue("SELECT `id_category` FROM "._DB_PREFIX_."category_lang WHERE (name = '".$deporte."' || name = '".strtoupper($deporte)."' || link_rewrite = '".$deporte."' || link_rewrite = '".str_replace(' ', '-', $deporte)."') AND id_lang = ".Context::getContext()->language->id);
        $category = new Category($id_deporte, $idLang);
        $row = Meta::getPresentedObject($category);
        $row['meta_title'] = Context::getContext()->getTranslator()->trans('Ofertas de %d% y descuentos - Álvarez Deporte y tiempo libre', ['%d%' => $deporte], 'Admin.Meta');
        $row['meta_description'] = Context::getContext()->getTranslator()->trans('Encuentra todas las ofertas de %d% disponibles en la sección de %d%  de Álvarez. Entra y descubre increibles descuentos en tus productos favoritos de %d%.', ['%d%' => $deporte], 'Admin.Meta');
        return Meta::completeMetaTags($row, $row['meta_title']);
    }

    public static function getOfertasMetas($deporte, $idLang, $pageName)
    {
        $id_deporte = Db::getInstance()->getValue("SELECT `id_category` FROM "._DB_PREFIX_."category_lang WHERE (name = '".$deporte."' || name = '".strtoupper($deporte)."' || link_rewrite = '".$deporte."' || link_rewrite = '".str_replace(' ', '-', $deporte)."') AND id_lang = ".Context::getContext()->language->id);
        $category = new Category($id_deporte, $idLang);
        $row = Meta::getPresentedObject($category);
        $row['meta_title'] = Context::getContext()->getTranslator()->trans('Ofertas de %d% y descuentos - Álvarez Deporte y tiempo libre', ['%d%' => $deporte], 'Admin.Meta');
        $row['meta_description'] = Context::getContext()->getTranslator()->trans('Encuentra todas las ofertas de %d% disponibles en la sección de %d%  de Álvarez. Entra y descubre increibles descuentos en tus productos favoritos de %d%.', ['%d%' => $deporte], 'Admin.Meta');
        return Meta::completeMetaTags($row, $row['meta_title']);
    }

    public static function getNovedadesMetas($deporte, $idLang, $pageName)
    {
        $id_deporte = Db::getInstance()->getValue("SELECT `id_category` FROM "._DB_PREFIX_."category_lang WHERE (name = '".$deporte."' || name = '".strtoupper($deporte)."' || link_rewrite = '".$deporte."' || link_rewrite = '".str_replace(' ', '-', $deporte)."') AND id_lang = ".Context::getContext()->language->id);
        $category = new Category($id_deporte, $idLang);
        $row = Meta::getPresentedObject($category);
        $row['meta_title'] = Context::getContext()->getTranslator()->trans('Álvarez - Novedades - Tu tienda de deportes online y tiempo libre', ['%d%' => $deporte], 'Admin.Meta');
        $row['meta_description'] = Context::getContext()->getTranslator()->trans('Todos los productos en deporte y tiempo libre: caza, pesca, golf, buceo, esquí y mucho más', ['%d%' => $deporte], 'Admin.Meta');
        return Meta::completeMetaTags($row, $row['meta_title']);
    }
	
   
}