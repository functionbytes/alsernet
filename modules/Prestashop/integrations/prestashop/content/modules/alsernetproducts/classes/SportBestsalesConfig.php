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

class SportBestsalesConfig extends ObjectModel {

    public $id_category_sport;

    public $list_days;
    public $list_limit;
    public $list_min_amount;
    public $list_max_amount;
    public $list_max_products_brand;
    public $list_max_products_category;
    public $list_exclude_id_product;
    public $list_exclude_feature_family;
    public $list_exclude_feature_subfamily;
    public $list_exclude_feature_group;
    public $list_exclude_id_category;
    public $list_include_id_product;
    
    public $home_days;
    public $home_limit;
    public $home_min_amount;
    public $home_max_amount;
    public $home_max_products_brand;
    public $home_max_products_category;
    public $home_exclude_id_product;
    public $home_exclude_feature_family;
    public $home_exclude_feature_subfamily;
    public $home_exclude_feature_group;
    public $home_exclude_id_category;
    public $home_include_id_product;

    public static $definition = array(
        'table' => 'sport_bestsales_config',
        'primary' => 'id_sport_bestsales_config',
        'multilang' => false,
        'multishop' => false,
        'fields' => array(
            'id_category_sport' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),

            'list_days' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'list_limit' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'list_min_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'list_max_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'list_max_products_brand' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'list_max_products_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'list_exclude_id_product' => ['type' => self::TYPE_STRING],
            'list_exclude_feature_family' => ['type' => self::TYPE_STRING],
            'list_exclude_feature_subfamily' => ['type' => self::TYPE_STRING],
            'list_exclude_feature_group' => ['type' => self::TYPE_STRING],
            'list_exclude_id_category' => ['type' => self::TYPE_STRING],
            'list_include_id_product' => ['type' => self::TYPE_STRING],

            'home_days' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'home_limit' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'home_min_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'home_max_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'home_max_products_brand' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'home_max_products_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'home_exclude_id_product' => ['type' => self::TYPE_STRING],
            'home_exclude_feature_family' => ['type' => self::TYPE_STRING],
            'home_exclude_feature_subfamily' => ['type' => self::TYPE_STRING],
            'home_exclude_feature_group' => ['type' => self::TYPE_STRING],
            'home_exclude_id_category' => ['type' => self::TYPE_STRING],
            'home_include_id_product' => ['type' => self::TYPE_STRING],
        ),
    );

    public static function getConfigByIdCategory($id_category_sport) {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'sport_bestsales_config` WHERE `id_category_sport`='.(int) pSQL($id_category_sport);
        $config = DB::getInstance()->getRow($sql);
        return $config;
    }

    public function convertJson() {
        $json = '{';
        $json .= '"'.self::$definition['primary'].'": '.$this->id.', ';
        foreach (self::$definition['fields'] as $key => $field) {
            if ($field['type'] == self::TYPE_INT || $field['type'] == self::TYPE_FLOAT) {
                $json .= '"'.$key.'": '.$this->{$key}.', ';
            } else {
                $json .= '"'.$key.'": "'.$this->{$key}.'", ';
            }
        }
        $json = substr($json, 0, -2);
        $json .= '}';
        return $json;
    }
}
