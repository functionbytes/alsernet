<?php

class TrackingController extends FrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $orderFormatted = null;
        $idOrder = (int) Tools::getValue('id_order');

        if ($idOrder) {
            $order = new Order($idOrder);

            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
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
                    'totals' => [
                        'total' => [
                            'value' => Tools::displayPrice($order->total_paid, $currency),
                        ]
                    ]
                ];
            }
        }


        $this->context->smarty->assign([
            'order' => $orderFormatted,
        ]);

        $this->setTemplate('customer/order-tracking.tpl');
    }
}
