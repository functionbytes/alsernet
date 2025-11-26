<?php


if (!defined('_PS_VERSION_')) {

    exit;
}

class Wishlist extends ObjectModel
{
    public $id;

    public $id_customer;

    public $token;

    public $name;

    public $date_add;

    public $date_upd;

    public $id_shop;

    public $id_shop_group;

    public $default;

    public static $definition = array(
        'table' => 'leofeature_wishlist',
        'primary' => 'id_wishlist',
        'fields' => array(
            'id_customer' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'token' =>            array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'name' =>            array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'date_add' =>        array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>        array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'id_shop' =>        array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop_group' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'default' => array('type' => self::TYPE_BOOL, 'validate' => 'isUnsignedId'),
        )
    );


    public static function createNewWishlist($id_customer, $id_lang = null, $name = 'My Wishlist', $is_default = false)
    {
        if (!Validate::isUnsignedId($id_customer)) {
            return false;
        }

        $context = Context::getContext();

        if ($id_lang === null) {
            $id_lang = (int)$context->language->id;
        }

        $date_now = date('Y-m-d H:i:s');
        $id_shop = (int)Shop::getContextShopID();
        $id_shop_group = (int)Shop::getContextShopGroupID();
        $token = uniqid(rand(), true);
        $counter = 0;

        $insert = Db::getInstance()->execute('
        INSERT INTO `'._DB_PREFIX_.'leofeature_wishlist` 
        (`id_customer`, `id_lang`, `date_add`, `date_upd`, `default`, `id_shop`, `id_shop_group`, `counter`, `name`, `token`)
        VALUES (
            '.(int)$id_customer.',
            '.(int)$id_lang.',
            "'.pSQL($date_now).'",
            "'.pSQL($date_now).'",
            '.($is_default ? 1 : 0).',
            '.(int)$id_shop.',
            '.(int)$id_shop_group.',
            '.(int)$counter.',
            "'.pSQL($name).'",
            "'.pSQL($token).'"
        )
    ');

        if ($insert) {
            $id_wishlist = Db::getInstance()->Insert_ID();

            return Db::getInstance()->getRow('
            SELECT *
            FROM `'._DB_PREFIX_.'leofeature_wishlist`
            WHERE `id_wishlist` = '.(int)$id_wishlist
            );
        }

        return false;
    }

    public function delete()
    {
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'leofeature_wishlist_product` WHERE `id_wishlist` = '.(int)($this->id));
        if ($this->default) {
            $result = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'leofeature_wishlist` WHERE `id_customer` = '.(int)$this->id_customer.' and `id_wishlist` != '.(int)$this->id.' LIMIT 1');
            foreach ($result as $res) {
                Db::getInstance()->update('wishlist', array('default' => '1'), 'id_wishlist = '.(int)$res['id_wishlist']);
            }
        }
        if (isset($this->context->cookie->id_wishlist)) {
            unset($this->context->cookie->id_wishlist);
        }

