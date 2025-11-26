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
use PrestaShop\PrestaShop\Core\Domain\Address\Exception\AddressException;

/**
 * Class AddressFormat.
 */
class AddressFormat extends AddressFormatCore
{
    /**
     * Generates the full address text.
     *
     * @param Address $address
     * @param array $patternRules A defined rules array to avoid some pattern
     * @param string $newLine A string containing the newLine format
     * @param string $separator A string containing the separator format
     * @param array $style
     *
     * @return string
     */
    public static function generateAddress(Address $address, $patternRules = [], $newLine = self::FORMAT_NEW_LINE, $separator = ' ', $style = [])
    {
        $addressFields = AddressFormat::getOrderedAddressFields($address->id_country);

        $addressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($address, $addressFields);
        if (strpos($address->alias, 'Mondial') !== false) {
            unset($addressFormatedValues["State:name"]);
        }

        $addressText = '';
        foreach ($addressFields as $line) {
            if (($patternsList = preg_split(self::_CLEANING_REGEX_, $line, -1, PREG_SPLIT_NO_EMPTY))) {
                $tmpText = '';
                foreach ($patternsList as $pattern) {
                    if ((!array_key_exists('avoid', $patternRules)) ||
                                (is_array($patternRules) && array_key_exists('avoid', $patternRules) && !in_array($pattern, $patternRules['avoid']))) {
                        $tmpText .= (isset($addressFormatedValues[$pattern]) && !empty($addressFormatedValues[$pattern])) ?
                                (((isset($style[$pattern])) ?
                                    (sprintf($style[$pattern], $addressFormatedValues[$pattern])) :
                                    $addressFormatedValues[$pattern]) . $separator) : '';
                    }
                }
                $tmpText = trim($tmpText);
                $addressText .= (!empty($tmpText)) ? $tmpText . $newLine : '';
            }
        }

        $addressText = preg_replace('/' . preg_quote($newLine, '/') . '$/i', '', $addressText);
        $addressText = rtrim($addressText, $separator);

        return $addressText;
    }
}