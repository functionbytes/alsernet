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

namespace PrestaShop\PrestaShop\Adapter\Product\Update;

use PrestaShop\PrestaShop\Adapter\Product\Combination\Repository\CombinationRepository;
use PrestaShop\PrestaShop\Adapter\Product\Repository\ProductRepository;
use PrestaShop\PrestaShop\Adapter\Product\Repository\ProductSupplierRepository;
use PrestaShop\PrestaShop\Adapter\Supplier\Repository\SupplierRepository;
use PrestaShop\PrestaShop\Core\Domain\Product\Combination\ValueObject\CombinationId;
use PrestaShop\PrestaShop\Core\Domain\Product\Exception\CannotUpdateProductException;
use PrestaShop\PrestaShop\Core\Domain\Product\Exception\InvalidProductTypeException;
use PrestaShop\PrestaShop\Core\Domain\Product\Supplier\Exception\DefaultProductSupplierNotAssociatedException;
use PrestaShop\PrestaShop\Core\Domain\Product\Supplier\ValueObject\ProductSupplierId;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;
use PrestaShop\PrestaShop\Core\Domain\Supplier\ValueObject\SupplierId;
use Product;
use ProductSupplier;

/**
 * Updates product supplier relation
 */
class ProductSupplierUpdater
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CombinationRepository
     */
    private $combinationRepository;

    /**
     * @var SupplierRepository
     */
    private $supplierRepository;

    /**
     * @var ProductSupplierRepository
     */
    private $productSupplierRepository;

    public function __construct(
        ProductRepository $productRepository,
        CombinationRepository $combinationRepository,
        SupplierRepository $supplierRepository,
        ProductSupplierRepository $productSupplierRepository
    ) {
        $this->productRepository = $productRepository;
        $this->supplierRepository = $supplierRepository;
        $this->productSupplierRepository = $productSupplierRepository;
        $this->combinationRepository = $combinationRepository;
    }

    /**
     * @param  array<int, ProductSupplier>  $productSuppliers
     * @return array<int, ProductSupplierId>
     */
    public function setProductSuppliers(
        ProductId $productId,
        array $productSuppliers
    ): array {
        $product = $this->productRepository->get($productId);

        if ($product->hasCombinations()) {
            $this->throwInvalidTypeException($productId);
        }

        $this->persistProductSuppliers($productId, $productSuppliers);

        return $this->getProductSupplierIds($productId);
    }

    /**
     * @param  array<int, ProductSupplier>  $productSuppliers
     * @return array<int, ProductSupplierId>
     */
    public function setCombinationSuppliers(
        ProductId $productId,
        CombinationId $combinationId,
        array $productSuppliers
    ): array {
        // Make sure all non-combination suppliers are deleted
        $existingNonCombinationSuppliers = $this->getProductSupplierIds($productId);
        $this->productSupplierRepository->bulkDelete($existingNonCombinationSuppliers);

        $this->persistProductSuppliers($productId, $productSuppliers, $combinationId);

        return $this->getProductSupplierIds($productId, $combinationId);
    }

    /**
     * Removes all product suppliers associated to specified product without combinations
     */
    public function removeAllForProduct(ProductId $productId): void
    {
        $product = $this->productRepository->get($productId);

        if ($product->hasCombinations()) {
            $this->throwInvalidTypeException($productId);
        }

        $productSupplierIds = $this->getProductSupplierIds($productId);
        $this->productSupplierRepository->bulkDelete($productSupplierIds);
        $this->resetDefaultSupplier($product);
    }

    /**
     * Removes all product suppliers associated to specified combination
     */
    public function removeAllForCombination(CombinationId $combinationId): void
    {
        $combination = $this->combinationRepository->get($combinationId);
        $productId = new ProductId((int) $combination->id_product);
        $product = $this->productRepository->get($productId);

        $productSupplierIds = $this->getProductSupplierIds($productId, $combinationId);
        $this->productSupplierRepository->bulkDelete($productSupplierIds);
        $this->resetDefaultSupplier($product);
    }

    public function resetDefaultSupplier(Product $product): void
    {
        $product->supplier_reference = '';
        $product->wholesale_price = '0';
        $product->id_supplier = 0;

        $this->productRepository->partialUpdate(
            $product,
            ['supplier_reference', 'wholesale_price', 'id_supplier'],
            CannotUpdateProductException::FAILED_UPDATE_DEFAULT_SUPPLIER
        );
    }

    public function updateProductDefaultSupplier(ProductId $productId, SupplierId $supplierId): void
    {
        $this->updateDefaultSupplier($productId, $supplierId, null);
    }

    public function updateCombinationDefaultSupplier(ProductId $productId, SupplierId $supplierId, CombinationId $combinationId): void
    {
        $this->updateDefaultSupplier($productId, $supplierId, $combinationId);
    }

    private function updateDefaultSupplier(ProductId $productId, SupplierId $supplierId, ?CombinationId $combinationId): void
    {
        $product = $this->productRepository->get($productId);
        $supplierIdValue = $supplierId->getValue();
        $productIdValue = (int) $product->id;

        $this->supplierRepository->assertSupplierExists($supplierId);
        if ($combinationId === null && $product->hasCombinations()) {
            $this->throwInvalidTypeException($productId);
        }

        $productSuppliers = $this->productSupplierRepository->getAssociatedProductSuppliers($productId, $supplierId);
        if (empty($productSuppliers)) {
            throw new DefaultProductSupplierNotAssociatedException(sprintf(
                'Supplier #%d is not associated with product #%d', $supplierIdValue, $productIdValue
            ));
        }

        $defaultSupplier = $this->productSupplierRepository->get(reset($productSuppliers));
        $product->supplier_reference = $defaultSupplier->product_supplier_reference;
        $product->wholesale_price = (string) $defaultSupplier->product_supplier_price_te;
        $product->id_supplier = $supplierIdValue;

        $this->productRepository->partialUpdate(
            $product,
            ['supplier_reference', 'wholesale_price', 'id_supplier'],
            CannotUpdateProductException::FAILED_UPDATE_DEFAULT_SUPPLIER
        );
    }

    /**
     * @param  array<int, ProductSupplier>  $productSuppliers
     */
    private function persistProductSuppliers(ProductId $productId, array $productSuppliers, ?CombinationId $combinationId = null): void
    {
        // Delete before updating to avoid potential duplicate entry (if updated entry has same value as a deleted one)
        $deletableProductSupplierIds = $this->getDeletableProductSupplierIds($productId, $productSuppliers, $combinationId);
        $this->productSupplierRepository->bulkDelete($deletableProductSupplierIds);

        foreach ($productSuppliers as $productSupplier) {
            if ($productSupplier->id) {
                $this->productSupplierRepository->update($productSupplier);
            } else {
                $this->productSupplierRepository->add($productSupplier);
            }
        }

        // Check if product has a default supplier if not use the first one
        $defaultSupplierId = $this->productSupplierRepository->getProductDefaultSupplierId($productId);
        if ($defaultSupplierId === null) {
            /** @var ProductSupplier $defaultSupplier */
            $defaultSupplier = reset($productSuppliers);
            $defaultSupplierId = new SupplierId((int) $defaultSupplier->id_supplier);
        }
        $this->updateDefaultSupplier($productId, $defaultSupplierId, $combinationId);
    }

    /**
     * @param  array<int, ProductSupplier>  $providedProductSuppliers
     * @return array<int, ProductSupplierId>
     */
    private function getDeletableProductSupplierIds(
        ProductId $productId,
        array $providedProductSuppliers,
        ?CombinationId $combinationId
    ): array {
        $existingIds = $this->getProductSupplierIds($productId, $combinationId);
        $idsForDeletion = [];

        foreach ($existingIds as $productSupplierId) {
            $idsForDeletion[$productSupplierId->getValue()] = $productSupplierId;
        }

        foreach ($providedProductSuppliers as $productSupplier) {
            $productSupplierId = (int) $productSupplier->id;

            if (isset($idsForDeletion[$productSupplierId])) {
                unset($idsForDeletion[$productSupplierId]);
            }
        }

        return $idsForDeletion;
    }

    /**
     * @return array<int, ProductSupplierId>
     */
    private function getProductSupplierIds(ProductId $productId, ?CombinationId $combinationId = null): array
    {
        return array_map(function (array $currentSupplier): ProductSupplierId {
            return new ProductSupplierId((int) $currentSupplier['id_product_supplier']);
        }, $this->productSupplierRepository->getProductSuppliersInfo($productId, $combinationId));
    }

    /**
     * @throws InvalidProductTypeException
     */
    private function throwInvalidTypeException(ProductId $productId): void
    {
        throw new InvalidProductTypeException(
            InvalidProductTypeException::EXPECTED_NO_COMBINATIONS_TYPE,
            sprintf(
                'Product #%d has combinations. Use %s::%s to set product suppliers for specified combination',
                $productId->getValue(),
                self::class,
                'setCombinationSuppliers()'
            ));
    }
}
