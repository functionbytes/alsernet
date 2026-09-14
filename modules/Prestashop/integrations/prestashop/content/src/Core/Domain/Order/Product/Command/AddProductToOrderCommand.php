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

namespace PrestaShop\PrestaShop\Core\Domain\Order\Product\Command;

use InvalidArgumentException;
use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidAmountException;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidProductQuantityException;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use PrestaShop\PrestaShop\Core\Domain\Order\ValueObject\OrderId;
use PrestaShop\PrestaShop\Core\Domain\Product\Combination\ValueObject\CombinationId;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;

/**
 * Adds product to an existing order.
 */
class AddProductToOrderCommand
{
    /**
     * @var OrderId
     */
    private $orderId;

    /**
     * @var ProductId
     */
    private $productId;

    /**
     * @var CombinationId|null
     */
    private $combinationId;

    /**
     * @var DecimalNumber
     */
    private $productPriceTaxIncluded;

    /**
     * @var DecimalNumber
     */
    private $productPriceTaxExcluded;

    /**
     * @var int
     */
    private $productQuantity;

    /**
     * @var int|null invoice id or null if new invoice should be created
     */
    private $orderInvoiceId;

    /**
     * @var bool|null bool if product is being added using new invoice
     */
    private $hasFreeShipping;

    /**
     * Add product to an order with new invoice. It applies to orders that were already paid and waiting for payment.
     *
     *
     * @return self
     *
     * @throws InvalidProductQuantityException
     * @throws InvalidAmountException
     * @throws OrderException
     */
    public static function withNewInvoice(
        int $orderId,
        int $productId,
        int $combinationId,
        string $productPriceTaxIncluded,
        string $productPriceTaxExcluded,
        int $productQuantity,
        ?bool $hasFreeShipping = null
    ) {
        $command = new self(
            $orderId,
            $productId,
            $combinationId,
            $productPriceTaxIncluded,
            $productPriceTaxExcluded,
            $productQuantity
        );

        $command->hasFreeShipping = $hasFreeShipping;

        return $command;
    }

    /**
     * Add product to an order using existing invoice. It applies only for orders that were not yet paid.
     *
     *
     * @return self
     *
     * @throws InvalidProductQuantityException
     * @throws InvalidAmountException
     * @throws OrderException
     */
    public static function toExistingInvoice(
        int $orderId,
        int $orderInvoiceId,
        int $productId,
        int $combinationId,
        string $productPriceTaxIncluded,
        string $productPriceTaxExcluded,
        int $productQuantity
    ) {
        $command = new self(
            $orderId,
            $productId,
            $combinationId,
            $productPriceTaxIncluded,
            $productPriceTaxExcluded,
            $productQuantity
        );

        $command->orderInvoiceId = $orderInvoiceId;

        return $command;
    }

    /**
     * @throws InvalidProductQuantityException
     * @throws InvalidAmountException
     * @throws OrderException
     */
    private function __construct(
        int $orderId,
        int $productId,
        int $combinationId,
        string $productPriceTaxIncluded,
        string $productPriceTaxExcluded,
        int $productQuantity
    ) {
        $this->orderId = new OrderId($orderId);
        $this->productId = new ProductId($productId);
        $this->combinationId = ! empty($combinationId) ? new CombinationId($combinationId) : null;
        try {
            $this->productPriceTaxIncluded = new DecimalNumber($productPriceTaxIncluded);
            $this->productPriceTaxExcluded = new DecimalNumber($productPriceTaxExcluded);
        } catch (InvalidArgumentException $e) {
            throw new InvalidAmountException;
        }
        $this->setProductQuantity($productQuantity);
    }

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getCombinationId(): ?CombinationId
    {
        return $this->combinationId;
    }

    public function getProductPriceTaxIncluded(): DecimalNumber
    {
        return $this->productPriceTaxIncluded;
    }

    public function getProductPriceTaxExcluded(): DecimalNumber
    {
        return $this->productPriceTaxExcluded;
    }

    public function getProductQuantity(): int
    {
        return $this->productQuantity;
    }

    public function getOrderInvoiceId(): ?int
    {
        return $this->orderInvoiceId;
    }

    public function hasFreeShipping(): ?bool
    {
        return $this->hasFreeShipping;
    }

    /**
     * @throws InvalidProductQuantityException
     */
    private function setProductQuantity(int $productQuantity): void
    {
        if ($productQuantity <= 0) {
            throw new InvalidProductQuantityException('When adding a product quantity must be strictly positive');
        }
        $this->productQuantity = $productQuantity;
    }
}
