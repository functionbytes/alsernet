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

namespace PrestaShop\PrestaShop\Core\Domain\Product\Command;

use PrestaShop\PrestaShop\Core\Domain\Manufacturer\Exception\ManufacturerConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\ValueObject\ManufacturerId;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\ValueObject\ManufacturerIdInterface;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\ValueObject\NoManufacturerId;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductCondition;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductVisibility;

class UpdateProductOptionsCommand
{
    /**
     * @var ProductId
     */
    private $productId;

    /**
     * @var bool|null
     */
    private $active;

    /**
     * @var ProductVisibility|null
     */
    private $visibility;

    /**
     * @var bool|null
     */
    private $availableForOrder;

    /**
     * @var bool|null
     */
    private $onlineOnly;

    /**
     * @var bool|null
     */
    private $showPrice;

    /**
     * @var ProductCondition|null
     */
    private $condition;

    /**
     * @var bool|null
     */
    private $showCondition;

    /**
     * @var ManufacturerIdInterface|null
     */
    private $manufacturerId;

    public function __construct(int $productId)
    {
        $this->productId = new ProductId($productId);
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): UpdateProductOptionsCommand
    {
        $this->active = $active;

        return $this;
    }

    public function getVisibility(): ?ProductVisibility
    {
        return $this->visibility;
    }

    public function isAvailableForOrder(): ?bool
    {
        return $this->availableForOrder;
    }

    public function setVisibility(string $visibility): UpdateProductOptionsCommand
    {
        $this->visibility = new ProductVisibility($visibility);

        return $this;
    }

    public function setAvailableForOrder(bool $availableForOrder): UpdateProductOptionsCommand
    {
        $this->availableForOrder = $availableForOrder;

        return $this;
    }

    public function isOnlineOnly(): ?bool
    {
        return $this->onlineOnly;
    }

    public function setOnlineOnly(bool $onlineOnly): UpdateProductOptionsCommand
    {
        $this->onlineOnly = $onlineOnly;

        return $this;
    }

    public function showPrice(): ?bool
    {
        return $this->showPrice;
    }

    public function setShowPrice(bool $showPrice): UpdateProductOptionsCommand
    {
        $this->showPrice = $showPrice;

        return $this;
    }

    public function getCondition(): ?ProductCondition
    {
        return $this->condition;
    }

    public function setCondition(string $condition): UpdateProductOptionsCommand
    {
        $this->condition = new ProductCondition($condition);

        return $this;
    }

    /**
     * @return $this
     */
    public function setShowCondition(bool $showCondition): UpdateProductOptionsCommand
    {
        $this->showCondition = $showCondition;

        return $this;
    }

    public function showCondition(): ?bool
    {
        return $this->showCondition;
    }

    public function getManufacturerId(): ?ManufacturerIdInterface
    {
        return $this->manufacturerId;
    }

    /**
     * @return $this
     *
     * @throws ManufacturerConstraintException
     */
    public function setManufacturerId(int $manufacturerId): UpdateProductOptionsCommand
    {
        $this->manufacturerId = $manufacturerId === NoManufacturerId::NO_MANUFACTURER_ID ?
            new NoManufacturerId :
            new ManufacturerId($manufacturerId);

        return $this;
    }
}
