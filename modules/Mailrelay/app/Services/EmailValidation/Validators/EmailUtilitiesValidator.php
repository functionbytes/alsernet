<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use AshAllenDesign\EmailUtilities\Email;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class EmailUtilitiesValidator implements ValidatorInterface
{
    public function validate(string $email): array
    {
        try {
            // Create Email instance from AshAllenDesign\EmailUtilities
            $emailUtility = new Email($email);

            // Check if email is disposable
            $isDisposable = $emailUtility->isDisposable();

            // Check if email is a role account
            $isRoleAccount = $emailUtility->isRoleAccount();

            // Determine validity - invalid if disposable or role account
            $isValid = ! $isDisposable && ! $isRoleAccount;

            // Calculate score based on checks
            $score = $this->calculateScore($isDisposable, $isRoleAccount);

            // Build details array
            $details = [
                'is_disposable' => $isDisposable,
                'is_role_account' => $isRoleAccount,
                'domain' => $emailUtility->domain(),
                'local_part' => $emailUtility->localPart(),
            ];

            // Generate message
            $message = $this->generateMessage($isDisposable, $isRoleAccount);

            return [
                'valid' => $isValid,
                'score' => $score,
                'details' => $details,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'score' => 0,
                'details' => [
                    'error' => $e->getMessage(),
                ],
                'message' => 'Email utilities validation failed',
            ];
        }
    }

    public function getName(): string
    {
        return 'email_utilities';
    }

    private function calculateScore(bool $isDisposable, bool $isRoleAccount): int
    {
        // If both issues are present, score is 0
        if ($isDisposable && $isRoleAccount) {
            return 0;
        }

        // If either is disposable or role account, score is 50 (risky)
        if ($isDisposable || $isRoleAccount) {
            return 50;
        }

        // Otherwise, score is 100 (valid)
        return 100;
    }

    private function generateMessage(bool $isDisposable, bool $isRoleAccount): string
    {
        if ($isDisposable && $isRoleAccount) {
            return 'Email is both disposable and a role account';
        }

        if ($isDisposable) {
            return 'Email is from a disposable domain';
        }

        if ($isRoleAccount) {
            return 'Email is a role account';
        }

        return 'Email passed utilities validation';
    }
}
