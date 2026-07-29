<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

class AlsernetRelatedSearch
{
    /* ====================== BÚSQUEDA POR REFERENCIA ====================== */

    public static function getProductByReferenceDetailed($reference)
    {
        $reference = trim((string) $reference);
        if ($reference === '') {
            return ['id_product' => 0, 'id_product_attribute' => 0];
        }

        // Combinación
        $row = Db::getInstance()->executeS(
            'SELECT pa.id_product, pa.id_product_attribute
             FROM '._DB_PREFIX_.'product_attribute pa
             WHERE pa.reference = "'.pSQL($reference).'"
             LIMIT 1'
        );
        if (count($row) != 0) {
            return [
                'id_product' => (int) $row[0]['id_product'],
                'id_product_attribute' => (int) $row[0]['id_product_attribute'],
            ];
        }

        // Producto simple
        $id_product = Db::getInstance()->executeS(
            'SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = "'.pSQL($reference).'"'
        );
        if (count($id_product) != 0) {
            return ['id_product' => (int) $id_product[0]['id_product'], 'id_product_attribute' => 0];
        }

        return ['id_product' => 0, 'id_product_attribute' => 0];
    }

    /* ====================== MAPEO LANG → COUNTRY ====================== */

    public static function getIdCountryByLang($id_lang)
    {
        $sql = 'SELECT ac.id_country
                FROM '._DB_PREFIX_.'lang al
                LEFT JOIN '._DB_PREFIX_.'country ac ON ac.iso_code COLLATE utf8mb4_unicode_ci = al.iso_code COLLATE utf8mb4_unicode_ci
                WHERE al.id_lang='.(int) $id_lang.' AND ac.active=1';

        return (int) Db::getInstance()->getValue($sql);
    }

    /* ====================== PRECIO POR PAÍS (specific_price) ====================== */

