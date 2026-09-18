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

namespace PrestaShop\PrestaShop\Adapter\Customer\CommandHandler;

use Customer;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;
use PrestaShop\PrestaShop\Core\Domain\Customer\Command\EditCustomerCommand;
use PrestaShop\PrestaShop\Core\Domain\Customer\CommandHandler\EditCustomerHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Customer\Exception\CustomerDefaultGroupAccessException;
use PrestaShop\PrestaShop\Core\Domain\Customer\Exception\CustomerException;
use PrestaShop\PrestaShop\Core\Domain\Customer\Exception\DuplicateCustomerEmailException;
use PrestaShop\PrestaShop\Core\Domain\Customer\ValueObject\RequiredField;
use PrestaShop\PrestaShop\Core\Domain\ValueObject\Email;

/**
 * Handles commands which edits given customer with provided data.
 *
 * @internal
 */
final class EditCustomerHandler extends AbstractCustomerHandler implements EditCustomerHandlerInterface
{
    /**
     * @var Hashing
     */
    private $hashing;

    /**
     * @var string Value of legacy _COOKIE_KEY_
     */
    private $legacyCookieKey;

    /**
     * @param  string  $legacyCookieKey
     */
    public function __construct(Hashing $hashing, $legacyCookieKey)
    {
        $this->hashing = $hashing;
        $this->legacyCookieKey = $legacyCookieKey;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(EditCustomerCommand $command)
    {
        $customerId = $command->getCustomerId();
        $customer = new Customer($customerId->getValue());

        $this->assertCustomerWasFound($customerId, $customer);

        $this->assertCustomerWithUpdatedEmailDoesNotExist($customer, $command);
        $this->assertCustomerCanAccessDefaultGroup($customer, $command);

        $this->updateCustomerWithCommandData($customer, $command);

        // validateFieldsRequiredDatabase() below is using $_POST
        // to check if required fields are set
        if ($command->isPartnerOffersSubscribed() !== null) {
            $_POST[RequiredField::PARTNER_OFFERS] = $command->isPartnerOffersSubscribed();
        } elseif ($command->isNewsletterSubscribed() !== null) {
            $_POST[RequiredField::NEWSLETTER] = $command->isNewsletterSubscribed();
        }

        // before validation, we need to get the list of customer mandatory fields from the database
        // and set their current values (only if it is not being modified: if it is not in $_POST)
        $requiredFields = $customer->getFieldsRequiredDatabase();
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field['field_name'], $_POST)) {
                $_POST[$field['field_name']] = $customer->{$field['field_name']};
            }
        }

        $this->assertRequiredFieldsAreNotMissing($customer);

        if ($customer->validateFields(false) === false) {
            throw new CustomerException('Customer contains invalid field values');
        }

        if ($customer->update() === false) {
            throw new CustomerException('Failed to update customer');
        }
    }

    private function updateCustomerWithCommandData(Customer $customer, EditCustomerCommand $command)
    {
        if ($command->getGenderId() !== null) {
            $customer->id_gender = $command->getGenderId();
        }

        if ($command->getFirstName() !== null) {
            $customer->firstname = $command->getFirstName()->getValue();
        }

        if ($command->getLastName() !== null) {
            $customer->lastname = $command->getLastName()->getValue();
        }

        if ($command->getEmail() !== null) {
            $customer->email = $command->getEmail()->getValue();
        }

        if ($command->getPassword() !== null) {
            $hashedPassword = $this->hashing->hash(
                $command->getPassword()->getValue(),
                $this->legacyCookieKey
            );

            $customer->passwd = $hashedPassword;
        }

        if ($command->getBirthday() !== null) {
            $customer->birthday = $command->getBirthday()->getValue();
        }

        if ($command->isEnabled() !== null) {
            $customer->active = $command->isEnabled();
        }

        if ($command->isPartnerOffersSubscribed() !== null) {
            $customer->optin = $command->isPartnerOffersSubscribed();
        }

        if ($command->getGroupIds() !== null) {
            $customer->groupBox = $command->getGroupIds();
        }

        if ($command->getDefaultGroupId() !== null) {
            $customer->id_default_group = $command->getDefaultGroupId();
        }

        if ($command->isNewsletterSubscribed() !== null) {
            $customer->newsletter = $command->isNewsletterSubscribed();
        }

        $this->updateCustomerB2bData($customer, $command);
    }

    private function updateCustomerB2bData(Customer $customer, EditCustomerCommand $command)
    {
        if ($command->getCompanyName() !== null) {
            $customer->company = $command->getCompanyName();
        }

        if ($command->getSiretCode() !== null) {
            $customer->siret = $command->getSiretCode();
        }

        if ($command->getApeCode() !== null) {
            $customer->ape = $command->getApeCode()->getValue();
        }

        if ($command->getWebsite() !== null) {
            $customer->website = $command->getWebsite();
        }

        if ($command->getAllowedOutstandingAmount() !== null) {
            $customer->outstanding_allow_amount = $command->getAllowedOutstandingAmount();
        }

        if ($command->getMaxPaymentDays() !== null) {
            $customer->max_payment_days = $command->getMaxPaymentDays();
        }

        if ($command->getRiskId() !== null) {
            $customer->id_risk = $command->getRiskId();
        }
    }

    private function assertCustomerWithUpdatedEmailDoesNotExist(Customer $customer, EditCustomerCommand $command)
    {
        // if email is not being updated
        // then assertion is not needed
        if ($command->getEmail() === null) {
            return;
        }

        if ($command->getEmail()->isEqualTo(new Email($customer->email))) {
            return;
        }

        $customerByEmail = new Customer;
        $customerByEmail->getByEmail($command->getEmail()->getValue());

        if ($customerByEmail->id) {
            throw new DuplicateCustomerEmailException($command->getEmail(), sprintf('Customer with email "%s" already exists', $command->getEmail()->getValue()));
        }
    }

    private function assertCustomerCanAccessDefaultGroup(Customer $customer, EditCustomerCommand $command)
    {
        // if neither default group
        // nor group ids are being edited
        // then no need to assert
        if ($command->getDefaultGroupId() === null
            || $command->getGroupIds() === null
        ) {
            return;
        }

        if (! in_array($command->getDefaultGroupId(), $command->getGroupIds())) {
            throw new CustomerDefaultGroupAccessException(sprintf('Customer default group with id "%s" must be in access groups', $command->getDefaultGroupId()));
        }
    }
}
