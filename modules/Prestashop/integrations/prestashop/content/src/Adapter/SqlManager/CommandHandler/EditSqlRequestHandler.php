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

namespace PrestaShop\PrestaShop\Adapter\SqlManager\CommandHandler;

use PrestaShop\PrestaShop\Core\Domain\SqlManagement\Command\EditSqlRequestCommand;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\CommandHandler\EditSqlRequestHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\Exception\CannotEditSqlRequestException;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\Exception\SqlRequestException;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\Exception\SqlRequestNotFoundException;
use PrestaShopException;
use RequestSql;

/**
 * Class EditSqlRequestHandler is responsible for updating SqlRequest.
 *
 * @internal
 */
final class EditSqlRequestHandler extends AbstractSqlRequestHandler implements EditSqlRequestHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     *
     * @throws CannotEditSqlRequestException
     * @throws SqlRequestException
     * @throws SqlRequestNotFoundException
     */
    public function handle(EditSqlRequestCommand $command)
    {
        $this->assertSqlQueryIsValid($command->getSql());

        try {
            $entity = new RequestSql($command->getSqlRequestId()->getValue());

            if ($entity->id <= 0) {
                throw new SqlRequestNotFoundException(sprintf('SqlRequest with id "%s" was not found for edit', $command->getSqlRequestId()->getValue()));
            }

            if ($command->getName() !== null) {
                $entity->name = $command->getName();
            }

            if ($command->getSql() !== null) {
                $entity->sql = $command->getSql();
            }

            if ($entity->update() === false) {
                throw new CannotEditSqlRequestException(sprintf('Error occurred when updating SqlRequest with id "%s"', $command->getSqlRequestId()->getValue()));
            }
        } catch (PrestaShopException $e) {
            throw new SqlRequestException(sprintf('Error occurred when updating SqlRequest with id "%s"', $command->getSqlRequestId()->getValue()));
        }
    }
}
