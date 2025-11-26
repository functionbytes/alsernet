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

/**
 * Class AddressCore.
 */
class Address extends AddressCore
{
    /** @var int Customer ID which address belongs to */
    public $id_customer = null;

    /** @var int Manufacturer ID which address belongs to */
    public $id_manufacturer = null;

    /** @var int Supplier ID which address belongs to */
    public $id_supplier = null;

    /**
     * @since 1.5.0
     *
     * @var int Warehouse ID which address belongs to
     */
    public $id_warehouse = null;

    /** @var int Country ID */
    public $id_country;

    /** @var int State ID */
    public $id_state;

    /** @var string Country name */
    public $country;

    /** @var string Alias (eg. Home, Work...) */
    public $alias;

    /** @var string Company (optional) */
    public $company;

    /** @var string Lastname */
    public $lastname;

    /** @var string Firstname */
    public $firstname;

    /** @var string Address first line */
    public $address1;

    /** @var string Address second line (optional) */
    public $address2;

    /** @var string Postal code */
    public $postcode;

    /** @var string City */
    public $city;

    /** @var string Any other useful information */
    public $other;

    /** @var string Phone number */
    public $phone;

    /** @var string Mobile phone number */
    public $phone_mobile;

    /** @var string VAT number */
    public $vat_number;

    /** @var string DNI number */
    public $dni;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /** @var string Object last modification date */
    public $default;

    /** @var string Object last modification date */
    public $active;

    /** @var bool True if address has been deleted (staying in database as deleted) */
    public $deleted = 0;

    /** @var array Zone IDs cache */
    protected static $_idZones = [];
    /** @var array Country IDs cache */
    protected static $_idCountries = [];

    public static $definition = [
        'table' => 'address',
        'primary' => 'id_address',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'id_warehouse' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_state' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'alias' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 64],
            'company' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255],
            'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255],
            'vat_number' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false],
            'address1' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128],
            'address2' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128],
            'postcode' => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12],
            'city' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 64],
            'other' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => 300],
            'phone' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
            'phone_mobile' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
            'dni' => ['type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'size' => 16],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'default' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
        ],
    ];

    CONST ADDRESS_ALIAS_DEMODAYS = 'Dirección Demo Day';

    /**
     * Get the first address id of the customer.
     *
     * NOTA: Función DESACTIVADA para evitar asignación automática de direcciones
     * al eliminar una dirección. Ahora retorna false para que el sistema no
     * asigne automáticamente la primera dirección disponible.
     *
     * @param int $id_customer Customer id
     * @param bool $active Active addresses only
     *
     * @return bool|int|null
     */
    public static function getFirstCustomerAddressId($id_customer, $active = true)
    {
        // DESACTIVADO: Evitar asignación automática de primera dirección
        // El usuario debe seleccionar manualmente sus direcciones
        return false;

        /* CÓDIGO ORIGINAL COMENTADO - NO ELIMINAR
        if (!$id_customer) {
            return false;
        }
        $cache_id = 'Address::getFirstCustomerAddressId_' . (int) $id_customer . '-' . (bool) $active;
        if (!Cache::isStored($cache_id)) {
            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
				SELECT `id_address`
				FROM `' . _DB_PREFIX_ . 'address`
				WHERE `id_customer` = ' . (int) $id_customer . ' AND `deleted` = 0' . ($active ? ' AND `active` = 1' : '') . ' AND `alias` != \''.self::ADDRESS_ALIAS_DEMODAYS.'\''
            );
            Cache::store($cache_id, $result);

            return $result;
        }

        return Cache::retrieve($cache_id);
        */
    }
}
