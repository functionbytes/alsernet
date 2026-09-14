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
 * Holds address data for cart information
 */
class CartAddress
{
    /**
     * @var int
     */
    private $addressId;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $formattedAddress;

    /**
     * @var bool is it used as delivery address
     */
    private $delivery;

    /**
     * @var bool is it used as invoice address
     */
    private $invoice;

    public function __construct(
        int $addressId,
        string $alias,
        string $formattedAddress,
        bool $delivery,
        bool $invoice
    ) {
        $this->addressId = $addressId;
        $this->alias = $alias;
        $this->formattedAddress = $formattedAddress;
        $this->delivery = $delivery;
        $this->invoice = $invoice;
    }

    public function getAddressId(): int
    {
        return $this->addressId;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFormattedAddress(): string
    {
        return $this->formattedAddress;
    }

    public function isDelivery(): bool
    {
        return $this->delivery;
    }

    public function isInvoice(): bool
    {
        return $this->invoice;
    }
}
