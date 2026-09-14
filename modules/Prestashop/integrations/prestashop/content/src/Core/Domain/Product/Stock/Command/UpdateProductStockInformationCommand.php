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

namespace PrestaShop\PrestaShop\Core\Domain\Product\Stock\Command;

use DateTimeInterface;
use PrestaShop\PrestaShop\Core\Domain\Product\Pack\Exception\ProductPackConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Product\Pack\ValueObject\PackStockType;
use PrestaShop\PrestaShop\Core\Domain\Product\Stock\Exception\ProductStockConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Product\Stock\ValueObject\OutOfStockType;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;

/**
 * Class UpdateProductStockInformationCommand update a given product stock
 */
class UpdateProductStockInformationCommand
{
    /**
     * @var ProductId
     */
    private $productId;

    /**
     * @var PackStockType|null
     */
    private $packStockType;

    /**
     * @var int|null
     */
    private $quantity;

    /**
     * @var OutOfStockType|null
     */
    private $outOfStockType;

    /**
     * @var int|null
     */
    private $minimalQuantity;

    /**
     * @var string|null
     */
    private $location;

    /**
     * @var int|null
     */
    private $lowStockThreshold;

    /**
     * @var bool|null
     */
    private $lowStockAlertEnabled;

    /**
     * @var string[]|null key value pairs where key is the id of language
     */
    private $localizedAvailableNowLabels;

    /**
     * @var string[]|null key value pairs where key is the id of language
     */
    private $localizedAvailableLaterLabels;

    /**
     * @var DateTimeInterface|null
     */
    private $availableDate;

    public function __construct(int $productId)
    {
        $this->productId = new ProductId($productId);
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getPackStockType(): ?PackStockType
    {
        return $this->packStockType;
    }

    /**
     * @throws ProductPackConstraintException
     */
    public function setPackStockType(int $packStockType): UpdateProductStockInformationCommand
    {
        $this->packStockType = new PackStockType($packStockType);

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): UpdateProductStockInformationCommand
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getOutOfStockType(): ?OutOfStockType
    {
        return $this->outOfStockType;
    }

    /**
     * @return $this
     *
     * @throws ProductStockConstraintException
     */
    public function setOutOfStockType(int $outOfStockType): UpdateProductStockInformationCommand
    {
        $this->outOfStockType = new OutOfStockType($outOfStockType);

        return $this;
    }

    public function getMinimalQuantity(): ?int
    {
        return $this->minimalQuantity;
    }

    public function setMinimalQuantity(int $minimalQuantity): UpdateProductStockInformationCommand
    {
        $this->minimalQuantity = $minimalQuantity;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): UpdateProductStockInformationCommand
    {
        $this->location = $location;

        return $this;
    }

    public function getLowStockThreshold(): ?int
    {
        return $this->lowStockThreshold;
    }

    public function setLowStockThreshold(int $lowStockThreshold): UpdateProductStockInformationCommand
    {
        $this->lowStockThreshold = $lowStockThreshold;

        return $this;
    }

    public function isLowStockAlertEnabled(): ?bool
    {
        return $this->lowStockAlertEnabled;
    }

    public function setLowStockAlert(bool $enabled): UpdateProductStockInformationCommand
    {
        $this->lowStockAlertEnabled = $enabled;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getLocalizedAvailableNowLabels(): ?array
    {
        return $this->localizedAvailableNowLabels;
    }

    /**
     * @param  string[]  $localizedAvailableNowLabels
     */
    public function setLocalizedAvailableNowLabels(array $localizedAvailableNowLabels): UpdateProductStockInformationCommand
    {
        $this->localizedAvailableNowLabels = $localizedAvailableNowLabels;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getLocalizedAvailableLaterLabels(): ?array
    {
        return $this->localizedAvailableLaterLabels;
    }

    /**
     * @param  string[]  $localizedAvailableLaterLabels
     */
    public function setLocalizedAvailableLaterLabels(array $localizedAvailableLaterLabels): UpdateProductStockInformationCommand
    {
        $this->localizedAvailableLaterLabels = $localizedAvailableLaterLabels;

        return $this;
    }

    public function getAvailableDate(): ?DateTimeInterface
    {
        return $this->availableDate;
    }

    public function setAvailableDate(DateTimeInterface $availableDate): UpdateProductStockInformationCommand
    {
        $this->availableDate = $availableDate;

        return $this;
    }
}
