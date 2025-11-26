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

/***
 * Class CustomerCore
 */
class Customer extends CustomerCore
{
    CONST ADDRESS_ALIAS_DEMODAYS = 'Dirección Demo Day';

    public $sports;

    public function logout()
    {
        parent::logout();
        session_start();
    }

    public function mylogout()
    {
        parent::mylogout();
        session_start();
    }

    public function getAddresses($idLang = null)
    {
        $addresses = parent::getAddresses($idLang);

        foreach($addresses as $key => $address) {
            if ($address['alias'] == self::ADDRESS_ALIAS_DEMODAYS) {
                unset($addresses[$key]);
            }
        }

        return $addresses;
    }

    public function getSimpleAddresses($idLang = null)
    {
        $addresses = parent::getSimpleAddresses($idLang);

        foreach($addresses as $key => $address) {
            if ($address['alias'] == self::ADDRESS_ALIAS_DEMODAYS) {
                unset($addresses[$key]);
            }
        }

        return $addresses;
    }


    public function getSimpleAddressSql($idAddress = null, $idLang = null)
    {
        $sql = parent::getSimpleAddressSql($idAddress, $idLang);

        $sql .= ' AND a.`alias` != \''.self::ADDRESS_ALIAS_DEMODAYS.'\' ';

        return $sql;
    }

    public static function getAddressesTotalById($idCustomer)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
            SELECT COUNT(`id_address`)
            FROM `' . _DB_PREFIX_ . 'address`
            WHERE `id_customer` = ' . (int) $idCustomer . ' AND `alias` != \''.self::ADDRESS_ALIAS_DEMODAYS.'\'
            AND `deleted` = 0'
        );
    }

    public function delete()
    {
        if (strpos($this->firstname, "fake-user")) {
            AddisLogger::log(__FILE__, __FUNCTION__, null, "Intento de borrado | Customer::delete | Fake customer");
            PrestaShopLogger::addLog("Intento de borrado | Customer::delete | Fake customer", false);
            return true;
        }else{
            return parent::delete();
        }
    }

}
