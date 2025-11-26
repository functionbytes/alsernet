<?php


//namespace PrestaShop\PrestaShop\Adapter\BestSalesSport;

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrderFactory;
//use ProductSale;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Used to query the latest products, see NewProductsController in Front Office.
 */
class BestSalesSportProductSearchProvider implements ProductSearchProviderInterface
{
    CONST TYPE_HOME = 1; // escaparate en home deporte
    CONST TYPE_LIST = 2; // listado de deporte

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Tipo de listado
     */
    public $list_type;

    /**
     * Id categoría deporte
     */
    public $id_category_sport;

    /**
     * @var SortOrderFactory
     */
    private $sortOrderFactory;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
        $this->sortOrderFactory = new SortOrderFactory($this->translator);

        // por defecto, listado
        $this->list_type = self::TYPE_LIST;
    }

    /**
     * @param ProductSearchContext $context
     * @param ProductSearchQuery $query
     * @param string $type
     *
     * @return array
     */
    private function getBestsaleProducts($id_lang, $page_number = 0, $nb_products = 10, $count = false, $order_by = null, $order_way = null, Context $context = null, $id_category)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        if ($page_number < 1) {
            $page_number = 1;
        }

        if ($nb_products < 1) {
            $nb_products = 10;
        }

        if (empty($order_by) || $order_by == 'position' || $order_by == 'sales') {
            $order_by = 'order';
        }

        if (empty($order_way)) {
            $order_way = 'ASC';
        }

        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'product_shop';
        } elseif ($order_by == 'name') {
            $order_by_prefix = 'pl';
        } elseif ($order_by == 'order') {
            $order_by_prefix = 'config_data';
        }

        if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way)) {
            die(Tools::displayError());
        }

        $sql_groups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql_groups = ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
            JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id) . ')
            WHERE cp.`id_product` = p.`id_product`)';
        }

        if (strpos($order_by, '.') > 0) {
            $order_by = explode('.', $order_by);
            $order_by_prefix = $order_by[0];
            $order_by = $order_by[1];
        }

        if ($count) {
            /*$sql = 'SELECT COUNT(p.`id_product`) AS nb
                    FROM `' . _DB_PREFIX_ . 'product` p
                    ' . Shop::addSqlAssociation('product', 'p') . '
                    '.$this->getSqlInnerConfigData().'
                    WHERE product_shop.`active` = 1
                    ' . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
                    ' . $sql_groups. ' and EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp2 WHERE cp2.`id_product` = p.`id_product` and cp2.id_category='.$id_category.')';*/
            $sql = 'SELECT COUNT(p.`id_product`) AS nb
                    FROM `' . _DB_PREFIX_ . 'product` p
                    ' . Shop::addSqlAssociation('product', 'p') . '
                    '.$this->getSqlInnerConfigData().'
                    WHERE product_shop.`active` = 1
                    ' . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
                    ' . $sql_groups;

            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        $sql = new DbQuery();
        $sql->select(
            'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
            pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name, config_data.`order`'
        );

        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->join($this->getSqlInnerConfigData());
        $sql->leftJoin(
            'product_lang',
            'pl',
            '
            p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id);
        $sql->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang);
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

        $sql->where('product_shop.`active` = 1');
        if ($front) {
            $sql->where('product_shop.`visibility` IN ("both", "catalog")');
        }
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql->where('EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
            JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id) . ')
            WHERE cp.`id_product` = p.`id_product`)');
        }
        //$sql->where('EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp2 WHERE cp2.`id_product` = p.`id_product` and cp2.id_category='.$id_category.')');

        if ($order_by !== 'price') {
            $sql->orderBy((isset($order_by_prefix) ? pSQL($order_by_prefix) . '.' : '') . '`' . pSQL($order_by) . '` ' . pSQL($order_way));
            $sql->limit($nb_products, (int) (($page_number - 1) * $nb_products));
        }

        if (Combination::isFeatureActive()) {
            $sql->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id);
        }

        $sql->join(Product::sqlStock('p', 0));
        $sql->where('stock.quantity>0');
        
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        

        if (!$result) {
            return false;
        }

        if ($order_by === 'price') {
            Tools::orderbyPrice($result, $order_way);
            //$result = array_slice($result, (int) (($nb_products - 1) * $page_number), (int) $page_number);
            $result = array_slice($result, (int) ((int) ($page_number - 1) * (int) $nb_products), (int) $nb_products);
        }
        $products_ids = [];
        foreach ($result as $row) {
            $products_ids[] = $row['id_product'];
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheFrontFeatures($products_ids, $id_lang);

        return Product::getProductsProperties((int) $id_lang, $result);
    }

    private function getSqlInnerConfigData() {
        $table = '';

        switch ($this->list_type) {
            case self::TYPE_HOME:
                $table = 'sport_bestsales_home_config_data_final';
                break;
            case self::TYPE_LIST:
                $table = 'sport_bestsales_list_config_data_final';
                break;
        }

        if (!empty($table)) {
            return ' INNER JOIN `'._DB_PREFIX_.$table.'` config_data ON config_data.`id_product`=p.`id_product` AND config_data.`id_category`= '.$this->id_category_sport.' ';
        }
    }

    private function getProductsOrCount(
        ProductSearchContext $context,
        ProductSearchQuery $query,
        $type = 'products'
    ) {
        return $this->getBestsaleProducts(
            $context->getIdLang(),
            $query->getPage(),
            $query->getResultsPerPage(),
            $type !== 'products',
            $query->getSortOrder()->toLegacyOrderBy(),
            $query->getSortOrder()->toLegacyOrderWay(),
            null,
            $query->getIdCategory()
        );
    }

    /**
     * @param ProductSearchContext $context
     * @param ProductSearchQuery $query
     *
     * @return ProductSearchResult
     */
    public function runQuery(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        /*$sortBySales = (new SortOrder('product', 'sales', 'desc'))->setLabel(
            $this->translator->trans('Sales, highest to lowest', [], 'Shop.Theme.Catalog')
        );*/
        $sortBySales = (new SortOrder('product', 'sales', 'asc'))->setLabel(
            $this->translator->trans('Sales, highest to lowest', [], 'Shop.Theme.Catalog')
        );

        if (!Tools::getValue('order', 0)) {
            $query->setSortOrder($sortBySales);
        }

        if (!$products = $this->getProductsOrCount($context, $query, 'products')) {
            $products = [];
        }
        
        /*if (!$products = ProductSale::getBestSalesSport(
            $context->getIdLang(),
            $query->getPage(),
            $query->getResultsPerPage(),
            $query->getSortOrder()->toLegacyOrderBy(),
            $query->getSortOrder()->toLegacyOrderWay(),
            $query->getIdCategory()
        )) {
            $products = [];
        }*/

        //$count = (int) ProductSale::getNbSalesSport($query->getIdCategory());
        $count = $this->getProductsOrCount($context, $query, 'count');

        $result = new ProductSearchResult();

        if (!empty($products)) {
            $result
                ->setProducts($products)
                ->setTotalProductsCount($count);

            $result->setAvailableSortOrders(
                [
                    $sortBySales,
                    (new SortOrder('product', 'name', 'asc'))->setLabel(
                        $this->translator->trans('Name, A to Z', [], 'Shop.Theme.Catalog')
                    ),
                    (new SortOrder('product', 'name', 'desc'))->setLabel(
                        $this->translator->trans('Name, Z to A', [], 'Shop.Theme.Catalog')
                    ),
                    (new SortOrder('product', 'price', 'asc'))->setLabel(
                        $this->translator->trans('Price, low to high', [], 'Shop.Theme.Catalog')
                    ),
                    (new SortOrder('product', 'price', 'desc'))->setLabel(
                        $this->translator->trans('Price, high to low', [], 'Shop.Theme.Catalog')
                    ),
                ]
            );
        }

        return $result;
    }
}
