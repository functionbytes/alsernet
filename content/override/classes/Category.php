<?php

class Category extends CategoryCore
{


    public $tituloh1;
    public $category_url_path;
    public $add_sitemap;
    public $prioridad;

    public static $definition = array(
        'table' => 'category',
        'primary' => 'id_category',
        'multilang' => true, // Indicar que la tabla tiene soporte para varios idiomas
        'multilang_shop' => true,
        'fields' => array(
            'nleft' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'nright' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'level_depth' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'id_parent' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_shop_default' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'is_root_category' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            /* Lang fields */
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
            'link_rewrite' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isLinkRewrite',
                'required' => true,
                'size' => 128,
                'ws_modifier' => [
                    'http_method' => WebserviceRequest::HTTP_POST,
                    'modifier' => 'modifierWsLinkRewrite',
                ],
            ],
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 512],
            'meta_keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'tituloh1' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255 ],
            'category_url_path' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 500 ],
            'add_sitemap' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 500 ],
            'prioridad' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 500 ],
        ),
    );



    public function getCategoryGrandFather($id)
    {

    	$padre = Db::getInstance()->getValue("SELECT id_parent FROM "._DB_PREFIX_."category WHERE id_category=".$id);
    	if ($padre<=2){
    		return $id;
    	}
    	else{
    		return self::getCategoryGrandFather($padre);
    	}


    }


    public static function getSportMain($id){

        $category=new Category($id);
        return $category->sportmain;

    }

    public static function getSport($id){

        $category=new Category($id);
        return $category->sport;

    }


    public function getSubCategoriesMenu($idLang)
    {
        $cache_id = 'getSubCategoriesMenu_'.$this->id.'_'.$idLang;
        if (Cache::isStored($cache_id)) {
            return Cache::retrieve($cache_id);
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT c.`id_category`, cl.`name`, cl.`link_rewrite`
		FROM `' . _DB_PREFIX_ . 'category` c
		' . Shop::addSqlAssociation('category', 'c') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` AND `id_lang` = ' . (int) $idLang . ' ' . Shop::addSqlRestrictionOnLang('cl') . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (c.`id_category` = cg.`id_category` AND cl.`id_category` = cg.`id_category`)
		WHERE `id_parent` = ' . (int) $this->id . ' AND `active` = 1 AND cg.id_group = '.(int) Group::getCurrent()->id.'
		GROUP BY c.`id_category`
		ORDER BY `level_depth` ASC, category_shop.`position` ASC');

        $i = 0;
        foreach ($result as $res) {
            $result[$i]['especial'] = ''; //Aqui hay que poner el valor de si la categoría es especial / evento (ej. Black Friday)
            $i = $i + 1;
        }

        if (!Cache::isStored($cache_id)) {
            Cache::store($cache_id, $result);
        } else {
            $result = Cache::retrieve($cache_id);
        }
        return $result;
    }

    public function checkAccess($idCustomer)
    {
        $groups = $this->getGroups();
        //dump($groups);
        if(count($groups) == 1){ //Si solo tiene 1 grupo de cliente asignado
            $grupo = $groups[0];
            if ($grupo == 4) { //Si el grupo asignado es el 4 (oculto)
                return 1;
            }
        }

        $cacheId = 'Category::checkAccess_' . (int) $this->id . '-' . $idCustomer . (!$idCustomer ? '-' . (int) Group::getCurrent()->id : '');
        if (!Cache::isStored($cacheId)) {
            if (!$idCustomer) {
                $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
				SELECT ctg.`id_group`
				FROM ' . _DB_PREFIX_ . 'category_group ctg
				WHERE ctg.`id_category` = ' . (int) $this->id . ' AND ctg.`id_group` = ' . (int) Group::getCurrent()->id);
            } else {
                $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
				SELECT ctg.`id_group`
				FROM ' . _DB_PREFIX_ . 'category_group ctg
				INNER JOIN ' . _DB_PREFIX_ . 'customer_group cg on (cg.`id_group` = ctg.`id_group` AND cg.`id_customer` = ' . (int) $idCustomer . ')
				WHERE ctg.`id_category` = ' . (int) $this->id);
            }
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    public function getParentCategory($idCategory)
    {
        $idLang = Context::getContext()->language->id;
        $sql = 'SELECT c.`id_parent` FROM `' . _DB_PREFIX_ . 'category` c, `' . _DB_PREFIX_ . 'category_lang` cl WHERE c.`id_category` = '.$idCategory.' AND c.`id_category` = cl.`id_category` AND cl.`id_lang` = ' . (int) $idLang;
        $id_parent = Db::getInstance()->getValue($sql);
        //echo $sql.'<br>';
        return $id_parent;
    }

    public static function categoryExistsName($nombre, $id_parent, $id_lang)
    {
        $row = Db::getInstance()->getRow('SELECT c.`id_category` FROM ' . _DB_PREFIX_ . 'category c, ' . _DB_PREFIX_ . 'category_lang cl WHERE c.id_parent = '.$id_parent.' AND cl.id_lang = '.$id_lang.' AND cl.`name` = "' . $nombre.'" AND c.id_category = cl.id_category', false);
        return isset($row['id_category']);
    }

    public static function categoryParentDeporte($nombre, $id_parent, $id_lang)
    {
        $id_category = Db::getInstance()->getValue('SELECT c.`id_category` FROM ' . _DB_PREFIX_ . 'category c, ' . _DB_PREFIX_ . 'category_lang cl WHERE c.id_parent = '.$id_parent.' AND cl.id_lang = '.$id_lang.' AND cl.`name` = "' . $nombre.'" AND c.id_category = cl.id_category', false);
        return $id_category;
    }

    public function getProductsManufacturer(
        $idLang,
        $pageNumber,
        $productPerPage,
        $id_manufacturer,
        $orderBy = null,
        $orderWay = null,
        $getTotal = false,
        $active = true,
        $random = false,
        $randomNumberProducts = 1,
        $checkAccess = true,
        Context $context = null
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        if ($checkAccess && !$this->checkAccess($context->customer->id)) {
            return false;
        }

        $front = in_array($context->controller->controller_type, ['front', 'modulefront']);
        $idSupplier = (int) Tools::getValue('id_supplier');

        /* Return only the number of products */
        if ($getTotal) {
            $sql = 'SELECT COUNT(cp.`id_product`) AS total
                    FROM `' . _DB_PREFIX_ . 'product` p
                    ' . Shop::addSqlAssociation('product', 'p') . '
                    LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.`id_product` = cp.`id_product`
                    WHERE cp.`id_category` = ' . (int) $this->id .
                ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') .
                ($active ? ' AND product_shop.`active` = 1' : '') .
                ($idSupplier ? ' AND p.id_supplier = ' . (int) $idSupplier : '');

            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        if ($pageNumber < 1) {
            $pageNumber = 1;
        }

        /** Tools::strtolower is a fix for all modules which are now using lowercase values for 'orderBy' parameter */
        $orderBy = Validate::isOrderBy($orderBy) ? Tools::strtolower($orderBy) : 'position';
        $orderWay = Validate::isOrderWay($orderWay) ? Tools::strtoupper($orderWay) : 'ASC';

        $orderByPrefix = false;
        if ($orderBy === 'id_product' || $orderBy === 'date_add' || $orderBy === 'date_upd') {
            $orderByPrefix = 'p';
        } elseif ($orderBy === 'name') {
            $orderByPrefix = 'pl';
        } elseif ($orderBy === 'manufacturer' || $orderBy === 'manufacturer_name') {
            $orderByPrefix = 'm';
            $orderBy = 'name';
        } elseif ($orderBy === 'position') {
            $orderByPrefix = 'cp';
        }

        if ($orderBy === 'price') {
            $orderBy = 'orderprice';
        }

        $nbDaysNewProduct = Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        if (!Validate::isUnsignedInt($nbDaysNewProduct)) {
            $nbDaysNewProduct = 20;
        }

        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity' . (Combination::isFeatureActive() ? ', IFNULL(product_attribute_shop.id_product_attribute, 0) AS id_product_attribute,
                    product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity' : '') . ', pl.`description`, pl.`description_short`, pl.`available_now`,
                    pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, image_shop.`id_image` id_image,
                    il.`legend` as legend, m.`name` AS manufacturer_name, cl.`name` AS category_default,
                    DATEDIFF(product_shop.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00",
                    INTERVAL ' . (int) $nbDaysNewProduct . ' DAY)) > 0 AS new, product_shop.price AS orderprice
                FROM `' . _DB_PREFIX_ . 'category_product` cp
                LEFT JOIN `' . _DB_PREFIX_ . 'product` p
                    ON p.`id_product` = cp.`id_product`
                ' . Shop::addSqlAssociation('product', 'p') .
            (Combination::isFeatureActive() ? ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
                ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')' : '') . '
                ' . Product::sqlStock('p', 0) . '
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON (product_shop.`id_category_default` = cl.`id_category`
                    AND cl.`id_lang` = ' . (int) $idLang . Shop::addSqlRestrictionOnLang('cl') . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON (p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = ' . (int) $idLang . Shop::addSqlRestrictionOnLang('pl') . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
                    ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il
                    ON (image_shop.`id_image` = il.`id_image`
                    AND il.`id_lang` = ' . (int) $idLang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m
                    ON m.`id_manufacturer` = p.`id_manufacturer`
                WHERE product_shop.`id_shop` = ' . (int) $context->shop->id . '
                    AND cp.`id_category` = ' . (int) $this->id
            . ' AND m.`id_manufacturer` = '.$id_manufacturer
            . ($active ? ' AND product_shop.`active` = 1' : '')
            . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '')
            . ($idSupplier ? ' AND p.id_supplier = ' . (int) $idSupplier : '');

        if ($random === true) {
            $sql .= ' ORDER BY RAND() LIMIT ' . (int) $randomNumberProducts;
        } elseif ($orderBy !== 'orderprice') {
            $sql .= ' ORDER BY ' . (!empty($orderByPrefix) ? $orderByPrefix . '.' : '') . '`' . bqSQL($orderBy) . '` ' . pSQL($orderWay) . '
            LIMIT ' . (((int) $pageNumber - 1) * (int) $productPerPage) . ',' . (int) $productPerPage;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);

        if (!$result) {
            return [];
        }

        if ($orderBy === 'orderprice') {
            Tools::orderbyPrice($result, $orderWay);
            $result = array_slice($result, (int) (($pageNumber - 1) * $productPerPage), (int) $productPerPage);
        }

        // Modify SQL result
        return Product::getProductsProperties($idLang, $result);
    }


    /******************************************** OVERRIDE REGENERAR CATEGORY TREE - ADDIS *********************************************************************************************************/

    /*
        public static function regenerateEntireNtreeMarcas()
        {
            $id = Context::getContext()->shop->id;
            $idShop = $id ? $id : Configuration::get('PS_SHOP_DEFAULT');
            $sql = new DbQuery();
            $sql->select('c.`id_category`, c.`id_parent`');
            $sql->from('category', 'c');
            $sql->leftJoin('category_shop', 'cs', 'c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $idShop);
            $sql->where('c.id_parent=2821');
            $sql->orderBy('c.`id_parent`, cs.`position` ASC');
            $categories = Db::getInstance()->executeS($sql);

            $categoriesArray = [];
            foreach ($categories as $category) {
                $categoriesArray[$category['id_parent']]['subcategories'][] = $category['id_category'];
            }
            $n = 1;

            if (isset($categoriesArray[2821]) && $categoriesArray[2821]['subcategories']) {
                dump($categoriesArray[2821]['subcategories']);
                $queries = Category::computeNTreeInfosMarcas($categoriesArray[2821]['subcategories'], 2821, $n);
                dump($queries);

                $chunks = array_chunk($queries, 5000);
                foreach ($chunks as $chunk) {
                    $sqlChunk = array_map(function ($value) { return '(' . rtrim(implode(',', $value)) . ')'; }, $chunk);
                    Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'category` (id_category, nleft, nright)
                    VALUES ' . rtrim(implode(',', $sqlChunk), ',') . '
                    ON DUPLICATE KEY UPDATE nleft=VALUES(nleft), nright=VALUES(nright)');
                }
            }
        }

        protected static function computeNTreeInfosMarcas(&$categories, $idCategory, &$n)
        {
            $queries = [];
            $left = $n++;

            if (isset($categories)) {
                foreach ($categories as $idSubcategory) {
                    dump($idSubcategory);
                    die();
                    $queries = array_merge($queries, Category::computeNTreeInfosMarcas($categories, (int) $idSubcategory, $n));
                }
            }
            $right = (int) $n++;

            $queries[] = [$idCategory, $left, $right];

            return $queries;
        }


    */
    /**
     * Re-calculate the values of all branches of the nested tree.
     */
    public static function regenerateEntireNtree()
    {
        $id = Context::getContext()->shop->id;
        $idShop = $id ? $id : Configuration::get('PS_SHOP_DEFAULT');
        $sql = new DbQuery();
        $sql->select('c.`id_category`, c.`id_parent`');
        $sql->from('category', 'c');
        $sql->leftJoin('category_shop', 'cs', 'c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $idShop);
        $sql->orderBy('c.`id_category`,c.`id_parent`, cs.`position` ASC');
        $categories = Db::getInstance()->executeS($sql);

        //echo $sql;

        $categoriesArray = [];
        foreach ($categories as $category) {
            $categoriesArray[$category['id_parent']]['subcategories'][] = $category['id_category'];
        }
        $n = 1;

        //dump($categoriesArray);

        if (isset($categoriesArray[0]) && $categoriesArray[0]['subcategories']) {
            $queries = Category::computeNTreeInfos($categoriesArray, $categoriesArray[0]['subcategories'][0], $n); //La categoría raiz no cuelga de $categoriesArray[0]['subcategories'][0], sino de $categoriesArray[0]['subcategories'][1] --> Cambia el 0 por el 1 y con esto regenera correctamente el arbol

            //dump($queries);

            // update by batch of 5000 categories
            $chunks = array_chunk($queries, 5000);
            foreach ($chunks as $chunk) {
                $sqlChunk = array_map(function ($value) { return '(' . rtrim(implode(',', $value)) . ')'; }, $chunk);

                //dump($sqlChunk);

                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'category` (id_category, nleft, nright)
                VALUES ' . rtrim(implode(',', $sqlChunk), ',') . '
                ON DUPLICATE KEY UPDATE nleft=VALUES(nleft), nright=VALUES(nright)');
            }
        }
    }

    /**
     * @param array $categories
     * @param int $idCategory
     * @param int $n
     *
     * @return array ntree infos
     */
    protected static function computeNTreeInfos(&$categories, $idCategory, &$n)
    {
        $queries = [];
        $left = $n++;
        if (isset($categories[(int) $idCategory]['subcategories'])) {
            foreach ($categories[(int) $idCategory]['subcategories'] as $idSubcategory) {
                $queries = array_merge($queries, Category::computeNTreeInfos($categories, (int) $idSubcategory, $n));
            }
        }
        $right = (int) $n++;

        $queries[] = [$idCategory, $left, $right];

        return $queries;
    }




}

