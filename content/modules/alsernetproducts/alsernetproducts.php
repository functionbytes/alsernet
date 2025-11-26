<?php


use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\BestSales\BestSalesProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;


if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__) . '/classes/Reviews/Comment.php');
include_once(dirname(__FILE__) . '/classes/Questions/Question.php');

class alsernetproducts extends Module implements WidgetInterface
{

    private $currentProductId;
    protected $config_form = false;
    private $link;
    public $link_cart;
    public $module_path;
    protected $_postErrors = array();
    public $is_gen_rtl;
    public $html = '';
    protected $pagination = array();
    protected $total_pages = 0;
    protected $total_orders = 0;
    protected $total_selected_orders = 0;
    protected $selected_pagination = 2;
    protected $limit = 100;
    protected $offset = 1;
    protected $orders = array();
    protected $sql = '';
    protected $sql_count = '';


    public function __construct()
    {
        $this->name = 'alsernetproducts';
        $this->author = 'Alsernet';
        $this->version = '2.0.4';
        $this->need_instance = 0;

        parent::__construct();

        $this->secure_key = Tools::encrypt($this->name);
        $this->displayName = "Alsernet - productos";
        $this->description = $this->getTranslator()->trans('Make your customers feel at home on your store, invite them to sign in!', [], 'Modules.Customersignin.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

    }


    public function initReviews()
    {
        $queries = array(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernetproducts_orders` (
                    `id_order` INT(11) NOT NULL,
                    `id_customer` INT(11) NOT NULL,
                    `hash` VARCHAR(60) NOT NULL,
                    `voted` INT(11) NOT NULL,
                    `sent` INT(11) NOT NULL,
                    `date_email` DATETIME NOT NULL,
                    `date_email2` DATETIME NOT NULL,
                    UNIQUE KEY `id_order` (`id_order`),
                    KEY `id_customer` (`id_customer`,`hash`,`voted`)
                ) ENGINE=' . (defined('ENGINE_TYPE') ? ENGINE_TYPE : 'Innodb') . ' CHARSET=utf8',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernetproducts_status` (
                    `id_order_status` INT(11) NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`id_order_status`)
                ) ENGINE=' . (defined('ENGINE_TYPE') ? ENGINE_TYPE : 'Innodb'),
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernetproducts_customergroups` (
                    `id_customer_group` INT(11) NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`id_customer_group`)
                ) ENGINE=' . (defined('ENGINE_TYPE') ? ENGINE_TYPE : 'Innodb'),
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernetproducts_multistore` (
                    `id_shop` INT(11) NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`id_shop`)
                ) ENGINE=' . (defined('ENGINE_TYPE') ? ENGINE_TYPE : 'Innodb'),
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernetproducts_productcomments` (
                    `id_productcomment` int(11) NOT NULL AUTO_INCREMENT,
                    `id_product` int(11) NOT NULL,
                    `id_product_attribute` int(11) NOT NULL,
                    `id_customer` int(11) NOT NULL,
                    `id_lang` int(11) NOT NULL,
                    `stars` int(11) NOT NULL,
                    `nick` varchar(255) NOT NULL,
                    `title` varchar(255) DEFAULT NULL,
                    `comment` text,
                    `answer` text,
                    `active` tinyint(1) NOT NULL,
                    `position` int(11) NOT NULL,
                    `date` datetime NOT NULL,
                    PRIMARY KEY (`id_productcomment`),
                    KEY `date` (`date`,`id_customer`,`id_product`,`stars`,`id_lang`,`active`,`position`),
                    KEY `alsernetproducts_id_product_index` (`id_product`)
                ) ENGINE=' . (defined('ENGINE_TYPE') ? ENGINE_TYPE : 'Innodb'),
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernetproducts_storecomments` (
                    `id_storecomment` int(11) NOT NULL AUTO_INCREMENT,
                    `id_order` int(11) NOT NULL,
                    `id_customer` int(11) NOT NULL,
                    `id_lang` int(11) NOT NULL,
                    `stars` int(11) NOT NULL,
                    `nick` varchar(255) NOT NULL,
                    `title` varchar(255) DEFAULT NULL,
                    `comment` text,
                    `answer` text,
                    `active` tinyint(1) NOT NULL,
                    `position` int(11) NOT NULL,
                    `date` datetime NOT NULL,
                    PRIMARY KEY (`id_storecomment`),
                    KEY `date` (`date`,`id_customer`,`id_order`,`stars`,`id_lang`,`active`,`position`)
                ) ENGINE=' . (defined('ENGINE_TYPE') ? ENGINE_TYPE : 'Innodb'),

        );

