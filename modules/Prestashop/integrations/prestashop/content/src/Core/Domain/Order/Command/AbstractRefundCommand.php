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

namespace PrestaShop\PrestaShop\Core\Domain\Order\Command;

use InvalidArgumentException;
use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidAmountException;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidCancelProductException;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use PrestaShop\PrestaShop\Core\Domain\Order\ValueObject\OrderId;

/**
 * Command abstract class for refund commands
 */
abstract class AbstractRefundCommand
{
    /**
     * @var OrderId
     */
    protected $orderId;

    /**
     * @var array
     */
    protected $orderDetailRefunds;

    /**
     * @var bool
     */
    protected $restockRefundedProducts;

    /**
     * @var bool
     */
    protected $generateCreditSlip;

    /**
     * @var bool
     */
    protected $generateVoucher;

    /**
     * @var int
     */
    protected $voucherRefundType;

    /**
     * @var DecimalNumber|null
     */
    protected $voucherRefundAmount;

    /**
     * @throws InvalidCancelProductException
     * @throws OrderException
     */
    public function __construct(
        int $orderId,
        array $orderDetailRefunds,
        bool $restockRefundedProducts,
        bool $generateCreditSlip,
        bool $generateVoucher,
        int $voucherRefundType,
        ?string $voucherRefundAmount = null
    ) {
        $this->orderId = new OrderId($orderId);
        $this->restockRefundedProducts = $restockRefundedProducts;
        $this->generateCreditSlip = $generateCreditSlip;
        $this->generateVoucher = $generateVoucher;
        $this->voucherRefundType = $voucherRefundType;
        if ($voucherRefundAmount !== null) {
            try {
                $this->voucherRefundAmount = new DecimalNumber($voucherRefundAmount);
            } catch (InvalidArgumentException $e) {
                throw new InvalidAmountException;
            }
        }
        $this->setOrderDetailRefunds($orderDetailRefunds);
        if (! $this->generateCreditSlip && ! $this->generateVoucher) {
            throw new InvalidCancelProductException(InvalidCancelProductException::NO_GENERATION);
        }
    }

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getOrderDetailRefunds(): array
    {
        return $this->orderDetailRefunds;
    }

    public function restockRefundedProducts(): bool
    {
        return $this->restockRefundedProducts;
    }

    public function generateCreditSlip(): bool
    {
        return $this->generateCreditSlip;
    }

    public function generateVoucher(): bool
    {
        return $this->generateVoucher;
    }

    public function getVoucherRefundType(): int
    {
        return $this->voucherRefundType;
    }

    public function getVoucherRefundAmount(): ?DecimalNumber
    {
        return $this->voucherRefundAmount;
    }

    /**
     * @throws InvalidCancelProductException
     * @throws OrderException
     */
    abstract protected function setOrderDetailRefunds(array $orderDetailRefunds);
}