    public static function getPriceByCountry($id_product, $id_product_attribute, $id_country)
    {
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;
        $id_country = (int) $id_country;

        $id_shop = (int) Context::getContext()->shop->id;
        $country = new Country($id_country);
        if (! Validate::isLoadedObject($country)) {
            $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        }

        $id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $id_group = (int) Configuration::get('PS_UNIDENTIFIED_GROUP');
        $id_customer = 0;

        $specific_price = null;
        $price = Product::priceCalculation(
            $id_shop,
            $id_product,
            $id_product_attribute,
            $id_country,
            0,
            '',
            $id_currency,
            $id_group,
            1,
            true,
            6,
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

        if (! $price || $price < 0) {
            $price = Product::getPriceStatic($id_product, true, $id_product_attribute);
        }

        return (float) $price;
    }

    /* ====================== IMAGEN COVER (large_default) ====================== */

    public static function getCoverImageUrl($id_product, $id_shop)
    {
        $cover = Image::getCover($id_product);
        if (! is_array($cover) || empty($cover['id_image'])) {
            return '';
        }
        $image = new Image($cover['id_image']);
        $link = Context::getContext()->link;

        $url = $link->getImageLink(null, $image->getExistingImgPath(), 'large_default');
        if (! $url || strpos($url, 'default') !== false) {
            $url = $link->getImageLink(null, $image->getExistingImgPath(), 'home_default');
        }
        if (! $url) {
            $url = _PS_IMG_.'p/es-default-large_default.jpg';
        }

        return $url;
    }

    /* ====================== DATOS BASE (con precio correcto y atributos) ====================== */

    public static function getBaseDataFromProduct($id_product, $id_lang, $id_shop, $id_product_attribute = 0)
    {
        $p = new Product($id_product, true, $id_lang, $id_shop);
        if (! Validate::isLoadedObject($p)) {
            return [];
        }

        // Atributos del producto o de la combinación exacta
        $attr_texts = [];

        if ($id_product_attribute) {
            foreach ($p->getAttributeCombinations($id_lang) as $c) {
                if ((int) $c['id_product_attribute'] === (int) $id_product_attribute) {
                    if (! empty($c['group_name']) && ! empty($c['attribute_name'])) {
                        $attr_texts[] = trim($c['group_name']).': '.trim($c['attribute_name']);
                    }
                }
            }
            $attr_texts = array_values(array_unique($attr_texts));
        } else {
            foreach ($p->getAttributesResume($id_lang) as $r) {
                if (! empty($r['group_name']) && ! empty($r['attribute_name'])) {
                    $attr_texts[] = $r['group_name'].': '.$r['attribute_name'];
                }
            }
        }

        // Precio por país para base:
        // - Si llega IPA: usar ese IPA
        // - Si no, usar IPA Por defecto del producto
        // - Si no hay IPA Por defecto, usar el mínimo precio entre combinaciones
        $id_country = self::getIdCountryByLang($id_lang);
        $ipa_for_price = (int) $id_product_attribute;

        if ($ipa_for_price <= 0) {
            $ipa_default = (int) Product::getDefaultAttribute($id_product);
            if ($ipa_default > 0) {
                $ipa_for_price = $ipa_default;
            } else {
                // No hay IPA Por defecto: buscar mínimo entre combinaciones
                $minPrice = null;
                foreach ($p->getAttributeCombinations($id_lang) as $c) {
                    $ipa_tmp = (int) $c['id_product_attribute'];
                    $price_tmp = self::getPriceByCountry($id_product, $ipa_tmp, $id_country);
                    if ($minPrice === null || $price_tmp < $minPrice) {
                        $minPrice = $price_tmp;
                    }
                }
                $price = ($minPrice !== null) ? (float) $minPrice : (float) self::getPriceByCountry($id_product, 0, $id_country);

                return [
                    'name' => $p->name,
                    'id_category' => (int) $p->id_category_default,
                    'category' => (new Category($p->id_category_default, $id_lang))->name ?? '',
                    'id_brand' => (int) $p->id_manufacturer,
                    'brand' => $p->manufacturer_name,
                    'attributes' => $attr_texts,
                    'price' => (float) $price,
                    'image' => str_replace('maxi.preproduccion.', '', self::getCoverImageUrl($id_product, $id_shop)),
                ];
            }
        }

        $price = self::getPriceByCountry($id_product, $ipa_for_price, $id_country);

        return [
            'name' => $p->name,
            'id_category' => (int) $p->id_category_default,
            'category' => (new Category($p->id_category_default, $id_lang))->name ?? '',
            'id_brand' => (int) $p->id_manufacturer,
            'brand' => $p->manufacturer_name,
            'attributes' => $attr_texts,
            'price' => (float) $price,
            'image' => str_replace('maxi.preproduccion.', '', self::getCoverImageUrl($id_product, $id_shop)),
        ];
    }

    /* ====================== COMBINACIONES DETALLADAS ====================== */

    public static function getProductCombinationsDetailed($id_product, $id_lang)
    {
        $p = new Product($id_product, true, $id_lang);
        if (! Validate::isLoadedObject($p)) {
            return [];
        }

        $combs = $p->getAttributeCombinations($id_lang);

        // Si NO hay combinaciones → devolver una pseudo–combinación para producto simple
        if (! $combs || ! is_array($combs) || ! count($combs)) {
            // referencia del producto simple
            $prodRef = Db::getInstance()->getValue(
                'SELECT reference FROM '._DB_PREFIX_.'product WHERE id_product='.(int) $id_product
            );

            // stock del producto simple (ipa=0)
            $stock = (int) StockAvailable::getQuantityAvailableByProduct($id_product, 0);

            // precio vigente por país (se resuelve en base al lang)
            $id_country = self::getIdCountryByLang($id_lang);
            $price = (float) self::getPriceByCountry($id_product, 0, (int) $id_country);

            return [[
                'id_product_attribute' => 0,
                'reference' => (string) $prodRef,
                'price' => $price,
                'stock' => $stock,
                'pairs' => [],     // no hay atributos en producto simple
                'active' => false,  // bandera visual, no aplica aquí
            ]];
        }

        // ------ flujo habitual para productos con combinaciones ------
        $map = [];
        foreach ($combs as $c) {
            $ipa = (int) $c['id_product_attribute'];
            if (! isset($map[$ipa])) {
                $map[$ipa] = [
                    'id_product_attribute' => $ipa,
                    'reference' => '',
                    'price' => 0.0,
                    'stock' => 0,
                    'pairs' => [],
                    'active' => false,
                ];
            }
            if (! empty($c['group_name']) && ! empty($c['attribute_name'])) {
                $map[$ipa]['pairs'][] = trim($c['group_name']).': '.trim($c['attribute_name']);
            }
        }

        // referencias por combinación
        $rows = Db::getInstance()->executeS(
            'SELECT id_product_attribute, reference
         FROM '._DB_PREFIX_.'product_attribute
         WHERE id_product='.(int) $id_product
        );
        foreach ($rows as $r) {
            $ipa = (int) $r['id_product_attribute'];
            if (isset($map[$ipa])) {
                $map[$ipa]['reference'] = (string) $r['reference'];
            }
        }

        // stock y precio por IPA (precio por país en base al lang)
        $id_country = self::getIdCountryByLang($id_lang);
        foreach ($map as &$o) {
            $o['stock'] = (int) StockAvailable::getQuantityAvailableByProduct($id_product, $o['id_product_attribute']);
            $o['price'] = (float) self::getPriceByCountry($id_product, $o['id_product_attribute'], (int) $id_country);
        }
        unset($o);

        return array_values($map);
    }

    /* ====================== BÚSQUEDA PRINCIPAL ====================== */

    public static function search(array $filters, $id_lang, $id_shop, $page = 1, $page_size = 20)
    {
        $ctx = Context::getContext();
        $link = $ctx->link;

        $id_country = (int) self::getIdCountryByLang($id_lang) ?: (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $id_category = (int) $filters['id_category'];
        $exclude_id = (int) $filters['exclude_id'];
        $price_from = $filters['price_from'] !== '' ? (float) $filters['price_from'] : '';
        $price_to = $filters['price_to'] !== '' ? (float) $filters['price_to'] : '';

        // Limpiar lista de marcas (descartar vacíos/0)
        $brand_list = [];
        if (! empty($filters['id_brand_list']) && is_array($filters['id_brand_list'])) {
            foreach ($filters['id_brand_list'] as $b) {
                $b = (int) $b;
                if ($b > 0) {
                    $brand_list[] = $b;
                }
            }
        }

        // Candidatos por categoría (activos)
        $sql = 'SELECT p.id_product, p.id_manufacturer
            FROM '._DB_PREFIX_.'category_product cp
            INNER JOIN '._DB_PREFIX_.'product p ON (p.id_product = cp.id_product AND p.active = 1)
            WHERE cp.id_category='.(int) $id_category.' AND p.id_product!='.(int) $exclude_id;
        $candidates = Db::getInstance()->executeS($sql);

        $items = [];
        foreach ($candidates as $row) {
            $idp = (int) $row['id_product'];
            $id_manufacturer = (int) $row['id_manufacturer'];

            // Filtro por marcas seleccionadas (si hay)
            if (! empty($brand_list) && ! in_array($id_manufacturer, $brand_list, true)) {
                continue;
            }

            $p = new Product($idp, true, $id_lang, $id_shop);
            if (! Validate::isLoadedObject($p)) {
                continue;
            }

            // Combinaciones + stock + precio vigente por país
            $combinations = self::getProductCombinationsDetailed($idp, $id_lang);

            // Solo combinaciones con stock > 0 (compat PHP < 7.4)
            $combinations = array_filter($combinations, function ($c) {
                return (int) $c['stock'] > 0;
            });

            $combinations = array_values($combinations);

            if (empty($combinations)) {
                continue;
            }

            // Precio mínimo real entre combinaciones con stock (para rango)
            $prices = [];
            foreach ($combinations as $c) {
                $prices[] = (float) $c['price'];
            }
            $min_price = ! empty($prices) ? min($prices) : 0;

            if ($price_from !== '' && $min_price < (float) $price_from) {
                continue;
            }
            if ($price_to !== '' && $min_price > (float) $price_to) {
                continue;
            }

            // Imagen y URL
            $imageUrl = self::getCoverImageUrl($idp, $id_shop);
            $productUrl = $link->getProductLink($idp, null, null, null, $id_lang, $id_shop);

            $items[] = [
                'id_product' => $idp,
                'name' => $p->name,
                'brand' => $p->manufacturer_name,
                'image' => str_replace('maxi.preproduccion.', '', $imageUrl),
                'url' => $productUrl,
                'combinations' => $combinations,
            ];
        }

        // Total sin paginación
        $total = count($items);

        // Marcas disponibles en los resultados (para poblar el select)
        $brand_map = [];
        foreach ($items as $it) {
            if (! empty($it['brand'])) {
                $brand_map[$it['brand']] = true;
            }
        }

        // Incluir SIEMPRE la marca del producto base
        if ($exclude_id > 0) {
            $baseProd = new Product($exclude_id, true, $id_lang, $id_shop);
            if (Validate::isLoadedObject($baseProd) && ! empty($baseProd->manufacturer_name)) {
                $brand_map[$baseProd->manufacturer_name] = true;
            }
        }

        $brand_options = [];
        foreach (array_keys($brand_map) as $brandName) {
            $id_man = Db::getInstance()->getValue(
                'SELECT id_manufacturer FROM '._DB_PREFIX_.'manufacturer WHERE name="'.pSQL($brandName).'"'
            );
            if ($id_man) {
                $brand_options[] = ['id_manufacturer' => (int) $id_man, 'name' => $brandName];
            }
        }

        usort($brand_options, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return [
            'total' => $total,
            'items' => $items,          // ← ya NO se pagina
            'brand_options' => $brand_options,  // ← marcas solo de lo visible + base
        ];
    }
}
