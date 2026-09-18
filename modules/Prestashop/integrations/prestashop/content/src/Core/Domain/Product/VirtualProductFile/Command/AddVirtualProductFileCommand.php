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

namespace PrestaShop\PrestaShop\Core\Domain\Product\VirtualProductFile\Command;

use DateTimeInterface;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;

/**
 * Adds downloadable file for virtual product
 */
class AddVirtualProductFileCommand
{
    /**
     * @var ProductId
     */
    private $productId;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $displayName;

    /**
     * @var DateTimeInterface|null
     */
    private $expirationDate;

    /**
     * @var int|null
     */
    private $accessDays;

    /**
     * @var int|null
     */
    private $downloadTimesLimit;

    /**
     * @param  string  $displayName  display name of the file
     */
    public function __construct(
        int $productId,
        string $filePath,
        string $displayName,
        ?int $accessDays = null,
        ?int $downloadTimesLimit = null,
        ?DateTimeInterface $expirationDate = null
    ) {
        $this->productId = new ProductId($productId);
        $this->filePath = $filePath;
        $this->displayName = $displayName;
        $this->accessDays = $accessDays;
        $this->downloadTimesLimit = $downloadTimesLimit;
        $this->expirationDate = $expirationDate;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getExpirationDate(): ?DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function getAccessDays(): ?int
    {
        return $this->accessDays;
    }

    public function getDownloadTimesLimit(): ?int
    {
        return $this->downloadTimesLimit;
    }
}
