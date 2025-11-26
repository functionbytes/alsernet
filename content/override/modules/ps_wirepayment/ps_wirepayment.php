<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class Ps_WirepaymentOverride extends Ps_Wirepayment
{
    const BANK_WIRE_PAYMENT_MIN_AMOUNT = 'BANK_WIRE_PAYMENT_MIN_AMOUNT';

    public $min_amount;
    // public $min_amount_default = 150;

    /**
     * Rellenar propiedad cantidad mínima
     */
    public function __construct()
    {
        $this->min_amount = Configuration::get(self::BANK_WIRE_PAYMENT_MIN_AMOUNT);
        $this->min_amount_default = 150;
        parent::__construct();
    }

    /**
     * Definir configuración cantidad mínima
     */
    public function install()
    {
        Configuration::updateValue(self::BANK_WIRE_PAYMENT_MIN_AMOUNT, $this->$min_amount_default);
        return parent::install();
    }

    /**
     * Eliminar configuración cantidad mínima
     */
    public function uninstall()
    {
         return Configuration::deleteByName(self::BANK_WIRE_PAYMENT_MIN_AMOUNT) && parent::uninstall();
    }

    /**
     * Validar que la cantidad mínima indicada es numérica
     */
    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (Tools::getValue(self::BANK_WIRE_PAYMENT_MIN_AMOUNT)) {
                if (!is_numeric(Tools::getValue(self::BANK_WIRE_PAYMENT_MIN_AMOUNT))) {
                    $this->_postErrors[] = $this->trans('La cantidad mínima permitida en el carrito debe ser un valor numérico', [], 'Modules.Wirepayment.Admin');
                }
            }
        }
    }

    /**
     * Guardar la cantidad mínima
     */
    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue(self::BANK_WIRE_PAYMENT_MIN_AMOUNT, Tools::getValue(self::BANK_WIRE_PAYMENT_MIN_AMOUNT));
        }

        return parent::_postProcess();
    }

    /**
     * Mostrar cantidad mínima en la configuración del módulo
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Account details', [], 'Modules.Wirepayment.Admin'),
                    'icon' => 'icon-envelope',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Account owner', [], 'Modules.Wirepayment.Admin'),
                        'name' => 'BANK_WIRE_OWNER',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Account details', [], 'Modules.Wirepayment.Admin'),
                        'name' => 'BANK_WIRE_DETAILS',
                        'desc' => $this->trans('Such as bank branch, IBAN number, BIC, etc.', [], 'Modules.Wirepayment.Admin'),
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Bank address', [], 'Modules.Wirepayment.Admin'),
                        'name' => 'BANK_WIRE_ADDRESS',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
        $fields_form_customization = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Customization', [], 'Modules.Wirepayment.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Reservation period', [], 'Modules.Wirepayment.Admin'),
                        'desc' => $this->trans('Number of days the items remain reserved', [], 'Modules.Wirepayment.Admin'),
                        'name' => 'BANK_WIRE_RESERVATION_DAYS',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Information to the customer', [], 'Modules.Wirepayment.Admin'),
                        'name' => 'BANK_WIRE_CUSTOM_TEXT',
                        'desc' => $this->trans('Information on the bank transfer (processing time, starting of the shipping...)', [], 'Modules.Wirepayment.Admin'),
                        'lang' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Display the invitation to pay in the order confirmation page', [], 'Modules.Wirepayment.Admin'),
                        'name' => self::FLAG_DISPLAY_PAYMENT_INVITE,
                        'is_bool' => true,
                        'hint' => $this->trans('Your country\'s legislation may require you to send the invitation to pay by email only. Disabling the option will hide the invitation on the confirmation page.', [], 'Modules.Wirepayment.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Precio mínimo permitido en el carrito', [], 'Modules.Wirepayment.Admin'),
                        'name' => self::BANK_WIRE_PAYMENT_MIN_AMOUNT,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
            . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form, $fields_form_customization]);
    }

    /**
     * Recoger la cantidad mínima
     */
    public function getConfigFieldsValues()
    {
        $configFieldsValues = parent::getConfigFieldsValues();
        $configFieldsValues[self::BANK_WIRE_PAYMENT_MIN_AMOUNT] = Tools::getValue(self::BANK_WIRE_PAYMENT_MIN_AMOUNT, $this->min_amount);
        return $configFieldsValues;
    }

    /**
     * El pago mediante transferencia bancaria sólo debe estar disponible a partir de 150€ en el carrito
     */
    public function hookPaymentOptions($params)
    {
        $cart = $params['cart'];

        $min_amount_in_cart = $this->min_amount_default;
        if (is_numeric($this->min_amount)) {
            $min_amount_in_cart = (int) $this->min_amount;
        }

        $total_cart = $cart->getOrderTotal();
        if ($total_cart < $min_amount_in_cart) {
            return [];
        } else {
            return parent::hookPaymentOptions($params);
        }
    }

}
