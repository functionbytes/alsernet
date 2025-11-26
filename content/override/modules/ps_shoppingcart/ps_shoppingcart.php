<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;

class Ps_ShoppingcartOverride extends Ps_Shoppingcart
{
   

    private function getCartSummaryURL()
    {
        return $this->context->link->getPageLink(
            'cart',
            null,
            $this->context->language->id,
            [
                'action' => 'show',
            ],
            false,
            null,
            true
        );
    }


    
    public function renderModal(Cart $cart, $id_product, $id_product_attribute, $id_customization)
    {
        $data = (new CartPresenter())->present($cart);
        dump($data);die();
        $product = null;
        foreach ($data['products'] as $p) {
            if ((int) $p['id_product'] == $id_product &&
                (int) $p['id_product_attribute'] == $id_product_attribute &&
                (int) $p['id_customization'] == $id_customization) {
                $product = $p;
                break;
            }
        }

        $prod = new Product($id_product);
	$product->id_category_default = $prod->id_category_default;

        $this->smarty->assign([
            'product' => $product,
            'cart' => $data,
            'cart_url' => $this->getCartSummaryURL(),
        ]);


        if ($prod->isBlocked()){
            return $this->fetch('module:ps_shoppingcart/modalbloqueo.tpl');
        } else {
            if ($prod->isBlockedByProductType()) {
                $wishlists = array();
                $id_wishlist = false;
                $url_wishlist = '';

                if (Module::isEnabled('leofeature')) {
                    require_once(_PS_MODULE_DIR_.'leofeature/classes/WishList.php');
                    require_once(_PS_MODULE_DIR_.'leofeature/classes/LeofeatureProduct.php');

                    if (Configuration::get('LEOFEATURE_ENABLE_PRODUCTWISHLIST')) {
                        $url_wishlist = $this->context->link->getModuleLink('leofeature', 'mywishlist');

                        if ($this->context->customer->isLogged()) {
                            $wishlists = Wishlist::getByIdCustomer($this->context->customer->id);
                            if (empty($this->context->cookie->id_wishlist) === true || WishList::exists($this->context->cookie->id_wishlist, $this->context->customer->id) === false) {
                                if (!count($wishlists)) {
                                    $id_wishlist = false;
                                } else {
                                    $id_wishlist = (int) $wishlists[0]['id_wishlist'];
                                    $this->context->cookie->id_wishlist = (int) $id_wishlist;
                                }
                            } else {
                                $id_wishlist = $this->context->cookie->id_wishlist;
                            }
                        }
                    }
                }

                $this->smarty->assign(array(
                    'wishlists' => $wishlists,
                    'id_wishlist' => $id_wishlist,
                    'token' => Tools::getToken(false),
                    'url_wishlist' => $url_wishlist,
                ));

                return $this->fetch('module:ps_shoppingcart/modalbloqueoproducttype.tpl');
            } else {
                $show_modal_categories_relation = false;
                $id_category_target = 0;
                $category_relation = null;
                if (Module::isEnabled('alvarezcategoriesrelation')) {
                    require_once _PS_MODULE_DIR_ . 'alvarezcategoriesrelation/classes/CategoriesRelation.php';

                    /*$id_category_target = CategoriesRelation::getCategoryTargetByIdcategory($prod->id_category_default);
                    if ($id_category_target) {
                        $show_modal_categories_relation = true;
                    }*/
                    $category_relation = CategoriesRelation::getRelationDataByIdcategory($prod->id_category_default, true, $this->context->language->id);
                    if ($category_relation) {
                        $id_category_target = $category_relation['id_category_target'];
                        $show_modal_categories_relation = true;
                    }
                }

                if ($show_modal_categories_relation && $id_category_target && $category_relation) {
                    $this->smarty->assign(array(
                        'category_relation' => $category_relation,
                        'url_category_target' => $this->context->link->getCategoryLink((int) $id_category_target),
                        'url_cart' => $this->context->link->getPageLink('cart'),
                    ));
                    return $this->fetch('module:ps_shoppingcart/modalcategoriesrelation.tpl');
                } else {
                    return $this->fetch('module:ps_shoppingcart/modal.tpl');
                }
            }
        }
    }

}
