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

namespace PrestaShop\PrestaShop\Core\Domain\Product\SpecificPrice\Command;

use DateTime;
use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\PrestaShop\Core\Domain\Exception\DomainConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Product\Combination\ValueObject\CombinationId;
use PrestaShop\PrestaShop\Core\Domain\Product\Exception\ProductConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;
use PrestaShop\PrestaShop\Core\Domain\ValueObject\Reduction;

/**
 * Add specific price to a Product
 */
class AddProductSpecificPriceCommand
{
    /**
     * @var ProductId
     */
    private $productId;

    /**
     * @var Reduction
     */
    private $reduction;

    /**
     * @var bool
     */
    private $includesTax;

    /**
     * @var DecimalNumber
     */
    private $price;

    /**
     * @var int
     */
    private $fromQuantity;

    /**
     * @var int|null
     */
    private $shopGroupId;

    /**
     * @var int|null
     */
    private $shopId;

    /**
     * @var CombinationId|null
     */
    private $combinationId;

    /**
     * @var int|null
     */
    private $currencyId;

    /**
     * @var int|null
     */
    private $countryId;

    /**
     * @var int|null
     */
    private $groupId;

    /**
     * @var int|null
     */
    private $customerId;

    /**
     * @var DateTime|null
     */
    private $dateTimeFrom;

    /**
     * @var DateTime|null
     */
    private $dateTimeTo;

    /**
     * @throws DomainConstraintException
     * @throws ProductConstraintException
     */
    public function __construct(
        int $productId,
        string $reductionType,
        float $reductionValue,
        bool $includeTax,
        float $price,
        int $fromQuantity
    ) {
        $this->productId = new ProductId($productId);
        $this->reduction = new Reduction($reductionType, $reductionValue);
        $this->includesTax = $includeTax;
        $this->price = new DecimalNumber((string) $price);
        $this->fromQuantity = $fromQuantity;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getReduction(): Reduction
    {
        return $this->reduction;
    }

    public function includesTax(): bool
    {
        return $this->includesTax;
    }

    public function getPrice(): DecimalNumber
    {
        return $this->price;
    }

    public function getFromQuantity(): int
    {
        return $this->fromQuantity;
    }

    public function getDateTimeFrom(): ?DateTime
    {
        return $this->dateTimeFrom;
    }

    /**
     * @return $this
     */
    public function setDateTimeFrom(?DateTime $dateTimeFrom): self
    {
        $this->dateTimeFrom = $dateTimeFrom;

        return $this;
    }

    public function getShopGroupId(): ?int
    {
        return $this->shopGroupId;
    }

    /**
     * @return $this
     */
    public function setShopGroupId(int $shopGroupId): self
    {
        $this->shopGroupId = $shopGroupId;

        return $this;
    }

    public function getShopId(): ?int
    {
        return $this->shopId;
    }

    /**
     * @return $this
     */
    public function setShopId(int $shopId): self
    {
        $this->shopId = $shopId;

        return $this;
    }

    public function getCombinationId(): ?CombinationId
    {
        return $this->combinationId;
    }

    /**
     * @return $this
     */
    public function setCombinationId(int $combinationId): self
    {
        $this->combinationId = new CombinationId($combinationId);

        return $this;
    }

    public function getCurrencyId(): ?int
    {
        return $this->currencyId;
    }

    /**
     * @return $this
     */
    public function setCurrencyId(int $currencyId): self
    {
        $this->currencyId = $currencyId;

        return $this;
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    /**
     * @return $this
     */
    public function setCountryId(int $countryId): self
    {
        $this->countryId = $countryId;

        return $this;
    }

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    /**
     * @return $this
     */
    public function setGroupId(int $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    /**
     * @return $this
     */
    public function setCustomerId(int $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getDateTimeTo(): ?DateTime
    {
        return $this->dateTimeTo;
    }

    /**
     * @return $this
     */
    public function setDateTimeTo(?DateTime $dateTimeTo): self
    {
        $this->dateTimeTo = $dateTimeTo;

        return $this;
    }
}
