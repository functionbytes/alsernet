<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;


class ProductController extends Module
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->ajax = true;
        parent::__construct();
    }



    public function view()
    {

        $requestedIdProductAttribute = 0;
        $isPreview = ('1' === Tools::getValue('preview'));
        $group = Tools::getValue('group');
        $id_product = (int)Tools::getValue('id_product');
        if (!empty($group)) {
            /*$requestedIdProductAttribute = (int) Product::getIdProductAttributesByIdAttributes(
                    $id_product,
                    $group
                );*/
            $requestedIdProductAttribute = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        }
        $_id_ipa = (int)Context::getContext()->cookie->__get('id_unique_ipa');
        $product = $this->getTemplateVarProduct();
        if (Tools::version_compare(_PS_VERSION_, '1.7.4.0', '<')) {
            $product['id_product_attribute'] = ($_id_ipa > 0) ? $_id_ipa : $product['id_product_attribute'];
        } else {
            $product['id_product_attribute'] = ($requestedIdProductAttribute > 0) ? $requestedIdProductAttribute : $product['id_product_attribute'];
            $this->context->cookie->id_unique_ipa = $product['id_product_attribute'];
            $this->context->cookie->write();
        }

        //if (!Product::hayStockPocomaco(!$id_product ? $product["id"] : $id_product, $requestedIdProductAttribute) || (!empty($this->context->cookie->th_country_selected) && $this->context->cookie->th_country_selected != _PSALV_COUNTRY_ID_ES_PENINSULA_)) $mostrar_envio_48_horas = false;
        if (!Product::hayStockPocomaco(!$id_product ? $product["id"] : $id_product, $requestedIdProductAttribute) || ($this->context->customer->isLogged() &&
            !in_array(strtolower($this->context->country->iso_code), ["es", "pt", "fr"]))) $product_full['mostrar_envio_48_horas'] = false;
        else $mostrar_envio_48_horas = true;

        /* BLOQUEOS */
        $bloqueo = false;
        if (Product::bloqueoMarcasCategorias(!$id_product ? $product["id"] : $id_product, (int)$this->context->country, 1)) {
            //Si es Verdadero el cliente no puede comprar este producto en su pais.
            $bloqueo = true;
        }

        if (Product::bloqueoMarcasCategorias(!$id_product ? $product["id"] : $id_product, (int)$this->context->country, 2)) {
            //Si es Verdadero el cliente no puede comprar este producto en su pais.
            $bloqueo = true;
        }

        if (Product::bloqueoFeature(!$id_product ? $product["id"] : $id_product, (int)$this->context->country)) {
            //Si es Verdadero el cliente no puede comprar este producto en su pais.
            $bloqueo = true;
        }

        if (Product::bloqueoEtiqueta(!$id_product ? $product["id"] : $id_product, (int)$this->context->country)) {
            $bloqueo = true;
        }

        $minimalProductQuantity = $this->getProductMinimalQuantity($product);


        ob_end_clean();
        header('Content-Type: application/json');
        echo Tools::jsonEncode(array(
            'product_prices' => $this->render('catalog/_partials/product-prices'),
            'product_cover_thumbnails' => $this->render('catalog/_partials/product-cover-thumbnails'),
            'product_customization' => $this->render(
                'catalog/_partials/product-customization',
                array(
                    'customizations' => $product['customizations'],
                )
            ),
            'product_details' => $this->render('catalog/_partials/product-details'),
            'product_variants' => $this->render('catalog/_partials/product-variants'),
            'product_variants_fitting' => $this->render('catalog/_partials/product-variants-fitting'),
            'product_variants_demoday' => $this->render('catalog/_partials/product-variants-demoday'),
            'product_discounts' => $this->render('catalog/_partials/product-discounts'),
            'product_add_to_cart' => $this->render('catalog/_partials/product-add-to-cart'),
            'product_additional_info' => $this->render('catalog/_partials/product-additional-info'),
            'product_images_modal' => $this->render('catalog/_partials/product-images-modal'),
            'product_url' => $this->context->link->getProductLink(
                $product['id_product'],
                null,
                null,
                null,
                $this->context->language->id,
                null,
                $product['id_product_attribute'],
                false,
                false,
                true,
                $isPreview ? array('preview' => '1') : array()
            ),
            'product_minimal_quantity' => $minimalProductQuantity,
            'product_has_combinations' => !empty($this->combinations),
            'id_product_attribute' => $product['id_product_attribute'],
            'mostrar_envio_48_horas' => $mostrar_envio_48_horas,
            'bloqueo' => $bloqueo
        ));

    }

    public function gets()
    {
        $productSettings = $this->getProductPresentationSettings();
        $extraContentFinder = new ProductExtraContentFinder();
        $_id_ipa = (int)Context::getContext()->cookie->__get('id_unique_ipa');
        $requestedIdProductAttribute = 0;
        $group = Tools::getValue('group');
        $id_product = (int)Tools::getValue('id_product');
        if (isset($_POST['ajax']) && !empty($group)) {
            /*$requestedIdProductAttribute = (int) Product::getIdProductAttributesByIdAttributes(
                    $id_product,
                    $group
                );*/
            $requestedIdProductAttribute = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        }
        $product = $this->objectPresenter->present($this->product);
        $product['id_product'] = (int) $this->product->id;
        $product['out_of_stock'] = (int) $this->product->out_of_stock;
        $product['new'] = (int) $this->product->new;
        if (Tools::version_compare(_PS_VERSION_, '1.7.4.0', '<')) {
            //$product['id_product_attribute'] = ($_id_ipa > 0) ? $_id_ipa : $this->getIdProductAttribute();
            $product['id_product_attribute'] = ($_id_ipa > 0) ? $_id_ipa : $this->getIdProductAttributeByGroupOrRequestOrDefault();
        } else {
            //$product['id_product_attribute'] = ($requestedIdProductAttribute > 0) ? $requestedIdProductAttribute : $this->getIdProductAttribute();
            $product['id_product_attribute'] = ($requestedIdProductAttribute > 0) ? $requestedIdProductAttribute : $this->getIdProductAttributeByGroupOrRequestOrDefault();
        }


        $product['minimal_quantity'] = $this->getProductMinimalQuantity($product);
        $product['quantity_wanted'] = $this->getRequiredQuantity($product);
        $product['extraContent'] = $extraContentFinder->addParams(array('product' => $this->product))->present();
        $product['ecotax'] = Tools::convertPrice((float) $product['ecotax'], $this->context->currency, true, $this->context);
        $product_full = Product::getProductProperties($this->context->language->id, $product, $this->context);

        $product_full = $this->addProductCustomizationData($product_full);

        $product_full['show_quantities'] = (bool) (
            Configuration::get('PS_DISPLAY_QTIES')
            && Configuration::get('PS_STOCK_MANAGEMENT')
            && $this->product->quantity > 0
            && $this->product->available_for_order
            && !Configuration::isCatalogMode()
        );
        $product_full['quantity_label'] = ($this->product->quantity > 1) ? $this->trans('Items', array(), 'Shop.Theme.Catalog') : $this->trans('Item', array(), 'Shop.Theme.Catalog');
        $product_full['quantity_discounts'] = $this->quantity_discounts;

        if ($product_full['unit_price_ratio'] > 0) {
            $unitPrice = ($productSettings->include_taxes) ? $product_full['price'] : $product_full['price_tax_exc'];
            $product_full['unit_price'] = $unitPrice / $product_full['unit_price_ratio'];
        }

        $group_reduction = GroupReduction::getValueForProduct($this->product->id, (int) Group::getCurrent()->id);
        if ($group_reduction === false) {
            $group_reduction = Group::getReduction((int) $this->context->cookie->id_customer) / 100;
        }
        $product_full['customer_group_discount'] = $group_reduction;
        $product_full['title'] = $this->getProductPageTitle();

        // round display price (without formatting, we don't want the currency symbol here, just the raw rounded value
        $product_full['rounded_display_price'] = Tools::ps_round(
            $product_full['price'],
            Context::getContext()->currency->precision
        );

        //if (!Product::hayStockPocomaco(!$id_product ? $product["id"] : $id_product, $requestedIdProductAttribute) || (!empty($this->context->cookie->th_country_selected) && (int)$this->context->cookie->th_country_selected != _PSALV_COUNTRY_ID_ES_PENINSULA_)) $product_full['mostrar_envio_48_horas'] = false;
        if (!Product::hayStockPocomaco(!$id_product ? $product["id"] : $id_product, $product['id_product_attribute']) || ($this->context->customer->isLogged()
                && !in_array(strtolower($this->context->country->iso_code), ["es", "pt", "fr"]))) $product_full['mostrar_envio_48_horas'] = false;
        else $product_full['mostrar_envio_48_horas'] = true;

        /* BLOQUEOS */
        $product_full['bloqueo'] = false;
        $id_iso_code = Db::getInstance()->getValue("SELECT id_country FROM " . _DB_PREFIX_ . "country WHERE iso_code = '" . strtoupper($this->context->language->iso_code) . "'");
        if (Product::bloqueoMarcasCategorias(!$id_product ? $product["id"] : $id_product, $id_iso_code, 1)) {
            //Si es Verdadero el cliente no puede comprar este producto en su pais.
            $product_full['bloqueo'] = true;
        }

        if (Product::bloqueoMarcasCategorias(!$id_product ? $product["id"] : $id_product, $id_iso_code, 2)) {
            //Si es Verdadero el cliente no puede comprar este producto en su pais.
            $product_full['bloqueo'] = true;
        }

        if (Product::bloqueoFeature(!$id_product ? $product["id"] : $id_product, $id_iso_code)) {
            //Si es Verdadero el cliente no puede comprar este producto en su pais.
            $product_full['bloqueo'] = true;
        }

        if (Product::bloqueoEtiqueta(!$id_product ? $product["id"] : $id_product, $id_iso_code)) {
            $product_full['bloqueo'] = true;
        }

        // $product_full['country_blocked'] = $this->product->isBlocked();

        $presenter = $this->getProductPresenter();
        Context::getContext()->cookie->__unset('id_unique_ipa');


        $varreturn = $presenter->present(
            $productSettings,
            $product_full,
            $this->context->language
        );


        return $varreturn;
    }

    public function viewProduct()
    {
        $id_product = (int)Tools::getValue('id_product');
        $iso = Tools::getValue('iso');

        if (empty($id_product) || !Validate::isUnsignedId($id_product)) {
            // No se recibió un ID de producto válido, no hacer nada
            return;
        }

        $idLang = (int)$this->context->language->id;
        $product = new Product($id_product, $idLang);
        // dump($product);die();
        // dump($this->getProductAnalytics($product,$idLang));
        $data = array(
            'product_analytics' => $this->getProductAnalytics($product, $idLang, $iso),
            'list_name' =>  '',
            'list_id'   =>  ''
        );

        return [
            'status' => 'success',
            'message' => '',
            'data' => $data,
        ];
    }

    protected function getProductMinimalQuantity($product)
    {
        $minimal_quantity = 1;

        if ($product['id_product_attribute']) {
            $combination = $this->findProductCombinationById($product['id_product_attribute']);
            if (is_array($combination) && isset($combination['minimal_quantity'])) {
                if ($combination['minimal_quantity']) {
                    $minimal_quantity = $combination['minimal_quantity'];
                }
            }
        } else {
            $minimal_quantity = $this->product->minimal_quantity;
        }

        return $minimal_quantity;
    }

    public function getProductAnalytics($products, $idLang, $iso_code)
    {

        if($iso_code == 'en'){
            $id_country = 17;
        }else{
            $id_country_iso = Db::getInstance()->executeS("select ac.id_country from aalv_country ac where ac.iso_code = '" . $iso_code . "'");
            $id_country = (int)$id_country_iso[0]['id_country'];
        }
        // $id_country_iso = Db::getInstance()->executeS("select ac.id_country from aalv_country ac where ac.iso_code = '" . $iso_code . "'");
        $lang_iso = Db::getInstance()->executeS("select id_lang from aalv_lang al where iso_code = '" . $iso_code . "'");
        $lang = (int)$lang_iso[0]['id_lang'];
        $id_country = (int)$id_country_iso[0]['id_country'];

        $product_info_array = [];

        $id_product_attribute = $products->getDefaultIdProductAttribute();

        // Variable para capturar la regla de descuento aplicada (specific price)
        $specific_price_output = null;

        $id_product            = (int) $products->id;     // o un entero fijo
        $id_product_attribute  = isset($id_product_attribute) ? (int)$id_product_attribute : 0;


        // IDs de contexto (ajusta si querés forzar otros)
        $id_shop      = 1;
        $id_currency  = 1;
        // $id_country   = 6;                  // país del contexto
        $id_state     = 0;
        $zipcode      = '';                                           // opcional si no necesitás reglas por zip
        $id_group     = 3;
        $id_customer  = 0;

        $quantity     = 1;
        $decimals     = 6;  // usá 2 si querés salida redondeada “de cara al usuario”

        // 1) PRECIO BASE (sin descuentos) — con impuestos
        $specific_price = null;
        $price_base_tax_inc = Product::priceCalculation(
            $id_shop,
            $id_product,
            $id_product_attribute,
            $id_country,
            $id_state,
            $zipcode,
            $id_currency,
            $id_group,
            $quantity,
            true,
            $decimals,
            false,
            false,
            true,
            $specific_price,
            true,
            $id_customer,
            true,
            0,
            0,
            0
        );

        // 2) PRECIO FINAL (aplicando specific price si corresponde) — con impuestos
        $specific_price = null;
        $price_final_tax_inc = Product::priceCalculation(
            $id_shop,
            $id_product,
            $id_product_attribute,
            $id_country,
            $id_state,
            $zipcode,
            $id_currency,
            $id_group,
            $quantity,
            true,
            $decimals,
            false,
            true,
            true,
            $specific_price,
            true,
            $id_customer,
            true,
            0,
            0,
            0
        );

        // 3) (Opcional) SOLO EL IMPORTE DE LA REDUCCIÓN — con impuestos
        $tmp = null;
        $reduction_amount_tax_inc = Product::priceCalculation(
            $id_shop,
            $id_product,
            $id_product_attribute,
            $id_country,
            $id_state,
            $zipcode,
            $id_currency,
            $id_group,
            $quantity,
            true,
            $decimals,
            true,
            true,
            true,
            $tmp,
            true,
            $id_customer,
            true,
            0,
            0,
            0
        );

        // Cálculos
        $discount_amount  = round(max(0, $price_base_tax_inc - $price_final_tax_inc), 2);

        $caracteristicas_combinacion = Db::getInstance()->getValue("SELECT
                        GROUP_CONCAT(
                            CONCAT(agl.name, ': ', al.name)
                            ORDER BY ag.position, a.position
                            SEPARATOR ' | '
                        ) AS attributes_full
                        FROM aalv_product_attribute pa
                            JOIN aalv_product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                            JOIN aalv_attribute a ON a.id_attribute = pac.id_attribute
                            JOIN aalv_attribute_lang al ON al.id_attribute = a.id_attribute
                            JOIN aalv_attribute_group ag ON ag.id_attribute_group = a.id_attribute_group
                            JOIN aalv_attribute_group_lang agl ON agl.id_attribute_group = ag.id_attribute_group AND agl.id_lang = al.id_lang
                        WHERE
                            pa.id_product = " . $products->id . "
                            AND al.id_lang = " . $lang . "
                            and pac.id_product_attribute = " . (int)$id_product_attribute . "
                        GROUP BY pa.id_product_attribute, al.id_lang
                        ORDER BY al.id_lang, pa.id_product_attribute;");


        $category = new Category($products->id_category_default, $lang);
        if (Validate::isLoadedObject($category)) {

            $parentName = Tools::strtolower($category->name);
        }
        $parent_category = Product::getSportByDefaultCategory($products);
        $parent_category_data = new Category($parent_category['id_category'],$lang);
        // dump($parent_category_data);die();
        if (Validate::isLoadedObject($parent_category_data)) {
            $parentName_parent_category = Tools::strtolower($parent_category_data->name);
        }
        // dump($parent_category);die();

        $product_info_array[] = [
            'item_id' => $products->id,
            'item_name' => $products->name[$lang],
            'item_brand' => $products->manufacturer_name,
            'item_category' => $parentName,
            'item_variant' => $caracteristicas_combinacion,
            'item_variant2' => '',
            'item_list_name' => $parentName_parent_category,
            'item_list_id' => '',
            'affiliation' => '',
            'price' => round($price_final_tax_inc,2),
            'coupon' => '',
            'discount' => $discount_amount,
            'index' => '',
            'location_id' => '',
            'quantity' => 1
        ];

        return $product_info_array; // Devolver el array con la información de todos los productos

    }
}
