<?php

namespace App\Services;

/**
 * WhatsApp Number Validator Service
 *
 * Validates WhatsApp numbers and checks if they're active
 */
class WhatsAppValidator
{
    protected PhoneNumberValidator $phoneValidator;

    public function __construct(PhoneNumberValidator $phoneValidator)
    {
        $this->phoneValidator = $phoneValidator;
    }

    /**
     * Validate WhatsApp number
     *
     * @return array{valid: bool, normalized: string|null, whatsapp_ready: bool, error: string|null}
     */
    public function validate(?string $phone): array
    {
        // First validate as regular phone number
        $phoneValidation = $this->phoneValidator->validate($phone);

        if (! $phoneValidation['valid']) {
            return [
                'valid' => false,
                'normalized' => null,
                'whatsapp_ready' => false,
                'error' => $phoneValidation['error'],
            ];
        }

        // WhatsApp validation logic
        // Note: Real WhatsApp validation would require API call to WhatsApp Business API
        // For now, we assume all valid mobile numbers can have WhatsApp
        $normalized = $phoneValidation['normalized'];
        $isWhatsAppReady = $this->isWhatsAppReady($normalized);

        return [
            'valid' => true,
            'normalized' => $normalized,
            'whatsapp_ready' => $isWhatsAppReady,
            'error' => null,
        ];
    }

    /**
     * Check if number is WhatsApp ready
     */
    protected function isWhatsAppReady(string $normalized): bool
    {
        // Colombian mobile numbers (start with +573) are likely WhatsApp ready
        if (preg_match('/^\+573\d{9}$/', $normalized)) {
            return true;
        }

        // Other mobile numbers might be WhatsApp ready
        // In a real implementation, this would check against WhatsApp Business API
        if (preg_match('/^\+\d{10,15}$/', $normalized)) {
            // Assume mobile numbers are WhatsApp ready
            // Landlines typically are not
            return ! $this->isLandline($normalized);
        }

        return false;
    }

    /**
     * Check if number is a landline
     */
    protected function isLandline(string $normalized): bool
    {
        // Colombian landlines start with +57 followed by area code 1-8
        return preg_match('/^\+57[1-8]\d{6,9}$/', $normalized);
    }

    /**
     * Check if WhatsApp number is valid
     */
    public function isValid(?string $phone): bool
    {
        return $this->validate($phone)['valid'];
    }

    /**
     * Check if number has WhatsApp
     */
    public function hasWhatsApp(?string $phone): bool
    {
        $result = $this->validate($phone);

        return $result['valid'] && $result['whatsapp_ready'];
    }

    /**
     * Get WhatsApp link for number
     */
    public function getWhatsAppLink(?string $phone, ?string $message = null): ?string
    {
        $result = $this->validate($phone);

        if (! $result['valid'] || ! $result['whatsapp_ready']) {
            return null;
        }

        // Remove + from number for WhatsApp API
        $cleanNumber = str_replace('+', '', $result['normalized']);

        $link = "https://wa.me/{$cleanNumber}";

        if ($message) {
            $link .= '?text='.urlencode($message);
        }

        return $link;
    }
}
