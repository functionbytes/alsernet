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
        /*foreach ($languages as $key => $lang) {
            if ((int) $lang['id_lang'] != (int) Configuration::get('PS_LANG_DEFAULT')) {
                unset($languages[$key]);
            }
        }*/
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
            $this->type_array = ['home', 'category', 'product', 'listmanufacturerall', 'listmanufacturerdeporte', 'manufacturer', 'evento', 'boletin'];


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
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param string $lang the language of link to add
     * @param int $index the index of the current Google sitemap file
     *
     * @return bool
     *
     * AÑADIDA ADDIS PARA CAMBIO PRIORIDAD - JAMS - 20250117
     */
    protected function recursiveSitemapCreator($link_sitemap, $lang, &$index)
    {
        if (!count($link_sitemap)) {
            return false;
        }

        $sitemap_link = $this->context->shop->id . '_' . $lang . '_' . $index . '_sitemap.xml';
        $write_fd = fopen($this->normalizeDirectory(_PS_ROOT_DIR_) . $sitemap_link, 'wb');

        fwrite($write_fd, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL);
        foreach ($link_sitemap as $file) {
            if (isset($file['link']) && $file['link'] != '') {
                fwrite($write_fd, '<url>' . PHP_EOL);
                $lastmod = (isset($file['lastmod']) && !empty($file['lastmod'])) ? date('c', strtotime($file['lastmod'])) : null;

                /*MODIFICACION SITEMAP*/
                if (isset($file['id_cat']) && $file['id_cat'] != '') {
                    $id_object = $file['id_cat'];
                }else if(isset($file['id_prod']) && $file['id_prod'] != ''){
                    $id_object = $file['id_prod'];
                }else{
                    $id_object = 0;
                }
                if (isset($file['priority']) && $file['priority'] != '') {
                    $priority = $file['priority'];
                }else{
                    $priority = $this->getPriorityPage($file['page']);
                }
                $this->addSitemapNode($write_fd, htmlspecialchars(strip_tags($file['link'])), $priority, Configuration::get('GSITEMAP_FREQUENCY'), $lastmod, $id_object);
                /*HASTA AQUI*/

                $images = [];
                if (isset($file['image']) && $file['image']) {
                    $images[] = $file['image'];
                }
                if (isset($file['images']) && $file['images']) {
                    $images = array_merge($images, $file['images']);
                }
                foreach ($images as $image) {
                    $this->addSitemapNodeImage($write_fd, htmlspecialchars(strip_tags($image['link'])), isset($image['title_img']) ? htmlspecialchars(str_replace([
                        "\r\n",
                        "\r",
                        "\n",
                    ], '', $this->removeControlCharacters(strip_tags($image['title_img'])))) : '', isset($image['caption']) ? htmlspecialchars(str_replace([
                        "\r\n",
                        "\r",
                        "\n",
                    ], '', strip_tags($image['caption']))) : '');
                }
                fwrite($write_fd, '</url>' . PHP_EOL);
            }
        }
        fwrite($write_fd, '</urlset>' . PHP_EOL);
        fclose($write_fd);
        $this->saveSitemapLink($sitemap_link);
        ++$index;

        return true;
    }

    /**
     * Add a new line to the sitemap file - ADDIS
     *
     * @param resource $fd file system object resource
     * @param string $loc string the URL of the object page
     * @param string $priority
     * @param string $change_freq
     * @param int $last_mod the last modification date/time as a timestamp
     */
    /*OLD protected function addSitemapNode($fd, $loc, $priority, $change_freq, $last_mod = null, $id_object = 0)
    {
        fwrite($fd, '<loc>' . (Configuration::get('PS_REWRITING_SETTINGS') ? '<![CDATA[' . $loc . ']]>' : $loc) . '</loc>' . PHP_EOL . ($last_mod ? '<lastmod>' . date('c', strtotime($last_mod)) . '</lastmod>' : '') . PHP_EOL . '<id_object>' . $id_object . '</id_object>' . PHP_EOL . '<changefreq>' . $change_freq . '</changefreq>' . PHP_EOL . '<priority>' . number_format($priority, 1, '.', '') . '</priority>' . PHP_EOL);
    }*/

    protected function addSitemapNode($fd, $loc, $priority, $change_freq, $last_mod = null, $id_object = 0)
    {
        fwrite($fd, '<loc>' . (Configuration::get('PS_REWRITING_SETTINGS') ? '<![CDATA[' . $loc . ']]>' : $loc) . '</loc>' . PHP_EOL . ($last_mod ? '<lastmod>' . date('c', strtotime($last_mod)) . '</lastmod>' : '') . PHP_EOL . '<changefreq>' . $change_freq . '</changefreq>' . PHP_EOL . '<priority>' . number_format($priority, 1, '.', '') . '</priority>' . PHP_EOL);
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

        /*JAMS - 20250116 - MODIFICACIONES SITEMAPS (EXCLUIR CATEGORIAS)*/
        /*DESDE AQUI SACO LAS CATEGORIAS DE UN MÁXIMO DE 3 NIVELES QUE NO SE INCLUIRÁN EN EL SITEMAP*/
        /*ESTO SE HARÁ EN EL CASO DE QUE LA PADRE ESTE MARCADA COMO NO SITEMAP*/
        $categorias_no_sitemap = Db::getInstance()->ExecuteS("SELECT id_category FROM "._DB_PREFIX_."category_lang WHERE add_sitemap = 0 AND id_lang = ".$lang['id_lang']);
        //Sacar las categorias hijas
        $categorias_no_sitemap_aux = array();
        foreach ($categorias_no_sitemap as $cat_no_sitemap) {
            $categorias_no_sitemap_aux[] = $cat_no_sitemap['id_category'];
            $categorias_no_sitemap_aux2[] = Db::getInstance()->ExecuteS("SELECT cl.id_category FROM "._DB_PREFIX_."category c, "._DB_PREFIX_."category_lang cl WHERE c.id_category = cl.id_category AND c.id_parent = ".$cat_no_sitemap['id_category']." AND cl.id_lang = ".$lang['id_lang']);
            $cont = 0;
            foreach ($categorias_no_sitemap_aux2[$cont] as $cat_no_sitemap_a2) {
                $categorias_no_sitemap_aux[] = $cat_no_sitemap_a2['id_category'];
                $cont = $cont + 1;
                $categorias_no_sitemap_aux3[$cont] = Db::getInstance()->ExecuteS("SELECT cl.id_category FROM "._DB_PREFIX_."category c, "._DB_PREFIX_."category_lang cl WHERE c.id_category = cl.id_category AND c.id_parent = ".$cat_no_sitemap_a2['id_category']." AND cl.id_lang = ".$lang['id_lang']);
                $cont2 = 0;
                foreach ($categorias_no_sitemap_aux3[$cont] as $cat_no_sitemap_a3) {
                    $categorias_no_sitemap_aux[] = $cat_no_sitemap_a3['id_category'];
                    $cont2 = $cont2 + 1;
                }
            }
        }
        $categorias_quitar_sitemap = implode(", ",$categorias_no_sitemap_aux);
        /*HASTA AQUI*/

        //Puesto en el where un "not in (categorias de la query add_sitemap)" - JAMS - 20250116
        if (count($categorias_no_sitemap_aux) > 1) {
            $categories_id = Db::getInstance()->ExecuteS('SELECT c.id_category FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON c.`id_category` = cs.`id_category`
                WHERE c.`id_category` >= ' . (int) $id_category . ' AND c.`active` = 1 AND c.`id_category` != ' . (int) Configuration::get('PS_ROOT_CATEGORY') . ' AND c.id_category != ' . (int) Configuration::get('PS_HOME_CATEGORY') . ' AND c.id_parent > 0 AND c.`id_category` > 0 AND cs.`id_shop` = ' . (int) $this->context->shop->id . ' AND c.id_category NOT IN ('.$categorias_quitar_sitemap.') ORDER BY c.`id_category` ASC');
        }else{
            $categories_id = Db::getInstance()->ExecuteS('SELECT c.id_category FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON c.`id_category` = cs.`id_category`
                WHERE c.`id_category` >= ' . (int) $id_category . ' AND c.`active` = 1 AND c.`id_category` != ' . (int) Configuration::get('PS_ROOT_CATEGORY') . ' AND c.id_category != ' . (int) Configuration::get('PS_HOME_CATEGORY') . ' AND c.id_parent > 0 AND c.`id_category` > 0 AND cs.`id_shop` = ' . (int) $this->context->shop->id . ' ORDER BY c.`id_category` ASC');
        }

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
                'id_cat' => $category->id,
                'lastmod' => $category->date_upd,
                'link' => $url,
                'priority' => Db::getInstance()->getValue("SELECT prioridad FROM "._DB_PREFIX_."category_lang WHERE id_category = ".$category_id['id_category']." AND id_lang = ".$lang['id_lang']),
                'image' => $image_category,
            ), $lang['iso_code'], $index, $i, (int) $category_id['id_category'])) {
                return false;
            }

            unset($image_link);
        }

        return true;
    }

    /**
     * @param array $link_sitemap contain all the links for the Google sitemap file to be generated
     * @param array $new_link contain the link elements
     * @param string $lang language of link to add
     * @param int $index index of the current Google sitemap file
     * @param int $i count of elements added to sitemap main array
     * @param int $id_obj identifier of the object of the link to be added to the Gogle sitemap file
     *
     * @return bool
	 *
	 * FUNCION OVERRIDE POR ADDIS - JAMS 20250117
     */
    public function addLinkToSitemap(&$link_sitemap, $new_link, $lang, &$index, &$i, $id_obj)
    {
    	if ($i <= 25000 && memory_get_usage() < 100000000) {
        	$link_sitemap[] = $new_link;
            ++$i;

            return true;
        } else {
            $this->recursiveSitemapCreator($link_sitemap, $lang, $index);
            if ($index % 20 == 0 && !$this->cron) {
                $this->context->smarty->assign([
                    'gsitemap_number' => (int) $index,
                    'gsitemap_refresh_page' => $this->context->link->getAdminLink('AdminModules', true, [], [
                        'tab_module' => $this->tab,
                        'module_name' => $this->name,
                        'continue' => 1,
                        'type' => $new_link['type'],
                        'lang' => $lang,
                        'index' => $index,
                        'id' => (int) $id_obj,
                        'id_shop' => $this->context->shop->id,
                    ]),
                ]);

                return false;
            } elseif ($index % 20 == 0 && $this->cron) {
                header('Refresh: 5; url=http' . (Configuration::get('PS_SSL_ENABLED') ? 's' : '') . '://' . Tools::getShopDomain(false, true) . __PS_BASE_URI__ . 'modules/gsitemap/gsitemap-cron.php?continue=1&token=' . Tools::substr(Tools::hash('gsitemap/cron'), 0, 10) . '&type=' . $new_link['type'] . '&lang=' . $lang . '&index=' . $index . '&id=' . (int) $id_obj . '&id_shop=' . $this->context->shop->id);
                exit();
            } else {
                if ($this->cron) {
                    Tools::redirect($this->context->link->getModuleLink(
                        'gsitemap',
                        'cron',
                        [
                            'continue' => '1',
                            'token' => Tools::substr(Tools::hash('gsitemap/cron'), 0, 10),
                            'type' => $new_link['type'],
                            'lang' => $lang,
                            'index' => $index,
                            'id' => (int) $id_obj,
                            'id_shop' => $this->context->shop->id,
                        ]
                    ));
                } else {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [], [
                        'tab_module' => $this->tab,
                        'module_name' => $this->name,
                        'configure' => $this->name,
                        'continue' => 1,
                        'type' => $new_link['type'],
                        'lang' => $lang,
                        'index' => $index,
                        'id' => (int) $id_obj,
                        'id_shop' => $this->context->shop->id,
                    ]));
                }
                exit();
            }
        }
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
        /* JAMS - 20250117 - EXCLUIR LOS PRODUCTOS CON LA CATEGORIA MARCADA COMO EXCLUIDA */
        /*DESDE AQUI SACO LAS CATEGORIAS DE UN MÁXIMO DE 3 NIVELES INTERIORES QUE NO SE INCLUIRÁN EN EL SITEMAP*/
        /*ESTO SE HARÁ EN EL CASO DE QUE LA PADRE ESTE MARCADA COMO NO SITEMAP*/
        $categorias_no_sitemap = Db::getInstance()->ExecuteS("SELECT id_category FROM "._DB_PREFIX_."category_lang WHERE add_sitemap = 0 AND id_lang = ".$lang['id_lang']);
        //Sacar las categorias hijas
        foreach ($categorias_no_sitemap as $cat_no_sitemap) {
            $categorias_no_sitemap_aux[] = $cat_no_sitemap['id_category'];
            $categorias_no_sitemap_aux2[] = Db::getInstance()->ExecuteS("SELECT cl.id_category FROM "._DB_PREFIX_."category c, "._DB_PREFIX_."category_lang cl WHERE c.id_category = cl.id_category AND c.id_parent = ".$cat_no_sitemap['id_category']." AND cl.id_lang = ".$lang['id_lang']);
            $cont = 0;
            foreach ($categorias_no_sitemap_aux2[$cont] as $cat_no_sitemap_a2) {
                $categorias_no_sitemap_aux[] = $cat_no_sitemap_a2['id_category'];
                $cont = $cont + 1;
                $categorias_no_sitemap_aux3[$cont] = Db::getInstance()->ExecuteS("SELECT cl.id_category FROM "._DB_PREFIX_."category c, "._DB_PREFIX_."category_lang cl WHERE c.id_category = cl.id_category AND c.id_parent = ".$cat_no_sitemap_a2['id_category']." AND cl.id_lang = ".$lang['id_lang']);
                $cont2 = 0;
                foreach ($categorias_no_sitemap_aux3[$cont] as $cat_no_sitemap_a3) {
                    $categorias_no_sitemap_aux[] = $cat_no_sitemap_a3['id_category'];
                    $cont2 = $cont2 + 1;
                }
            }
        }
        $categorias_quitar_sitemap = implode(", ",$categorias_no_sitemap_aux);
        /*HASTA AQUI*/

        if (count($categorias_no_sitemap_aux) > 1) {
            $sql = 'SELECT ps.`id_product` FROM `' . _DB_PREFIX_ . 'product_shop` ps INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product`=ps.`id_product` AND pl.`id_shop`='.$this->context->shop->id.' AND pl.`id_lang`='.(int) $lang['id_lang'].' WHERE ps.`id_product` >= ' . (int) $id_product . ' AND ps.`active` = 1 AND ps.`id_shop`=' . $this->context->shop->id . ' AND ps.id_category_default NOT IN ('.$categorias_quitar_sitemap.') ORDER BY ps.`id_product` ASC';
        }else{
            $sql = 'SELECT ps.`id_product` FROM `' . _DB_PREFIX_ . 'product_shop` ps INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product`=ps.`id_product` AND pl.`id_shop`='.$this->context->shop->id.' AND pl.`id_lang`='.(int) $lang['id_lang'].' WHERE ps.`id_product` >= ' . (int) $id_product . ' AND ps.`active` = 1 AND ps.`id_shop`=' . $this->context->shop->id . ' ORDER BY ps.`id_product` ASC';

        }
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
                'id_prod' => $product_id['id_product'],
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

    protected function getEventoLink(&$link_sitemap, $lang, &$index, &$i, $id_evento = 0)
    {
        // URL eventos
        $sql = 'SELECT ec.`id`, apl.`friendly_url`
                FROM `'._DB_PREFIX_.'evento_categoria` ec
                INNER JOIN `'._DB_PREFIX_.'urls_eventos` ue ON ue.`etiqueta_evento`=ec.`etiqueta`
                INNER JOIN `'._DB_PREFIX_.'appagebuilder_profiles` ap ON ap.`profile_key`=ue.`id_profile`
                INNER JOIN `'._DB_PREFIX_.'appagebuilder_profiles_shop` aps ON aps.`id_appagebuilder_profiles`=ap.`id_appagebuilder_profiles` AND aps.`id_shop`='.$this->context->shop->id.'
                INNER JOIN `'._DB_PREFIX_.'appagebuilder_profiles_lang` apl ON apl.`id_appagebuilder_profiles`=ap.`id_appagebuilder_profiles` AND apl.`id_lang`='.(int) $lang['id_lang'].'
                WHERE ec.`activo`=1 AND ec.`id`>='.(int) $id_evento.'
                ORDER BY ec.`id` ASC';
        $evento = DB::getInstance()->executeS($sql);

        foreach ($evento as $evento_id) {
            if (!$this->addLinkToSitemap($link_sitemap, array(
                'type' => 'evento',
                'page' => 'evento',
                'link' => $this->context->link->getBaseLink().$evento_id['friendly_url'].'.html',
                'images' => false,
            ), $lang['iso_code'], $index, $i, $evento_id['id'])) {
                return false;
            }
        }

        return true;
    }

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
