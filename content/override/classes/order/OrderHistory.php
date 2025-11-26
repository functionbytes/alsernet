<?php


use PrestaShop\PrestaShop\Core\Stock\StockManager;
use PrestaShop\PrestaShop\Adapter\StockManager as StockManagerAdapter;

class OrderHistory extends OrderHistoryCore
{

    public function changeIdOrderState($new_order_state, $id_order, $use_existing_payment = false, $tpv = null)
    {



        if (!$new_order_state || !$id_order) {
            return;
        }
        if (!is_object($id_order) && is_numeric($id_order)) {
            $order = new Order((int) $id_order);
        } elseif (is_object($id_order)) {
            $order = $id_order;
        } else {
            return;
        }
        ShopUrl::cacheMainDomainForShop($order->id_shop);
        $new_os = new OrderState((int) $new_order_state, $order->id_lang);
        $old_os = $order->getCurrentOrderState();
        if (in_array($new_os->id, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_WS_PAYMENT')))) {
            Hook::exec('actionPaymentConfirmation', array('id_order' => (int) $order->id), null, false, true, false, $order->id_shop);
        }
        Hook::exec('actionOrderStatusUpdate', array('newOrderStatus' => $new_os, 'id_order' => (int) $order->id), null, false, true, false, $order->id_shop);
        if (Validate::isLoadedObject($order) && ($new_os instanceof OrderState)) {
            $context = Context::getContext();
            $virtual_products = $order->getVirtualProducts();
            if ($virtual_products && (!$old_os || !$old_os->logable) && $new_os && $new_os->logable) {
                $assign = array();
                foreach ($virtual_products as $key => $virtual_product) {
                    $id_product_download = ProductDownload::getIdFromIdProduct($virtual_product['product_id']);
                    $product_download = new ProductDownload($id_product_download);
                    if ($product_download->display_filename != '') {
                        $assign[$key]['name'] = $product_download->display_filename;
                        $dl_link = $product_download->getTextLink(false, $virtual_product['download_hash'])
                            . '&id_order=' . (int) $order->id
                            . '&secure_key=' . $order->secure_key;
                        $assign[$key]['link'] = $dl_link;
                        if (isset($virtual_product['download_deadline']) && $virtual_product['download_deadline'] != '0000-00-00 00:00:00') {
                            $assign[$key]['deadline'] = Tools::displayDate($virtual_product['download_deadline']);
                        }
                        if ($product_download->nb_downloadable != 0) {
                            $assign[$key]['downloadable'] = (int) $product_download->nb_downloadable;
                        }
                    }
                }
                $customer = new Customer((int) $order->id_customer);
                $links = '<ul>';
                foreach ($assign as $product) {
                    $links .= '<li>';
                    $links .= '<a href="' . $product['link'] . '">' . Tools::htmlentitiesUTF8($product['name']) . '</a>';
                    if (isset($product['deadline'])) {
                        $links .= '&nbsp;' . $this->trans('expires on %s.', array($product['deadline']), 'Admin.Orderscustomers.Notification');
                    }
                    if (isset($product['downloadable'])) {
                        $links .= '&nbsp;' . $this->trans('downloadable %d time(s)', array((int) $product['downloadable']), 'Admin.Orderscustomers.Notification');
                    }
                    $links .= '</li>';
                }
                $links .= '</ul>';
                $data = array(
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                    '{id_order}' => (int) $order->id,
                    '{order_name}' => $order->getUniqReference(),
                    '{nbProducts}' => count($virtual_products),
                    '{virtualProducts}' => $links,
                );
                if (!empty($assign)) {
                    $orderLanguage = new Language((int) $order->id_lang);
                    Mail::Send(
                        (int) $order->id_lang,
                        'download_product',
                        Context::getContext()->getTranslator()->trans(
                            'The virtual product that you bought is available for download',
                            array(),
                            'Emails.Subject',
                            $orderLanguage->locale
                        ),
                        $data,
                        $customer->email,
                        $customer->firstname . ' ' . $customer->lastname,
                        null,
                        null,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        false,
                        (int) $order->id_shop
                    );
                }
            }
            $manager = null;
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $manager = StockManagerFactory::getManager();
            }
            $error_or_canceled_statuses = array(Configuration::get('PS_OS_ERROR'), Configuration::get('PS_OS_CANCELED'));
            $employee = null;
            if (!(int) $this->id_employee || !Validate::isLoadedObject(($employee = new Employee((int) $this->id_employee)))) {
                if (!Validate::isLoadedObject($old_os) && $context != null) {
                    $employee = $context->employee; // filled if from BO and order created (because no old_os)
                    if ($employee) {
                        $this->id_employee = $employee->id;
                    }
                } else {
                    $employee = null;
                }
            }
            foreach ($order->getProductsDetail() as $product) {
                if (Validate::isLoadedObject($old_os)) {
                    if ($new_os->logable && !$old_os->logable) {
                        ProductSale::addProductSale($product['product_id'], $product['product_quantity']);
                        if (!Pack::isPack($product['product_id']) &&
                            in_array($old_os->id, $error_or_canceled_statuses) &&
                            !StockAvailable::dependsOnStock($product['id_product'], (int) $order->id_shop)) {
                            StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], -(int) $product['product_quantity'], $order->id_shop);
                        }
                    } elseif (!$new_os->logable && $old_os->logable) {
                        ProductSale::removeProductSale($product['product_id'], $product['product_quantity']);
                        if (!Pack::isPack($product['product_id']) &&
                            in_array($new_os->id, $error_or_canceled_statuses) &&
                            !StockAvailable::dependsOnStock($product['id_product'])) {
                            StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], (int) $product['product_quantity'], $order->id_shop);
                        }
                    } elseif (!$new_os->logable && !$old_os->logable &&
                        in_array($new_os->id, $error_or_canceled_statuses) &&
                        !in_array($old_os->id, $error_or_canceled_statuses) &&
                        !StockAvailable::dependsOnStock($product['id_product'])
                    ) {
                        StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], (int) $product['product_quantity'], $order->id_shop);
                    }
                }
                if ($new_os->shipped == 1 && (!Validate::isLoadedObject($old_os) || $old_os->shipped == 0) &&
                    Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') &&
                    Warehouse::exists($product['id_warehouse']) &&
                    $manager != null &&
                    (int) $product['advanced_stock_management'] == 1) {
                    $warehouse = new Warehouse($product['id_warehouse']);
                    $manager->removeProduct(
                        $product['product_id'],
                        $product['product_attribute_id'],
                        $warehouse,
                        ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return']),
                        Configuration::get('PS_STOCK_CUSTOMER_ORDER_REASON'),
                        true,
                        (int) $order->id,
                        0,
                        $employee
                    );
                } elseif ($new_os->shipped == 0 && Validate::isLoadedObject($old_os) && $old_os->shipped == 1 &&
                    Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') &&
                    Warehouse::exists($product['id_warehouse']) &&
                    $manager != null &&
                    (int) $product['advanced_stock_management'] == 1
                ) {
                    if (Pack::isPack($product['product_id'])) {
                        $pack_products = Pack::getItems($product['product_id'], Configuration::get('PS_LANG_DEFAULT', null, null, $order->id_shop));
                        foreach ($pack_products as $pack_product) {
                            if ($pack_product->advanced_stock_management == 1) {
                                $mvts = StockMvt::getNegativeStockMvts($order->id, $pack_product->id, 0, $pack_product->pack_quantity * $product['product_quantity']);
                                foreach ($mvts as $mvt) {
                                    $manager->addProduct(
                                        $pack_product->id,
                                        0,
                                        new Warehouse($mvt['id_warehouse']),
                                        $mvt['physical_quantity'],
                                        null,
                                        $mvt['price_te'],
                                        true,
                                        null,
                                        $employee
                                    );
                                }
                                if (!StockAvailable::dependsOnStock($product['id_product'])) {
                                    StockAvailable::updateQuantity($pack_product->id, 0, (int) $pack_product->pack_quantity * $product['product_quantity'], $order->id_shop);
                                }
                            }
                        }
                    } else {
                        $mvts = StockMvt::getNegativeStockMvts(
                            $order->id,
                            $product['product_id'],
                            $product['product_attribute_id'],
                            ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return'])
                        );
                        foreach ($mvts as $mvt) {
                            $manager->addProduct(
                                $product['product_id'],
                                $product['product_attribute_id'],
                                new Warehouse($mvt['id_warehouse']),
                                $mvt['physical_quantity'],
                                null,
                                $mvt['price_te'],
                                true
                            );
                        }
                    }
                }
                if (version_compare(_PS_VERSION_, '1.7.1.2', '>')) {
                    if (Validate::isLoadedObject($old_os) && Validate::isLoadedObject($new_os) && $new_os->shipped != $old_os->shipped && !Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $product_quantity = (int) ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return']);
                        if ($product_quantity > 0) {
                            $current_shop_context_type = Context::getContext()->shop->getContextType();
                            if ($current_shop_context_type !== Shop::CONTEXT_SHOP) {
                                $current_shop_group_id = Context::getContext()->shop->getContextShopGroupID();
                                Context::getContext()->shop->setContext(Shop::CONTEXT_SHOP, $order->id_shop);
                            }
                            (new StockManager())->saveMovement(
                                (int) $product['product_id'],
                                (int) $product['product_attribute_id'],
                                (int) $product_quantity * ($new_os->shipped == 1 ? -1 : 1),
                                array(
                                    'id_order' => $order->id,
                                    'id_stock_mvt_reason' => ($new_os->shipped == 1 ? Configuration::get('PS_STOCK_CUSTOMER_ORDER_REASON') : Configuration::get('PS_STOCK_CUSTOMER_ORDER_CANCEL_REASON')),
                                )
                            );
                            if ($current_shop_context_type !== Shop::CONTEXT_SHOP) {
                                Context::getContext()->shop->setContext($current_shop_context_type, $current_shop_group_id);
                            }
                        }
                    }
                }
            }
        }
        $this->id_order_state = (int) $new_order_state;
        if (!Validate::isLoadedObject($new_os) || !Validate::isLoadedObject($order)) {
            die($this->trans('Invalid new order status', array(), 'Admin.Orderscustomers.Notification'));
        }
        $order->current_state = $this->id_order_state;
        $order->valid = $new_os->logable;
        $order->update();
        if ($new_os->invoice && !$order->invoice_number) {
            $order->setInvoice($use_existing_payment);
        } elseif ($new_os->delivery && !$order->delivery_number) {
            $order->setDeliverySlip();
        }
        if ($new_os->paid == 1) {
            $invoices = $order->getInvoicesCollection();
            if ($order->total_paid != 0) {
                $payment_method = Module::getInstanceByName($order->module);
            } else {
                $payment_method = "";
            }
            foreach ($invoices as $invoice) {

                $rest_paid = $invoice->getRestPaid();
                if ($rest_paid > 0) {
                    $payment = new OrderPayment();
                    $payment->order_reference = Tools::substr($order->reference, 0, 9);
                    $payment->id_currency = $order->id_currency;
                    $payment->amount = $rest_paid;
                    if ($order->total_paid != 0) {
                        if (isset($tpv) && $tpv != null && $tpv) {
                            $text_advanced_payment = $tpv->advanced_payment_text[$order->id_lang] ? $tpv->advanced_payment_text[$order->id_lang] : $tpv->advanced_payment_text[Configuration::get('PS_LANG_DEFAULT')];
                            $payment->payment_method = $text_advanced_payment;
                        } else {
                            $payment->payment_method = $payment_method->displayName;
                        }
                    } else {
                        $payment->payment_method = null;
                    }
                    if ($payment->id_currency == $order->id_currency) {
                        $order->total_paid_real += $payment->amount;
                    } else {
                        $order->total_paid_real += Tools::ps_round(Tools::convertPrice($payment->amount, $payment->id_currency, false), 2);
                    }
                    $order->save();
                    $payment->conversion_rate = 1;
                    $payment->save();
                    Db::getInstance()->execute('
                    INSERT INTO `'._DB_PREFIX_.'order_invoice_payment` (`id_order_invoice`, `id_order_payment`, `id_order`)
                    VALUES('.(int)$invoice->id.', '.(int)$payment->id.', '.(int)$order->id.')');
                }
            }
        }
        if ($new_os->delivery) {
            $order->setDelivery();
        }
        Hook::exec('actionOrderStatusPostUpdate', array('newOrderStatus' => $new_os, 'id_order' => (int) $order->id), null, false, true, false, $order->id_shop);
        if (version_compare(_PS_VERSION_, '1.7.1.2', '>')) {
            (new StockManagerAdapter())->updatePhysicalProductQuantity(
                (int) $order->id_shop,
                (int) Configuration::get('PS_OS_ERROR'),
                (int) Configuration::get('PS_OS_CANCELED'),
                null,
                (int) $order->id
            );
        }

        // Send document request to Laravel when order status changes to paid (manual status change)
        if ($new_order_state == 2 && (!Validate::isLoadedObject($new_order_state) || $new_order_state != 2)) {
            // Only trigger if this is a manual status change (not from payment processor)
            // Payment processor triggers are handled in PaymentModule.php via actionPaymentConfirmation hook
            $order->sendDocumentRequest();
        }


        ShopUrl::resetMainDomainCache();
    }




}
