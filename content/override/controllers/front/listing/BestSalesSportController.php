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
//use PrestaShop\PrestaShop\Adapter\BestSales\BestSalesProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

require_once _PS_MODULE_DIR_ . 'alvareznewsbestsales/classes/SportBestsalesConfig.php';

class BestSalesSportControllerCore extends ProductListingFrontController
{
    public $php_self = 'bestsalessport';
    public $config_data;

    public function canonicalRedirection($canonicalURL = '')
    {
        //dump($_GET);
        if ($_GET['deporte']) {
            parent::canonicalRedirection($this->context->link->getBestSalesDeporteLink($this->getIdDeporteByName($_GET['deporte'])));
            if($this->getCurrentURL() != $this->context->link->getBestSalesDeporteLink($this->getIdDeporteByName($_GET['deporte'])) && !Tools::getValue('page') && !Tools::getValue('order')){
                Tools::redirectLink($this->context->link->getBestSalesDeporteLink($this->getIdDeporteByName($_GET['deporte'])));
            }
        }else{
            parent::canonicalRedirection($this->context->link->getBestSalesDeporteLink(Tools::getValue('id_category')));
            if($this->getCurrentURL() != $this->context->link->getBestSalesDeporteLink($this->getIdDeporteByName(Tools::getValue('id_category'))) && !Tools::getValue('page') && !Tools::getValue('order')) {
                Tools::redirectLink($this->context->link->getBestSalesDeporteLink($this->getIdDeporteByName(Tools::getValue('id_category'))));
            }
        }
    }

    public function getAlternativeLangsUrl()
    {
        $alternativeLangs = parent::getAlternativeLangsUrl();

            $languages = Language::getLanguages(true, $this->context->shop->id);
            foreach ($languages as $lang) {
                $alternativeLangs[$lang['language_code']] = $this->context->link->getBestSalesDeporteLink(Tools::getValue('id_category'),null,$lang['id_lang']);
            }

        return $alternativeLangs;
    }

    public function getIdDeporteByName($name_deporte)
    {
        return Db::getInstance()->getValue("SELECT `id_category` FROM "._DB_PREFIX_."category_lang WHERE (name = '".$name_deporte."' || name = '".strtoupper($name_deporte)."' || link_rewrite = '".$name_deporte."' || link_rewrite = '".str_replace(' ', '-', $name_deporte)."') AND id_lang = ".$this->context->language->id);
    }

    /**
     * Initializes controller.
     *
     * @see FrontController::init()
     *
     * @throws PrestaShopException
     */
    public function init()
    {
            parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        parent::initContent();

        $id_config = SportBestsalesConfig::getConfigByIdCategory((int) Tools::getValue("id_category"));
        if ($id_config) {
            $this->config_data = new SportBestsalesConfig((int) $id_config['id_sport_bestsales_config']);
        } else {
            $this->config_data = new SportBestsalesConfig();
        }

        $variables = $this->getProductSearchVariables();

        $this->context->smarty->assign([
            'listing' => $variables,
            'title' => $this->trans('Best sellers', [], 'Shop.Theme.Catalog'),
        ]);

        $this->setTemplate('catalog/listing/best-sales', [
            // 'id' => $this->category->id,
            // 'category' => $this->category,
            'entity' => 'best-sales-sport',
            'listing' => $variables,
        ]);


    }

    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setQueryType('best-sales-sport')
            ->setIdCategory(Tools::getValue("id_category"))
            ->setSortOrder(new SortOrder('config_data', 'order', 'asc'));

