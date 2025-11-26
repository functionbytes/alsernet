<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'alsernetcustomer/classes/Wishlist/Wishlist.php';


use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class WishlistController extends Module
{

    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->module =  Module::getInstanceByName("alsernetcustomer");
    }

    public function delete()
    {

        $id_wishlist = (int) Tools::getValue('id_wishlist');
        $iso = Tools::getValue('iso');
        $id_product = (int) Tools::getValue('id_product');
        $id_product_attribute = (int) Tools::getValue('id_product_attribute');

        WishList::removeProduct($id_wishlist, $id_product, $id_product_attribute);

        $data = array(
            'title' => $this->l('Remove to Wishlist', 'wishlistcontroller',$iso),
            'icon' => 'fa-light fa-heart',
        );

        return [
            'status' => 'success',
            'message' => 'success',
            'data' => $data,
            'operation' => $this->l('Successful operation.',$iso),
        ];

    }

    public function remove()
    {


        $iso = Tools::getValue('iso');
        $id_wishlist = (int) Tools::getValue('id_wishlist');
        $id_product = (int) Tools::getValue('id_product');
        $id_product_attribute = (int) Tools::getValue('id_product_attribute');

        WishList::removeProduct($id_wishlist, $id_product, $id_product_attribute);

        $data = array(
            'title' => $this->l('Remove to Wishlist', 'wishlistcontroller',$iso),
            'icon' => 'fa-light fa-heart',
        );

        return [
            'status' => 'success',
            'message' => 'success',
            'data' => $data,
            'operation' => $this->l('Successful operation.', 'wishlistcontroller',$iso),
        ];

    }

    public function cart()
    {
        $id_customer = $this->context->customer->id;
        $id_product = (int) Tools::getValue('id_product');
        $iso = Tools::getValue('iso');
        $id_product_attribute = (int) Tools::getValue('id_product_attribute');
        $quantity = (int) Tools::getValue('quantity', 1);

        if (!$id_product || !$id_customer) {
            return [
                'status' => 'error',
                'message' => $this->l('Invalid product or customer ID.'),
                'operation' => $this->l('Failed operation.',$iso),
            ];
        }

        $cart = $this->context->cart;
        if (!$cart) {
            $cart = new Cart();
            $cart->id_customer = $id_customer;
            $cart->id_address_delivery = $this->context->cart->id_address_delivery;
            $cart->id_address_invoice = $this->context->cart->id_address_invoice;
            $cart->id_currency = $this->context->cart->id_currency;
            $cart->id_lang = $this->context->cart->id_lang;
            $cart->add();
            $this->context->cart = $cart;
        }

        $productAdded = $cart->updateQty($quantity, $id_product, $id_product_attribute);
        $cart->update();

        if ($productAdded) {
            return [
                'status' => 'success',
                'message' => $this->l('Product successfully added to the cart.', 'wishlistcontroller',$iso),
                'operation' => $this->l('Successful operation.','wishlistcontroller',$iso),
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $this->l('Failed to add the product to the cart.', 'wishlistcontroller',$iso),
                'operation' => $this->l('Failed operation.', 'wishlistcontroller',$iso),
            ];
        }
    }

    public function add()
    {

        $context = $this->context;
        $id_customer = (int) $context->customer->id;
        $id_lang = (int) $context->language->id;
        $iso = Tools::getValue('iso');
        $id_product = (int) Tools::getValue('id_product');
        $id_product_attribute = (int) Tools::getValue('id_product_attribute');

        // 1. Validar si ya existe una wishlist (por idioma)
        $wishlist = Wishlist::existsLang($id_customer, 1);

        // 2. Si no existe, crearla manualmente (esto no debería ocurrir, pero puede fallar existsLang)
        if (!$wishlist || empty($wishlist['id_wishlist'])) {
            $wishlist = Wishlist::createNewWishlist($id_customer,  1 , 'My Wishlist', true);
        }

        // Validación final de seguridad
        if (!$wishlist || empty($wishlist['id_wishlist'])) {
            return [
                'status' => 'error',
                'message' => 'No se pudo crear una wishlist para el cliente',
            ];
        }

        $id_wishlist = (int) $wishlist['id_wishlist'];

        Wishlist::addProduct($id_wishlist, $id_product, $id_product_attribute);

        $data = array(
            'title' => $this->l('Add from Wishlist', 'wishlistcontroller',$iso),
            'icon' => 'fa-solid fa-heart',
        );

        return [
            'status' => 'success',
            'message' => 'success',
            'operation' => $this->l('Successful operation.', 'wishlistcontroller',$iso),
            'data' => $data,
        ];

    }


    public function count()
    {
        $context = $this->context;
        $id_customer = (int) $context->customer->id;
        $iso = Tools::getValue('iso');

        if (!$id_customer) {
            return [
                'status' => 'error',
                'message' => $this->l('Customer not logged in.', 'wishlistcontroller', $iso),
                'count' => 0
            ];
        }

        try {
            // Get customer's wishlist
            $wishlist = Wishlist::existsLang($id_customer, 1);

            if (!$wishlist || empty($wishlist['id_wishlist'])) {
                return [
                    'status' => 'success',
                    'message' => $this->l('No wishlist found.', 'wishlistcontroller', $iso),
                    'count' => 0
                ];
            }

            $id_wishlist = (int) $wishlist['id_wishlist'];

            // Count products in wishlist
            $count = Db::getInstance()->getValue('
                SELECT COUNT(DISTINCT wp.id_product, wp.id_product_attribute)
                FROM `'._DB_PREFIX_.'leofeature_wishlist_product` wp
                WHERE wp.`id_wishlist` = '.(int)$id_wishlist
            );

            return [
                'status' => 'success',
                'message' => $this->l('Wishlist count retrieved successfully.', 'wishlistcontroller', $iso),
                'count' => (int) $count
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $this->l('Error retrieving wishlist count.', 'wishlistcontroller', $iso),
                'count' => 0
            ];
        }
    }

    public function clear()
    {

    }


    public function update()
    {

    }


    public function move()
    {

    }



    public function l($string, $specific = false, $locale = null)
    {

        return $this->getModuleTranslation(
            $this->module,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            $locale
        );
    }


    public  function getModuleTranslation(
        $module,
        $originalString,
        $source,
        $sprintf = null,
        $js = false,
        $locale = null,
        $fallback = true,
        $escape = true
    ) {


        global $_MODULES, $_MODULE, $_LANGADM;
        static $langCache = [];
        static $name = null;
        static $translationsMerged = [];

        $name = $module->name;

        $iso = $locale;

        if (!isset($translationsMerged[$name][$iso])) {
            $filesByPriority = [
                _PS_MODULE_DIR_ . $name . '/translations/' . $iso . '.php',
                _PS_MODULE_DIR_ . $name . '/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/' . $iso . '.php',
            ];
            foreach ($filesByPriority as $file) {
                if (file_exists($file)) {
                    include_once $file;
                    $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                }
            }
            $translationsMerged[$name][$iso] = true;
        }

        $string = preg_replace("/\\\*'/", "\'", $originalString);
        $key = md5($string);

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int) $js . '|' . $iso;
        if (isset($langCache[$cacheKey])) {
            $ret = $langCache[$cacheKey];
        } else {

            $currentKey = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $defaultKey = strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;


            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $defaultKeyFile = strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = stripslashes($_MODULES[$currentKeyFile]);
            } elseif (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = stripslashes($_MODULES[$defaultKeyFile]);
            } elseif (!empty($_MODULES[$currentKey])) {
                $ret = stripslashes($_MODULES[$currentKey]);
            } elseif (!empty($_MODULES[$defaultKey])) {
                $ret = stripslashes($_MODULES[$defaultKey]);
            } elseif (!empty($_LANGADM)) {
                // if translation was not found in module, look for it in AdminController or Helpers
                $ret = stripslashes(Translate::getGenericAdminTranslation($string, $key, $_LANGADM));
            } else {
                $ret = stripslashes($string);
            }


            if (
                $sprintf !== null &&
                (!is_array($sprintf) || !empty($sprintf)) &&
                !(count($sprintf) === 1 && isset($sprintf['legacy']))
            ) {
                $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
            }

            if ($js) {
                $ret = addslashes($ret);
            } elseif ($escape) {
                $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
            }

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            }
        }

        if (!is_array($sprintf) && null !== $sprintf) {
            $sprintf_for_trans = [$sprintf];
        } elseif (null === $sprintf) {
            $sprintf_for_trans = [];
        } else {
            $sprintf_for_trans = $sprintf;
        }

        if ($ret === $originalString && $fallback) {
            $ret = Context::getContext()->getTranslator()->trans($originalString, $sprintf_for_trans, null, $locale);
        }

        return $ret;
    }


}