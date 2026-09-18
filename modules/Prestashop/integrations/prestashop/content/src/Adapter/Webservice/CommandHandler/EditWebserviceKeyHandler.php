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

namespace PrestaShop\PrestaShop\Adapter\Webservice\CommandHandler;

use PrestaShop\PrestaShop\Core\Domain\Webservice\Command\EditWebserviceKeyCommand;
use PrestaShop\PrestaShop\Core\Domain\Webservice\CommandHandler\EditWebserviceKeyHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Webservice\Exception\WebserviceConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Webservice\Exception\WebserviceException;
use PrestaShop\PrestaShop\Core\Domain\Webservice\Exception\WebserviceKeyNotFoundException;
use PrestaShop\PrestaShop\Core\Domain\Webservice\ValueObject\WebserviceKeyId;
use WebserviceKey;

/**
 * Handles command that edits legacy WebserviceKey
 *
 * @internal
 */
final class EditWebserviceKeyHandler extends AbstractWebserviceKeyHandler implements EditWebserviceKeyHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(EditWebserviceKeyCommand $command)
    {
        $webserviceKey = $this->getLegacyWebserviceKey($command->getWebserviceKeyId());

        $this->updateLegacyWebserviceKeyWithCommandData($webserviceKey, $command);
    }

    /**
     * @return WebserviceKey
     */
    private function getLegacyWebserviceKey(WebserviceKeyId $webserviceKeyId)
    {
        $webserviceKey = new WebserviceKey($webserviceKeyId->getValue());

        if ($webserviceKeyId->getValue() !== $webserviceKey->id) {
            throw new WebserviceKeyNotFoundException(sprintf('Webservice key with id "%s was not found', $webserviceKeyId->getValue()));
        }

        return $webserviceKey;
    }

    private function updateLegacyWebserviceKeyWithCommandData(
        WebserviceKey $webserviceKey,
        EditWebserviceKeyCommand $command
    ) {
        if ($command->getKey() !== null) {
            $webserviceKey->key = $command->getKey()->getValue();
        }

        if ($command->getDescription() !== null) {
            $webserviceKey->description = $command->getDescription();
        }

        if ($command->getStatus() !== null) {
            $webserviceKey->active = $command->getStatus();
        }

        if ($webserviceKey->validateFields(false) === false) {
            throw new WebserviceConstraintException('One or more fields are invalid in WebserviceKey');
        }

        if ($webserviceKey->update() === false) {
            throw new WebserviceException(sprintf('Failed to update WebserviceKey with id "%s"', $webserviceKey->id));
        }

        if ($command->getShopAssociation() !== null) {
            $this->associateWithShops($webserviceKey, $command->getShopAssociation());
        }

        if ($command->getPermissions() !== null) {
            $this->setPermissionsForWebserviceKey($webserviceKey, $command->getPermissions());
        }
    }
}
