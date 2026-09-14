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
        OrderPreviewShippingDetails::class,
        OrderPreview::class,
        'getInvoiceAddressFormatted()'
    ),
    E_USER_DEPRECATED
);

/**
 * DTO for order invoice details
 *
 * @deprecated Since 1.7.7.5 and will be removed in the next major.
 */
class OrderPreviewInvoiceDetails
{
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
    private $address1;

    /**
     * @var string
     */
    private $address2;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $company;

    /**
     * @var string|null
     */
    private $vatNumber;

    /**
     * @var string
     */
    private $postalCode;

    /**
     * @var string|null
     */
    private $stateName;

    /**
     * @var string|null
     */
    private $dni;

    /**
     * InvoiceDetails constructor.
     */
    public function __construct(
        string $firstName,
        string $lastName,
        ?string $company,
        ?string $vatNumber,
        string $address1,
        string $address2,
        string $city,
        string $postalCode,
        ?string $stateName,
        string $country,
        ?string $email,
        string $phone,
        ?string $dni = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->city = $city;
        $this->country = $country;
        $this->email = $email;
        $this->phone = $phone;
        $this->company = $company;
        $this->vatNumber = $vatNumber;
        $this->postalCode = $postalCode;
        $this->stateName = $stateName;
        $this->dni = $dni;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getAddress1(): string
    {
        return $this->address1;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getAddress2(): string
    {
        return $this->address2;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getStateName(): ?string
    {
        return $this->stateName;
    }

    public function getDNI(): ?string
    {
        return $this->dni;
    }
}
