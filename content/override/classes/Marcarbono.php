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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Marcarbono extends ObjectModel {

    /**
     * public properties
     */
    public $id;
    public $id_cart;
    public $id_order;
    public $id_cart_rule;
    public $bono;
    public $codigo_verificacion;
    public $operacion;
    public $importe_venta;
    public $importe_inicial_tarjeta_regalo;
    public $origen;
    public $erp_response;
    public $deleted;
    public $date_add;
    public $date_upd;


    public static $definition = [
        'table' => 'marcarbono',
        'primary' => 'id_marcarbono',
        'multilang' => false,
        'multishop' => false,
        'fields' => [
            'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_cart_rule' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'bono' => ['type' => self::TYPE_STRING, 'size' => 255, 'required' => true],
            'codigo_verificacion' => ['type' => self::TYPE_STRING, 'size' => 255, 'required' => true],
            'operacion' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'importe_venta' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'importe_inicial_tarjeta_regalo' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'origen' => ['type' => self::TYPE_STRING, 'size' => 255, 'required' => true],
            'erp_response' => ['type' => self::TYPE_HTML],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function add($auto_date = true, $null_values = false) {
        parent:: add($auto_date, $null_values);

        if (strtolower(trim($this->erp_response)) != 'ok') {
            // enviar email al webmaster para indicar que ha fallado el marcar bono del ERP
            $data = [
                '{bono}' => $this->bono,
                '{codigo_verificacion}' => $this->codigo_verificacion,
                '{operacion}' => $this->operacion,
                '{importe_venta}' => Tools::displayPrice($this->importe_venta),
                '{importe_inicial_tarjeta_regalo}' => Tools::displayPrice($this->importe_inicial_tarjeta_regalo),
                '{origen}' => $this->origen,
                '{id_order}' => $this->id_order,
                '{response_erp}' => $this->erp_response,
            ];

            Mail::Send(
                (int) Context::getContext()->language->id,
                'marcarbono_ko',
                'Error al marcar el bono '.$this->bono,
                $data,
                Configuration::get('PS_SHOP_EMAIL'),
                null,
                Configuration::get('PS_SHOP_EMAIL'),
                Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                _PS_MAIL_DIR_,
                false,
                (int) Context::getContext()->shop->id
            );
        }
    }

    public function delete() {
        $this->deleted = 1;
        return parent::save();
    }

}
