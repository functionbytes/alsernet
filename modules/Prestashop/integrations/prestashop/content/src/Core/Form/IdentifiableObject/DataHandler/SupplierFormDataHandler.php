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
use PrestaShop\PrestaShop\Core\Domain\Supplier\Command\AddSupplierCommand;
use PrestaShop\PrestaShop\Core\Domain\Supplier\Command\EditSupplierCommand;
use PrestaShop\PrestaShop\Core\Domain\Supplier\ValueObject\SupplierId;
use PrestaShop\PrestaShop\Core\Image\Uploader\ImageUploaderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handles submitted supplier form data
 */
final class SupplierFormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var ImageUploaderInterface
     */
    private $imageUploader;

    public function __construct(
        CommandBusInterface $commandBus,
        ImageUploaderInterface $imageUploader
    ) {
        $this->commandBus = $commandBus;
        $this->imageUploader = $imageUploader;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        if (! isset($data['shop_association']) || ! $data['shop_association']) {
            $data['shop_association'] = [];
        }

        /** @var SupplierId $supplierId */
        $supplierId = $this->commandBus->handle(new AddSupplierCommand(
            $data['name'],
            $data['address'],
            $data['city'],
            (int) $data['id_country'],
            (bool) $data['is_enabled'],
            $data['description'],
            $data['meta_title'],
            $data['meta_description'],
            $data['meta_keyword'],
            $data['shop_association'],
            $data['address2'],
            $data['post_code'],
            isset($data['id_state']) ? (int) $data['id_state'] : null,
            $data['phone'],
            $data['mobile_phone'],
            $data['dni']
        ));

        /** @var UploadedFile $uploadedLogo */
        $uploadedLogo = $data['logo'];

        if ($uploadedLogo instanceof UploadedFile) {
            $this->imageUploader->upload($supplierId->getValue(), $uploadedLogo);
        }

        return $supplierId->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function update($supplierId, array $data)
    {
        /** @var UploadedFile $uploadedLogo */
        $uploadedLogo = $data['logo'];
        $logo = null;

        if ($uploadedLogo instanceof UploadedFile) {
            $this->imageUploader->upload($supplierId, $uploadedLogo);
        }

        $command = new EditSupplierCommand($supplierId);
        $this->fillCommandWithData($command, $data);

        $this->commandBus->handle($command);
    }

    /**
     * Fills command with provided data
     */
    private function fillCommandWithData(EditSupplierCommand $command, array $data)
    {
        if ($data['name'] !== null) {
            $command->setName($data['name']);
        }
        if ($data['description'] !== null) {
            $command->setLocalizedDescriptions($data['description']);
        }
        if ($data['phone'] !== null) {
            $command->setPhone($data['phone']);
        }
        if ($data['mobile_phone'] !== null) {
            $command->setMobilePhone($data['mobile_phone']);
        }
        if ($data['address'] !== null) {
            $command->setAddress($data['address']);
        }
        if ($data['address2'] !== null) {
            $command->setAddress2($data['address2']);
        }
        if ($data['post_code'] !== null) {
            $command->setPostCode($data['post_code']);
        }
        if ($data['city'] !== null) {
            $command->setCity($data['city']);
        }
        if ($data['id_country'] !== null) {
            $command->setCountryId((int) $data['id_country']);
        }
        if ($data['meta_title'] !== null) {
            $command->setLocalizedMetaTitles($data['meta_title']);
        }
        if ($data['meta_description'] !== null) {
            $command->setLocalizedMetaDescriptions($data['meta_description']);
        }
        if ($data['meta_keyword'] !== null) {
            $command->setLocalizedMetaKeywords($data['meta_keyword']);
        }
        if ($data['is_enabled'] !== null) {
            $command->setEnabled((bool) $data['is_enabled']);
        }
        if ($data['dni'] !== null) {
            $command->setDni($data['dni']);
        }

        if (isset($data['id_state'])) {
            $command->setStateId((int) $data['id_state']);
        }

        if (isset($data['shop_association'])) {
            $shopAssociation = $data['shop_association'] ?: [];
            $shopAssociation = array_map(function ($shopId) {
                return (int) $shopId;
            }, $shopAssociation);

            $command->setAssociatedShops($shopAssociation);
        }
    }
}
