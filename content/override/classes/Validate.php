<?php

class Validate extends ValidateCore
{

    /**
     * Check for zip code format validity.
     *
     * @param string $zip_code zip code format to validate
     *
     * @return bool Validity is ok or not
     */
    public static function isZipCodeFormat($zip_code)
    {
        if (!empty($zip_code)) {
	    // 20230807 - Anhadimos "[", "]" y ","
            return preg_match('/^[\[\],NLCnlc 0-9-]+$/', $zip_code);
        }

        return true;
    }

}

