<?php
/**
 * 2012-2021 INNERCODE
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA (End User License Agreement)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.innercode.lt/ps-module-eula.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@innercode.lt so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author    Innercode
 * @copyright Copyright (c) 2012 - 2021 INNERCODE, UAB. (https://www.innercode.lt)
 * @license   https://www.innercode.lt/ps-module-eula.txt
 * @package   freeshippingamountdisplay
 * @site      https://www.innercode.lt
 */

class FreeShippingAmountDisplayOverride extends FreeShippingAmountDisplay
{
    /**
     * @param string $position
     * @param array $data
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function getMessageHTML($position = 'checkout', $data = array())
    {
        //return parent::getMessageHTML($position, $data);

        $id_carrito = (int) $this->context->cookie->id_cart;
        $carrito = new Cart((int)$id_carrito);

        $this->context->smarty->assign(array(
            'id_carrito' => $id_carrito,
            'cart_sumario' => $carrito->getSummaryDetails(),
        ));

        if (!$this->context->currency) {
            $currencyId = (int) $this->context->cookie->id_currency;
            $this->context->currency = new Currency($currencyId);
        }

        $freeShippingFrom = $this->getFreeShippingPrice();
        $displayFreeShippingBlock = Configuration::get('FSAD_'.strtoupper($position).'_DISPLAY_FREE_SHIPPING');

        if ($freeShippingFrom === false) {
            return;
        }

        $freeShippingText = '';
        $product = null;

        if (isset($data['product'])) {
            $product = $data['product'];
        } elseif (Tools::getValue('id_product')) {
            $product = new Product((int) Tools::getValue('id_product'));
        }

        if (Validate::isLoadedObject($product)) {
            if ($product->is_virtual) {
                return;
            }

            // If it is product page, check if product is not restricted by carrier
            // If so, don't show the block in that position
            $productCarriers = Db::getInstance()->executeS(
                'SELECT *
                FROM '._DB_PREFIX_.'product_carrier
                WHERE `id_product` = '.$product->id
            );

            if (!empty($productCarriers)) {
                $idCarrier = $this->getCarrierId();
                $carrier = new Carrier($idCarrier);
                $found = false;

                foreach ($productCarriers as $productCarrier) {
                    if ($productCarrier['id_carrier_reference'] == $carrier->id_reference) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    return;
                }
            }
        }

        if (!$this->context->cart) {
            $cartId = (int) $this->context->cookie->id_cart;
            $this->context->cart = new Cart((int)$cartId);
        }

        /* comprobar si hay productos con costes de envío especiales en la cesta para distinguir el mensaje */
        $has_special_products = false;
        $has_standar_products = false;
        $product_list = $this->context->cart->getProducts(false, false, null, true);
        foreach ($product_list as $key => $value) {
            $sql = 'SELECT pp.`id` FROM `' . _DB_PREFIX_ . 'portes_producto` pp WHERE pp.`id_product`='.(int) $value['id_product'].' AND pp.`id_product_attribute`='.(int) $value['id_product_attribute'];
            $id_portes_product = DB::getInstance()->getValue($sql);

            if ($id_portes_product) {
                unset($product_list[$key]);
                $has_special_products = true;
            } else {
                $has_standar_products = true;
            }
        }

        /* si solo hay productos especiales no muestro nada */
        if ($has_special_products && !$has_standar_products) {
            return;
        }

        $this->context->smarty->assign(array(
            'has_special_products' => $has_special_products,
            'has_standar_products' => $has_standar_products,
        ));
        /* comprobar si hay productos con costes de envío especiales en la cesta para distinguir el mensaje */

        $totalPrice = $this->context->cart->getOrderTotal($this->useTaxes(), Cart::BOTH_WITHOUT_SHIPPING, $product_list);
        $amountLeft = $this->amountUntilFree($totalPrice);

        // Something is wrong, probably this carrier doesn't have free shipping
        if ($amountLeft === false) {
            return;
        }

        $amountLeft = (float) $amountLeft;

        // Display free shipping block
        if ($amountLeft <= 0) {
            return $displayFreeShippingBlock ? $this->getFreeShippingMessageHTML($position) : '';
        } elseif ($displayFreeShippingBlock &&
            $position == 'product' &&
            $product && $product->getPrice() > $freeShippingFrom
        ) {
            return $this->getFreeShippingMessageHTML($position, $this->l('This product has free shipping!'));
        }

        // Don't show block if cart is empty
        if (!$totalPrice && !Configuration::get('FSAD_DISPLAY_WHEN_EMPTY')) {
            return;
        }

        $amountLeftDisplay = Tools::displayPrice($amountLeft, $this->context->currency);
        $messageText = Configuration::get('FSAD_TEXT', $this->context->language->id);

        if ($messageText) {
            $messageText = str_replace('{price}', '<span class="price">' . $amountLeftDisplay . '</span>', $messageText);
        }

        $this->context->smarty->assign(array(
            'amountLeft' => $amountLeft,
            'amountLeftDisplay' => $amountLeftDisplay,
            'percentage' => $totalPrice / $freeShippingFrom * 100,
            'position' => $position,
            'freeShippingText' => $freeShippingText,
            'messageText' => $messageText
        ));

        return $this->display(__FILE__, 'views/templates/front/message.tpl');
    }


    /**
     * @param $totalPrice
     * @return float|int
     */
    public function amountUntilFree($totalPrice)
    {
        if ($this->context->cart->id) {
            foreach ($this->context->cart->getCartRules() as $rule) {
                if ($rule['free_shipping'] && ! $rule['carrier_restriction']) {
                    return 0;
                }
            }
        }

        $product_list = $this->context->cart->getProducts(false, false, null, true);
        foreach ($product_list as $key => $value) {
            $sql = 'SELECT pp.`id` FROM `' . _DB_PREFIX_ . 'portes_producto` pp WHERE pp.`id_product`='.(int) $value['id_product'].' AND pp.`id_product_attribute`='.(int) $value['id_product_attribute'];
            $id_portes_product = DB::getInstance()->getValue($sql);

            if ($id_portes_product) {
                unset($product_list[$key]);
            }
        }

        $freeShippingFrom = $this->getFreeShippingPrice();
        $totalShipping = $this->context->cart->getOrderTotal($this->useTaxes(), Cart::ONLY_SHIPPING, $product_list);

        // 0 only if it is 0 when cart is not empty
        if ($totalPrice > 0 && ! $totalShipping) {
            return 0;
        }

        // If amount is not reached, return how much left.
        // If it is reached, check if shipping is free, otherwise return false.
        // this case is possible when there are delivery limitations by carrier for products
        // and that carrier doesn't have free shipping
        // while our calculations might show that it must have (based on default carrier settings)
        return $totalPrice < $freeShippingFrom ? $freeShippingFrom - $totalPrice : ($totalShipping > 0 ? false : 0);
    }
}
