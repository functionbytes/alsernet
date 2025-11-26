<?php

require_once _PS_MODULE_DIR_ . 'alsernetcustomer/classes/Wishlist/Wishlist.php';
class MyAccountController extends MyAccountControllerCore
{
    public $auth = true;
    public $php_self = 'my-account';
    public $authRedirection = 'my-account';
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $id_customer = (int) $this->context->customer->id;

        $total_orders = Order::getCustomerOrders($id_customer, true);
        $orders_count = ($total_orders) ? count($total_orders) : 0;

        $pending_orders = array_filter($total_orders, function ($order) {
            return (int)$order['current_state'] != Configuration::get('PS_OS_DELIVERED');
        });
        $pending_orders_count = count($pending_orders);

        $addresses_count = count($this->context->customer->getSimpleAddresses());


        // 1. Validar si ya existe una wishlist (por idioma)
        $wishlist = Wishlist::existsLang($id_customer, 1);

        // 2. Si no existe, crearla manualmente (esto no debería ocurrir, pero puede fallar existsLang)
        if (!$wishlist || empty($wishlist['id_wishlist'])) {
            $wishlist = Wishlist::createNewWishlist($id_customer,  1 , 'My Wishlist', true);
            $wishlist_count = count(WishList::getProductByIdWishlist((int)$wishlist['id_wishlist'], $this->context->customer->id, $this->context->language->id));

        }
        else{
            $wishlist_count = count(WishList::getProductByIdWishlist((int)$wishlist['id_wishlist'], $this->context->customer->id, $this->context->language->id));

        }


        $this->context->smarty->assign([
            'logout_url' => $this->context->link->getPageLink('index', true, null, 'mylogout'),
            'orders_count' => $orders_count,
            'pending_orders_count' => $pending_orders_count,
            'addresses_count' => $addresses_count,
            'wishlist_count' => $wishlist_count,
        ]);

        $this->setTemplate('customer/my-account');
    }
}
