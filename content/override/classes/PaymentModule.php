<?php
use PrestaShop\PrestaShop\Adapter\StockManager;
abstract class PaymentModule extends PaymentModuleCore
{
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = [],
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {
        if (self::DEBUG_MODE) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Function called', 1, null, 'Cart', (int) $id_cart, true);
        }
        if (!isset($this->context)) {
            $this->context = Context::getContext();
        }
        $this->context->cart = new Cart((int) $id_cart);
        $this->context->customer = new Customer((int) $this->context->cart->id_customer);
        $this->context->cart->setTaxCalculationMethod();
        $this->context->language = new Language((int) $this->context->cart->id_lang);
        $this->context->shop = ($shop ? $shop : new Shop((int) $this->context->cart->id_shop));
        ShopUrl::resetMainDomainCache();
        $id_currency = $currency_special ? (int) $currency_special : (int) $this->context->cart->id_currency;
        $this->context->currency = new Currency((int) $id_currency, null, (int) $this->context->shop->id);
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $context_country = $this->context->country;
        }
        $order_status = new OrderState((int) $id_order_state, (int) $this->context->language->id);
        if (!Validate::isLoadedObject($order_status)) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status cannot be loaded', 3, null, 'Cart', (int) $id_cart, true);
            throw new PrestaShopException('Can\'t load Order status');
        }
        if (!$this->active) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Module is not active', 3, null, 'Cart', (int) $id_cart, true);
            die(Tools::displayError());
        }
        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
            if ($secure_key !== false && $secure_key != $this->context->cart->secure_key) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Secure key does not match', 3, null, 'Cart', (int) $id_cart, true);
                die(Tools::displayError());
            }
            $delivery_option_list = $this->context->cart->getDeliveryOptionList();
            $package_list = $this->context->cart->getPackageList();
            $cart_delivery_option = $this->context->cart->getDeliveryOption();
            foreach ($delivery_option_list as $id_address => $package) {
                if (
                    !isset($cart_delivery_option[$id_address])
                    || !array_key_exists($cart_delivery_option[$id_address], $package)
                ) {
                    foreach ($package as $key => $val) {
                        $cart_delivery_option[$id_address] = $key;
                        break;
                    }
                }
            }
            $order_list = array();
            $order_detail_list = array();
            do {
                $reference = Order::generateReference();
            } while (Order::getByReference($reference)->count());
            $this->currentOrderReference = $reference;
            $cart_total_paid = (float) Tools::ps_round(
                (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
                2
            );
            foreach ($cart_delivery_option as $id_address => $key_carriers) {
                foreach ($delivery_option_list[$id_address][$key_carriers]['carrier_list'] as $id_carrier => $data) {
                    foreach ($data['package_list'] as $id_package) {
                        $package_list[$id_address][$id_package]['id_warehouse'] =
                            (int) $this->context->cart->getPackageIdWarehouse(
                                $package_list[$id_address][$id_package],
                                (int) $id_carrier
                            );
                        $package_list[$id_address][$id_package]['id_carrier'] = $id_carrier;
                    }
                }
            }
            CartRule::cleanCache();
            $cart_rules = $this->context->cart->getCartRules();
            foreach ($cart_rules as $cart_rule) {
                if (($rule = new CartRule((int) $cart_rule['obj']->id)) && Validate::isLoadedObject($rule)) {
                    if ($error = $rule->checkValidity($this->context, true, true)) {
                        $this->context->cart->removeCartRule((int) $rule->id);
                        if (isset($this->context->cookie, $this->context->cookie->id_customer) && $this->context->cookie->id_customer && !empty($rule->code)) {
                            Tools::redirect('index.php?controller=order&submitAddDiscount=1&discount_name=' . urlencode($rule->code));
                        } else {
                            $rule_name = isset($rule->name[(int) $this->context->cart->id_lang]) ? $rule->name[(int) $this->context->cart->id_lang] : $rule->code;
                            $error = $this->trans('The cart rule named "%1s" (ID %2s) used in this cart is not valid and has been withdrawn from cart', [$rule_name, (int) $rule->id], 'Admin.Payment.Notification');
                            PrestaShopLogger::addLog($error, 3, '0000002', 'Cart', (int) $this->context->cart->id);
                        }
                    }
                }
            }
            if ($order_status->logable && number_format($cart_total_paid, Context::getContext()->getComputingPrecision()) != number_format($amount_paid, _PS_PRICE_COMPUTE_PRECISION_)) {
                $id_order_state = Configuration::get('PS_OS_ERROR');
            }
            foreach ($package_list as $id_address => $packageByAddress) {
                foreach ($packageByAddress as $id_package => $package) {
                    $orderData = $this->createOrderFromCart(
                        $this->context->cart,
                        $this->context->currency,
                        $package['product_list'],
                        $id_address,
                        $this->context,
                        $reference,
                        $secure_key,
                        $payment_method,
                        $this->name,
                        $dont_touch_amount,
                        $amount_paid,
                        $package_list[$id_address][$id_package]['id_warehouse'],
                        $cart_total_paid,
                        self::DEBUG_MODE,
                        $order_status,
                        $id_order_state,
                        isset($package['id_carrier']) ? $package['id_carrier'] : null
                    );
                    $order = $orderData['order'];
                    $order_list[] = $order;
                    $order_detail_list[] = $orderData['orderDetail'];
                }
            }
            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                $this->context->country = $context_country;
            }
            if (!$this->context->country->active) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Country is not active', 3, null, 'Cart', (int) $id_cart, true);
                throw new PrestaShopException('The order address country is not active.');
            }
            if (self::DEBUG_MODE) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Payment is about to be added', 1, null, 'Cart', (int) $id_cart, true);
            }
            if ($order_status->logable) {
                if (isset($extra_vars['transaction_id'])) {
                    $transaction_id = $extra_vars['transaction_id'];
                } else {
                    $transaction_id = null;
                }
                if (!isset($order) || !$order->addOrderPayment($amount_paid, null, $transaction_id)) {
                    PrestaShopLogger::addLog('PaymentModule::validateOrder - Cannot save Order Payment', 3, null, 'Cart', (int) $id_cart, true);
                    throw new PrestaShopException('Can\'t save Order Payment');
                }
            }
            $only_one_gift = false;
            $products = $this->context->cart->getProducts();
            CartRule::cleanCache();
            foreach ($order_detail_list as $key => $order_detail) {
                $order = $order_list[$key];
                if (isset($order->id)) {
                    if (!$secure_key) {
                        $message .= '<br />' . $this->trans('Warning: the secure key is empty, check your payment account before validation', [], 'Admin.Payment.Notification');
                    }
                    if (!empty($message)) {
                        $msg = new Message();
                        $message = strip_tags($message, '<br>');
                        if (Validate::isCleanHtml($message)) {
                            if (self::DEBUG_MODE) {
                                PrestaShopLogger::addLog('PaymentModule::validateOrder - Message is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                            }
                            $msg->message = $message;
                            $msg->id_cart = (int) $id_cart;
                            $msg->id_customer = (int) ($order->id_customer);
                            $msg->id_order = (int) $order->id;
                            $msg->private = 1;
                            $msg->add();
                        }
                    }
                    $products_list = '';
                    $virtual_product = true;
                    $product_var_tpl_list = [];
                    foreach ($order->product_list as $product) {
                        $price = Product::getPriceStatic((int) $product['id_product'], false, ($product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null), 6, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $specific_price, true, true, null, true, $product['id_customization']);
                        $price_wt = Product::getPriceStatic((int) $product['id_product'], true, ($product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null), 2, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $specific_price, true, true, null, true, $product['id_customization']);
                        $product_price = Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, Context::getContext()->getComputingPrecision()) : $price_wt;
                        $product_var_tpl = [
                            'id_product' => $product['id_product'],
                            'id_product_attribute' => $product['id_product_attribute'],
                            'reference' => $product['reference'],
                            'name' => $product['name'] . (isset($product['attributes']) ? ' - ' . $product['attributes'] : ''),
                            'price' => Tools::getContextLocale($this->context)->formatPrice($product_price * $product['quantity'], $this->context->currency->iso_code),
                            'quantity' => $product['quantity'],
                            'customization' => [],
                        ];
                        if (isset($product['price']) && $product['price']) {
                            $product_var_tpl['unit_price'] = Tools::getContextLocale($this->context)->formatPrice($product_price, $this->context->currency->iso_code);
                            $product_var_tpl['unit_price_full'] = Tools::getContextLocale($this->context)->formatPrice($product_price, $this->context->currency->iso_code)
                                . ' ' . $product['unity'];
                        } else {
                            $product_var_tpl['unit_price'] = $product_var_tpl['unit_price_full'] = '';
                        }
                        $customized_datas = Product::getAllCustomizedDatas((int) $order->id_cart, null, true, null, (int) $product['id_customization']);
                        if (isset($customized_datas[$product['id_product']][$product['id_product_attribute']])) {
                            $product_var_tpl['customization'] = [];
                            foreach ($customized_datas[$product['id_product']][$product['id_product_attribute']][$order->id_address_delivery] as $customization) {
                                $customization_text = '';
                                if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD])) {
                                    foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text) {
                                        $customization_text .= '<strong>' . $text['name'] . '</strong>: ' . $text['value'] . '<br />';
                                    }
                                }
                                if (isset($customization['datas'][Product::CUSTOMIZE_FILE])) {
                                    $customization_text .= $this->trans('%d image(s)', [count($customization['datas'][Product::CUSTOMIZE_FILE])], 'Admin.Payment.Notification') . '<br />';
                                }
                                $customization_quantity = (int) $customization['quantity'];
                                $product_var_tpl['customization'][] = [
                                    'customization_text' => $customization_text,
                                    'customization_quantity' => $customization_quantity,
                                    'quantity' => Tools::getContextLocale($this->context)->formatPrice($customization_quantity * $product_price, $this->context->currency->iso_code),
                                ];
                            }
                        }
                        $product_var_tpl_list[] = $product_var_tpl;
                        if (!$product['is_virtual']) {
                            $virtual_product &= false;
                        }
                    } // end foreach ($products)
                    $product_list_txt = '';
                    $product_list_html = '';
                    if (count($product_var_tpl_list) > 0) {
                        $product_list_txt = $this->getEmailTemplateContent('order_conf_product_list.txt', Mail::TYPE_TEXT, $product_var_tpl_list);
                        $product_list_html = $this->getEmailTemplateContent('order_conf_product_list.tpl', Mail::TYPE_HTML, $product_var_tpl_list);
                    }
                    $total_reduction_value_ti = 0;
                    $total_reduction_value_tex = 0;
                    $cart_rules_list = $this->createOrderCartRules(
                        $order,
                        $this->context->cart,
                        $order_list,
                        $total_reduction_value_ti,
                        $total_reduction_value_tex,
                        $id_order_state
                    );
                    $cart_rules_list_txt = '';
                    $cart_rules_list_html = '';
                    if (count($cart_rules_list) > 0) {
                        $cart_rules_list_txt = $this->getEmailTemplateContent('order_conf_cart_rules.txt', Mail::TYPE_TEXT, $cart_rules_list);
                        $cart_rules_list_html = $this->getEmailTemplateContent('order_conf_cart_rules.tpl', Mail::TYPE_HTML, $cart_rules_list);
                    }
                    $old_message = Message::getMessageByCartId((int) $this->context->cart->id);
                    if ($old_message && !$old_message['private']) {
                        $update_message = new Message((int) $old_message['id_message']);
                        $update_message->id_order = (int) $order->id;
                        $update_message->update();
                        $customer_thread = new CustomerThread();
                        $customer_thread->id_contact = 0;
                        $customer_thread->id_customer = (int) $order->id_customer;
                        $customer_thread->id_shop = (int) $this->context->shop->id;
                        $customer_thread->id_order = (int) $order->id;
                        $customer_thread->id_lang = (int) $this->context->language->id;
                        $customer_thread->email = $this->context->customer->email;
                        $customer_thread->status = 'open';
                        $customer_thread->token = Tools::passwdGen(12);
                        $customer_thread->add();
                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = 0;
                        $customer_message->message = $update_message->message;
                        $customer_message->private = 0;
                        if (!$customer_message->add()) {
                            $this->errors[] = $this->trans('An error occurred while saving message', [], 'Admin.Payment.Notification');
                        }
                    }
                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Hook validateOrder is about to be called', 1, null, 'Cart', (int) $id_cart, true);
                    }
                    Hook::exec('actionValidateOrder', [
                        'cart' => $this->context->cart,
                        'order' => $order,
                        'customer' => $this->context->customer,
                        'currency' => $this->context->currency,
                        'orderStatus' => $order_status,
                    ]);
                    foreach ($this->context->cart->getProducts() as $product) {
                        if ($order_status->logable) {
                            ProductSale::addProductSale((int) $product['id_product'], (int) $product['cart_quantity']);
                        }
                    }
                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                    }
                    $new_history = new OrderHistory();
                    $new_history->id_order = (int) $order->id;
                    $new_history->changeIdOrderState((int) $id_order_state, $order, true);
                    $new_history->addWithemail(true, $extra_vars);
                    if (Configuration::get('PS_STOCK_MANAGEMENT') &&
                        ($order_detail->getStockState() ||
                            $order_detail->product_quantity_in_stock < 0)) {
                        $history = new OrderHistory();
                        $history->id_order = (int) $order->id;
                        $history->changeIdOrderState(Configuration::get($order->hasBeenPaid() ? 'PS_OS_OUTOFSTOCK_PAID' : 'PS_OS_OUTOFSTOCK_UNPAID'), $order, true);
                        $history->addWithemail();
                    }
                    unset($order_detail);
                    $order = new Order((int) $order->id);
                    if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && $this->context->customer->id) {
                        $invoice = new Address((int) $order->id_address_invoice);
                        $delivery = new Address((int) $order->id_address_delivery);
                        $delivery_state = $delivery->id_state ? new State((int) $delivery->id_state) : false;
                        $invoice_state = $invoice->id_state ? new State((int) $invoice->id_state) : false;
                        $carrier = $order->id_carri17	6835fc97868c0	APEAGUHAV	gun	0	896291	762586	945453	2025-05-27 17:55:35.000	2025-11-24 11:44:46.000	2025-11-24 12:44:46.000
18	6836058980eaf	PYMQHXDEC	gun	0	896302	762594	945644	2025-05-27 18:33:45.000	2025-11-24 10:16:35.000
19	68360a185ac39	BUUYBWBHK	gun	0	269575	762597	945670	2025-05-27 18:53:12.000	2025-11-24 10:16:35.000
20	683622d7e765e	RHKLNCZSK	gun	0	896324	762616	945692	2025-05-27 20:38:47.000	2025-11-24 10:16:35.000
21	683631f152099	PRRDAYAKB	gun	0	895159	762627	945116	2025-05-27 21:43:13.000	2025-11-24 10:16:36.000
22	68363c6b4bb08	RLPTUIDUO	gun	0	896344	762634	945079	2025-05-27 22:27:55.000	2025-11-24 11:40:56.000	2025-11-24 12:40:56.000
23	6836c4cfdd9a9	YLELNCYZV	dni	0	812519	761445	939636	2025-05-28 08:09:51.000	2025-11-24 10:16:36.000
24	6836c7e4b1c0c	DVOLZLPEQ	gun	0	135150	762656	946009	2025-05-28 08:23:00.000	2025-11-24 10:16:36.000	2025-11-24 10:54:41.000
26	6836dd4a5699b	MGUEBYVSO	gun	0	812519	762670	940608	2025-05-28 09:54:18.000	2025-11-24 10:16:36.000
27	6836e30a27619	BBERWPDSG	dni	0	812519	762675	946121	2025-05-28 10:18:50.000	2025-11-24 10:16:36.000
28	6836f8e69170e	XGKRWCQMS	dni	0	896361	762693	946196	2025-05-28 11:52:06.000	2025-11-24 10:16:36.000
29	68371c71a61d5	ALONVVEEO	dni	0	896411	762711	946301	2025-05-28 14:23:45.000	2025-11-24 10:16:36.000
30	68373cc430d55	PWOUMAQHW	dni	0	896423	762732	946447	2025-05-28 16:41:40.000	2025-11-24 10:16:36.000
31	68380862de088	NFGXNIVXC	dni	0	896483	762795	946802	2025-05-29 07:10:26.000	2025-11-24 10:16:36.000
32	683898ac78781	VIPPCPXEF	dni	0	896546	762864	947178	2025-05-29 17:26:04.000	2025-11-24 10:16:36.000
33	6838d3b8d3a6e	TTNZJCJTZ	dni	0	896569	762888	947350	2025-05-29 21:38:00.000	2025-11-24 10:16:36.000
34	6838ea6cd993b	JPOSQBGJQ	dni	0	852022	762894	947386	2025-05-29 23:14:52.000	2025-11-24 10:16:36.000
35	6838eb9ebab59	RSCOAQNLP	dni	0	896574	762895	947382	2025-05-29 23:19:58.000	2025-11-24 10:16:36.000
36	6838f7a48f4f7	ORXFHJOBE	dni	0	896575	762896	942920	2025-05-30 00:11:16.000	2025-11-24 10:16:37.000
37	68394d6911c72	DLJCYWIIV	dni	0	896585	762900	947425	2025-05-30 06:17:13.000	2025-11-24 10:16:37.000
38	68395bdad80f2	OOWDRPLQJ	dni	0	896597	762906	947450	2025-05-30 07:18:50.000	2025-11-24 10:16:37.000
39	68397aa92f511	UCCALWRKB	escopeta	0	233153	762922	947522	2025-05-30 09:30:17.000	2025-11-24 10:16:37.000
40	683993318450c	SELHSUMLJ	dni	0	896623	762938	947588	2025-05-30 11:14:57.000	2025-11-24 10:16:37.000
41	68399751745b2	JRRATFXSM	dni	0	98040	762939	947592	2025-05-30 11:32:33.000	2025-11-24 10:16:37.000
42	683b21f589042	SBEIPZPKJ	corta	0	896731	763063	948212	2025-05-31 15:36:21.000	2025-11-24 10:16:37.000
43	6845b313dc40c	HXGTQUWJI	corta	0	897787	764281	955177	2025-06-08 15:58:11.000	2025-11-24 10:16:37.000
44	6847718b96ca5	PHBUDVLFJ	dni	0	898012	764551	956419	2025-06-09 23:43:07.000	2025-11-24 10:16:37.000
45	684c072a4d38b	ZMCYZUKUH	dni	0	898519	765119	959697	2025-06-13 11:10:34.000	2025-11-24 10:16:37.000
46	684c4daa0a0f4	DNCGENUCX	dni	0	416570	765159	960012	2025-06-13 16:11:22.000	2025-11-24 10:16:37.000
47	684c5a989dc9f	HCXCAWZPL	dni	0	856551	765166	960059	2025-06-13 17:06:32.000	2025-11-24 10:16:38.000
48	684d5e5f11676	YUQPCGVID	dni	0	898616	765225	960495	2025-06-14 11:34:55.000	2025-11-24 10:16:38.000
49	684d79b2bc183	FVHMZBZPJ	dni	0	898629	765236	960619	2025-06-14 13:31:30.000	2025-11-24 10:16:38.000
50	684d807c91cb7	HMOKPQYSW	dni	0	898625	765238	960593	2025-06-14 14:00:28.000	2025-11-24 10:16:38.000
51	684d9a0b24a73	GRRDYXEBG	dni	0	898641	765249	960726	2025-06-14 15:49:31.000	2025-11-24 10:16:38.000
52	684d9a8b5962a	NTPROTRPR	dni	0	197545	765250	960730	2025-06-14 15:51:39.000	2025-11-24 10:16:38.000
53	684dca1ee1ada	VWFXMVCVA	dni	0	305015	765270	960897	2025-06-14 19:14:38.000	2025-11-24 10:16:38.000
54	684de5773de30	UZJXZRXZI	dni	0	409365	765279	960534	2025-06-14 21:11:19.000	2025-11-24 10:16:38.000
55	684e00fd7f21d	RBYNCVOYG	dni	0	898671	765285	961057	2025-06-14 23:08:45.000	2025-11-24 10:16:38.000
56	684e8d6cb431f	TBLJDKNRR	dni	0	898673	765301	961070	2025-06-15 09:07:56.000	2025-11-24 10:16:38.000
57	684e9a9a035ee	RUENMLUHR	dni	0	361995	765307	961228	2025-06-15 10:04:10.000	2025-11-24 10:16:38.000
58	684eb3ab1226f	NTZGCISJI	dni	0	62674	765323	961308	2025-06-15 11:51:07.000	2025-11-24 10:16:38.000
59	684ed707c966d	XHCNDKBHY	dni	0	898701	765344	961445	2025-06-15 14:21:59.000	2025-11-24 10:16:39.000
60	684ed8399a20c	MBXRWNXYV	dni	0	898713	765346	961428	2025-06-15 14:27:05.000	2025-11-24 10:16:39.000
61	684ee1f1aecc7	XSLALATAD	dni	0	377686	765352	960474	2025-06-15 15:08:33.000	2025-11-24 10:16:39.000
62	684f2a359b963	RBJLVAOMY	dni	0	369508	765394	961797	2025-06-15 20:16:53.000	2025-11-24 10:16:39.000
63	684fc7115a499	THZQINQBM	dni	0	898795	765425	962040	2025-06-16 07:26:09.000	2025-11-24 10:16:39.000
64	684fd29a1c094	DPBDPYDUX	dni	0	898798	765429	961913	2025-06-16 08:15:22.000	2025-11-24 10:16:39.000
65	684fd2e3c4efe	SCLFDWQWL	dni	0	858494	765430	962083	2025-06-16 08:16:35.000	2025-11-24 10:16:39.000
66	684ff5d76fc33	SSAFYPRUD	dni	0	131047	765452	958559	2025-06-16 10:45:43.000	2025-11-24 10:16:39.000
67	684ffa652a04b	XDDTGSFYN	dni	0	898829	765459	962239	2025-06-16 11:05:09.000	2025-11-24 10:16:39.000
68	685000b6b3066	EAQCXGALV	dni	0	898836	765465	961935	2025-06-16 11:32:06.000	2025-11-24 10:16:39.000
69	68502b0d23a54	WBYQHOZFH	dni	0	898868	765493	962462	2025-06-16 14:32:45.000	2025-11-24 10:16:39.000
70	685045fa4e7b1	DIOICLZJA	dni	0	898786	765514	961965	2025-06-16 16:27:38.000	2025-11-24 10:16:39.000
71	685048a532a5e	FXOLZZAPW	dni	0	898883	765518	962618	2025-06-16 16:39:01.000	2025-11-24 10:16:39.000
72	68506177007c3	CLBLUTWZP	dni	0	394740	765540	962751	2025-06-16 18:24:55.000	2025-11-24 10:16:39.000
73	685078c4626e2	VPQUDCZYQ	dni	0	215223	765563	962858	2025-06-16 20:04:20.000	2025-11-24 10:16:39.000
74	68507eb0eb171	HUNDTXSCY	dni	0	377686	765568	962867	2025-06-16 20:29:36.000	2025-11-24 10:16:39.000
75	68509a7c67a90	KQLIYQJJA	dni	0	898943	765585	962978	2025-06-16 22:28:12.000	2025-11-24 10:16:39.000
76	68509df60008b	JMAUFTOWU	dni	0	366360	765586	960579	2025-06-16 22:43:02.000	2025-11-24 10:16:40.000
77	68516eb4ce12a	LCHJICJFH	dni	0	413277	765674	961734	2025-06-17 13:33:40.000	2025-11-24 10:16:40.000
78	68518379990d6	NQUZADJPP	dni	0	899037	765688	962460	2025-06-17 15:02:17.000	2025-11-24 10:16:40.000
79	685187cca6719	UUHOYZZII	dni	0	899038	765690	963541	2025-06-17 15:20:44.000	2025-11-24 10:16:40.000
80	68519965bcf15	VTLSQPRJY	escopeta	0	899049	765703	963625	2025-06-17 16:35:49.000	2025-11-24 10:16:40.000
81	6851b2c228712	DBILYZPUR	dni	0	899064	765721	963708	2025-06-17 18:24:02.000	2025-11-24 10:16:40.000
82	6851d28478eab	TUKDGOBZN	dni	0	899091	765745	963833	2025-06-17 20:39:32.000	2025-11-24 10:16:40.000
83	6851df1897114	GIXZVDXBN	dni	0	899099	765755	963887	2025-06-17 21:33:12.000	2025-11-24 10:16:40.000
84	6851e3da49207	MUAFYBPFG	dni	0	899106	765762	963912	2025-06-17 21:53:30.000	2025-11-24 10:16:40.000
85	685253da8fe0f	ARHISLLSL	escopeta	0	899081	765771	963785	2025-06-18 05:51:22.000	2025-11-24 10:16:40.000
86	68525739c317d	ZZITFYOMD	dni	0	899126	765776	963519	2025-06-18 06:05:45.000	2025-11-24 10:16:40.000
87	6852667ce27e6	NRYQIRKEO	escopeta	0	899045	765782	963587	2025-06-18 07:10:52.000	2025-11-24 10:16:40.000
88	685279c7283a6	TZVGTSOUX	dni	0	885311	765791	964046	2025-06-18 08:33:11.000	2025-11-24 10:16:40.000
89	6852e8429c834	TRGCVDYTF	dni	0	899197	765862	964416	2025-06-18 16:24:34.000	2025-11-24 10:16:40.000
90	68533aa04ca41	HDDLEPTWD	dni	0	308719	765920	953070	2025-06-18 22:16:00.000	2025-11-24 10:16:40.000
91	685373a58ab2e	FUDHWJUYA	dni	0	899011	765924	963360	2025-06-19 02:19:17.000	2025-11-24 10:16:40.000
92	6853ec0a7fbaa	EICTGJYOL	dni	0	826604	765969	964938	2025-06-19 10:52:58.000	2025-11-24 10:16:40.000
93	6853f36810e02	DWWRQELNU	dni	0	100682	765972	959072	2025-06-19 11:24:24.000	2025-11-24 10:16:41.000
94	685403561298b	UUGAKWVDN	dni	0	894233	765982	963847	2025-06-19 12:32:22.000	2025-11-24 10:16:41.000
95	685403aa8489a	AZBSLXADO	escopeta	0	226802	765984	964989	2025-06-19 12:33:46.000	2025-11-24 10:16:41.000
96	68545b55cfba8	KIOUVGAEI	dni	0	865658	766044	965211	2025-06-19 18:47:49.000	2025-11-24 10:16:41.000
97	6854707c617aa	QONPFSRMI	dni	0	899337	766057	965383	2025-06-19 20:18:04.000	2025-11-24 10:16:41.000
98	68547c197e5a0	UTBUQTVXP	dni	0	899340	766063	962977	2025-06-19 21:07:37.000	2025-11-24 10:16:41.000
99	685540a2dd4ca	PLFMHUHBA	dni	0	899399	766125	964942	2025-06-20 11:06:10.000	2025-11-24 10:16:41.000
100	6855493571d4a	NBYXTAMLA	dni	0	396669	766127	965390	2025-06-20 11:42:45.000	2025-11-24 10:16:41.000
101	685568886107b	QWONLJVKK	dni	0	182904	766137	965793	2025-06-20 13:56:24.000	2025-11-24 10:16:41.000
102	6856994e7bd5e	CLREGLRKX	dni	0	899486	766223	965197	2025-06-21 11:36:46.000	2025-11-24 10:16:41.000
103	685820255a7ad	DIJTDVCAW	dni	0	899430	766326	965899	2025-06-22 15:24:21.000	2025-11-24 10:16:41.000
104	68584219e1f98	AJUFRZVLR	dni	0	899588	766351	963424	2025-06-22 17:49:13.000	2025-11-24 10:16:41.000
105	68596d6933b47	NUEDOKPDS	dni	0	860470	766491	967764	2025-06-23 15:06:17.000	2025-11-24 10:16:41.000
106	6859a16f17ecc	GADPDLFAC	dni	0	337781	766532	966760	2025-06-23 18:48:15.000	2025-11-24 10:16:41.000
107	6859e24fd5d0e	EMTVMBWKD	dni	0	874514	766571	968133	2025-06-23 23:25:03.000	2025-11-24 10:16:41.000
108	685a61550fc0e	HPRLVOUYT	dni	0	899758	766588	968109	2025-06-24 08:27:01.000	2025-11-24 10:16:42.000
109	685a6da1bbbca	BKRCPQLZL	dni	0	899784	766597	968276	2025-06-24 09:19:29.000	2025-11-24 10:16:42.000
110	685a75dc5716f	SCUCNZYWC	dni	0	899789	766602	967240	2025-06-24 09:54:36.000	2025-11-24 10:16:42.000
111	685abe00ae8f3	XGEIGEALK	dni	0	899549	766649	968325	2025-06-24 15:02:24.000	2025-11-24 10:16:42.000
112	685acfb138878	JTVLYFJVV	dni	0	899833	766661	968589	2025-06-24 16:17:53.000	2025-11-24 10:16:42.000
113	685b4520c7239	GHIEWEIXX	dni	0	899891	766730	968947	2025-06-25 00:38:56.000	2025-11-24 10:16:42.000
115	685bbfc11eae7	OIEIMDFDC	dni	0	899926	766759	957296	2025-06-25 09:22:09.000	2025-11-24 10:16:42.000
116	685bd4334952d	OXXWSXBAD	dni	0	414697	766770	967709	2025-06-25 10:49:23.000	2025-11-24 10:16:42.000
117	685be7008e14a	WNNAOEZWM	dni	0	899945	766778	969179	2025-06-25 12:09:36.000	2025-11-24 10:16:42.000
118	685bf461d7ea1	MHLFHGUYT	dni	0	899840	766783	968625	2025-06-25 13:06:41.000	2025-11-24 10:16:42.000
119	685c35bd3bd2e	NSGIKCXWT	dni	0	352756	766815	969307	2025-06-25 17:45:33.000	2025-11-24 10:16:42.000
120	685c3c5b1875d	IYZSOKALQ	dni	0	884849	766821	969418	2025-06-25 18:13:47.000	2025-11-24 10:16:42.000
121	685c4476d2a9d	EFAZJEKJF	dni	0	899989	766827	969508	2025-06-25 18:48:22.000	2025-11-24 10:16:43.000
122	685c63ea075f4	TFZITUMCS	dni	0	297225	766864	969289	2025-06-25 21:02:34.000	2025-11-24 10:16:43.000
123	685c672fc9d13	BBWOUJZHZ	dni	0	900008	766865	969616	2025-06-25 21:16:31.000	2025-11-24 10:16:43.000
124	685c915b99cfd	XSAKKQUGH	dni	0	900018	766879	969708	2025-06-26 00:16:27.000	2025-11-24 10:16:43.000
125	685d3230be965	EYHCEFKXH	dni	0	900062	766922	968948	2025-06-26 11:42:40.000	2025-11-24 10:16:43.000
126	685d62446be97	NCXMMFIQE	dni	0	856860	766954	970114	2025-06-26 15:07:48.000	2025-11-24 10:16:43.000
127	685d71ca2cc3b	UDOBRQEKZ	dni	0	900102	766966	970171	2025-06-26 16:14:02.000	2025-11-24 10:16:43.000
128	685daa9e57d67	YCZBDIYSA	dni	0	900122	767006	970304	2025-06-26 20:16:30.000	2025-11-24 10:16:43.000
129	685dc6185e89b	SVQZGUKLF	dni	0	900016	767022	969682	2025-06-26 22:13:44.000	2025-11-24 10:16:43.000
130	685e77d5e3e6a	MGJPSMGCN	dni	0	881063	767072	970701	2025-06-27 10:52:05.000	2025-11-24 10:16:43.000
132	686224e245221	DRVMAMTGA	dni	0	349602	767370	965609	2025-06-30 05:47:14.000	2025-11-24 10:16:43.000
133	6862311d0af0d	VZFRLUHQK	rifle	0	852904	767374	972398	2025-06-30 06:39:25.000	2025-11-24 10:16:43.000
136	6864188936d1e	HIRNAHKGE	dni	0	278370	767692	974002	2025-07-01 17:19:05.000	2025-11-24 10:16:43.000
138	686500adac8da	EIZMXGFOA	dni	0	317706	767783	974433	2025-07-02 09:49:33.000	2025-11-24 10:16:43.000
139	68655d7d48825	AQAAYAUXI	dni	0	900874	767875	973707	2025-07-02 16:25:33.000	2025-11-24 10:16:44.000
140	6865795369fae	ASNCNVNJV	dni	0	900894	767894	974941	2025-07-02 18:24:19.000	2025-11-24 10:16:44.000
141	686585a848118	JUJEFRTPP	dni	0	900903	767902	974983	2025-07-02 19:16:56.000	2025-11-24 10:16:44.000
142	6865a29d7ac47	VTFMUJNYZ	dni	0	900928	767929	975080	2025-07-02 21:20:29.000	2025-11-24 10:16:44.000
143	6865b8e106bbc	RMZQOJLKK	dni	1	804351	767939	975144	2025-07-02 22:55:29.000	2025-11-24 10:16:44.000	2025-07-10 13:22:31.000
144	68664ed935bcc	DLGWNOWEO	escopeta	0	863971	767978	974559	2025-07-03 09:35:21.000	2025-11-24 10:16:44.000
145	686655ff3deca	ESDPGKVUR	dni	0	900980	767985	975235	2025-07-03 10:05:51.000	2025-11-24 10:16:44.000
146	686661bf7d713	JPSOPRBXF	dni	0	900991	767996	974615	2025-07-03 10:55:59.000	2025-11-24 10:16:44.000
147	68666a501df88	VXQJVNEST	dni	0	900983	768003	974829	2025-07-03 11:32:32.000	2025-11-24 10:16:44.000
148	68669c326aa67	APKFIBWRG	dni	0	901025	768038	975582	2025-07-03 15:05:22.000	2025-11-24 10:16:44.000
149	6866a21386a21	OARXTDBCE	dni	0	901032	768046	975613	2025-07-03 15:30:27.000	2025-11-24 10:16:44.000
150	6866d832ccb91	EKAWWIJZZ	dni	0	900598	768068	974073	2025-07-03 19:21:22.000	2025-11-24 10:16:44.000
151	6866ff0486d0d	DUKZCZLFU	dni	0	901073	768087	975821	2025-07-03 22:07:00.000	2025-11-24 10:16:44.000
152	6867a785200e9	JSLMPKHXK	dni	0	892995	768132	974446	2025-07-04 10:05:57.000	2025-11-24 10:16:44.000
153	6867de9094d25	NWOMBMYCU	dni	0	901133	768176	976362	2025-07-04 14:00:48.000	2025-11-24 10:16:44.000
154	6867e9e0a1aab	LHUISCNBH	dni	0	901137	768184	976386	2025-07-04 14:49:04.000	2025-11-24 10:16:45.000
155	6867edba86ce9	UATNXGCNS	dni	0	800282	768187	975119	2025-07-04 15:05:30.000	2025-11-24 10:16:45.000
156	6867f30b15cf1	DVMSSGQMC	dni	0	901144	768191	976425	2025-07-04 15:28:11.000	2025-11-24 10:16:45.000
157	686804626b221	LZPGDBLKZ	dni	0	374063	768204	976227	2025-07-04 16:42:10.000	2025-11-24 10:16:45.000
158	68686406de9a1	CNYTEGASB	dni	0	901198	768253	976726	2025-07-04 23:30:14.000	2025-11-24 10:16:45.000
159	686866005c5f4	CWQJLBPXA	dni	0	901199	768254	973352	2025-07-04 23:38:40.000	2025-11-24 10:16:45.000
160	6868cbba7981d	UIRGLCIOD	dni	0	901205	768264	976769	2025-07-05 06:52:42.000	2025-11-24 10:16:45.000
161	6868eb098a3fd	ZBMCMMRFU	dni	0	901215	768275	976831	2025-07-05 09:06:17.000	2025-11-24 10:16:45.000
162	68693be972511	RRXGUVJLA	dni	0	901253	768314	977026	2025-07-05 14:51:21.000	2025-11-24 10:16:45.000
163	6869748f62b90	ZBFIGKWIV	dni	0	901281	768347	976813	2025-07-05 18:53:03.000	2025-11-24 10:16:45.000
164	686974b523268	EFUHQGYNW	dni	0	901283	768348	977179	2025-07-05 18:53:41.000	2025-11-24 10:16:45.000
165	686977e29e679	MJZZGJQJN	dni	0	81354	768352	977161	2025-07-05 19:07:14.000	2025-11-24 10:16:45.000
166	686a4d810acee	HCULUOIYA	dni	0	901318	768388	977367	2025-07-06 10:18:41.000	2025-11-24 10:16:46.000
167	686a57f24a20a	POCBKDZQA	rifle	0	900878	768395	974838	2025-07-06 11:03:14.000	2025-11-24 10:16:46.000
168	686a7f465c68f	HYLJRTCIM	dni	0	901338	768414	954141	2025-07-06 13:51:02.000	2025-11-24 10:16:46.000
169	686a9bdcc5a00	JCGZWUTGY	dni	0	901353	768430	977624	2025-07-06 15:53:00.000	2025-11-24 10:16:46.000
170	686aaaa42849d	NJLECFYYY	dni	0	889775	768442	977645	2025-07-06 16:56:04.000	2025-11-24 10:16:46.000
171	686aec82250c2	YGGOBEJNQ	dni	0	901404	768498	977864	2025-07-06 21:37:06.000	2025-11-24 10:16:46.000
172	686b94cb255b2	UPFBDNJUM	dni	0	901451	768553	978164	2025-07-07 09:35:07.000	2025-11-24 10:16:46.000
173	686b955de5f59	ZOCHQPQRK	dni	0	901453	768555	978158	2025-07-07 09:37:33.000	2025-11-24 10:16:46.000
174	686bbd0e83ea5	DXPUOLOMF	dni	0	901481	768585	978357	2025-07-07 12:26:54.000	2025-11-24 10:16:46.000
175	686bc50c41071	VFZHZZLRY	dni	0	901485	768588	978400	2025-07-07 13:01:00.000	2025-11-24 10:16:46.000
176	686bc6e6e6757	RJIZHMBGW	dni	0	901486	768592	978386	2025-07-07 13:08:54.000	2025-11-24 10:16:46.000
177	686bd0504149d	RMXVEJLAX	rifle	0	900878	768603	977423	2025-07-07 13:49:04.000	2025-11-24 10:16:47.000
178	686bd13c52d4e	VVCNTEUHJ	dni	0	901493	768607	974507	2025-07-07 13:53:00.000	2025-11-24 10:16:47.000
179	686bd2868b2ab	SXBJOBPAZ	dni	0	901494	768610	978460	2025-07-07 13:58:30.000	2025-11-24 10:16:47.000
180	686bd74f4cb8c	YFMQIGXLJ	dni	0	901343	768616	977283	2025-07-07 14:18:55.000	2025-11-24 10:16:47.000
181	686be17046473	DPLVTDMRW	dni	0	901479	768624	978553	2025-07-07 15:02:08.000	2025-11-24 10:16:47.000
182	686bf421687cf	TUYZKHUUO	dni	0	901521	768639	978642	2025-07-07 16:21:53.000	2025-11-24 10:16:47.000
183	686bfee82c700	OXWGXDDNF	dni	0	901529	768649	978681	2025-07-07 17:07:52.000	2025-11-24 10:16:47.000
184	686c07fa1b028	QRSWTYJQC	dni	1	901539	768662	978744	2025-07-07 17:46:34.000	2025-11-24 10:16:47.000	2025-07-11 07:54:48.000
185	686c0b6e5527b	AKAMOUGOB	dni	0	901540	768663	978752	2025-07-07 18:01:18.000	2025-11-24 10:16:47.000
186	686c18fb85f15	HCWDCBLXE	dni	0	200790	768668	978793	2025-07-07 18:59:07.000	2025-11-24 10:16:47.000
187	686c24538de78	TSPUOKYVA	dni	0	901561	768682	977838	2025-07-07 19:47:31.000	2025-11-24 10:16:47.000
188	686c4b5b56410	NKOHBWPKP	dni	0	901348	768715	977578	2025-07-07 22:34:03.000	2025-11-24 10:16:47.000
189	686c5732833b5	XQPTLDTLS	dni	0	901578	768720	978974	2025-07-07 23:24:34.000	2025-11-24 10:16:47.000
190	686c70a54a1ac	AJLHVUGHF	dni	0	901590	768723	979042	2025-07-08 01:13:09.000	2025-11-24 10:16:47.000
191	686cdc7acb907	QUFNHPJPH	dni	0	901607	768756	979171	2025-07-08 08:53:14.000	2025-11-24 10:16:47.000
192	686d01abbb41b	VFRQCHBPX	dni	0	901631	768786	979286	2025-07-08 11:31:55.000	2025-11-24 10:16:48.000
193	686d0502bfc2c	UMTOZOQFO	dni	0	900459	768790	972479	2025-07-08 11:46:10.000	2025-11-24 10:16:48.000
194	686d067136a23	YZLKQAJTT	dni	0	809494	768791	977248	2025-07-08 11:52:17.000	2025-11-24 10:16:48.000
195	686d07a33aa34	OKFPRMAFX	dni	0	901640	768794	979323	2025-07-08 11:57:23.000	2025-11-24 10:16:48.000
196	686d09f1d9dad	CQWLUPPIB	dni	1	901641	768795	979330	2025-07-08 12:07:13.000	2025-11-24 10:16:48.000	2025-07-10 14:34:15.000
197	686d43fb610d2	ASTSCOYSG	dni	0	362675	768843	979502	2025-07-08 16:14:51.000	2025-11-24 10:16:48.000
198	686d58edaf012	KGGOATQMM	dni	1	901692	768862	979600	2025-07-08 17:44:13.000	2025-11-24 10:16:48.000	2025-07-11 20:31:40.000
199	686d5a1f8be83	HTNKESJBM	dni	1	901693	768864	979594	2025-07-08 17:49:19.000	2025-11-24 10:16:48.000	2025-07-14 20:05:45.000
200	686d654c0736c	CGOTZEFRK	dni	0	901703	768875	979620	2025-07-08 18:37:00.000	2025-11-24 10:16:48.000
201	686d6821717e1	CFLWMHTNW	dni	0	901704	768876	979664	2025-07-08 18:49:05.000	2025-11-24 10:16:48.000
202	686d70eeee9ac	EUKUALUOO	dni	0	901709	768886	979693	2025-07-08 19:26:38.000	2025-11-24 10:16:48.000
203	686d744be4f44	IWPAJSZQW	dni	0	901606	768889	978956	2025-07-08 19:40:59.000	2025-11-24 10:16:48.000
204	686e03fe79cf7	DPLGNUYFL	dni	0	901743	768918	979896	2025-07-09 05:54:06.000	2025-11-24 10:16:48.000
205	686e09832cade	DMJTIEENC	dni	0	901745	768921	979903	2025-07-09 06:17:39.000	2025-11-24 10:16:48.000
206	686e2e675b42c	OOQVJPRVE	dni	0	901761	768941	980006	2025-07-09 08:55:03.000	2025-11-24 10:16:48.000
207	686e74088a016	KLJKBLSJZ	dni	0	901812	768998	980073	2025-07-09 13:52:08.000	2025-11-24 10:16:49.000
208	686e7b369b64b	VXJJSMSUD	dni	1	900085	769005	980283	2025-07-09 14:22:46.000	2025-11-24 10:16:49.000	2025-07-09 16:23:12.000
209	686ea06a28342	ROMFJYSDC	dni	0	415497	769049	980356	2025-07-09 17:01:30.000	2025-11-24 10:16:49.000
210	686ec365bf71b	MVZZUEPYM	dni	0	901868	769083	980526	2025-07-09 19:30:45.000	2025-11-24 10:16:49.000
211	686ed58e77b4f	VIRWXPHCW	dni	0	901887	769099	980629	2025-07-09 20:48:14.000	2025-11-24 10:16:49.000
212	686edd3818163	ZCBLGHIFG	dni	0	175406	769104	980617	2025-07-09 21:20:56.000	2025-11-24 10:16:49.000
213	686ef112012fd	RJUZSAMXQ	dni	0	901898	769114	980698	2025-07-09 22:45:38.000	2025-11-24 10:16:49.000
214	686ef49baded5	MGOXVXUFJ	dni	0	901903	769117	980708	2025-07-09 23:00:43.000	2025-11-24 10:16:49.000
215	686ef96cde046	GSIMOVAGR	dni	1	901904	769120	980721	2025-07-09 23:21:16.000	2025-11-24 10:16:49.000
216	686f02c32559c	FFURVZQTT	dni	0	901906	769123	980745	2025-07-10 00:01:07.000	2025-11-24 10:16:49.000
217	686f17745313b	CMSOZMDJX	dni	1	901909	769126	980755	2025-07-10 01:29:24.000	2025-11-24 10:16:49.000	2025-07-10 16:06:02.000
218	686f6e8c431f4	PIZAECGPL	dni	1	901905	769139	980728	2025-07-10 07:41:00.000	2025-11-24 10:16:49.000	2025-07-10 14:58:22.000
219	686f969482f0e	YENEQIAIG	dni	1	901931	769167	980912	2025-07-10 10:31:48.000	2025-11-24 10:16:49.000	2025-07-10 12:54:52.000
220	686f9a3ed74b0	MXFAGOQDO	dni	0	901919	769170	980878	2025-07-10 10:47:26.000	2025-11-24 10:16:49.000
221	686fa103ec107	ROZWYXMBU	dni	1	901939	769172	980963	2025-07-10 11:16:19.000	2025-11-24 10:16:50.000	2025-07-10 13:26:53.000
222	686fa89411b76	SZIBKILXP	dni	0	892545	769179	980964	2025-07-10 11:48:36.000	2025-11-24 10:16:50.000
223	686fc806644ae	WUCHPIEEV	dni	1	901957	769195	980811	2025-07-10 14:02:46.000	2025-11-24 10:16:50.000	2025-07-10 16:11:08.000
224	686fd534810a0	HZSRYSXFG	dni	0	901966	769206	981151	2025-07-10 14:59:00.000	2025-11-24 10:16:50.000
225	686fd8b1d5e69	WZFCUVCJJ	dni	0	901966	769211	981172	2025-07-10 15:13:53.000	2025-11-24 10:16:50.000
226	686fdb285e792	TBGEBFHYY	dni	0	901971	769212	981143	2025-07-10 15:24:24.000	2025-11-24 10:16:50.000
227	686fef9dabb14	IRMPPVLBU	dni	0	901979	769226	981269	2025-07-10 16:51:41.000	2025-11-24 10:16:50.000
228	686ff140e4f89	MXOESMLTC	dni	1	900980	769229	976463	2025-07-10 16:58:40.000	2025-11-24 10:16:50.000	2025-07-10 19:02:44.000
229	68700a32bbd72	MOEVRZZPA	dni	0	901652	769255	979403	2025-07-10 18:45:06.000	2025-11-24 10:16:50.000
230	687043f020808	LTFGTQTHV	dni	1	902031	769287	981523	2025-07-10 22:51:28.000	2025-11-24 10:16:50.000	2025-07-11 01:05:32.000
231	687056e17d58d	XAVGNNVBJ	dni	1	389175	769290	976733	2025-07-11 00:12:17.000	2025-11-24 10:16:50.000	2025-07-11 02:15:12.000
232	6870cb27a0806	HZRTNOXQU	dni	0	902047	769310	981645	2025-07-11 08:28:23.000	2025-11-24 10:16:50.000
233	6870d9d2877cc	JJLOORMRR	dni	0	902068	769324	981695	2025-07-11 09:30:58.000	2025-11-24 10:16:50.000
234	687163ee64284	YHIYQMWRI	dni	0	902140	769401	982050	2025-07-11 19:20:14.000	2025-11-24 10:16:50.000
235	6871967a557e5	HPUZBXJGI	dni	0	900241	769415	982167	2025-07-11 22:55:54.000	2025-11-24 10:16:50.000
236	6872a0db15beb	GKPCGXXVZ	dni	1	408620	769494	981796	2025-07-12 17:52:27.000	2025-11-24 10:16:50.000	2025-07-20 17:03:13.000
237	6872d62c419dd	WXHGDORYA	dni	0	902234	769514	982657	2025-07-12 21:39:56.000	2025-11-24 10:16:50.000
238	6872dccd4be7c	DLOEMGMOK	dni	1	902236	769515	982239	2025-07-12 22:08:13.000	2025-11-24 10:16:51.000	2025-07-13 08:24:47.000
239	68738a5a1a631	OAMQZIWKK	dni	0	828552	769536	982942	2025-07-13 10:28:42.000	2025-11-24 10:16:51.000
240	6873b8837f718	AAOVNNSHG	dni	1	413871	769565	983067	2025-07-13 13:45:39.000	2025-11-24 10:16:51.000	2025-07-22 09:18:55.000
241	6873bdd23e997	QZBTABUKS	corta	0	902278	769567	983095	2025-07-13 14:08:18.000	2025-11-24 10:16:51.000
242	68741b7a9ef0f	OFXLXUSDZ	dni	1	902338	769641	983432	2025-07-13 20:47:54.000	2025-11-24 10:16:51.000	2025-07-13 22:54:03.000
243	687488a539365	YXFFJMDMP	dni	1	173092	769661	981411	2025-07-14 04:33:41.000	2025-11-24 10:16:51.000	2025-07-14 06:41:58.000
244	6874c27063d10	DVOFIROGF	dni	1	902377	769688	981154	2025-07-14 08:40:16.000	2025-11-24 10:16:51.000	2025-07-14 10:41:37.000
245	6874d65b15c76	QQMGSAOLR	dni	0	855372	769707	983656	2025-07-14 10:05:15.000	2025-11-24 10:16:51.000
246	6874eafce7caa	YUBSSRTTC	dni	1	902411	769724	979617	2025-07-14 11:33:16.000	2025-11-24 10:16:51.000	2025-07-14 13:39:41.000
247	68751e9d83cf8	ZPIGINMQE	dni	0	378962	769767	983993	2025-07-14 15:13:33.000	2025-11-24 10:16:51.000
248	68752d19b7ed8	BAZNHYCTD	dni	0	902458	769776	984072	2025-07-14 16:15:21.000	2025-11-24 10:16:51.000
249	68754bddb7cda	JCILYUTAM	dni	0	902491	769803	983149	2025-07-14 18:26:37.000	2025-11-24 10:16:51.000
250	6875510788bfb	VDJBTIOOA	dni	0	902495	769807	984222	2025-07-14 18:48:39.000	2025-11-24 10:16:52.000
251	68755e519de36	RKSUDOGDZ	dni	0	899549	769819	984152	2025-07-14 19:45:21.000	2025-11-24 10:16:52.000
252	68756bfe58fbb	QAAEPPLCQ	dni	1	902521	769835	984351	2025-07-14 20:43:42.000	2025-11-24 10:16:52.000	2025-07-14 22:47:27.000
253	6875ac34c8acc	NMILINCNN	dni	0	902535	769855	984465	2025-07-15 01:17:40.000	2025-11-24 10:16:52.000
254	68761609d1fd2	DNALESLPG	dni	0	902305	769884	982977	2025-07-15 08:49:13.000	2025-11-24 10:16:52.000
255	687618e224b82	ICEFQUXLR	dni	1	878681	769889	984572	2025-07-15 09:01:22.000	2025-11-24 10:16:52.000	2025-07-15 11:02:47.000
256	68761fa6e9ab8	QYMVIIWNX	dni	0	341338	769895	979554	2025-07-15 09:30:14.000	2025-11-24 10:16:52.000
257	68765aa319a84	ORTCXXUGA	dni	0	902588	769943	984857	2025-07-15 13:41:55.000	2025-11-24 10:16:52.000
258	68768b1f1ec53	DMWZBTZZC	dni	0	902625	769981	977418	2025-07-15 17:08:47.000	2025-11-24 10:16:52.000
259	68768b89671fb	DJHILBYWC	dni	0	902510	769982	984226	2025-07-15 17:10:33.000	2025-11-24 10:16:52.000
260	6876b71c09c30	XKPVWLOXM	dni	1	902660	770014	985175	2025-07-15 20:16:28.000	2025-11-24 10:16:52.000
261	6877b6aa58fe5	ZDNXNARZF	dni	0	902769	770137	985735	2025-07-16 14:26:50.000	2025-11-24 10:16:52.000
262	6877d1d498362	GOHWWRJNF	rifle	1	902786	770150	982993	2025-07-16 16:22:44.000	2025-11-24 10:16:52.000	2025-07-16 18:49:01.000
263	6878221fbdd31	EUGEOMNNP	dni	0	902837	770206	986109	2025-07-16 22:05:19.000	2025-11-24 10:16:52.000
264	6878ac3f260d1	UILRGPAIE	dni	0	385802	770228	986093	2025-07-17 07:54:39.000	2025-11-24 10:16:52.000
265	6878bec67a3dd	CPFZUVWOJ	dni	1	902871	770246	986283	2025-07-17 09:13:42.000	2025-11-24 10:16:52.000	2025-07-17 14:25:49.000
266	68794d820a593	NRJARNLQW	dni	1	355031	770335	986755	2025-07-17 19:22:42.000	2025-11-24 10:16:52.000	2025-07-17 23:02:39.000
267	68797c535ff7f	QGYGCCQAQ	dni	0	318137	770361	985462	2025-07-17 22:42:27.000	2025-11-24 10:16:52.000
268	687a1f4c547a1	IPGKTPIUN	rifle	0	866515	770403	981579	2025-07-18 10:17:48.000	2025-11-24 10:16:53.000
269	687a2d8ba0da7	TNRNLWMRT	dni	1	902713	770410	985497	2025-07-18 11:18:35.000	2025-11-24 10:16:53.000	2025-07-18 15:33:11.000
270	687a2dc7e96e9	XLFXEPHHT	dni	1	844721	770411	987106	2025-07-18 11:19:35.000	2025-11-24 10:16:53.000	2025-07-18 13:26:11.000
271	687a44a3c6942	AJNRWHVXV	dni	0	903027	770424	987185	2025-07-18 12:57:07.000	2025-11-24 10:16:53.000
272	687a70796f27f	VZHTMFJDW	dni	0	903059	770444	987307	2025-07-18 16:04:09.000	2025-11-24 10:16:53.000
273	687a841ab2c69	FNQVVONKP	dni	0	903033	770463	977957	2025-07-18 17:27:54.000	2025-11-24 10:16:53.000
274	687afc5201ea0	RJFMEDBCS	dni	0	903103	770507	987580	2025-07-19 02:00:50.000	2025-11-24 10:16:53.000
275	687b854172f78	ODYYVJFLF	dni	0	903135	770537	987750	2025-07-19 11:45:05.000	2025-11-24 10:16:53.000
276	687cdb567db4a	IAUYNAAIB	dni	0	903231	770641	988342	2025-07-20 12:04:38.000	2025-11-24 10:16:53.000
277	687ce7e68fca8	WPVDNNFDD	dni	1	903240	770648	988409	2025-07-20 12:58:14.000	2025-11-24 10:16:53.000	2025-07-20 15:04:44.000
278	687cef4b76c74	HKRLMYKTC	dni	1	903246	770655	988438	2025-07-20 13:29:47.000	2025-11-24 10:16:53.000	2025-07-21 10:51:53.000
279	687d1f6bada20	HHDJSCTSJ	dni	0	903281	770689	988589	2025-07-20 16:55:07.000	2025-11-24 10:16:53.000
280	687d45ffe75f9	FLHBODVFH	dni	1	899534	770715	988280	2025-07-20 19:39:43.000	2025-11-24 10:16:53.000	2025-07-21 13:24:28.000
281	687d49de40ffe	VFENZDYXH	dni	0	903314	770720	988752	2025-07-20 19:56:14.000	2025-11-24 10:16:53.000
282	687d542265352	GCUDXDXUM	dni	0	903320	770731	986221	2025-07-20 20:40:02.000	2025-11-24 10:16:53.000
283	687d5b3588606	UZCOGLSPI	dni	0	903325	770738	988804	2025-07-20 21:10:13.000	2025-11-24 10:16:54.000
284	687d63e60d883	GRDDXKDSR	dni	1	903329	770744	988798	2025-07-20 21:47:18.000	2025-11-24 10:16:54.000	2025-07-22 10:29:48.000
285	687d7a4a24c25	BTAXBXUXJ	dni	1	342989	770751	988862	2025-07-20 23:22:50.000	2025-11-24 10:16:54.000	2025-07-21 01:25:32.000
286	687e2eb79b0cd	KMMZGAMCS	dni	0	902823	770817	985990	2025-07-21 12:12:39.000	2025-11-24 10:16:54.000
287	687e341314c82	KWQWGNXLH	dni	0	903391	770825	989208	2025-07-21 12:35:31.000	2025-11-24 10:16:54.000
288	687e43aa025af	EVFFCOKYS	dni	0	903365	770835	989039	2025-07-21 13:42:02.000	2025-11-24 10:16:54.000
289	687e6032b1624	YRBFTBCFV	dni	1	335325	770855	989426	2025-07-21 15:43:46.000	2025-11-24 10:16:54.000	2025-07-21 17:47:20.000
290	687ec6aeb8277	MZDJJPITN	dni	1	903516	770938	989822	2025-07-21 23:01:02.000	2025-11-24 10:16:54.000	2025-07-23 18:26:42.000
291	687eed53d0a16	RBGNDLRCM	dni	0	903525	770947	989848	2025-07-22 01:45:55.000	2025-11-24 10:16:54.000
292	687efc08aae48	DMFNBMSZR	dni	0	902535	770949	988888	2025-07-22 02:48:40.000	2025-11-24 10:16:54.000
293	687f53e39b895	ZVEUOIAUB	dni	0	903549	770974	989739	2025-07-22 09:03:31.000	2025-11-24 10:16:54.000
294	687f6291c3701	JPAHIIURV	dni	1	903561	770991	987892	2025-07-22 10:06:09.000	2025-11-24 10:16:54.000	2025-07-22 12:36:46.000
295	687fb6574e830	TSNGNCPTE	dni	0	900085	771054	990393	2025-07-22 16:03:35.000	2025-11-24 10:16:54.000
296	687fcf192bca1	BJZBVMKHY	dni	1	903644	771072	990476	2025-07-22 17:49:13.000	2025-11-24 10:16:54.000	2025-07-22 23:31:45.000
297	68804fe8e5519	MDEVPHWSG	dni	1	903686	771118	990716	2025-07-23 02:58:48.000	2025-11-24 10:16:54.000	2025-07-23 05:17:37.000
298	6881dee91b40c	EPNRVBXJL	dni	0	903852	771292	991685	2025-07-24 07:21:13.000	2025-11-24 10:16:55.000
299	688227cc1d6b3	KKJDSBYAG	dni	0	887284	771346	991951	2025-07-24 12:32:12.000	2025-11-24 10:16:55.000
300	6882650495245	RBRCMRWTT	dni	0	814892	771383	992160	2025-07-24 16:53:24.000	2025-11-24 10:16:55.000
301	6882b53cb8026	YEJHKEMZF	dni	0	269924	771430	992414	2025-07-24 22:35:40.000	2025-11-24 10:16:55.000
302	6882d02e5bb02	LCIHXLXFQ	dni	0	411502	771435	992388	2025-07-25 00:30:38.000	2025-11-24 10:16:55.000
303	6883922943d9d	NCLMHJPDV	dni	0	904073	771492	992813	2025-07-25 14:18:17.000	2025-11-24 10:16:55.000
304	6883f1af386e7	GNORPZKWD	dni	0	904116	771553	993095	2025-07-25 21:05:51.000	2025-11-24 10:16:55.000
305	68849100883b2	XDVFZSVZF	dni	1	904136	771572	993175	2025-07-26 08:25:36.000	2025-11-24 10:16:55.000	2025-07-26 10:53:58.000
306	6886029c3ec11	SUAHVNMYJ	dni	0	844266	771692	993862	2025-07-27 10:42:36.000	2025-11-24 10:16:55.000
307	68874c99dadcb	TILRSPVJM	dni	1	225944	771817	992971	2025-07-28 10:10:33.000	2025-11-24 10:16:55.000	2025-07-28 12:20:30.000
308	68875716aa495	CTSUROCFW	dni	0	904329	771825	994681	2025-07-28 10:55:18.000	2025-11-24 10:16:55.000
309	688759e9b3b2c	IYYDRMNDO	dni	0	380706	771828	994673	2025-07-28 11:07:21.000	2025-11-24 10:16:55.000
310	6887b85cb2e52	FVUUCXYWC	dni	0	903748	771909	989347	2025-07-28 17:50:20.000	2025-11-24 10:16:55.000
311	6888d53f8fcd6	TIASUVPEH	dni	1	813659	772060	995871	2025-07-29 14:05:51.000	2025-11-24 10:16:55.000	2025-07-29 16:10:44.000
312	6888e44448a48	NHYTPXQSL	dni	1	904518	772073	995886	2025-07-29 15:09:56.000	2025-11-24 10:16:56.000	2025-07-29 17:12:42.000
313	6888ede43bbdf	BUYXPLDJL	dni	0	904539	772084	995497	2025-07-29 15:51:00.000	2025-11-24 10:16:56.000
314	688a981f3db8b	DSGTCLSFL	dni	0	904755	772339	997208	2025-07-30 22:09:35.000	2025-11-24 10:16:56.000
315	688b699e5a376	WKLRJAVDR	dni	1	904830	772424	997621	2025-07-31 13:03:26.000	2025-11-24 10:16:56.000	2025-07-31 15:08:18.000
316	688b6b8ca4644	FZLAKVSUF	dni	0	904829	772426	997593	2025-07-31 13:11:40.000	2025-11-24 10:16:56.000
317	688bc96b50e98	NINSJEOHR	dni	1	904888	772504	996800	2025-07-31 19:52:11.000	2025-11-24 10:16:56.000	2025-07-31 21:55:28.000
318	688bfb2206046	FTTBBOPOW	dni	1	904918	772532	998101	2025-07-31 23:24:18.000	2025-11-24 10:16:56.000	2025-08-01 10:10:51.000
319	688bfb9d809ea	ZGVDINSJD	dni	0	414146	772533	998104	2025-07-31 23:26:21.000	2025-11-24 10:16:56.000
320	688c940bb4f60	LRNIRIXKP	dni	0	904958	772568	998308	2025-08-01 10:16:43.000	2025-11-24 10:16:56.000
321	688ccf1f4bbe9	TNTOKGFQE	dni	0	904993	772617	998519	2025-08-01 14:28:47.000	2025-11-24 10:16:56.000
322	688cd35dcff08	PSWQOGNXS	dni	1	348485	772622	998540	2025-08-01 14:46:53.000	2025-11-24 10:16:56.000	2025-08-06 22:12:46.000
323	688d3002cea68	GJNSOMOAO	dni	1	905045	772672	998805	2025-08-01 21:22:10.000	2025-11-24 10:16:56.000	2025-08-01 23:42:26.000
324	688d42717878b	PZFQWMCTF	dni	1	357431	772681	998836	2025-08-01 22:40:49.000	2025-11-24 10:16:56.000	2025-08-08 16:11:11.000
325	688db5db403ab	DIABVQJFB	rifle	1	905058	772690	998349	2025-08-02 06:53:15.000	2025-11-24 10:16:57.000
326	688e3b08ba337	MWDZSQNPM	dni	1	905108	772761	996122	2025-08-02 16:21:28.000	2025-11-24 10:16:57.000	2025-08-02 19:08:38.000
327	688e500f47491	SMSWQHQKV	dni	1	905120	772773	999264	2025-08-02 17:51:11.000	2025-11-24 10:16:57.000	2025-08-02 19:59:11.000
328	688e61ecbb9d1	JLCMKFIXE	dni	0	905129	772785	996077	2025-08-02 19:07:24.000	2025-11-24 10:16:57.000
329	688e904cd0a08	ERBJUBEVG	dni	1	905144	772803	999438	2025-08-02 22:25:16.000	2025-11-24 10:16:57.000	2025-08-03 22:46:05.000
330	688e9555ea0c8	RFEMPHXTJ	dni	0	349894	772805	998970	2025-08-02 22:46:45.000	2025-11-24 10:16:57.000
331	688edf254ea18	RDJGKLLDL	dni	0	905147	772808	999488	2025-08-03 04:01:41.000	2025-11-24 10:16:57.000
332	688f3b0894e25	ZVZOGQJZZ	dni	1	905173	772835	999562	2025-08-03 10:33:44.000	2025-11-24 10:16:57.000	2025-08-08 13:47:35.000
333	688f3dbe64279	XXGTIXFZV	dni	0	905176	772839	999644	2025-08-03 10:45:18.000	2025-11-24 10:16:57.000
334	688ffbaf73fab	WQXZPPBQE	dni	0	893992	772939	1000235	2025-08-04 00:15:43.000	2025-11-24 10:16:57.000
335	68900c412568a	YCINURNME	dni	0	394563	772942	1000012	2025-08-04 01:26:25.000	2025-11-24 10:16:57.000
336	68909408253b6	DSAVGEXHV	dni	0	851307	773001	991561	2025-08-04 11:05:44.000	2025-11-24 10:16:57.000
337	68909c4e20a76	FXDTSKYKT	dni	1	905337	773007	1000564	2025-08-04 11:41:02.000	2025-11-24 10:16:57.000	2025-08-04 13:54:08.000
338	6890b59dda113	NUKMAZQQB	dni	0	905364	773033	1000686	2025-08-04 13:29:01.000	2025-11-24 10:16:57.000
339	6890c4f848a7a	XKXZHLHHF	dni	1	905377	773045	1000770	2025-08-04 14:34:32.000	2025-11-24 10:16:57.000	2025-08-04 16:36:01.000
340	6890daf6b6198	PINEOSLZB	dni	1	888542	773069	1000864	2025-08-04 16:08:22.000	2025-11-24 10:16:57.000	2025-08-04 18:08:46.000
341	6890dd8625615	OVPTBBDWH	dni	0	881119	773074	1000767	2025-08-04 16:19:18.000	2025-11-24 10:16:58.000
342	689131033e2e8	MWGVHGKZC	dni	1	903521	773144	989791	2025-08-04 22:15:31.000	2025-11-24 10:16:58.000	2025-08-09 14:35:14.000
343	6891c9b979b15	EAHBNQRLA	dni	1	905418	773182	999300	2025-08-05 09:07:05.000	2025-11-24 10:16:58.000	2025-08-12 11:44:24.000
344	689232ee48ea9	LNRDUFHQW	dni	1	905562	773271	1001812	2025-08-05 16:35:58.000	2025-11-24 10:16:58.000	2025-08-05 23:00:52.000
345	68925e6df05ba	IMXYTUYAH	dni	0	905594	773301	1001427	2025-08-05 19:41:33.000	2025-11-24 10:16:58.000
346	68926ddca1b88	GVESSWLWT	dni	0	904944	773311	998386	2025-08-05 20:47:24.000	2025-11-24 10:16:58.000
347	68928753103df	UYCSXQOEV	dni	0	905625	773331	1002082	2025-08-05 22:36:03.000	2025-11-24 10:16:58.000
348	689294edbe2b6	VKEUAUORP	dni	0	905629	773339	1002131	2025-08-05 23:34:05.000	2025-11-24 10:16:58.000
349	68931e035f65e	KFHESHXXI	dni	0	402400	773369	1002305	2025-08-06 09:18:59.000	2025-11-24 10:16:58.000
350	689333583190f	BCUULFNZI	dni	1	905575	773386	1002402	2025-08-06 10:50:00.000	2025-11-24 10:16:58.000	2025-08-06 12:52:55.000
351	68936970d3046	JUKVBLCGZ	dni	0	905702	773433	1002616	2025-08-06 14:40:48.000	2025-11-24 10:16:58.000
352	68936f6da1779	TGNRIMLMC	dni	1	905699	773440	1002609	2025-08-06 15:06:21.000	2025-11-24 10:16:58.000	2025-08-06 17:13:23.000
353	68936fa159eea	AJWCANLZH	dni	1	892876	773441	1001876	2025-08-06 15:07:13.000	2025-11-24 10:16:58.000	2025-08-08 00:32:02.000
354	68938a1a8bf5f	GQRCSTFOJ	dni	1	905730	773458	1000872	2025-08-06 17:00:10.000	2025-11-24 10:16:58.000	2025-08-06 19:02:57.000
355	689399449a472	RPZXSQNCE	dni	1	905686	773476	1002297	2025-08-06 18:04:52.000	2025-11-24 10:16:58.000	2025-08-06 20:08:58.000
356	6893b91f522b9	ARFTLDPEE	dni	1	905767	773502	998926	2025-08-06 20:20:47.000	2025-11-24 10:16:58.000	2025-08-06 23:02:40.000
357	6893dad388e10	UIBHBTRWX	dni	1	69634	773525	1003017	2025-08-06 22:44:35.000	2025-11-24 10:16:59.000	2025-08-07 11:57:58.000
358	68944edae95f8	MNAVYQMWD	rifle	0	904438	773544	1003132	2025-08-07 06:59:38.000	2025-11-24 10:16:59.000
359	68946695400ba	FAMXTKINM	dni	0	905809	773555	1003177	2025-08-07 08:40:53.000	2025-11-24 10:16:59.000
360	689498a1dc6ec	HZSNLOGMQ	dni	0	905842	773597	1003435	2025-08-07 12:14:25.000	2025-11-24 10:16:59.000
361	6894bfb3a6747	CMGBLLMKT	dni	0	284355	773623	1003561	2025-08-07 15:01:07.000	2025-11-24 10:16:59.000
362	6894eb1ad27ae	BKZDCILZZ	dni	0	905883	773672	1000229	2025-08-07 18:06:18.000	2025-11-24 10:16:59.000
363	6894f08429e12	HNBFLIZVK	dni	0	905907	773675	1003764	2025-08-07 18:29:24.000	2025-11-24 10:16:59.000
364	6894f9968b120	AAXLCVYGV	dni	1	904136	773678	1001148	2025-08-07 19:08:06.000	2025-11-24 10:16:59.000	2025-08-07 21:27:18.000
365	6894fa14467eb	PXDSTTDEO	dni	0	857968	773680	1003536	2025-08-07 19:10:12.000	2025-11-24 10:16:59.000
366	6895060fba196	KQNYYCOJO	dni	1	905917	773690	1003880	2025-08-07 20:01:19.000	2025-11-24 10:16:59.000	2025-08-07 22:03:53.000
367	689515e5abafa	INEPWFGWX	dni	1	905924	773698	1003919	2025-08-07 21:08:53.000	2025-11-24 10:17:00.000	2025-08-07 23:11:05.000
368	6895b25607a25	GXXEBBDKA	dni	1	905734	773732	1003372	2025-08-08 08:16:22.000	2025-11-24 10:17:00.000	2025-08-08 10:22:36.000
369	6895b918547bb	HCPDRKPFC	dni	0	905950	773736	1004142	2025-08-08 08:45:12.000	2025-11-24 10:17:00.000
370	6895db43b3948	GBVHKYTRZ	dni	0	851919	773757	1004234	2025-08-08 11:10:59.000	2025-11-24 10:17:00.000
371	689600a8e5eec	BLJUGAUQD	dni	1	905993	773790	1004364	2025-08-08 13:50:32.000	2025-11-24 10:17:00.000	2025-08-11 14:35:26.000
372	6896274faae7c	WPHKFZOOC	dni	1	906018	773815	1004514	2025-08-08 16:35:27.000	2025-11-24 10:17:00.000	2025-08-08 18:38:56.000
373	68964aafd17d6	OJODGGADN	dni	0	906035	773836	1004621	2025-08-08 19:06:23.000	2025-11-24 10:17:00.000
374	68965940736e5	ANPRSDKUZ	dni	0	884655	773850	1004679	2025-08-08 20:08:32.000	2025-11-24 10:17:00.000
375	6896682801cde	JKCNWJPCY	dni	0	880693	773856	1004715	2025-08-08 21:12:08.000	2025-11-24 10:17:00.000
376	68966a75d2290	GQLDPUGNJ	dni	1	883787	773860	1000115	2025-08-08 21:21:57.000	2025-11-24 10:17:00.000	2025-08-09 18:04:10.000
377	6896794b58f57	IMOZHKRII	dni	1	906058	773868	1004753	2025-08-08 22:25:15.000	2025-11-24 10:17:01.000	2025-08-09 00:28:43.000
378	68968974398d0	DJTSGWIDB	dni	1	906053	773873	1004769	2025-08-08 23:34:12.000	2025-11-24 10:17:01.000	2025-08-09 01:46:59.000
379	68971c09ac40a	IXCRCVNHQ	dni	1	112796	773892	1004913	2025-08-09 09:59:37.000	2025-11-24 10:17:01.000	2025-08-09 12:13:49.000
380	6897207b471c8	SNYWGAFMM	dni	0	905435	773895	1000003	2025-08-09 10:18:35.000	2025-11-24 10:17:01.000
381	68972a5a1e22e	WDWEGNYIM	dni	1	226633	773903	996458	2025-08-09 11:00:42.000	2025-11-24 10:17:01.000	2025-08-09 13:04:12.000
382	68974407390e4	KVIHIWFTJ	dni	1	906099	773913	1005026	2025-08-09 12:50:15.000	2025-11-24 10:17:01.000	2025-08-09 14:52:17.000
383	68975872888f8	VEXBNYXJY	dni	0	906117	773929	1000631	2025-08-09 14:17:22.000	2025-11-24 10:17:01.000
384	6898566ec0ae4	RTBNYNESE	dni	0	906130	773990	1005169	2025-08-10 08:21:02.000	2025-11-24 10:17:01.000
385	6898730a05871	JQMFGQUIW	dni	1	906194	774011	1005535	2025-08-10 10:23:06.000	2025-11-24 10:17:01.000	2025-08-11 10:47:00.000
386	6898785f18b25	DIVMIYSRL	dni	1	906198	774014	1005522	2025-08-10 10:45:51.000	2025-11-24 10:17:01.000	2025-08-10 13:09:28.000
387	6898b48b86ee1	PHFILTXGK	dni	0	906215	774043	1005487	2025-08-10 15:02:35.000	2025-11-24 10:17:01.000
388	6898b71402d9f	CMBFGHVHX	dni	1	906234	774045	1005627	2025-08-10 15:13:24.000	2025-11-24 10:17:01.000	2025-08-11 12:14:18.000
389	6898ead97aef5	BUCFSHXSG	dni	0	906256	774080	1005888	2025-08-10 18:54:17.000	2025-11-24 10:17:02.000
390	6899351aca99e	OFUTGHCYV	dni	0	906278	774102	1005832	2025-08-11 00:11:06.000	2025-11-24 10:17:02.000
391	689935efec117	NIRYFNZHX	dni	1	894661	774103	1005403	2025-08-11 00:14:39.000	2025-11-24 10:17:02.000	2025-08-11 02:24:33.000
392	68993e9dea88f	LLKXRNDQW	dni	1	906279	774104	1004640	2025-08-11 00:51:41.000	2025-11-24 10:17:02.000	2025-08-11 02:54:29.000
393	6899a5934f013	CPGBOYGJO	dni	0	906294	774121	1006155	2025-08-11 08:10:59.000	2025-11-24 10:17:02.000
394	6899b07f13532	AJPPIOQNP	dni	0	902588	774128	967932	2025-08-11 08:57:35.000	2025-11-24 10:17:02.000
395	6899bbd013b5b	VIFUOAIQZ	dni	0	906308	774136	1006248	2025-08-11 09:45:52.000	2025-11-24 10:17:02.000
396	6899ca0928af3	ATAANKKZY	dni	0	144410	774142	1005952	2025-08-11 10:46:33.000	2025-11-24 10:17:03.000
397	6899ca6d12f5b	EIHASZOYC	dni	0	906317	774143	1006296	2025-08-11 10:48:13.000	2025-11-24 10:17:03.000
398	6899cda958709	OFUFUIMJR	dni	0	906322	774148	1006302	2025-08-11 11:02:01.000	2025-11-24 10:17:03.000
399	6899f4aaac602	MRVRYPSJG	dni	0	906361	774181	1005769	2025-08-11 13:48:26.000	2025-11-24 10:17:03.000
400	689a3d7b53a7a	AQXHHSQNT	dni	0	272256	774238	1006709	2025-08-11 18:59:07.000	2025-11-24 10:17:03.000
401	689a3ff31271f	VKDFLWZHS	dni	0	358289	774242	995771	2025-08-11 19:09:39.000	2025-11-24 10:17:03.000
402	689a4f821f7ac	ITENIQDHA	dni	0	906418	774259	1006813	2025-08-11 20:16:02.000	2025-11-24 10:17:03.000
403	689a6a695000f	DTIPYDRAZ	dni	0	906439	774268	1006932	2025-08-11 22:10:49.000	2025-11-24 10:17:03.000
404	689abcf66e2cf	BLLKUMJRL	dni	1	906349	774284	1006434	2025-08-12 04:03:02.000	2025-11-24 10:17:03.000	2025-08-12 06:15:40.000
405	689b0f7998e0c	THMNTHUEA	dni	0	906205	774313	1005587	2025-08-12 09:55:05.000	2025-11-24 10:17:03.000
406	689b5a12e8bc4	AJUXZBADS	dni	0	409908	774353	1007418	2025-08-12 15:13:22.000	2025-11-24 10:17:03.000
407	689b67cdd7712	TXJQFNKJN	dni	1	906528	774366	1007497	2025-08-12 16:11:57.000	2025-11-24 10:17:03.000	2025-08-12 18:21:51.000
408	689b6acfef00a	XKGQIBJAZ	dni	0	906527	774369	1007450	2025-08-12 16:24:47.000	2025-11-24 10:17:03.000
409	689b900ebcd4a	MJPYMCLCC	dni	0	851411	774390	1007621	2025-08-12 19:03:42.000	2025-11-24 10:17:04.000
410	689bbb1d38dbf	EZOQWARZC	dni	1	906568	774420	1007731	2025-08-12 22:07:25.000	2025-11-24 10:17:04.000	2025-08-13 00:10:01.000
411	689c699fcbc26	GVQGBBMYL	dni	0	226186	774455	1007964	2025-08-13 10:31:59.000	2025-11-24 10:17:04.000
412	689c6c0f73bf0	NSMSECMTH	dni	1	120835	774459	1007948	2025-08-13 10:42:23.000	2025-11-24 10:17:04.000	2025-08-13 13:59:54.000
413	689c9ef063e67	NVWDYPNPM	dni	1	906609	774488	1007967	2025-08-13 14:19:28.000	2025-11-24 10:17:04.000	2025-08-13 16:51:55.000
414	689ca10f7d210	ADWAGMNVX	dni	0	906635	774491	1008166	2025-08-13 14:28:31.000	2025-11-24 10:17:04.000
415	689cae0aaa448	HUKDEJXNZ	dni	1	227108	774500	1008224	2025-08-13 15:23:54.000	2025-11-24 10:17:04.000	2025-08-14 17:53:52.000
416	689cae331f682	OHLWMWMEZ	dni	0	364366	774501	1008222	2025-08-13 15:24:35.000	2025-11-24 10:17:04.000
417	689cbc9d4b931	ZVHQQLPZP	dni	0	877171	774510	1008263	2025-08-13 16:26:05.000	2025-11-24 10:17:04.000
418	689ce1fd04013	KLVOHDBJK	dni	0	906668	774542	1008385	2025-08-13 19:05:33.000	2025-11-24 10:17:04.000
419	689ce6cd7423a	BFCKBLVUU	dni	1	906378	774544	1006598	2025-08-13 19:26:05.000	2025-11-24 10:17:04.000	2025-08-13 21:28:41.000
420	689cfcb74a7cb	JRJEIBWOH	dni	0	349894	774561	1004002	2025-08-13 20:59:35.000	2025-11-24 10:17:05.000
421	689d340e7a0b3	MMQOZZKBN	dni	1	906711	774578	1008581	2025-08-14 00:55:42.000	2025-11-24 10:17:05.000	2025-08-14 03:01:13.000
422	689dbc2f3729a	CIOTUXYKV	dni	1	906738	774608	1007592	2025-08-14 10:36:31.000	2025-11-24 10:17:05.000	2025-08-14 12:40:51.000
423	689dbff19646c	WOMLWPLFN	dni	0	906741	774610	1008776	2025-08-14 10:52:33.000	2025-11-24 10:17:05.000
424	689dc572a933a	FIJEHPOIU	dni	1	906613	774613	1007995	2025-08-14 11:16:02.000	2025-11-24 10:17:05.000	2025-08-14 16:13:21.000
425	689dce35640dd	SCKVQMNSS	escopeta	0	906579	774618	1007791	2025-08-14 11:53:25.000	2025-11-24 10:17:05.000
426	689dddb9caf24	MNUPFPEDS	dni	1	906755	774625	1008840	2025-08-14 12:59:37.000	2025-11-24 10:17:05.000	2025-08-14 15:13:30.000
427	689dded950da6	TASUFMLNV	dni	1	906754	774626	1008841	2025-08-14 13:04:25.000	2025-11-24 10:17:05.000	2025-08-14 15:16:54.000
428	689e2e1356694	ILIBIJLJM	dni	0	906636	774679	1008184	2025-08-14 18:42:27.000	2025-11-24 10:17:05.000
429	689e32a986619	OTVPOOEGR	dni	0	904427	774683	1009114	2025-08-14 19:02:01.000	2025-11-24 10:17:05.000
430	689e46bff0c8a	ISLKWMJVR	dni	0	417254	774687	1009167	2025-08-14 20:27:43.000	2025-11-24 10:17:06.000
431	689e5502a9f22	HEVBUBPAY	dni	1	906815	774691	1005691	2025-08-14 21:28:34.000	2025-11-24 10:17:06.000	2025-08-14 23:32:08.000
432	689eed8c96332	XYEHKJGFH	dni	1	410413	774713	1009340	2025-08-15 08:19:24.000	2025-11-24 10:17:06.000	2025-08-15 10:30:59.000
433	689f2f75ac6a9	YJRXIGVWN	dni	0	906855	774748	1009505	2025-08-15 13:00:37.000	2025-11-24 10:17:06.000
434	689f393d40716	AOXMNGWNN	dni	1	906859	774752	1009540	2025-08-15 13:42:21.000	2025-11-24 10:17:06.000	2025-08-15 16:06:18.000
435	689f4c27b3e3c	HRSJVKKOE	dni	1	906872	774762	1009615	2025-08-15 15:03:03.000	2025-11-24 10:17:06.000	2025-08-15 17:26:16.000
436	689f58d1c9211	DSXCCPBMJ	dni	1	906881	774765	1009662	2025-08-15 15:57:05.000	2025-11-24 10:17:06.000	2025-08-15 18:08:04.000
437	689f5d85f228e	SKGNTRMTS	dni	1	906880	774769	1009500	2025-08-15 16:17:09.000	2025-11-24 10:17:06.000	2025-08-15 18:25:16.000
438	689f6d1c10faf	DYEWKWJWB	dni	1	906894	774779	1009734	2025-08-15 17:23:40.000	2025-11-24 10:17:06.000	2025-08-16 11:27:35.000
439	689f6f00738d4	IZEGHCJBM	dni	0	906895	774780	1009735	2025-08-15 17:31:44.000	2025-11-24 10:17:07.000
440	689f9270a8579	YIHJWLTPB	dni	0	906913	774795	1009817	2025-08-15 20:02:56.000	2025-11-24 10:17:07.000
441	689fa3d0e946c	DJYXWADUA	dni	0	392393	774800	1008836	2025-08-15 21:17:04.000	2025-11-24 10:17:07.000
442	68a0579060892	NMRBLQJJP	dni	1	906942	774824	1010043	2025-08-16 10:04:00.000	2025-11-24 10:17:07.000	2025-08-16 12:05:46.000
443	68a08132f314a	NKHRKBVXX	dni	1	858556	774840	1006848	2025-08-16 13:01:38.000	2025-11-24 10:17:07.000	2025-08-16 16:04:59.000
444	68a0b3fb822dc	GUXXWQFFP	dni	1	906988	774869	1010299	2025-08-16 16:38:19.000	2025-11-24 10:17:07.000	2025-08-16 18:56:13.000
445	68a0c5ed0430b	DEPYCJDCA	dni	0	207738	774883	1010377	2025-08-16 17:54:53.000	2025-11-24 10:17:07.000
446	68a0f5855061b	OYKLFUQTA	dni	0	906633	774899	1008149	2025-08-16 21:17:57.000	2025-11-24 10:17:07.000
447	68a0fb6684b44	DXULULQCK	dni	1	906707	774906	1008562	2025-08-16 21:43:02.000	2025-11-24 10:17:07.000	2025-08-17 20:53:41.000
448	68a0fdb32aeab	NRVIRXVGP	dni	1	907016	774907	1010467	2025-08-16 21:52:51.000	2025-11-24 10:17:07.000	2025-08-16 23:55:54.000
449	68a1340ae760d	DKILKABHC	dni	1	907025	774913	1010541	2025-08-17 01:44:42.000	2025-11-24 10:17:07.000	2025-08-17 03:48:40.000
450	68a1b69e2ad38	JUVWQCNHG	dni	1	264305	774930	1010693	2025-08-17 11:01:50.000	2025-11-24 10:17:07.000	2025-08-17 13:27:06.000
451	68a1c7796398b	NVGIXKNFE	dni	1	907046	774935	1010715	2025-08-17 12:13:45.000	2025-11-24 10:17:07.000	2025-08-17 14:57:59.000
452	68a1d4f21f479	YZHLGMNQW	dni	0	907015	774941	1010481	2025-08-17 13:11:14.000	2025-11-24 10:17:07.000
453	68a1e2d512f41	JYHIAUMHT	dni	0	907062	774944	1010839	2025-08-17 14:10:29.000	2025-11-24 10:17:07.000
454	68a1e42dec28f	GTICDFOBW	dni	1	907063	774945	1010829	2025-08-17 14:16:13.000	2025-11-24 10:17:07.000	2025-08-17 17:36:18.000
455	68a1fdde0dcfd	SUBELUGYZ	dni	0	390321	774961	1010784	2025-08-17 16:05:50.000	2025-11-24 10:17:07.000
456	68a20fd222f4e	OZOGKAGLF	dni	0	246551	774972	1010700	2025-08-17 17:22:26.000	2025-11-24 10:17:08.000
457	68a214724a40f	QOAFLVILG	dni	0	269263	774973	1005349	2025-08-17 17:42:10.000	2025-11-24 10:17:08.000
458	68a23039a6db6	VRUIKLLLD	dni	1	907099	774983	1011026	2025-08-17 19:40:41.000	2025-11-24 10:17:08.000	2025-08-17 21:50:03.000
459	68a240a8114b5	IIJNVFEMF	rifle	0	862020	774999	1010988	2025-08-17 20:50:48.000	2025-11-24 10:17:08.000
460	68a26bf88350c	IALWZAUQL	dni	0	395868	775015	1008569	2025-08-17 23:55:36.000	2025-11-24 10:17:08.000
461	68a271c62c44e	ZVHVDERRH	dni	0	907124	775016	1011180	2025-08-18 00:20:22.000	2025-11-24 10:17:08.000
462	68a2f940aaa6c	OESYKBXUW	dni	0	907145	775045	1011344	2025-08-18 09:58:24.000	2025-11-24 10:17:08.000
463	68a3061a91809	BKCZPULAX	dni	1	853587	775054	1011366	2025-08-18 10:53:14.000	2025-11-24 10:17:08.000	2025-08-18 23:45:31.000
464	68a3081cf2443	GQGGALSSR	dni	1	907153	775056	1011388	2025-08-18 11:01:48.000	2025-11-24 10:17:08.000	2025-08-18 13:10:56.000
465	68a32dec03780	FKSPDVBUJ	dni	0	907186	775085	1011527	2025-08-18 13:43:08.000	2025-11-24 10:17:08.000
466	68a33abe1444b	TSGXKJNFJ	dni	0	907197	775092	1011618	2025-08-18 14:37:50.000	2025-11-24 10:17:08.000
467	68a36bdf00b90	RUNSNJIJB	dni	0	882246	775142	1011846	2025-08-18 18:07:27.000	2025-11-24 10:17:08.000
468	68a398373e598	EKRVNSLOH	dni	0	907266	775169	1011975	2025-08-18 21:16:39.000	2025-11-24 10:17:08.000
469	68a3e81277ebb	IFRODZYYP	escopeta	1	228044	775188	1004051	2025-08-19 02:57:22.000	2025-11-24 10:17:08.000	2025-08-19 09:32:19.000
470	68a4272c9bbc7	KOSOHWAUD	dni	1	360622	775200	1010731	2025-08-19 07:26:36.000	2025-11-24 10:17:08.000	2025-08-24 17:07:45.000
471	68a43160249f5	DVSYIZHXN	dni	0	906703	775206	1008533	2025-08-19 08:10:08.000	2025-11-24 10:17:08.000
472	68a48873273e4	NBDHJCWKD	dni	0	907360	775274	1012568	2025-08-19 14:21:39.000	2025-11-24 10:17:09.000
473	68a4b024c15b2	OIGCDQVCK	dni	1	907394	775311	1012694	2025-08-19 17:11:00.000	2025-11-24 10:17:09.000	2025-08-20 08:18:07.000
474	68a4b2f31010e	NZJZLSTKE	dni	0	907391	775314	988366	2025-08-19 17:22:59.000	2025-11-24 10:17:09.000
475	68a4ded08d6e6	AYNRQTCCV	dni	0	907406	775350	1012773	2025-08-19 20:30:08.000	2025-11-24 10:17:09.000
476	68a510cbd97fd	KMFCKQXEG	dni	0	907442	775366	1012963	2025-08-20 00:03:23.000	2025-11-24 10:17:09.000
477	68a565ab26a0c	RTWRIAJLC	dni	0	362126	775373	1013010	2025-08-20 06:05:31.000	2025-11-24 10:17:09.000
478	68a584f01fbd9	FEBFSFDNT	escopeta	0	907464	775387	1010244	2025-08-20 08:18:56.000	2025-11-24 10:17:09.000
479	68a5b20ca4489	OWNPDTOUI	dni	0	413176	775412	1013224	2025-08-20 11:31:24.000	2025-11-24 10:17:09.000
480	68a5b98be95c2	GVHLCZZDR	dni	0	907493	775418	1013261	2025-08-20 12:03:23.000	2025-11-24 10:17:09.000
481	68a5d10fb5445	QHDHFLFRF	rifle	0	862020	775434	1011104	2025-08-20 13:43:43.000	2025-11-24 10:17:09.000
482	68a5db0cd82c3	MUTCCHUDM	dni	1	907512	775439	1011578	2025-08-20 14:26:20.000	2025-11-24 10:17:09.000	2025-08-20 16:32:50.000
483	68a5eb1c10b46	HQKSXIMDT	dni	0	907522	775446	1013446	2025-08-20 15:34:52.000	2025-11-24 10:17:09.000
484	68a5f6d07eb1c	KAJKLPNLX	dni	1	907534	775456	1013487	2025-08-20 16:24:48.000	2025-11-24 10:17:09.000	2025-08-20 18:28:56.000
485	68a624d1ba730	QWFULSHUP	dni	1	907576	775507	1013683	2025-08-20 19:41:05.000	2025-11-24 10:17:09.000	2025-08-20 21:44:09.000
486	68a63f0997d09	AWBZSMSSW	dni	0	907591	775524	1013741	2025-08-20 21:32:57.000	2025-11-24 10:17:10.000
487	68a6f746736fe	YSJHEWIAZ	dni	1	221705	775576	1014014	2025-08-21 10:39:02.000	2025-11-24 10:17:10.000	2025-08-22 09:08:38.000
488	68a7675e90fb0	FABSNGCXW	dni	1	907709	775640	1014493	2025-08-21 18:37:18.000	2025-11-24 10:17:10.000	2025-08-22 14:37:14.000
489	68a7731251477	SUQDZJJQW	dni	0	907718	775647	1014530	2025-08-21 19:27:14.000	2025-11-24 10:17:10.000
490	68a77a7d7d8b0	LWNSMSWZQ	dni	0	289474	775651	1014369	2025-08-21 19:58:53.000	2025-11-24 10:17:10.000
491	68a77e9682b02	OETAQJMVS	dni	1	907010	775654	1010466	2025-08-21 20:16:22.000	2025-11-24 10:17:10.000	2025-08-21 22:17:35.000
492	68a780ca3eeaf	FCFPDMWPS	dni	1	907726	775657	1013877	2025-08-21 20:25:46.000	2025-11-24 10:17:10.000	2025-08-21 22:28:38.000
493	68a78fea2a7b9	PPLEEBFNM	dni	1	907729	775662	1012700	2025-08-21 21:30:18.000	2025-11-24 10:17:10.000	2025-08-21 23:33:41.000
494	68a7c2e564483	DRTWGHTEW	dni	1	254552	775674	1006277	2025-08-22 01:07:49.000	2025-11-24 10:17:10.000	2025-08-22 03:13:31.000
495	68a82872b3aac	OZBAWADEA	dni	0	907734	775686	1014198	2025-08-22 08:21:06.000	2025-11-24 10:17:10.000
496	68a84d0e9599e	CILLKRBUQ	dni	0	907769	775708	1014904	2025-08-22 10:57:18.000	2025-11-24 10:17:10.000
497	68a85f594a05d	XQKUVNJBT	dni	0	907779	775714	1014965	2025-08-22 12:15:21.000	2025-11-24 10:17:10.000
498	68a8b6050a3d9	CSLITUMGN	dni	1	907819	775755	1015173	2025-08-22 18:25:09.000	2025-11-24 10:17:10.000	2025-08-22 20:27:08.000
499	68a972c52e415	NYORIQOIN	dni	1	907845	775778	1015483	2025-08-23 07:50:29.000	2025-11-24 10:17:10.000	2025-08-23 09:54:44.000
500	68a973b9a8b40	JQVIQQZED	dni	0	907505	775780	1013326	2025-08-23 07:54:33.000	2025-11-24 10:17:10.000
501	68a9b02bdec05	HFRYKTTND	dni	1	892876	775813	1015662	2025-08-23 12:12:27.000	2025-11-24 10:17:10.000	2025-08-25 16:46:00.000
502	68a9b2676c21c	TQRYQTPTF	dni	1	907876	775814	1015652	2025-08-23 12:21:59.000	2025-11-24 10:17:11.000	2025-08-23 21:22:12.000
503	68a9f13b6409d	PXZUOPTQH	dni	0	907898	775845	1015616	2025-08-23 16:50:03.000	2025-11-24 10:17:11.000
504	68aa013b7659e	GODHSNWUN	dni	0	907882	775853	1013601	2025-08-23 17:58:19.000	2025-11-24 10:17:11.000
505	68aaf47ecd915	NKVUTFCIR	dni	1	907956	775910	1016290	2025-08-24 11:16:14.000	2025-11-24 10:17:11.000	2025-08-24 13:19:17.000
506	68aafc31285ee	RKVQACYVD	dni	0	905809	775919	1006868	2025-08-24 11:49:05.000	2025-11-24 10:17:11.000
507	68ab028c18550	IOZMPKVOU	dni	0	906620	775924	1008084	2025-08-24 12:16:12.000	2025-11-24 10:17:11.000
508	68ab1b1f11921	FUUNHYBMY	dni	0	907610	775934	1015151	2025-08-24 14:01:03.000	2025-11-24 10:17:11.000
509	68ab2c5cac97c	KIVYKEKAD	dni	1	906920	775942	1016412	2025-08-24 15:14:36.000	2025-11-24 10:17:11.000	2025-08-26 14:15:03.000
510	68ab92d553c28	WEFYQEXBI	dni	1	908045	776011	1016785	2025-08-24 22:31:49.000	2025-11-24 10:17:11.000	2025-08-25 00:33:07.000
511	68ac2976b4457	NEYKHTLLX	dni	1	908085	776034	1017002	2025-08-25 09:14:30.000	2025-11-24 10:17:11.000	2025-08-25 13:03:54.000
512	68ac67ed2309a	HNLGZXTDF	dni	1	908136	776083	1014711	2025-08-25 13:41:01.000	2025-11-24 10:17:11.000	2025-08-25 15:47:49.000
513	68ac7db86d9e5	ZOFYKULAK	dni	0	838065	776104	1017359	2025-08-25 15:14:00.000	2025-11-24 10:17:11.000
514	68acc82166089	BTPJPRXIC	dni	1	908220	776196	1017688	2025-08-25 20:31:29.000	2025-11-24 10:17:11.000	2025-08-25 22:33:24.000
515	68ace8550988f	NHGSZTWWZ	dni	1	908233	776216	1017789	2025-08-25 22:48:53.000	2025-11-24 10:17:12.000	2025-08-26 00:52:28.000
516	68ad6c5854997	NUZPZSRQW	dni	1	908060	776239	1017902	2025-08-26 08:12:08.000	2025-11-24 10:17:12.000	2025-08-26 10:22:55.000
517	68ad891ef0dd1	CZJQRZAVG	dni	0	893649	776259	1013599	2025-08-26 10:14:54.000	2025-11-24 10:17:12.000
518	68ada34434909	WMXFAYQGD	dni	0	908284	776286	1018200	2025-08-26 12:06:28.000	2025-11-24 10:17:12.000
519	68adb54d6c486	QTBEADTLW	dni	0	907534	776302	1014037	2025-08-26 13:23:25.000	2025-11-24 10:17:12.000
520	68adc8e16b9c6	XKCIMIKDP	dni	0	371344	776314	1015403	2025-08-26 14:46:57.000	2025-11-24 10:17:12.000
521	68adcd045f94b	PKGLGUNLO	escopeta	0	894786	776317	1018395	2025-08-26 15:04:36.000	2025-11-24 10:17:12.000
522	68adf2fb63cb5	WNJYNEBYR	dni	1	908345	776358	1018614	2025-08-26 17:46:35.000	2025-11-24 10:17:12.000	2025-08-29 12:30:32.000
523	68ae107266929	TFKCTATDP	rifle	0	908364	776388	1018684	2025-08-26 19:52:18.000	2025-11-24 10:17:12.000
524	68ae2991e972e	JEINDTPII	dni	0	308719	776409	1018851	2025-08-26 21:39:29.000	2025-11-24 10:17:12.000
525	68aefec62342d	WFHENSVDY	dni	0	906308	776506	1019336	2025-08-27 12:49:10.000	2025-11-24 10:17:12.000
526	68af418caf446	OWOWYHBZI	dni	1	908508	776550	1019624	2025-08-27 17:34:04.000	2025-11-24 10:17:12.000	2025-09-03 20:43:17.000
527	68af45be94c68	NYKAEQLHT	dni	1	908509	776552	1019656	2025-08-27 17:51:58.000	2025-11-24 10:17:12.000	2025-08-27 21:20:20.000
528	68af498beace4	MFOTEHVAV	dni	0	847234	776556	1019674	2025-08-27 18:08:11.000	2025-11-24 10:17:12.000
529	68af6d5d4d8d0	IMDNJGLDE	dni	1	907699	776583	1014448	2025-08-27 20:41:01.000	2025-11-24 10:17:12.000	2025-08-27 23:26:48.000
530	68af9052b693a	UGKQHODSJ	dni	1	908554	776599	1011529	2025-08-27 23:10:10.000	2025-11-24 10:17:12.000	2025-08-28 01:16:54.000
531	68b0336f87dae	YJNRUNHMT	dni	1	908598	776648	1020135	2025-08-28 10:46:07.000	2025-11-24 10:17:12.000	2025-08-28 12:59:27.000
532	68b04c67cc0cd	LVUFSWJEB	dni	0	381530	776671	1020217	2025-08-28 12:32:39.000	2025-11-24 10:17:12.000
533	68b0601fa8376	NDQWVASDA	dni	0	908622	776682	1020324	2025-08-28 13:56:47.000	2025-11-24 10:17:12.000
534	68b078ec3c816	IGMJWCMAB	dni	1	908638	776701	1020411	2025-08-28 15:42:36.000	2025-11-24 10:17:13.000	2025-08-28 21:39:02.000
535	68b08b919ffde	XJSTLLMDA	dni	1	908654	776713	1020440	2025-08-28 17:02:09.000	2025-11-24 10:17:13.000	2025-08-28 19:13:21.000
536	68b0aa6ee9818	NJZJWQNBX	dni	1	908671	776735	1020604	2025-08-28 19:13:50.000	2025-11-24 10:17:13.000	2025-08-28 21:24:59.000
537	68b0c7677b667	QQNWLMEHL	dni	0	908685	776758	1016084	2025-08-28 21:17:27.000	2025-11-24 10:17:13.000
538	68b15ec938d22	KPSCICIII	dni	0	908271	776797	1018089	2025-08-29 08:03:21.000	2025-11-24 10:17:13.000
539	68b1821305347	ZIBZWOKFE	dni	1	908740	776825	1020992	2025-08-29 10:33:55.000	2025-11-24 10:17:13.000	2025-09-02 15:44:22.000
540	68b18997ba909	ATKTEACQA	escopeta	1	908621	776830	1020990	2025-08-29 11:05:59.000	2025-11-24 10:17:13.000	2025-08-29 14:29:07.000
541	68b1beb922954	BXYYFILTJ	dni	0	903656	776876	998746	2025-08-29 14:52:41.000	2025-11-24 10:17:13.000
542	68b1e6f376be2	UGKKDLBDH	dni	1	908821	776912	1021384	2025-08-29 17:44:19.000	2025-11-24 10:17:13.000	2025-08-29 19:47:13.000
543	68b1e8b84458d	DHIWRQQHP	dni	0	908822	776913	1021311	2025-08-29 17:51:52.000	2025-11-24 10:17:13.000
544	68b1ed6eba638	AEFZBCBNP	dni	0	425710	776921	1020851	2025-08-29 18:11:58.000	2025-11-24 10:17:13.000
545	68b1f878487e0	NZZNDIPCZ	dni	1	908835	776928	1021369	2025-08-29 18:59:04.000	2025-11-24 10:17:13.000	2025-09-01 11:27:44.000
546	68b2a75d3b518	PPCEGPXCH	dni	1	908858	776951	1021678	2025-08-30 07:25:17.000	2025-11-24 10:17:13.000	2025-08-30 10:02:36.000
547	68b2bd8850b24	TAAFUETHQ	dni	0	811123	776958	1021422	2025-08-30 08:59:52.000	2025-11-24 10:17:13.000
548	68b2bfafad50a	LYAQTVJWP	escopeta	0	881952	776959	1021720	2025-08-30 09:09:03.000	2025-11-24 10:17:13.000
549	68b31dba40553	IEGUSJFXD	dni	0	908909	777027	1021994	2025-08-30 15:50:18.000	2025-11-24 10:17:13.000
550	68b327940b0be	XKEKLSSGU	dni	1	908911	777036	1022027	2025-08-30 16:32:20.000	2025-11-24 10:17:13.000	2025-09-10 11:23:39.000
551	68b35e936681b	MCTFUMRFG	dni	1	908937	777068	1022145	2025-08-30 20:26:59.000	2025-11-24 10:17:14.000	2025-08-30 22:37:26.000
552	68b3f5ce743b1	WJMWLYLHW	dni	1	908950	777088	1022290	2025-08-31 07:12:14.000	2025-11-24 10:17:14.000	2025-09-12 03:15:48.000
553	68b41703507de	XAEADFOTE	dni	1	908655	777105	1020498	2025-08-31 09:33:55.000	2025-11-24 10:17:14.000	2025-08-31 11:43:16.000
554	68b42bddbeaf9	TZEVJWGDT	dni	1	908977	777117	1022482	2025-08-31 11:02:53.000	2025-11-24 10:17:14.000	2025-09-01 18:14:16.000
555	68b4518cb952e	JCWCRGJYP	dni	0	909001	777135	1022597	2025-08-31 13:43:40.000	2025-11-24 10:17:14.000
556	68b47603e5aa7	UBLMTZPVC	dni	0	909026	777159	1022728	2025-08-31 16:19:15.000	2025-11-24 10:17:14.000
557	68b47c27e888c	MQQSNRKCM	dni	1	909025	777166	1020641	2025-08-31 16:45:27.000	2025-11-24 10:17:14.000	2025-08-31 18:56:43.000
558	68b48a1562a42	IUHVFNBMQ	dni	1	909047	777183	1022797	2025-08-31 17:44:53.000	2025-11-24 10:17:14.000	2025-09-03 11:57:59.000
559	68b49c266d0a9	AFDXVNAXU	dni	1	909060	777197	1022864	2025-08-31 19:01:58.000	2025-11-24 10:17:14.000	2025-09-02 11:01:42.000
560	68b4a72ad79ff	TLDCSBLRT	dni	1	909068	777213	1022870	2025-08-31 19:48:58.000	2025-11-24 10:17:14.000	2025-09-01 14:16:15.000
561	68b4b0535e00b	JNCTJFFQN	dni	1	908920	777225	1022075	2025-08-31 20:28:03.000	2025-11-24 10:17:14.000	2025-09-02 00:07:33.000
562	68b4c3866b5ed	SKXNDLOSQ	escopeta	0	909089	777238	1023025	2025-08-31 21:49:58.000	2025-11-24 10:17:14.000
563	68b57b1812def	PQYJXRIVV	dni	0	909149	777295	1023346	2025-09-01 10:53:12.000	2025-11-24 10:17:14.000
564	68b58176de73d	LCAQSJYQI	dni	1	909159	777302	1023277	2025-09-01 11:20:22.000	2025-11-24 10:17:15.000	2025-09-01 13:23:44.000
565	68b5b8f9b4da5	GTXFBBLEC	dni	0	909193	777355	1023729	2025-09-01 15:17:13.000	2025-11-24 10:17:15.000
566	68b5ccc520f99	ZDPFUAATR	rifle	0	270756	777372	1021334	2025-09-01 16:41:41.000	2025-11-24 10:17:15.000
567	68b5d8ad0cc88	DGHJCEVRG	dni	0	358313	777391	1023889	2025-09-01 17:32:29.000	2025-11-24 10:17:15.000
568	68b5f62eb72a4	JCHHGYJYD	dni	0	909237	777414	1023994	2025-09-01 19:38:22.000	2025-11-24 10:17:15.000
569	68b615a0d5f8f	YJPHHIWRS	dni	0	909220	777447	1023917	2025-09-01 21:52:32.000	2025-11-24 10:17:15.000
570	68b61da6efe2f	KDWYLXQEG	dni	1	233989	777451	1016692	2025-09-01 22:26:46.000	2025-11-24 10:17:15.000	2025-09-02 00:30:25.000
571	68b6a1a495b2e	IWZWGQKDO	dni	1	909287	777473	1024351	2025-09-02 07:49:56.000	2025-11-24 10:17:15.000	2025-09-02 09:53:11.000
572	68b6b9b708bd7	FKJBEVHTZ	dni	0	909299	777495	1024400	2025-09-02 09:32:39.000	2025-11-24 10:17:15.000
573	68b6d8336df9d	LXCXRFYNU	dni	0	182842	777533	1024463	2025-09-02 11:42:43.000	2025-11-24 10:17:15.000
574	68b704e586f70	TNETMAYHV	dni	0	909349	777577	1024802	2025-09-02 14:53:25.000	2025-11-24 10:17:15.000
575	68b7192a2e34c	WRCAFNEHH	corta	0	427456	777602	1024891	2025-09-02 16:19:54.000	2025-11-24 10:17:15.000
576	68b71bb5e5c35	XAIMYHCXX	dni	0	854929	777604	1023513	2025-09-02 16:30:45.000	2025-11-24 10:17:15.000
577	68b73df12bbc5	BBSBRENUW	escopeta	0	858789	777641	1025060	2025-09-02 18:56:49.000	2025-11-24 10:17:15.000
578	68b754c200416	HIXHITSUR	dni	0	909407	777656	1023053	2025-09-02 20:34:10.000	2025-11-24 10:17:15.000
579	68b766a283888	ZHLQTPNMR	dni	1	909242	777677	1024047	2025-09-02 21:50:26.000	2025-11-24 10:17:16.000	2025-09-03 00:16:48.000
580	68b7e5582d32d	INLJWKMGA	dni	0	908609	777698	1020248	2025-09-03 06:51:04.000	2025-11-24 10:17:16.000
581	68b7fba13b669	WUYKISAVU	dni	0	908609	777710	1025443	2025-09-03 08:26:09.000	2025-11-24 10:17:16.000
582	68b8218d15c00	YGHSVPFBG	dni	0	909479	777739	1025473	2025-09-03 11:07:57.000	2025-11-24 10:17:16.000
583	68b831903de98	ADPMBTOJO	dni	0	294671	777752	1024909	2025-09-03 12:16:16.000	2025-11-24 10:17:16.000
584	68b83422ae97a	LHDWBVXVV	dni	0	908461	777754	1022520	2025-09-03 12:27:14.000	2025-11-24 10:17:16.000
585	68b83a6bd2172	GJHBPTYSD	dni	1	909469	777760	1024652	2025-09-03 12:54:03.000	2025-11-24 10:17:16.000	2025-09-03 14:57:35.000
586	68b84dcd280fe	CLNFXHTZQ	dni	0	294671	777776	1025765	2025-09-03 14:16:45.000	2025-11-24 10:17:16.000
587	68b8524580733	UYLTFSCPC	dni	0	908743	777779	1020988	2025-09-03 14:35:49.000	2025-11-24 10:17:16.000
588	68b859d84b05b	DYNYDEWJN	rifle	1	281176	777784	1023970	2025-09-03 15:08:08.000	2025-11-24 10:17:16.000	2025-09-03 17:19:26.000
589	68b861126085a	DRKVOJRHY	dni	0	909516	777796	1025864	2025-09-03 15:38:58.000	2025-11-24 10:17:16.000
590	68b86dca06bcc	NSNRSEAPJ	dni	1	294671	777807	1025923	2025-09-03 16:33:14.000	2025-11-24 10:17:16.000	2025-09-05 15:28:57.000
591	68b8876ab0dae	PXQESLYUE	escopeta	1	301632	777832	1023751	2025-09-03 18:22:34.000	2025-11-24 10:17:16.000	2025-09-03 20:51:01.000
592	68b88db1d7d9e	YFWRCMYHX	escopeta	1	909541	777836	1026018	2025-09-03 18:49:21.000	2025-11-24 10:17:16.000	2025-09-04 16:25:03.000
593	68b8a0b5c9a7f	ULFSCCZFD	dni	0	909558	777858	1024784	2025-09-03 20:10:29.000	2025-11-24 10:17:16.000
594	68b8ab4945ae4	KAHYUXDCA	dni	1	909567	777867	1026170	2025-09-03 20:55:37.000	2025-11-24 10:17:17.000	2025-09-05 13:03:45.000
595	68b8aece964ef	HRTDDGGHY	dni	0	909429	777872	1025222	2025-09-03 21:10:38.000	2025-11-24 10:17:17.000
596	68b8cac820bbf	QEOKXRGSU	rifle	0	881375	777891	1026259	2025-09-03 23:10:00.000	2025-11-24 10:17:17.000
597	68b93a025062b	FCWSVNOZC	dni	0	909157	777900	1022121	2025-09-04 07:04:34.000	2025-11-24 10:17:17.000
598	68b94b0cdc6da	LMGXOIVWO	dni	0	878460	777906	1026383	2025-09-04 08:17:16.000	2025-11-24 10:17:17.000
599	68b94d55db8a3	DHFQOWOIE	dni	0	887434	777909	1021947	2025-09-04 08:27:01.000	2025-11-24 10:17:17.000
600	68b9775b48122	QAZMRHBZO	dni	0	909614	777931	1026549	2025-09-04 11:26:19.000	2025-11-24 10:17:17.000
601	68b97b848cbac	DSLUTLUFL	escopeta	0	858789	777936	1026583	2025-09-04 11:44:04.000	2025-11-24 10:17:17.000
602	68b99ae31d444	BFKLODEWZ	dni	1	909642	777958	1026665	2025-09-04 13:57:55.000	2025-11-24 10:17:17.000	2025-09-04 16:01:06.000
603	68b9ad385f0a9	DTFDIGDIU	dni	0	909655	777975	1026761	2025-09-04 15:16:08.000	2025-11-24 10:17:17.000	2025-09-04 17:17:19.000
604	68b9e03ce6c7f	FTDXOBWNI	dni	0	419819	778007	1021838	2025-09-04 18:53:48.000	2025-11-24 10:17:18.000
605	68b9fcec50979	GWOIQJJFS	escopeta	0	908381	778026	1026860	2025-09-04 20:56:12.000	2025-11-24 10:17:18.000
606	68ba0eb4abd46	YSLPHNJNG	dni	0	851931	778034	1027028	2025-09-04 22:12:04.000	2025-11-24 10:17:18.000
607	68ba17286a23a	WPRQWTTWW	dni	0	909700	778036	1027107	2025-09-04 22:48:08.000	2025-11-24 10:17:18.000
608	68ba1f5951867	LDKNTJHUQ	dni	0	295203	778039	1027101	2025-09-04 23:23:05.000	2025-11-24 10:17:18.000
609	68bb3370aae2f	YWHGVPNBF	dni	0	205308	778135	1027699	2025-09-05 19:01:04.000	2025-11-24 10:17:18.000
610	68bb40a3a78ca	IYPXBAVQT	dni	0	229876	778140	1027665	2025-09-05 19:57:23.000	2025-11-24 10:17:18.000
611	68bb45faef20f	AJCTXELSI	dni	0	909791	778144	1027734	2025-09-05 20:20:10.000	2025-11-24 10:17:18.000	2025-09-10 13:16:06.000
612	68bb4c9cc4335	QHRWPTHGC	dni	1	909793	778148	1027752	2025-09-05 20:48:28.000	2025-11-24 10:17:18.000	2025-09-05 22:55:03.000
613	68bb5a3019b53	UNSAKFIOR	dni	0	909798	778155	1027786	2025-09-05 21:46:24.000	2025-11-24 10:17:18.000
614	68bb5e1de4588	UJEBSPPCL	dni	0	801732	778157	1025950	2025-09-05 22:03:09.000	2025-11-24 10:17:18.000
615	68bbdaf2641e1	QJJBOJMYG	escopeta	0	391747	778167	1027864	2025-09-06 06:55:46.000	2025-11-24 10:17:18.000
616	68bc13bc78a2b	GYZYTWLAA	dni	1	909826	778194	1027983	2025-09-06 10:58:04.000	2025-11-24 10:17:18.000	2025-09-06 19:06:21.000
617	68bc3f4de9895	VCYIKYUXW	dni	0	909844	778214	1028082	2025-09-06 14:03:57.000	2025-11-24 10:17:19.000
618	68bc4566bdb3a	PGXITLOFG	dni	0	909847	778219	1028071	2025-09-06 14:29:58.000	2025-11-24 10:17:19.000
619	68bc5da5da008	KHFAYGOIR	dni	0	908943	778238	1022241	2025-09-06 16:13:25.000	2025-11-24 10:17:19.000
620	68bc5fbde3b9b	JRSFHWGOB	dni	0	909867	778243	1028212	2025-09-06 16:22:21.000	2025-11-24 10:17:19.000
621	68bc83afcf5eb	RNWJBCWZV	dni	0	896770	778261	1026775	2025-09-06 18:55:43.000	2025-11-24 10:17:19.000
622	68bc9a0db8a51	TNJGNFFGS	dni	1	909890	778276	1028352	2025-09-06 20:31:09.000	2025-11-24 10:17:19.000	2025-09-06 23:10:40.000
623	68bd52467ccdb	EZFUKLAPH	dni	0	909921	778316	1028566	2025-09-07 09:37:10.000	2025-11-24 10:17:19.000
624	68bd8a4f907a2	SEIXMMWKA	dni	1	909953	778348	1028690	2025-09-07 13:36:15.000	2025-11-24 10:17:19.000	2025-09-08 17:16:48.000
625	68bd90112b32c	IFGITEFJV	dni	1	909951	778352	1028507	2025-09-07 14:00:49.000	2025-11-24 10:17:19.000	2025-09-07 16:07:25.000
626	68bdb6f5ef9e2	GJLNSSNSU	dni	1	908589	778378	1028838	2025-09-07 16:46:45.000	2025-11-24 10:17:19.000	2025-09-07 18:49:07.000
627	68be91d2996e1	RVFGHOSRD	dni	1	910037	778448	1023882	2025-09-08 08:20:34.000	2025-11-24 10:17:19.000	2025-09-11 18:47:54.000
628	68beb6d4986fa	RZAIEDXNQ	dni	1	909747	778485	1027507	2025-09-08 10:58:28.000	2025-11-24 10:17:19.000	2025-09-08 13:57:18.000
629	68bed2b2aa0f7	AZTHVIMWC	dni	0	910079	778507	1029030	2025-09-08 12:57:22.000	2025-11-24 10:17:20.000
630	68bf5ca8158b8	GVMTIEEUA	dni	0	910167	778628	1026277	2025-09-08 22:46:00.000	2025-11-24 10:17:20.000
631	68c01719d348c	GPFJRJNIA	dni	1	228603	778703	1028458	2025-09-09 12:01:29.000	2025-11-24 10:17:20.000	2025-09-09 14:02:22.000
632	68c0385427469	NWGLKXYZU	dni	0	908136	778734	1017841	2025-09-09 14:23:16.000	2025-11-24 10:17:20.000
633	68c0482822795	PAOPROJCH	dni	0	910093	778753	1029628	2025-09-09 15:30:48.000	2025-11-24 10:17:20.000
634	68c058621c3f5	GEOPNAFZM	dni	1	910284	778773	1030786	2025-09-09 16:40:02.000	2025-11-24 10:17:20.000	2025-09-09 18:56:02.000
635	68c065b1bf301	EGONLCBQX	dni	0	910281	778783	1029556	2025-09-09 17:36:49.000	2025-11-24 10:17:20.000
636	68c0701d0010a	VXQJEGSXO	dni	1	838759	778797	1030895	2025-09-09 18:21:17.000	2025-11-24 10:17:20.000	2025-09-09 20:25:09.000
637	68c0833ae096a	DSBXHHDYZ	dni	0	910311	778810	1030991	2025-09-09 19:42:50.000	2025-11-24 10:17:20.000
638	68c089f9bff14	KZMAMIYOL	dni	0	391836	778818	1031021	2025-09-09 20:11:37.000	2025-11-24 10:17:20.000
639	68c0c5c0e5c3e	OVCPCUDGX	dni	1	910339	778847	1031195	2025-09-10 00:26:40.000	2025-11-24 10:17:20.000	2025-09-10 23:37:21.000
640	68c1351e1be4e	MVLNGEROG	dni	0	910362	778875	1031062	2025-09-10 08:21:50.000	2025-11-24 10:17:20.000
641	68c1435062692	HZLWUXGNK	dni	0	881119	778887	1026407	2025-09-10 09:22:24.000	2025-11-24 10:17:20.000
642	68c19c0163853	KXXDNTGQJ	dni	0	384539	778962	1031748	2025-09-10 15:40:49.000	2025-11-24 10:17:20.000
643	68c19e4c912f1	UQMFWCWLI	dni	0	910434	778965	1031728	2025-09-10 15:50:36.000	2025-11-24 10:17:21.000
644	68c1c228275c8	PCRFVXZUQ	dni	0	910444	778991	1031806	2025-09-10 18:23:36.000	2025-11-24 10:17:21.000
645	68c1dd8b0785d	VAIJJFXMC	dni	0	909778	779015	1027683	2025-09-10 20:20:27.000	2025-11-24 10:17:21.000
646	68c1f22d75a79	TGHUGYBMX	dni	0	910486	779038	1029319	2025-09-10 21:48:29.000	2025-11-24 10:17:21.000
647	68c1fb103085d	BJEQQJRTG	dni	0	910492	779046	1032076	2025-09-10 22:26:24.000	2025-11-24 10:17:21.000
648	68c205f7bc66b	NVMRECOHR	dni	1	385063	779052	1027816	2025-09-10 23:12:55.000	2025-11-24 10:17:21.000	2025-09-11 09:23:27.000
649	68c28738c94b3	PZUUZFVQB	dni	1	800887	779079	1030516	2025-09-11 08:24:24.000	2025-11-24 10:17:21.000	2025-09-11 10:26:34.000
650	68c2ae4f48cfa	QHFKFIAEI	dni	0	910534	779120	1032417	2025-09-11 11:11:11.000	2025-11-24 10:17:21.000
651	68c2c0b02d042	GKDHRHSRG	dni	0	910546	779137	1032507	2025-09-11 12:29:36.000	2025-11-24 10:17:21.000
652	68c3063ce06e2	NLDJHJGQG	dni	0	887597	777934	1026394	2025-09-11 17:26:20.000	2025-11-24 10:17:21.000
653	68c3350da5144	FQVXQQDPD	dni	0	910610	779221	1028162	2025-09-11 20:46:05.000	2025-11-24 10:17:21.000
654	68c3412cf25f0	PKGIKASMP	dni	0	910621	779229	1032934	2025-09-11 21:37:48.000	2025-11-24 10:17:21.000
655	68c34ad3b64c3	KWQRPKJMP	dni	1	86169	779236	1015870	2025-09-11 22:18:59.000	2025-11-24 10:17:22.000	2025-09-12 00:21:43.000
656	68c36e028a84a	GFIQBUAEY	dni	0	816957	779243	1033071	2025-09-12 00:49:06.000	2025-11-24 10:17:22.000
657	68c3d2955b01b	NSENHCDNY	dni	0	909558	779260	1033175	2025-09-12 07:58:13.000	2025-11-24 10:17:22.000
658	68c3fc971960e	DSLUTLUFL	rifle	0	857090	777936	1026414	2025-09-12 10:57:27.000	2025-11-24 10:17:22.000
659	68c433d99567e	MWWAUFARI	dni	1	910679	779325	1033485	2025-09-12 14:53:13.000	2025-11-24 10:17:22.000	2025-09-12 16:54:54.000
660	68c459ffd352e	NJJYTKHKC	dni	0	341048	779354	1031168	2025-09-12 17:35:59.000	2025-11-24 10:17:22.000
661	68c52d1fa9c58	NMZTLMBKY	dni	0	910743	779395	1033856	2025-09-13 08:36:47.000	2025-11-24 10:17:22.000	2025-09-13 10:40:14.000
662	68c53615735c5	MBXVKHHRH	dni	0	910743	779404	1033889	2025-09-13 09:15:01.000	2025-11-24 10:17:22.000	2025-09-13 11:17:06.000
663	68c54379d1b81	NUZKKOJYT	dni	1	910627	779411	1033573	2025-09-13 10:12:09.000	2025-11-24 10:17:22.000	2025-09-17 09:02:08.000
664	68c58b30e19d2	UMUMPAMQV	dni	0	910791	779439	1034121	2025-09-13 15:18:08.000	2025-11-24 10:17:22.000
665	68c5b713bae76	DUCQGCVSI	dni	0	910809	779459	1034235	2025-09-13 18:25:23.000	2025-11-24 10:17:22.000
666	68c5e7f1f26fe	VZNKZGSLI	dni	0	910826	779476	1034348	2025-09-13 21:53:53.000	2025-11-24 10:17:22.000
667	68c5f8829bf93	RGFHGJASH	dni	0	403856	779482	1034369	2025-09-13 23:04:34.000	2025-11-24 10:17:22.000
668	68c681ea0ec04	JAHLBHPJT	dni	1	910848	779504	1034490	2025-09-14 08:50:50.000	2025-11-24 10:17:22.000	2025-09-14 10:59:10.000
669	68c68cbfaafb7	BHPJDKMZX	dni	0	910854	779509	1027416	2025-09-14 09:37:03.000	2025-11-24 10:17:22.000
670	68c6ddcca90af	UNUVAILFD	dni	0	910899	779555	1034754	2025-09-14 15:22:52.000	2025-11-24 10:17:22.000
671	68c6e300d006a	BSDOQNOIO	corta	0	276213	779560	1034768	2025-09-14 15:45:04.000	2025-11-24 10:17:23.000
672	68c6e53d1f6d1	JGHRKTIFV	dni	1	898827	779562	1034762	2025-09-14 15:54:37.000	2025-11-24 10:17:23.000	2025-09-14 20:12:12.000
673	68c6f37444f82	JNDSXEZUJ	escopeta	0	910914	779574	1034844	2025-09-14 16:55:16.000	2025-11-24 10:17:23.000
674	68c7038824eac	XXACTMEOD	dni	0	910921	779585	1034891	2025-09-14 18:03:52.000	2025-11-24 10:17:23.000
675	68c7210db1f60	HVWMSSLYK	dni	1	910789	779605	1034924	2025-09-14 20:09:49.000	2025-11-24 10:17:23.000	2025-09-14 22:22:49.000
676	68c72e2818f4a	WDXHPWWPP	rifle	0	897786	779618	1034857	2025-09-14 21:05:44.000	2025-11-24 10:17:23.000
677	68c7f1e0b1831	JVRLRQJHN	dni	1	910994	779680	1035214	2025-09-15 11:00:48.000	2025-11-24 10:17:23.000	2025-09-15 13:03:26.000
678	68c8169eb3222	JARGIJJPP	dni	1	910731	779705	1033947	2025-09-15 13:37:34.000	2025-11-24 10:17:23.000	2025-09-15 19:00:54.000
679	68c8319f8eea7	JBGMHKTQU	dni	0	911027	779729	1035629	2025-09-15 15:32:47.000	2025-11-24 10:17:23.000
680	68c831a95924d	IUFSYFQRN	dni	1	911023	779730	1035608	2025-09-15 15:32:57.000	2025-11-24 10:17:23.000	2025-09-15 17:57:34.000
681	68c85f9da173e	OBBXBXZUZ	rifle	0	859512	779779	1035828	2025-09-15 18:49:01.000	2025-11-24 10:17:23.000
682	68c867e073882	PDCIJXJCV	dni	0	911078	779786	1035875	2025-09-15 19:24:16.000	2025-11-24 10:17:24.000
683	68c87a046d566	TQSQIMGCM	dni	0	911069	779806	1035816	2025-09-15 20:41:40.000	2025-11-24 10:17:24.000
684	68c88f2c09d09	WJLKOFPWK	dni	0	876254	779820	1036055	2025-09-15 22:11:56.000	2025-11-24 10:17:24.000
685	68c8929c0ebb7	YXMHFSUXK	dni	0	911108	779822	1036032	2025-09-15 22:26:36.000	2025-11-24 10:17:24.000
686	68c921e1988f9	RRZBWRKFW	corta	1	326004	779856	1036211	2025-09-16 08:37:53.000	2025-11-24 10:17:24.000	2025-09-21 08:17:27.000
687	68c93184be891	XKNAGIXCS	dni	0	356793	779873	1030907	2025-09-16 09:44:36.000	2025-11-24 10:17:24.000
688	68c93fcc28ddb	ESMRKQKHR	dni	0	911153	779887	1036397	2025-09-16 10:45:32.000	2025-11-24 10:17:24.000
689	68c945b7528b6	OLUOQVGET	dni	1	109787	779896	1036441	2025-09-16 11:10:47.000	2025-11-24 10:17:24.000	2025-09-16 13:12:20.000
690	68c949e12ba52	GBZCSHQMQ	dni	1	911158	779897	1036452	2025-09-16 11:28:33.000	2025-11-24 10:17:24.000	2025-09-20 15:48:13.000
691	68c95152315e7	BFUTVSNKL	rifle	0	857090	779636	1035202	2025-09-16 12:00:18.000	2025-11-24 10:17:24.000
692	68c962afedf4a	XXEPBCMIL	escopeta	0	812519	779638	1035203	2025-09-16 13:14:23.000	2025-11-24 10:17:25.000
693	68c964f8052bb	WISWAROVW	dni	1	911181	779928	1036697	2025-09-16 13:24:08.000	2025-11-24 10:17:25.000	2025-09-18 12:07:50.000
694	68c983792c4d3	XRWEYTITM	dni	0	910962	779646	1035224	2025-09-16 15:34:17.000	2025-11-24 10:17:25.000
695	68c991258671e	JLXBFCEPB	dni	1	911214	779977	1037023	2025-09-16 16:32:37.000	2025-11-24 10:17:25.000	2025-09-16 18:43:54.000
696	68c99efad6fd5	AYUQGKHJY	dni	0	899102	779994	1031235	2025-09-16 17:31:38.000	2025-11-24 10:17:25.000	2025-09-16 19:59:38.000
697	68c9a407ab134	LASOZHVJE	dni	0	911229	779997	1037158	2025-09-16 17:53:11.000	2025-11-24 10:17:25.000
698	68c9cccdcb45b	YARJZWZFL	dni	1	911265	780037	1037328	2025-09-16 20:47:09.000	2025-11-24 10:17:25.000	2025-09-16 22:52:35.000
699	68ca66e24a4e3	VADAMGKJY	dni	0	297318	779650	1035229	2025-09-17 07:44:34.000	2025-11-24 10:17:25.000	2025-09-17 09:49:22.000
700	68ca68bdd1381	VQQKZOWPZ	dni	0	297318	779651	1035238	2025-09-17 07:52:29.000	2025-11-24 10:17:25.000	2025-09-17 09:53:59.000
701	68ca6f157e7cb	DOIYVLJUJ	dni	0	911303	780087	1037706	2025-09-17 08:19:33.000	2025-11-24 10:17:25.000
702	68caadb7b0a18	UVNYQZDCJ	dni	1	371898	780146	1038031	2025-09-17 12:46:47.000	2025-11-24 10:17:25.000	2025-09-17 14:49:49.000
703	68cace9cef075	BLZPTTRNU	dni	0	911352	780175	1037450	2025-09-17 15:07:08.000	2025-11-24 10:17:26.000
704	68cae21155e5f	JRFQMZTBX	dni	1	911223	780190	1037049	2025-09-17 16:30:09.000	2025-11-24 10:17:26.000	2025-09-17 18:34:26.000
705	68caf38dba165	XCOCJTVQE	rifle	0	910975	779660	1035262	2025-09-17 17:44:45.000	2025-11-24 10:17:26.000
706	68cb1275d4426	YAZXIHFTR	dni	1	911397	780238	1038632	2025-09-17 19:56:37.000	2025-11-24 10:17:26.000	2025-09-17 22:13:23.000
707	68cb4221bddcb	PGTNCUQMM	dni	0	911428	780269	1038782	2025-09-17 23:20:01.000	2025-11-24 10:17:26.000
708	68cb6c5ebb5d6	CENBABMAC	dni	0	874872	780274	1038840	2025-09-18 02:20:14.000	2025-11-24 10:17:26.000
709	68cbb28714950	EMXIRREAT	dni	1	911438	780289	1038907	2025-09-18 07:19:35.000	2025-11-24 10:17:26.000	2025-09-18 09:56:45.000
710	68cbd747c9e2a	ONUDYEHMK	dni	0	910578	780312	1030834	2025-09-18 09:56:23.000	2025-11-24 10:17:26.000
711	68cbf421ae058	ZPLHFVVJT	dni	0	911469	780340	1037190	2025-09-18 11:59:29.000	2025-11-24 10:17:26.000
712	68cc237028825	UMBTVOEFH	dni	1	80700	780378	1036421	2025-09-18 15:21:20.000	2025-11-24 10:17:26.000	2025-09-18 17:23:32.000
713	68cc3a0dbc45b	VCZBTZNVH	escopeta	0	910979	779664	1035273	2025-09-18 16:57:49.000	2025-11-24 10:17:26.000
714	68cc466ea9654	ZAMBDSCJH	dni	1	911513	780397	1038676	2025-09-18 17:50:38.000	2025-11-24 10:17:26.000	2025-09-18 20:12:43.000
715	68cc55cf38003	LKSJJCIXG	dni	1	863620	780409	1035775	2025-09-18 18:56:15.000	2025-11-24 10:17:26.000	2025-09-18 21:41:12.000
716	68cc67c0b2811	EWZYTRYXE	dni	0	911535	780426	1038610	2025-09-18 20:12:48.000	2025-11-24 10:17:26.000
717	68cc6a76d47e6	CQYNXMSWL	dni	0	911538	780431	1039691	2025-09-18 20:24:22.000	2025-11-24 10:17:27.000
718	68cc6c896bff8	FWHHPSLVW	dni	1	911546	780432	1039666	2025-09-18 20:33:13.000	2025-11-24 10:17:27.000	2025-09-22 18:35:59.000
719	68cc785c7ad60	WYEGZMCRG	dni	1	911551	780440	1039745	2025-09-18 21:23:40.000	2025-11-24 10:17:27.000	2025-09-23 19:19:27.000
720	68cd0fbc3f35b	BJOSJQNAK	dni	1	890168	780466	1039923	2025-09-19 08:09:32.000	2025-11-24 10:17:27.000	2025-09-19 11:41:35.000
721	68cd19aee2a0a	CGMNFGREN	escopeta	0	297318	779669	1035281	2025-09-19 08:51:58.000	2025-11-24 10:17:27.000	2025-09-19 10:52:41.000
722	68cd1fdd6201e	AWRHSVABD	dni	0	297318	779670	1035282	2025-09-19 09:18:21.000	2025-11-24 10:17:27.000	2025-09-19 11:18:54.000
723	68cd209ccb80f	ZMEUMOFTW	rifle	0	297318	779671	1035284	2025-09-19 09:21:32.000	2025-11-24 10:17:27.000
724	68cd23f331b0a	RACHTLBDQ	rifle	0	297318	779673	1035287	2025-09-19 09:35:47.000	2025-11-24 10:17:27.000
725	68cd2af2e81e8	DGHISJAHI	escopeta	0	887597	779678	1035297	2025-09-19 10:05:38.000	2025-11-24 10:17:27.000	2025-09-19 12:07:21.000
726	68cd2b9fc2ea8	ZMHQGNLHB	dni	0	911590	780484	1040053	2025-09-19 10:08:31.000	2025-11-24 10:17:27.000
727	68cd4eef52998	CMEGPENPL	dni	1	908220	780515	1040246	2025-09-19 12:39:11.000	2025-11-24 10:17:27.000	2025-09-19 14:40:33.000
728	68cd643a462c8	GFTIHQYHC	dni	0	911616	780530	1035106	2025-09-19 14:10:02.000	2025-11-24 10:17:27.000
729	68cd67e09a2b1	JHOUGFQTL	dni	0	400873	780533	1040387	2025-09-19 14:25:36.000	2025-11-24 10:17:27.000
730	68cd692606067	RQBSWITCL	escopeta	0	910990	779688	1035343	2025-09-19 14:31:02.000	2025-11-24 10:17:27.000
731	68cd76e9e9e5d	PMSOAQPGJ	dni	0	911632	780550	1038428	2025-09-19 15:29:45.000	2025-11-24 10:17:28.000
732	68cd7a2ae1a1c	HZEVIUOER	dni	0	911635	780554	1040488	2025-09-19 15:43:38.000	2025-11-24 10:17:28.000
733	68cd7f1636085	UJEAMEXMW	dni	0	911640	780562	1034572	2025-09-19 16:04:38.000	2025-11-24 10:17:28.000
734	68cd8cd80da0e	EWSQEHZXN	dni	1	911283	780574	1037598	2025-09-19 17:03:20.000	2025-11-24 10:17:28.000	2025-09-19 19:11:43.000
735	68cdac07c337a	IOHTTYSSX	escopeta	0	911001	779691	1035349	2025-09-19 19:16:23.000	2025-11-24 10:17:28.000
736	68cdadfbd0665	JZTLBCYLZ	rifle	0	887597	779692	1035350	2025-09-19 19:24:43.000	2025-11-24 10:17:28.000
737	68cdaf6feba1f	VAXNNQSCJ	rifle	0	887597	779693	1035351	2025-09-19 19:30:55.000	2025-11-24 10:17:28.000
738	68ce67a24b641	VSOAUDJWA	dni	1	911702	780641	1041014	2025-09-20 08:36:50.000	2025-11-24 10:17:28.000	2025-09-20 10:50:28.000
739	68ce96561a410	QVSGXSGSW	dni	1	911722	780677	1041090	2025-09-20 11:56:06.000	2025-11-24 10:17:29.000	2025-09-20 21:32:39.000
740	68cf11b1b7c3e	PTRZRDKZP	dni	0	850415	780740	1041506	2025-09-20 20:42:25.000	2025-11-24 10:17:29.000
741	68cf1dcdaad3e	KQVFYOYTR	dni	0	365199	780745	1041609	2025-09-20 21:34:05.000	2025-11-24 10:17:29.000
742	68cfa16a8eba7	VCBJBNXIB	dni	1	911799	780757	1041741	2025-09-21 06:55:38.000	2025-11-24 10:17:29.000	2025-09-21 09:15:02.000
743	68cfbf26dbc70	QSFZFIKNK	dni	0	906527	780763	1041801	2025-09-21 09:02:30.000	2025-11-24 10:17:29.000
744	68cfc4b6d5fc3	WXPSMDAGP	dni	0	911806	780770	1041803	2025-09-21 09:26:14.000	2025-11-24 10:17:29.000
745	68cfcdae55de2	IKDRXXOKM	dni	1	911813	780778	1035057	2025-09-21 10:04:30.000	2025-11-24 10:17:29.000	2025-09-21 12:11:16.000
746	68cfe03b43173	LYUVIKLAL	dni	0	425784	780794	1040143	2025-09-21 11:23:39.000	2025-11-24 10:17:29.000
747	68cfe268e78b6	UJGAOVSHS	dni	1	911832	780796	1041315	2025-09-21 11:32:56.000	2025-11-24 10:17:29.000	2025-09-21 13:42:00.000
748	68cffd1924e84	CLSTUNBST	dni	0	911846	780816	1025539	2025-09-21 13:26:49.000	2025-11-24 10:17:30.000
749	68d0211709811	LROQHUZKB	dni	0	911871	780839	1042167	2025-09-21 16:00:23.000	2025-11-24 10:17:30.000
750	68d02c9d005c3	UGACGWKZF	dni	1	911878	780849	1037229	2025-09-21 16:49:33.000	2025-11-24 10:17:30.000	2025-09-21 19:37:11.000
751	68d03234e8b28	HLSXZCCJR	dni	1	911880	780852	1042227	2025-09-21 17:13:24.000	2025-11-24 10:17:30.000	2025-09-21 19:26:43.000
752	68d052094655b	BZFOFNNNP	dni	0	911904	780887	1025875	2025-09-21 19:29:13.000	2025-11-24 10:17:30.000
753	68d05903b0493	LAHBKMIEY	dni	1	415092	780891	1042450	2025-09-21 19:58:59.000	2025-11-24 10:17:30.000	2025-09-22 14:55:29.000
754	68d0612f8aa06	PNOYXQMHJ	dni	1	911912	780908	1041598	2025-09-21 20:33:51.000	2025-11-24 10:17:30.000	2025-09-21 22:40:20.000
755	68d06494a071c	SUERMYAMF	dni	0	909770	780912	1027640	2025-09-21 20:48:20.000	2025-11-24 10:17:30.000
756	68d088c17e777	YHFHRJJVQ	dni	0	820008	780938	1042611	2025-09-21 23:22:41.000	2025-11-24 10:17:30.000
758	68d0c57309162	KEPVNRQZV	dni	0	909953	780944	1042638	2025-09-22 03:41:39.000	2025-11-24 10:17:30.000	2025-09-22 09:05:58.000
759	68d1034f2cb3d	RTOARQAPW	dni	0	842290	780967	1042780	2025-09-22 08:05:35.000	2025-11-24 10:17:30.000
760	68d119a947554	UKOUXDFAU	dni	1	911980	780996	1042836	2025-09-22 09:40:57.000	2025-11-24 10:17:31.000	2025-09-22 11:43:06.000
761	68d145b9c506c	SAAZGEAZP	dni	1	912018	781053	1043175	2025-09-22 12:48:57.000	2025-11-24 10:17:31.000	2025-09-22 14:56:27.000
762	68d14a6a45df2	VNINYBTCN	dni	0	312477	781062	1043183	2025-09-22 13:08:58.000	2025-11-24 10:17:31.000
763	68d1578bf0d91	XGBURHBAH	dni	0	912027	781080	1043234	2025-09-22 14:04:59.000	2025-11-24 10:17:31.000
764	68d16b018c633	SJZMGDAEP	dni	0	912046	781097	1042068	2025-09-22 15:28:01.000	2025-11-24 10:17:31.000
765	68d17d1d4495d	NGFHDTTXX	dni	0	912071	781121	1043485	2025-09-22 16:45:17.000	2025-11-24 10:17:31.000
766	68d1869c9fb6e	RVIMROPIW	dni	0	912037	781136	1043299	2025-09-22 17:25:48.000	2025-11-24 10:17:31.000
767	68d18daa938d1	SQYOXWMUI	dni	0	911951	781145	1043308	2025-09-22 17:55:54.000	2025-11-24 10:17:31.000
768	68d1c29fb6b17	ULQCGLHCA	dni	1	912144	781228	1043891	2025-09-22 21:41:51.000	2025-11-24 10:17:31.000	2025-09-22 23:42:49.000
769	68d1c8944400c	MYKFUGZNY	dni	0	912150	781234	1043919	2025-09-22 22:07:16.000	2025-11-24 10:17:31.000
770	68d1cd9b8325c	DUFPVJQNY	dni	1	912153	781238	1043932	2025-09-22 22:28:43.000	2025-11-24 10:17:31.000	2025-09-23 00:42:31.000
771	68d26ababe6d2	TIKRFJQLR	dni	0	912182	781278	1044133	2025-09-23 09:39:06.000	2025-11-24 10:17:31.000
772	68d2729671afa	BICUDXWBW	corta	0	912185	781280	1044206	2025-09-23 10:12:38.000	2025-11-24 10:17:32.000
773	68d28232932c1	UIUEOKWDB	dni	0	842290	781293	1044307	2025-09-23 11:19:14.000	2025-11-24 10:17:32.000
774	68d29e5064e1b	BNKTVBHOV	escopeta	0	912217	781331	1044468	2025-09-23 13:19:12.000	2025-11-24 10:17:32.000	2025-09-23 18:25:24.000
775	68d2a799e1172	WPUIBZLYE	dni	0	160337	781340	1044570	2025-09-23 13:58:49.000	2025-11-24 10:17:32.000
776	68d2c92d01fab	RWIAJZZSM	dni	0	894211	781378	1044778	2025-09-23 16:22:05.000	2025-11-24 10:17:32.000
777	68d2d08447498	GTXXTHPCU	dni	1	912191	781384	1044002	2025-09-23 16:53:24.000	2025-11-24 10:17:32.000	2025-09-23 18:58:14.000
778	68d2de5865bfb	QPJIRWNZG	dni	0	843636	781395	1042778	2025-09-23 17:52:24.000	2025-11-24 10:17:32.000
779	68d30b6274ba6	CVBTUTDON	dni	0	912303	781437	1045177	2025-09-23 21:04:34.000	2025-11-24 10:17:32.000
780	68d315327ad13	KAWCGIUNW	dni	1	912308	781446	1045229	2025-09-23 21:46:26.000	2025-11-24 10:17:32.000	2025-09-24 02:22:45.000
781	68d315bd06d6b	DKLHEUYPO	corta	0	285545	781447	1045252	2025-09-23 21:48:45.000	2025-11-24 10:17:32.000
782	68d32f83c5fed	JYUISJUYL	dni	1	899240	781456	1038974	2025-09-23 23:38:43.000	2025-11-24 10:17:32.000	2025-09-24 12:27:01.000
783	68d32f8e70d17	AYTQKVWGD	dni	0	912317	781457	1044856	2025-09-23 23:38:54.000	2025-11-24 10:17:32.000
784	68d36ebb71f15	FXZASDQNF	dni	0	334640	781462	1045356	2025-09-24 04:08:27.000	2025-11-24 10:17:32.000
785	68d3cba9206c6	UAIWVNDFD	dni	1	912365	781519	1045672	2025-09-24 10:44:57.000	2025-11-24 10:17:32.000	2025-09-24 12:47:53.000
786	68d3d79380ca4	TYZDTFFSO	dni	0	323678	781523	1045733	2025-09-24 11:35:47.000	2025-11-24 10:17:32.000
787	68d3e1b35ff02	AEWWAVELA	dni	0	258257	781533	1045792	2025-09-24 12:18:59.000	2025-11-24 10:17:33.000
788	68d3f041d0d5f	VYYILPHZQ	dni	1	907261	781542	1045652	2025-09-24 13:21:05.000	2025-11-24 10:17:33.000	2025-09-24 15:22:43.000
789	68d4065a48f90	QWCWUEGUD	dni	0	912412	781568	1046035	2025-09-24 14:55:22.000	2025-11-24 10:17:33.000
790	68d4152b6ed04	AKNJWRHBC	dni	0	881119	781587	1045546	2025-09-24 15:58:35.000	2025-11-24 10:17:33.000
791	68d431a3255a2	YVQHMUQXA	dni	0	912452	781604	1046248	2025-09-24 18:00:03.000	2025-11-24 10:17:33.000
792	68d45c8d82b03	XTMUDQSJP	dni	0	912485	781641	1046497	2025-09-24 21:03:09.000	2025-11-24 10:17:33.000
793	68d46ecf94a88	IGTGEVWWK	dni	0	912498	781655	1046597	2025-09-24 22:21:03.000	2025-11-24 10:17:33.000
794	68d471c4ae5a0	GEMQTYSYC	dni	1	887814	781658	1046593	2025-09-24 22:33:40.000	2025-11-24 10:17:33.000	2025-09-25 00:40:57.000
795	68d4b30ae35e4	ELAGZOPKT	dni	1	374091	781667	1046666	2025-09-25 03:12:10.000	2025-11-24 10:17:33.000	2025-09-25 05:16:27.000
796	68d4f3b43aa4b	LNIKCOHXC	dni	0	352267	781675	1046753	2025-09-25 07:48:04.000	2025-11-24 10:17:34.000
797	68d512b61ec5b	ERMMBUQKZ	dni	0	354128	781695	1046904	2025-09-25 10:00:22.000	2025-11-24 10:17:34.000
798	68d53e9b1cb49	WKTGKZCPX	rifle	0	341802	781723	1047094	2025-09-25 13:07:39.000	2025-11-24 10:17:34.000
799	68d599019e460	YOQVHMFME	rifle	1	270123	781773	1047446	2025-09-25 19:33:21.000	2025-11-24 10:17:34.000	2025-09-25 21:38:14.000
800	68d5b16aec212	TACECAPPE	dni	0	912592	781791	1047337	2025-09-25 21:17:30.000	2025-11-24 10:17:34.000
801	68d5bae7d4d7d	NDBQEKFJZ	dni	0	912640	781798	1047609	2025-09-25 21:57:59.000	2025-11-24 10:17:34.000
802	68d5c7228a163	NQMAKKXAN	dni	0	855169	781803	1047643	2025-09-25 22:50:10.000	2025-11-24 10:17:34.000
803	68d64ff283ff9	AAMHDLFJR	dni	0	911216	781819	1047784	2025-09-26 08:33:54.000	2025-11-24 10:17:34.000
804	68d66da340a5b	UAIIXCNRN	dni	1	912674	781846	1047913	2025-09-26 10:40:35.000	2025-11-24 10:17:34.000	2025-09-26 12:44:16.000
805	68d6c0764600c	XKYHSISMW	rifle	0	215871	781909	1048244	2025-09-26 16:33:58.000	2025-11-24 10:17:34.000	2025-09-26 18:42:36.000
806	68d6fa36a9e7e	TIXAYQPIE	dni	0	337781	781954	1048469	2025-09-26 20:40:22.000	2025-11-24 10:17:34.000
807	68d72bca2fce5	HXFTJYZAJ	dni	0	912774	781970	1048566	2025-09-27 00:11:54.000	2025-11-24 10:17:35.000
808	68d7717d7050a	PMJEMTMYB	dni	1	912602	781973	1047381	2025-09-27 05:09:17.000	2025-11-24 10:17:35.000	2025-09-27 10:43:31.000
809	68d77cbebceff	WHBYGDWNQ	dni	1	912781	781975	1048605	2025-09-27 05:57:18.000	2025-11-24 10:17:35.000	2025-09-27 08:08:24.000
810	68d799c87217d	TOCQFZAFG	dni	1	912792	781986	1048657	2025-09-27 08:01:12.000	2025-11-24 10:17:35.000	2025-09-27 10:23:34.000
811	68d7b04838cd0	WTCAWPNFS	dni	0	912800	782000	1048720	2025-09-27 09:37:12.000	2025-11-24 10:17:35.000
812	68d7bb140a8b3	PQFJCJLFP	rifle	0	912791	782010	1048644	2025-09-27 10:23:16.000	2025-11-24 10:17:35.000
813	68d7ebc1cb081	WVYGVSVKN	dni	0	912828	782038	1048869	2025-09-27 13:50:57.000	2025-11-24 10:17:35.000
814	68d7fcdba95ec	KNQBKGFJF	dni	0	912777	782048	1048583	2025-09-27 15:03:55.000	2025-11-24 10:17:35.000	2025-09-27 17:06:22.000
815	68d81094a568d	USQJOAGBP	dni	1	912842	782060	1049025	2025-09-27 16:28:04.000	2025-11-24 10:17:35.000	2025-09-27 19:35:38.000
816	68d822a84d81c	XIGAGGBGA	dni	0	912849	782070	1049001	2025-09-27 17:45:12.000	2025-11-24 10:17:35.000
817	68d856597f580	JWGJXLELL	escopeta	0	911592	782096	1049259	2025-09-27 21:25:45.000	2025-11-24 10:17:35.000	2025-09-27 23:35:00.000
818	68d85f79c2a85	ROSGQEUEU	dni	0	416523	782099	1047566	2025-09-27 22:04:41.000	2025-11-24 10:17:35.000
819	68d8de72c63ac	FYZFTPUNK	dni	0	411417	782113	1049395	2025-09-28 07:06:26.000	2025-11-24 10:17:35.000
820	68d8e51b501f0	IEVZPTQPF	dni	1	912898	782117	1047765	2025-09-28 07:34:51.000	2025-11-24 10:17:36.000	2025-09-28 09:49:04.000
821	68d9079c0c09e	YJOATFGQL	dni	0	911699	782137	1049533	2025-09-28 10:02:04.000	2025-11-24 10:17:36.000
822	68d913587ee9b	GRKPINRVG	dni	0	912921	782148	1049618	2025-09-28 10:52:08.000	2025-11-24 10:17:36.000
823	68d92000d9692	VCQJRUXHV	dni	0	912935	782165	1049677	2025-09-28 11:46:08.000	2025-11-24 10:17:36.000
824	68d923266aa88	AFESTCSFO	dni	0	912939	782170	1049725	2025-09-28 11:59:34.000	2025-11-24 10:17:36.000
825	68d968a970fae	VWMYRRPGL	dni	1	912977	782233	1049975	2025-09-28 16:56:09.000	2025-11-24 10:17:36.000	2025-09-28 19:44:45.000
826	68d975aa1aecf	JCQKGRCLX	dni	1	912992	782245	1050202	2025-09-28 17:51:38.000	2025-11-24 10:17:36.000	2025-09-28 19:54:03.000
827	68d9935c2f0eb	VQVMNBTQM	dni	0	405365	782280	1050353	2025-09-28 19:58:20.000	2025-11-24 10:17:36.000
828	68d9a473e9019	QXYARJFCY	dni	0	913025	782302	1050440	2025-09-28 21:11:15.000	2025-11-24 10:17:36.000
829	68d9bc7c9636a	DUGMGQLFO	dni	1	913042	782318	1050539	2025-09-28 22:53:48.000	2025-11-24 10:17:36.000	2025-09-29 00:58:06.000
830	68d9ca400798d	XTYTLVCRK	dni	0	913045	782322	1050551	2025-09-28 23:52:32.000	2025-11-24 10:17:36.000
831	68da3b0aa31a0	SEZYQMZRA	dni	0	235926	782337	1050682	2025-09-29 07:53:46.000	2025-11-24 10:17:36.000
832	68da3de969819	ZONLKTYYV	dni	0	109638	782340	1050236	2025-09-29 08:06:01.000	2025-11-24 10:17:36.000
833	68da6fcd0a352	JLHRZLSVN	dni	0	361177	782417	1050990	2025-09-29 11:38:53.000	2025-11-24 10:17:37.000
834	68da7da779e22	QIIKQJYAM	dni	1	225928	782429	1051065	2025-09-29 12:37:59.000	2025-11-24 10:17:37.000	2025-09-29 14:39:22.000
835	68da82a2a92f4	XRHONAIMV	dni	0	906214	782433	1051090	2025-09-29 12:59:14.000	2025-11-24 10:17:37.000
836	68da84861cc60	LACVJXFVL	dni	1	298618	782435	1050857	2025-09-29 13:07:18.000	2025-11-24 10:17:37.000	2025-09-29 15:52:46.000
837	68daa6663e9d5	CMUXNTUGT	dni	0	912915	782472	1051247	2025-09-29 15:31:50.000	2025-11-24 10:17:37.000
838	68dad0755eedf	THYOJFUPF	dni	1	912042	782519	1051318	2025-09-29 18:31:17.000	2025-11-24 10:17:37.000	2025-09-29 20:41:18.000
839	68dadbaef3334	AZUGESDTE	dni	0	909770	782536	1040773	2025-09-29 19:19:10.000	2025-11-24 10:17:37.000
840	68dae511b8b73	KGDXSRYDI	dni	1	810970	782548	1051651	2025-09-29 19:59:13.000	2025-11-24 10:17:37.000	2025-09-30 11:00:58.000
841	68db039b13f4e	RWWAOJCRX	dni	0	913227	782584	1051782	2025-09-29 22:09:31.000	2025-11-24 10:17:37.000
842	68db14b49c70c	SNSKEWNOJ	dni	0	913238	782590	1051564	2025-09-29 23:22:28.000	2025-11-24 10:17:37.000
843	68db95fa8e592	CCENOKDJD	dni	1	912144	782614	1051171	2025-09-30 08:34:02.000	2025-11-24 10:17:37.000	2025-09-30 10:35:19.000
844	68db9b47a4308	YIPGINWEK	dni	1	913265	782621	1052009	2025-09-30 08:56:39.000	2025-11-24 10:17:38.000	2025-09-30 11:35:48.000
845	68db9eb1a130a	UFRSLLQJM	dni	0	863920	782628	1052016	2025-09-30 09:11:13.000	2025-11-24 10:17:38.000
846	68dbaae064034	HUHQQFQMY	dni	1	913277	782641	1044111	2025-09-30 10:03:12.000	2025-11-24 10:17:38.000	2025-09-30 12:13:11.000
847	68dbb7f988100	QZJXWXXQZ	dni	0	425784	782656	1042497	2025-09-30 10:59:05.000	2025-11-24 10:17:38.000
848	68dbceb8eb233	MHKRWMQWX	dni	0	913304	782687	1052274	2025-09-30 12:36:08.000	2025-11-24 10:17:38.000
849	68dc1ae30bab3	HLXKYFEZP	dni	1	249826	782769	1051829	2025-09-30 18:01:07.000	2025-11-24 10:17:38.000	2025-09-30 20:09:10.000
850	68dc397c27da9	OMHOFOLWU	dni	1	913359	782808	1052733	2025-09-30 20:11:40.000	2025-11-24 10:17:38.000	2025-09-30 22:34:41.000
851	68dc3ecb3bbf1	TMYWUNGON	dni	0	913376	782815	1052767	2025-09-30 20:34:19.000	2025-11-24 10:17:38.000
852	68dc4d105cad2	VBEHKVJEC	dni	0	913399	782845	1052940	2025-09-30 21:35:12.000	2025-11-24 10:17:38.000
853	68dc5203d0b92	EZQNWFHWZ	dni	0	911229	782853	1052863	2025-09-30 21:56:19.000	2025-11-24 10:17:38.000
854	68dcd1a9b8feb	JMIMUAIXB	dni	0	417376	782882	1053092	2025-10-01 07:00:57.000	2025-11-24 10:17:38.000
855	68dce6cb8a2bc	WEQVQOZDX	corta	1	913425	782888	1053138	2025-10-01 08:31:07.000	2025-11-24 10:17:38.000	2025-10-01 10:35:24.000
856	68dcffad7d9cc	TOVMVXYQJ	dni	1	910410	782912	1053273	2025-10-01 10:17:17.000	2025-11-24 10:17:38.000	2025-10-01 12:23:15.000
857	68dd20cc3e01d	JWDITJPLU	dni	1	913467	782949	1053390	2025-10-01 12:38:36.000	2025-11-24 10:17:38.000	2025-10-03 14:51:34.000
858	68dd2cf979fff	RFDVEUBPK	dni	0	913477	782962	1053377	2025-10-01 13:30:33.000	2025-11-24 10:17:39.000
859	68dd4c9d86104	RTCZIIXBX	dni	0	913496	782986	1053560	2025-10-01 15:45:33.000	2025-11-24 10:17:39.000
860	68dd546e4b15b	PSYZIQULG	dni	0	913501	782996	1053600	2025-10-01 16:18:54.000	2025-11-24 10:17:39.000
861	68dd6d0650c90	BKSTTLLAR	dni	0	913523	783019	1053679	2025-10-01 18:03:50.000	2025-11-24 10:17:39.000
862	68dd72816bde4	AAEZDBZAD	dni	0	357123	783023	1053713	2025-10-01 18:27:13.000	2025-11-24 10:17:39.000
863	68dd74d473476	KWYXLNFKT	dni	1	155870	783026	1053717	2025-10-01 18:37:08.000	2025-11-24 10:17:39.000	2025-10-01 20:39:43.000
864	68ddaf981a8d5	IAAHWDRVS	dni	0	912303	783076	1053957	2025-10-01 22:47:52.000	2025-11-24 10:17:39.000
865	68ddcd036150b	NFCJLFPLY	dni	0	274258	783080	1053944	2025-10-02 00:53:23.000	2025-11-24 10:17:39.000
866	68de3eb87475b	SGWMHMTPH	dni	0	913592	783104	1054113	2025-10-02 08:58:32.000	2025-11-24 10:17:39.000
867	68de87dfabe8f	UTMRWSTGF	dni	0	160337	783148	1054386	2025-10-02 14:10:39.000	2025-11-24 10:17:39.000
868	68de88fd14c87	JNWIICSGE	dni	0	305710	783150	1054330	2025-10-02 14:15:25.000	2025-11-24 10:17:39.000
869	68ded15b95d6f	GQAWWHOGR	dni	0	912873	783205	1054477	2025-10-02 19:24:11.000	2025-11-24 10:17:39.000
870	68df7377eb04c	SWNNGPKXP	dni	1	913704	783247	1054916	2025-10-03 06:55:51.000	2025-11-24 10:17:39.000	2025-10-03 14:09:12.000
871	68df9593c6e93	NCKGGFUWX	dni	0	913724	783268	1055026	2025-10-03 09:21:23.000	2025-11-24 10:17:39.000
872	68dfd96b6ee83	UVJFYJBQU	dni	1	912577	783325	1054459	2025-10-03 14:10:51.000	2025-11-24 10:17:39.000	2025-10-03 16:16:23.000
873	68dfdf76a7e2c	VNQQUTUZK	dni	0	912939	783328	1049791	2025-10-03 14:36:38.000	2025-11-24 10:17:39.000
874	68e002c1bcf8d	MVFIJSKYN	dni	0	913779	783361	1055440	2025-10-03 17:07:13.000	2025-11-24 10:17:39.000
875	68e03f06d0b6d	DXCZERRSB	dni	0	913790	783395	1055614	2025-10-03 21:24:22.000	2025-11-24 10:17:40.000
876	68e12eb560eb7	PSAYIKFTH	dni	1	912631	783457	1055949	2025-10-04 14:27:01.000	2025-11-24 10:17:40.000	2025-10-04 16:35:03.000
877	68e163c8b96a7	XRTHYSCUD	dni	1	910486	783483	1056145	2025-10-04 18:13:28.000	2025-11-24 10:17:40.000	2025-10-04 20:23:13.000
878	68e16ea431c9d	TWYGJCURX	dni	1	913884	783489	1056164	2025-10-04 18:59:48.000	2025-11-24 10:17:40.000	2025-10-04 21:02:06.000
879	68e194b84aae1	VRSWVMWEH	dni	1	913905	783510	1056293	2025-10-04 21:42:16.000	2025-11-24 10:17:40.000	2025-10-04 23:44:46.000
880	68e1c0bb9d2a4	SGXLNFEDR	rifle	0	215871	783517	1056328	2025-10-05 00:50:03.000	2025-11-24 10:17:40.000
881	68e2370124d0c	XPWJSFMDB	dni	0	913929	783537	1056452	2025-10-05 09:14:41.000	2025-11-24 10:17:40.000
882	68e2621f6e4e1	YHOQEWBZD	dni	0	913948	783570	1056585	2025-10-05 12:18:39.000	2025-11-24 10:17:40.000
883	68e26b16cf168	KMIPBVQME	dni	0	415497	783572	1056161	2025-10-05 12:56:54.000	2025-11-24 10:17:40.000
884	68e298c740d3e	ZBNFRGFGR	dni	0	913978	783602	1056777	2025-10-05 16:11:51.000	2025-11-24 10:17:40.000
885	68e2a043764af	XMSJPDWDU	dni	1	356486	783608	1056803	2025-10-05 16:43:47.000	2025-11-24 10:17:40.000	2025-10-05 19:08:20.000
886	68e2c69791014	BWSESITGR	dni	0	914020	783649	1056542	2025-10-05 19:27:19.000	2025-11-24 10:17:40.000
887	68e2d081907c1	BHGTMXLIV	dni	1	914020	783656	1056968	2025-10-05 20:09:37.000	2025-11-24 10:17:40.000	2025-10-05 22:30:56.000
888	68e2d0cd10df9	UQZKMOQAN	dni	0	884296	783657	1049171	2025-10-05 20:10:53.000	2025-11-24 10:17:40.000
889	68e2e48db470e	CZQEEAAGY	dni	0	914037	783676	1057079	2025-10-05 21:35:09.000	2025-11-24 10:17:40.000
890	68e3d96aea93e	CDZYVLUPP	dni	1	912971	783801	1057707	2025-10-06 14:59:54.000	2025-11-24 10:17:40.000	2025-10-06 17:11:09.000
891	68e3db1d95019	PUHXFQGSL	dni	0	914122	783805	1057717	2025-10-06 15:07:09.000	2025-11-24 10:17:40.000
892	68e3e7ac4d081	TABLAHQFN	dni	0	914134	783821	1057792	2025-10-06 16:00:44.000	2025-11-24 10:17:40.000
893	68e3ea6668949	NHVGYOSNV	dni	0	318129	783822	1056607	2025-10-06 16:12:22.000	2025-11-24 10:17:41.000
894	68e3eaee2b051	GVLREWKFS	dni	1	914132	783824	1057790	2025-10-06 16:14:38.000	2025-11-24 10:17:41.000	2025-10-08 10:52:45.000
895	68e412584ec29	XLMYCLORM	dni	0	914172	783876	1058030	2025-10-06 19:02:48.000	2025-11-24 10:17:41.000
896	68e4c4cbdc082	BJEBRGMRO	corta	0	914218	783947	1058387	2025-10-07 07:44:11.000	2025-11-24 10:17:41.000
897	68e4cd91b7da5	HHZLFXTIX	dni	0	908418	783954	1058426	2025-10-07 08:21:37.000	2025-11-24 10:17:41.000
898	68e525ab3f6f1	LPYNZKRGT	dni	0	883911	784033	1058818	2025-10-07 14:37:31.000	2025-11-24 10:17:41.000
899	68e5417726bda	WXXDPFXVT	dni	1	914319	784059	1058960	2025-10-07 16:36:07.000	2025-11-24 10:17:41.000	2025-10-07 18:46:55.000
900	68e580d130ffd	SNFZTCLDO	dni	1	247167	784126	1059289	2025-10-07 21:06:25.000	2025-11-24 10:17:41.000	2025-10-07 23:11:43.000
901	68e58fb96b807	TYXTKJBUA	dni	0	914387	784137	1059352	2025-10-07 22:10:01.000	2025-11-24 10:17:41.000
902	68e59ce8dba77	PDXBPNNIL	dni	1	283637	784144	1057085	2025-10-07 23:06:16.000	2025-11-24 10:17:41.000	2025-10-08 01:57:37.000
903	68e624b5c1b3f	VSLRKCGKM	dni	0	367120	784177	1059528	2025-10-08 08:45:41.000	2025-11-24 10:17:41.000
904	68e625695cfb0	YHDOFYTUV	dni	0	914417	784178	1059500	2025-10-08 08:48:41.000	2025-11-24 10:17:41.000
905	68e65bba5187c	RIDWIIVHI	dni	0	914455	784233	1059820	2025-10-08 12:40:26.000	2025-11-24 10:17:41.000
906	68e66259910cc	ENZDRITXX	dni	0	914459	784241	1059723	2025-10-08 13:08:41.000	2025-11-24 10:17:41.000
907	68e6770045570	XDKNSPTQJ	dni	0	914481	784266	1059970	2025-10-08 14:36:48.000	2025-11-24 10:17:41.000
908	68e6917772b41	PVNPEDKFJ	dni	0	914498	784287	1060063	2025-10-08 16:29:43.000	2025-11-24 10:17:41.000
909	68e69557288b7	MIVMXFFBU	dni	0	914498	784293	1060090	2025-10-08 16:46:15.000	2025-11-24 10:17:42.000
910	68e69c0deb2d6	MIXAFSWKE	dni	1	914203	784296	1058248	2025-10-08 17:14:53.000	2025-11-24 10:17:42.000	2025-10-12 01:45:34.000
911	68e6c534cd275	PSYEHKANG	dni	0	914448	784328	1059753	2025-10-08 20:10:28.000	2025-11-24 10:17:42.000
912	68e6d445bd6fa	FOWLFPAES	dni	0	914546	784342	1060362	2025-10-08 21:14:45.000	2025-11-24 10:17:42.000
913	68e783ea148f7	JDCTYVPBX	dni	1	913884	784391	1060661	2025-10-09 09:44:10.000	2025-11-24 10:17:42.000	2025-10-09 11:46:43.000
914	68e786fac7de6	PCDXLUKQB	dni	0	233902	784396	1060673	2025-10-09 09:57:14.000	2025-11-24 10:17:42.000
915	68e813ab2fe87	YCSZGFFYC	dni	0	914684	784509	1061230	2025-10-09 19:57:31.000	2025-11-24 10:17:42.000
916	68e84d71d47d3	PPQHSDSGU	dni	0	914718	784549	1061469	2025-10-10 00:04:01.000	2025-11-24 10:17:42.000
917	68e8679acc305	SNPSFFCSR	dni	0	913592	784550	1056483	2025-10-10 01:55:38.000	2025-11-24 10:17:42.000
918	68e8935d3c398	JXYOPPBWB	dni	0	914537	784552	1060321	2025-10-10 05:02:21.000	2025-11-24 10:17:42.000
919	68e8c2b767689	DMVUMOTQV	dni	1	914731	784569	1061566	2025-10-10 08:24:23.000	2025-11-24 10:17:42.000	2025-10-10 10:31:54.000
920	68e8d404ec246	QFVZJCTIG	dni	1	914739	784579	1061648	2025-10-10 09:38:12.000	2025-11-24 10:17:43.000	2025-10-10 11:40:48.000
921	68e8d8038f5d4	CUEERGJYR	dni	1	914719	784583	1061487	2025-10-10 09:55:15.000	2025-11-24 10:17:43.000	2025-10-14 06:20:31.000
922	68e8dad9ca4d4	NRWZKTTBA	dni	0	914742	784586	1055320	2025-10-10 10:07:21.000	2025-11-24 10:17:43.000
923	68e905ebcecb4	BRGQBMUZQ	rifle	0	412516	784607	1056979	2025-10-10 13:11:07.000	2025-11-24 10:17:43.000	2025-10-10 21:30:40.000
924	68e90e51ab2e0	YLNBLBVVA	rifle	0	215871	784616	1061884	2025-10-10 13:46:57.000	2025-11-24 10:17:43.000	2025-10-10 15:55:44.000
925	68e936e831e3c	COIQZPIIX	dni	0	883911	784637	1061911	2025-10-10 16:40:08.000	2025-11-24 10:17:43.000
926	68e9400674aee	OVYOICGAC	dni	1	914800	784644	1062045	2025-10-10 17:19:02.000	2025-11-24 10:17:43.000	2025-10-14 10:58:16.000
927	68e95a07c252a	RIXXDPSUU	dni	1	914809	784665	1062099	2025-10-10 19:09:59.000	2025-11-24 10:17:43.000	2025-10-10 21:11:46.000
928	68e9efcf84c82	RMMCEWABO	dni	0	878841	784698	1062351	2025-10-11 05:49:03.000	2025-11-24 10:17:43.000
929	68e9f1bd46cf9	UQUKKXRSB	dni	1	914848	784699	1062354	2025-10-11 05:57:17.000	2025-11-24 10:17:43.000	2025-10-11 11:50:46.000
930	68ea7e4e6e4ed	EAHSFJASR	dni	0	914890	784756	1062650	2025-10-11 15:57:02.000	2025-11-24 10:17:43.000
931	68eaa99c15084	ORAWPZRQN	dni	1	381321	784780	1062850	2025-10-11 19:01:48.000	2025-11-24 10:17:43.000	2025-10-14 08:11:54.000
932	68ebc261bb12b	EXAAUGUNN	dni	0	914985	784868	1063377	2025-10-12 14:59:45.000	2025-11-24 10:17:43.000
933	68ebcb679d282	PADXOCTFK	dni	0	914995	784876	1063332	2025-10-12 15:38:15.000	2025-11-24 10:17:43.000
934	68ebef78f1033	SWIYEGLIY	dni	1	915013	784897	1063518	2025-10-12 18:12:08.000	2025-11-24 10:17:43.000	2025-10-12 20:16:39.000
935	68ec2b2a90a56	DLANOEUFK	dni	1	915044	784930	1063724	2025-10-12 22:26:50.000	2025-11-24 10:17:43.000	2025-10-20 22:50:13.000
936	68ec2f542707e	FRTROFHNZ	dni	0	914844	784931	1063786	2025-10-12 22:44:36.000	2025-11-24 10:17:43.000
937	68ed20511c1cd	CWCYNWAKA	dni	0	906214	785051	1064439	2025-10-13 15:52:49.000	2025-11-24 10:17:44.000
938	68ed2d34e978f	JIGMWQSFC	dni	0	912592	785064	1058244	2025-10-13 16:47:48.000	2025-11-24 10:17:44.000
939	68ed30df7347a	WEWRHFFSD	dni	1	915151	785068	1063504	2025-10-13 17:03:27.000	2025-11-24 10:17:44.000	2025-10-14 07:28:12.000
940	68ed4d2dadb17	RUOUPBZXR	dni	0	305710	785097	1062197	2025-10-13 19:04:13.000	2025-11-24 10:17:44.000
941	68ee08cfcbb7b	UBBQJITRF	dni	1	915246	785152	1064598	2025-10-14 08:24:47.000	2025-11-24 10:17:44.000	2025-10-14 14:47:30.000
942	68ee3f0771f5d	TOPWQKFWO	dni	1	914339	785217	1065340	2025-10-14 12:16:07.000	2025-11-24 10:17:44.000	2025-10-14 14:19:53.000
943	68ee5bac6b6be	UAAURMJGL	dni	0	914947	785248	1065400	2025-10-14 14:18:20.000	2025-11-24 10:17:44.000
944	68ee659dd8784	RZLCJHMHT	dni	0	915313	785258	1061166	2025-10-14 15:00:45.000	2025-11-24 10:17:44.000
945	68ee6a9ec61d0	ASZKXVITB	dni	0	330765	785261	1065581	2025-10-14 15:22:06.000	2025-11-24 10:17:44.000
946	68ee86c1c54ea	XORQVMDMQ	dni	1	912880	785292	1064330	2025-10-14 17:22:09.000	2025-11-24 10:17:44.000	2025-10-14 19:27:05.000
947	68ee9d96c16d1	BFNQAMWFQ	dni	0	915361	785322	1059378	2025-10-14 18:59:34.000	2025-11-24 10:17:44.000
948	68eea36075996	ZTLTJHPIF	dni	0	915369	785327	1065929	2025-10-14 19:24:16.000	2025-11-24 10:17:44.000
949	68eeb67e3d216	TVRLZOPSU	dni	1	807648	785345	1064205	2025-10-14 20:45:50.000	2025-11-24 10:17:44.000	2025-10-15 16:37:33.000
950	68eecd11c40b8	BIEMUDREV	dni	0	915401	785368	1066105	2025-10-14 22:22:09.000	2025-11-24 10:17:44.000
951	68efa35aa744d	YPZKBOLLG	dni	0	915483	785456	1066590	2025-10-15 13:36:26.000	2025-11-24 10:17:44.000
952	68efaf2873184	XPHFUMOQJ	escopeta	0	915484	785463	1066594	2025-10-15 14:26:48.000	2025-11-24 10:17:44.000
953	68efbafad7849	JSUORTOGU	escopeta	0	842290	785386	1066174	2025-10-15 15:17:14.000	2025-11-24 10:17:45.000
954	68efbcd4eb4e7	OEZQDRUSU	escopeta	0	842290	785478	1066699	2025-10-15 15:25:08.000	2025-11-24 10:17:45.000
955	68efdef818600	FCRDFITBG	escopeta	0	344420	785510	1066872	2025-10-15 17:50:48.000	2025-11-24 10:17:45.000
956	68effa7a542f9	HJGCSTXGN	dni	0	226271	785537	1062904	2025-10-15 19:48:10.000	2025-11-24 10:17:45.000
957	68f0287399640	LJCMMVYTC	dni	1	915560	785572	1066072	2025-10-15 23:04:19.000	2025-11-24 10:17:45.000	2025-10-16 01:10:31.000
958	68f09d1439822	WFTCZFWQR	dni	0	913477	785584	1067255	2025-10-16 07:21:56.000	2025-11-24 10:17:45.000	2025-10-16 10:10:22.000
959	68f0ab1ca7b8d	UGTATEUPU	dni	0	915578	785597	1067294	2025-10-16 08:21:48.000	2025-11-24 10:17:45.000
960	68f0b055c5d19	ZSQXREXCX	dni	0	915401	785600	1066229	2025-10-16 08:44:05.000	2025-11-24 10:17:45.000
961	68f0cf7648d08	PELTWRVGT	dni	0	814212	785621	1064046	2025-10-16 10:56:54.000	2025-11-24 10:17:45.000
962	68f0d04b6cba1	TLMXPSHFW	dni	0	223170	785623	1067457	2025-10-16 11:00:27.000	2025-11-24 10:17:45.000
963	68f0e1dc72f35	BSCTMCQZJ	dni	1	915610	785635	1067531	2025-10-16 12:15:24.000	2025-11-24 10:17:45.000	2025-10-16 14:35:30.000
964	68f0f281740c4	HNWYFXELR	dni	0	915618	785649	1067599	2025-10-16 13:26:25.000	2025-11-24 10:17:45.000
965	68f1066c98fd6	CSAPBJKFG	dni	0	914970	785669	1062556	2025-10-16 14:51:24.000	2025-11-24 10:17:45.000
966	68f14ee82dc68	GMUAKAILV	dni	0	894389	785725	1067984	2025-10-16 20:00:40.000	2025-11-24 10:17:46.000
967	68f153daed399	PIYMUEYBG	dni	1	915323	785728	1065449	2025-10-16 20:21:46.000	2025-11-24 10:17:46.000	2025-10-17 13:22:34.000
968	68f157de7eab0	YXGOJBZKL	dni	0	257304	785729	1066112	2025-10-16 20:38:54.000	2025-11-24 10:17:46.000
969	68f167504003e	TMULZNGFO	dni	0	915706	785743	1068103	2025-10-16 21:44:48.000	2025-11-24 10:17:46.000
970	68f231cda60fa	SXXTDRPEY	dni	0	915772	785816	1068475	2025-10-17 12:08:45.000	2025-11-24 10:17:46.000
971	68f2b5acc7b30	WFXJXPKLD	dni	0	915832	785889	1068701	2025-10-17 21:31:24.000	2025-11-24 10:17:46.000
972	68f32c70be7f6	APDVCDSRM	dni	0	915846	785903	1069005	2025-10-18 05:58:08.000	2025-11-24 10:17:46.000
973	68f36369246f4	IHLFLUQOX	dni	1	255766	785922	1069113	2025-10-18 09:52:41.000	2025-11-24 10:17:46.000	2025-10-19 21:54:16.000
974	68f3af9882d48	POTTXOQGA	dni	0	915889	785954	1069294	2025-10-18 15:17:44.000	2025-11-24 10:17:46.000
975	68f4267c91d8c	PCSHHPGFR	dni	0	915944	786013	1069581	2025-10-18 23:45:00.000	2025-11-24 10:17:46.000
976	68f4c0eb90aee	XGVRXWFVN	dni	0	915966	786043	1069745	2025-10-19 10:43:55.000	2025-11-24 10:17:46.000
977	68f4c99636aef	LQNKWAITS	escopeta	0	915978	786053	1069213	2025-10-19 11:20:54.000	2025-11-24 10:17:46.000
978	68f50612040a9	THKVJUOCB	escopeta	1	916008	786096	1070048	2025-10-19 15:38:58.000	2025-11-24 10:17:46.000	2025-10-19 17:47:42.000
979	68f5159663a2a	EGQLPZXOQ	dni	0	916029	786110	1070096	2025-10-19 16:45:10.000	2025-11-24 10:17:46.000
980	68f53ddfdcbb3	FWAENKAEH	dni	1	916059	786154	1070357	2025-10-19 19:37:03.000	2025-11-24 10:17:47.000	2025-10-19 21:40:35.000
981	68f5e213f00fb	GSFJIATKT	dni	0	916104	786208	1070660	2025-10-20 07:17:39.000	2025-11-24 10:17:47.000
982	68f5e45671c15	LOPABJHRK	dni	1	916105	786209	1069852	2025-10-20 07:27:18.000	2025-11-24 10:17:47.000	2025-10-20 19:01:43.000
983	68f615cfcdc78	XLRBSZVAX	dni	0	915352	786252	1065835	2025-10-20 10:58:23.000	2025-11-24 10:17:47.000
984	68f64d63aa714	LHKXGWDGU	dni	0	916192	786314	1068349	2025-10-20 14:55:31.000	2025-11-24 10:17:47.000
985	68f66add6ed28	APIBAOVBT	dni	0	916216	786335	1071258	2025-10-20 17:01:17.000	2025-11-24 10:17:47.000
986	68f6746da533a	TLOGXIVAU	dni	0	916227	786345	1071344	2025-10-20 17:42:05.000	2025-11-24 10:17:47.000
987	68f6a92025504	PEIOENGZO	dni	0	147968	786404	1071640	2025-10-20 21:26:56.000	2025-11-24 10:17:47.000
988	68f745383dccf	OOPSCICEE	dni	0	916322	786447	1068694	2025-10-21 08:32:56.000	2025-11-24 10:17:47.000
989	68f74c2fc04e5	KZEWZEVOW	dni	1	916325	786450	1071911	2025-10-21 09:02:39.000	2025-11-24 10:17:47.000	2025-10-21 11:11:53.000
990	68f76f7e4e2ec	MMDMEEYQZ	dni	1	226193	786480	1071919	2025-10-21 11:33:18.000	2025-11-24 10:17:47.000	2025-10-21 13:54:50.000
991	68f782a16f2a4	ZXZAFVFTP	dni	0	896324	786490	1072229	2025-10-21 12:54:57.000	2025-11-24 10:17:47.000
992	68f79f144bf7d	SISUAZEXX	dni	0	916212	786518	1072319	2025-10-21 14:56:20.000	2025-11-24 10:17:47.000
993	68f7c7062dd15	WVEVJIOBK	dni	1	916416	786576	1072589	2025-10-21 17:46:46.000	2025-11-24 10:17:47.000	2025-10-21 19:49:18.000
994	68f7c81672b4c	EJHMGXQWU	dni	1	897103	786577	1072606	2025-10-21 17:51:18.000	2025-11-24 10:17:47.000	2025-10-21 20:06:25.000
995	68f7cfd3551e2	MCMALFXYP	dni	1	916425	786585	1072646	2025-10-21 18:24:19.000	2025-11-24 10:17:47.000	2025-10-21 20:26:09.000
996	68f7e092b8267	ABDZWCTNY	dni	0	916435	786604	1072716	2025-10-21 19:35:46.000	2025-11-24 10:17:47.000
997	68f7f3774246f	ESJUYFJKX	rifle	0	422915	786639	1072822	2025-10-21 20:56:23.000	2025-11-24 10:17:47.000
998	68f7fa71d653c	GZWCZJVMU	dni	0	916469	786648	1072913	2025-10-21 21:26:09.000	2025-11-24 10:17:47.000
999	68f7ffe0705f2	EAWJQTNKY	dni	0	916473	786654	1072936	2025-10-21 21:49:20.000	2025-11-24 10:17:48.000
1000	68f8a84a303fb	QAEDTLVTW	dni	1	916515	786706	1073235	2025-10-22 09:47:54.000	2025-11-24 10:17:48.000	2025-10-22 11:51:48.000
1001	68f8b1f0351b3	UVWXEHLLD	dni	1	913477	786714	1068269	2025-10-22 10:29:04.000	2025-11-24 10:17:48.000	2025-10-22 12:30:10.000
1002	68f8d35b3c49c	BVYTULXNC	corta	0	914218	786752	1058364	2025-10-22 12:51:39.000	2025-11-24 10:17:48.000
1003	68f8d8f30189e	ITEPUMPOY	corta	0	423603	786754	1073435	2025-10-22 13:15:31.000	2025-11-24 10:17:48.000
1004	68f8f0d732d33	RDJVXWRZQ	dni	0	245616	786780	1073622	2025-10-22 14:57:27.000	2025-11-24 10:17:48.000
1005	68f8f2e82b2e4	FIWHKQHGG	dni	0	916573	786785	1073632	2025-10-22 15:06:16.000	2025-11-24 10:17:48.000
1006	68f8f57c307d8	QJDRKOPIZ	dni	1	916574	786790	1073160	2025-10-22 15:17:16.000	2025-11-24 10:17:48.000	2025-10-27 18:41:24.000
1007	68f90a7c81768	XLLBABJEC	dni	1	916350	786817	1072124	2025-10-22 16:46:52.000	2025-11-24 10:17:48.000	2025-10-28 19:56:52.000
1008	68f91c8a9aa30	MYDLIAKCR	dni	0	916602	786840	1073844	2025-10-22 18:03:54.000	2025-11-24 10:17:48.000
1009	68f942c664185	XZQYJRITK	dni	0	916637	786880	1073908	2025-10-22 20:47:02.000	2025-11-24 10:17:48.000
1010	68f9472a3487b	ITWFABWSG	dni	0	916637	786885	1074027	2025-10-22 21:05:46.000	2025-11-24 10:17:48.000
1011	68f95535b3edf	IMKTNXNNT	dni	1	916659	786899	1074127	2025-10-22 22:05:41.000	2025-11-24 10:17:48.000	2025-10-23 00:06:50.000
1012	68f9d0b3a4ab1	XLPTIILPS	dni	0	147609	786907	1074241	2025-10-23 06:52:35.000	2025-11-24 10:17:48.000
1013	68f9da0816d02	PQRWOVJSL	dni	0	915610	786909	1074274	2025-10-23 07:32:24.000	2025-11-24 10:17:48.000
1014	68f9f948a03be	YMMKESUKC	dni	1	916685	786933	1074372	2025-10-23 09:45:44.000	2025-11-24 10:17:49.000	2025-10-23 11:47:48.000
1015	68fa0972bb18a	WEFPWZQOA	dni	0	916699	786945	1071902	2025-10-23 10:54:42.000	2025-11-24 10:17:49.000
1016	68fa203ac3530	EBJBRXVAC	dni	0	858831	786964	1073128	2025-10-23 12:31:54.000	2025-11-24 10:17:49.000
1017	68fa43aa074e3	RNFPEHZHF	escopeta	0	916742	786999	1074722	2025-10-23 15:03:06.000	2025-11-24 10:17:49.000
1018	68fa4dd5b5a2a	MHLREXYUK	dni	0	916746	787012	1072219	2025-10-23 15:46:29.000	2025-11-24 10:17:49.000
1019	68fa5837e9d6f	HOROWBBUY	dni	0	916756	787019	1071301	2025-10-23 16:30:47.000	2025-11-24 10:17:49.000
1020	68fb3c01c9dbf	HXKNCGNYL	dni	1	915935	787117	1069540	2025-10-24 08:42:41.000	2025-11-24 10:17:49.000	2025-10-24 17:26:17.000
1021	68fb7e225564e	CNFXYREWT	dni	0	916639	787171	1074035	2025-10-24 13:24:50.000	2025-11-24 10:17:49.000
1022	68fb9142492ce	XKRCCHZTV	dni	0	195090	787178	1075704	2025-10-24 14:46:26.000	2025-11-24 10:17:49.000
1023	68fbe59a6d8cd	YDKRFRZUM	dni	0	916922	787228	1075147	2025-10-24 20:46:18.000	2025-11-24 10:17:49.000
1024	68fc9cc384c0e	EUCRELVQB	dni	1	916948	787260	1076247	2025-10-25 09:47:47.000	2025-11-24 10:17:49.000	2025-10-25 11:53:45.000
1025	68fcb716197ec	PJGRJQSTT	escopeta	1	859488	787278	1076334	2025-10-25 11:40:06.000	2025-11-24 10:17:49.000	2025-10-25 13:42:02.000
1026	68fce7dec91db	DAOYFDGFZ	dni	1	916975	787299	1076435	2025-10-25 15:08:14.000	2025-11-24 10:17:49.000	2025-10-28 15:56:28.000
1027	68fcecacbb8cf	IWIBZBUVT	dni	0	812322	787302	1075800	2025-10-25 15:28:44.000	2025-11-24 10:17:49.000
1028	68fcf34e4907e	CXNEIQKLW	dni	0	916980	787308	1076501	2025-10-25 15:57:02.000	2025-11-24 10:17:49.000
1029	68fcf3be5a3ef	ULXIIAFHG	dni	0	916981	787309	1076486	2025-10-25 15:58:54.000	2025-11-24 10:17:49.000
1030	68fd09ef218eb	PATCWOABQ	dni	0	914738	787318	1076575	2025-10-25 17:33:35.000	2025-11-24 10:17:49.000
1031	68fd1b08f245b	AEPEZQACJ	dni	0	916997	787326	1076631	2025-10-25 18:46:32.000	2025-11-24 10:17:50.000
1032	68fe1728d0299	SDELSYUPR	dni	0	917042	787408	1077116	2025-10-26 12:42:16.000	2025-11-24 10:17:50.000
1033	68fe6b899e6e3	ZIMEYLCFH	dni	0	917107	787463	1077093	2025-10-26 18:42:17.000	2025-11-24 10:17:50.000
1034	68fe77c2c4693	IRMJRHMZY	escopeta	0	891152	787473	1063544	2025-10-26 19:34:26.000	2025-11-24 10:17:50.000
1035	68fe858d088f7	ZJCYRKUGI	dni	1	917124	787484	1077543	2025-10-26 20:33:17.000	2025-11-24 10:17:50.000	2025-10-26 21:41:12.000
1036	68ff46fa43571	OMGTWUQYZ	dni	1	917024	787559	1076811	2025-10-27 10:18:34.000	2025-11-24 10:17:50.000	2025-10-27 12:26:16.000
1037	68ff495c961bd	JTGWUJUSK	dni	0	917126	787563	1077548	2025-10-27 10:28:44.000	2025-11-24 10:17:50.000	2025-10-27 11:49:26.000
1038	68ff69f32cdf3	SSAAJWWZZ	corta	0	917058	787588	1077080	2025-10-27 12:47:47.000	2025-11-24 10:17:50.000
1039	68ff8af83e26d	DVTJTJUEJ	dni	0	917241	787616	1078201	2025-10-27 15:08:40.000	2025-11-24 10:17:50.000
1040	68ffa53416eeb	QRXRXVINH	dni	0	917261	787640	1078350	2025-10-27 17:00:36.000	2025-11-24 10:17:50.000
1041	68ffb32ae4f8f	IROMCUYGV	dni	1	917268	787657	1078404	2025-10-27 18:00:10.000	2025-11-24 10:17:50.000	2025-10-28 12:41:34.000
1042	68ffcac011a0c	EFBELCHYX	dni	1	917294	787690	1078549	2025-10-27 19:40:48.000	2025-11-24 10:17:50.000	2025-10-28 06:56:52.000
1043	68ffd0c552f7a	UNWPYAHPP	escopeta	1	917298	787696	1078589	2025-10-27 20:06:29.000	2025-11-24 10:17:50.000	2025-10-27 21:11:14.000
1044	68ffd7358f840	MKYIKFFOA	escopeta	1	300371	787707	1078612	2025-10-27 20:33:57.000	2025-11-24 10:17:51.000	2025-10-29 20:43:20.000
1045	68ffd8ecc570c	ZERYLVXJG	dni	1	917308	787710	1078641	2025-10-27 20:41:16.000	2025-11-24 10:17:51.000	2025-10-27 21:44:25.000
1046	68ffe0af4a638	ZUDEXEFOC	dni	1	395482	787721	1078698	2025-10-27 21:14:23.000	2025-11-24 10:17:51.000	2025-10-27 22:18:10.000
1047	68fff1c01f2ee	AFGTJPMLE	dni	0	917326	787736	1078775	2025-10-27 22:27:12.000	2025-11-24 10:17:51.000
1048	68fff45e40c9c	WFVYVIJLP	dni	0	917322	787739	1078632	2025-10-27 22:38:22.000	2025-11-24 10:17:51.000
1049	68fffbdc5bc33	QFLZHJHTU	dni	0	917330	787744	1078808	2025-10-27 23:10:20.000	2025-11-24 10:17:51.000
1050	69006fb2660b7	KCNUTRTCX	dni	0	917254	787760	1078304	2025-10-28 07:24:34.000	2025-11-24 10:17:51.000
1051	69009fdcddd41	KOMJFWVFK	dni	0	917363	787789	1078995	2025-10-28 10:50:04.000	2025-11-24 10:17:51.000
1052	6900c5f3673fd	JNWACRKHL	dni	1	917397	787826	1079241	2025-10-28 13:32:35.000	2025-11-24 10:17:51.000	2025-10-28 17:12:02.000
1053	6900dc138d62a	CHWXNYILT	dni	0	172446	787853	1079300	2025-10-28 15:06:59.000	2025-11-24 10:17:51.000
1054	6900dfb35f1cc	SYTHYYBJO	dni	0	914890	787854	1079295	2025-10-28 15:22:27.000	2025-11-24 10:17:51.000
1055	6900f4b984ba8	AKYJOAQZO	dni	0	814892	787881	1053128	2025-10-28 16:52:09.000	2025-11-24 10:17:51.000	2025-10-28 18:01:04.000
1056	6900f58926c86	BCTNJCDHQ	dni	0	814892	787883	1079490	2025-10-28 16:55:37.000	2025-11-24 10:17:51.000
1057	690112a5a331f	JRGEAZPEI	dni	1	917458	787901	1079630	2025-10-28 18:59:49.000	2025-11-24 10:17:51.000	2025-10-28 20:04:25.000
1058	69012a1443fa1	ZQUNCYLGK	dni	1	917474	787924	1079747	2025-10-28 20:39:48.000	2025-11-24 10:17:51.000	2025-10-29 08:24:58.000
1059	69013055d80e6	PYLXSTCPW	dni	1	917469	787932	1079723	2025-10-28 21:06:29.000	2025-11-24 10:17:52.000	2025-10-28 22:09:20.000
1060	6901317867140	YIIRRMYVJ	dni	0	917481	787935	1078868	2025-10-28 21:11:20.000	2025-11-24 10:17:52.000
1061	6901347a10ec8	YYQXORMWB	dni	1	917485	787939	1079810	2025-10-28 21:24:10.000	2025-11-24 10:17:52.000	2025-10-28 22:27:09.000
1062	690141c96602c	TAMOCJJWO	dni	0	917499	787958	1074809	2025-10-28 22:20:57.000	2025-11-24 10:17:52.000
1063	6901d63582482	ZINWXLVYA	dni	1	917533	787989	1080070	2025-10-29 08:54:13.000	2025-11-24 10:17:52.000	2025-10-29 09:57:22.000
1064	6901f728ca583	ABYZOCWFA	dni	0	386013	788012	1080149	2025-10-29 11:14:48.000	2025-11-24 10:17:52.000
1065	69020058e6c2e	DBHUCAPVR	dni	0	319249	788026	1080208	2025-10-29 11:54:00.000	2025-11-24 10:17:52.000
1066	690203135ac1c	HAVZVLCFQ	dni	0	814212	788034	1072290	2025-10-29 12:05:39.000	2025-11-24 10:17:52.000
1067	690206b2d4ac7	XUGUTMSNW	dni	1	917566	788038	1080296	2025-10-29 12:21:06.000	2025-11-24 10:17:52.000	2025-10-29 16:13:37.000
1068	69020db318d39	PLZVNNUBW	escopeta	1	917575	788051	1080327	2025-10-29 12:50:59.000	2025-11-24 10:17:52.000	2025-10-29 13:53:00.000
1069	690215436bf36	XEUVIOYAE	dni	0	917581	788058	1076943	2025-10-29 13:23:15.000	2025-11-24 10:17:52.000
1070	69021551e0d63	FLXNISFHM	dni	1	917580	788059	1080363	2025-10-29 13:23:29.000	2025-11-24 10:17:52.000	2025-10-29 14:30:15.000
1071	690251dc0827a	MGIKTWKYI	dni	1	913308	788110	1080657	2025-10-29 17:41:48.000	2025-11-24 10:17:52.000	2025-10-29 18:48:02.000
1072	690258b192048	RISREURBZ	escopeta	1	911920	788118	1080632	2025-10-29 18:10:57.000	2025-11-24 10:17:52.000	2025-10-29 23:57:14.000
1073	69026be558076	OFFCTEAAI	dni	0	917512	788135	1080772	2025-10-29 19:32:53.000	2025-11-24 10:17:52.000
1074	6902775132383	BTJWFYTWL	escopeta	0	908829	788148	1073161	2025-10-29 20:21:37.000	2025-11-24 10:17:52.000
1075	69028d05df879	WILOHJKSX	dni	0	917665	788169	1080943	2025-10-29 21:54:13.000	2025-11-24 10:17:52.000
1076	6902996ed1164	MMKKBRRXY	dni	1	917673	788179	1080983	2025-10-29 22:47:10.000	2025-11-24 10:17:53.000	2025-10-30 13:36:31.000
1077	6903398756c71	KLMLUIAOL	dni	1	887597	788227	1081232	2025-10-30 10:10:15.000	2025-11-24 10:17:53.000	2025-10-30 11:16:21.000
1078	69034b796c8da	QHQFXXUGY	dni	0	915966	788240	1081315	2025-10-30 11:26:49.000	2025-11-24 10:17:53.000
1079	690361fec703d	YMXHQECES	dni	0	815490	788262	1080515	2025-10-30 13:02:54.000	2025-11-24 10:17:53.000
1080	69036c91378ed	VHCTZYSGK	dni	1	917739	788269	1081430	2025-10-30 13:48:01.000	2025-11-24 10:17:53.000	2025-10-30 15:17:29.000
1081	690380436f510	VHILGOYWP	dni	0	917694	788292	1081161	2025-10-30 15:12:03.000	2025-11-24 10:17:53.000
1082	6903d2955cd1f	KYXIIUZLV	dni	0	387250	788363	1081837	2025-10-30 21:03:17.000	2025-11-24 10:17:53.000
1083	6903e34a643d5	GTOSEITCK	dni	0	866023	788373	1081920	2025-10-30 22:14:34.000	2025-11-24 10:17:53.000
1084	69043f07a2a56	EJYZBSWUQ	dni	0	917827	788382	1082011	2025-10-31 04:45:59.000	2025-11-24 10:17:53.000
1085	69045ffd7fe1b	SWAWMJOZT	dni	0	915151	788386	1082034	2025-10-31 07:06:37.000	2025-11-24 10:17:53.000
1086	690477c1dd683	UYDOXXGTR	dni	0	916388	788396	1082087	2025-10-31 08:48:01.000	2025-11-24 10:17:53.000
1087	6904a9baefd7b	ENCINSSTM	dni	0	917866	788438	1082146	2025-10-31 12:21:14.000	2025-11-24 10:17:53.000
1088	6904d252783d6	UCRFHJSNJ	dni	0	917451	788465	1082416	2025-10-31 15:14:26.000	2025-11-24 10:17:53.000
1089	6904e1de2f891	EKPXJYQWK	dni	0	869738	788477	1082469	2025-10-31 16:20:46.000	2025-11-24 10:17:53.000
1090	6904e36ba3217	TJOFILITP	dni	1	917898	788478	1082446	2025-10-31 16:27:23.000	2025-11-24 10:17:53.000	2025-10-31 17:35:31.000
1091	6904e3c2248ba	FPIYCLURM	dni	0	855823	788479	1076478	2025-10-31 16:28:50.000	2025-11-24 10:17:53.000
1092	6904f90bc19c1	TDGUWQARV	dni	0	917908	788490	1082527	2025-10-31 17:59:39.000	2025-11-24 10:17:53.000
1093	69050a46b709f	HSAGUPXWU	dni	1	917915	788496	1082597	2025-10-31 19:13:10.000	2025-11-24 10:17:53.000	2025-10-31 21:25:12.000
1094	690511e231c5d	YPTDQVOIN	dni	0	320477	788507	1080956	2025-10-31 19:45:38.000	2025-11-24 10:17:54.000
1095	690536400fa99	GWKYXJNUO	dni	1	917124	788525	1082729	2025-10-31 22:20:48.000	2025-11-24 10:17:54.000	2025-10-31 23:23:41.000
1096	690540564383a	YHWTLRHEM	dni	0	276286	788534	1082682	2025-10-31 23:03:50.000	2025-11-24 10:17:54.000
1097	6905dc802d2c1	EOTNGWSNK	dni	0	917959	788561	1082915	2025-11-01 10:10:08.000	2025-11-24 10:17:54.000	2025-11-02 17:35:48.000
1098	6906058cd2848	LTELWVFHO	dni	0	917975	788582	1083025	2025-11-01 13:05:16.000	2025-11-24 10:17:54.000
1099	6906269413285	EOUPBGGAJ	dni	0	917994	788608	1083114	2025-11-01 15:26:12.000	2025-11-24 10:17:54.000
1100	6906298e0be94	EMTIBMYQB	dni	0	885083	788610	1081800	2025-11-01 15:38:54.000	2025-11-24 10:17:54.000
1101	69065573f2c56	ODEGRSKQP	dni	1	905158	788636	1083285	2025-11-01 18:46:11.000	2025-11-24 10:17:54.000	2025-11-01 19:55:23.000
1102	69066574b1d14	FXTHEAQTI	dni	1	247461	788644	1083336	2025-11-01 19:54:28.000	2025-11-24 10:17:54.000	2025-11-01 21:06:42.000
1103	690671661ebcc	CUZHRHJSL	dni	1	914530	788652	1083368	2025-11-01 20:45:26.000	2025-11-24 10:17:54.000	2025-11-01 21:48:13.000
1104	6907331eb5c54	LZHMHMUYG	dni	0	280438	788697	1083658	2025-11-02 10:31:58.000	2025-11-24 10:17:54.000
1105	6907615e950cc	WBAKMADFQ	dni	0	918092	788735	1083810	2025-11-02 13:49:18.000	2025-11-24 10:17:54.000
1106	690775bf5656d	RNDAVHVEV	dni	0	868057	788751	1083861	2025-11-02 15:16:15.000	2025-11-24 10:17:54.000
1107	6907da6e03824	ALXKYVHKP	dni	0	203166	788834	1084053	2025-11-02 22:25:50.000	2025-11-24 10:17:54.000
1108	6907e628d088d	WLQSISQDC	dni	0	918180	788843	1082437	2025-11-02 23:15:52.000	2025-11-24 10:17:54.000
1109	6907e84c78065	QZHWITRBR	dni	0	918180	788846	1084439	2025-11-02 23:25:00.000	2025-11-24 10:17:54.000
1110	69086718601e4	LZFKLKBJB	dni	0	918201	788869	1084545	2025-11-03 08:26:00.000	2025-11-24 10:17:54.000
1111	69086e6e43041	RYETFYIJH	dni	1	918210	788875	1084587	2025-11-03 08:57:18.000	2025-11-24 10:17:54.000	2025-11-03 10:34:32.000
1112	690883dfa448b	FLUSLLDHV	escopeta	0	917375	788895	1076139	2025-11-03 10:28:47.000	2025-11-24 10:17:55.000
1113	69088d935c688	XXTYQHTBP	dni	0	88126	788907	1084565	2025-11-03 11:10:11.000	2025-11-24 10:17:55.000
1114	69088f963cb7c	SMFIHESWZ	dni	0	918236	788908	1084753	2025-11-03 11:18:46.000	2025-11-24 10:17:55.000
1115	6908d1af2872e	WXBKXUTRS	dni	1	918176	788992	1084426	2025-11-03 16:00:47.000	2025-11-24 10:17:55.000	2025-11-03 17:02:19.000
1116	6908db83c1dbe	GFPUGRHQS	dni	1	820448	789007	1085191	2025-11-03 16:42:43.000	2025-11-24 10:17:55.000	2025-11-04 16:42:55.000
1117	6908f119347ad	BFMCCUVRU	dni	0	918285	789033	1085092	2025-11-03 18:14:49.000	2025-11-24 10:17:55.000
1118	690927eec471a	WUOZETTAR	dni	1	912483	789117	1085594	2025-11-03 22:08:46.000	2025-11-24 10:17:55.000	2025-11-03 23:11:08.000
1119	6909ae690f273	QXALCQIJB	dni	0	340783	789140	1085796	2025-11-04 07:42:33.000	2025-11-24 10:17:55.000
1120	6909ef954d377	YFRYXMBVY	dni	0	918437	789219	1086065	2025-11-04 12:20:37.000	2025-11-24 10:17:55.000
1121	6909f83903b34	UWKZZQRCK	dni	1	918454	789233	1086113	2025-11-04 12:57:29.000	2025-11-24 10:17:55.000	2025-11-04 14:04:13.000
1122	690a063ea153b	CNDMJEGSN	dni	1	918285	789248	1085881	2025-11-04 13:57:18.000	2025-11-24 10:17:55.000	2025-11-04 16:30:27.000
1123	690a10c0d7c98	GSIWFMKLN	dni	1	918479	789259	1086227	2025-11-04 14:42:08.000	2025-11-24 10:17:55.000	2025-11-04 16:49:14.000
1124	690a31a9e9bd3	BDKGCLYXD	dni	0	918485	789293	1086267	2025-11-04 17:02:33.000	2025-11-24 10:17:55.000
1125	690a398bed36b	OWMIFIAYQ	dni	0	918505	789300	1086361	2025-11-04 17:36:11.000	2025-11-24 10:17:55.000
1126	690a447c90119	DAYJDXQZO	dni	1	918514	789313	1086466	2025-11-04 18:22:52.000	2025-11-24 10:17:55.000	2025-11-04 19:39:52.000
1127	690a4df6020b4	OVQWHZJUG	dni	1	918508	789324	1086532	2025-11-04 19:03:18.000	2025-11-24 10:17:55.000	2025-11-04 20:43:56.000
1128	690a53688db8e	KUUJHWOWE	dni	0	916965	789333	1086543	2025-11-04 19:26:32.000	2025-11-24 10:17:55.000
1129	690b11ef07a23	STJZSZCNU	dni	0	918587	789414	1086314	2025-11-05 08:59:27.000	2025-11-24 10:17:56.000
1130	690b1e3e37253	RSDRXZBAI	dni	0	918582	789422	1086940	2025-11-05 09:51:58.000	2025-11-24 10:17:56.000
1131	690b3680396d4	GZIWPUACU	dni	0	918612	789449	1087116	2025-11-05 11:35:28.000	2025-11-24 10:17:56.000
1132	690b56d637ebc	SVKDMNYYD	dni	0	241326	789477	1087255	2025-11-05 13:53:26.000	2025-11-24 10:17:56.000
1133	690b572ab62ca	IGTQYXRUO	dni	1	917124	789478	1087254	2025-11-05 13:54:50.000	2025-11-24 10:17:56.000	2025-11-05 14:56:32.000
1134	690b760db8781	AJPWLEKIJ	dni	1	918667	789501	1086542	2025-11-05 16:06:37.000	2025-11-24 10:17:56.000	2025-11-05 17:22:26.000
1135	690bcbde8967c	UEHGVGUTB	dni	0	918738	789636	1087952	2025-11-05 22:12:46.000	2025-11-24 10:17:56.000
1136	690c76d6e4a1e	SNQXYJZXC	dni	0	918758	789703	1088209	2025-11-06 10:22:14.000	2025-11-24 10:17:56.000
1137	690c8407b9120	UCHMNLLFX	escopeta	0	390903	789713	1088076	2025-11-06 11:18:31.000	2025-11-24 10:17:56.000	2025-11-06 20:45:37.000
1138	690cbf61bbc21	QCXZBGSTT	dni	0	88126	789757	1083154	2025-11-06 15:31:45.000	2025-11-24 10:17:56.000
1139	690cd1d364027	RBECTYIXG	dni	0	918813	789776	1088629	2025-11-06 16:50:27.000	2025-11-24 10:17:56.000
1140	690d080c94aa1	ZWLPJNTKK	dni	1	885188	789838	1087820	2025-11-06 20:41:48.000	2025-11-24 10:17:56.000	2025-11-06 21:46:30.000
1141	690d595eca31f	IAKMYJWMX	dni	0	918884	789868	1089081	2025-11-07 02:28:46.000	2025-11-24 10:17:56.000
1142	690de2069362a	SEDKJDJDH	dni	0	910493	789919	1089294	2025-11-07 12:11:50.000	2025-11-24 10:17:56.000
1143	690e37e23c10a	LKERTPXBC	dni	0	911229	789980	1085326	2025-11-07 18:18:10.000	2025-11-24 10:17:56.000
1144	690e6a6b05bcc	PRYXUHOHP	dni	0	918985	790020	1089904	2025-11-07 21:53:47.000	2025-11-24 10:17:56.000	2025-11-07 23:00:04.000
1145	690e7228c8b98	HTDNDUTAX	dni	1	917694	790025	1082512	2025-11-07 22:26:48.000	2025-11-24 10:17:56.000	2025-11-07 23:28:12.000
1146	690f1b1290f02	EDZATYGRC	dni	0	918760	790045	1090118	2025-11-08 10:27:30.000	2025-11-24 10:17:56.000
1147	690f40c7bcc4b	FMRCAHLZF	dni	0	919025	790056	1090220	2025-11-08 13:08:23.000	2025-11-24 10:17:56.000
1148	690f498ac633d	SGXETFHPJ	dni	0	919028	790061	1090230	2025-11-08 13:45:46.000	2025-11-24 10:17:56.000
1149	690f559da6120	EYVIGJTMM	dni	0	919034	790066	1090274	2025-11-08 14:37:17.000	2025-11-24 10:17:57.000
1150	690fd28f4553e	CERJLGWGG	dni	1	422917	790126	1081019	2025-11-08 23:30:23.000	2025-11-24 10:17:57.000	2025-11-09 00:45:31.000
1151	69106771bb267	OUHAMTDQS	dni	0	919117	790140	1090752	2025-11-09 10:05:37.000	2025-11-24 10:17:57.000
1152	69107675c142c	CHWGNBUUF	dni	0	360793	790146	1081516	2025-11-09 11:09:41.000	2025-11-24 10:17:57.000
1153	6910848ed1454	BZQIINNRH	escopeta	0	919124	790152	1090740	2025-11-09 12:09:50.000	2025-11-24 10:17:57.000
1154	6910a41d3b473	GPGSSWHFX	dni	0	919090	790166	1090605	2025-11-09 14:24:29.000	2025-11-24 10:17:57.000
1155	6910c03c7d98b	BPYRIJGNF	dni	0	387037	790184	1087234	2025-11-09 16:24:28.000	2025-11-24 10:17:57.000
1156	6910db96d4c7a	SQKTFJGYD	dni	0	850913	790207	1091103	2025-11-09 18:21:10.000	2025-11-24 10:17:57.000
1157	6910e45012dfe	MGJHKIXIX	dni	1	919182	790220	1091144	2025-11-09 18:58:24.000	2025-11-24 10:17:57.000	2025-11-09 20:04:57.000
1158	6910e7b915f8a	HHVCLYWFO	escopeta	1	919183	790223	1091158	2025-11-09 19:12:57.000	2025-11-24 10:17:57.000	2025-11-09 20:16:21.000
1159	6910f3fa202da	FTXULYHKV	dni	0	919197	790242	1091199	2025-11-09 20:05:14.000	2025-11-24 10:17:57.000
1160	69113122b3cb2	NULJTVPQP	dni	1	919232	790283	1091434	2025-11-10 00:26:10.000	2025-11-24 10:17:57.000
1161	69119b7b72927	NFGRGBHAW	dni	0	917261	790296	1091502	2025-11-10 07:59:55.000	2025-11-24 10:17:57.000
1162	6911ce1108d73	CPPCSCTJV	dni	0	919231	790344	1091430	2025-11-10 11:35:45.000	2025-11-24 10:17:57.000
1163	6911d75326da5	ZMXLOYZCL	corta	1	919280	790352	1091765	2025-11-10 12:15:15.000	2025-11-24 10:17:57.000	2025-11-10 13:17:43.000
1164	6911dd733f0b8	CNBBPDEKD	dni	1	919287	790357	1091485	2025-11-10 12:41:23.000	2025-11-24 10:17:57.000	2025-11-10 14:14:57.000
1165	69120ad32c684	HCEVJVHPV	dni	0	919321	790422	1092037	2025-11-10 15:54:59.000	2025-11-24 10:17:57.000
1166	69124b6f5f2e2	UTVVUSFUT	dni	1	919379	790503	1092424	2025-11-10 20:30:39.000	2025-11-24 10:17:57.000	2025-11-10 21:34:40.000
1167	6912642049c1b	KUIXVAEDF	dni	1	919404	790541	1092560	2025-11-10 22:16:00.000	2025-11-24 10:17:57.000	2025-11-10 23:50:23.000
1168	691268efcce81	LIQBVEEXD	dni	1	919400	790544	1092558	2025-11-10 22:36:31.000	2025-11-24 10:17:57.000	2025-11-10 23:42:43.000
1169	69126cf833e84	OESIBDFTP	dni	1	321125	790549	1083824	2025-11-10 22:53:44.000	2025-11-24 10:17:57.000	2025-11-11 07:17:20.000
1170	6912a3f456664	QLRHEFCVR	dni	1	919419	790559	1092656	2025-11-11 02:48:20.000	2025-11-24 10:17:58.000	2025-11-11 03:51:03.000
1171	6912fac107980	DZSWLLTHS	dni	0	902588	790582	1092747	2025-11-11 08:58:41.000	2025-11-24 10:17:58.000
1172	69130137de2b1	DQBLJFZUZ	dni	1	919171	790589	1088074	2025-11-11 09:26:15.000	2025-11-24 10:17:58.000	2025-11-11 10:32:09.000
1173	691320c78b88c	HSJFASFHJ	dni	1	249160	790629	1092926	2025-11-11 11:40:55.000	2025-11-24 10:17:58.000	2025-11-11 12:51:45.000
1174	6913267409726	ZTIUWSSXQ	dni	0	918666	790636	1087393	2025-11-11 12:05:08.000	2025-11-24 10:17:58.000
1175	691332379cdd8	MZLEFIODL	dni	1	919486	790650	1093110	2025-11-11 12:55:19.000	2025-11-24 10:17:58.000	2025-11-11 13:59:30.000
1176	69135001e3f6d	IOEBJGUDV	dni	1	919506	790688	1093307	2025-11-11 15:02:25.000	2025-11-24 10:17:58.000	2025-11-11 16:06:31.000
1177	69135a9a2a5e6	JIGQLMTVU	dni	0	919509	790698	1093337	2025-11-11 15:47:38.000	2025-11-24 10:17:58.000
1178	69136fba012ed	ITEROHJBO	dni	0	919528	790715	1093491	2025-11-11 17:17:46.000	2025-11-24 10:17:58.000
1179	69137f867b0d5	CSYOWNTHV	dni	0	919539	790735	1093592	2025-11-11 18:25:10.000	2025-11-24 10:17:58.000
1180	691383e95d69e	AMDODHVXF	dni	1	918666	790742	1093623	2025-11-11 18:43:53.000	2025-11-24 10:17:59.000	2025-11-12 00:18:48.000
1181	69138e770fab0	SXNODUEFS	dni	1	919176	790768	1090800	2025-11-11 19:28:55.000	2025-11-24 10:17:59.000	2025-11-11 22:52:10.000
1182	69138f1e7cb88	BEJAUMZHL	dni	0	914713	790770	1093587	2025-11-11 19:31:42.000	2025-11-24 10:17:59.000
1183	69139ef8c10c0	WWWFLZFQM	dni	1	919346	790795	1092245	2025-11-11 20:39:20.000	2025-11-24 10:17:59.000	2025-11-11 21:55:46.000
1184	6913abdb98aac	EUXMZKJYH	dni	1	919597	790821	1093907	2025-11-11 21:34:19.000	2025-11-24 10:17:59.000	2025-11-11 22:48:14.000
1185	6913c01817404	GRRPMDTOZ	dni	0	919621	790854	1094024	2025-11-11 23:00:40.000	2025-11-24 10:17:59.000
1186	6913cad323ec0	KUHZTCLNT	dni	0	919624	790863	1094058	2025-11-11 23:46:27.000	2025-11-24 10:17:59.000
1187	6913f28a141f8	HDTDJXULJ	dni	1	919100	790868	1090648	2025-11-12 02:35:54.000	2025-11-24 10:17:59.000	2025-11-12 03:38:31.000
1188	69143c46b286d	OFZXACUCE	dni	0	207566	790883	1094165	2025-11-12 07:50:30.000	2025-11-24 10:17:59.000
1189	6914570aeb469	KOOPIFAFH	dni	0	864721	790899	1094256	2025-11-12 09:44:42.000	2025-11-24 10:17:59.000
1190	691469ae26b78	CNAHQZAOG	corta	0	850403	790926	1094310	2025-11-12 11:04:14.000	2025-11-24 10:17:59.000
1191	6914794893b6a	TQBFNCCKC	dni	0	919473	790943	1093013	2025-11-12 12:10:48.000	2025-11-24 10:17:59.000
1192	69147eb5cfc55	WLJIOAOOB	dni	1	919608	790950	1093966	2025-11-12 12:33:57.000	2025-11-24 10:17:59.000	2025-11-12 13:41:39.000
1193	691481af80bcf	YNJYACCQJ	dni	0	919691	790953	1094479	2025-11-12 12:46:39.000	2025-11-24 10:18:00.000
1194	6914a77d4607a	AYDSZFETD	dni	1	919729	791017	1094747	2025-11-12 15:27:57.000	2025-11-24 10:18:00.000	2025-11-12 20:42:54.000
1195	6914b6ec72952	PFEZRBTZQ	dni	1	919625	791034	1092293	2025-11-12 16:33:48.000	2025-11-24 10:18:00.000
1196	6914cc04e4318	XMPIXIZNU	dni	1	919765	791061	1094933	2025-11-12 18:03:48.000	2025-11-24 10:18:00.000
1197	6914d09a74e0a	OCBVTFYVK	dni	1	919769	791065	1094966	2025-11-12 18:23:22.000	2025-11-24 10:18:00.000
1198	6915b27b01877	WNUNFZTEN	dni	1	919863	791170	1095608	2025-11-13 10:27:07.000	2025-11-24 10:18:00.000
1199	6915d190dab40	KLAPZWRVG	dni	1	919885	791198	1095783	2025-11-13 12:39:44.000	2025-11-24 10:18:00.000	2025-11-13 14:34:36.000
1200	691619f3387a3	QNXGGVAFH	dni	1	820448	791266	1096184	2025-11-13 17:48:35.000	2025-11-24 10:18:00.000
1201	69161acf5858c	OPXHWZTUR	dni	0	919931	791268	1096133	2025-11-13 17:52:15.000	2025-11-24 10:18:00.000
1202	6916247aa92e1	PTPJSQYTJ	dni	1	138314	791277	1096243	2025-11-13 18:33:30.000	2025-11-24 10:18:00.000
1203	69162d8411857	WLNVOEZDZ	dni	1	919948	791285	1096300	2025-11-13 19:12:04.000	2025-11-24 10:18:00.000	2025-11-13 20:33:22.000
1204	6916362a0a7fa	SXRRLSJZC	dni	1	882943	791297	1096272	2025-11-13 19:48:58.000	2025-11-24 10:18:00.000
1205	691706ee16aaf	JFJKTSBVW	escopeta	1	920030	791384	1096836	2025-11-14 10:39:42.000	2025-11-24 10:18:00.000	2025-11-14 12:10:59.000
1206	691709a9b1c87	TXTXGOSKW	dni	1	920031	791387	1090690	2025-11-14 10:51:21.000	2025-11-24 10:18:00.000
1207	69172fac331ef	RHNWQIPXJ	dni	1	920063	791431	1097048	2025-11-14 13:33:32.000	2025-11-24 10:18:00.000	2025-11-14 14:56:07.000
1208	69174316d80c8	VFDVNOHRD	dni	1	424926	791447	1097133	2025-11-14 14:56:22.000	2025-11-24 10:18:01.000
1209	691759694fb0f	UTIRSXFRY	dni	1	920080	791463	1097227	2025-11-14 16:31:37.000	2025-11-24 10:18:01.000	2025-11-17 11:43:17.000
1210	691762ef04612	LALCGPUXL	dni	1	817760	791468	1097265	2025-11-14 17:12:15.000	2025-11-24 10:18:01.000	2025-11-14 18:18:07.000
1211	69176fcb3175a	FDEWELIUA	dni	1	920078	791480	1094613	2025-11-14 18:07:07.000	2025-11-24 10:18:01.000
1212	691789e99b11d	UHRQDNMPF	dni	1	350830	791504	1097413	2025-11-14 19:58:33.000	2025-11-24 10:18:01.000
1213	69179710bc119	YPYPMMWFB	dni	1	920116	791515	1097498	2025-11-14 20:54:40.000	2025-11-24 10:18:01.000
1214	69188304e138c	BUFJBYUCX	dni	1	920187	791591	1097171	2025-11-15 13:41:24.000	2025-11-24 10:18:01.000
1215	69188d399a2d5	ZWVNLFXIB	dni	0	920191	791600	1098019	2025-11-15 14:24:57.000	2025-11-24 10:18:01.000
1216	6918911744db2	TYJGXDINE	dni	1	920186	791605	1097985	2025-11-15 14:41:27.000	2025-11-24 10:18:01.000
1217	6918a14f0aa39	UUVSMJUKP	dni	1	920206	791623	1098105	2025-11-15 15:50:39.000	2025-11-24 10:18:01.000
1218	6918a7e056319	UOQAJEWIV	dni	1	919614	791626	1093989	2025-11-15 16:18:40.000	2025-11-24 10:18:01.000
1219	6918e21bf2eae	TGZSVIPEP	dni	1	920241	791648	1098583	2025-11-15 20:27:07.000	2025-11-24 10:18:01.000
1220	6919a9468cf9e	CWGWIVZEN	dni	0	920292	791704	1096592	2025-11-16 10:36:54.000	2025-11-24 10:18:01.000
1221	6919ab222c7cd	QHDNLVBRT	dni	1	920290	791706	1098907	2025-11-16 10:44:50.000	2025-11-24 10:18:01.000	2025-11-16 19:36:35.000
1222	6919bb780d7bd	WOETPXFNS	dni	1	920303	791722	1099028	2025-11-16 11:54:32.000	2025-11-24 10:18:02.000
1223	6919bea62f00c	STBOQSZGM	dni	1	856826	791724	1099038	2025-11-16 12:08:06.000	2025-11-24 10:18:02.000	2025-11-16 13:16:16.000
1224	6919bffa924d7	LNVQVWMBO	dni	1	397264	791728	1099050	2025-11-16 12:13:46.000	2025-11-24 10:18:02.000	2025-11-16 13:21:38.000
1225	6919cb22764aa	XUWBSZGMA	dni	1	916334	791742	1097838	2025-11-16 13:01:22.000	2025-11-24 10:18:02.000	2025-11-16 17:03:33.000
1226	6919da941aa69	ODMHIIMOJ	dni	1	893649	791757	1099178	2025-11-16 14:07:16.000	2025-11-24 10:18:02.000	2025-11-16 15:09:56.000
1227	691a0cbf78f8d	AWGCJOKTZ	dni	1	881403	791806	1099449	2025-11-16 17:41:19.000	2025-11-24 10:18:02.000	2025-11-16 18:50:34.000
1228	691a12763f285	FDIYMVPFW	dni	1	919509	791813	1093392	2025-11-16 18:05:42.000	2025-11-24 10:18:02.000
1229	691a1cf9ae1af	MEEZITQTZ	dni	1	894806	791825	1099548	2025-11-16 18:50:33.000	2025-11-24 10:18:02.000	2025-11-16 19:54:01.000
1230	691a2052961a0	YWEDPCLRY	dni	1	891551	791834	1096436	2025-11-16 19:04:50.000	2025-11-24 10:18:02.000	2025-11-16 20:07:17.000
1231	691a40e66b4db	BFRHBWKVV	dni	1	920408	791868	1099783	2025-11-16 21:23:50.000	2025-11-24 10:18:02.000	2025-11-16 22:26:32.000
1232	691a4bc880eb9	PTEYGCZSM	dni	1	917124	791880	1099870	2025-11-16 22:10:16.000	2025-11-24 10:18:02.000	2025-11-16 23:12:01.000
1233	691b01db4d5ac	XGIAEOSXV	dni	1	813951	791957	1091209	2025-11-17 11:07:07.000	2025-11-24 10:18:02.000
1234	691b5a22e00bc	PRLAKMVMR	dni	1	380870	792064	1100773	2025-11-17 17:23:46.000	2025-11-24 10:18:02.000
1235	691b607d723c8	NGPTILJEE	dni	0	920577	792070	1100826	2025-11-17 17:50:53.000	2025-11-24 10:18:02.000
1236	691b819683f1c	PXLMYNTYW	dni	1	920604	792118	1099984	2025-11-17 20:12:06.000	2025-11-24 10:18:02.000
1237	691b89461af16	FAVKDVDWQ	dni	0	284027	792131	1101115	2025-11-17 20:44:54.000	2025-11-24 10:18:02.000
1238	691b8e86488ae	EUGFGPDIF	dni	1	920616	792136	1099603	2025-11-17 21:07:18.000	2025-11-24 10:18:02.000
1239	691ba3f0af219	AJMJYXQUI	dni	1	920635	792161	1101283	2025-11-17 22:38:40.000	2025-11-24 10:18:02.000
1240	691c1d53a222d	SFIBLQOMH	dni	1	920656	792193	1101430	2025-11-18 07:16:35.000	2025-11-24 10:18:02.000
1241	691cabda1b5ce	SHRXRBQWX	dni	0	206326	792314	1101995	2025-11-18 17:24:42.000	2025-11-24 10:18:02.000
1242	691cac6d2d39d	RYICKAAXC	dni	0	920594	792315	1100922	2025-11-18 17:27:09.000	2025-11-24 10:18:03.000
1243	691ce5ec72055	SNYKVRXRY	dni	0	234513	792365	1100254	2025-11-18 21:32:28.000	2025-11-24 10:18:03.000
1244	691cf6bc32385	GEUISMQTB	dni	1	920811	792380	1102356	2025-11-18 22:44:12.000	2025-11-24 10:18:03.000
1245	691db9d616921	TDZWGLMQG	dni	1	920577	792450	1102723	2025-11-19 12:36:38.000	2025-11-24 10:18:03.000
1246	691dc4bfa83d1	SLOLYQNZS	dni	0	920779	792457	1102195	2025-11-19 13:23:11.000	2025-11-24 10:18:03.000
1247	691ddbd7c27c0	VMNTENEJE	dni	0	881403	792489	1101007	2025-11-19 15:01:43.000	2025-11-24 10:18:03.000
1248	691e0a2091055	KCEYKDCVU	escopeta	0	917859	792543	1103115	2025-11-19 18:19:12.000	2025-11-24 10:18:03.000	2025-11-20 17:02:32.000
1249	691e0e496c658	ICXUKSBSD	dni	0	920930	792547	1099250	2025-11-19 18:36:57.000	2025-11-24 10:18:03.000
1250	691e13cea075c	ZGHAXYFKN	dni	0	918369	792558	1103196	2025-11-19 19:00:30.000	2025-11-24 10:18:03.000
1251	691e29b40b105	ALGBBJWYC	dni	0	920954	792581	1099416	2025-11-19 20:33:56.000	2025-11-24 10:18:03.000
1252	691e316cf11d8	AVHGWQPRD	dni	0	920964	792593	1101693	2025-11-19 21:06:52.000	2025-11-24 10:18:03.000
1253	691e46f558635	VCRJQQASL	dni	0	920980	792609	1103454	2025-11-19 22:38:45.000	2025-11-24 10:18:03.000
1254	691e5e4819cb3	MLEIAJIPI	dni	1	919882	792616	1095768	2025-11-20 00:18:16.000	2025-11-24 10:18:03.000
1255	691ec4cbb3e5e	DBANSIAKX	dni	0	920934	792629	1103192	2025-11-20 07:35:39.000	2025-11-24 10:18:03.000
1256	691ee395c92ae	BPXYMZYIM	dni	1	921003	792648	1103641	2025-11-20 09:47:01.000	2025-11-24 10:18:03.000
1257	691f2da3d9698	MMAKNHXMD	dni	0	336526	792704	1102814	2025-11-20 15:02:59.000	2025-11-24 10:18:03.000
1258	691f99fa86045	LLPQVFPQV	dni	0	921112	792794	1104409	2025-11-20 22:45:14.000	2025-11-24 10:18:03.000
1259	692000dadcd42	PVDNFRSYK	dni	0	887597	792806	1104531	2025-11-21 06:04:10.000	2025-11-24 10:18:03.000
1260	69201253b4113	UYEKUQMVA	dni	0	853807	792817	1104509	2025-11-21 07:18:43.000	2025-11-24 10:18:04.000
1261	69201a302415b	TRRQHCAUO	dni	0	921145	792821	1104572	2025-11-21 07:52:16.000	2025-11-24 10:18:04.000
1262	692026bde96bb	CMBSIYMOF	dni	0	55218	792831	1104621	2025-11-21 08:45:49.000	2025-11-24 10:18:04.000
1263	692026ddbe979	BOIQGWORR	dni	0	921153	792832	1104614	2025-11-21 08:46:21.000	2025-11-24 10:18:04.000
1264	692027fb6e956	AQLBKZHCJ	dni	0	921156	792833	1104625	2025-11-21 08:51:07.000	2025-11-24 10:18:04.000
1265	692047a090410	ILXDTUIEQ	dni	0	215765	792892	1104839	2025-11-21 11:06:08.000	2025-11-24 10:18:04.000
1266	69205100e8960	OMRSBYQNB	dni	0	897690	792907	1104701	2025-11-21 11:46:08.000	2025-11-24 10:18:04.000
1267	69205aa5beda1	JGVXCQVFC	dni	0	920552	792927	1100681	2025-11-21 12:27:17.000	2025-11-24 10:18:04.000
1268	69205c34b19c1	PQOVKKAYZ	dni	0	921210	792931	1101815	2025-11-21 12:33:56.000	2025-11-24 10:18:04.000
1269	6920638f3331e	HHNILMUGQ	dni	0	921217	792941	1105066	2025-11-21 13:05:19.000	2025-11-24 10:18:04.000
1270	6920738e231f5	DPJVTKYSY	dni	0	883911	792964	1101641	2025-11-21 14:13:34.000	2025-11-24 10:18:05.000
1271	6920899a73fbd	RJAODXQLL	dni	0	914732	792992	1098248	2025-11-21 15:47:38.000	2025-11-24 10:18:05.000
1272	69208fd4c12ae	IKCVMFSGT	dni	0	921269	793002	1105370	2025-11-21 16:14:12.000	2025-11-24 10:18:05.000
1273	6920a45a3e502	RUFGUPBWU	dni	0	919597	793034	1105520	2025-11-21 17:41:46.000	2025-11-24 10:18:05.000
1274	6920adefbe14e	YYVSILKIS	dni	0	858106	793050	1105553	2025-11-21 18:22:39.000	2025-11-24 10:18:05.000
1275	6920bea16d0a9	KIRAMPSRY	dni	0	920992	793075	1102302	2025-11-21 19:33:53.000	2025-11-24 10:18:05.000
1276	6920c0a6ae65a	BLMYBQHIR	dni	0	921323	793079	1104340	2025-11-21 19:42:30.000	2025-11-24 10:18:05.000
1277	6920c0bdf236e	QVPAGNSCH	dni	0	921326	793080	1105814	2025-11-21 19:42:53.000	2025-11-24 10:18:05.000
1278	6920c9d9b3510	MMNVGOQLX	dni	0	273726	793095	1105843	2025-11-21 20:21:45.000	2025-11-24 10:18:05.000
1279	6920d5b333141	EKHJMWKRG	escopeta	0	921343	793119	1105750	2025-11-21 21:12:19.000	2025-11-24 10:18:05.000
1280	6920d878a98e3	TDMDYNDBH	escopeta	0	921114	793122	1105929	2025-11-21 21:24:08.000	2025-11-24 10:18:05.000
1281	6920dcf186cfd	LHTGVMPAG	dni	0	251283	793129	1106032	2025-11-21 21:43:13.000	2025-11-24 10:18:05.000
1282	6920e16299d93	OILTSFQRA	dni	0	881952	793135	1104327	2025-11-21 22:02:10.000	2025-11-24 10:18:05.000
1283	6920e695cf641	CYHYABPBF	dni	0	920257	793143	1106067	2025-11-21 22:24:21.000	2025-11-24 10:18:05.000
1284	6920e98301701	RXOMDORYP	dni	0	921370	793147	1106093	2025-11-21 22:36:51.000	2025-11-24 10:18:05.000
1285	6920f1b435385	GYWTHMVMA	dni	0	838854	793153	1106116	2025-11-21 23:11:48.000	2025-11-24 10:18:05.000
1286	692135112065b	SDNQYLYBO	dni	0	921385	793170	1106206	2025-11-22 03:59:13.000	2025-11-24 10:18:05.000
1287	69216eda5af60	MSUEEOOUD	dni	0	921392	793180	1106253	2025-11-22 08:05:46.000	2025-11-24 10:18:05.000
1288	6921765094b1d	AGSPGLOOR	dni	0	921402	793190	1106300	2025-11-22 08:37:36.000	2025-11-24 10:18:05.000
1289	69217eabc67d7	LFYIOOCQE	dni	0	881119	793194	1106259	2025-11-22 09:13:15.000	2025-11-24 10:18:06.000
1290	69218d6892cd7	UAKTFTANX	escopeta	0	920406	793212	1099791	2025-11-22 10:16:08.000	2025-11-24 10:18:06.000
1291	692191f7e7a26	TOTKSCIQE	corta	0	862916	793217	1104177	2025-11-22 10:35:35.000	2025-11-24 10:18:06.000
1292	69219e366d331	QLMWFUQZG	dni	0	921422	793233	1105952	2025-11-22 11:27:50.000	2025-11-24 10:18:06.000
1293	6921abe5817e4	BKJSJNTMB	dni	0	921444	793248	1106552	2025-11-22 12:26:13.000	2025-11-24 10:18:06.000
1294	6921b2d524115	KVDJGJLUP	dni	0	921453	793255	1106588	2025-11-22 12:55:49.000	2025-11-24 10:18:06.000
1295	6921dc8c59e55	MKWGNTTWV	dni	0	916730	793291	1074658	2025-11-22 15:53:48.000	2025-11-24 10:18:06.000
1296	6921e00d42a9a	VXAPFATWP	dni	0	921484	793296	1106793	2025-11-22 16:08:45.000	2025-11-24 10:18:06.000
1297	69221552500e5	WKEUQBDVA	dni	0	920116	793367	1107106	2025-11-22 19:56:02.000	2025-11-24 10:18:06.000
1298	69221e0d8a9b9	JYSVDPNJY	escopeta	0	921553	793383	1098109	2025-11-22 20:33:17.000	2025-11-24 10:18:06.000
1299	6922205ec20fe	QFFQMTPGP	dni	0	921561	793386	1107125	2025-11-22 20:43:10.000	2025-11-24 10:18:06.000
1300	692230780910e	CSLMFDSLZ	dni	0	921580	793404	1107266	2025-11-22 21:51:52.000	2025-11-24 10:18:06.000
1301	692230eb74292	SVXSNKWBE	dni	0	921579	793405	1107270	2025-11-22 21:53:47.000	2025-11-24 10:18:07.000
1302	6922413949530	XJZINFKYI	dni	0	921590	793416	1107334	2025-11-22 23:03:21.000	2025-11-24 10:18:07.000
1303	69224d34e3fe3	JREGZLRRW	dni	0	343876	793421	1106112	2025-11-22 23:54:28.000	2025-11-24 10:18:07.000
1304	69225f02d6adc	UGYAWFFMS	dni	0	921603	793429	1107377	2025-11-23 01:10:26.000	2025-11-24 10:18:07.000
1305	6922b75b6a4e8	GPSRAFLER	dni	0	921611	793440	1107432	2025-11-23 07:27:23.000	2025-11-24 10:18:07.000
1306	6922cd048775b	QIWKDMXSE	dni	0	921484	793456	1107506	2025-11-23 08:59:48.000	2025-11-24 10:18:07.000
1307	6922e3d95821c	IYPFBTKTI	dni	0	407155	793478	1107328	2025-11-23 10:37:13.000	2025-11-24 10:18:07.000
1308	6922e538b42ac	IVTCEAWKK	dni	0	921638	793482	1107612	2025-11-23 10:43:04.000	2025-11-24 10:18:07.000
1309	6922f11d0c5f2	OREQRELLT	dni	0	921650	793499	1107688	2025-11-23 11:33:49.000	2025-11-24 10:18:07.000
1310	6922f9a145205	LRLMJUHDD	dni	0	258502	793508	1107730	2025-11-23 12:10:09.000	2025-11-24 10:18:07.000
1311	6922ff24d680c	UZPDPABYD	dni	0	921671	793519	1107770	2025-11-23 12:33:40.000	2025-11-24 10:18:07.000
1312	69230a1b94c69	CZGYBWLRO	dni	0	921678	793532	1107839	2025-11-23 13:20:27.000	2025-11-24 10:18:07.000
1313	69231c1f1ebe5	VDFJTMBTG	dni	0	921690	793548	1107915	2025-11-23 14:37:19.000	2025-11-24 10:18:07.000
1314	6923346b2f033	WMIVFGBAE	dni	0	921694	793580	1107958	2025-11-23 16:20:59.000	2025-11-24 10:18:07.000
1315	692337417afb2	FQRYDLZMN	dni	0	921726	793587	1108087	2025-11-23 16:33:05.000	2025-11-24 10:18:07.000
1316	6923458c8c37c	OVYMTVKKV	dni	0	239109	793618	1108171	2025-11-23 17:34:04.000	2025-11-24 10:18:07.000
1317	69234dedd3c04	XAMMDAJAV	dni	0	911904	793639	1107531	2025-11-23 18:09:49.000	2025-11-24 10:18:07.000
1318	692366acc7142	XTSJUZFRY	dni	0	921789	793684	1106166	2025-11-23 19:55:24.000	2025-11-24 10:18:08.000
1319	69238cb08671c	DIFTDWPQI	dni	0	826320	793755	1101142	2025-11-23 22:37:36.000	2025-11-24 10:18:08.000
1320	69239560389a0	LHUORGEUE	dni	0	921853	793764	1108821	2025-11-23 23:14:40.000	2025-11-24 10:18:08.000
1321	69242bde59e46	VIKXLVXJO	dni	0	921906	793830	1109194	2025-11-24 09:56:46.000	2025-11-24 10:18:08.000
1322	69242d21bbe6f	VNAQDUHQY	corta	0	857090	793774	1108920	2025-11-24 10:02:09.000	2025-11-24 10:18:08.000	2025-11-24 11:04:41.000
1323	69243408712db	QMVEYADEE	dni	0	921919	793837	1109266	2025-11-24 10:31:36.000	2025-11-24 10:31:36.000
1324	69243566def23	HRVFJNMJH	dni	0	874468	793840	1101036	2025-11-24 10:37:26.000	2025-11-24 10:37:27.000
1325	6924380ba2b2a	JJEWRNVQF	dni	0	888729	793847	1109360	2025-11-24 10:48:43.000	2025-11-24 10:48:43.000
1326	69243caf5f549	NKVSSBDSJ	dni	0	234513	793862	1109413	2025-11-24 11:08:31.000	2025-11-24 11:08:31.000
1327	69244623ca6f1	HOWEGIAQP	dni	0	921377	793880	1106143	2025-11-24 11:48:51.000	2025-11-24 11:48:51.000
1328	692449249bb26	ZLKWLAEVT	dni	0	902588	793883	1109599	2025-11-24 12:01:40.000	2025-11-24 12:01:40.000
1329	692451467f5a9	HPBAGYLDJ	dni	0	396389	793908	1109646	2025-11-24 12:36:22.000	2025-11-24 12:36:22.000
1330	692453347b5b0	HFAHVLATZ	dni	0	921967	793918	1105711	2025-11-24 12:44:36.000	2025-11-24 12:44:36.000	er ? new Carrier($order->id_carrier) : false;
                        $orderLanguage = new Language((int) $order->id_lang);
                        if ((int) Configuration::get('PS_INVOICE') && $order_status->invoice && $order->invoice_number) {
                            $currentLanguage = $this->context->language;
                            $this->context->language = $orderLanguage;
                            $this->context->getTranslator()->setLocale($orderLanguage->locale);
                            $order_invoice_list = $order->getInvoicesCollection();
                            Hook::exec('actionPDFInvoiceRender', ['order_invoice_list' => $order_invoice_list]);
                            $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, $this->context->smarty);
                            $file_attachement['content'] = $pdf->render(false);
                            $file_attachement['name'] = Configuration::get('PS_INVOICE_PREFIX', (int) $order->id_lang, null, $order->id_shop) . sprintf('%06d', $order->invoice_number) . '.pdf';
                            $file_attachement['mime'] = 'application/pdf';
                            $this->context->language = $currentLanguage;
                            $this->context->getTranslator()->setLocale($currentLanguage->locale);
                        } else {
                            $file_attachement = null;
                        }
                        if (self::DEBUG_MODE) {
                            PrestaShopLogger::addLog('PaymentModule::validateOrder - Mail is about to be sent', 1, null, 'Cart', (int) $id_cart, true);
                        }
                        if (Validate::isEmail($this->context->customer->email)) {
                            $multiple_product_types_message_html = '';
                            $multiple_product_types_message_txt = '';
                            $products_in_cart_pickup_gc = Cart::haveMultipleProductTypes($this->context->cart->id);
                            if ($products_in_cart_pickup_gc) {
                                $multiple_product_types_message_txt = $this->trans('Weapon-type products will be collected at the intervention of the selected Civil Guard, the rest of the products will be sent to the specified billing address.', [], 'Shop.Theme.Checkout') . PHP_EOL . PHP_EOL . $this->trans('The weapon-type products that you must collect in the intervention of the selected Civil Guard are the following:', [], 'Shop.Theme.Checkout') . PHP_EOL . PHP_EOL;
                                $multiple_product_types_message_html = '<p>' . $this->trans('[b]Weapon-type products will be collected at the intervention of the selected Civil Guard, the rest of the products will be sent to the specified billing address.[/b]', ['[b]' => '<strong>', '[/b]' => '</strong>'], 'Shop.Theme.Checkout') . '</p><p>' . $this->trans('[b]The weapon-type products that you must collect in the intervention of the selected Civil Guard are the following:[/b]', ['[b]' => '<strong>', '[/b]' => '</strong>'], 'Shop.Theme.Checkout') . '</p><ul>';
                                foreach ($products_in_cart_pickup_gc as $product_cart) {
                                    $multiple_product_types_message_txt .= $product_cart['name'] . PHP_EOL;
                                    $multiple_product_types_message_html .= '<li>' . $product_cart['name'] . '</li>';
                                }
                                $multiple_product_types_message_html .= '</ul>';
                            }
                            // Solo solicitar documento si la orden está pagada
                            $document = '';
                            if ($order->isPaid()) {
                                $document = $order->getDocumentInstructions() ?? '';
                            }
                            $lottery = $order->requesLottery();
                            $tracking = $order->requestDeliveryTimes();
                            $data = [
                                '{firstname}' => $this->context->customer->firstname,
                                '{lastname}' => $this->context->customer->lastname,
                                '{email}' => $this->context->customer->email,
                                '{delivery_block_txt}' => $this->_getFormatedAddress($delivery, AddressFormat::FORMAT_NEW_LINE),
                                '{invoice_block_txt}' => $this->_getFormatedAddress($invoice, AddressFormat::FORMAT_NEW_LINE),
                                '{delivery_block_html}' => $this->_getFormatedAddress($delivery, '<br />', [
                                    'firstname' => '<span style="font-weight:bold;">%s</span>',
                                    'lastname' => '<span style="font-weight:bold;">%s</span>',
                                ]),
                                '{invoice_block_html}' => $this->_getFormatedAddress($invoice, '<br />', [
                                    'firstname' => '<span style="font-weight:bold;">%s</span>',
                                    'lastname' => '<span style="font-weight:bold;">%s</span>',
                                ]),
                                '{delivery_company}' => $delivery->company,
                                '{delivery_firstname}' => $delivery->firstname,
                                '{delivery_lastname}' => $delivery->lastname,
                                '{delivery_address1}' => $delivery->address1,
                                '{delivery_address2}' => $delivery->address2,
                                '{delivery_city}' => $delivery->city,
                                '{delivery_postal_code}' => $delivery->postcode,
                                '{delivery_country}' => $delivery->country,
                                '{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
                                '{delivery_phone}' => ($delivery->phone) ? $delivery->phone : $delivery->phone_mobile,
                                '{delivery_other}' => $delivery->other,
                                '{invoice_company}' => $invoice->company,
                                '{invoice_vat_number}' => $invoice->vat_number,
                                '{invoice_firstname}' => $invoice->firstname,
                                '{invoice_lastname}' => $invoice->lastname,
                                '{invoice_address2}' => $invoice->address2,
                                '{invoice_address1}' => $invoice->address1,
                                '{invoice_city}' => $invoice->city,
                                '{invoice_postal_code}' => $invoice->postcode,
                                '{invoice_country}' => $invoice->country,
                                '{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
                                '{invoice_phone}' => ($invoice->phone) ? $invoice->phone : $invoice->phone_mobile,
                                '{invoice_other}' => $invoice->other,
                                '{order_name}' => $order->getUniqReference(),
                                '{order_id}' => $order->id,
                                '{date}' => Tools::displayDate(date('Y-m-d H:i:s'), null, 1),
                                '{carrier}' => ($virtual_product || !isset($carrier->name)) ? $this->trans('No carrier', [], 'Admin.Payment.Notification') : $carrier->name,
                                '{payment}' => Tools::substr($order->payment, 0, 255) . ($order->hasBeenPaid() ? '' : '&nbsp;' . $this->trans('(waiting for validation)', [], 'Emails.Body')),
                                '{products}' => $product_list_html,
                                '{products_txt}' => $product_list_txt,
                                '{discounts}' => $cart_rules_list_html,
                                '{discounts_txt}' => $cart_rules_list_txt,
                                '{total_paid}' => Tools::getContextLocale($this->context)->formatPrice($order->total_paid, $this->context->currency->iso_code),
                                '{total_products}' => Tools::getContextLocale($this->context)->formatPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? $order->total_products : $order->total_products_wt, $this->context->currency->iso_code),
                                '{total_discounts}' => Tools::getContextLocale($this->context)->formatPrice($order->total_discounts, $this->context->currency->iso_code),
                                '{total_shipping}' => Tools::getContextLocale($this->context)->formatPrice($order->total_shipping, $this->context->currency->iso_code),
                                '{total_shipping_tax_excl}' => Tools::getContextLocale($this->context)->formatPrice($order->total_shipping_tax_excl, $this->context->currency->iso_code),
                                '{total_shipping_tax_incl}' => Tools::getContextLocale($this->context)->formatPrice($order->total_shipping_tax_incl, $this->context->currency->iso_code),
                                '{total_wrapping}' => Tools::getContextLocale($this->context)->formatPrice($order->total_wrapping, $this->context->currency->iso_code),
                                '{total_tax_paid}' => Tools::getContextLocale($this->context)->formatPrice(($order->total_paid_tax_incl - $order->total_paid_tax_excl), $this->context->currency->iso_code),
                                '{delivery_message}' => $order->getFirstMessage(),
                                '{product_pickup_gc_message}' => $multiple_product_types_message_html,
                                '{product_pickup_gc_message_txt}' => $multiple_product_types_message_txt,
                             ];
                                $data['{document}'] = $document;
                                $data['{tracking}'] = $tracking;
                                $data['{lottery}'] = $lottery;
                            Mail::Send(
                                (int) $order->id_lang,
                                'order_conf',
                                $this->context->getTranslator()->trans(
                                    'Order confirmation',
                                    [],
                                    'Emails.Subject',
                                    $orderLanguage->locale
                                ),
                                $data,
                                $this->context->customer->email,
                                $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
                                null,
                                null,
                                $file_attachement,
                                null,
                                _PS_MAIL_DIR_,
                                false,
                                (int) $order->id_shop
                            );
                        }
                    }
                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $product_list = $order->getProducts();
                        foreach ($product_list as $product) {
                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                StockAvailable::synchronize($product['product_id'], $order->id_shop);
                            }
                        }
                    }
                    $order->updateOrderDetailTax();
                    (new StockManager())->updatePhysicalProductQuantity(
                        (int) $order->id_shop,
                        (int) Configuration::get('PS_OS_ERROR'),
                        (int) Configuration::get('PS_OS_CANCELED'),
                        null,
                        (int) $order->id
                    );
                } else {
                    $error = $this->trans('Order creation failed', [], 'Admin.Payment.Notification');
                    PrestaShopLogger::addLog($error, 4, '0000002', 'Cart', (int) ($order->id_cart));
                    die(Tools::displayError($error));
                }
            } // End foreach $order_detail_list
            if (isset($order) && $order->id) {
                $this->currentOrder = (int) $order->id;
            }
            if (self::DEBUG_MODE) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - End of validateOrder', 1, null, 'Cart', (int) $id_cart, true);
            }
            return true;
        } else {
            $error = $this->trans('Cart cannot be loaded or an order has already been placed using this cart', [], 'Admin.Payment.Notification');
            PrestaShopLogger::addLog($error, 4, '0000001', 'Cart', (int) ($this->context->cart->id));
            die(Tools::displayError($error));
        }
    }
    /*
    * module: idxrcustomproduct
    * date: 2025-10-07 10:54:09
    * version: 1.8.4
    */
    public function validateOrder1(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null,
        ?string $order_reference = null
    ) {
        if ((bool) Module::isEnabled('idxrcustomproduct')) {
            $module = Module::getInstanceByName('idxrcustomproduct');
            $module->adjustStock($id_cart);
        }
        return parent::validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method, $message, $extra_vars, $currency_special, $dont_touch_amount, $secure_key, $shop, $order_reference);
    }
}
