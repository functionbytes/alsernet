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

namespace PrestaShop\PrestaShop\Core\Domain\Order\QueryResult;

/**
 * DTO for order preview data
 */
class OrderPreview
{
    /**
     * @var OrderPreviewInvoiceDetails
     */
    private $invoiceDetails;

    /**
     * @var OrderPreviewShippingDetails
     */
    private $shippingDetails;

    /**
     * @var OrderPreviewProductDetail[]
     */
    private $productDetails;

    /**
     * @var bool
     */
    private $taxIncluded;

    /**
     * @var bool
     */
    private $isVirtual;

    /**
     * @var string
     */
    private $invoiceAddressFormatted;

    /**
     * @var string
     */
    private $shippingAddressFormatted;

    public function __construct(
        OrderPreviewInvoiceDetails $invoiceDetails,
        OrderPreviewShippingDetails $shippingDetails,
        array $productDetails,
        bool $isVirtual,
        bool $taxIncluded,
        string $invoiceAddressFormatted = '',
        string $shippingAddressFormatted = ''
    ) {
        $this->invoiceDetails = $invoiceDetails;
        $this->shippingDetails = $shippingDetails;
        $this->productDetails = $productDetails;
        $this->taxIncluded = $taxIncluded;
        $this->isVirtual = $isVirtual;
        $this->invoiceAddressFormatted = $invoiceAddressFormatted;
        $this->shippingAddressFormatted = $shippingAddressFormatted;
    }

    public function getInvoiceDetails(): OrderPreviewInvoiceDetails
    {
        return $this->invoiceDetails;
    }

    public function getShippingDetails(): OrderPreviewShippingDetails
    {
        return $this->shippingDetails;
    }

    /**
     * @return OrderPreviewProductDetail[]
     */
    public function getProductDetails(): array
    {
        return $this->productDetails;
    }

    public function isTaxIncluded(): bool
    {
        return $this->taxIncluded;
    }

    public function isVirtual(): bool
    {
        return $this->isVirtual;
    }

    public function getInvoiceAddressFormatted(): string
    {
        return $this->invoiceAddressFormatted;
    }

    public function getShippingAddressFormatted(): string
    {
        return $this->shippingAddressFormatted;
    }
}
