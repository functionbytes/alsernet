<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class CategoryController extends Module
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->ajax = true;
        parent::__construct();
    }

    public function news($category, $type)
    {

        $title = $this->getTranslator()->trans('Newest', [], 'Modules.alsernetproducts.Front');
        $link = Context::getContext()->link->getBestSalesDeporteLink($category);
        $products = $this->getProducts($category, $type);

        $data = array(
            'products' => $products,
            'link' => $link,
            'title' => $title,
        );

        return [
                'status' => 'success',
                'message' => '',
                'data' => $data,
        ];

    }

    public function sales($category, $type)
    {

        $title = $this->getTranslator()->trans('Best sales', [], 'Modules.alsernetproducts.Front');
        $link = Context::getContext()->link->getBestSalesDeporteLink($category);
        $products = $this->getProducts($category, $type);

        $data = array(
            'products' => $products,
            'link' => $link,
            'title'=> $title,
            'type'=> $type,
        );

        return [
                'status' => 'success',
                'message' => '',
                'data' => $data,
        ];

    }

    public function analytics($category, $type)
    {

        $type = Tools::getValue('type');
        $parentId = Tools::getValue('category');
        $parentName = '';

        if ($parentId > 0) {
            $idLang = (int)$this->context->language->id;
            $category = new Category($parentId, $idLang);
            if (Validate::isLoadedObject($category)) {
                $parentName = Tools::strtolower($category->name);
            }
        }

        $product_analytics = $this->getProducts($parentId, $type,$parentName);


        $data = array(
            'product_analytics' => $product_analytics,
            'list_name' =>  $parentName,
            'list_id'   =>  $parentName
        );

        return [
                'status' => 'success',
                'message' => '',
                'data' => $data,
        ];

    }

    function getProducts($category, $type ,$parent)
    {

        $context = new ProductSearchContext($this->context);
        $query = new ProductSearchQuery();

        if ($type == "sales"){

            $searchProvider = new BestSalesSportProductSearchProvider($this->getTranslator());
            $searchProvider->list_type = BestSalesSportProductSearchProvider::TYPE_HOME;
            $searchProvider->id_category_sport = (int) $category;


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


        }elseif ($type == "news"){

            $searchProvider = new NewProductsSportProductSearchProvider($this->getTranslator());
            $searchProvider->list_type = NewProductsSportProductSearchProvider::TYPE_HOME;
            $searchProvider->id_category_sport = (int) $category;

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


        }elseif($type == "analytics"){

            $searchProviderAnalyticsNews = new NewProductsSportProductSearchProvider($this->getTranslator());
            $searchProviderAnalyticsNews->list_type = NewProductsSportProductSearchProvider::TYPE_HOME;
            $searchProviderAnalyticsNews->id_category_sport = (int) $category;
            $searchProviderAnalyticsBests = new BestSalesSportProductSearchProvider($this->getTranslator());
            $searchProviderAnalyticsBests->list_type = BestSalesSportProductSearchProvider::TYPE_HOME;
            $searchProviderAnalyticsBests->id_category_sport = (int) $category;

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
                $this->getProductAnalytics($resultAnalyticsBests->getProducts(), "analytics",$parent),
                $this->getProductAnalytics($resultAnalyticsNews->getProducts(), "analytics",$parent)
            );

            return $products;

        }



    }

    public function getCustomerAnalytics($category) {

            $order = Context::getContext()->cart;

            return [
                'user_id' => (!empty($order->id_customer)) ? $order->id_customer : 0 ,
                'user_type' => (!empty($order->id_customer)) ? 'registrado' : 'invitado',
                'country' => Context::getContext()->language->iso_code,
                'page_type' => Context::getContext()->controller->getPageName(), // 'order-confirmation',
                'payment_type' => $order->payment,
                'item_list_name' => $category->name,
                'item_list_id' => strtolower($category->name),
                'currency' =>  'EUR'
        ];


    }

    public function getProductAnalytics($products,$type = null,$parent = null) {

        $lang  = $this->context->language->id;
        $id_combinacion_stock = null;
        $product_info_array = [];

            foreach ($products as $product) {


                $id_articulo = Db::getInstance()->getValue("
                    SELECT id_articulo
                    FROM (
                        SELECT id_articulo FROM aalv_combinacionunica_import aci WHERE id_product = ".$product['id_product']."
                        UNION
                        SELECT aci.id_articulo
                        FROM aalv_combinaciones_import aci
                        LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                        WHERE apa.id_product = ".$product['id_product']."
                    ) AS produ
                    GROUP BY id_articulo
                ");

                $sql_caracteristicas = "
                    SELECT aal.name
                    FROM aalv_product_attribute_combination apac
                    LEFT JOIN aalv_attribute_lang aal ON aal.id_attribute = apac.id_attribute
                    WHERE aal.id_lang = ".$lang."
                    AND apac.id_product_attribute =".(int)$id_combinacion_stock;

                $caracteristicas_combinacion = Db::getInstance()->getValue($sql_caracteristicas);


                if($caracteristicas_combinacion == null){
                    $caracteristicas_combinacion = "";
                }

                $sql = "SELECT * FROM aalv_specific_price WHERE id_product = ".(int)$product['id_product'];

                $specific_prices = Db::getInstance()->executeS($sql);

                $precio_minimo = 10000000;
                $id_combinacion_stock = null;

                foreach ($specific_prices as $specific_price) {
                    $stock_combinacion = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $specific_price['id_product_attribute']);

                    // Verifica si hay stock y si el precio actual es menor al mínimo
                    if ($stock_combinacion > 0 && $specific_price['price'] < $precio_minimo) {
                        // Calcula el precio con IVA aplicando las reglas de impuestos
                        $precio_con_iva = Product::getPriceStatic(
                            $specific_price['id_product'],
                            true, // con impuestos
                            $specific_price['id_product_attribute'],
                            2, // precisión del precio
                            null, // fecha específica (puedes ajustar esto según tus necesidades)
                            false, // utiliza el precio reducido si está configurado
                            true, // utiliza el ecotax si está configurado
                            1 // cantidad (generalmente 1)
                            // false, // obtén el precio del producto, no el total
                            // null, // ID del cliente (puedes ajustar esto según tus necesidades)
                            // null, // ID de la tienda (puedes ajustar esto según tus necesidades)
                            // true // reglas de impuestos aplicables
                        );



                        if ($precio_con_iva < $precio_minimo) {
                            $precio_minimo = round($precio_con_iva, 2);
                            $id_combinacion_stock = $specific_price['id_product_attribute'];

                            $sql_caracteristicas = "select
                                                        aal.name
                                                    from
                                                        aalv_product_attribute_combination apac
                                                        left join aalv_attribute_lang aal on aal.id_attribute = apac.id_attribute
                                                    where
                                                        aal.id_lang = ".$lang."
                                                        and apac.id_product_attribute =".(int)$id_combinacion_stock;
                            $caracteristicas_combinacion = Db::getInstance()->getValue($sql_caracteristicas);
                            $descuento_combinacion = SpecificPrice::getSpecificPrice(
                                $product['id_product'],
                                $id_shop = null,
                                $id_combinacion_stock,
                                $id_country = null,
                                $id_group = null,
                                $id_customer = null,
                                $id_currency = null,
                                $id_country = null,
                                $id_group = null,
                                $id_customer = null,
                                $id_combinacion_stock,
                                $cart_quantity = 1,
                                $real_quantity = true,
                                $id_currency = null,
                                $id_country = null,
                                $id_group = null,
                                $id_customer = null,
                                $id_shop = null
                            );
                            $reduction_sin_iva = $descuento_combinacion['reduction'];

                            $sql_rate = "select
                                                        at2.rate
                                                    from
                                                        aalv_lang al
                                                        left join aalv_country ac on UPPER(ac.iso_code) COLLATE utf8mb4_unicode_ci = UPPER(al.iso_code) COLLATE utf8mb4_unicode_ci
                                                        left join aalv_tax_rule atr on atr.id_country = ac.id_country
                                                        left join aalv_tax at2 on at2.id_tax = atr.id_tax
                                                    WHERE
                                                        al.id_lang = ".$lang."
                                                    GROUP BY atr.id_country";
                            $tax_percentage = Db::getInstance()->getValue($sql_rate);
                            // Calcula el descuento con IVA
                            $aumento = $reduction_sin_iva * ($tax_percentage / 100);
                            $discount = round($reduction_sin_iva + $aumento, 2);

                        }
                    }
                }

                $product_info_array[] = [
                    'item_id' => $product['id_product'],
                    'item_name' => $product['name'],
                    'item_brand' => $product['manufacturer_name'],
                    'item_category' => $product['category_name'],
                    'item_variant' => $caracteristicas_combinacion,
                    'item_variant2' => '',
                    'item_list_name' => $parent,
                    'item_list_id' => $parent,
                    'affiliation' => '',
                    'price' => $precio_minimo,
                    'coupon' => '',
                    'discount' => $discount,
                    'index' => '',
                    'location_id' => '',
                    'quantity' => 1
                ];


        }

        return $product_info_array; // Devolver el array con la información de todos los productos

    }

}