        if (!empty($queries)) {
            $commit = true;

            Db::getInstance()->execute('START TRANSACTION');

            foreach ($queries as $query) {
                if (!Db::getInstance()->execute($query)) {
                    Db::getInstance()->execute('ROLLBACK');

                    $commit = false;

                    break;
                }
            }

            if ($commit) {
                Db::getInstance()->execute('COMMIT');
            }


        }

    }

    public function addFields()
    {
        $columns = [
            'translated' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
        ];

        $tableName = _DB_PREFIX_ . 'product_lang';

        foreach ($columns as $columnName => $columnType) {
            // Verifica si la columna ya existe
            $sql = "SELECT COUNT(*) as count
                FROM information_schema.COLUMNS
                WHERE TABLE_NAME = '{$tableName}'
                AND COLUMN_NAME = '{$columnName}'
                AND TABLE_SCHEMA = DATABASE();";

            $result = Db::getInstance()->getRow($sql);

            if ($result['count'] == 0) {
                // Si la columna no existe, la añade
                $sql = "ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$columnType};";
                if (!Db::getInstance()->execute($sql)) {
                    return false; // En caso de error en la consulta
                }
            }
        }


        return true; // Todos los campos se han añadido o ya existen
    }

    public function install()
    {
        return parent::install()
            && $this->addFields()
            && $this->registerHook('header')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayViewProduct');

    }


    public function getWidgetVariables($hookName, array $configuration)
    {
        return [

        ];
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {

        if ($hookName == 'displayViewProduct') {

            $product = isset($configuration['product']) ? $configuration['product'] : "";
            $view = $product['view'];


            $this->smarty->assign(array(
                'view' => $view,
                'product' => $product,
                'category' => $configuration['category']
            ));


            switch ($view) {
                case 'card':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/card.tpl');
                case 'demoday':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/demoday.tpl');
                case 'fishing':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/fishing.tpl');
                case 'fitting':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/fitting.tpl');
                case 'custom':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/custom.tpl');
                case 'hunt':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/hunt.tpl');
                case 'lotery':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/lotery.tpl');
                // case 'lot':
                //     return $this->fetch('module:alsernetproducts/views/templates/view/product/lot.tpl');
                case 'pack':
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/pack.tpl');
                default:
                    return $this->fetch('module:alsernetproducts/views/templates/view/product/default.tpl');
            }


        } elseif ($hookName == 'displayBeforeBodyClosingTag') {

            return $this->fetch('module:alsernetproducts/views/templates/hook/view/modal.tpl');

        } elseif ($hookName == 'displayProductAdditionalInfo') {

            if (isset($configuration['product']['id_product'])) {
                $currentProductId = $configuration['product']['id_product'];
                $this->addViewedProduct($currentProductId);
                return;
            }


        } elseif (isset($configuration['type'])) {

            if ($configuration['type'] == 'news') {

                $title = $this->l('Newest', 'alsernetproducts');

                $link = Context::getContext()->link->getBestSalesDeporteLink($configuration['category']);
                $products = $this->getProducts($configuration['category'], $configuration['type']);

                $this->smarty->assign(array(
                    'products' => $products,
                    'link' => $link,
                    'title' => $title,
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/product/products.tpl');


            } elseif ($configuration['type'] == 'featured') {


                $title = $this->l('Our Products', 'alsernetproducts');
                $link = Context::getContext()->link->getBestSalesDeporteLink($configuration['category']);
                $products = $this->getProducts($configuration['category'], $configuration['type']);

                $this->smarty->assign(array(
                    'products' => $products,
                    'link' => $link,
                    'title' => $title,
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/product/products.tpl');

            } elseif ($configuration['type'] == 'sales') {


                $title = $this->l('Best sales', 'alsernetproducts');
                $link = Context::getContext()->link->getBestSalesDeporteLink($configuration['category']);
                $products = $this->getProducts($configuration['category'], $configuration['type']);

                $this->smarty->assign(array(
                    'products' => $products,
                    'link' => $link,
                    'title' => $title,
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/product/products.tpl');

            } elseif ($configuration['type'] == 'newproducts') {

                $title = $this->l('New products', 'alsernetproducts');
                $link = Context::getContext()->link->getBestSalesDeporteLink($configuration['category']);
                $products = $this->getProducts($configuration['category'], $configuration['type']);

                $this->smarty->assign(array(
                    'products' => $products,
                    'link' => $link,
                    'title' => $title,
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/product/products.tpl');

            } elseif ($configuration['type'] == 'sellers') {


                $title = $this->l('Best seller', 'alsernetproducts');
                $link = Context::getContext()->link->getPageLink('best-sales');
                $products = $this->getProducts($configuration['category'], $configuration['type']);

                $this->smarty->assign(array(
                    'products' => $products,
                    'link' => $link,
                    'title' => $title,
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/product/products.tpl');

            } elseif ($configuration['type'] == 'viewproduct') {

                $title = $this->l('View products', 'alsernetproducts');
                $products = $this->getProducts($configuration['category'], $configuration['type']);

                $this->smarty->assign(array(
                    'products' => $products,
                    'link' => '',
                    'title' => $title,
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/product/products.tpl');

            } elseif ($configuration['type'] == 'analytics') {

                $this->smarty->assign(array(
                    'category' => $configuration['category'],
                    'type' => $configuration['type'],
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/product/analytics.tpl');

            } elseif ($configuration['type'] == 'reviews') {

                if (isset($configuration['option'])) {

                    if ($configuration['option'] == 'reviews') {

                        $this->context->smarty->assign(Comment::getProductReviewsDetailsShow($configuration['product']['id']));
                        return $this->fetch('module:alsernetproducts/views/templates/hook/reviews/reviews.tpl');

                    } elseif ($configuration['option'] == 'viewreviews') {


                        $this->smarty->assign(array(
                            'logged' => $this->context->customer->isLogged(),
                            'comment' => Comment::getProductReviewsDetails(),
                        ));

                        return $this->fetch('module:alsernetproducts/views/templates/hook/reviews/view-reviews.tpl');

                    } elseif ($configuration['option'] == 'comments') {

                        $this->context->smarty->assign(Comment::getProductReviewsDetails());
                        return $this->fetch('module:alsernetproducts/views/templates/hook/reviews/comments.tpl');
                    }
                }

            } elseif ($configuration['type'] == 'questions') {


                if ($configuration['option'] == 'questions') {

                    $this->context->smarty->assign(Question::getProductQuestionsDetails());
                    return $this->fetch('module:alsernetproducts/views/templates/hook/questions/questions.tpl');

                } elseif ($configuration['option'] == 'modal') {

                    return $this->fetch('module:alsernetproducts/views/templates/hook/questions/modal.tpl');
                }


            } elseif ($configuration['type'] == 'wishlist') {

                return $this->fetch('module:alsernetproducts/views/templates/hook/wishlist/btn.tpl');

            } elseif ($configuration['type'] == 'compare') {

                return $this->fetch('module:alsernetproducts/views/templates/hook/compare/btn.tpl');

            } elseif ($configuration['type'] == 'social') {


                if (!method_exists($this->context->controller, 'getProduct')) {
                    return;
                }

                $product = $this->context->controller->getProduct();

                if (!Validate::isLoadedObject($product)) {
                    return;
                }

                $product->name = (is_array($product->name)) ? $product->name[1] : $product->name;

                $social_share_links = [];
                $sharing_url = urlencode(addcslashes($this->context->link->getProductLink($product), "'"));
                $sharing_name = urlencode(addcslashes($product->name, "'"));

                $image_cover_id = $product->getCover($product->id);
                if (is_array($image_cover_id) && isset($image_cover_id['id_image'])) {
                    $image_cover_id = (int)$image_cover_id['id_image'];
                } else {
                    $image_cover_id = 0;
                }

                $sharing_img = urlencode(addcslashes($this->context->link->getImageLink($product->link_rewrite, $image_cover_id), "'"));


                $social_share_links['facebook'] = [
                    'label' => $this->trans('Share', [], 'Modules.Sharebuttons.Shop'),
                    'class' => 'facebook-f',
                    'url' => 'https://www.facebook.com/sharer.php?u=' . $sharing_url,
                ];


                $social_share_links['x'] = [
                    'label' => $this->trans('Post on X', [], 'Modules.Sharebuttons.Shop'),
                    'class' => 'x-twitter',
                    'url' => 'https://twitter.com/intent/tweet?text=' . $sharing_name . ' ' . $sharing_url, // La URL de Twitter sigue funcionando para X
                ];


                $social_share_links['whatsapp'] = [
                    'label' => $this->trans('Share on WhatsApp', [], 'Modules.Sharebuttons.Shop'),
                    'class' => 'whatsapp',
                    'url' => 'https://api.whatsapp.com/send?text=' . $sharing_name . ' ' . $sharing_url,
                ];

                $this->smarty->assign(array(
                    'social_share_links' => $social_share_links,
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/social/share.tpl');

            } elseif ($configuration['type'] == 'buttons') {

                $this->smarty->assign(array(
                    'product' => $configuration['product'],
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/view/buttons.tpl');


            } elseif ($configuration['type'] == 'hour') {

                $this->smarty->assign(array(
                    'product' => $configuration['product'],
                ));

                return $this->fetch('module:alsernetproducts/views/templates/hook/view/hour.tpl');

            } elseif ($configuration['type'] == 'manufacture') {

                return $this->fetch('module:alsernetproducts/views/templates/hook/manufacture/manufacture.tpl');
            } elseif ($configuration['type'] == 'complementary') {


                if (isset($configuration['product'])) {

                    $product = $configuration['product'];

                    if ($product->id_product_attribute == 0) {
                        $idretail = "1" . $product->id;
                    } else {
                        $idretail = "2" . $product->id . $product->id_product_attribute;
                    }

                    $cart = Context::getContext()->cart;
                    $products_data = [];
                    $products_data[] = $this->getProductsComplementary($product->id, $product->id_product_attribute);

                    $products = [];
                    if ($products_data) {
                        foreach ($products_data as $product_group) {
                            foreach ($product_group as $product) {
                                // comprobar que el producto no esté añadido a la cesta
                                $product_exists_in_cart = false;
                                foreach ($cart->getProducts() as $product_cart) {
                                    if ((int)$product_cart['id_product'] == (int)$product['id_product']) {
                                        $product_exists_in_cart = true;
                                    }
                                }

                                if (!$product_exists_in_cart) {
                                    $products[$product['id_product']] = $product;
                                }
                            }
                        }
                    }

                    $this->smarty->assign(array(
                        'products' => $products,
                        'idproduct' => $idretail,
                    ));

                    return $this->fetch('module:alsernetproducts/views/templates/hook/complementary/list.tpl');

                } elseif (isset($configuration['cart']) && $configuration['cart']) {


                    $products = [];

                    $cart = $configuration['cart'];
                    if (isset($cart['products']) && $cart['products'] && count($cart['products'])) {
                        $products_data = [];
                        foreach ($cart['products'] as $product) {
                            $products_data[] = $this->getProductsComplementary((int)$product['id_product'], (int)$product['id_product_attribute']);
                        }

                        if ($products_data) {
                            foreach ($products_data as $product_group) {
                                foreach ($product_group as $product) {
                                    // comprobar que el producto no esté añadido a la cesta
                                    $product_exists_in_cart = false;
                                    foreach ($cart['products'] as $product_cart) {
                                        if ((int)$product_cart['id_product'] == (int)$product['id_product']) {
                                            $product_exists_in_cart = true;
                                        }
                                    }

                                    if (!$product_exists_in_cart) {
                                        $products[$product['id_product']] = $product;
                                    }
                                }
                            }
                        }
                    }

                    $this->smarty->assign(array(
                        'products' => $products,
                    ));

                    return $this->fetch('module:alsernetproducts/views/templates/hook/complementary/cart.tpl');
                }

            }

        }

    }


    public function getProductsComplementary($id_prod, $id_productattribute)
    {
        $etiquetas = '';
        $products_for_template = [];
        if ('' . $id_productattribute == '0') {
            $etiquetas = Db::getInstance()->getValue("SELECT etiqueta FROM aalv_combinacionunica_import WHERE id_product=" . $id_prod);
        } else {
            $etiquetas = Db::getInstance()->getValue("SELECT etiqueta FROM aalv_combinaciones_import WHERE id_product_attribute=" . $id_productattribute);
        }

        $rows = Db::getInstance()->ExecuteS("SELECT etiqueta, id_product FROM aalv_complementarios");

        $lisproducts = [];

        foreach (explode(',', $etiquetas) as $etiqueta) {
            foreach ($rows as $complementario) {
                if (trim(strtolower($etiqueta)) == trim(strtolower($complementario['etiqueta']))) {
                    $lisproducts[] = $complementario["id_product"];
                }
            }
        }

        /*foreach($rows as $row){
            preg_match("/(.*?)".$row["etiqueta"]."(.*)/", $etiquetas, $coincidencias);
            if (count($coincidencias)>0){
                $lisproducts[]=$row["id_product"];
            } else {
                preg_match("/".$row["etiqueta"]."(.*)/", $etiquetas, $coincidencias2);
                if (count($coincidencias2)>0){
                    $lisproducts[]=$row["id_product"];
                } else {
                    preg_match("/".$row["etiqueta"]."/", $etiquetas, $coincidencias3);
                    if (count($coincidencias3)>0){
                        $lisproducts[]=$row["id_product"];
                    }
                }
            }
        }*/

        if (count($lisproducts) == 0) {
            return $products_for_template;
        }

        //$sql = 'SELECT * FROM `'._DB_PREFIX_.'product` WHERE id_product in ('.implode(',', $lisproducts).')';
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'product` WHERE id_product in (' . implode(',', $lisproducts) . ') AND id_product <> ' . $id_prod;
        $products = Db::getInstance()->executeS($sql);

        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );


        if ($products) {
            foreach ($products as $rawProduct) {
                /*
                $products_for_template[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
                */

                $ppres = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );

                //{if (($product.add_to_cart_url) && ($product.id_product_attribute==0)) || ($product.id_product_attribute!=0) }
                if (($ppres["add_to_cart_url"] && ($ppres["id_product_attribute"] == 0)) || $ppres["id_product_attribute"] != 0) {
                    /* JLP - 12/07/2022 - si es un producto bundle se debe comportar como producto con combinación porque los productos que componen el bundle puede tener combinaciones */
                    $ppres['is_bundle'] = 0;
                    $sql = 'SELECT `id_ps_product` FROM `' . _DB_PREFIX_ . 'wk_bundle_product` WHERE `id_ps_product`=' . (int)$ppres['id_product'];
                    $isBundle = DB::getInstance()->getValue($sql);
                    if ($isBundle) {
                        $ppres['is_bundle'] = 1;
                    }

                    $products_for_template[] = $ppres;
                }
            }
        }

        return $products_for_template;
    }


    function getProducts($category, $type)
    {

        $context = new ProductSearchContext($this->context);
        $query = new ProductSearchQuery();

        if ($type == "sales") {

            $searchProvider = new BestSalesSportProductSearchProvider($this->getTranslator());
            $searchProvider->list_type = BestSalesSportProductSearchProvider::TYPE_HOME;
            $searchProvider->id_category_sport = (int)$category;


            $result = $searchProvider->runQuery(
                $context,
                $query
            );

            $assembler = new ProductAssembler($this->context);

            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = $presenterFactory->getPresenter();

            $products_for_template = [];

            foreach ($result->getProducts() as $rawProduct) {
                if (!$rawProduct['blocked']) {
                    $products_for_template[] = $presenter->present(
                        $presentationSettings,
                        $assembler->assembleProduct($rawProduct),
                        $this->context->language
                    );
                }
            }

            return $products_for_template;


        } elseif ($type == "news") {

            $searchProvider = new NewProductsSportProductSearchProvider($this->getTranslator());
            $searchProvider->list_type = NewProductsSportProductSearchProvider::TYPE_HOME;
            $searchProvider->id_category_sport = (int)$category;

            $result = $searchProvider->runQuery(
                $context,
                $query
            );

            $assembler = new ProductAssembler($this->context);

            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = $presenterFactory->getPresenter();

            $products_for_template = [];

            foreach ($result->getProducts() as $rawProduct) {
                if (!$rawProduct['blocked']) {
                    $products_for_template[] = $presenter->present(
                        $presentationSettings,
                        $assembler->assembleProduct($rawProduct),
                        $this->context->language
                    );
                }
            }

            return $products_for_template;


        } elseif ($type == "analytics") {

            $searchProviderAnalyticsNews = new NewProductsSportProductSearchProvider($this->getTranslator());
            $searchProviderAnalyticsNews->list_type = NewProductsSportProductSearchProvider::TYPE_HOME;
            $searchProviderAnalyticsNews->id_category_sport = (int)$category;

            $searchProviderAnalyticsBests = new BestSalesSportProductSearchProvider($this->getTranslator());
            $searchProviderAnalyticsBests->list_type = BestSalesSportProductSearchProvider::TYPE_HOME;
            $searchProviderAnalyticsBests->id_category_sport = (int)$category;

            $resultAnalyticsNews = $searchProviderAnalyticsNews->runQuery(
                $context,
                $query
            );

            $resultAnalyticsBests = $searchProviderAnalyticsBests->runQuery(
                $context,
                $query
            );

            $products = [];

            $products = array_merge(
                $this->getProductAnalytics($resultAnalyticsBests->getProducts(), "analytics"),
                $this->getProductAnalytics($resultAnalyticsNews->getProducts(), "analytics")
            );

            return $products;

        } elseif ($type == "sellers") {

            $searchProvider = new BestSalesProductSearchProvider(
                $this->context->getTranslator()
            );

            $context = new ProductSearchContext($this->context);

            $query = new ProductSearchQuery();

            $nProducts = (int)Configuration::get('PS_BLOCK_BESTSELLERS_TO_DISPLAY');

            $query
                ->setResultsPerPage($nProducts)
                ->setPage(1);

            $query->setSortOrder(SortOrder::random());

            $result = $searchProvider->runQuery(
                $context,
                $query
            );

            $assembler = new ProductAssembler($this->context);

            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(
                new ImageRetriever(
                    $this->context->link
                ),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->context->getTranslator()
            );

            $products_for_template = [];

            foreach ($result->getProducts() as $rawProduct) {
                $products_for_template[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
            }

            return $products_for_template;


        } elseif ($type == "featureds") {

            $category = new Category((int)Configuration::get('HOME_FEATURED_CAT'));

            $searchProvider = new CategoryProductSearchProvider(
                $this->context->getTranslator(),
                $category
            );

            $context = new ProductSearchContext($this->context);

            $query = new ProductSearchQuery();

            $nProducts = Configuration::get('HOME_FEATURED_NBR');
            if ($nProducts < 0) {
                $nProducts = 12;
            }

            $query
                ->setResultsPerPage($nProducts)
                ->setPage(1);

            if (Configuration::get('HOME_FEATURED_RANDOMIZE')) {
                $query->setSortOrder(SortOrder::random());
            } else {
                $query->setSortOrder(new SortOrder('product', 'position', 'asc'));
            }

            $result = $searchProvider->runQuery(
                $context,
                $query
            );

            $assembler = new ProductAssembler($this->context);

            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = $presenterFactory->getPresenter();

            $products_for_template = [];

            foreach ($result->getProducts() as $rawProduct) {
                $products_for_template[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
            }

            return $products_for_template;

        } elseif ($type == "newproducts") {

            if (Configuration::get('PS_CATALOG_MODE')) {
                return false;
            }

            $newProducts = false;

            if (Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) {
                $newProducts = Product::getNewProducts(
                    (int)$this->context->language->id,
                    0,
                    (int)Configuration::get('NEW_PRODUCTS_NBR')
                );
            }

            $assembler = new ProductAssembler($this->context);

            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(
                new ImageRetriever(
                    $this->context->link
                ),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->context->getTranslator()
            );

            $products_for_template = array();

            if (is_array($newProducts)) {
                foreach ($newProducts as $rawProduct) {
                    $products_for_template[] = $presenter->present(
                        $presentationSettings,
                        $assembler->assembleProduct($rawProduct),
                        $this->context->language
                    );
                }
            }

            return $products_for_template;
        } elseif ($type == "viewproduct") {

            $productIds = $this->getViewedProductIds();
            // dump($productIds);

            if (!empty($productIds)) {
                $assembler = new ProductAssembler($this->context);

                $presenterFactory = new ProductPresenterFactory($this->context);
                $presentationSettings = $presenterFactory->getPresentationSettings();
                if (version_compare(_PS_VERSION_, '1.7.5', '>=')) {
                    $presenter = new \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter(
                        new ImageRetriever(
                            $this->context->link
                        ),
                        $this->context->link,
                        new PriceFormatter(),
                        new ProductColorsRetriever(),
                        $this->context->getTranslator()
                    );
                } else {
                    $presenter = new \PrestaShop\PrestaShop\Core\Product\ProductListingPresenter(
                        new ImageRetriever(
                            $this->context->link
                        ),
                        $this->context->link,
                        new PriceFormatter(),
                        new ProductColorsRetriever(),
                        $this->context->getTranslator()
                    );
                }

                $products_for_template = [];

                if (is_array($productIds)) {
                    foreach ($productIds as $productId) {
                        if ($this->currentProductId !== $productId) {
                            $products_for_template[] = $presenter->present(
                                $presentationSettings,
                                $assembler->assembleProduct(['id_product' => $productId]),
                                $this->context->language
                            );
                        }
                    }
                }

                return $products_for_template;
            }

            return false;
        }
    }


    protected function addViewedProduct($idProduct)
    {
        $arr = [];

        if (isset($this->context->cookie->viewed)) {
            $arr = explode(',', $this->context->cookie->viewed);
        }

        if (!in_array($idProduct, $arr)) {
            $arr[] = $idProduct;
            $arr = array_reverse(array_slice(array_reverse($arr), 0, ((int)Configuration::get('PRODUCTS_VIEWED_NBR') + 1)));

            $this->context->cookie->viewed = trim(implode(',', $arr), ',');
        }
    }

    protected function getViewedProductIds()
    {
        // dump($this->context->cookie->viewed);
        $viewedProductsIds = array_reverse(explode(',', $this->context->cookie->viewed));
        if (null !== $this->currentProductId && in_array($this->currentProductId, $viewedProductsIds)) {
            $viewedProductsIds = array_diff($viewedProductsIds, [$this->currentProductId]);
        }

        $existingProducts = $this->getExistingProductsIds();
        $viewedProductsIds = array_filter($viewedProductsIds, function ($entry) use ($existingProducts) {
            return in_array($entry, $existingProducts);
        });

        return array_slice($viewedProductsIds, 0, (int)(Configuration::get('PRODUCTS_VIEWED_NBR')));
    }


    private function getExistingProductsIds()
    {
        $existingProductsQuery = Db::getInstance((bool)_PS_USE_SQL_SLAVE_)->executeS('
            SELECT p.id_product
            FROM ' . _DB_PREFIX_ . 'product p
            WHERE p.active = 1'
        );

        return array_map(function ($entry) {
            return $entry['id_product'];
        }, $existingProductsQuery);
    }


    public function hookHeader($params)
    {


        $this->context->controller->addCSS($this->_path . 'views/vendor/swiper/swiper-bundle.min.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/vendor/photoswipe/photoswipe.min.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/vendor/photoswipe/default-skin/default-skin.min.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/vendor/magnific-popup/magnific-popup.min.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/front/style.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/front/optional.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/front/fitting.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/front/demoday.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/front/default.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/front/list.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/front/custom.css', 'all');


        //$this->context->controller->addJS($this->_path . 'views/js/front/items.js');
        $this->context->controller->addJS($this->_path . 'views/js/front/analytics/analytics.js');

        // VEndor libreria externa
        //$this->context->controller->addJS($this->_path . 'views/vendor/sticky/sticky.min.js');
        $this->context->controller->addJS($this->_path . 'views/vendor/imagesloaded/imagesloaded.pkgd.min.js');
        //$this->context->controller->addJS($this->_path . 'views/vendor/magnific-popup/jquery.magnific-popup.min.js');
        $this->context->controller->addJS($this->_path . 'views/vendor/swiper/swiper-bundle.min.js');
        $this->context->controller->addJS($this->_path . 'views/vendor/zoom/jquery.zoom.js');
        $this->context->controller->addJS($this->_path . 'views/vendor/photoswipe/photoswipe.min.js');
        $this->context->controller->addJS($this->_path . 'views/vendor/photoswipe/photoswipe-ui-default.min.js');
        //$this->context->controller->addJS($this->_path . 'views/vendor/threesixty-degree/threesixty.min.js');

        $this->context->controller->addJS($this->_path . 'views/js/front/main/scripts.js');
        //$this->context->controller->addJS($this->_path . 'views/js/front/view/default.js');
        $this->context->controller->addJS($this->_path . 'views/js/front/view/scripts.js');


    }


}


