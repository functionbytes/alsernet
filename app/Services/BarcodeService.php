<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Generate a barcode from location code and section code
     * Format: LOCATION_CODE + SECTION_LEVEL + sequential number
     *
     * @param string $locationCode - The location code (e.g., "PAB01")
     * @param string $sectionCode - The section code (e.g., "SEC-1")
     * @param int $sectionLevel - The section level number
     * @return string
     */
    public static function generateFromLocationAndSection(string $locationCode, string $sectionCode, int $sectionLevel): string
    {
        // Remove special characters and convert to uppercase
        $cleanLocation = preg_replace('/[^A-Z0-9]/i', '', strtoupper($locationCode));
        $cleanSection = preg_replace('/[^A-Z0-9]/i', '', strtoupper($sectionCode));

        // Create barcode: LOCATION-SECTION-LEVEL (e.g., PAB01-SEC1-01)
        // For CODE128 compatibility, we can use alphanumeric format
        $barcode = sprintf('%s-%s-%02d', $cleanLocation, $cleanSection, $sectionLevel);

        return $barcode;
    }

    /**
     * Generate a numeric barcode (for CODE128 or EAN compatibility)
     * Uses hash-based approach
     *
     * @param string $locationCode
     * @param string $sectionCode
     * @param int $sectionLevel
     * @return string
     */
    public static function generateNumericBarcode(string $locationCode, string $sectionCode, int $sectionLevel): string
    {
        // Create a string from the inputs
        $input = strtoupper($locationCode . $sectionCode . $sectionLevel);

        // Use hash to generate a numeric barcode
        $hash = crc32($input);

        // Convert to absolute value and take first 12 digits (for EAN-13 format)
        $numeric = abs($hash) % 9999999999999;

        // Pad to 12 digits and return
        return str_pad($numeric, 12, '0', STR_PAD_LEFT);
    }

    /**
     * Generate alphanumeric barcode with location warehouse info
     *
     * @param string $locationCode
     * @param string $warehouseCode
     * @param string $floorCode
     * @param string $sectionCode
     * @return string
     */
    public static function generateComprehensiveBarcode(
        string $locationCode,
        string $warehouseCode,
        string $floorCode,
        string $sectionCode
    ): string
    {
        // Create comprehensive barcode: WH-FLOOR-LOC-SECTION
        $cleanWH = substr(preg_replace('/[^A-Z0-9]/i', '', strtoupper($warehouseCode)), 0, 3);
        $cleanFloor = substr(preg_replace('/[^A-Z0-9]/i', '', strtoupper($floorCode)), 0, 3);
        $cleanLocation = substr(preg_replace('/[^A-Z0-9]/i', '', strtoupper($locationCode)), 0, 5);
        $cleanSection = substr(preg_replace('/[^A-Z0-9]/i', '', strtoupper($sectionCode)), 0, 5);

        return sprintf('%s-%s-%s-%s', $cleanWH, $cleanFloor, $cleanLocation, $cleanSection);
    }

    /**
     * Validate if a string is a valid barcode format
     *
     * @param string $barcode
     * @return bool
     */
    public static function isValidBarcode(string $barcode): bool
    {
        // Allow alphanumeric with hyphens
        return preg_match('/^[A-Z0-9\-]+$/i', $barcode) && strlen($barcode) >= 3;
    }

    /**
     * Get barcode type/format suggestion based on length
     *
     * @param string $barcode
     * @return string
     */
    public static function getRecommendedBarcodeType(string $barcode): string
    {
        $length = strlen(str_replace('-', '', $barcode));

        if ($length <= 8) {
            return 'CODE128'; // Generic CODE128
        } elseif ($length <= 12) {
            return 'CODE128'; // CODE128 can handle it
        } elseif ($length <= 14) {
            return 'CODE128'; // Better for longer strings
        }

        return 'CODE128';
    }

    /**
     * Format barcode for display (add hyphens at intervals)
     *
     * @param string $barcode
     * @param int $intervalLength
     * @return string
     */
    public static function formatBarcodeForDisplay(string $barcode, int $intervalLength = 4): string
    {
        // Remove existing hyphens
        $clean = str_replace('-', '', $barcode);

        // Add hyphens at intervals
        $formatted = '';
        for ($i = 0; $i < strlen($clean); $i += $intervalLength) {
            if ($i > 0) {
                $formatted .= '-';
            }
            $formatted .= substr($clean, $i, $intervalLength);
        }

        return $formatted;
    }
}
