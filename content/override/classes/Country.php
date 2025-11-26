<?php
class Country extends CountryCore
{
    /**
     * Replace letters of zip code format And check this format on the zip code.
     *
     * @param string $zipCode zip code
     *
     * @return bool Indicates whether the zip code is correct
     */
    public function checkZipCode($zipCode)
    {
        if (empty($this->zip_code_format)) {
            return true;
        }

        // 20230807 - España Peninsular no puede solapar/validar Baleares, Canarias ni Ceuta y Melilla
        if (!empty($this->id) && $this->id == _PSALV_COUNTRY_ID_ES_PENINSULA_) {
            $possibles = ["[1,2,4]NNNN", "0[1-6,8,9]NNN", "3[0-4,6,7,9]NNN", "50NNN"];
            foreach ($possibles as $zipEs) {
                $zipRegexp = '/^' . $zipEs . '$/ui';
                if (preg_match_all('/\[(.*?)\]/', $zipEs, $match) >= 1) {
                    foreach($match[0] as $accord) {
                        $zipRegexp = str_replace($accord, $accord, $zipRegexp);
                    }
                }
                $zipRegexp = str_replace('N', '[0-9]', $zipRegexp);
                $zipRegexp = str_replace('L', '[a-zA-Z]', $zipRegexp);
                $zipRegexp = str_replace('C', $this->iso_code, $zipRegexp);
                if ((bool) preg_match($zipRegexp, $zipCode)) {
                    return true;
                }
            }
            return false;
        }

        $zipRegexp = '/^' . $this->zip_code_format . '$/ui';

        // 20230807 - Usar RegExpr
        if (preg_match_all('/\[(.*?)\]/', $this->zip_code_format, $match) >= 1) {
            foreach($match[0] as $accord) {
                $zipRegexp = str_replace($accord, $accord, $zipRegexp);
            }
        }

        $zipRegexp = str_replace('N', '[0-9]', $zipRegexp);
        $zipRegexp = str_replace('L', '[a-zA-Z]', $zipRegexp);
        $zipRegexp = str_replace('C', $this->iso_code, $zipRegexp);

        return (bool) preg_match($zipRegexp, $zipCode);
    }
}

