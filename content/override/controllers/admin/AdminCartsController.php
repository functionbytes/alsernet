<?php
/**
* Quantity Discount Pro
*
* NOTICE OF LICENSE
*
* This product is licensed for one customer to use on one installation (test stores and multishop included).
* Site developer has the right to modify this module to suit their needs, but can not redistribute the module in
* whole or in part. Any other use of this module constitues a violation of the user agreement.
*
* DISCLAIMER
*
* NO WARRANTIES OF DATA SAFETY OR MODULE SECURITY
* ARE EXPRESSED OR IMPLIED. USE THIS MODULE IN ACCORDANCE
* WITH YOUR MERCHANT AGREEMENT, KNOWING THAT VIOLATIONS OF
* PCI COMPLIANCY OR A DATA BREACH CAN COST THOUSANDS OF DOLLARS
* IN FINES AND DAMAGE A STORES REPUTATION. USE AT YOUR OWN RISK.
*
*  @author    idnovate.com <info@idnovate.com>
*  @copyright 2020 idnovate.com
*  @license   See above
*/
class AdminCartsController extends AdminCartsControllerCore
{
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:11:00
    * version: 2.1.37
    */
    public function ajaxProcessUpdateDeliveryOption()
    {
        if (Module::isEnabled('quantitydiscountpro')) {
            $delivery_option = Tools::getValue('delivery_option');
            if ($delivery_option !== false) {
                $this->context->cart->setDeliveryOption(array($this->context->cart->id_address_delivery => $delivery_option));
            }
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            $quantityDiscount = new QuantityDiscountRule();
            $quantityDiscount->createAndRemoveRules();
        }
        echo parent::ajaxProcessUpdateDeliveryOption();
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:11:00
    * version: 2.1.37
    */
    public function ajaxProcessAddVoucher()
    {
        if (!Module::isEnabled('quantitydiscountpro')) {
            return parent::ajaxProcessAddVoucher();
        }
        if ($this->access('edit')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            $errors = array();
            if (($id_cart_rule = Tools::getValue('id_cart_rule')) && substr($id_cart_rule, 0, 4) === 'QDP~') {
                $quantityDiscount = new quantityDiscountRule(str_replace('QDP~', '', $id_cart_rule));
                if (Validate::isLoadedObject($quantityDiscount)) {
                    if (!$quantityDiscount->validateQuantityDiscountRuleConditions()) {
                        $errors[] = Tools::displayError('Can\'t add the voucher3.');
                    } elseif ($quantityDiscount->calculateCartRule($quantityDiscount) !== true) {
                        $errors[] = Tools::displayError('Can\'t add the voucher4.');
                    }
                } else {
                    $errors[] = 'Error loading cart rule.';
                }
                echo json_encode(array_merge($this->ajaxReturnVars(), array('errors' => $errors)));
                return;
            } elseif (!($id_cart_rule = Tools::getValue('id_cart_rule')) || !$cart_rule = new CartRule((int)$id_cart_rule)) {
                $errors[] = Tools::displayError('Invalid voucher.');
            } elseif ($err = $cart_rule->checkValidity($this->context)) {
                $errors[] = $err;
            }
            if (!count($errors)) {
                if (!$this->context->cart->addCartRule((int)$cart_rule->id)) {
                    $errors[] = Tools::displayError('Can\'t add the voucher.');
                }
            }
            echo json_encode(array_merge($this->ajaxReturnVars(), array('errors' => $errors)));
        }
    }
}
