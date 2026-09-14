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

namespace PrestaShop\PrestaShop\Core\Domain\Cart\QueryResult\CartForOrderCreation;

/**
 * Holds cart summary data
 */
class CartSummary
{
    /**
     * @var string
     */
    private $totalProductsPrice;

    /**
     * @var string
     */
    private $totalDiscount;

    /**
     * @var string
     */
    private $totalShippingPrice;

    /**
     * @var string
     */
    private $totalShippingWithoutTaxes;

    /**
     * @var string
     */
    private $totalTaxes;

    /**
     * @var string
     */
    private $totalPriceWithTaxes;

    /**
     * @var string
     */
    private $totalPriceWithoutTaxes;

    /**
     * @var string
     */
    private $orderMessage;

    /**
     * @var string
     */
    private $processOrderLink;

    public function __construct(
        string $totalProductsPrice,
        string $totalDiscount,
        string $totalShippingPrice,
        string $totalShippingWithoutTaxes,
        string $totalTaxes,
        string $totalPriceWithTaxes,
        string $totalPriceWithoutTaxes,
        string $orderMessage,
        string $processOrderLink
    ) {
        $this->totalProductsPrice = $totalProductsPrice;
        $this->totalDiscount = $totalDiscount;
        $this->totalShippingPrice = $totalShippingPrice;
        $this->totalShippingWithoutTaxes = $totalShippingWithoutTaxes;
        $this->totalTaxes = $totalTaxes;
        $this->totalPriceWithTaxes = $totalPriceWithTaxes;
        $this->totalPriceWithoutTaxes = $totalPriceWithoutTaxes;
        $this->processOrderLink = $processOrderLink;
        $this->orderMessage = $orderMessage;
    }

    public function getTotalProductsPrice(): string
    {
        return $this->totalProductsPrice;
    }

    public function getTotalDiscount(): string
    {
        return $this->totalDiscount;
    }

    public function getTotalShippingPrice(): string
    {
        return $this->totalShippingPrice;
    }

    public function getTotalShippingWithoutTaxes(): string
    {
        return $this->totalShippingWithoutTaxes;
    }

    public function getTotalTaxes(): string
    {
        return $this->totalTaxes;
    }

    public function getTotalPriceWithTaxes(): string
    {
        return $this->totalPriceWithTaxes;
    }

    public function getTotalPriceWithoutTaxes(): string
    {
        return $this->totalPriceWithoutTaxes;
    }

    public function getProcessOrderLink(): string
    {
        return $this->processOrderLink;
    }

    public function getOrderMessage(): string
    {
        return $this->orderMessage;
    }
}
