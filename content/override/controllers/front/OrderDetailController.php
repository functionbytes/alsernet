<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;

class OrderDetailController extends OrderDetailControllerCore
{
    public $php_self = 'order-detail';
    public $auth = true;
    public $authRedirection = 'history';
    public $ssl = true;

    protected $order_to_display;

    protected $reference;


    public function initContent()
    {
        if (Configuration::isCatalogMode()) {
            Tools::redirect('index.php');
        }

        $id_order = (int) Tools::getValue('id_order');
        $id_order = $id_order && Validate::isUnsignedId($id_order) ? $id_order : false;

        if (!$id_order) {
            $reference = Tools::getValue('reference');
            $reference = $reference && Validate::isReference($reference) ? $reference : false;
            $order = $reference ? Order::getByReference($reference)->getFirst() : false;
            $id_order = $order ? $order->id : false;
        }

        if (!$id_order) {
            $this->redirect_after = '404';
            $this->redirect();
        } else {
            if (Tools::getIsset('errorQuantity')) {
                $this->errors[] = $this->trans('You do not have enough products to request an additional merchandise return.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorMsg')) {
                $this->errors[] = $this->trans('Please provide an explanation for your RMA.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorDetail1')) {
                $this->errors[] = $this->trans('Please check at least one product you would like to return.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorDetail2')) {
                $this->errors[] = $this->trans('For each product you wish to add, please specify the desired quantity.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorNotReturnable')) {
                $this->errors[] = $this->trans('This order cannot be returned', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('messagesent')) {
                $this->success[] = $this->trans('Message successfully sent', [], 'Shop.Notifications.Success');
            }

            $order = new Order($id_order);

            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {

                $lang = $this->context->language->id;
                $orderData = (new OrderPresenter())->present($order);
                $shipping = $order->getOrderShippingTracking($order, (int) $lang);

                $lang=$this->context->language->id;
                $customer = new Customer($order->id_customer);
                $deliveryAddress = new Address($order->id_address_delivery);
                $invoiceAddress = new Address($order->id_address_invoice);
                $deliveryCountry = new Country($deliveryAddress->id_country,$lang);
                $invoiceCountry = new Country($invoiceAddress->id_country,$lang);
                $carrier = new Carrier($order->id_carrier,$lang);
                $currency = new Currency($order->id_currency,$lang);
                $orderState = $order->getCurrentOrderState($lang);
                $historyRaw = $order->getHistory((int) $lang);
                $products = $order->getProducts();

                $history = array_map(function ($item) {
                    return [
                        'history_date' => isset($item['date_add']) ? date('d/m/Y', strtotime($item['date_add'])) : '',
                        'id_order_state' => $item['id_order_state'],
                        'ostate_name' => $item['ostate_name'],
                    ];
                }, $historyRaw);

                $shipping = $order->getOrderShippingTracking($order, (int) $lang);

                $invoiceUrl = $order->hasInvoice()
                    ? $this->context->link->getPageLink('pdf-invoice', true, null, ['id_order' => $order->id])
                    : null;

                $orderFormatted = [
                    'details' => [
                        'id' => $order->id,
                        'reference' => $order->reference,
                        'status' => $orderState ? $orderState->name : '',
                        'date_add' => Tools::displayDate($order->date_add),
                        'order_date' => Tools::displayDate($order->date_add),
                        'total_paid' => Tools::displayPrice($order->total_paid, $currency),
                        'payment' => $order->payment,
                        'invoice_url' => $invoiceUrl,
                        'gift_message' => $order->gift_message,
                        'recyclable' => (bool) $order->recyclable,
                        'is_returnable' => false,
                    ],
                    'carrier' => [
                        'name' => $carrier->name,
                    ],
                    'order' => $order,
                    'addresses' => [
                        'delivery' => [
                            'firstname' => $deliveryAddress->firstname,
                            'lastname' => $deliveryAddress->lastname,
                            'address1' => $deliveryAddress->address1,
                            'address2' => $deliveryAddress->address2,
                            'postcode' => $deliveryAddress->postcode,
                            'city' => $deliveryAddress->city,
                            'country' => $deliveryCountry->name,
                            'state' => State::getNameById($deliveryAddress->id_state),
                            'phone' => $deliveryAddress->phone,
                            'alias' => $deliveryAddress->alias,
                        ],
                        'invoice' => [
                            'firstname' => $invoiceAddress->firstname,
                            'lastname' => $invoiceAddress->lastname,
                            'address1' => $invoiceAddress->address1,
                            'address2' => $invoiceAddress->address2,
                            'postcode' => $invoiceAddress->postcode,
                            'city' => $invoiceAddress->city,
                            'country' => $invoiceCountry->name,
                            'state' => State::getNameById($invoiceAddress->id_state),
                            'phone' => $invoiceAddress->phone,
                            'alias' => $invoiceAddress->alias,
                        ],
                    ],
                    'history' => $history,
                    'shipping' => $shipping,
                    'products' => $products,
                    'totals' => [
                        'total' => [
                            'value' => Tools::displayPrice($order->total_paid, $currency),
                        ]
                    ]
                ];

                $this->context->smarty->assign([
                    'order' => $orderFormatted,
                ]);

            } else {
                $this->redirect_after = '404';
                $this->redirect();
            }
            unset($order);
        }

        parent::initContent();
        $this->setTemplate('customer/order-detail');
    }

}
