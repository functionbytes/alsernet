<?php

class IdentityController extends IdentityControllerCore
{
    public $auth = true;
    public $php_self = 'identity';
    public $ssl = true;
    public $passwordRequired = true;

    public function initContent()
    {
        parent::initContent();

        $context = Context::getContext();
        $customer = $context->customer;

        if (!$customer || !$customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        $fields = [
            'firstname'     => ['type' => 'text', 'required' => true],
            'lastname'      => ['type' => 'text', 'required' => true],
            'birthday'      => ['type' => 'date', 'required' => false],
            'password'      => ['type' => 'password', 'required' => false],
            'new_password'  => ['type' => 'password', 'required' => false],
        ];

        $customer_form_fields = [];

       
        foreach ($fields as $name => $options) {

            $translator = Context::getContext()->getTranslator();

            $isPassword = in_array($name, ['password', 'new_password']);
            $value = $isPassword ? '' : ($customer->{$name} ?? '');
            $customer_form_fields[] = [
                'name' => str_replace('_', '', $name),
                'value' => $value,
                'label' => Context::getContext()->getTranslator()->trans(
                    str_replace(' ', '', str_replace('_', ' ', trim($name))),
                    [],
                    'Shop.Customer.Labels',
                    Context::getContext()->language->locale
                ),
                'type' => $options['type'],
                'required' => $options['required'] ,
                'errors' => [],
            ];
        }
       
        $customer_sports_fields = array_map('trim', explode(',', $customer->sports ?? ''));
        $customer_sports_fields = array_map('strval', $customer_sports_fields);

        $this->context->smarty->assign([
            'customer_form_fields' => $customer_form_fields,
            'customer_sports_fields' => $customer_sports_fields,
            'customer_sports' => $customer->sports,
        ]);

        $this->setTemplate('customer/identity.tpl');
    }


}
