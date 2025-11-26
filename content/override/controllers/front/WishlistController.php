<?php


require_once(_PS_MODULE_DIR_.'alsernetcustomer/classes/Wishlist/Wishlist.php');
require_once(_PS_MODULE_DIR_.'alsernetcustomer/classes/Wishlist/FeatureProduct.php');

class WishlistController extends FrontController
{
    public $php_self = 'wishlist';

    public function initContent()
    {
        parent::initContent();

        if ($this->context->customer->isLogged()) {

            $products = array();

            $wishlist = WishList::existsLang($this->context->customer->id, 1);

            if ($wishlist) {

                $wishlist_product = WishList::getProductByIdWishlist((int)$wishlist['id_wishlist'], $this->context->customer->id, 1);

                $product_object = new FeatureProduct();

                if (!empty($wishlist_product)) {

                    foreach ($wishlist_product as $wishlist_product_item) {
                        $list_product_tmp['product'] = $product_object->getTemplateVarProductTemplate($wishlist_product_item['id_product'], $wishlist_product_item['id_product_attribute']);
                        $list_product_tmp['stock'] = StockAvailable::getQuantityAvailableByProduct($wishlist_product_item['id_product'], 0);
                        $products[] = $list_product_tmp;
                    }
                }

            }


            $this->context->smarty->assign(array(
                'wishlist' => $wishlist,
                'id_wishlist' => $wishlist['id_wishlist'],
                'products' => $products,
            ));

            $this->setTemplate('customer/wishlist.tpl');

        }else{
            Tools::redirectLink($this->context->link->getPageLink('authentication', true));

        }

    }


}
