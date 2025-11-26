<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author ADDIS Network <info@addis.es>
*  @copyright  2021-2021 ADDIS Network
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminMarcarbonoController extends AdminController {

    public function __construct() {
        $this->bootstrap = true;
        $this->table = 'marcarbono';
        $this->className = 'Marcarbono';
        $this->lang = false;
        $this->deleted = true;

        $this->_defaultOrderBy = 'id_marcarbono'; 
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->fields_list = [
            'id_marcarbono' => [
                'title' => $this->trans('ID', [], 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'id_cart' => [
                'title' => $this->trans('Carrito', [], 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'callback' => 'viewCart',
                'remove_onclick' => true,
            ],
            'id_order' => [
                'title' => $this->trans('Pedido', [], 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'callback' => 'viewOrder',
                'remove_onclick' => true,
            ],
            'id_cart_rule' => [
                'title' => $this->trans('Regla de carrito', [], 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'callback' => 'viewCartRule',
                'remove_onclick' => true,
            ],
            'bono' => [
                'title' => $this->trans('Bono', [], 'Admin.Global'),
                'align' => 'text-left',
            ],
            'codigo_verificacion' => [
                'title' => $this->trans('Código verificación', [], 'Admin.Global'),
                'align' => 'text-left',
            ],
            'operacion' => [
                'title' => $this->trans('Operación', [], 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'callback' => 'displayOperacion',
            ],
            'importe_venta' => [
                'title' => $this->trans('Importe venta', [], 'Admin.Global'),
                'align' => 'text-right',
                'class' => 'fixed-width-xs',
                'orderby' => false,
                'search' => false,
                'callback' => 'displayPrice',
            ],
            'importe_inicial_tarjeta_regalo' => [
                'title' => $this->trans('Importe inicial tarjeta regalo', [], 'Admin.Global'),
                'align' => 'text-right',
                'class' => 'fixed-width-xs',
                'orderby' => false,
                'search' => false,
                'callback' => 'displayPrice',
            ],
            'origen' => [
                'title' => $this->trans('Origen', [], 'Admin.Global'),
                'align' => 'text-left',
            ],
        ];
        $this->bulk_actions = [];

        $this->allow_export = true;
    }

    public function viewCart($id_cart) {
        if ($id_cart) {
            return '<a target="_blank" href="'.$this->context->link->getAdminLink('AdminCarts', true, [], ['id_cart' => $id_cart, 'viewcart' => 1]).'">#'.$id_cart.'</a>';
        } else {
            return '--';
        }
    }

    public function viewOrder($id_order) {
        if ($id_order) {
            return '<a target="_blank" href="'.$this->context->link->getAdminLink('AdminOrders', true, [], ['id_order' => $id_order, 'vieworder' => 1]).'">#'.$id_order.'</a>';
        } else {
            return '--';
        }
    }

    public function viewCartRule($id_cart_rule) {
        if ($id_cart_rule) {
            return '<a target="_blank" href="'.$this->context->link->getAdminLink('AdminCartRules', true, [], ['id_cart_rule' => $id_cart_rule, 'updatecart_rule' => 1]).'">#'.$id_cart_rule.'</a>';
        } else {
            return '--';
        }
    }

    public function displayOperacion($operacion) {
        switch ($operacion) {
            case AlvarezERP::MARCAR_BONO_ANULAR:
                return 'Anular';
                break;
            case AlvarezERP::MARCAR_BONO_RECARGAR:
                return 'Recargar';
                break;
            case AlvarezERP::MARCAR_BONO_CONSUMIR:
                return 'Consumir';
                break;
            default:
                return $operacion;
        }
    }

    public function displayPrice($amount) {
        return Tools::displayPrice($amount);
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        return parent::renderList();
    }

    public function renderForm()
    {
        $obj = $this->loadObject(true);
        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Marcar bono', [], 'Admin.Global'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->trans('Carrito', [], 'Admin.Global'),
                    'name' => 'id_cart',
                    'id' => 'id_cart',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Pedido', [], 'Admin.Global'),
                    'name' => 'id_order',
                    'id' => 'id_order',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Regla de carrito', [], 'Admin.Global'),
                    'name' => 'id_cart_rule',
                    'id' => 'id_cart_rule',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Bono', [], 'Admin.Global'),
                    'name' => 'bono',
                    'id' => 'bono',
                    'required' => true,
                    'class' => 'fixed-width-lg',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Código verificación', [], 'Admin.Global'),
                    'name' => 'codigo_verificacion',
                    'id' => 'codigo_verificacion',
                    'required' => true,
                    'class' => 'fixed-width-lg',
                ],
                [
                    'type' => 'select',
                    'label' => $this->trans('Operación', [], 'Admin.Global'),
                    'name' => 'operacion',
                    'id' => 'operacion',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'options' => [
                        'query' => [
                            0 => [
                                'operacion_id' => AlvarezERP::MARCAR_BONO_ANULAR,
                                'operacion_name' => 'Anular',
                            ],
                            1 => [
                                'operacion_id' => AlvarezERP::MARCAR_BONO_RECARGAR,
                                'operacion_name' => 'Recargar',
                            ],
                            2 => [
                                'operacion_id' => AlvarezERP::MARCAR_BONO_CONSUMIR,
                                'operacion_name' => 'Consumir',
                            ],
                        ],
                        'id' => 'operacion_id',
                        'name' => 'operacion_name',
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Importe venta', [], 'Admin.Global'),
                    'name' => 'importe_venta',
                    'id' => 'importe_venta',
                    'required' => true,
                    'class' => 'fixed-width-md',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Importe inicial tarjeta regalo', [], 'Admin.Global'),
                    'name' => 'importe_inicial_tarjeta_regalo',
                    'id' => 'importe_inicial_tarjeta_regalo',
                    'required' => true,
                    'class' => 'fixed-width-md',
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Origen', [], 'Admin.Global'),
                    'name' => 'origen',
                    'id' => 'origen',
                    'required' => true,
                    'class' => 'fixed-width-lg',
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->trans('ERP Respuesta', [], 'Admin.Global'),
                    'name' => 'erp_response',
                    'id' => 'erp_response',
                ],
            ],
        ];

        $this->fields_form['submit'] = [];

        return parent::renderForm();
    }

}
