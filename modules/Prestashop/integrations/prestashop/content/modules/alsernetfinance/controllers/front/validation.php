<?php
/**
 * @since 1.0.0
 */
class AlsernetfinanceValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * Procesa la validación después del pago
     */
    public function postProcess()
    {
        // Verifica si el contexto es válido y si la opción de pago está disponible
        if (!$this->checkIfContextIsValid() || !$this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink('order', true));
        }

        // Obtén el cliente y valida si existe
        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink('order', true));
        }

        // Realiza la validación del pedido
        $this->module->validateOrder(
            (int)$this->context->cart->id,
            (int)Configuration::get('PS_OS_WAIT_FOR_FINANCE'),
            (float)$this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            array(),
            (int)$this->context->currency->id,
            false,
            $customer->secure_key
        );

        // Redirige a la página de confirmación de pedido
        Tools::redirect($this->context->link->getPageLink(
            'order-confirmation',
            true,
            null,
            array(
                'id_cart' => (int)$this->context->cart->id,
                'id_module' => (int)$this->module->id,
                'id_order' => (int)$this->module->currentOrder,
                'key' => $customer->secure_key,
            )
        ));
    }

    /**
     * Verifica si el contexto es válido
     */
    private function checkIfContextIsValid()
    {
        return Validate::isLoadedObject($this->context->cart)
            && Validate::isUnsignedId($this->context->cart->id_customer)
            && Validate::isUnsignedId($this->context->cart->id_address_delivery)
            && Validate::isUnsignedId($this->context->cart->id_address_invoice)
            && !$this->context->cart->isVirtualCart();
    }

    /**
     * Verifica si la opción de pago está disponible
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $paymentModules = Module::getPaymentModules();
        foreach ($paymentModules as $module) {
            if ($module['name'] === $this->module->name) {
                return true;
            }
        }
        return false;
    }
}
