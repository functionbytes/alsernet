<?php

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;

class HistoryController extends HistoryControllerCore
{
    public $auth = true;
    public $php_self = 'history';
    public $authRedirection = 'history';
    public $ssl = true;
    public $order_presenter;

    public function __construct()
    {
        parent::__construct();
        $this->order_presenter = new OrderPresenter();
    }

    public function initContent()
    {
        parent::initContent();

        $statuses = $this->getOrderStatuses();
        $orders = $this->getCustomerOrders();

        if (empty($orders)) {
            $this->warning[] = $this->trans('You have not placed any orders.', [], 'Shop.Notifications.Warning');
        }

        $this->context->smarty->assign([
            'statuses' => $statuses,
            'orders' => $orders
        ]);


        $this->setTemplate('customer/history.tpl');
    }

    private function getOrders()
    {
        $orders = [];
        $customer_orders = Order::getCustomerOrders($this->context->customer->id);

        foreach ($customer_orders as $customer_order) {
            $order = new Order((int) $customer_order['id_order']);
            $presentedOrder = $this->order_presenter->present($order);

            if (!isset($presentedOrder['totals'])) {
                $presentedOrder['totals'] = [
                    'total' => $order->getOrdersTotalPaid(),
                ];
            }

            $orders[$customer_order['id_order']] = $presentedOrder;
        }

        return $orders;
    }

    private function getCustomerOrders()
    {
        $id_customer = (int) $this->context->customer->id;
        if (!$id_customer) {
            return [];
        }

        $sql = 'SELECT o.id_order, o.reference, o.total_paid, o.payment, o.date_add, 
                       o.current_state, osl.name AS order_state
                FROM ' . _DB_PREFIX_ . 'orders o
                INNER JOIN ' . _DB_PREFIX_ . 'order_state_lang osl ON  o.current_state= osl.id_order_state
                WHERE o.id_customer = ' . $id_customer . '
                AND osl.id_lang = ' . (int) $this->context->language->id . '
                GROUP BY o.id_order 
                ORDER BY o.date_add DESC';

        $orders = Db::getInstance()->executeS($sql);

        $orderDetails = [];

        foreach ($orders as $order) {
            $orderDetails[] = [
                'id_order' => $order['id_order'],
                'reference' => $order['reference'],
                'total_paid' => Tools::displayPrice($order['total_paid'], $this->context->currency),
                'payment' => $order['payment'],
                'date_add' => date('d/m/Y', strtotime($order['date_add'])),
                'id_order_state' => $order['current_state'],
                'order_state' => $order['order_state'],
                'details' => [
                    'tracking_link' => $this->context->link->getPageLink('tracking', true, null, ['id_order' => $order['id_order']]),
                    //'tracking_link' => $this->context->link->getPageLink('order-tracking', true, null, ['id_order' => $order['id_order']]),
                    'view_link' => $this->context->link->getPageLink('order-detail', true, null, ['id_order' => $order['id_order']]),
                    'invoice_link' => $this->getInvoiceLink($order['id_order']),
                ]
            ];
        }

        return $orderDetails;
    }


    private function getOrderStatuses()
    {
        $id_customer = (int) $this->context->customer->id;
        if (!$id_customer) {
            return [];
        }

        $sql = 'SELECT o.current_state AS id_order_state, osl.name, COUNT(*) as total
            FROM ' . _DB_PREFIX_ . 'orders o
            INNER JOIN ' . _DB_PREFIX_ . 'order_state_lang osl 
                ON o.current_state = osl.id_order_state
            WHERE o.id_customer = ' . $id_customer . '
            AND osl.id_lang = ' . (int) $this->context->language->id . '
            GROUP BY o.current_state, osl.name
            HAVING COUNT(*) > 0
            ORDER BY o.current_state ASC';


        $statuses = Db::getInstance()->executeS($sql);

        if (!is_array($statuses)) {
            return [];
        }

        foreach ($statuses as &$status) {
            $status['id_order_state'] = (string) $status['id_order_state']; // ya existe
            $status['name'] = (string) $status['name'];
        }

        return $statuses;
    }


    private function getInvoiceLink($id_order)
    {
        $order = new Order((int) $id_order);
        if ($order->invoice_number) {
            return $this->context->link->getPageLink('pdf-invoice', true, null, ['id_order' => $id_order]);
        }
        return null;
    }


}
