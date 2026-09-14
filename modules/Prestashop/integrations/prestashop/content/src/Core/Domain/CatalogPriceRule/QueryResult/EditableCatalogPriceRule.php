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

namespace PrestaShop\PrestaShop\Core\Domain\CatalogPriceRule\QueryResult;

use DateTime;
use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\PrestaShop\Core\Domain\CatalogPriceRule\ValueObject\CatalogPriceRuleId;
use PrestaShop\PrestaShop\Core\Domain\ValueObject\Reduction;

/**
 * Provides data for editing CatalogPriceRule
 */
class EditableCatalogPriceRule
{
    /**
     * @var CatalogPriceRuleId
     */
    private $catalogPriceRuleId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $shopId;

    /**
     * @var int
     */
    private $currencyId;

    /**
     * @var int
     */
    private $countryId;

    /**
     * @var int
     */
    private $groupId;

    /**
     * @var int
     */
    private $fromQuantity;

    /**
     * @var DecimalNumber
     */
    private $price;

    /**
     * @var ?DateTime
     */
    private $from;

    /**
     * @var ?DateTime
     */
    private $to;

    /**
     * @var bool
     */
    private $includeTax;

    /**
     * @var Reduction
     */
    private $reduction;

    public function __construct(
        CatalogPriceRuleId $catalogPriceRuleId,
        string $name,
        int $shopId,
        int $currencyId,
        int $countryId,
        int $groupId,
        int $fromQuantity,
        DecimalNumber $price,
        Reduction $reduction,
        bool $includeTax,
        ?DateTime $from,
        ?DateTime $to
    ) {
        $this->catalogPriceRuleId = $catalogPriceRuleId;
        $this->name = $name;
        $this->shopId = $shopId;
        $this->currencyId = $currencyId;
        $this->countryId = $countryId;
        $this->groupId = $groupId;
        $this->fromQuantity = $fromQuantity;
        $this->price = $price;
        $this->from = $from;
        $this->to = $to;
        $this->reduction = $reduction;
        $this->includeTax = $includeTax;
    }

    public function getCatalogPriceRuleId(): CatalogPriceRuleId
    {
        return $this->catalogPriceRuleId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getCountryId(): int
    {
        return $this->countryId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getFromQuantity(): int
    {
        return $this->fromQuantity;
    }

    public function getPrice(): DecimalNumber
    {
        return $this->price;
    }

    public function getFrom(): ?DateTime
    {
        return $this->from;
    }

    public function getTo(): ?DateTime
    {
        return $this->to;
    }

    public function getReduction(): Reduction
    {
        return $this->reduction;
    }

    public function isTaxIncluded(): bool
    {
        return $this->includeTax;
    }
}
