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

namespace PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler;

use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Address\Command\AddManufacturerAddressCommand;
use PrestaShop\PrestaShop\Core\Domain\Address\Command\EditManufacturerAddressCommand;
use PrestaShop\PrestaShop\Core\Domain\Address\Exception\AddressConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Address\ValueObject\AddressId;

/**
 * Handles submitted manufacturer address form data
 */
final class ManufacturerAddressFormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        /** @var AddressId $addressId */
        $addressId = $this->commandBus->handle(new AddManufacturerAddressCommand(
            $data['last_name'],
            $data['first_name'],
            $data['address'],
            $data['id_country'],
            $data['city'],
            $data['id_manufacturer'],
            $data['address2'],
            $data['post_code'],
            $data['id_state'],
            $data['home_phone'],
            $data['mobile_phone'],
            $data['other'],
            $data['dni']
        ));

        return $addressId->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function update($addressId, array $data)
    {
        $command = new EditManufacturerAddressCommand((int) $addressId);
        $this->fillCommandWithData($command, $data);

        $this->commandBus->handle($command);
    }

    /**
     * Fills EditManufacturerAddressCommand with form data
     *
     *
     * @throws AddressConstraintException
     */
    private function fillCommandWithData(EditManufacturerAddressCommand $command, array $data)
    {
        if ($data['id_manufacturer'] !== null) {
            $command->setManufacturerId($data['id_manufacturer']);
        }
        if ($data['last_name'] !== null) {
            $command->setLastName($data['last_name']);
        }
        if ($data['first_name'] !== null) {
            $command->setFirstName($data['first_name']);
        }
        if ($data['address'] !== null) {
            $command->setAddress($data['address']);
        }
        if ($data['id_country'] !== null) {
            $command->setCountryId($data['id_country']);
        }
        if ($data['city'] !== null) {
            $command->setCity($data['city']);
        }
        if ($data['address2'] !== null) {
            $command->setAddress2($data['address2']);
        }
        if ($data['post_code'] !== null) {
            $command->setPostCode($data['post_code']);
        }
        if ($data['id_state'] !== null) {
            $command->setStateId($data['id_state']);
        }
        if ($data['home_phone'] !== null) {
            $command->setHomePhone($data['home_phone']);
        }
        if ($data['mobile_phone'] !== null) {
            $command->setMobilePhone($data['mobile_phone']);
        }
        if ($data['other'] !== null) {
            $command->setOther($data['other']);
        }
        if ($data['dni'] !== null) {
            $command->setDni($data['dni']);
        }
    }
}
