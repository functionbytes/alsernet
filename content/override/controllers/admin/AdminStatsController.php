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

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

class AdminStatsController extends AdminStatsControllerCore {


    public static function getVisits($unique, $date_from, $date_to, $granularity = false)
    {
        $visits = ($granularity == false) ? 0 : [];
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        /** @var Gapi $gapi */
        $gapi = false; // $moduleManager->isInstalled('gapi') ? Module::getInstanceByName('gapi') : false;
        if (Validate::isLoadedObject($gapi) && $gapi->isConfigured()) {
            $metric = $unique ? 'visitors' : 'visits';
            if ($result = $gapi->requestReportData(
                $granularity ? 'ga:date' : '',
                'ga:' . $metric,
                $date_from,
                $date_to,
                null,
                null,
                1,
                5000
            )
            ) {
                foreach ($result as $row) {
                    if ($granularity == 'day') {
                        $visits[strtotime(
                            preg_replace('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', '$1-$2-$3', $row['dimensions']['date'])
                        )] = $row['metrics'][$metric];
                    } elseif ($granularity == 'month') {
                        if (!isset(
                            $visits[strtotime(
                                preg_replace(
                                    '/^([0-9]{4})([0-9]{2})([0-9]{2})$/',
                                    '$1-$2-01',
                                    $row['dimensions']['date']
                                )
                            )]
                        )
                        ) {
                            $visits[strtotime(
                                preg_replace(
                                    '/^([0-9]{4})([0-9]{2})([0-9]{2})$/',
                                    '$1-$2-01',
                                    $row['dimensions']['date']
                                )
                            )] = 0;
                        }
                        $visits[strtotime(
                            preg_replace('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', '$1-$2-01', $row['dimensions']['date'])
                        )] += $row['metrics'][$metric];
                    } else {
                        $visits = $row['metrics'][$metric];
                    }
                }
            }
        } else {
            if ($granularity == 'day') {
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    '
				SELECT date(`date_add`) as date, COUNT(' . ($unique ? 'DISTINCT id_guest' : '*') . ') as visits
				FROM `' . _DB_PREFIX_ . 'connections`
				WHERE `date_add` BETWEEN "' . pSQL($date_from) . ' 00:00:00" AND "' . pSQL($date_to) . ' 23:59:59"
				' . Shop::addSqlRestriction() . '
				GROUP BY date(`date_add`)'
                );
                foreach ($result as $row) {
                    $visits[strtotime($row['date'])] = $row['visits'];
                }
            } elseif ($granularity == 'month') {
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    '
				SELECT LEFT(LAST_DAY(`date_add`), 7) as date, COUNT(' . ($unique ? 'DISTINCT id_guest' : '*') . ') as visits
				FROM `' . _DB_PREFIX_ . 'connections`
				WHERE `date_add` BETWEEN "' . pSQL($date_from) . ' 00:00:00" AND "' . pSQL($date_to) . ' 23:59:59"
				' . Shop::addSqlRestriction() . '
				GROUP BY LAST_DAY(`date_add`)'
                );
                foreach ($result as $row) {
                    $visits[strtotime($row['date'] . '-01')] = $row['visits'];
                }
            } else {
                $visits = 0;
		/*Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    '
				SELECT COUNT(' . ($unique ? 'DISTINCT id_guest' : '*') . ') as visits
				FROM `' . _DB_PREFIX_ . 'connections`
				WHERE `date_add` BETWEEN "' . pSQL($date_from) . ' 00:00:00" AND "' . pSQL($date_to) . ' 23:59:59"
				' . Shop::addSqlRestriction()
                );*/
            }
        }

        return $visits;
    }

}
