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
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class CheckoutDeliveryStep extends CheckoutDeliveryStepCore
{
   

    public function render(array $extraParams = [])
    {
        $deliveryAddressPatternRules = json_decode(Configuration::get('PS_INVCE_DELIVERY_ADDR_RULES'), true);
        $delivery_address = new Address($this->getCheckoutSession()->getIdAddressDelivery());
        $formatted_delivery_address = AddressFormat::generateAddress($delivery_address, $deliveryAddressPatternRules, '<br />', ' ');

        return $this->renderTemplate(
            $this->getTemplate(),
            $extraParams,
            [
                'hookDisplayBeforeCarrier' => Hook::exec('displayBeforeCarrier', ['cart' => $this->getCheckoutSession()->getCart()]),
                'hookDisplayAfterCarrier' => Hook::exec('displayAfterCarrier', ['cart' => $this->getCheckoutSession()->getCart()]),
                'id_address' => $this->getCheckoutSession()->getIdAddressDelivery(),
                'delivery_options' => $this->getCheckoutSession()->getDeliveryOptions(),
                'delivery_option' => $this->getCheckoutSession()->getSelectedDeliveryOption(),
                'recyclable' => $this->getCheckoutSession()->isRecyclable(),
                'recyclablePackAllowed' => $this->isRecyclablePackAllowed(),
                'delivery_message' => $this->getCheckoutSession()->getMessage(),
                'gift' => [
                    'allowed' => $this->isGiftAllowed(),
                    'isGift' => $this->getCheckoutSession()->getGift()['isGift'],
                    'label' => $this->getTranslator()->trans(
                        'I would like my order to be gift wrapped %cost%',
                        ['%cost%' => $this->getGiftCostForLabel()],
                        'Shop.Theme.Checkout'
                    ),
                    'message' => $this->getCheckoutSession()->getGift()['message'],
                ],
                'formatted_delivery_address' => $formatted_delivery_address,
            ]
        );
    }

    protected function isModuleComplete($requestParams)
    {
        $deliveryOptions = $this->getCheckoutSession()->getDeliveryOptions();
        $currentDeliveryOption = $deliveryOptions[$this->getCheckoutSession()->getSelectedDeliveryOption()];
        if (!$currentDeliveryOption['is_module']) {
            return true;
        }

        $isComplete = true;
        Hook::exec(
            'actionValidateStepComplete',
            [
                'step_name' => 'delivery',
                'request_params' => $requestParams,
                'completed' => &$isComplete,
            ],
            Module::getModuleIdByName($currentDeliveryOption['external_module_name'])
        );

        return $isComplete;
    }
}