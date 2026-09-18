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

namespace PrestaShop\PrestaShop\Core\Domain\Order\QueryResult;

@trigger_error(
    sprintf(
        '%s is deprecated since version 1.7.7.5 and will be removed in the next major version. Use %s::%s instead.',
        OrderInvoiceAddressForViewing::class,
        OrderForViewing::class,
        'getShippingAddressFormatted()'
    ),
    E_USER_DEPRECATED
);

/**
 * @deprecated Since 1.7.7.5 and will be removed in the next major.
 */
class OrderShippingAddressForViewing
{
    /**
     * @var int
     */
    private $addressId;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $companyName;

    /**
     * @var string|null
     */
    private $vatNumber;

    /**
     * @var string
     */
    private $address1;

    /**
     * @var string
     */
    private $address2;

    /**
     * @var string
     */
    private $cityName;

    /**
     * @var string
     */
    private $stateName;

    /**
     * @var string
     */
    private $countryName;

    /**
     * @var string
     */
    private $postCode;

    /**
     * @var string
     */
    private $phoneNumber;

    /**
     * @var string
     */
    private $mobilePhoneNumber;

    /**
     * @var string|null
     */
    private $dni;

    /**
     * @param  string|null  $dni  If null the DNI is not required for the country, else string
     */
    public function __construct(
        int $addressId,
        string $firstName,
        string $lastName,
        string $companyName,
        string $address1,
        string $address2,
        string $stateName,
        string $cityName,
        string $countryName,
        string $postCode,
        string $phone,
        string $phoneMobile,
        ?string $vatNumber = null,
        ?string $dni = null
    ) {
        $this->addressId = $addressId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->companyName = $companyName;
        $this->vatNumber = $vatNumber;
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->stateName = $stateName;
        $this->cityName = $cityName;
        $this->countryName = $countryName;
        $this->postCode = $postCode;
        $this->phoneNumber = $phone;
        $this->mobilePhoneNumber = $phoneMobile;
        $this->dni = $dni;
    }

    public function getAddressId(): int
    {
        return $this->addressId;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function getAddress1(): string
    {
        return $this->address1;
    }

    public function getAddress2(): string
    {
        return $this->address2;
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    /**
     * If null the DNI is not required for the country, else string
     */
    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function getStateName(): string
    {
        return $this->stateName;
    }

    public function getCountryName(): string
    {
        return $this->countryName;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getMobilePhoneNumber(): string
    {
        return $this->mobilePhoneNumber;
    }
}
