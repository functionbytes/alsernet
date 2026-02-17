<?php

namespace App\Services;

/**
 * Phone Number Validator Service
 *
 * Validates and normalizes phone numbers for Colombian format
 */
class PhoneNumberValidator
{
    /**
     * Validate and normalize a phone number
     *
     * @return array{valid: bool, normalized: string|null, formatted: string|null, error: string|null}
     */
    public function validate(?string $phone): array
    {
        if (empty($phone)) {
            return [
                'valid' => false,
                'normalized' => null,
                'formatted' => null,
                'error' => 'Phone number is empty',
            ];
        }

        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // Check if it's empty after cleaning
        if (empty($cleaned)) {
            return [
                'valid' => false,
                'normalized' => null,
                'formatted' => null,
                'error' => 'Phone number contains no digits',
            ];
        }

        // Normalize Colombian phone numbers
        $normalized = $this->normalizeColombianPhone($cleaned);

        if ($normalized === null) {
            return [
                'valid' => false,
                'normalized' => null,
                'formatted' => null,
                'error' => 'Invalid phone number format',
            ];
        }

        return [
            'valid' => true,
            'normalized' => $normalized,
            'formatted' => $this->formatPhone($normalized),
            'error' => null,
        ];
    }

    /**
     * Normalize Colombian phone number to E.164 format
     */
    protected function normalizeColombianPhone(string $phone): ?string
    {
        // Remove country code prefix if present
        $phone = preg_replace('/^\+57/', '', $phone);
        $phone = preg_replace('/^57/', '', $phone);

        // Colombian mobile phones: 10 digits starting with 3
        if (preg_match('/^3\d{9}$/', $phone)) {
            return '+57'.$phone;
        }

        // Colombian landlines: 7 digits (without area code)
        if (preg_match('/^\d{7}$/', $phone)) {
            // Assume Bogotá area code (1) if not specified
            return '+571'.$phone;
        }

        // Colombian landlines with area code: 10 digits
        if (preg_match('/^[1-8]\d{6,9}$/', $phone)) {
            return '+57'.$phone;
        }

        // International format
        if (preg_match('/^\+\d{10,15}$/', $phone)) {
            return $phone;
        }

        return null;
    }

    /**
     * Format phone number for display
     */
    protected function formatPhone(string $normalized): string
    {
        // Colombian mobile: +57 3XX XXX XXXX
        if (preg_match('/^\+573(\d{2})(\d{3})(\d{4})$/', $normalized, $matches)) {
            return '+57 3'.$matches[1].' '.$matches[2].' '.$matches[3];
        }

        // Colombian landline with area code: +57 X XXX XXXX
        if (preg_match('/^\+57([1-8])(\d{3})(\d{4})$/', $normalized, $matches)) {
            return '+57 '.$matches[1].' '.$matches[2].' '.$matches[3];
        }

        // Default: just return normalized
        return $normalized;
    }

    /**
     * Check if phone number is valid
     */
    public function isValid(?string $phone): bool
    {
        return $this->validate($phone)['valid'];
    }

    /**
     * Get normalized phone number
     */
    public function normalize(?string $phone): ?string
    {
        return $this->validate($phone)['normalized'];
    }

    /**
     * Get formatted phone number for display
     */
    public function format(?string $phone): ?string
    {
        return $this->validate($phone)['formatted'];
    }
}
