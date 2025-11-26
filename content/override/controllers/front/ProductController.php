<?php

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductExtraContentFinder;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PrestaShop\Core\Product\ProductInterface;

class ProductController extends ProductControllerCore
{
    protected $quantity_discounts;

    public function init()
    {
        parent::init();

        $this->context->cookie->id_unique_ipa = 0;
        $this->context->cookie->write();

        //$this->linkRewrite();
        //$this->accentedChars();

    }

    public function initContent()
    {
        if (!$this->errors) {
            if (
                Pack::isPack((int) $this->product->id)
                && !Pack::isInStock((int) $this->product->id, $this->product->minimal_quantity, $this->context->cart)
            ) {
                $this->product->quantity = 0;
            }

            $this->product->description = $this->transformDescriptionWithImg($this->product->description);

            $priceDisplay = Product::getTaxCalculationMethod((int) $this->context->cookie->id_customer);
            $productPrice = 0;
            $productPriceWithoutReduction = 0;

            if (!$priceDisplay || $priceDisplay == 2) {
                $productPrice = $this->product->getPrice(true, null, 6);
                $productPriceWithoutReduction = $this->product->getPriceWithoutReduct(false, null);
            } elseif ($priceDisplay == 1) {
                $productPrice = $this->product->getPrice(false, null, 6);
                $productPriceWithoutReduction = $this->product->getPriceWithoutReduct(true, null);
            }

            if (Tools::isSubmit('submitCustomizedData')) {
                // If cart has not been saved, we need to do it so that customization fields can have an id_cart
                // We check that the cookie exists first to avoid ghost carts
                if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
                    $this->context->cart->add();
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
                $this->pictureUpload();
                $this->textRecord();
            } elseif (Tools::getIsset('deletePicture') && !$this->context->cart->deleteCustomizationToProduct($this->product->id, Tools::getValue('deletePicture'))) {
                $this->errors[] = $this->trans('An error occurred while deleting the selected picture.', [], 'Shop.Notifications.Error');
            }

            $pictures = [];
            $text_fields = [];
            if ($this->product->customizable) {
                $files = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_FILE, true);
                foreach ($files as $file) {
                    $pictures['pictures_' . $this->product->id . '_' . $file['index']] = $file['value'];
                }

                $texts = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_TEXTFIELD, true);

                foreach ($texts as $text_field) {
                    $text_fields['textFields_' . $this->product->id . '_' . $text_field['index']] = str_replace('<br />', "\n", $text_field['value']);
                }
            }

            $this->context->smarty->assign([
                'pictures' => $pictures,
                'textFields' => $text_fields,
            ]);

            $this->product->customization_required = false;

            $customization_fields = $this->product->customizable ? $this->product->getCustomizationFields($this->context->language->id) : false;
            if (is_array($customization_fields)) {
                foreach ($customization_fields as &$customization_field) {
                    if ($customization_field['type'] == Product::CUSTOMIZE_FILE) {
                        $customization_field['key'] = 'pictures_' . $this->product->id . '_' . $customization_field['id_customization_field'];
                    } elseif ($customization_field['type'] == Product::CUSTOMIZE_TEXTFIELD) {
                        $customization_field['key'] = 'textFields_' . $this->product->id . '_' . $customization_field['id_customization_field'];
                    }
                }
                unset($customization_field);
            }

            // Assign template vars related to the category + execute hooks related to the category
            $this->assignCategory();
            // Assign template vars related to the price and tax
            $this->assignPriceAndTax();

            // Assign attributes combinations to the template
            $this->assignAttributesCombinations();

            // Pack management
            $pack_items = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : [];

            $assembler = new ProductAssembler($this->context);
            $presenter = new ProductListingPresenter(
                new ImageRetriever(
                    $this->context->link
                ),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->getTranslator()
            );
            $presentationSettings = $this->getProductPresentationSettings();

            $presentedPackItems = [];
            foreach ($pack_items as $item) {
                $presentedPackItems[] = $presenter->present(
                    $this->getProductPresentationSettings(),
                    $assembler->assembleProduct($item),
                    $this->context->language
                );
            }

            $this->context->smarty->assign('packItems', $presentedPackItems);
            $this->context->smarty->assign('noPackPrice', $this->product->getNoPackPrice());
            $this->context->smarty->assign('displayPackPrice', ($pack_items && $productPrice < $this->product->getNoPackPrice()) ? true : false);
            $this->context->smarty->assign('priceDisplay', $priceDisplay);
            $this->context->smarty->assign('packs', Pack::getPacksTable($this->product->id, $this->context->language->id, true, 1));

            $accessories = $this->product->getAccessories($this->context->language->id);
            if (is_array($accessories)) {
                foreach ($accessories as &$accessory) {
                    $accessory = $presenter->present(
                        $presentationSettings,
                        Product::getProductProperties($this->context->language->id, $accessory, $this->context),
                        $this->context->language
                    );
                }
                unset($accessory);
            }

            if ($this->product->customizable) {
                $customization_datas = $this->context->cart->getProductCustomization($this->product->id, null, true);
            }

            $product_for_template = $this->getTemplateVarProduct();

            $filteredProduct = Hook::exec(
                'filterProductContent',
                ['object' => $product_for_template],
                null,
                false,
                true,
                false,
                null,
                true
            );
            if (!empty($filteredProduct['object'])) {
                $product_for_template = $filteredProduct['object'];
            }

            $productManufacturer = new Manufacturer((int) $this->product->id_manufacturer, $this->context->language->id);

            $manufacturerImageUrl = $this->context->link->getManufacturerImageLink($productManufacturer->id);
            $undefinedImage = $this->context->link->getManufacturerImageLink(null);
            if ($manufacturerImageUrl === $undefinedImage) {
                $manufacturerImageUrl = null;
            }

            $productBrandUrl = $this->context->link->getManufacturerLink($productManufacturer->id);

            $this->context->smarty->assign([
                'priceDisplay' => $priceDisplay,
                'productPriceWithoutReduction' => $productPriceWithoutReduction,
                'customizationFields' => $customization_fields,
                'id_customization' => empty($customization_datas) ? null : $customization_datas[0]['id_customization'],
                'accessories' => $accessories,
                'product' => $product_for_template,
                'displayUnitPrice' => (!empty($this->product->unity) && $this->product->unit_price_ratio > 0.000000) ? true : false,
                'product_manufacturer' => $productManufacturer,
                'manufacturer_image_url' => $manufacturerImageUrl,
                'product_brand_url' => $productBrandUrl,
            ]);

