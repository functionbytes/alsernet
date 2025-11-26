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
use PrestaShop\PrestaShop\Adapter\Manufacturer\ManufacturerProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class ManufacturerDeporteControllerCore extends ProductListingFrontController
{
    public $php_self = 'manufacturerdeporte';

    protected $manufacturer;
    protected $label;
    protected $deporte;


    public function canonicalRedirection($canonicalURL = '')
    {
        if (Validate::isLoadedObject($this->manufacturer)) {
            parent::canonicalRedirection($this->context->link->getManufacturerLink($this->manufacturer));
            //parent::canonicalRedirection($this->context->link->getMarcasDeporteLink($this->manufacturer));
        } elseif ($canonicalURL) {
            if ($_GET['deporte']) {
                parent::canonicalRedirection($this->context->link->getMarcasDeporteLink($this->getIdDeporteByName($_GET['deporte'])));
                if ($this->getCurrentURL() != $this->context->link->getMarcasDeporteLink($this->getIdDeporteByName($_GET['deporte']))) {
                    Tools::redirectLink($this->context->link->getMarcasDeporteLink($this->getIdDeporteByName($_GET['deporte'])));
                }
            } else {
                parent::canonicalRedirection($this->context->link->getMarcasDeporteLink(Tools::getValue('id_deporte')));
                if ($this->getCurrentURL() != $this->context->link->getMarcasDeporteLink(Tools::getValue('id_deporte'))) {
                    Tools::redirectLink($this->context->link->getMarcasDeporteLink(Tools::getValue('id_deporte')));
                }
            }
        }
    }

    public function getAlternativeLangsUrl()
    {
        $alternativeLangs = parent::getAlternativeLangsUrl();

        $languages = Language::getLanguages(true, $this->context->shop->id);
        foreach ($languages as $lang) {
            $alternativeLangs[$lang['language_code']] = $this->context->link->getMarcasDeporteLink(Tools::getValue('id_deporte'), null, $lang['id_lang']);
        }

        return $alternativeLangs;
    }

    /**
     * Initialize manufaturer controller.
     *
     * @see FrontController::init()
     */
    public function init()
    {
        parent::init();

        $this->deporte = "" . Tools::getValue('id_deporte');

        if (!$this->deporte || $this->deporte == "") {
            $this->deporte = $this->getIdDeporteByName(Tools::getValue('deporte'));
        }

        // compruebo que el deporte existe
        if ($this->deporte && is_numeric($this->deporte)) {
            $sql = 'SELECT `id_category`
                    FROM `' . _DB_PREFIX_ . 'deportes`
                    WHERE `id_category`=' . (int) $this->deporte;
            $this->deporte = DB::getInstance()->getValue($sql);
            if ($this->deporte && is_numeric($this->deporte)) {
                $this->deporte = (int) $this->deporte;
            } else {
                $this->deporte = '';
            }
        } else {
            $this->deporte = '';
        }

        if ($id_manufacturer = Tools::getValue('id_manufacturer')) {
            $this->manufacturer = new Manufacturer((int) $id_manufacturer, $this->context->language->id);

            if (!Validate::isLoadedObject($this->manufacturer) || !$this->manufacturer->active || !$this->manufacturer->isAssociatedToShop()) {
                $this->redirect_after = '404';
                $this->redirect();
            } else {
                // $this->canonicalRedirection();
            }
        }
    }

    public function getIdDeporteByName($name_deporte)
    {
        //echo "SELECT `id_category` FROM "._DB_PREFIX_."category_lang WHERE name = '".$name_deporte."' AND id_lang = ".$this->context->language->id;
        return Db::getInstance()->getValue("SELECT `id_category` FROM " . _DB_PREFIX_ . "category_lang WHERE (name = '" . $name_deporte . "' || name = '" . strtoupper($name_deporte) . "' || link_rewrite = '" . $name_deporte . "' || link_rewrite = '" . str_replace(' ', '-', $name_deporte) . "') AND id_lang = " . $this->context->language->id);
    }

    /**
     * Assign template vars related to page content.
     *
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        if (Configuration::get('PS_DISPLAY_MANUFACTURERS')) {
            parent::initContent();

            if (Validate::isLoadedObject($this->manufacturer) && $this->manufacturer->active && $this->manufacturer->isAssociatedToShop()) {
                $this->assignManufacturer();
                $this->label = $this->trans(
                    'List of products by brand %brand_name%',
                    [
                        '%brand_name%' => $this->manufacturer->name,
                    ],
                    'Shop.Theme.Catalog'
                );
                $this->doProductSearch(
                    'catalog/listing/manufacturer',
                    ['entity' => 'manufacturer', 'id' => $this->manufacturer->id]
                );
            } else {
                $this->assignAll();
                $this->label = $this->trans(
                    'List of all brands',
                    [],
                    'Shop.Theme.Catalog'
                );
                $this->setTemplate('catalog/manufacturers', ['entity' => 'manufacturers']);
            }
        } else {
            $this->redirect_after = '404';
            $this->redirect();
        }
    }

    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setIdManufacturer($this->manufacturer->id)
            ->setSortOrder(new SortOrder('product', Tools::getProductsOrder('by'), Tools::getProductsOrder('way')));

        return $query;
    }

    protected function getDefaultProductSearchProvider()
    {
        return new ManufacturerProductSearchProvider(
            $this->getTranslator(),
            $this->manufacturer
        );
    }

    /**
     * Assign template vars if displaying one manufacturer.
     */
    protected function assignManufacturer()
    {
        $manufacturerVar = $this->objectPresenter->present($this->manufacturer);
        $filteredManufacturer = Hook::exec(
            'filterManufacturerContent',
            ['filtered_content' => $manufacturerVar['description']],
            $id_module = null,
            $array_return = false,
            $check_exceptions = true,
            $use_push = false,
            $id_shop = null,
            $chain = true
        );
        if (!empty($filteredManufacturer)) {
            $manufacturerVar['description'] = $filteredManufacturer;
        }

        $myid = Db::getInstance()->getValue("SELECT id FROM " . _DB_PREFIX_ . "manufacturer_deporte WHERE id_category_deporte=" . $this->deporte . " and id_manufacturer=" . $this->manufacturer->id);

        $manufacturerVar['texto_superior'] = Db::getInstance()->getValue("SELECT texto_superior FROM " . _DB_PREFIX_ . "manufacturer_deporte_lang WHERE id=" . $myid . " and id_lang=" . $this->context->language->id);
        $manufacturerVar['texto_inferior'] = Db::getInstance()->getValue("SELECT texto_inferior FROM " . _DB_PREFIX_ . "manufacturer_deporte_lang WHERE id=" . $myid . " and id_lang=" . $this->context->language->id);

        $this->context->smarty->assign([
            'manufacturer' => $manufacturerVar,
        ]);
    }

    /**
     * Assign template vars if displaying the manufacturer list.
     */
    protected function assignAll()
    {
        // if ($this->deporte == ""){
        // $manufacturersVar = $this->getTemplateVarManufacturers();
        // dump($manufacturersVar);die();
        // if (!empty($manufacturersVar)) {
        //     foreach ($manufacturersVar as $k => $manufacturer) {
        //         $filteredManufacturer = Hook::exec(
        //             'filterManufacturerContent',
        //             ['filtered_content' => $manufacturer['text']],
        //             $id_module = null,
        //             $array_return = false,
        //             $check_exceptions = true,
        //             $use_push = false,
        //             $id_shop = null,
        //             $chain = true
        //         );
        //         if (!empty($filteredManufacturer)) {
        //             $manufacturersVar[$k]['text'] = $filteredManufacturer;
        //         }
        //     }
        // }
        $result = Db::getInstance()->executeS("SELECT
                                    GROUP_CONCAT(am.id_manufacturer ) as id_manufacturer,
                                    aabc.id_category,
                                    LOWER(acl.name) as name
                                from
                                    aalv_alsernet_brand_category aabc
                                    left join aalv_manufacturer am on am.id_manufacturer = aabc.id_manufacturer
                                    left join aalv_category_lang acl on acl.id_category = aabc.id_category
                                WHERE
                                    acl.id_lang = " . Context::getContext()->language->id . "
                                GROUP BY aabc.id_category");
        $datos = [];

        foreach ($result as $value) {
            if (!isset($value['name']) || empty($value['name'])) {
                continue; // Saltar si no tiene 'name'
            }

            $valores = explode(',', $value['id_manufacturer']);

            foreach ($valores as $val) {
                $name = "" . Db::getInstance()->getValue("SELECT am.name FROM aalv_manufacturer am WHERE am.active = 1 AND am.id_manufacturer = " . (int)$val);

                if ($name != '') {
                    $expectedUrl = Context::getContext()->link->getManufacturerLink(
                        $val,
                        null,
                        Context::getContext()->language->id
                    );

                    // Quitar acentos y caracteres raros
                    $cleanKey = iconv('UTF-8', 'ASCII//TRANSLIT', $value['name']);
                    // Reemplazar espacios u otros símbolos si querés
                    $cleanKey = preg_replace('/[^A-Za-z0-9_]/', '', $cleanKey);
                    $datos[$cleanKey][] = [
                        "name" => $name,
                        "url" => $expectedUrl
                    ];
                }
            }
        }


        // 🔽 Ordenar los elementos internos por 'name' en orden alfabético descendente
        foreach ($datos as &$grupo) {
            usort($grupo, function ($a, $b) {
                return strcmp($a['name'], $b['name']); // Orden descendente
            });
        }

        unset($grupo); // Limpiar referencia
        $deportesVar = $this->getTemplateVarDeportes();
        // dump($datos);
        // die();
        /*$this->context->smarty->assign([
                'brands' => $manufacturersVar,
            ]);*/
        $this->context->smarty->assign([
            'brands' => $datos,
            // 'brandsmain' => [],
            'deportes' => $deportesVar,
            // 'deporteactual' => '',
        ]);
        // }else{
        //     $manufacturersVar = $this->getTemplateVarManufacturersDeporte();
        //     $manufacturersMainVar = $this->getTemplateVarManufacturersMainDeporte();
        //     $deportesVar = $this->getTemplateVarDeportes();

        //     $this->context->smarty->assign([
        //         'brands' => $manufacturersVar,
        //         'brandsmain' => $manufacturersMainVar,
        //         'deportes' => 0,
        //         'deporteactual' => $this->deporte,
        //     ]);
        // }
    }

    public function getTemplateVarDeportes()
    {
        // 1	Español (Spanish)
        // 2	English (English)
        // 3	Français (French)
        // 4	Português (Portuguese)
        // 5	Deutsch (German)
        // 6	Italiano (Italian)
        $exclusiones_por_idioma = [
            1 => [104762, 2821, 2820],
            2 => [104762, 2821, 2820],
            3 => [104762, 2821, 2820],
            4 => [104762, 2821, 2820],
            5 => [104762, 2821, 2820],
            6 => [104762, 2821, 2820]
        ];

        $id_lang = (int)$this->context->language->id;

        $deportes = Category::getHomeCategories($id_lang);

        if (isset($exclusiones_por_idioma[$id_lang])) {
            $exclusiones = $exclusiones_por_idioma[$id_lang];

            $deportes = array_filter($deportes, function ($categoria) use ($exclusiones) {
                return !in_array((int)$categoria['id_category'], $exclusiones);
            });

            // Opcionalmente, reindexar el array para evitar huecos en los índices
            $deportes = array_values($deportes);
        }

        $i = 0;
        foreach ($deportes as $deporte) {

            $nummarcas = Db::getInstance()->getValue("  select
                                                            count(aabc.id) AS count_manufacturer
                                                        from
                                                            `" . _DB_PREFIX_ . "alsernet_brand_category` aabc
                                                            left join `" . _DB_PREFIX_ . "manufacturer` am on am.id_manufacturer = aabc.id_manufacturer
                                                        where
                                                            am.active = 1
                                                            and aabc.id_category = " . $deporte['id_category']);

            $deportes[$i]['subcats'] = $nummarcas;
            $i = $i + 1;
        }
        return $deportes;
    }


    public function getTemplateVarManufacturersDeporte()
    {


        $cache_id = 'getTemplateVarManufacturersDeporte_' . $this->deporte . '_' . $this->context->language->id;
        if (Cache::isStored($cache_id)) {
            return Cache::retrieve($cache_id);
        }


        $manufacturers_for_display = [];
        //$manufacturers = Db::getInstance()->ExecuteS("SELECT a.id_manufacturer, b.name FROM "._DB_PREFIX_."manufacturer_deporte a inner join "._DB_PREFIX_."manufacturer b on a.id_manufacturer=b.id_manufacturer WHERE id_category_deporte=".$this->deporte. " and tiene_productos=1 order by b.name");
        $cat = $this->deporte;
        $catdep = new Category($cat);
        $categories = $catdep->getAllChildren();
        $listids = [];
        $listids[] = (int)$cat;
        foreach ($categories as $category) {
            $listids[] = (int)$category->id;
        }

        $sql = "select id_manufacturer, (select b.name from aalv_manufacturer b where b.id_manufacturer=a.id_manufacturer) 'name' from aalv_product a where a.id_product in (SELECT id_product FROM aalv_category_product WHERE id_category in (" . implode(",", $listids) . ")) and a.id_product in (SELECT id_product FROM aalv_combinacionunica_import union select id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import)) and a.active=1 and a.visibility='both' and a.id_manufacturer<>0 order by name";
        $manufacturers = Db::getInstance()->ExecuteS($sql);

        foreach ($manufacturers as $manufacturer) {
            $id_manu = $manufacturer['id_manufacturer'];
            $manufacturers_for_display[$id_manu]['id_manufacturer'] = $id_manu;
            $manufacturers_for_display[$id_manu]['name'] = $manufacturer['name'];
            $manufacturers_for_display[$id_manu]['image'] = $this->context->link->getManufacturerImageLink($id_manu, 'small_default');
            $manufacturers_for_display[$id_manu]['url'] = '?id_deporte=' . $this->deporte . '&id_manufacturer=' . $id_manu;
            //$manufacturers_for_display[$id_manu]['url'] = $this->context->link->getManufacturerLink($id_manu);
            //$numprod=Db::getInstance()->getValue('SELECT COUNT(DISTINCT p.id_product) as nb_products FROM ' . _DB_PREFIX_ . 'product p USE INDEX (product_manufacturer)' . Shop::addSqlAssociation('product', 'p') . ' WHERE p.id_manufacturer != 0 AND product_shop.visibility NOT IN ("none") AND product_shop.active = 1 ');
            // $manufacturers_for_display[$id_manu]['nb_products'] = $numprod > 1 ? ($this->trans('%number% products', ['%number%' => $numprod], 'Shop.Theme.Catalog')) : $this->trans('%number% product', ['%number%' => $numprod], 'Shop.Theme.Catalog');
        }

        if (!Cache::isStored($cache_id)) {
            Cache::store($cache_id, $manufacturers_for_display);
        } else {
            $manufacturers_for_display = Cache::retrieve($cache_id);
        }
        return $manufacturers_for_display;
    }

    public function getTemplateVarManufacturersMainDeporte()
    {
        $cache_id = 'getTemplateVarManufacturersMainDeporte_' . $this->deporte . '_' . $this->context->language->id;
        if (Cache::isStored($cache_id)) {
            return Cache::retrieve($cache_id);
        }

        $manufacturers_for_display = [];
        $manufacturers = Db::getInstance()->ExecuteS("SELECT a.id_manufacturer, b.name FROM " . _DB_PREFIX_ . "manufacturer_deporte a inner join " . _DB_PREFIX_ . "manufacturer b on a.id_manufacturer=b.id_manufacturer WHERE id_category_deporte=" . $this->deporte . " and destacado=1 and tiene_productos=1 order by orden");

        foreach ($manufacturers as $manufacturer) {
            $id_manu = $manufacturer['id_manufacturer'];
            $manufacturers_for_display[$id_manu]['id_manufacturer'] = $id_manu;
            $manufacturers_for_display[$id_manu]['name'] = $manufacturer['name'];

            $manufacturers_for_display[$id_manu]['image'] = $this->context->link->getManufacturerImageLink($id_manu, 'small_default');
            $manufacturers_for_display[$id_manu]['url'] = '?id_deporte=' . $this->deporte . '&id_manufacturer=' . $id_manu;
            //$manufacturers_for_display[$id_manu]['url'] = $this->context->link->getManufacturerLink($id_manu);

            //$numprod=Db::getInstance()->getValue('SELECT COUNT(DISTINCT p.id_product) as nb_products FROM ' . _DB_PREFIX_ . 'product p USE INDEX (product_manufacturer)' . Shop::addSqlAssociation('product', 'p') . ' WHERE p.id_manufacturer != 0 AND product_shop.visibility NOT IN ("none") AND product_shop.active = 1 ');

            //$manufacturers_for_display[$id_manu]['nb_products'] = $numprod > 1 ? ($this->trans('%number% products', ['%number%' => $numprod], 'Shop.Theme.Catalog')) : $this->trans('%number% product', ['%number%' => $numprod], 'Shop.Theme.Catalog');

        }

        if (!Cache::isStored($cache_id)) {
            Cache::store($cache_id, $manufacturers_for_display);
        } else {
            $manufacturers_for_display = Cache::retrieve($cache_id);
        }
        return $manufacturers_for_display;
    }


    public function getTemplateVarManufacturers()
    {
        $cache_id = 'getTemplateVarManufacturers_' . $this->context->language->id;
        if (Cache::isStored($cache_id)) {
            return Cache::retrieve($cache_id);
        }
        $manufacturers = Manufacturer::getManufacturers(true, $this->context->language->id, true, $this->p, $this->n, false);
        $manufacturers_for_display = [];

        foreach ($manufacturers as $manufacturer) {
            $manufacturers_for_display[$manufacturer['id_manufacturer']] = $manufacturer;
            $manufacturers_for_display[$manufacturer['id_manufacturer']]['text'] = $manufacturer['short_description'];
            $manufacturers_for_display[$manufacturer['id_manufacturer']]['image'] = $this->context->link->getManufacturerImageLink($manufacturer['id_manufacturer'], 'small_default');
            $manufacturers_for_display[$manufacturer['id_manufacturer']]['url'] = '?id_deporte=' . $this->deporte . '&id_manufacturer=' . $manufacturer['id_manufacturer'];
            //$manufacturers_for_display[$manufacturer['id_manufacturer']]['url'] = $this->context->link->getManufacturerLink($manufacturer['id_manufacturer']);
            $manufacturers_for_display[$manufacturer['id_manufacturer']]['nb_products'] = $manufacturer['nb_products'] > 1 ? ($this->trans('%number% products', ['%number%' => $manufacturer['nb_products']], 'Shop.Theme.Catalog')) : $this->trans('%number% product', ['%number%' => $manufacturer['nb_products']], 'Shop.Theme.Catalog');
        }

        if (!Cache::isStored($cache_id)) {
            Cache::store($cache_id, $manufacturers_for_display);
        } else {
            $manufacturers_for_display = Cache::retrieve($cache_id);
        }
        return $manufacturers_for_display;
    }

    public function getListingLabel()
    {
        return $this->label;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        if ($this->deporte != "") {
            $catdep = new Category($this->deporte);
            $breadcrumb['links'][] = [
                'title' => $catdep->name[$this->context->language->id],
                'url' => $this->context->link->getCategoryLink($this->deporte),
            ];
        }

        $breadcrumb['links'][] = [
            'title' => $this->getTranslator()->trans('Brands', [], 'Shop.Theme.Global'),
            //'url' => $this->context->link->getPageLink('manufacturer', true),
            'url' => '?id_deporte=' . $this->deporte,

        ];

        /*if (Validate::isLoadedObject($this->manufacturer) && $this->manufacturer->active && $this->manufacturer->isAssociatedToShop()) {
            $breadcrumb['links'][] = [
                'title' => $this->manufacturer->name,
                'url' => $this->context->link->getManufacturerLink($this->manufacturer),
            ];
        }*/
        return $breadcrumb;
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();

        if (Validate::isLoadedObject($this->manufacturer) && $this->manufacturer->active && $this->manufacturer->isAssociatedToShop()) {
            $page['body_classes']['layout-left-column'] = true;
            $page['body_classes']['layout-full-width'] = false;
        } else {
            $page['body_classes']['layout-left-column'] = false;
            $page['body_classes']['layout-full-width'] = true;
        }

        return $page;
    }

    public function getLayout()
    {
        $entity = $this->php_self;
        if (empty($entity)) {
            $entity = $this->getPageName();
        }

        if (Validate::isLoadedObject($this->manufacturer) && $this->manufacturer->active && $this->manufacturer->isAssociatedToShop()) {
            $layout = 'layouts/layout-left-column.tpl';
        } else {
            $layout = $this->context->shop->theme->getLayoutRelativePathForPage($entity);
        }
        //$layout = $this->context->shop->theme->getLayoutRelativePathForPage($entity);

        $content_only = (int) Tools::getValue('content_only');

        if ($overridden_layout = Hook::exec(
            'overrideLayoutTemplate',
            [
                'default_layout' => $layout,
                'entity' => $entity,
                'locale' => $this->context->language->locale,
                'controller' => $this,
                'content_only' => $content_only,
            ]
        )) {
            return $overridden_layout;
        }

        if ($content_only) {
            $layout = 'layouts/layout-content-only.tpl';
        }

        return $layout;
    }
}