        return $query;
    }

    protected function getDefaultProductSearchProvider()
    {

        $aux= new BestSalesSportProductSearchProvider(
            $this->getTranslator()
        );

        return $aux;

    }

    public function getListingLabel()
    {
        return $this->getTranslator()->trans('Best sellers', [], 'Shop.Theme.Catalog');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $category = new Category(Tools::getValue("id_category"));


        $breadcrumb['links'][] = [
            'title' => $category->name[$this->context->language->id],
            'url' => $this->context->link->getCategoryLink(Tools::getValue("id_category")),
        ];

        $breadcrumb['links'][] = [
            'title' => $this->trans('Best sellers', [], 'Shop.Theme.Catalog'),
            //'url' => $this->context->link->getPageLink('best-sales-sport&id_category='.Tools::getValue("id_category"), true, ),
            'url' => $this->context->link->getBestSalesDeporteLink(Tools::getValue("id_category")),
        ];

        return $breadcrumb;
    }



    protected function getProductSearchVariables()
    {
        /*
         * To render the page we need to find something (a ProductSearchProviderInterface)
         * that knows how to query products.
         */

        // the search provider will need a context (language, shop...) to do its job
        $context = $this->getProductSearchContext();

        // the controller generates the query...
        $query = $this->getProductSearchQuery();

        // ...modules decide if they can handle it (first one that can is used)
        //$provider = $this->getProductSearchProviderFromModules($query);

        // if no module wants to do the query, then the core feature is used
        //if (null === $provider) {
        $provider = $this->getDefaultProductSearchProvider();
        $provider->list_type = BestSalesSportProductSearchProvider::TYPE_LIST;
        $provider->id_category_sport = $this->config_data->id_category_sport;
        //}


        $resultsPerPage = (int) Tools::getValue('resultsPerPage');
        if ($resultsPerPage <= 0) {
            $resultsPerPage = Configuration::get('PS_PRODUCTS_PER_PAGE');
        }

        // we need to set a few parameters from back-end preferences
        $query
            ->setResultsPerPage($resultsPerPage)
            ->setPage(max((int) Tools::getValue('page'), 1))
        ;

        // set the sort order if provided in the URL
        if (($encodedSortOrder = Tools::getValue('order'))) {
            $query->setSortOrder(SortOrder::newFromString(
                $encodedSortOrder
            ));
        }

        // get the parameters containing the encoded facets from the URL
        $encodedFacets = Tools::getValue('q');

        /*
         * The controller is agnostic of facets.
         * It's up to the search module to use /define them.
         *
         * Facets are encoded in the "q" URL parameter, which is passed
         * to the search provider through the query's "$encodedFacets" property.
         */

        $query->setEncodedFacets($encodedFacets);

        Hook::exec('actionProductSearchProviderRunQueryBefore', [
            'query' => $query,
        ]);

        // We're ready to run the actual query!

        /** @var ProductSearchResult $result */
        $result = $provider->runQuery(
            $context,
            $query
        );

        Hook::exec('actionProductSearchProviderRunQueryAfter', [
            'query' => $query,
            'result' => $result,
        ]);

        if (Configuration::get('PS_CATALOG_MODE') && !Configuration::get('PS_CATALOG_MODE_WITH_PRICES')) {
            $this->disablePriceControls($result);
        }

        // sort order is useful for template,
        // add it if undefined - it should be the same one
        // as for the query anyway
        if (!$result->getCurrentSortOrder()) {
            $result->setCurrentSortOrder($query->getSortOrder());
        }

        // prepare the products
        $products = $this->prepareMultipleProductsForTemplate(
            $result->getProducts()
        );

        // render the facets
        if ($provider instanceof FacetsRendererInterface) {
            // with the provider if it wants to
            $rendered_facets = $provider->renderFacets(
                $context,
                $result
            );
            $rendered_active_filters = $provider->renderActiveFilters(
                $context,
                $result
            );
        } else {
            // with the core
            $rendered_facets = $this->renderFacets(
                $result
            );
            $rendered_active_filters = $this->renderActiveFilters(
                $result
            );
        }

        $pagination = $this->getTemplateVarPagination(
            $query,
            $result
        );

        // prepare the sort orders
        // note that, again, the product controller is sort-orders
        // agnostic
        // a module can easily add specific sort orders that it needs
        // to support (e.g. sort by "energy efficiency")
        $sort_orders = $this->getTemplateVarSortOrders(
            $result->getAvailableSortOrders(),
            $query->getSortOrder()->toString()
        );

        $sort_selected = false;
        if (!empty($sort_orders)) {
            foreach ($sort_orders as $order) {
                if (isset($order['current']) && true === $order['current']) {
                    $sort_selected = $order['label'];

                    break;
                }
            }
        }

        $searchVariables = [
            'result' => $result,
            'label' => $this->getListingLabel(),
            'products' => $products,
            'sort_orders' => $sort_orders,
            'sort_selected' => $sort_selected,
            'pagination' => $pagination,
            'rendered_facets' => $rendered_facets,
            'rendered_active_filters' => $rendered_active_filters,
            'js_enabled' => $this->ajax,
            'current_url' => $this->updateQueryString([
                'q' => $result->getEncodedFacets(),
            ]),
        ];

        Hook::exec('filterProductSearch', ['searchVariables' => &$searchVariables]);
        Hook::exec('actionProductSearchAfter', $searchVariables);

        return $searchVariables;
    }



public function getCategory(){
    //para que el categorytree coja la categoria
    $category = new Category(Tools::getValue("id_category"));
    return $category;
}


public function getLayout()
    {
        /*$entity = $this->php_self;

        $layout = $this->context->shop->theme->getLayoutRelativePathForPage($entity);

        dump($layout);

        layouts/layout-both-columns.tpl

        if ($overridden_layout = Hook::exec('overrideLayoutTemplate', array(
            'default_layout' => $layout,
            'entity' => $entity,
            'locale' => $this->context->language->locale,
            'controller' => $this))) {
            return $overridden_layout;
        }

        if ((int) Tools::getValue('content_only')) {
            $layout = 'layouts/layout-content-only.tpl';
        }
*/
        return "layouts/layout-left-column.tpl";
    }

}
