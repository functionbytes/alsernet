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

namespace PrestaShop\PrestaShop\Adapter\Supplier\CommandHandler;

use Address;
use PrestaShop\PrestaShop\Adapter\Supplier\AbstractSupplierHandler;
use PrestaShop\PrestaShop\Core\Domain\Supplier\Command\EditSupplierCommand;
use PrestaShop\PrestaShop\Core\Domain\Supplier\CommandHandler\EditSupplierHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Supplier\Exception\SupplierException;
use PrestaShopException;
use Supplier;

/**
 * Handles command which edits supplier using legacy object model
 */
final class EditSupplierHandler extends AbstractSupplierHandler implements EditSupplierHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws SupplierException
     */
    public function handle(EditSupplierCommand $command)
    {
        $supplierId = $command->getSupplierId();
        $supplier = $this->getSupplier($supplierId);
        $address = $this->getSupplierAddress($supplierId);

        $this->populateSupplierWithData($supplier, $command);
        $this->populateAddressWithData($address, $command);

        try {
            $this->validateFields($supplier, $address);

            if ($supplier->update() === false) {
                throw new SupplierException(sprintf('Cannot update supplier with id "%s"', $supplier->id));
            }
            if ($address->update() === false) {
                throw new SupplierException(sprintf('Cannot update supplier address with id "%s"', $address->id));
            }
            if ($command->getAssociatedShops() !== null) {
                $this->associateWithShops($supplier, $command->getAssociatedShops());
            }
        } catch (PrestaShopException $e) {
            throw new SupplierException(sprintf('Cannot update supplier with id "%s"', $supplier->id));
        }
    }

    /**
     * Populates Supplier object with given data
     */
    private function populateSupplierWithData(Supplier $supplier, EditSupplierCommand $command)
    {
        if ($command->getName() !== null) {
            $supplier->name = $command->getName();
        }
        if ($command->getLocalizedDescriptions() !== null) {
            $supplier->description = $command->getLocalizedDescriptions();
        }
        if ($command->getLocalizedMetaDescriptions() !== null) {
            $supplier->meta_description = $command->getLocalizedMetaDescriptions();
        }
        if ($command->getLocalizedMetaKeywords() !== null) {
            $supplier->meta_keywords = $command->getLocalizedMetaKeywords();
        }
        if ($command->getLocalizedMetaTitles() !== null) {
            $supplier->meta_title = $command->getLocalizedMetaTitles();
        }
        if ($command->isEnabled() !== null) {
            $supplier->active = $command->isEnabled();
        }
        $supplier->date_upd = date('Y-m-d H:i:s');
    }

    /**
     * Populates Supplier address with given data
     */
    private function populateAddressWithData(Address $address, EditSupplierCommand $command)
    {
        if ($command->getAddress() !== null) {
            $address->address1 = $command->getAddress();
        }
        if ($command->getAddress2() !== null) {
            $address->address2 = $command->getAddress2();
        }
        if ($command->getPostCode() !== null) {
            $address->postcode = $command->getPostCode();
        }
        if ($command->getPhone() !== null) {
            $address->phone = $command->getPhone();
        }
        if ($command->getMobilePhone() !== null) {
            $address->phone_mobile = $command->getMobilePhone();
        }
        if ($command->getCity() !== null) {
            $address->city = $command->getCity();
        }
        if ($command->getCountryId() !== null) {
            $address->id_country = $command->getCountryId();
        }
        if ($command->getStateId() !== null) {
            $address->id_state = $command->getStateId();
        }
        if ($command->getDni() !== null) {
            $address->dni = $command->getDni();
        }
    }
}
