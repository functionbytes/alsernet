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

namespace PrestaShop\PrestaShop\Adapter\Address\CommandHandler;

use Address;
use Country;
use PrestaShop\PrestaShop\Adapter\Address\AbstractAddressHandler;
use PrestaShop\PrestaShop\Core\Domain\Address\Command\EditManufacturerAddressCommand;
use PrestaShop\PrestaShop\Core\Domain\Address\CommandHandler\EditManufacturerAddressHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Address\Exception\AddressException;
use PrestaShopException;

/**
 * Handles command which edits manufacturer address
 */
final class EditManufacturerAddressHandler extends AbstractAddressHandler implements EditManufacturerAddressHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(EditManufacturerAddressCommand $command)
    {
        $addressId = $command->getAddressId();
        $address = $this->getAddress($addressId);
        $this->populateAddressWithData($address, $command);

        try {
            $this->validateAddress($address);
            if (! $address->update()) {
                throw new AddressException(sprintf('Cannot update address with id "%s"', $address->id));
            }
        } catch (PrestaShopException $e) {
            throw new AddressException(sprintf('Cannot update address with id "%s"', $address->id));
        }
    }

    /**
     * Populates Address object with given data
     *
     * @param  EditManufacturerAddressCommand  $command
     */
    private function populateAddressWithData(Address $address, $command)
    {
        if ($command->getManufacturerId() !== null) {
            $address->id_manufacturer = $command->getManufacturerId();
        }
        if ($command->getLastName() !== null) {
            $address->lastname = $command->getLastName();
        }
        if ($command->getFirstName() !== null) {
            $address->firstname = $command->getFirstName();
        }
        if ($command->getAddress() !== null) {
            $address->address1 = $command->getAddress();
        }
        if ($command->getAddress2() !== null) {
            $address->address2 = $command->getAddress2();
        }
        if ($command->getPostCode() !== null) {
            $address->postcode = $command->getPostCode();
        }
        if ($command->getCity() !== null) {
            $address->city = $command->getCity();
        }
        if ($command->getCountryId() !== null) {
            $address->id_country = $command->getCountryId();
        }
        if ($command->getStateId() !== null) {
            $address->id_state = $command->getStateId();
        } elseif ($command->getCountryId() !== null) {
            // If country was changed but not state we check if state value needs to be reset
            $country = new Country($command->getCountryId());
            if (! $country->contains_states) {
                $address->id_state = 0;
            }
        }
        if ($command->getHomePhone() !== null) {
            $address->phone = $command->getHomePhone();
        }
        if ($command->getMobilePhone() !== null) {
            $address->phone_mobile = $command->getMobilePhone();
        }
        if ($command->getOther() !== null) {
            $address->other = $command->getOther();
        }
        if ($command->getDni() !== null) {
            $address->dni = $command->getDni();
        }
    }
}
