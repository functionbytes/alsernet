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

namespace PrestaShop\PrestaShop\Adapter\OrderState\CommandHandler;

use OrderState;
use PrestaShop\PrestaShop\Core\Domain\OrderState\Command\EditOrderStateCommand;
use PrestaShop\PrestaShop\Core\Domain\OrderState\CommandHandler\EditOrderStateHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\OrderState\Exception\MissingOrderStateRequiredFieldsException;
use PrestaShop\PrestaShop\Core\Domain\OrderState\Exception\OrderStateException;

/**
 * Handles commands which edits given order state with provided data.
 *
 * @internal
 */
final class EditOrderStateHandler extends AbstractOrderStateHandler implements EditOrderStateHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(EditOrderStateCommand $command)
    {
        $orderStateId = $command->getOrderStateId();
        $orderState = new OrderState($orderStateId->getValue());

        $this->assertOrderStateWasFound($orderStateId, $orderState);

        $this->updateOrderStateWithCommandData($orderState, $command);

        $this->assertRequiredFieldsAreNotMissing($orderState);

        if ($orderState->validateFields(false) === false) {
            throw new OrderStateException('OrderState contains invalid field values');
        }

        if ($orderState->update() === false) {
            throw new OrderStateException('Failed to update order state');
        }
    }

    /**
     * @throws MissingOrderStateRequiredFieldsException
     */
    protected function assertRequiredFieldsAreNotMissing(OrderState $orderState)
    {
        // Check that we have templates for all languages when send_email is on
        $haveMissingTemplates = (
            ! is_array($orderState->template) ||
            count($orderState->template) != count(array_filter($orderState->template, 'strlen'))
        );

        if ($orderState->send_email === true && $haveMissingTemplates === true) {
            throw new MissingOrderStateRequiredFieldsException(['template'], 'One or more required fields for order state are missing. Missing fields are: template');
        }

        parent::assertRequiredFieldsAreNotMissing($orderState);
    }

    private function updateOrderStateWithCommandData(OrderState $orderState, EditOrderStateCommand $command)
    {
        if ($command->getName() !== null) {
            $orderState->name = $command->getName();
        }

        if ($command->getColor() !== null) {
            $orderState->color = $command->getColor();
        }

        if ($command->isLoggable() !== null) {
            $orderState->logable = $command->isLoggable();
        }

        if ($command->isHidden() !== null) {
            $orderState->hidden = $command->isHidden();
        }

        if ($command->isInvoice() !== null) {
            $orderState->invoice = $command->isInvoice();
        }

        if ($command->isSendEmailEnabled() !== null) {
            $orderState->send_email = $command->isSendEmailEnabled();
        }

        if ($command->isPdfInvoice() !== null) {
            $orderState->pdf_invoice = $command->isPdfInvoice();
        }

        if ($command->isPdfDelivery() !== null) {
            $orderState->pdf_delivery = $command->isPdfDelivery();
        }

        if ($command->isShipped() !== null) {
            $orderState->shipped = $command->isShipped();
        }

        if ($command->isPaid() !== null) {
            $orderState->paid = $command->isPaid();
        }

        if ($command->isDelivery() !== null) {
            $orderState->delivery = $command->isDelivery();
        }

        if ($command->getTemplate() !== null) {
            $orderState->template = $command->getTemplate();
        }
    }
}
