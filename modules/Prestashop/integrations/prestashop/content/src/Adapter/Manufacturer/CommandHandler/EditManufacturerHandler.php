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

namespace PrestaShop\PrestaShop\Adapter\Manufacturer\CommandHandler;

use Manufacturer;
use PrestaShop\PrestaShop\Adapter\Manufacturer\AbstractManufacturerHandler;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\Command\EditManufacturerCommand;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\CommandHandler\EditManufacturerHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\Exception\ManufacturerException;
use PrestaShopException;

/**
 * Handles command which edits manufacturer using legacy object model
 */
final class EditManufacturerHandler extends AbstractManufacturerHandler implements EditManufacturerHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws ManufacturerException
     */
    public function handle(EditManufacturerCommand $command)
    {
        $manufacturerId = $command->getManufacturerId();
        $manufacturer = $this->getManufacturer($manufacturerId);
        $this->populateManufacturerWithData($manufacturer, $command);

        try {
            if ($manufacturer->validateFields(false) === false) {
                throw new ManufacturerException('Manufacturer contains invalid field values');
            }

            if (! $manufacturer->update()) {
                throw new ManufacturerException(sprintf('Cannot update manufacturer with id "%s"', $manufacturer->id));
            }

            if ($command->getAssociatedShops() !== null) {
                $this->associateWithShops($manufacturer, $command->getAssociatedShops());
            }
        } catch (PrestaShopException $e) {
            throw new ManufacturerException(sprintf('Cannot update manufacturer with id "%s"', $manufacturer->id));
        }
    }

    /**
     * Populates Manufacturer object with given data
     */
    private function populateManufacturerWithData(Manufacturer $manufacturer, EditManufacturerCommand $command)
    {
        if ($command->getName() !== null) {
            $manufacturer->name = $command->getName();
        }
        if ($command->getLocalizedShortDescriptions() !== null) {
            $manufacturer->short_description = $command->getLocalizedShortDescriptions();
        }
        if ($command->getLocalizedDescriptions() !== null) {
            $manufacturer->description = $command->getLocalizedDescriptions();
        }
        if ($command->getLocalizedMetaDescriptions() !== null) {
            $manufacturer->meta_description = $command->getLocalizedMetaDescriptions();
        }
        if ($command->getLocalizedMetaKeywords() !== null) {
            $manufacturer->meta_keywords = $command->getLocalizedMetaKeywords();
        }
        if ($command->getLocalizedMetaTitles() !== null) {
            $manufacturer->meta_title = $command->getLocalizedMetaTitles();
        }
        if ($command->isEnabled() !== null) {
            $manufacturer->active = $command->isEnabled();
        }
    }
}
