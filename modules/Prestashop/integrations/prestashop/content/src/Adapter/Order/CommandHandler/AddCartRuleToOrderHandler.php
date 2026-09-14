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

namespace PrestaShop\PrestaShop\Adapter\Order\CommandHandler;

use Cart;
use CartRule;
use Configuration;
use Currency;
use Customer;
use Order;
use OrderInvoice;
use PrestaShop\PrestaShop\Adapter\ContextStateManager;
use PrestaShop\PrestaShop\Adapter\Order\AbstractOrderHandler;
use PrestaShop\PrestaShop\Adapter\Order\OrderAmountUpdater;
use PrestaShop\PrestaShop\Core\Domain\CartRule\Exception\InvalidCartRuleDiscountValueException;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\AddCartRuleToOrderCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\CommandHandler\AddCartRuleToOrderHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use PrestaShop\PrestaShop\Core\Domain\Order\OrderDiscountType;
use PrestaShopException;
use Shop;
use Validate;

/**
 * @internal
 */
final class AddCartRuleToOrderHandler extends AbstractOrderHandler implements AddCartRuleToOrderHandlerInterface
{
    /**
     * @var OrderAmountUpdater
     */
    private $orderAmountUpdater;

    /**
     * @var ContextStateManager
     */
    private $contextStateManager;