            // Assign attribute groups to the template
            $this->assignAttributesGroups($product_for_template);
        }

        parent::initContent();
    }

    public function accentedChars()
    {
        $allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
        if ($allow_accented_chars > 0) {
            $id_product = (int)Tools::getValue('id_product');
            if ($id_product <= 0) {
                $id = (int)$this->crawlDbForId($_GET['product_rewrite']);
                if ($id > 0) {
                    $_POST['id_product'] = $id;
                }
            }
        }
    }

    public function linkRewrite()
    {

        $link_rewrite = Tools::safeOutput(urldecode(Tools::getValue('product_rewrite')));
        $prod_pattern = '/.*?\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)\.html/';
        preg_match($prod_pattern, $_SERVER['REQUEST_URI'], $url_array);

        if (isset($url_array[2]) && $url_array[2] != '') {
            $link_rewrite = $url_array[2];
        }

        if ($link_rewrite) {
            $id_lang = $this->context->language->id;
            $id_shop = $this->context->shop->id;
            $sql = 'SELECT id_product
                        FROM ' . _DB_PREFIX_ . 'product_lang
                        WHERE link_rewrite = \'' . pSQL($link_rewrite) . '\' AND id_lang = ' . (int)$id_lang . ' AND id_shop = ' . (int)$id_shop;
            $id_product = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
            if ($id_product > 0) {
                $_POST['id_product'] = $id_product;
                $_GET['product_rewrite'] = '';
            } else {
                $prod_pattern_sec = '/.*?\/([0-9]+)\-([_a-zA-Z0-9-\pL]*\-[0-9\pL]*)\.html/';
                preg_match($prod_pattern_sec, $_SERVER['REQUEST_URI'], $url_array_sec);

                if (isset($url_array_sec[2]) && $url_array_sec[2] != '') {
                    $segments = explode('-', $url_array_sec[2]);
                    array_pop($segments);
                    $link_rewrite = implode('-', $segments);
                }
                $sql = 'SELECT id_product
                        FROM ' . _DB_PREFIX_ . 'product_lang
                        WHERE link_rewrite = \'' . pSQL($link_rewrite) . '\' AND id_lang = ' . (int)$id_lang . ' AND id_shop = ' . (int)$id_shop;
                $id_product = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                if ($id_product > 0) {
                    $_POST['id_product'] = $id_product;
                    $_GET['product_rewrite'] = '';
                }
            }
        }
    }


    protected function assignAttributesGroups($product_for_template = null)
    {
        $colors = [];
        $groups = [];
        $this->combinations = [];

        // --- 1) Selección actual desde REQUEST (group[...] o ipa) ---
        $requested_groups = [];
        if (Tools::getIsset('group')) {
            $g = Tools::getValue('group');
            if (is_array($g)) {
                foreach ($g as $gid => $aid) {
                    $requested_groups[(int)$gid] = (int)$aid;
                }
            }
        }
        if (Tools::getIsset('ipa')) {
            $ipa = (int)Tools::getValue('ipa');
            if ($ipa > 0) {
                $rowsIpa = Db::getInstance()->executeS(
                    'SELECT a.id_attribute, a.id_attribute_group
                 FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                 INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute
                 WHERE pac.id_product_attribute=' . (int)$ipa
                );
                foreach ($rowsIpa as $r) {
                    $requested_groups[(int)$r['id_attribute_group']] = (int)$r['id_attribute'];
                }
            }
        }
        if (empty($requested_groups) && isset($product_for_template['attributes']) && is_array($product_for_template['attributes'])) {
            foreach ($product_for_template['attributes'] as $gid => $row) {
                if (isset($row['id_attribute'])) {
                    $requested_groups[(int)$gid] = (int)$row['id_attribute'];
                }
            }
        }

        // --- 2) Obtener atributos del producto ---
        $attributes_groups = $this->product->getAttributesGroups($this->context->language->id);
        if (!is_array($attributes_groups) || !$attributes_groups) {
            $this->context->smarty->assign([
                'groups' => [],
                'colors' => false,
                'combinations' => [],
                'combinationImages' => [],
            ]);
            return;
        }

        $combination_images = $this->product->getCombinationImages($this->context->language->id);
        $combination_prices_set = [];

        // Para acumular cantidades por atributo (suma de las combinaciones que lo incluyen)
        $attributeStockSum = [];      // [id_attribute] => qty total
        $attributeCombinationIds = []; // [id_attribute] => [ipa1, ipa2, ...]

        /* -------------------------------------------
     * 3) Construcción de $groups y $this->combinations
     * ------------------------------------------- */

        // Regla especial: detectar selección del grupo 192 (por ejemplo "fecha")
        $selectedFechaAttributeId = 0;
        if (!empty($requested_groups[192])) {
            $selectedFechaAttributeId = (int)$requested_groups[192];
        } elseif (!empty($product_for_template['attributes'][192]['id_attribute'])) {
            $selectedFechaAttributeId = (int)$product_for_template['attributes'][192]['id_attribute'];
        }

        // Si hay atributo de fecha seleccionado, obtengo todas las combinaciones (ipa) que lo contienen
        $ipasConFecha = [];
        if ($selectedFechaAttributeId > 0) {
            $ipasConFecha = Db::getInstance()->executeS(
                'SELECT id_product_attribute
             FROM ' . _DB_PREFIX_ . 'product_attribute_combination
             WHERE id_attribute=' . (int)$selectedFechaAttributeId
            ) ?: [];
            $ipasConFecha = array_map(static function ($r) {
                return (int)$r['id_product_attribute'];
            }, $ipasConFecha);
        }

        foreach ($attributes_groups as $row) {
            $gid = (int)$row['id_attribute_group'];
            $aid = (int)$row['id_attribute'];
            $ipa = (int)$row['id_product_attribute'];

            // Colores
            if (((isset($row['is_color_group']) && $row['is_color_group'] && !empty($row['attribute_color'])))
                || file_exists(_PS_COL_IMG_DIR_ . $aid . '.jpg')
            ) {
                if (!isset($colors[$aid])) {
                    $colors[$aid] = [
                        'value' => $row['attribute_color'],
                        'name'  => $row['attribute_name'],
                        'attributes_quantity' => 0,
                    ];
                }
                $colors[$aid]['attributes_quantity'] += (int)$row['quantity'];
            }

            if (!isset($groups[$gid])) {
                $groups[$gid] = [
                    'group_name' => $row['group_name'],
                    'name'       => $row['public_group_name'],
                    'group_type' => $row['group_type'],
                    'default'    => -1,
                    'attributes' => [],
                    'attributes_quantity' => [],
                ];
            }

            // 3.c) Atributo dentro del grupo (sin pisar datos)
            if (!isset($groups[$gid]['attributes'][$aid])) {
                $groups[$gid]['attributes'][$aid] = [
                    'name'                 => $row['attribute_name'],
                    'html_color_code'      => $row['attribute_color'],
                    'quantity'             => 0,                  // se rellenará más abajo con el acumulado real
                    'product_attribute_ids' => [],                 // ✅ todas las combinaciones donde aparece
                    'texture'              => (@filemtime(_PS_COL_IMG_DIR_ . $aid . '.jpg')) ? _THEME_COL_DIR_ . $aid . '.jpg' : '',
                    'selected'             => false,
                ];
                $groups[$gid]['attributes_quantity'][$aid] = 0;
            }

            // 3.d) Acumular cantidades y combinaciones por atributo
            $groups[$gid]['attributes_quantity'][$aid] += (int)$row['quantity'];

            if (!isset($attributeStockSum[$aid])) {
                $attributeStockSum[$aid] = 0;
            }
            $attributeStockSum[$aid] += (int)$row['quantity']; // suma de stock de todas las ipas que contienen este atributo

            if (!isset($attributeCombinationIds[$aid])) {
                $attributeCombinationIds[$aid] = [];
            }
            $attributeCombinationIds[$aid][$ipa] = true; // set

            // 3.e) Default del grupo
            if (!empty($row['default_on']) && $groups[$gid]['default'] === -1) {
                $groups[$gid]['default'] = $aid;
            }

            // 3.f) Combos (estructura por ipa)
            if (!isset($this->combinations[$ipa])) {
                $this->combinations[$ipa] = [
                    'attributes_values' => [],
                    'attributes'        => [],
                ];
            }
            $this->combinations[$ipa]['attributes_values'][$gid] = $row['attribute_name'];
            $this->combinations[$ipa]['attributes'][] = $aid;
            $this->combinations[$ipa]['price'] = (float)$row['price'];

            if (!isset($combination_prices_set[$ipa])) {
                $combination_specific_price = null;
                Product::getPriceStatic(
                    (int)$this->product->id,
                    false,
                    $ipa,
                    6,
                    null,
                    false,
                    true,
                    1,
                    false,
                    null,
                    null,
                    null,
                    $combination_specific_price
                );
                $combination_prices_set[$ipa] = true;
                $this->combinations[$ipa]['specific_price'] = $combination_specific_price;
            }

            $this->combinations[$ipa]['ecotax'] = (float)$row['ecotax'];
            $this->combinations[$ipa]['weight'] = (float)$row['weight'];
            $this->combinations[$ipa]['quantity'] = (int)$row['quantity'];
            $this->combinations[$ipa]['reference'] = $row['reference'];
            $this->combinations[$ipa]['ean13'] = $row['ean13'];
            $this->combinations[$ipa]['mpn'] = $row['mpn'];
            $this->combinations[$ipa]['upc'] = $row['upc'];
            $this->combinations[$ipa]['isbn'] = $row['isbn'];
            $this->combinations[$ipa]['unit_impact'] = $row['unit_price_impact'];
            $this->combinations[$ipa]['minimal_quantity'] = $row['minimal_quantity'];

            if ($row['available_date'] != '0000-00-00' && Validate::isDate($row['available_date'])) {
                $this->combinations[$ipa]['available_date'] = $row['available_date'];
                $this->combinations[$ipa]['date_formatted'] = Tools::displayDate($row['available_date']);
            } else {
                $this->combinations[$ipa]['available_date'] = $this->combinations[$ipa]['date_formatted'] = '';
            }

            $this->combinations[$ipa]['id_image'] = !empty($combination_images[$ipa][0]['id_image'])
                ? (int)$combination_images[$ipa][0]['id_image']
                : -1;
        }

        // Volcar los ipas al atributo (todas las combinaciones en que aparece) y setear cantidad agregada
        foreach ($groups as $gid => &$group) {
            foreach ($group['attributes'] as $aid => &$attr) {
                $attr['product_attribute_ids'] = isset($attributeCombinationIds[$aid])
                    ? array_map('intval', array_keys($attributeCombinationIds[$aid]))
                    : [];

                // Cantidad agregada por atributo (suma de las combinaciones que lo incluyen).
                // Si prefieres el MÁXIMO en lugar de SUMA, cámbialo por un getValue MAX() por cada ipa asociado.
                $attr['quantity'] = isset($attributeStockSum[$aid]) ? (int)$attributeStockSum[$aid] : 0;

                // --- Reglas 192/193/194 (opcionales y CORREGIDAS) ---
                // Si hay un atributo de fecha seleccionado (192) y este atributo (de 193/194) coexiste con alguna ipa de $ipasConFecha,
                // podemos recalcular 'quantity' como el stock REAL de esas ipas conjuntas (SUMA).
                if (($gid === 193 || $gid === 194) && !empty($ipasConFecha) && !empty($attr['product_attribute_ids'])) {
                    $ipasInterseccion = array_values(array_intersect($attr['product_attribute_ids'], $ipasConFecha));
                    if (!empty($ipasInterseccion)) {
                        $sumQty = 0;
                        foreach ($ipasInterseccion as $ipaMatch) {
                            $sumQty += (int)Db::getInstance()->getValue(
                                'SELECT quantity FROM ' . _DB_PREFIX_ . 'stock_available
                             WHERE id_product_attribute=' . (int)$ipaMatch . ' AND id_product=' . (int)$this->product->id
                            );
                        }
                        $attr['quantity'] = $sumQty; // ✅ cantidad real combinada
                    }
                }
            }
            unset($attr);
        }
        unset($group);

        /* -------------------------------------------
     * 4) Seleccionados iniciales
     * ------------------------------------------- */
        foreach ($groups as $gid => &$group) {
            $selectedAid = isset($requested_groups[$gid]) ? (int)$requested_groups[$gid] : (int)$group['default'];
            foreach ($group['attributes'] as &$attr) {
                $attr['selected'] = false;
            }
            unset($attr);

            if ($selectedAid && isset($group['attributes'][$selectedAid])) {
                $group['attributes'][$selectedAid]['selected'] = true;
            } elseif (!empty($group['attributes'])) {
                $first = (int) array_key_first($group['attributes']);
                $group['attributes'][$first]['selected'] = true;
            }
        }
        unset($group);

        /* -------------------------------------------
     * 5) Wash estilo core (grupos precedentes)
     * ------------------------------------------- */
        $doWash = function (array &$groups) {
            $current_selected_attributes = [];
            $count = 0;

            foreach ($groups as &$group) {
                ++$count;
                if ($count > 1) {
                    // ipas que contienen todos los atributos seleccionados hasta ahora
                    $id_product_attributes = [0];
                    $query = 'SELECT pac.`id_product_attribute`
                          FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                          INNER JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = pac.id_product_attribute
                          WHERE pa.id_product = ' . (int)$this->product->id . '
                            AND pac.id_attribute IN (' . implode(',', array_map('intval', $current_selected_attributes)) . ')
                          GROUP BY pac.id_product_attribute
                          HAVING COUNT(*) = ' . (int)count($current_selected_attributes);
                    $results = Db::getInstance()->executeS($query) ?: [];
                    foreach ($results as $r) {
                        $id_product_attributes[] = (int)$r['id_product_attribute'];
                    }

                    // atributos permitidos del grupo actual, dados los PRECEDENTES
                    $sql = 'SELECT pac2.`id_attribute`
                        FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac2';
                    if (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock) && 0 == (int)Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) {
                        $sql .= ' INNER JOIN `' . _DB_PREFIX_ . 'stock_available` sa ON sa.id_product_attribute = pac2.id_product_attribute
                             WHERE sa.quantity > 0 AND ';
                        $sql .= 'sa.id_product = ' . (int)$this->product->id . ' AND ';
                    } else {
                        $sql .= ' WHERE ';
                    }
                    $sql .= 'pac2.`id_product_attribute` IN (' . implode(',', array_map('intval', $id_product_attributes)) . ')';
                    $id_attributes = Db::getInstance()->executeS($sql) ?: [];
                    $allowed = array_map(static function ($r) {
                        return (int)$r['id_attribute'];
                    }, $id_attributes);

                    foreach (array_keys($group['attributes']) as $key) {
                        if (!in_array((int)$key, $allowed, true)) {
                            unset($group['attributes'][$key], $group['attributes_quantity'][$key]);
                        }
                    }
                }

                // selected del grupo o primero
                $index = 0;
                $current_selected_attribute = 0;
                foreach ($group['attributes'] as $key => $attribute) {
                    if ($index === 0) {
                        $current_selected_attribute = (int)$key;
                    }
                    if (!empty($attribute['selected'])) {
                        $current_selected_attribute = (int)$key;
                        break;
                    }
                    ++$index;
                }
                if ($current_selected_attribute > 0) {
                    $current_selected_attributes[] = $current_selected_attribute;
                }
            }
            unset($group);
        };

        // Primer wash
        $doWash($groups);

        /* -------------------------------------------
     * 6) Auto-repair + re-wash si hace falta
     * ------------------------------------------- */
        $needsRefresh = false;
        foreach ($groups as $gid => &$group) {
            $selectedId = null;
            foreach ($group['attributes'] as $aid => $attr) {
                if (!empty($attr['selected'])) {
                    $selectedId = (int)$aid;
                    break;
                }
            }
            if ($selectedId === null || !isset($group['attributes'][$selectedId])) {
                if (!empty($group['attributes'])) {
                    foreach ($group['attributes'] as &$attr) {
                        $attr['selected'] = false;
                    }
                    unset($attr);
                    $firstKey = (int) array_key_first($group['attributes']);
                    $group['attributes'][$firstKey]['selected'] = true;
                    $needsRefresh = true;
                }
            }
        }
        unset($group);

        if ($needsRefresh) {
            $doWash($groups);
        }

        /* -------------------------------------------
     * 7) Filtrado por stock (opción de PrestaShop)
     * ------------------------------------------- */
        if (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock) && (int)Configuration::get('PS_DISP_UNAVAILABLE_ATTR') === 0) {
            foreach ($groups as &$group) {
                foreach ($group['attributes_quantity'] as $key => $qty) {
                    if ((int)$qty <= 0) {
                        unset($group['attributes'][$key]);
                    }
                }
            }
            unset($group);

            foreach ($colors as $key => $color) {
                if ($color['attributes_quantity'] <= 0) {
                    unset($colors[$key]);
                }
            }
        }

        /* -------------------------------------------
     * 8) lista para cada combinación
     * ------------------------------------------- */
        foreach ($this->combinations as $id_product_attribute => $comb) {
            $attribute_list = '';
            foreach ($comb['attributes'] as $id_attribute) {
                $attribute_list .= '\'' . (int)$id_attribute . '\',';
            }
            $this->combinations[$id_product_attribute]['list'] = rtrim($attribute_list, ',');
        }

        $this->context->smarty->assign([
            'groups' => $groups,
            'colors' => count($colors) ? $colors : false,
            'combinations' => $this->combinations,
            'combinationImages' => $combination_images,
        ]);
    }


    protected function getIdProductAttributeByGroupOrRequestOrDefault()
    {
        $requestedIdProductAttribute = (int) Tools::getValue('id_product_attribute');

        if ($requestedIdProductAttribute) {
            return $requestedIdProductAttribute;
        }

        return $this->getIdProductAttributeByGroup();
    }
    public function addProductCustomizationData($product_full)
    {

        if ($product_full['customizable']) {
            $customizationData = [
                'fields' => [],
            ];

            $customized_data = [];

            $already_customized = $this->context->cart->getProductCustomization(
                $product_full['id_product'],
                null,
                true
            );

            $id_customization = 0;
            foreach ($already_customized as $customization) {
                $id_customization = $customization['id_customization'];
                $customized_data[$customization['index']] = $customization;
            }

            $customization_fields = $this->product->getCustomizationFields($this->context->language->id);
            if (is_array($customization_fields)) {
                foreach ($customization_fields as $customization_field) {
                    // 'id_customization_field' maps to what is called 'index'
                    // in what Product::getProductCustomization() returns
                    $key = $customization_field['id_customization_field'];

                    $field['label'] = $customization_field['name'];
                    $field['id_customization_field'] = $customization_field['id_customization_field'];
                    $field['required'] = $customization_field['required'];

                    switch ($customization_field['type']) {
                        case Product::CUSTOMIZE_FILE:
                            $field['type'] = 'image';
                            $field['image'] = null;
                            $field['input_name'] = 'file' . $customization_field['id_customization_field'];

                            break;
                        case Product::CUSTOMIZE_TEXTFIELD:
                            $field['type'] = 'text';
                            $field['text'] = '';
                            $field['input_name'] = 'textField' . $customization_field['id_customization_field'];

                            break;
                        default:
                            $field['type'] = null;
                    }

                    if (array_key_exists($key, $customized_data)) {
                        $data = $customized_data[$key];
                        $field['is_customized'] = true;
                        switch ($customization_field['type']) {
                            case Product::CUSTOMIZE_FILE:
                                $imageRetriever = new ImageRetriever($this->context->link);
                                $field['image'] = $imageRetriever->getCustomizationImage(
                                    $data['value']
                                );
                                $field['remove_image_url'] = $this->context->link->getProductDeletePictureLink(
                                    $product_full,
                                    $customization_field['id_customization_field']
                                );

                                break;
                            case Product::CUSTOMIZE_TEXTFIELD:
                                $field['text'] = $data['value'];

                                break;
                        }
                    } else {
                        $field['is_customized'] = false;
                    }

                    $customizationData['fields'][] = $field;
                }
            }
            $product_full['customizations'] = $customizationData;
            $product_full['id_customization'] = $id_customization;
            $product_full['is_customizable'] = true;
        } else {
            $product_full['customizations'] = [
                'fields' => [],
            ];
            $product_full['id_customization'] = 0;
            $product_full['is_customizable'] = false;
        }

        return $product_full;
    }
    public function displayAjaxRefresh()
    {
        // 1) Producto con la selección inicial llegada por POST (group[...] / ipa / ipa viejo)
        $product = $this->getTemplateVarProduct();

        // 2) Poner producto en Smarty y recalcular grupos/combos (wash + autorepair)
        $this->context->smarty->assign('product', $product);
        $this->assignAttributesGroups($product);

        // 3) Tomar la selección FINAL (ya lavada) desde Smarty y resolver el IPA definitivo
        $finalGroups = isset($this->context->smarty->tpl_vars['groups']->value)
            ? $this->context->smarty->tpl_vars['groups']->value
            : [];

        $selectedAids = [];
        foreach ($finalGroups as $gid => $group) {
            if (!empty($group['attributes'])) {
                foreach ($group['attributes'] as $aid => $attr) {
                    if (!empty($attr['selected'])) {
                        $selectedAids[] = (int)$aid;
                        break;
                    }
                }
            }
        }

        $finalIpa = 0;
        if (!empty($selectedAids)) {
            $ids = implode(',', array_map('intval', $selectedAids));
            $hideOut = (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock)
                && (int)Configuration::get('PS_DISP_UNAVAILABLE_ATTR') === 0);

            // Exact match (todas las AIDs seleccionadas)
            $sqlExact = '
            SELECT pac.id_product_attribute
            FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
            INNER JOIN ' . _DB_PREFIX_ . 'product_attribute pa
                ON pa.id_product_attribute = pac.id_product_attribute
            ' . ($hideOut ? 'INNER JOIN ' . _DB_PREFIX_ . 'stock_available sa
                ON sa.id_product_attribute = pa.id_product_attribute AND sa.quantity > 0' : '') . '
            WHERE pa.id_product = ' . (int)$this->product->id . '
              AND pac.id_attribute IN (' . $ids . ')
            GROUP BY pac.id_product_attribute
            HAVING COUNT(DISTINCT pac.id_attribute) = ' . (int)count($selectedAids) . '
            ORDER BY MAX(pa.default_on) DESC, pac.id_product_attribute ASC';
            $finalIpa = (int)Db::getInstance()->getValue($sqlExact);

            // Mejor coincidencia (por si acaso)
            if ($finalIpa <= 0) {
                $sqlBest = '
                SELECT pac.id_product_attribute, COUNT(DISTINCT pac.id_attribute) AS matched
                FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                INNER JOIN ' . _DB_PREFIX_ . 'product_attribute pa
                    ON pa.id_product_attribute = pac.id_product_attribute
                ' . ($hideOut ? 'INNER JOIN ' . _DB_PREFIX_ . 'stock_available sa
                    ON sa.id_product_attribute = pa.id_product_attribute AND sa.quantity > 0' : '') . '
                WHERE pa.id_product = ' . (int)$this->product->id . '
                  AND pac.id_attribute IN (' . $ids . ')
                GROUP BY pac.id_product_attribute
                HAVING matched >= 1
                ORDER BY matched DESC, MAX(pa.default_on) DESC, pac.id_product_attribute ASC';
                $finalIpa = (int)Db::getInstance()->getValue($sqlBest);
            }
        }

        // 4) Si el IPA definitivo difiere, reconstruimos el producto con ese IPA (para que impacte YA)
        if ($finalIpa > 0 && (int)$product['id_product_attribute'] !== $finalIpa) {
            // Mapa de atributos (grupo->id_attribute) de ese IPA
            $rows = Db::getInstance()->executeS(
                'SELECT a.id_attribute, a.id_attribute_group
             FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
             INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute
             WHERE pac.id_product_attribute = ' . (int)$finalIpa
            ) ?: [];
            $selectedMap = [];
            foreach ($rows as $r) {
                $selectedMap[(int)$r['id_attribute_group']] = ['id_attribute' => (int)$r['id_attribute']];
            }

            // Reconstruir el array "product" presentado para ese IPA
            $productSettings = $this->getProductPresentationSettings();
            $presenter = $this->getProductPresenter();
            $extraContentFinder = new ProductExtraContentFinder();

            // Base presentada del objeto actual
            $base = $this->objectPresenter->present($this->product);
            $base['id_product'] = (int)$this->product->id;
            $base['id_product_attribute'] = (int)$finalIpa;
            $this->product->id_product_attribute = (int)$finalIpa;


            if (!empty($selectedMap)) {
                $base['attributes'] = $selectedMap;
            }

            // Recalcular mínimos/cantidades/ecotasa con IPA fijado
            $base['minimal_quantity'] = $this->getProductMinimalQuantity($base);
            $base['quantity_wanted']  = $this->getRequiredQuantity($base);
            $base['extraContent']     = $extraContentFinder->addParams(['product' => $this->product])->present();
            $base['ecotax']           = Tools::convertPrice($this->getProductEcotax($base), $this->context->currency, true, $this->context);
            $base['out_of_stock']     = $this->product->out_of_stock;

            // Expandir propiedades y presentar
            $product_full = Product::getProductProperties($this->context->language->id, $base, $this->context);
            $product_full = $this->addProductCustomizationData($product_full);

            $product_full['show_quantities'] = (bool)(
                Configuration::get('PS_DISPLAY_QTIES')
                && Configuration::get('PS_STOCK_MANAGEMENT')
                && $this->product->quantity > 0
                && $this->product->available_for_order
                && !Configuration::isCatalogMode()
            );
            $product_full['quantity_label'] = ($this->product->quantity > 1)
                ? $this->trans('Items', [], 'Shop.Theme.Catalog')
                : $this->trans('Item', [], 'Shop.Theme.Catalog');
            $product_full['quantity_discounts'] = $this->quantity_discounts;

            if (!empty($product_full['unit_price_ratio']) && $product_full['unit_price_ratio'] > 0) {
                $unitPrice = ($productSettings->include_taxes) ? $product_full['price'] : $product_full['price_tax_exc'];
                $product_full['unit_price'] = $unitPrice / $product_full['unit_price_ratio'];
            }

            $group_reduction = GroupReduction::getValueForProduct($this->product->id, (int)Group::getCurrent()->id);
            if ($group_reduction === false) {
                $group_reduction = Group::getReduction((int)$this->context->cookie->id_customer) / 100;
            }
            $product_full['customer_group_discount'] = $group_reduction;
            $product_full['title'] = $this->getProductPageTitle();
            $product_full['rounded_display_price'] = Tools::ps_round(
                $product_full['price'],
                Context::getContext()->currency->precision
            );

            $product_full['reference_to_display'] = $this->getCombinationReference((int)$finalIpa);



            $product = $presenter->present(
                $productSettings,
                $product_full,
                $this->context->language
            );

            // Reasignar el product final al Smarty (para que los parciales salgan consistentes)
            $this->context->smarty->assign('product', $product);
        }

        // 5) (Opcional) Sincronizar portada con la combinación actual
        $ipaSelected = (int)($product['id_product_attribute'] ?? 0);
        if ($ipaSelected > 0) {
            $combination_images = $this->product->getCombinationImages($this->context->language->id);
            if (isset($combination_images[$ipaSelected]) && !empty($combination_images[$ipaSelected])) {
                $id_image = (int)$combination_images[$ipaSelected][0]['id_image'];
                if (isset($this->context->smarty->tpl_vars['images']->value)) {
                    $product_images = $this->context->smarty->tpl_vars['images']->value;
                    $current_cover = null;
                    if (isset($this->context->smarty->tpl_vars['product']->value['images'])) {
                        foreach ($this->context->smarty->tpl_vars['product']->value['images'] as $img) {
                            if (!empty($img['cover'])) {
                                $current_cover = $img;
                                break;
                            }
                        }
                    }
                    if (!$current_cover && !empty($this->context->smarty->tpl_vars['product']->value['images'])) {
                        $tmp = array_values($this->context->smarty->tpl_vars['product']->value['images']);
                        $current_cover = $tmp ? $tmp[0] : null;
                    }
                    if (isset($product_images[$id_image])) {
                        if ($current_cover && isset($product_images[$current_cover['id_image']])) {
                            $product_images[$current_cover['id_image']]['cover'] = 0;
                        }
                        $product_images[$id_image]['cover'] = 1;
                        $cover = $product_images[$id_image];
                        $cover['id_image'] = (Configuration::get('PS_LEGACY_IMAGES') ? ($this->product->id . '-' . $id_image) : $id_image);
                        $cover['id_image_only'] = $id_image;

                        $this->context->smarty->assign('cover', $cover);
                        $this->context->smarty->assign('mainImage', $product_images[$id_image]);
                        $this->context->smarty->assign('images', $product_images);
                    }
                }
            }
        }

        // 6) Calcular mínima cantidad con el producto final
        $minimalProductQuantity = $this->getProductMinimalQuantity($product);

        // 7) Flags de vista
        $isPreview = ('1' === Tools::getValue('preview'));
        $isQuickView = ('1' === Tools::getValue('quickview'));

        // 8) Responder con los parciales renderizados
        ob_end_clean();
        header('Content-Type: application/json');

        if ($product['view'] == 'fitting') {
            $this->ajaxRender(json_encode([
                'product_variants' => $this->render('catalog/_partials/product-variants'),
                'product_add_to_cart' => $this->render('catalog/_partials/product-add-to-cart'),
                'view' => $product['view'],
                'id_product_attribute' => $product['id_product_attribute'],
                'id_customization' => $product['id_customization'],
            ]));
        } elseif ($product['view'] == 'demoday') {
            $this->ajaxRender(json_encode([
                'product_variants' => $this->render('catalog/_partials/product-variants'),
                'product_add_to_cart' => $this->render('catalog/_partials/product-add-to-cart'),
                'view' => $product['view'],
                'id_product_attribute' => $product['id_product_attribute'],
                'id_customization' => $product['id_customization'],
            ]));
        } else {

            $this->ajaxRender(json_encode([
                'product_prices' => $this->render('catalog/_partials/product-prices'),
                'product_cover_thumbnails' => $this->render('catalog/_partials/product-cover-thumbnails'),
                'product_details' => $this->render('catalog/_partials/product-details'),
                'product_variants' => $this->render('catalog/_partials/product-variants'),
                'product_discounts' => $this->render('catalog/_partials/product-discounts'),
                'product_add_to_cart' => $this->render('catalog/_partials/product-add-to-cart'),
                'product_additional_info' => $this->render('catalog/_partials/product-additional-info'),
                'product_flags' => $this->render('catalog/_partials/product-flags'),
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
                    $isPreview ? ['preview' => '1'] : []
                ),
                'product_minimal_quantity' => $minimalProductQuantity,
                'view' => $product['view'],
                'reference' => $this->getCombinationReference((int)$finalIpa),
                'id_product_attribute' => $product['id_product_attribute'],
                'id_customization' => $product['id_customization'],
                'product_title' => $this->getTemplateVarPage()['meta']['title'],
            ]));
        }
    }

    protected function assignPriceAndTax()
    {
        $id_customer = (isset($this->context->customer) ? (int) $this->context->customer->id : 0);
        $id_group = (int) Group::getCurrent()->id;
        $id_country = $id_customer ? (int) Customer::getCurrentCountry($id_customer) : (int) Tools::getCountry();

        $tax = (float) $this->product->getTaxesRate(new Address((int) $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        $this->context->smarty->assign('tax_rate', $tax);

        $product_price_with_tax = Product::getPriceStatic($this->product->id, true, null, 6);
        if (Product::$_taxCalculationMethod == PS_TAX_INC) {
            $product_price_with_tax = Tools::ps_round($product_price_with_tax, 2);
        }

        $id_currency = (int) $this->context->cookie->id_currency;
        $id_product = (int) $this->product->id;
        $id_product_attribute = $this->getIdProductAttributeByGroupOrRequestOrDefault(); //Tools::getValue('id_product_attribute', null);
        $id_shop = $this->context->shop->id;

        $quantity_discounts = SpecificPrice::getQuantityDiscounts($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_product_attribute, false, (int) $this->context->customer->id);
        foreach ($quantity_discounts as &$quantity_discount) {
            if ($quantity_discount['id_product_attribute']) {
                $combination = new Combination((int) $quantity_discount['id_product_attribute']);
                $attributes = $combination->getAttributesName((int) $this->context->language->id);
                foreach ($attributes as $attribute) {
                    $quantity_discount['attributes'] = $attribute['name'] . ' - ';
                }
                $quantity_discount['attributes'] = rtrim($quantity_discount['attributes'], ' - ');
            }
            if ((int) $quantity_discount['id_currency'] == 0 && $quantity_discount['reduction_type'] == 'amount') {
                $quantity_discount['reduction'] = Tools::convertPriceFull($quantity_discount['reduction'], null, Context::getContext()->currency);
            }
        }

        $product_price = $this->product->getPrice(Product::$_taxCalculationMethod == PS_TAX_INC, false);
        $this->quantity_discounts = $this->formatQuantityDiscounts($quantity_discounts, $product_price, (float) $tax, $this->product->ecotax);

        $this->context->smarty->assign(array(
            'no_tax' => Tax::excludeTaxeOption() || !$tax,
            'tax_enabled' => Configuration::get('PS_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC'),
            'customer_group_without_tax' => Group::getPriceDisplayMethod($this->context->customer->id_default_group),
        ));
    }
    protected function crawlDbForId($rew)
    {
        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $sql = new DbQuery();
        $sql->select('`id_product`');
        $sql->from('product_lang');
        $sql->where('`id_lang` = ' . (int)$id_lang . ' AND `id_shop` = ' . (int)$id_shop . ' AND `link_rewrite` = "' . pSQL($rew) . '"');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
    private function getIdProductAttributeByGroup()
    {
        $groups = Tools::getValue('group');
        if (empty($groups)) {
            return null;
        }

        return (int) Product::getIdProductAttributeByIdAttributes(
            $this->product->id,
            $groups,
            true
        );
    }

    // Crear función para extraer y convertir el "name" a timestamp
    private function extract_date($str)
    {
        // Separar por espacio y tomar la última parte: "dd/mm/yyyy"
        $parts = explode(' ', $str);
        $date = end($parts);
        $dateTime = DateTime::createFromFormat('d/m/Y', $date);
        return $dateTime ? $dateTime->getTimestamp() : 0;
    }

    /**
     * Devuelve el id_product_attribute a partir de los group[...] posteados.
     * - Si existe la combinación exacta, devuelve esa.
     * - Si no, devuelve la combinación que MÁS atributos comparta (>=1).
     * - Si no hay group o no encuentra nada, devuelve 0.
     */
    protected function resolveIpaFromPostedGroupsBestMatch(int $idProduct): int
    {
        $posted = Tools::getValue('group');
        if (!is_array($posted) || empty($posted)) {
            return 0;
        }

        $selectedAids = [];
        foreach ($posted as $gid => $aid) {
            $aid = (int)$aid;
            if ($aid > 0) {
                $selectedAids[] = $aid;
            }
        }
        if (empty($selectedAids)) {
            return 0;
        }

        $ids   = implode(',', array_map('intval', $selectedAids));
        $count = (int)count($selectedAids);

        $hideOut = (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock)
            && (int)Configuration::get('PS_DISP_UNAVAILABLE_ATTR') === 0);

        // 1) Match exacto (HAVING COUNT = N) — SIN LIMIT, getValue añade 1
        $sqlExact = '
        SELECT pac.id_product_attribute
        FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
        INNER JOIN ' . _DB_PREFIX_ . 'product_attribute pa
                ON pa.id_product_attribute = pac.id_product_attribute
        ' . ($hideOut ? 'INNER JOIN ' . _DB_PREFIX_ . 'stock_available sa
                ON sa.id_product_attribute = pa.id_product_attribute AND sa.quantity > 0' : '') . '
        WHERE pa.id_product = ' . (int)$idProduct . '
          AND pac.id_attribute IN (' . $ids . ')
        GROUP BY pac.id_product_attribute
        HAVING COUNT(DISTINCT pac.id_attribute) = ' . $count . '
        ORDER BY MAX(pa.default_on) DESC, pac.id_product_attribute ASC';

        $ipa = (int)Db::getInstance()->getValue($sqlExact);
        if ($ipa > 0) {
            return $ipa;
        }

        // 2) Mejor coincidencia (>=1 atributo) — SIN LIMIT, getValue añade 1
        $sqlBest = '
        SELECT pac.id_product_attribute, COUNT(DISTINCT pac.id_attribute) AS matched
        FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
        INNER JOIN ' . _DB_PREFIX_ . 'product_attribute pa
                ON pa.id_product_attribute = pac.id_product_attribute
        ' . ($hideOut ? 'INNER JOIN ' . _DB_PREFIX_ . 'stock_available sa
                ON sa.id_product_attribute = pa.id_product_attribute AND sa.quantity > 0' : '') . '
        WHERE pa.id_product = ' . (int)$idProduct . '
          AND pac.id_attribute IN (' . $ids . ')
        GROUP BY pac.id_product_attribute
        HAVING matched >= 1
        ORDER BY matched DESC, MAX(pa.default_on) DESC, pac.id_product_attribute ASC';

        $ipa = (int)Db::getInstance()->getValue($sqlBest);
        return $ipa > 0 ? $ipa : 0;
    }

    protected function getSelectedAttributesMapForIpa(int $ipa): array
    {
        if ($ipa <= 0) return [];
        $rows = Db::getInstance()->executeS(
            'SELECT a.id_attribute, a.id_attribute_group
         FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
         INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute
         WHERE pac.id_product_attribute = ' . (int)$ipa
        ) ?: [];

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['id_attribute_group']] = ['id_attribute' => (int)$r['id_attribute']];
        }
        return $map;
    }


    public function getTemplateVarProduct()
    {
        $productSettings = $this->getProductPresentationSettings();
        $extraContentFinder = new ProductExtraContentFinder();

        $product = $this->objectPresenter->present($this->product);
        $product['id_product']   = (int)$this->product->id;
        $product['out_of_stock'] = (int)$this->product->out_of_stock;
        $product['new']          = (int)$this->product->new;

        // 1) Intentar resolver IPA desde group[...] (mejor coincidencia)
        $ipa = $this->resolveIpaFromPostedGroupsBestMatch((int)$this->product->id);

        // 2) Si no vino por group, tomar el ipa posteado o fallback
        if ($ipa <= 0) {
            $ipa = (int)Tools::getValue('id_product_attribute', 0);
            if ($ipa <= 0) {
                $ipa = (int)Tools::getValue('ipa', 0);
            }
        }
        if ($ipa <= 0) {
            $ipa = (int)$this->getIdProductAttributeByGroupOrRequestOrDefault();
        }

        // 3) Validar IPA SOLO si es > 0 (SIN LIMIT 1, getValue lo pone solo)
        if ($ipa > 0) {
            $validIpa = (int)Db::getInstance()->getValue(
                'SELECT pa.id_product_attribute
             FROM ' . _DB_PREFIX_ . 'product_attribute pa
             WHERE pa.id_product=' . (int)$this->product->id . '
               AND pa.id_product_attribute=' . (int)$ipa
            );
            if ($validIpa <= 0) {
                $ipa = (int)$this->getIdProductAttributeByGroupOrRequestOrDefault();
            }
        }

        // 4) Fijar IPA y mapa de atributos para esa IPA
        $product['id_product_attribute'] = (int)$ipa;
        $this->product->id_product_attribute = (int)$ipa;

        $selectedMap = $this->getSelectedAttributesMapForIpa($ipa);
        if (!empty($selectedMap)) {
            $product['attributes'] = $selectedMap;
        }

        // 5) Recalcular mínimos/cantidades
        $product['minimal_quantity'] = $this->getProductMinimalQuantity($product);
        $product['quantity_wanted']  = $this->getRequiredQuantity($product);

        // 6) Extra, ecotasa, propiedades y presentación
        $product['extraContent'] = $extraContentFinder->addParams(['product' => $this->product])->present();
        $product['ecotax'] = Tools::convertPrice($this->getProductEcotax($product), $this->context->currency, true, $this->context);

        $product_full = Product::getProductProperties($this->context->language->id, $product, $this->context);
        $product_full = $this->addProductCustomizationData($product_full);

        $product_full['show_quantities'] = (bool)(
            Configuration::get('PS_DISPLAY_QTIES')
            && Configuration::get('PS_STOCK_MANAGEMENT')
            && $this->product->quantity > 0
            && $this->product->available_for_order
            && !Configuration::isCatalogMode()
        );
        $product_full['quantity_label'] = ($this->product->quantity > 1)
            ? $this->trans('Items', [], 'Shop.Theme.Catalog')
            : $this->trans('Item', [], 'Shop.Theme.Catalog');
        $product_full['quantity_discounts'] = $this->quantity_discounts;

        if (!empty($product_full['unit_price_ratio']) && $product_full['unit_price_ratio'] > 0) {
            $unitPrice = ($productSettings->include_taxes) ? $product_full['price'] : $product_full['price_tax_exc'];
            $product_full['unit_price'] = $unitPrice / $product_full['unit_price_ratio'];
        }

        $group_reduction = GroupReduction::getValueForProduct($this->product->id, (int)Group::getCurrent()->id);
        if ($group_reduction === false) {
            $group_reduction = Group::getReduction((int)$this->context->cookie->id_customer) / 100;
        }
        $product_full['customer_group_discount'] = $group_reduction;
        $product_full['title'] = $this->getProductPageTitle();
        $product_full['rounded_display_price'] = Tools::ps_round(
            $product_full['price'],
            Context::getContext()->currency->precision
        );

        $presenter = $this->getProductPresenter();

        return $presenter->present(
            $productSettings,
            $product_full,
            $this->context->language
        );
    }

    private function getProductPageTitle(array $meta = null)
    {
        $title = $this->product->name;
        if (isset($meta['title'])) {
            $title = $meta['title'];
        } elseif (isset($meta['meta_title'])) {
            $title = $meta['meta_title'];
        }
        if (!Configuration::get('PS_PRODUCT_ATTRIBUTES_IN_TITLE')) {
            return $title;
        }

        $idProductAttribute = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        if ($idProductAttribute) {
            $attributes = $this->product->getAttributeCombinationsById($idProductAttribute, $this->context->language->id);
            if (is_array($attributes) && count($attributes) > 0) {
                foreach ($attributes as $attribute) {
                    $title .= ' ' . $attribute['group_name'] . ' ' . $attribute['attribute_name'];
                }
            }
        }

        return $title;
    }

    /**
     * Obtiene la referencia de una combinación dado su ID.
     *
     * @param int $id_product_attribute
     * @return string|null  Devuelve la referencia o null si no existe.
     */
    function getCombinationReference($id_product_attribute)
    {
        if (!Validate::isUnsignedId($id_product_attribute)) {
            return null;
        }

        // Cargar la combinación por su ID
        $combination = new Combination((int)$id_product_attribute);

        // Verificar si existe
        if (Validate::isLoadedObject($combination)) {
            return $combination->reference;
        }

        return null;
    }
}
