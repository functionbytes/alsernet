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
use PrestaShop\PrestaShop\Core\Domain\Product\VirtualProductFile\ValueObject\VirtualProductFileId;

class UpdateVirtualProductFileCommand
{
    /**
     * @var VirtualProductFileId
     */
    private $virtualProductFileId;

    /**
     * @var string|null
     */
    private $filePath;

    /**
     * @var string|null
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

    public function __construct(
        int $virtualProductFileId
    ) {
        $this->virtualProductFileId = new VirtualProductFileId($virtualProductFileId);
    }

    public function getVirtualProductFileId(): VirtualProductFileId
    {
        return $this->virtualProductFileId;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getExpirationDate(): ?DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?DateTimeInterface $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    public function getAccessDays(): ?int
    {
        return $this->accessDays;
    }

    public function setAccessDays(?int $accessDays): void
    {
        $this->accessDays = $accessDays;
    }

    public function getDownloadTimesLimit(): ?int
    {
        return $this->downloadTimesLimit;
    }

    public function setDownloadTimesLimit(?int $downloadTimesLimit): void
    {
        $this->downloadTimesLimit = $downloadTimesLimit;
    }
}