    public function __construct(OrderAmountUpdater $orderAmountUpdater, ContextStateManager $contextStateManager)
    {
        $this->orderAmountUpdater = $orderAmountUpdater;
        $this->contextStateManager = $contextStateManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(AddCartRuleToOrderCommand $command): void
    {
        $this->assertPercentCartRule($command);
        $order = $this->getOrder($command->getOrderId());

        $this->contextStateManager
            ->setCurrency(new Currency($order->id_currency))
            ->setCustomer(new Customer($order->id_customer))
            ->setShop(new Shop($order->id_shop));

        try {
            $this->addCartRuleAndUpdateOrder($command, $order);
        } finally {
            $this->contextStateManager->restorePreviousContext();
        }
    }

    /**
     * @throws InvalidCartRuleDiscountValueException
     * @throws OrderException
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    private function addCartRuleAndUpdateOrder(AddCartRuleToOrderCommand $command, Order $order): void
    {
        // If the discount is for only one invoice
        $orderInvoice = null;
        if ($order->hasInvoice() && $command->getOrderInvoiceId() !== null) {
            $orderInvoice = new OrderInvoice($command->getOrderInvoiceId()->getValue());
            if (! Validate::isLoadedObject($orderInvoice)) {
                throw new OrderException('Can\'t load Order Invoice object');
            }
        }
        $this->assertAmountCartRule($command, $order, $orderInvoice);
        $this->assertFreeShippingCartRule($command, $order, $orderInvoice);

        $cart = Cart::getCartByOrderId($order->id);
        $cartRuleObj = new CartRule;
        $cartRuleObj->date_from = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order->date_add)));
        $cartRuleObj->date_to = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $cartRuleObj->name[Configuration::get('PS_LANG_DEFAULT')] = $command->getCartRuleName();
        // This a one time cart rule, for a specific user that can only be used once
        $cartRuleObj->id_customer = $cart->id_customer;
        $cartRuleObj->quantity = 1;
        $cartRuleObj->quantity_per_user = 1;
        $cartRuleObj->reduction_currency = (int) $order->id_currency;
        $cartRuleObj->active = false;
        $cartRuleObj->highlight = false;

        if ($command->getCartRuleType() === OrderDiscountType::DISCOUNT_PERCENT) {
            $cartRuleObj->reduction_percent = (float) (string) $command->getDiscountValue();
        } elseif ($command->getCartRuleType() === OrderDiscountType::DISCOUNT_AMOUNT) {
            $discountValueTaxIncluded = (float) (string) $command->getDiscountValue();
            $cartRuleObj->reduction_amount = $discountValueTaxIncluded;
            $cartRuleObj->reduction_tax = true;
        } elseif ($command->getCartRuleType() === OrderDiscountType::FREE_SHIPPING) {
            $cartRuleObj->free_shipping = true;
        }

        try {
            if (! $cartRuleObj->add()) {
                throw new OrderException('An error occurred during the CartRule creation');
            }
        } catch (PrestaShopException $e) {
            throw new OrderException('An error occurred during the CartRule creation', 0, $e);
        }

        try {
            // It's important to add the cart rule to the cart Or it will be ignored when cart performs AutoRemove AddAdd
            if (! $cart->addCartRule($cartRuleObj->id)) {
                throw new OrderException('An error occurred while adding CartRule to cart');
            }
        } catch (PrestaShopException $e) {
            throw new OrderException('An error occurred while adding CartRule to cart', 0, $e);
        }

        $this->orderAmountUpdater->update($order, $cart, $orderInvoice !== null ? (int) $orderInvoice->id : null);
    }

    /**
     * @throws InvalidCartRuleDiscountValueException
     */
    private function assertPercentCartRule(AddCartRuleToOrderCommand $command): void
    {
        if ($command->getCartRuleType() !== OrderDiscountType::DISCOUNT_PERCENT) {
            return;
        }

        $discountValue = (float) (string) $command->getDiscountValue();
        if ($discountValue <= 0) {
            throw new InvalidCartRuleDiscountValueException(
                'Percent value must be greater than 0',
                InvalidCartRuleDiscountValueException::INVALID_MIN_PERCENT
            );
        } elseif ($discountValue > 100) {
            throw new InvalidCartRuleDiscountValueException(
                'Percent value must be less than 100',
                InvalidCartRuleDiscountValueException::INVALID_MAX_PERCENT
            );
        }
    }

    /**
     * @throws InvalidCartRuleDiscountValueException
     */
    private function assertAmountCartRule(AddCartRuleToOrderCommand $command, Order $order, ?OrderInvoice $orderInvoice): void
    {
        if ($command->getCartRuleType() !== OrderDiscountType::DISCOUNT_AMOUNT) {
            return;
        }

        if ($command->getDiscountValue() === null || $command->getDiscountValue()->isLowerOrEqualThanZero()) {
            throw new InvalidCartRuleDiscountValueException(
                'Discount amount specified is not positive',
                InvalidCartRuleDiscountValueException::INVALID_MIN_AMOUNT
            );
        }

        $discountValue = (float) (string) $command->getDiscountValue();
        if ($orderInvoice !== null) {
            $orderInvoices = [$orderInvoice];
        } elseif ($order->hasInvoice()) {
            $orderInvoices = $order->getInvoicesCollection()->getResults();
        }
        if (! empty($orderInvoices)) {
            foreach ($orderInvoices as $invoice) {
                if ($discountValue > $invoice->total_paid_tax_incl) {
                    throw new InvalidCartRuleDiscountValueException(
                        'Discount amount specified is too high',
                        InvalidCartRuleDiscountValueException::INVALID_MAX_AMOUNT
                    );
                }
            }
        } else {
            if ($discountValue > $order->total_paid_tax_incl) {
                throw new InvalidCartRuleDiscountValueException(
                    'Discount amount specified is too high',
                    InvalidCartRuleDiscountValueException::INVALID_MAX_AMOUNT
                );
            }
        }
    }

    /**
     * @throws InvalidCartRuleDiscountValueException
     */
    private function assertFreeShippingCartRule(AddCartRuleToOrderCommand $command, Order $order, ?OrderInvoice $orderInvoice): void
    {
        if ($command->getCartRuleType() !== OrderDiscountType::FREE_SHIPPING) {
            return;
        }

        if ($orderInvoice !== null) {
            $orderInvoices = [$orderInvoice];
        } elseif ($order->hasInvoice()) {
            $orderInvoices = $order->getInvoicesCollection()->getResults();
        }
        if (! empty($orderInvoices)) {
            foreach ($orderInvoices as $invoice) {
                if ($invoice->total_paid_tax_incl < $invoice->total_shipping_tax_incl) {
                    throw new InvalidCartRuleDiscountValueException(
                        'Discount amount specified is too high',
                        InvalidCartRuleDiscountValueException::INVALID_FREE_SHIPPING
                    );
                }
            }
        } else {
            if ($order->total_paid_tax_incl < $order->total_shipping_tax_incl) {
                throw new InvalidCartRuleDiscountValueException(
                    'Discount amount specified is too high',
                    InvalidCartRuleDiscountValueException::INVALID_FREE_SHIPPING
                );
            }
        }
    }
}
