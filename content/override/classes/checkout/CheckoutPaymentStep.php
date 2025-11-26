<?php

use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;

class CheckoutPaymentStep extends CheckoutPaymentStepCore
{

    public $selected_payment_option = null;

    public function render(array $extraParams = [])
    {

        $presenter = new CartPresenter();
        $cart = $presenter->present($this->getCheckoutSession()->getCart(), $shouldSeparateGifts = true, $this->context->language->id);

        $isFree = 0 == (float)$this->getCheckoutSession()->getCart()->getOrderTotal(true, Cart::BOTH);
        $paymentOptions = $this->paymentOptionsFinder->present($isFree);
        $conditionsToApprove = $this->conditionsToApproveFinder->getConditionsToApproveForTemplate();
        $deliveryOptions = $this->getCheckoutSession()->getDeliveryOptions();
        $deliveryOptionKey = $this->getCheckoutSession()->getSelectedDeliveryOption();


        if (isset($deliveryOptions[$deliveryOptionKey])) {
            $selectedDeliveryOption = $deliveryOptions[$deliveryOptionKey];
        } else {
            $selectedDeliveryOption = 0;
        }

        if (isset($this->selected_payment_option)) {
            $selected_payment_option = $this->selected_payment_option;
        } else {
            $selected_payment_option = NULL;
        }
        unset($selectedDeliveryOption['product_list']);

        $allPaymentOptions = [];

        if (!empty($paymentOptions)) {
            $flattened = array_values($paymentOptions);
            $flattened = array_filter($flattened, 'is_array');
            if (!empty($flattened)) {
                $allPaymentOptions = array_merge(...$flattened);
            }
        }

        $totalCart = $cart['totals']['total']['amount'];
        $iso = strtolower($this->context->language->iso_code);
        $lottery = $cart['lottery'];
        $card = $cart['card'];
        $armero = $cart['armero'];
        $cartucho = $cart['cartucho'];
        $armas = $cart['armas'];
        $armas_balines = $cart['armas_balines'];
        $licencia = $cart['licencia'];

        $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
            ['module_name' => 'AddisDemoday']
        ], 'remove');

        if ($armas_balines) {
            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'credit_card'],
                ['module_name' => 'local_payment_hipay'],
                ['module_name' => 'klarnapayment']

            ], 'remove');
        }

        if ($armas) {
            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'paypal'],
                ['module_name' => 'paypal_bnpl'],
                ['module_name' => 'ps_cashondelivery'],
                ['module_name' => 'credit_card'],
                ['module_name' => 'local_payment_hipay'],
                ['module_name' => 'klarnapayment']
            ], 'remove');
        }

        if ($armero) {
            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'paypal'],
                ['module_name' => 'paypal_bnpl'],
                ['module_name' => 'credit_card'],
                ['module_name' => 'local_payment_hipay']
            ], 'remove');
        }
        if ($cartucho) {
            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'ps_cashondelivery'],
                ['module_name' => 'credit_card'],
                ['module_name' => 'local_payment_hipay'],
                ['module_name' => 'klarnapayment']
            ], 'remove');
        }
        if ($licencia) {
            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'ps_cashondelivery'],
                ['module_name' => 'banlendismart'],
                ['module_name' => 'credit_card'],
                ['module_name' => 'sequra'],
                ['module_name' => 'paypal'],
                ['module_name' => 'paypal_bnpl'],
                ['module_name' => 'local_payment_hipay'],
                ['module_name' => 'klarnapayment']
            ], 'remove');
        }
        if ($lottery || $card) {

            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'ps_cashondelivery'],
                ['module_name' => 'banlendismart'],
                ['module_name' => 'sequra'],
                ['module_name' => 'paypal'],
                ['module_name' => 'paypal_bnpl'],
                ['module_name' => 'local_payment_hipay'],
                ['module_name' => 'klarnapayment']
            ], 'remove');
        }


        if ($iso == 'es') {

            if ($totalCart < 300) {

                $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'banlendismart']
                ], 'remove');


            } elseif ($totalCart > 300) {

                $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'sequra']
                ], 'remove');

            } else {
                $filteredOptions = $allPaymentOptions;
            }
        }
        elseif ($iso == 'pt') {

            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'banlendismart'],
                //['module_name' => 'ps_cashondelivery']
            ], 'remove');

            if ($totalCart > 3000) {

                $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'sequra']
                ], 'remove');

            } else {
                $filteredOptions = $allPaymentOptions;
            }

        }
        elseif ($iso == 'fr') {

            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'banlendismart'],
                //['module_name' => 'ps_cashondelivery']
            ], 'remove');

            if ($totalCart > 3000) {

                $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'sequra']
                ], 'remove');

            } else {
                $filteredOptions = $allPaymentOptions;
            }

        }elseif ($iso == 'it') {

            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'banlendismart'],
                //['module_name' => 'ps_cashondelivery']
            ], 'remove');

            if ($totalCart > 3000) {

                $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'sequra']
                ], 'remove');

            } else {
                $filteredOptions = $allPaymentOptions;
            }

        } else {

            $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'banlendismart'],
                ['module_name' => 'sequra'],
                ['module_name' => 'ps_cashondelivery'],
                //['module_name' => 'inespay'],
            ], 'remove');

        }

        $assignedVars = [
            'is_free' => $isFree,
            'payment_options' => $filteredOptions,
            'need_invoice' => $this->context->cart->need_invoice,
            'conditions_to_approve' => $conditionsToApprove,
            'selected_payment_option' => $selected_payment_option,
            'selected_delivery_option' => $selectedDeliveryOption,
            'show_final_summary' => Configuration::get('PS_FINAL_SUMMARY_ENABLED'),
        ];

        return $this->renderTemplate($this->getTemplate(), $extraParams, $assignedVars);
    }


    function filterPaymentOptions(array $options, array $conditions): array
    {
        return array_filter($options, function ($option) use ($conditions) {
            foreach ($conditions as $condition) {
                $isValid = true;
                foreach ($condition as $key => $value) {

                    if (is_array($value)) {
                        if (!isset($option[$key]) || !in_array($option[$key], $value)) {
                            $isValid = false;
                            break;
                        }
                    } else {

                        if (!isset($option[$key]) || $option[$key] !== $value) {
                            $isValid = false;
                            break;
                        }
                    }
                }

                if ($isValid) {
                    return true;
                }
            }
            return false;
        });
    }

    function removePaymentOptions(array $options, array $conditions): array
    {
        return array_filter($options, function ($option) use ($conditions) {
            foreach ($conditions as $condition) {
                $isValid = true;
                foreach ($condition as $key => $value) {

                    if (is_array($value)) {
                        if (!isset($option[$key]) || !in_array($option[$key], $value)) {
                            $isValid = false;
                            break;
                        }
                    } else {

                        if (!isset($option[$key]) || $option[$key] !== $value) {
                            $isValid = false;
                            break;
                        }
                    }
                }

                if ($isValid) {
                    return false;
                }
            }
            // Si no cumple con ninguna condición, mantener la opción
            return true;
        });
    }

    function processPaymentOptions(array $options, array $conditions, string $action = 'filter'): array
    {

        if (!in_array($action, ['filter', 'remove'])) {
            throw new InvalidArgumentException('El valor de acción debe ser "filter" o "remove".');
        }

        return array_filter($options, function ($option) use ($conditions, $action) {
            foreach ($conditions as $condition) {
                $isValid = true;
                foreach ($condition as $key => $value) {

                    if (is_array($value)) {
                        if (!isset($option[$key]) || !in_array($option[$key], $value)) {
                            $isValid = false;
                            break;
                        }
                    } else {
                        // Validar si el campo coincide exactamente con el valor
                        if (!isset($option[$key]) || $option[$key] !== $value) {
                            $isValid = false;
                            break;
                        }
                    }
                }

                if ($isValid) {
                    return $action === 'filter';
                }
            }

            return $action === 'remove';
        });
    }


}