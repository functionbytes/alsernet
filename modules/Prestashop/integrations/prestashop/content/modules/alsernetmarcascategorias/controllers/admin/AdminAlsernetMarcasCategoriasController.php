<?php

use PrestaShop\PrestaShop\Core\Framework\Controller\AdminController;

class AdminAlsernetMarcasCategoriasController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->table = 'alsernet_brand_category';
        $this->className = 'AlsernetBrandCategory';
        $this->context = Context::getContext();
        $this->multishop_context_group = true;
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_title = 'Marcas & Categorías';
    }

    public function initContent()
    {
        parent::initContent();
        $idLang = (int)$this->context->language->id;

        // ===== CATEGORÍAS EN CABECERA (IDs 3..11) con checkbox por celda =====
        $allowed = range(3, 11);
        $catInfos = Category::getCategoryInformations($allowed, $idLang);
        $categoriesHeader = [];
        foreach ($allowed as $cid) {
            $categoriesHeader[] = [
                'id_category' => $cid,
                'name' => isset($catInfos[$cid]['name']) ? $catInfos[$cid]['name'] : ('ID ' . $cid),
            ];
        }

        // ===== CATEGORÍAS PARA "MARCAS COMO CATEGORÍAS" (id_parent = 2821) =====
        $childCategories = Db::getInstance()->executeS(
            'SELECT c.id_category, cl.name
             FROM `' . _DB_PREFIX_ . 'category` c
             INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                 ON (c.id_category = cl.id_category AND cl.id_lang = ' . (int)$idLang . ')
             WHERE c.id_parent = 2821
             ORDER BY cl.name ASC'
        );

        // ===== FABRICANTES =====
        $manufacturers = self::getManufacturers();

        // ===== CONTADORES DE PRODUCTOS POR MARCA (activos/inactivos) =====
        $counts = Db::getInstance()->executeS(
            'SELECT id_manufacturer,
                    SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) AS inactive_count
             FROM `' . _DB_PREFIX_ . 'product`
             GROUP BY id_manufacturer'
        );
        $prodCount = [];
        foreach ($counts as $row) {
            $prodCount[(int)$row['id_manufacturer']] = [
                'active' => (int)$row['active_count'],
                'inactive' => (int)$row['inactive_count'],
            ];
        }

        // ===== ASOCIACIONES MARCA->CATEGORÍA (PARTE 1) =====
        $assocRows = Db::getInstance()->executeS(
            'SELECT id_manufacturer, id_category FROM `' . _DB_PREFIX_ . 'alsernet_brand_category`'
        );
        $assocMap = [];
        foreach ($assocRows as $r) {
            $mid = (int)$r['id_manufacturer'];
            $cid = (int)$r['id_category'];
            if (!isset($assocMap[$mid])) { $assocMap[$mid] = []; }
            $assocMap[$mid][$cid] = true;
        }

        // ===== PARES MARCA-CATEGORÍA (TABLA alsernet_brand_as_category) =====
        $asCatRows = Db::getInstance()->executeS(
            'SELECT id_manufacturer, id_category FROM `' . _DB_PREFIX_ . 'alsernet_brand_as_category`'
        );
        $brandCatPairs = [];
        $asCatMap = [];
        foreach ($asCatRows as $row) {
            $brandId = (int)$row['id_manufacturer'];
            $catId   = (int)$row['id_category'];
            $brandCatPairs[] = [
                'brand'    => $brandId,
                'category' => $catId,
            ];
            if (!isset($asCatMap[$brandId])) { $asCatMap[$brandId] = []; }
            $asCatMap[$brandId][$catId] = true;
        }

        // ===== CONSTRUIR FILAS DE MARCAS =====
        $brandRows = [];
        foreach ($manufacturers as $m) {
            $mid = (int)$m['id_manufacturer'];
            $brandRows[] = [
                'id_manufacturer' => $mid,
                'name' => $m['name'],
                'active_count' => isset($prodCount[$mid]) ? $prodCount[$mid]['active'] : 0,
                'inactive_count' => isset($prodCount[$mid]) ? $prodCount[$mid]['inactive'] : 0,
                'is_active' => (isset($m['active']) && $m['active'] > 0) ? 1 : 0,
                'img' => 'https://www.a-alvarez.com/img/m/' . $mid . '-small_default.jpg',
                'assoc' => isset($assocMap[$mid]) ? $assocMap[$mid] : [],
                'as_cat' => isset($asCatMap[$mid]) ? $asCatMap[$mid] : [],
            ];
        }

        $catId = (int)Tools::getValue('category_id');
        if ($catId) {
            // ===== MODO EDICIÓN =====
            $currentCat = new Category($catId, $idLang);
            $currentAssoc = Db::getInstance()->executeS(
                'SELECT id_manufacturer FROM `' . _DB_PREFIX_ . 'alsernet_brand_category` WHERE id_category = ' . $catId
            );
            $currentBrands = array_column($currentAssoc, 'id_manufacturer');

            $this->context->smarty->assign([
                'mode'                => 'edit',
                'currentCategory'     => $currentCat,
                'manufacturers'       => $manufacturers,
                'currentBrands'       => $currentBrands,
                'brandToCategoryJson' => json_encode(array_column($asCatRows, 'id_category', 'id_manufacturer')),
                'token'               => $this->token,
            ]);
        } else {
            // ===== MODO LISTADO =====
            $this->context->smarty->assign([
                'mode'               => 'list',
                'categoriesHeader'   => $categoriesHeader,
                'brandRows'          => $brandRows,
                'brandCatPairs'      => $brandCatPairs,
                'manufacturers'      => $manufacturers,
                'childCategories'    => $childCategories,
                'token'              => $this->token,
            ]);
        }

        $this->setTemplate('configure.tpl');
    }

    public function postProcess()
    {
        // Guardado de asociaciones de la parte 1
        if (Tools::isSubmit('submitCategoryBrands')) {
            $catId      = (int)Tools::getValue('category_id');
            $brands     = Tools::getValue('brands', []);
            Db::getInstance()->delete('alsernet_brand_category', 'id_category = ' . $catId);
            foreach ($brands as $brandId) {
                Db::getInstance()->insert('alsernet_brand_category', [
                    'id_manufacturer' => (int)$brandId,
                    'id_category'     => $catId,
                ]);
            }
            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
        }

        // Guardado del bloque "Marcas como Categorías" (tabla manual con JSON)
        if (Tools::isSubmit('saveBrandCatTable')) {
            $json = Tools::getValue('brand_cat_json');
            $data = @json_decode($json, true);
            if (!is_array($data)) {
                $this->errors[] = 'Formato de datos inválido.';
                return parent::postProcess();
            }
            // Limpiar e insertar
            Db::getInstance()->execute('TRUNCATE `' . _DB_PREFIX_ . 'alsernet_brand_as_category`');
            foreach ($data as $r) {
                if (!empty($r['brand']) && !empty($r['category'])) {
                    Db::getInstance()->insert('alsernet_brand_as_category', [
                        'id_manufacturer' => (int)$r['brand'],
                        'id_category'     => (int)$r['category'],
                    ]);
                }
            }
        }

        parent::postProcess();
    }

    // ===== AJAX: Toggle marca-categoría en alsernet_brand_category =====
    public function ajaxProcessToggleBrandAsCategory()
    {
        $brandId = (int)Tools::getValue('brand_id');
        $catId   = (int)Tools::getValue('category_id');
        $checked = (int)Tools::getValue('checked');
        if ($brandId <= 0 || $catId <= 0) {
            die(Tools::jsonEncode(['success' => false, 'message' => 'Parámetros inválidos']));
        }
        if ($checked) {
            // Insert if not exists in alsernet_brand_category
            $exists = (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'alsernet_brand_category`
                 WHERE id_manufacturer = ' . $brandId . ' AND id_category = ' . $catId
            );
            if (!$exists) {
                Db::getInstance()->insert('alsernet_brand_category', [
                    'id_manufacturer' => $brandId,
                    'id_category'     => $catId,
                ]);
            }
        } else {
            Db::getInstance()->delete('alsernet_brand_category',
                'id_manufacturer = ' . $brandId . ' AND id_category = ' . $catId
            );
        }
        die(Tools::jsonEncode(['success' => true]));
    }

    public function getManufacturers($getNbProducts = false, $idLang = 0, $active = true, $p = false, $n = false, $allGroup = false, $group_by = false, $withProduct = false)
    {
        if (!$idLang) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        if (!Group::isFeatureActive()) {
            $allGroup = true;
        }

        $manufacturers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT m.*, ml.`description`, ml.`short_description`
		FROM `' . _DB_PREFIX_ . 'manufacturer` m'
        . Shop::addSqlAssociation('manufacturer', 'm') .
        'INNER JOIN `' . _DB_PREFIX_ . 'manufacturer_lang` ml ON (m.`id_manufacturer` = ml.`id_manufacturer` AND ml.`id_lang` = ' . (int) $idLang . ')' .
        'WHERE 1 ' .
        ($withProduct ? 'AND m.`id_manufacturer` IN (SELECT `id_manufacturer` FROM `' . _DB_PREFIX_ . 'product`) ' : '') .
        ($group_by ? ' GROUP BY m.`id_manufacturer`' : '') .
        'ORDER BY m.`name` ASC
		' . ($p ? ' LIMIT ' . (((int) $p - 1) * (int) $n) . ',' . (int) $n : ''));
        if ($manufacturers === false) {
            return false;
        }

        if ($getNbProducts) {
            $sqlGroups = '';
            if (!$allGroup) {
                $groups = FrontController::getCurrentCustomerGroups();
                $sqlGroups = (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id);
            }

            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
					SELECT  p.`id_manufacturer`, COUNT(DISTINCT p.`id_product`) as nb_products
					FROM `' . _DB_PREFIX_ . 'product` p USE INDEX (product_manufacturer)
					' . Shop::addSqlAssociation('product', 'p') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` as m ON (m.`id_manufacturer`= p.`id_manufacturer`)
					WHERE p.`id_manufacturer` != 0 AND product_shop.`visibility` NOT IN ("none")
					' . ($active ? ' AND product_shop.`active` = 1 ' : '') . '
					' . (Group::isFeatureActive() && $allGroup ? '' : ' AND EXISTS (
						SELECT 1
						FROM `' . _DB_PREFIX_ . 'category_group` cg
						LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (cp.`id_category` = cg.`id_category`)
						WHERE p.`id_product` = cp.`id_product` AND cg.`id_group` ' . $sqlGroups . '
					)') . '
					GROUP BY p.`id_manufacturer`'
                );

            $counts = [];
            foreach ($results as $result) {
                $counts[(int) $result['id_manufacturer']] = (int) $result['nb_products'];
            }

            foreach ($manufacturers as $key => $manufacturer) {
                if (array_key_exists((int) $manufacturer['id_manufacturer'], $counts)) {
                    $manufacturers[$key]['nb_products'] = $counts[(int) $manufacturer['id_manufacturer']];
                } else {
                    $manufacturers[$key]['nb_products'] = 0;
                }
            }
        }

        $totalManufacturers = count($manufacturers);
        $rewriteSettings = (int) Configuration::get('PS_REWRITING_SETTINGS');
        for ($i = 0; $i < $totalManufacturers; ++$i) {
            $manufacturers[$i]['link_rewrite'] = ($rewriteSettings ? Tools::link_rewrite($manufacturers[$i]['name']) : 0);
        }

        return $manufacturers;
    }
}