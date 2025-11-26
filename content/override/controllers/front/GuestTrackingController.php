<?php

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;

class GuestTrackingController extends GuestTrackingControllerCore
{
    public $ssl = true;
    public $auth = false;
    public $php_self = 'guest-tracking';
    protected $order;


    public function init()
    {
        parent::init();
    }

    public function postProcess()
    {
        $this->order_reference = current(explode('#', Tools::getValue('order_reference')));
        $email = Tools::getValue('email');

        if (!$email && !$this->order_reference) {
            return;
        } elseif (!$email || !$this->order_reference) {
            $this->errors[] = $this->getTranslator()->trans(
                'Please provide the required information',
                [],
                'Shop.Notifications.Error'
            );

            return;
        }

        $this->order = Order::getByReferenceAndEmail($this->order_reference, $email);
        if (!Validate::isLoadedObject($this->order)) {
            $this->errors[] = $this->getTranslator()->trans(
                    'We couldn\'t find your order with the information provided, please try again',
                    [],
                    'Shop.Notifications.Error'
                );
        }

        if (Tools::isSubmit('submitTransformGuestToCustomer') && Tools::getValue('password')) {
            $customer = new Customer((int) $this->order->id_customer);
            $password = Tools::getValue('password');

            if (strlen($password) < Validate::PASSWORD_LENGTH) {
                $this->errors[] = $this->trans(
                    'Your password must be at least %min% characters long.',
                    ['%min%' => Validate::PASSWORD_LENGTH],
                    'Shop.Forms.Help'
                );
            } elseif ($customer->transformToCustomer($this->context->language->id, $password)) {
                $this->success[] = $this->trans(
                    'Your guest account has been successfully transformed into a customer account. You can now log in as a registered shopper.',
                    [],
                    'Shop.Notifications.Success'
                );
            } else {
                $this->success[] = $this->trans(
                    'An unexpected error occurred while creating your account.',
                    [],
                    'Shop.Notifications.Error'
                );
            }
        }
    }

    public function initContent()
    {
        parent::initContent();

        if (!Validate::isLoadedObject($this->order)) {

            return $this->setTemplate('customer/guest-login');
        }

        if ((int) $this->order->isReturnable()) {
            $this->info[] = $this->trans(
                'You cannot return merchandise with a guest account.',
                [],
                'Shop.Notifications.Warning'
            );
        }

            $lang=$this->context->language->id;
            $customer = new Customer($this->order->id_customer);
            $deliveryAddress = new Address($this->order->id_address_delivery);
            $invoiceAddress = new Address($this->order->id_address_invoice);
            $deliveryCountry = new Country($deliveryAddress->id_country,$lang);
            $invoiceCountry = new Country($invoiceAddress->id_country,$lang);
            $carrier = new Carrier($this->order->id_carrier,$lang);
            $currency = new Currency($this->order->id_currency,$lang);
            $this->orderState = $this->order->getCurrentOrderState($lang);
            $historyRaw = $this->order->getHistory((int) $lang);

            $history = array_map(function ($item) {
                return [
                    'history_date' => isset($item['date_add']) ? date('d/m/Y', strtotime($item['date_add'])) : '',
                    'id_order_state' => $item['id_order_state'],
                    'ostate_name' => $item['ostate_name'],
                ];
            }, $historyRaw);

            $shipping = $this->order->getOrderShippingTracking($this->order, (int) $lang);

            $invoiceUrl = $this->order->hasInvoice()
                ? $this->context->link->getPageLink('pdf-invoice', true, null, ['id_order' => $this->order->id])
                : null;

            $orderFormatted = [
                'details' => [
                    'id' => $this->order->id,
                    'reference' => $this->order->reference,
                    'status' => $this->orderState ? $this->orderState->name : '',
                    'date_add' => Tools::displayDate($this->order->date_add),
                    'order_date' => Tools::displayDate($this->order->date_add),
                    'total_paid' => Tools::displayPrice($this->order->total_paid, $currency),
                    'payment' => $this->order->payment,
                    'invoice_url' => $invoiceUrl,
                    'gift_message' => $this->order->gift_message,
                    'recyclable' => (bool) $this->order->recyclable,
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
                        'value' => Tools::displayPrice($this->order->total_paid, $currency),
                    ]
                ]
            ];

            $this->context->smarty->assign([
                'order' => $orderFormatted,
                'is_customer' => Customer::customerExists(Tools::getValue('email'), false, true),
                'HOOK_DISPLAYORDERDETAIL' => Hook::exec('displayOrderDetail', ['order' => $this->order]),
            ]);


            return $this->setTemplate('customer/guest-tracking');


    }

}
