<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (! defined('_PS_VERSION_')) {
    exit;
}

class Alsernetfinance extends PaymentModule
{
    protected $_html;

    const FINANCED_MIN_AMOUNT = 'FINANCED_MIN_AMOUNT';

    const FINANCED_PAYMENT_TEXT = 'FINANCED_PAYMENT_TEXT';

    const FINANCED_ADDITIONAL_INFO = 'FINANCED_ADDITIONAL_INFO';

    public $min_amount;

    public $min_amount_default = 200;

    public $payment_text;

    public $payment_text_default = 'Personalised financing -- Requires telephone contact';

    public $additional_info;

    public $additional_info_default = 'We will call you to arrange the financing that best suits your needs';

    public function __construct()
    {
        $this->name = 'alsernetfinance';
        $this->tab = 'payment_gateways';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 0;
        $this->displayName = $this->l('Financed Payment');
        $this->description = $this->l('Financed Payment');
        $this->bootstrap = true;
        $this->min_amount = Configuration::get(self::FINANCED_MIN_AMOUNT);
        $this->payment_text_default = $this->l('Personalised financing -- Requires telephone contact');
        $this->additional_info_default = $this->l('We will call you to arrange the financing that best suits your needs');

        $this->payment_text = $this->trans(Configuration::get(self::FINANCED_PAYMENT_TEXT));
        $this->additional_info = $this->trans(Configuration::get(self::FINANCED_ADDITIONAL_INFO));

        parent::__construct();
    }

    protected function translateUserInput($input)
    {

        return $this->trans($input);
    }

    public function install()
    {

        Configuration::updateValue(self::FINANCED_MIN_AMOUNT, $this->min_amount) &&
        Configuration::updateValue(self::FINANCED_PAYMENT_TEXT, $this->payment_text_default) &&
        Configuration::updateValue(self::FINANCED_ADDITIONAL_INFO, $this->additional_info_default);

        if (
            ! parent::install() ||
            ! $this->registerHook('paymentOptions')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        return
            Configuration::deleteByName(self::FINANCED_MIN_AMOUNT) &&
            Configuration::deleteByName(self::FINANCED_PAYMENT_TEXT) &&
            Configuration::deleteByName(self::FINANCED_ADDITIONAL_INFO);

        return true;
    }

    public function hookDisplayHeader($params)
    {
        return $this->display(__FILE__, 'views/templates/hook/displayHeader.tpl');
    }

    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (Tools::getValue(self::FINANCED_MIN_AMOUNT)) {
                if (! is_numeric(Tools::getValue(self::FINANCED_MIN_AMOUNT))) {
                    $this->_postErrors[] = $this->trans('La cantidad mínima permitida para el pago financiado debe ser un valor numérico', [], 'modules.Alsernetfinance.Admin');
                }
            }
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (! count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue(self::FINANCED_MIN_AMOUNT, Tools::getValue(self::FINANCED_MIN_AMOUNT));
            Configuration::updateValue(self::FINANCED_PAYMENT_TEXT, $this->translateUserInput(Tools::getValue(self::FINANCED_PAYMENT_TEXT)));
            Configuration::updateValue(self::FINANCED_ADDITIONAL_INFO, $this->translateUserInput(Tools::getValue(self::FINANCED_ADDITIONAL_INFO)));
        }

        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Global'));
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans(
                        'Personalización',
                        [],
                        'modules.Alsernetfinance.Admin'
                    ),
                    'icon' => 'fa-duotone icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Precio mínimo en carrito para el pago financiado', [], 'modules.Alsernetfinance.Admin'),
                        'name' => self::FINANCED_MIN_AMOUNT,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Nombre del tipo de pago', [], 'modules.Alsernetfinance.Admin'),
                        'name' => self::FINANCED_PAYMENT_TEXT,
                        'desc' => $this->trans('Nombre del tipo de pago', [], 'modules.Alsernetfinance.Admin'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Información adicional', [], 'modules.Alsernetfinance.Admin'),
                        'name' => self::FINANCED_ADDITIONAL_INFO,
                        'desc' => $this->trans('Información adicional', [], 'modules.Alsernetfinance.Admin'),
                        'required' => true,
                    ],

                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],

            ],
        ];

        $helper = new HelperForm;
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='
            .$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $configFieldsValues = [];
        $configFieldsValues[self::FINANCED_MIN_AMOUNT] = Tools::getValue(self::FINANCED_MIN_AMOUNT, $this->min_amount);
        $configFieldsValues[self::FINANCED_PAYMENT_TEXT] = Tools::getValue(self::FINANCED_PAYMENT_TEXT, $this->trans(Configuration::get(self::FINANCED_PAYMENT_TEXT)));
        $configFieldsValues['FINANCED_ADDITIONAL_INFO'] = Tools::getValue(self::FINANCED_ADDITIONAL_INFO, $this->trans(Configuration::get(self::FINANCED_ADDITIONAL_INFO)));

        return $configFieldsValues;
    }

    public function getTemplateVars()
    {
        return [
            'shop_name' => $this->context->shop->name,
            'custom_var' => $this->l('My custom var value'),
            'payment_details' => $this->l('custom details'),
        ];
    }

    public function hookPaymentOptions($params)
    {
        if (! $this->active) {
            return;
        }

        $cart = $this->context->cart;
        $delivery_address = new Address($cart->id_address_delivery);
        $country = new Country($delivery_address->id_country);

        if (in_array($country->id, [6, 242, 243, 244])) {
            $total_cart = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $min_amount_in_cart = $this->min_amount_default;

            if (is_numeric($this->min_amount)) {
                $min_amount_in_cart = (int) $this->min_amount;
            }

            if ($total_cart < $min_amount_in_cart) {
                return;
            }
        } else {
            return;
        }

        $paymentOptions = [];

        $payment_text = ! empty($this->payment_text) ? $this->payment_text : $this->payment_text_default;
        $additional_info = ! empty($this->additional_info) ? $this->additional_info : $this->additional_info_default;

        $standardPayment = new PaymentOption;
        $standardPayment->setModuleName($this->name)
            ->setCallToActionText($payment_text)
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            ->setAdditionalInformation($additional_info);

        $paymentOptions[] = $standardPayment;

        return $paymentOptions;
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (! $this->active) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVars()
        );

        return
            $this->l('Your order on is complete.').'<br><br>'.
            $this->l('You have chosen the customized financing payment method').'<br><br><span>'.
            $this->l('We will call you to arrange the financed payment').'<br><br>'.
            $this->l('Your order will be sent soon.').'</span><br><br>'.
            $this->l('For any questions or for further information, please contact our customer support');
    }
}
