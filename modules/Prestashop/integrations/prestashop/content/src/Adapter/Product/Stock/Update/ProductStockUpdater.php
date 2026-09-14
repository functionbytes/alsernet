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

namespace PrestaShop\PrestaShop\Adapter\Product\Stock\Update;

use PrestaShop\PrestaShop\Adapter\Product\Repository\ProductRepository;
use PrestaShop\PrestaShop\Adapter\Product\Stock\Repository\StockAvailableRepository;
use PrestaShop\PrestaShop\Adapter\Product\Update\ProductStockProperties;
use PrestaShop\PrestaShop\Core\Domain\Product\Exception\CannotUpdateProductException;
use PrestaShop\PrestaShop\Core\Domain\Product\Stock\Exception\ProductStockException;
use PrestaShop\PrestaShop\Core\Domain\Product\Stock\Exception\StockAvailableNotFoundException;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;
use PrestaShop\PrestaShop\Core\Exception\CoreException;
use PrestaShop\PrestaShop\Core\Stock\StockManager;
use PrestaShop\PrestaShop\Core\Util\DateTime\DateTime;
use Product;
use StockAvailable;

/**
 * Updates backups related to Product stock
 */
class ProductStockUpdater
{
    /**
     * @var StockManager
     */
    private $stockManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var StockAvailableRepository
     */
    private $stockAvailableRepository;

    /**
     * @var bool
     */
    private $advancedStockEnabled;

    public function __construct(
        StockManager $stockManager,
        ProductRepository $productRepository,
        StockAvailableRepository $stockAvailableRepository,
        bool $advancedStockEnabled
    ) {
        $this->stockManager = $stockManager;
        $this->productRepository = $productRepository;
        $this->stockAvailableRepository = $stockAvailableRepository;
        $this->advancedStockEnabled = $advancedStockEnabled;
    }

    public function update(ProductId $productId, ProductStockProperties $properties)
    {
        $product = $this->productRepository->get($productId);
        $this->productRepository->partialUpdate(
            $product,
            $this->fillUpdatableProperties($product, $properties),
            CannotUpdateProductException::FAILED_UPDATE_STOCK
        );

        $this->updateStockAvailable($product, $properties);

        if ($this->advancedStockEnabled && $product->depends_on_stock) {
            StockAvailable::synchronize($product->id);
        }
    }

    /**
     * @return string[]|array<string, int[]>
     */
    private function fillUpdatableProperties(
        Product $product,
        ProductStockProperties $properties
    ): array {
        $updatableProperties = [];

        $localizedLaterLabels = $properties->getLocalizedAvailableLaterLabels();
        if ($localizedLaterLabels !== null) {
            $product->available_later = $localizedLaterLabels;
            $updatableProperties['available_later'] = array_keys($localizedLaterLabels);
        }

        $localizedNowLabels = $properties->getLocalizedAvailableNowLabels();
        if ($localizedNowLabels !== null) {
            $product->available_now = $localizedNowLabels;
            $updatableProperties['available_now'] = array_keys($localizedNowLabels);
        }
        if ($properties->getLocation() !== null) {
            $product->location = $properties->getLocation();
            $updatableProperties[] = 'location';
        }
        if ($properties->isLowStockAlertEnabled() !== null) {
            $product->low_stock_alert = $properties->isLowStockAlertEnabled();
            $updatableProperties[] = 'low_stock_alert';
        }
        if ($properties->getLowStockThreshold() !== null) {
            $product->low_stock_threshold = $properties->getLowStockThreshold();
            $updatableProperties[] = 'low_stock_threshold';
        }
        if ($properties->getMinimalQuantity() !== null) {
            $product->minimal_quantity = $properties->getMinimalQuantity();
            $updatableProperties[] = 'minimal_quantity';
        }
        if ($properties->getOutOfStockType() !== null) {
            $product->out_of_stock = $properties->getOutOfStockType()->getValue();
            $updatableProperties[] = 'out_of_stock';
        }
        if ($properties->getPackStockType() !== null) {
            $product->pack_stock_type = $properties->getPackStockType()->getValue();
            $updatableProperties[] = 'pack_stock_type';
        }
        if ($properties->getQuantity() !== null) {
            $product->quantity = $properties->getQuantity();
            $updatableProperties[] = 'quantity';
        }
        if ($properties->getAvailableDate() !== null) {
            $product->available_date = $properties->getAvailableDate()->format(DateTime::DEFAULT_DATE_FORMAT);
            $updatableProperties[] = 'available_date';
        }

        return $updatableProperties;
    }

    private function updateStockAvailable(Product $product, ProductStockProperties $properties)
    {
        $stockAvailable = $this->getStockAvailable($product);
        $stockUpdateRequired = false;

        if ($properties->getOutOfStockType() !== null) {
            $stockAvailable->out_of_stock = $properties->getOutOfStockType()->getValue();
            $stockUpdateRequired = true;
        }
        if ($properties->getLocation() !== null) {
            $stockAvailable->location = $properties->getLocation();
            $stockUpdateRequired = true;
        }

        if ($properties->getQuantity() !== null) {
            $this->updateQuantity($stockAvailable, $properties->getQuantity());
            $stockUpdateRequired = true;
        }

        if ($stockUpdateRequired) {
            $this->stockAvailableRepository->update($stockAvailable);
        }
    }

    private function updateQuantity(StockAvailable $stockAvailable, int $newQuantity): void
    {
        $deltaQuantity = $newQuantity - (int) $stockAvailable->quantity;
        $stockAvailable->quantity = $newQuantity;

        if ($deltaQuantity !== 0) {
            $this->stockManager->saveMovement($stockAvailable->id_product, $stockAvailable->id_product_attribute, $deltaQuantity);
        }
    }

    /**
     * @throws CoreException
     * @throws ProductStockException
     */
    private function getStockAvailable(Product $product): StockAvailable
    {
        $productId = new ProductId($product->id);
        try {
            return $this->stockAvailableRepository->getForProduct($productId);
        } catch (StockAvailableNotFoundException $e) {
            return $this->stockAvailableRepository->create($productId);
        }
    }
}
