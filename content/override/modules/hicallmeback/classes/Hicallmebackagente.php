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

require_once dirname(__FILE__).'/../hicallmeback.php';

class Hicallmebackagente extends ObjectModel {

    /**
     * public properties
     */
    public $id;
    public $name;
    public $email;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'hicallmebackagente',
        'primary' => 'id_hicallmebackagente',
        'multilang' => false,
        'multishop' => false,
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'size' => 255],
            'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function delete() {
        $module = new HICallMeBackOverride();

        $email_old = self::getEmailFromDb($this->id);
        if ($email_old != $this->email) {
            if ($module->hasEmailInNotificationList($email_old)) {
                $module->changeEmailNotificationList($email_old, 1);
            }
        }

        $module->changeEmailNotificationList($this->email, 1);

        return parent::delete();
    }

    public function update($null_values = false) {
        $module = new HICallMeBackOverride();

        $email_old = self::getEmailFromDb($this->id);
        if ($email_old != $this->email) {
            if ($module->hasEmailInNotificationList($email_old)) {
                $module->changeEmailNotificationList($this->email, 2, $email_old);
            }
        }
        
        if ($this->active) {
            if (!$module->hasEmailInNotificationList($this->email)) {
                $module->changeEmailNotificationList($this->email, 0);
            }
        } else {
            if ($module->hasEmailInNotificationList($this->email)) {
                $module->changeEmailNotificationList($this->email, 1);
            }
        }

        return parent::update($null_values);
    }

    public static function getEmailFromDb($id_hicallmebackagente) {
        $sql = 'SELECT `email` FROM `'._DB_PREFIX_.'hicallmebackagente` WHERE `id_hicallmebackagente`='.(int) $id_hicallmebackagente;
        return DB::getInstance()->getValue($sql);
    }

    public static function getActiveFromDb($id_hicallmebackagente) {
        $sql = 'SELECT `active` FROM `'._DB_PREFIX_.'hicallmebackagente` WHERE `id_hicallmebackagente`='.(int) $id_hicallmebackagente;
        return DB::getInstance()->getValue($sql);
    }

}