        return (parent::delete());
    }

    public static function inCounter($id_wishlist)
    {
        if (!Validate::isUnsignedId($id_wishlist)) {
            die(Tools::displayError());
        }

        $result = Db::getInstance()->getRow('
            SELECT `counter`
            FROM `'._DB_PREFIX_.'leofeature_wishlist`
            WHERE `id_wishlist` = '.(int)$id_wishlist);
        
        if ($result == false || !count($result) || empty($result) === true) {
            return (false);
        }

        return Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'leofeature_wishlist` SET
            `counter` = '.(int)($result['counter'] + 1).'
            WHERE `id_wishlist` = '.(int)$id_wishlist);
    }

    public static function outCounter($id_wishlist)
    {
        if (!Validate::isUnsignedId($id_wishlist)) {
            die(Tools::displayError());
        }
        
        $result = Db::getInstance()->getRow('
            SELECT `counter`
            FROM `'._DB_PREFIX_.'leofeature_wishlist`
            WHERE `id_wishlist` = '.(int)$id_wishlist);
        
        if ($result == false || !count($result) || empty($result) === true) {
            return (false);
        }

        return Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'leofeature_wishlist` SET
            `counter` = '.(int)($result['counter'] - 1).'
            WHERE `id_wishlist` = '.(int)$id_wishlist);
    }

    public static function isExistsByNameForUser($name)
    {
        if (Shop::getContextShopID()) {
            $shop_restriction = 'AND id_shop = '.(int)Shop::getContextShopID();
        } elseif (Shop::getContextShopGroupID()) {
            $shop_restriction = 'AND id_shop_group = '.(int)Shop::getContextShopGroupID();
        } else {
            $shop_restriction = '';
        }

        $context = Context::getContext();
        return Db::getInstance()->getValue('SELECT COUNT(*) AS total
            FROM `'._DB_PREFIX_.'leofeature_wishlist`
            WHERE `name` = \''.pSQL($name).'\'
                AND `id_customer` = '.(int)$context->customer->id.'
                '.$shop_restriction);
    }
   
    public static function exists($id_wishlist, $id_customer, $return = false)
    {
        if (!Validate::isUnsignedId($id_wishlist) or !Validate::isUnsignedId($id_customer)) {
            die(Tools::displayError());
        }
        $result = Db::getInstance()->getRow('
            SELECT `id_wishlist`, `name`, `token`
              FROM `'._DB_PREFIX_.'leofeature_wishlist`
            WHERE `id_wishlist` = '.(int)($id_wishlist).'
            AND `id_customer` = '.(int)($id_customer).'
            AND `id_shop` = '.(int)Context::getContext()->shop->id);
        if (empty($result) === false and $result != false and sizeof($result)) {
            if ($return === false) {
                return (true);
            } else {
                return ($result);
            }
        }
        return (false);
    }

    public static  function getSimpleProductByIdValidateProduct($id_customer, $id_product, $id_lang, $id_shop)
    {
    
        $wishlists = Db::getInstance()->executeS('
            SELECT w.`id_wishlist`
            FROM `'._DB_PREFIX_.'leofeature_wishlist` w
            WHERE w.`id_customer` = '.(int)$id_customer.' 
            AND w.`id_lang` = '.(int)$id_lang);
        

        // Si no hay wishlists, devolver false
        if (empty($wishlists)) {
            return false;
        }


        // Recorrer las wishlists y buscar el producto
        foreach ($wishlists as $wishlists_val) {
            $product = Db::getInstance()->getRow('
                SELECT *
                FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
                JOIN `'._DB_PREFIX_.'leofeature_wishlist` w ON w.`id_wishlist` = wp.`id_wishlist`
                WHERE wp.`id_wishlist` = '.(int)$wishlists_val['id_wishlist'].'
                AND wp.`id_product` = '.(int)$id_product.'
                AND w.`id_lang` = '.(int)$id_lang.'
            ');

          
            
            // Si el producto se encuentra en alguna wishlist, devolver true
            if (!empty($product)) {
                
                return $product['id_product_attribute'];
            }
        }


        // Si no se encuentra en ninguna wishlist, devolver false
        return false;
    }

    public static  function getSimpleProductByIdValidate($id_customer, $id_product, $id_product_attribute, $id_lang, $id_shop)
    {
    
        $wishlists = Db::getInstance()->executeS('
            SELECT w.`id_wishlist`
            FROM `'._DB_PREFIX_.'leofeature_wishlist` w
            WHERE w.`id_customer` = '.(int)$id_customer.' 
            AND w.`id_lang` = '.(int)$id_lang);
        

        // Si no hay wishlists, devolver false
        if (empty($wishlists)) {
            return false;
        }

        // Recorrer las wishlists y buscar el producto
        foreach ($wishlists as $wishlists_val) {
            $product = Db::getInstance()->getRow('
            SELECT wp.`id_product`
            FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
            WHERE wp.`id_wishlist` = '.(int)$wishlists_val['id_wishlist'].'
            AND wp.`id_product` = '.(int)$id_product.'
            AND wp.`id_product_attribute` = '.(int)$id_product_attribute);

            // Si el producto se encuentra en alguna wishlist, devolver true
            if (!empty($product)) {
                return true;
            }
        }

        return false;
    }
   
    public static function getCustomers()
    {
        $cache_id = 'WhishList::getCustomers';
        if (!Cache::isStored($cache_id)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                SELECT c.`id_customer`, c.`firstname`, c.`lastname`
                  FROM `'._DB_PREFIX_.'leofeature_wishlist` w
                INNER JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = w.`id_customer`
                ORDER BY c.`firstname` ASC');
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }
   
    public static function getByToken($token)
    {
        if (!Validate::isMessage($token)) {
            die(Tools::displayError());
        }
        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT w.`id_wishlist`, w.`name`, w.`id_customer`, c.`firstname`, c.`lastname`
              FROM `'._DB_PREFIX_.'leofeature_wishlist` w
            INNER JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = w.`id_customer`
            WHERE `token` = \''.pSQL($token).'\''));
    }

    public static function getByWishlist()
    {
       return (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT *   FROM `'._DB_PREFIX_.'leofeature_wishlist`'));
    }

    public static function deleteByWishlist($wishlist)
    {
       return Db::getInstance()->execute('
       DELETE FROM `'._DB_PREFIX_.'leofeature_wishlist`
       WHERE `id_wishlist` = '.(int)($wishlist));
    }

    public static function getByIdCustomer($id_customer,$id_lang)
    {

        $cache_id = 'WhishList::getByIdCustomer_'.(int)$id_customer.'-'.(int)Shop::getContextShopID().'-'.(int)Shop::getContextShopGroupID();
        if (!Cache::isStored($cache_id)) {
            $result = Db::getInstance()->executeS('
                SELECT w.`id_wishlist`, w.`name`, w.`token`, w.`date_add`, w.`date_upd`, w.`counter`, w.`default`, w.`id_lang`
                FROM `'._DB_PREFIX_.'leofeature_wishlist` w
                WHERE w.`id_customer` = '.$id_customer.'
                AND w.`id_lang` = '.$id_lang.'
                '.$shop_restriction.'
                ORDER BY w.`name` ASC'
            );
            Cache::store($cache_id, $result);
        }

        return Cache::retrieve($cache_id);
    }

    public static function isProductInAnyWishlist($id_customer, $id_product, $id_product_attribute = 0)
    {
        if (!Validate::isUnsignedId($id_customer) || !Validate::isUnsignedId($id_product)) {
            return false;
        }

        if (Shop::getContextShopID()) {
            $shop_restriction = 'AND w.id_shop = '.(int)Shop::getContextShopID();
        } elseif (Shop::getContextShopGroupID()) {
            $shop_restriction = 'AND w.id_shop_group = '.(int)Shop::getContextShopGroupID();
        } else {
            $shop_restriction = '';
        }

        // Búsqueda en todas las wishlists del cliente (sin filtro de idioma)
        $sql = 'SELECT wp.id_wishlist, wp.id_product, wp.id_product_attribute, wp.quantity, w.name as wishlist_name, w.date_upd, w.id_lang
            FROM `' . _DB_PREFIX_ . 'leofeature_wishlist_product` wp
            INNER JOIN `' . _DB_PREFIX_ . 'leofeature_wishlist` w ON (wp.id_wishlist = w.id_wishlist)
            WHERE w.id_customer = ' . (int)$id_customer . '
            AND wp.id_product = ' . (int)$id_product;

        // Si se especifica un atributo, incluirlo en la búsqueda
        if ($id_product_attribute > 0) {
            $sql .= ' AND wp.id_product_attribute = ' . (int)$id_product_attribute;
        }

        $sql .= ' '.$shop_restriction.'
              ORDER BY w.default DESC, w.date_upd DESC, w.id_wishlist DESC 
              LIMIT 1';

        $result = Db::getInstance()->getRow($sql);

        return !empty($result) ? $result : false;
    }

    public static function getProductByIdCustomer($id_wishlist, $id_customer, $id_lang, $id_product = null, $quantity = false)
    {
        if (!Validate::isUnsignedId($id_customer) or !Validate::isUnsignedId($id_lang) or !Validate::isUnsignedId($id_wishlist)) {
            die(Tools::displayError());
        }
        
        $products = Db::getInstance()->executeS('
            SELECT wp.`id_product`, wp.`quantity`, p.`quantity` AS product_quantity, pl.`name`, wp.`id_product_attribute`, wp.`priority`, pl.link_rewrite, cl.link_rewrite AS category_rewrite
            FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
            LEFT JOIN `'._DB_PREFIX_.'product` p ON p.`id_product` = wp.`id_product`
            '.Shop::addSqlAssociation('product', 'p').'
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.`id_product` = wp.`id_product`'.Shop::addSqlRestrictionOnLang('pl').'
            LEFT JOIN `'._DB_PREFIX_.'leofeature_wishlist` w ON w.`id_wishlist` = wp.`id_wishlist`
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON cl.`id_category` = product_shop.`id_category_default` and cl.id_lang='.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').'
            WHERE w.`id_customer` = '.(int)($id_customer).'
            AND pl.`id_lang` = '.(int)($id_lang).'
            AND wp.`id_wishlist` = '.(int)($id_wishlist).
            (empty($id_product) === false ? ' AND wp.`id_product` = '.(int)($id_product) : '').
            ($quantity == true ? ' AND wp.`quantity` != 0': '').'
            GROUP BY p.id_product, wp.id_product_attribute');
        
        if (empty($products) === true or !sizeof($products)) {
            return array();
        }
        
        for ($i = 0; $i < sizeof($products); ++$i) {
            if (isset($products[$i]['id_product_attribute']) and Validate::isUnsignedInt($products[$i]['id_product_attribute'])) {
                $result = Db::getInstance()->executeS('
                    SELECT al.`name` AS attribute_name, pa.`quantity` AS "attribute_quantity"
                    FROM `'._DB_PREFIX_.'product_attribute_combination` pac
                    LEFT JOIN `'._DB_PREFIX_.'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
                    LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
                    LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)($id_lang).')
                    LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)($id_lang).')
                    LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                    '.Shop::addSqlAssociation('product_attribute', 'pa').'
                    WHERE pac.`id_product_attribute` = '.(int)($products[$i]['id_product_attribute']));
                
                $products[$i]['attributes_small'] = '';
                
                if ($result) {
                    foreach ($result as $k => $row) {
                        $products[$i]['attributes_small'] .= $row['attribute_name'].', ';
                    }
                    // validate module
                    unset($k);
                }
                
                $products[$i]['attributes_small'] = rtrim($products[$i]['attributes_small'], ', ');
                
                if (isset($result[0])) {
                    $products[$i]['attribute_quantity'] = $result[0]['attribute_quantity'];
                }
            } else {
                $products[$i]['attribute_quantity'] = $products[$i]['product_quantity'];
            }
        }
        return ($products);
    }


    public static function getProductByIdWishlist($id_wishlist, $id_customer, $id_lang, $id_product = null, $quantity = false)
    {
        
        $products = Db::getInstance()->executeS('
            SELECT wp.`id_product`, wp.`quantity`, p.`quantity` AS product_quantity, pl.`name`, wp.`id_product_attribute`, wp.`priority`, pl.link_rewrite, cl.link_rewrite AS category_rewrite
            FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
            LEFT JOIN `'._DB_PREFIX_.'product` p ON p.`id_product` = wp.`id_product`
            '.Shop::addSqlAssociation('product', 'p').'
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.`id_product` = wp.`id_product`'.Shop::addSqlRestrictionOnLang('pl').'
            LEFT JOIN `'._DB_PREFIX_.'leofeature_wishlist` w ON w.`id_wishlist` = wp.`id_wishlist`
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON cl.`id_category` = product_shop.`id_category_default` and cl.id_lang='.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').'
            WHERE w.`id_customer` = '.(int)($id_customer).'
            AND wp.`id_wishlist` = '.(int)($id_wishlist).
            (empty($id_product) === false ? ' AND wp.`id_product` = '.(int)($id_product) : '').
            ($quantity == true ? ' AND wp.`quantity` != 0': '').'
            GROUP BY p.id_product, wp.id_product_attribute');
        
        if (empty($products) === true or !sizeof($products)) {
            return array();
        }
        
        for ($i = 0; $i < sizeof($products); ++$i) {
            if (isset($products[$i]['id_product_attribute']) and Validate::isUnsignedInt($products[$i]['id_product_attribute'])) {
                $result = Db::getInstance()->executeS('
                    SELECT al.`name` AS attribute_name, pa.`quantity` AS "attribute_quantity"
                    FROM `'._DB_PREFIX_.'product_attribute_combination` pac
                    LEFT JOIN `'._DB_PREFIX_.'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
                    LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
                    LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)($id_lang).')
                    LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)($id_lang).')
                    LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                    '.Shop::addSqlAssociation('product_attribute', 'pa').'
                    WHERE pac.`id_product_attribute` = '.(int)($products[$i]['id_product_attribute']));
                
                $products[$i]['attributes_small'] = '';
                
                if ($result) {
                    foreach ($result as $k => $row) {
                        $products[$i]['attributes_small'] .= $row['attribute_name'].', ';
                    }
                    // validate module
                    unset($k);
                }
                
                $products[$i]['attributes_small'] = rtrim($products[$i]['attributes_small'], ', ');
                
                if (isset($result[0])) {
                    $products[$i]['attribute_quantity'] = $result[0]['attribute_quantity'];
                }
            } else {
                $products[$i]['attribute_quantity'] = $products[$i]['product_quantity'];
            }
        }
        return ($products);
    }
    
    public static function getSimpleProductByIdCustomer($id_customer, $id_lang , $id_shop )
    {
        
        if (!Validate::isUnsignedId($id_customer) or !Validate::isUnsignedId($id_shop)) {
            die(Tools::displayError());
        }

        $wishlists = Db::getInstance()->executeS('
        SELECT w.`id_wishlist`
        FROM `'._DB_PREFIX_.'leofeature_wishlist` w
        WHERE w.`id_customer` = '.(int)$id_customer.' 
        AND w.`id_lang` = '.(int)$id_lang);
        
        if (empty($wishlists) === true or !sizeof($wishlists)) {
            return array();
        }
        
        $wishlist_product = array();
        foreach ($wishlists as $wishlists_val) {
            $product = Db::getInstance()->executeS('
            SELECT wp.`id_product`, wp.`id_product_attribute`
            FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
            WHERE wp.`id_wishlist` = '.(int)$wishlists_val['id_wishlist'].'');
            $wishlist_product[$wishlists_val['id_wishlist']] = $product;
        }
        
        
        return ($wishlist_product);
    }


    public static function getInfosByIdCustomer($id_customer, $id_wishlist)
    {
        if (Shop::getContextShopID()) {
            $shop_restriction = 'AND id_shop = '.(int)Shop::getContextShopID();
        } elseif (Shop::getContextShopGroupID()) {
            $shop_restriction = 'AND id_shop_group = '.(int)Shop::getContextShopGroupID();
        } else {
            $shop_restriction = '';
        }

        if (!Validate::isUnsignedId($id_customer)) {
            die(Tools::displayError());
        }
        
        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT SUM(wp.`quantity`) AS nbProducts, wp.`id_wishlist`
              FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
            INNER JOIN `'._DB_PREFIX_.'leofeature_wishlist` w ON (w.`id_wishlist` = wp.`id_wishlist`)
            WHERE w.`id_customer` = '.(int)($id_customer).' AND wp.`id_wishlist` = '.(int)($id_wishlist).'
            '.$shop_restriction.'
            GROUP BY w.`id_wishlist`
            ORDER BY w.`name` ASC'));
    }

    public static function addProduct($id_wishlist, $id_product, $id_product_attribute)
    {
        
        $quantity = 1;
        $result = Db::getInstance()->getRow('
            SELECT wp.`quantity`
              FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
            JOIN `'._DB_PREFIX_.'leofeature_wishlist` w ON (w.`id_wishlist` = wp.`id_wishlist`)
            WHERE wp.`id_wishlist` = '.(int)($id_wishlist).'
            AND wp.`id_product` = '.(int)($id_product).'
            AND wp.`id_product_attribute` = '.(int)($id_product_attribute));


        if (empty($result) === false and sizeof($result)) {
            if (($result['quantity'] ) <= 0) {
                return (WishList::removeProduct($id_wishlist, $id_customer, $id_product, $id_product_attribute));
            } else {
                return (Db::getInstance()->execute('
                    UPDATE `'._DB_PREFIX_.'leofeature_wishlist_product` SET
                    `quantity` = '.(int)($quantity + $result['quantity']).'
                    WHERE `id_wishlist` = '.(int)($id_wishlist).'
                    AND `id_product` = '.(int)($id_product).'
                    AND `id_product_attribute` = '.(int)($id_product_attribute)));
            }
        } else {
            return (Db::getInstance()->execute('
                INSERT INTO `'._DB_PREFIX_.'leofeature_wishlist_product` (`id_wishlist`, `id_product`, `id_product_attribute`, `quantity`, `priority`) VALUES(
                '.(int)($id_wishlist).',
                '.(int)($id_product).',
                '.(int)($id_product_attribute).',
                '.(int)($quantity).', 1)'));
        }


        $this->inCounter($id_wishlist);
        
    }

    public static function updateProduct($id_wishlist, $id_product, $id_product_attribute, $priority, $quantity)
    {
        if (!Validate::isUnsignedId($id_wishlist) or !Validate::isUnsignedId($id_product) or !Validate::isUnsignedId($quantity) or $priority < 0 or $priority > 2) {
            die(Tools::displayError());
        }
        return (Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'leofeature_wishlist_product` SET
            `priority` = '.(int)($priority).',
            `quantity` = '.(int)($quantity).'
            WHERE `id_wishlist` = '.(int)($id_wishlist).'
            AND `id_product` = '.(int)($id_product).'
            AND `id_product_attribute` = '.(int)($id_product_attribute)));
    }

    public static function removeProduct($id_wishlist,  $id_product, $id_product_attribute)
    {
        return Db::getInstance()->execute('
            DELETE FROM `'._DB_PREFIX_.'leofeature_wishlist_product`
            WHERE `id_wishlist` = '.(int)($id_wishlist).'
            AND `id_product` = '.(int)($id_product).'
            AND `id_product_attribute` = '.(int)($id_product_attribute));
            
    }

    public static function isDefault($id_customer)
    {
        return (Bool)Db::getInstance()->getValue('SELECT * FROM `'._DB_PREFIX_.'leofeature_wishlist` WHERE `id_customer` = '.(int)$id_customer.' AND `default` = 1');
    }

    public static function getDefault($id_customer)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'leofeature_wishlist` WHERE `id_customer` = '.(int)$id_customer.' AND `default` = 1');
    }

    public function setDefault()
    {
        if ($default = $this->getDefault($this->id_customer)) {
            Db::getInstance()->update('leofeature_wishlist', array('default' => '0'), 'id_wishlist = '.(int)$default[0]['id_wishlist']);
        }

        return Db::getInstance()->update('leofeature_wishlist', array('default' => '1'), 'id_wishlist = '.(int)$this->id);
    }
   
    public static function removeProductWishlist($id_wishlist, $id_wishlist_product)
    {
        if (!Validate::isUnsignedId($id_wishlist_product) || !Validate::isUnsignedId($id_wishlist)) {
            die(Tools::displayError());
        }
        
        return Db::getInstance()->execute('
            DELETE FROM `'._DB_PREFIX_.'leofeature_wishlist_product`
            WHERE `id_wishlist_product` = '.(int)($id_wishlist_product).'
            AND `id_wishlist` = '.(int)($id_wishlist));
    }
   
    public static function updateProductWishlist($id_wishlist, $id_wishlist_product, $priority, $quantity)
    {
        if (!Validate::isUnsignedId($id_wishlist_product) || !Validate::isUnsignedId($id_wishlist) || !Validate::isUnsignedInt($quantity) || !Validate::isUnsignedInt($priority) || $priority < 0 || $priority > 2) {
            die(Tools::displayError());
        }
            
        return Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'leofeature_wishlist_product` SET
            `priority` = '.(int)($priority).',
            `quantity` = '.(int)($quantity).'
            WHERE `id_wishlist` = '.(int)($id_wishlist).'
            AND `id_wishlist_product` = '.(int)($id_wishlist_product));
    }
    
    public static function getSimpleProductByIdWishlist($id_wishlist)
    {
        if (!Validate::isUnsignedId($id_wishlist)) {
            die(Tools::displayError());
        }
        
        return Db::getInstance()->executeS('
            SELECT wp.*
            FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
            WHERE wp.`id_wishlist` = '.(int)$id_wishlist.'');
    }

    public static function existsLang($id_customer, $id_lang)
    {
        // Check if a wishlist exists for the given customer and language
        $wishlists = Db::getInstance()->executeS('
        SELECT *
        FROM `'._DB_PREFIX_.'leofeature_wishlist` w
        WHERE w.`id_customer` = '.(int)$id_customer.' 
        AND w.`id_lang` = '.(int)$id_lang);
    
        // If at least one wishlist exists, return it
        if (count($wishlists) > 0) {
            return $wishlists[0]; // Return the first wishlist found
        }
    
        // Generate values for the additional fields
        $date_now = date('Y-m-d H:i:s');
        $default = 0; // Adjust as needed
        $id_shop = (int)Shop::getContextShopID(); // Adjust according to context
        $id_shop_group = (int)Shop::getContextShopGroupID(); // Adjust according to context
        $counter = 0; // Initialize the counter to 0
        $name = 'My Wishlist'; // Default name, adjust as needed
        $token = uniqid(rand(), true); // Generate a unique token
    
        // Insert a new wishlist
        $insert = Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'leofeature_wishlist` 
            (`id_customer`, `id_lang`, `date_add`, `date_upd`, `default`, `id_shop`, `id_shop_group`, `counter`, `name`, `token`)
            VALUES 
            ('.(int)$id_customer.', '.(int)$id_lang.', "'.$date_now.'", "'.$date_now.'", '.(int)$default.', '.(int)$id_shop.', '.(int)$id_shop_group.', '.(int)$counter.', "'.pSQL($name).'", "'.pSQL($token).'")');
    
        // If insertion was successful, return the newly created wishlist
        if ($insert) {
            // Get the ID of the last inserted wishlist
            $id_wishlist = Db::getInstance()->Insert_ID();
    
            // Retrieve the newly inserted wishlist
            $new_wishlist = Db::getInstance()->getRow('
            SELECT *
            FROM `'._DB_PREFIX_.'leofeature_wishlist` w
            WHERE w.`id_wishlist` = '.(int)$id_wishlist);
    
            return $new_wishlist;
        }
    
        // Return false if insertion failed
        return false;
    }

    public static function isProductInCustomerWishlist($id_customer, $id_product, $id_product_attribute = 0, $id_lang = null)
    {
        if (!Validate::isUnsignedId($id_customer) || !Validate::isUnsignedId($id_product)) {
            return false;
        }

        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        // Búsqueda específica por producto y atributo
        $sql = 'SELECT wp.id_wishlist, wp.id_product, wp.id_product_attribute, wp.quantity, w.name as wishlist_name
            FROM `' . _DB_PREFIX_ . 'leofeature_wishlist_product` wp
            INNER JOIN `' . _DB_PREFIX_ . 'leofeature_wishlist` w ON (wp.id_wishlist = w.id_wishlist)
            WHERE w.id_customer = ' . (int)$id_customer . '
            AND w.id_lang = ' . (int)$id_lang . '
            AND wp.id_product = ' . (int)$id_product;

        // Si se especifica un atributo, buscarlo específicamente
        if ($id_product_attribute > 0) {
            $sql .= ' AND wp.id_product_attribute = ' . (int)$id_product_attribute;
        }

        $sql .= ' ORDER BY w.default DESC, w.date_upd DESC LIMIT 1';

        $result = Db::getInstance()->getRow($sql);

        // Si se encontró, devolver la información
        if (!empty($result)) {
            return $result;
        }

        // Si no se encontró con atributo específico y se proporcionó uno, buscar cualquier variante
        if ($id_product_attribute > 0) {
            $sql = 'SELECT wp.id_wishlist, wp.id_product, wp.id_product_attribute, wp.quantity, w.name as wishlist_name
                FROM `' . _DB_PREFIX_ . 'leofeature_wishlist_product` wp
                INNER JOIN `' . _DB_PREFIX_ . 'leofeature_wishlist` w ON (wp.id_wishlist = w.id_wishlist)
                WHERE w.id_customer = ' . (int)$id_customer . '
                AND w.id_lang = ' . (int)$id_lang . '
                AND wp.id_product = ' . (int)$id_product . '
                ORDER BY w.default DESC, w.date_upd DESC LIMIT 1';

            $result = Db::getInstance()->getRow($sql);

            if (!empty($result)) {
                return $result;
            }
        }

        return false;
    }

    public static function isProductInWishlist($id_customer, $id_product, $id_product_attribute = 0, $id_lang = null)
    {
        $result = self::isProductInCustomerWishlist($id_customer, $id_product, $id_product_attribute, $id_lang);
        return !empty($result);
    }

    public static function getLatestByIdCustomer($id_customer)
    {

        $result = Db::getInstance()->getRow('
        SELECT w.`id_wishlist`, w.`name`, w.`token`, w.`date_add`, w.`date_upd`, w.`counter`, w.`default`, w.`id_lang`
        FROM `'._DB_PREFIX_.'leofeature_wishlist` w
        WHERE w.`id_customer` = '.(int)$id_customer.'
        ORDER BY w.`default` DESC, w.`date_upd` DESC, w.`id_wishlist` DESC
        LIMIT 1'
        );

        return $result;
    }
    public static function isProductInLatestWishlist($id_customer, $id_product, $id_product_attribute = 0, $id_lang = null)
    {
        if (!Validate::isUnsignedId($id_customer) || !Validate::isUnsignedId($id_product)) {
            return false;
        }

        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        // Búsqueda en la wishlist más reciente
        $sql = 'SELECT wp.id_wishlist, wp.id_product, wp.id_product_attribute, wp.quantity, w.name as wishlist_name, w.date_upd
            FROM `' . _DB_PREFIX_ . 'leofeature_wishlist_product` wp
            INNER JOIN `' . _DB_PREFIX_ . 'leofeature_wishlist` w ON (wp.id_wishlist = w.id_wishlist)
            WHERE w.id_customer = ' . (int)$id_customer . '
            AND w.id_lang = ' . (int)$id_lang . '
            AND wp.id_product = ' . (int)$id_product;

        // Si se especifica un atributo, incluirlo en la búsqueda
        if ($id_product_attribute > 0) {
            $sql .= ' AND wp.id_product_attribute = ' . (int)$id_product_attribute;
        }

        $sql .= ' ORDER BY w.default DESC, w.date_upd DESC, w.id_wishlist DESC LIMIT 1';

        $result = Db::getInstance()->getRow($sql);

        return !empty($result) ? $result : false;
    }
}
