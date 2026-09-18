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
declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\Supplier\QueryResult;

use PrestaShop\PrestaShop\Core\Domain\Supplier\ValueObject\SupplierId;

/**
 * Transfers supplier data for editing
 */
class EditableSupplier
{
    /**
     * @var SupplierId
     */
    private $supplierId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $localizedDescriptions;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $address2;

    /**
     * @var int
     */
    private $countryId;

    /**
     * @var string
     */
    private $postCode;

    /**
     * @var int
     */
    private $stateId;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $mobilePhone;

    /**
     * @var array
     */
    private $logoImage;

    /**
     * @var string[]
     */
    private $localizedMetaTitles;

    /**
     * @var string[]
     */
    private $localizedMetaDescriptions;

    /**
     * @var string[]
     */
    private $localizedMetaKeywords;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var array
     */
    private $associatedShops;

    /**
     * @var string
     */
    private $dni;

    /**
     * @param  string[]  $localizedDescriptions
     * @param  string[]  $localizedMetaTitles
     * @param  string[]  $localizedMetaDescriptions
     * @param  string[]  $localizedMetaKeywords
     */
    public function __construct(
        SupplierId $supplierId,
        string $name,
        array $localizedDescriptions,
        string $address,
        string $city,
        string $address2,
        int $countryId,
        string $postCode,
        int $stateId,
        string $phone,
        string $mobilePhone,
        array $localizedMetaTitles,
        array $localizedMetaDescriptions,
        array $localizedMetaKeywords,
        bool $enabled,
        array $associatedShops,
        string $dni,
        ?array $logoImage = null
    ) {
        $this->supplierId = $supplierId;
        $this->name = $name;
        $this->localizedDescriptions = $localizedDescriptions;
        $this->address = $address;
        $this->city = $city;
        $this->address2 = $address2;
        $this->countryId = $countryId;
        $this->postCode = $postCode;
        $this->stateId = $stateId;
        $this->phone = $phone;
        $this->mobilePhone = $mobilePhone;
        $this->logoImage = $logoImage;
        $this->localizedMetaTitles = $localizedMetaTitles;
        $this->localizedMetaDescriptions = $localizedMetaDescriptions;
        $this->localizedMetaKeywords = $localizedMetaKeywords;
        $this->enabled = $enabled;
        $this->dni = $dni;
        $this->associatedShops = $associatedShops;
    }

    public function getSupplierId(): SupplierId
    {
        return $this->supplierId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getLocalizedDescriptions(): array
    {
        return $this->localizedDescriptions;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getAddress2(): string
    {
        return $this->address2;
    }

    public function getCountryId(): int
    {
        return $this->countryId;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function getStateId(): int
    {
        return $this->stateId;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getMobilePhone(): string
    {
        return $this->mobilePhone;
    }

    public function getLogoImage(): ?array
    {
        return $this->logoImage;
    }

    /**
     * @return string[]
     */
    public function getLocalizedMetaTitles(): array
    {
        return $this->localizedMetaTitles;
    }

    /**
     * @return string[]
     */
    public function getLocalizedMetaDescriptions(): array
    {
        return $this->localizedMetaDescriptions;
    }

    /**
     * @return string[]
     */
    public function getLocalizedMetaKeywords(): array
    {
        return $this->localizedMetaKeywords;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getAssociatedShops(): array
    {
        return $this->associatedShops;
    }

    public function getDni(): string
    {
        return $this->dni;
    }
}
