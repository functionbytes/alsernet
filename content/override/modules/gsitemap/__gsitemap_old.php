<?php
/**
 * 2007-2020 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class GsitemapOverride extends Gsitemap
{
    /**
     * Create the Google sitemap by Shop
     *
     * @param int $id_shop Shop identifier
     *
     * @return bool
     */
    public function createSitemap($id_shop = 0)
    {
        if (@fopen($this->normalizeDirectory(_PS_ROOT_DIR_) . '/test.txt', 'wb') == false) {
            $this->context->smarty->assign('google_maps_error', $this->trans('An error occured while trying to check your file permissions. Please adjust your permissions to allow PrestaShop to write a file in your root directory.', array(), 'Modules.Gsitemap.Admin'));

            return false;
        } else {
            @unlink($this->normalizeDirectory(_PS_ROOT_DIR_) . 'test.txt');
        }

        if ($id_shop != 0) {
            $this->context->shop = new Shop((int) $id_shop);
        }

        $type = Tools::getValue('type') ? Tools::getValue('type') : '';
        /* JLP - 01/06/2022 - SITEMAP SOLO EN ESPAÑOL */
        $languages = Language::getLanguages(true, $this->context->shop->id);
        foreach ($languages as $key => $lang) {
            if ((int) $lang['id_lang'] != (int) Configuration::get('PS_LANG_DEFAULT')) {
                unset($languages[$key]);
            }
        }
        $lang_stop = Tools::getValue('lang') ? true : false;
        $id_obj = Tools::getValue('id') ? (int) Tools::getValue('id') : 0;
        foreach ($languages as $lang) {
            $i = 0;
            $index = (Tools::getValue('index') && Tools::getValue('lang') == $lang['iso_code']) ? (int) Tools::getValue('index') : 0;
            if ($lang_stop && $lang['iso_code'] != Tools::getValue('lang')) {
                continue;
            } elseif ($lang_stop && $lang['iso_code'] == Tools::getValue('lang')) {
                $lang_stop = false;
            }

            $link_sitemap = array();
            $this->type_array = ['home', 'category', 'product', 'blog', 'blogcategory', 'blogpost', 'listmanufacturerall', 'listmanufacturerdeporte', 'manufacturer', 'evento', 'boletin'];
            foreach ($this->type_array as $type_val) {
                if ($type == '' || $type == $type_val) {
                    $function = 'get' . Tools::ucfirst($type_val) . 'Link';
                    if (method_exists($this, $function)) {
                        if (!$this->$function($link_sitemap, $lang, $index, $i, $id_obj)) {
                            return false;
                        }
                    }
                    $type = '';
                    $id_obj = 0;
                }
            }
            $this->recursiveSitemapCreator($link_sitemap, $lang['iso_code'], $index);
            $page = '';
            $index = 0;
        }
        $this->createIndexSitemap();
        Configuration::updateValue('GSITEMAP_LAST_EXPORT', date('r'));
        //Tools::file_get_contents('https://www.google.com/webmasters/sitemaps/ping?sitemap=' . urlencode($this->context->link->getBaseLink() . $this->context->shop->physical_uri . $this->context->shop->virtual_uri . $this->context->shop->id));

        if ($this->cron) {
            die();
        }
        Tools::redirectAdmin('index.php?tab=AdminModules&configure=gsitemap&token=' . Tools::getAdminTokenLite('AdminModules') . '&tab_module=' . $this->tab . '&module_name=gsitemap&validation');
        die();
    }

    /**
     * Hydrate $link_sitemap with categories link
     *
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param string $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     * @param int $id_category category object identifier
     *
     * @return bool
     */
    protected function getCategoryLink(&$link_sitemap, $lang, &$index, &$i, $id_category = 0)
    {
        $link = new Link();
        if (method_exists('ShopUrl', 'resetMainDomainCache')) {
            ShopUrl::resetMainDomainCache();
        }

        $categories_id = Db::getInstance()->ExecuteS('SELECT c.id_category FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON c.`id_category` = cs.`id_category`
                WHERE c.`id_category` >= ' . (int) $id_category . ' AND c.`active` = 1 AND c.`id_category` != ' . (int) Configuration::get('PS_ROOT_CATEGORY') . ' AND c.id_category != ' . (int) Configuration::get('PS_HOME_CATEGORY') . ' AND c.id_parent > 0 AND c.`id_category` > 0 AND cs.`id_shop` = ' . (int) $this->context->shop->id . ' ORDER BY c.`id_category` ASC');

        foreach ($categories_id as $category_id) {
            $category = new Category((int) $category_id['id_category'], (int) $lang['id_lang']);
            //$url = $link->getCategoryLink($category, urlencode($category->link_rewrite), (int) $lang['id_lang']);
            $url = $link->getCategoryLink($category->id, urlencode($category->link_rewrite), (int) $lang['id_lang']);

            if ($category->id_image) {
                $image_link = $this->context->link->getCatImageLink($category->link_rewrite, (int) $category->id_image, ImageType::getFormattedName('category'));
                $image_link = (!in_array(rtrim(Context::getContext()->shop->virtual_uri, '/'), explode('/', $image_link))) ? str_replace(array(
                    'https',
                    Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri,
                ), array(
                    'http',
                    Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri . Context::getContext()->shop->virtual_uri,
                ), $image_link) : $image_link;
            }
            $file_headers = (Configuration::get('GSITEMAP_CHECK_IMAGE_FILE')) ? @get_headers($image_link) : true;
            $image_category = array();
            if (isset($image_link) && ($file_headers[0] != 'HTTP/1.1 404 Not Found' || $file_headers === true)) {
                $image_category = array(
                    'title_img' => htmlspecialchars(strip_tags($category->name)),
                    'caption' => Tools::substr(htmlspecialchars(strip_tags($category->description)), 0, 350),
                    'link' => $image_link,
                );
            }

            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'category',
                'page' => 'category',
                'lastmod' => $category->date_upd,
                'link' => $url,
                'image' => $image_category,
            ), $lang['iso_code'], $index, $i, (int) $category_id['id_category'])) {
                return false;
            }

            unset($image_link);
        }

        return true;
    }

    /**
     * Hydrate $link_sitemap with products link
     *
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param string $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     * @param int $id_product product object identifier
     *
     * @return bool
     */
    protected function getProductLink(&$link_sitemap, $lang, &$index, &$i, $id_product = 0)
    {
        $link = new Link();
        if (method_exists('ShopUrl', 'resetMainDomainCache')) {
            ShopUrl::resetMainDomainCache();
        }

        /* JLP - 01/06/2022 - TODOS LOS PRODUCTOS - VISIBLES Y NO VISIBLES */
        $sql = 'SELECT ps.`id_product` FROM `' . _DB_PREFIX_ . 'product_shop` ps INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product`=ps.`id_product` AND pl.`id_shop`='.$this->context->shop->id.' AND pl.`id_lang`='.(int) $lang['id_lang'].' WHERE ps.`id_product` >= ' . (int) $id_product . ' AND ps.`active` = 1 AND ps.`id_shop`=' . $this->context->shop->id . ' ORDER BY ps.`id_product` ASC';
        $products_id = Db::getInstance()->ExecuteS($sql);

        foreach ($products_id as $product_id) {
            $product = new Product((int) $product_id['id_product'], false, (int) $lang['id_lang']);

            $url = $link->getProductLink($product, $product->link_rewrite, htmlspecialchars(strip_tags($product->category)), $product->ean13, (int) $lang['id_lang'], (int) $this->context->shop->id, 0);

            $images_product = array();
            foreach($product->getImages($lang) as $id_image) {
                if (isset($id_image['id_image'])) {
                    $image_link = $this->context->link->getImageLink($product->link_rewrite, $product->id . '-' . (int) $id_image['id_image'], ImageType::getFormattedName('large'));
                    $image_link = (!in_array(rtrim(Context::getContext()->shop->virtual_uri, '/'), explode('/', $image_link))) ? str_replace(array(
                        'https',
                        Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri,
                    ), array(
                        'http',
                        Context::getContext()->shop->domain . Context::getContext()->shop->physical_uri . Context::getContext()->shop->virtual_uri,
                    ), $image_link) : $image_link;
                }
                $file_headers = (Configuration::get('GSITEMAP_CHECK_IMAGE_FILE')) ? @get_headers($image_link) : true;
                if (isset($image_link) && ($file_headers[0] != 'HTTP/1.1 404 Not Found' || $file_headers === true)) {
                    $images_product[] = array(
                        'title_img' => htmlspecialchars(strip_tags($product->name)),
                        'caption' => htmlspecialchars(strip_tags($product->meta_description)),
                        'link' => $image_link,
                    );
                }
                unset($image_link);
            }

            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'product',
                'page' => 'product',
                'lastmod' => $product->date_upd,
                'link' => $url,
                'images' => $images_product,
            ), $lang['iso_code'], $index, $i, $product_id['id_product'])) {
                return false;
            }
        }

        return true;
    }

    protected function getListmanufacturerallLink(&$link_sitemap, $lang, &$index, &$i, $id_obj = 0)
    {
        $link = new Link();

        // URL listado todas las marcas
        return $this->addLinkToSitemap($link_sitemap, array(
            'type' => 'listmanufacturerall',
            'page' => 'listmanufacturerall',
            'link' => $link->getPageLink('manufacturerdeporte', null, (int) $lang['id_lang']),
            'images' => false
        ), $lang['iso_code'], $index, $i, -1);
    }

    protected function getListmanufacturerdeporteLink(&$link_sitemap, $lang, &$index, &$i, $id_category = 0)
    {
        $link = new Link();

        // URL listado marcas por deporte
        $sql = 'SELECT d.`id_category` FROM `'._DB_PREFIX_.'deportes` d INNER JOIN `'._DB_PREFIX_.'deporte_origen` do ON do.`id`=d.`id_deporte_origen` AND d.`id_category`>='.(int) $id_category.' ORDER BY d.`id_category` ASC';
        $deportes_category = DB::getInstance()->executeS($sql);

        foreach ($deportes_category as $category_id) {
            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'listmanufacturerdeporte',
                'page' => 'listmanufacturerdeporte',
                'link' => $link->getMarcasDeporteLink((int) $category_id['id_category'], null, (int) $lang['id_lang']),
                'images' => false,
            ), $lang['iso_code'], $index, $i, $category_id['id_category'])) {
                return false;
            }
        }

        return true;
    }

    protected function getManufacturerLink(&$link_sitemap, $lang, &$index, &$i, $id_manufacturer = 0)
    {
        $link = new Link();

        // URL listado marcas por deporte
        $sql = 'SELECT m.`id_manufacturer` FROM `'._DB_PREFIX_.'manufacturer` m INNER JOIN `'._DB_PREFIX_.'manufacturer_shop` ms ON ms.`id_manufacturer`=m.`id_manufacturer` AND ms.`id_shop`='.$this->context->shop->id.' INNER JOIN `'._DB_PREFIX_.'manufacturer_lang` ml ON ml.`id_manufacturer`=m.`id_manufacturer` AND ml.`id_lang`='.(int) $lang['id_lang'].' WHERE m.`active`=1 AND m.`id_manufacturer` >= '.(int) $id_manufacturer.' ORDER BY m.`id_manufacturer` ASC';
        $manufacturer = DB::getInstance()->executeS($sql);

        foreach ($manufacturer as $manufacturer_id) {
            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'manufacturer',
                'page' => 'manufacturer',
                'link' => $link->getManufacturerLink((int) $manufacturer_id['id_manufacturer'], null, (int) $lang['id_lang']),
                'images' => false,
            ), $lang['iso_code'], $index, $i, $manufacturer_id['id_manufacturer'])) {
                return false;
            }
        }

        return true;
    }

    // protected function getEventoLink(&$link_sitemap, $lang, &$index, &$i, $id_evento = 0)
    // {
    //     // URL eventos
    //     $sql = 'SELECT ec.`id`, apl.`friendly_url`
    //             FROM `'._DB_PREFIX_.'evento_categoria` ec
    //             INNER JOIN `'._DB_PREFIX_.'urls_eventos` ue ON ue.`etiqueta_evento`=ec.`etiqueta`
    //             INNER JOIN `'._DB_PREFIX_.'appagebuilder_profiles` ap ON ap.`profile_key`=ue.`id_profile`
    //             INNER JOIN `'._DB_PREFIX_.'appagebuilder_profiles_shop` aps ON aps.`id_appagebuilder_profiles`=ap.`id_appagebuilder_profiles` AND aps.`id_shop`='.$this->context->shop->id.'
    //             INNER JOIN `'._DB_PREFIX_.'appagebuilder_profiles_lang` apl ON apl.`id_appagebuilder_profiles`=ap.`id_appagebuilder_profiles` AND apl.`id_lang`='.(int) $lang['id_lang'].'
    //             WHERE ec.`activo`=1 AND ec.`id`>='.(int) $id_evento.'
    //             ORDER BY ec.`id` ASC';
    //     $evento = DB::getInstance()->executeS($sql);

    //     foreach ($evento as $evento_id) {
    //         if (!$this->addLinkToSitemap($link_sitemap, array(
    //             'type' => 'evento',
    //             'page' => 'evento',
    //             'link' => $this->context->link->getBaseLink().$evento_id['friendly_url'].'.html',
    //             'images' => false,
    //         ), $lang['iso_code'], $index, $i, $evento_id['id'])) {
    //             return false;
    //         }
    //     }

    //     return true;
    // }

    protected function getBoletinLink(&$link_sitemap, $lang, &$index, &$i, $id_category = 0)
    {
        $link = new Link();

        // URL boletines por deporte
        $sql = 'SELECT d.`id_category`
                FROM `'._DB_PREFIX_.'deportes` d
                INNER JOIN `'._DB_PREFIX_.'boletines` b ON b.`id_deporte`=d.`id_deporte_origen` AND b.`id_idioma`='.(int) $lang['id_lang'].'
                WHERE d.`id_category` >= '.(int) $id_category.'
                GROUP BY d.`id_category`
                ORDER BY d.`id_category` ASC';
        $boletin_category = DB::getInstance()->executeS($sql);

        foreach ($boletin_category as $category_id) {
            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'boletin',
                'page' => 'boletin',
                'link' => $link->getBoletinesDeporteLink((int) $category_id['id_category'], null, (int) $lang['id_lang']),
                'images' => false,
            ), $lang['iso_code'], $index, $i, $category_id['id_category'])) {
                return false;
            }
        }

        return true;
    }

    protected function getBlogLink(&$link_sitemap, $lang, &$index, &$i, $id_obj = 0)
    {
        $blog_module = Module::getInstanceByName('ybc_blog');

        // URL blog
        return $this->addLinkToSitemap($link_sitemap, array(
            'type' => 'blog',
            'page' => 'blog',
            'link' => $blog_module->getLink('blog', [], (int) $lang['id_lang']),
            'images' => false
        ), $lang['iso_code'], $index, $i, -1);
    }

    protected function getBlogcategoryLink(&$link_sitemap, $lang, &$index, &$i, $id_category = 0)
    {
        $blog_module = Module::getInstanceByName('ybc_blog');

        // URL categorias blog
        $sql = 'SELECT c.`id_category`, c.`datetime_modified`
                FROM `'._DB_PREFIX_.'ybc_blog_category` c
                INNER JOIN `'._DB_PREFIX_.'ybc_blog_category_shop` cs ON (c.`id_category`=cs.`id_category` AND cs.`id_shop`='.(int) $this->context->shop->id.')
                INNER JOIN `'._DB_PREFIX_.'ybc_blog_category_lang` cl ON c.`id_category` = cl.`id_category` AND cl.`id_lang`='.(int) $lang['id_lang'].'
                WHERE c.`enabled`=1 AND c.`id_category` >= '.(int) $id_category .'
                ORDER BY c.`id_category` ASC';
        $blog_categories = Db::getInstance()->executeS($sql);

        foreach ($blog_categories as $category_id) {
            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'blogcategory',
                'page' => 'blogcategory',
                'lastmod' => $category_id['datetime_modified'],
                'link' => $blog_module->getLink('blog', ['id_category' => $category_id['id_category']], (int) $lang['id_lang']),
                'images' => false,
            ), $lang['iso_code'], $index, $i, $category_id['id_category'])) {
                return false;
            }
        }

        return true;
    }

    protected function getBlogpostLink(&$link_sitemap, $lang, &$index, &$i, $id_post = 0)
    {
        $blog_module = Module::getInstanceByName('ybc_blog');

        // URL entradas blog
        $sql = 'SELECT p.`id_post`, p.`datetime_modified`
                FROM `'._DB_PREFIX_.'ybc_blog_post` p
                INNER JOIN `'._DB_PREFIX_.'ybc_blog_post_shop` ps ON (p.`id_post`=ps.`id_post` AND ps.`id_shop`='.(int) $this->context->shop->id.')
                INNER JOIN `'._DB_PREFIX_.'ybc_blog_post_lang` pl ON p.`id_post` = pl.`id_post` AND pl.`id_lang` = '.(int) $lang['id_lang'].'
                WHERE (p.`enabled`=1 OR p.`enabled`=-1) AND p.`id_post` >= '.(int) $id_post.'
                GROUP BY p.`id_post`
                ORDER BY p.`id_post` ASC';
        $blog_posts = Db::getInstance()->executeS($sql);

        foreach ($blog_posts as $post_id) {
            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'blogpost',
                'page' => 'blogpost',
                'lastmod' => $post_id['datetime_modified'],
                'link' => $blog_module->getLink('blog', ['id_post' => $post_id['id_post']], (int) $lang['id_lang']),
                'images' => false,
            ), $lang['iso_code'], $index, $i, $post_id['id_post'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create the index file for all generated sitemaps
     *
     * @return bool
     */
    protected function createIndexSitemap()
    {
        $sitemaps = Db::getInstance()->ExecuteS('SELECT `link` FROM `' . _DB_PREFIX_ . 'gsitemap_sitemap` WHERE id_shop = ' . $this->context->shop->id);
        if (!$sitemaps) {
            return false;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>';
        $xml_feed = new SimpleXMLElement($xml);

        foreach ($sitemaps as $link) {
            $sitemap = $xml_feed->addChild('sitemap');
            $sitemap->addChild('loc', $this->context->link->getBaseLink() . $link['link']);
            $sitemap->addChild('lastmod', date('c'));
        }
        file_put_contents($this->normalizeDirectory(_PS_ROOT_DIR_) . $this->context->shop->id . '_index_sitemap.xml', $xml_feed->asXML());

        return true;
    }
}
