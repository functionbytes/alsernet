<?php

class CustomerAddressForm extends CustomerAddressFormCore
{
    public function fillWith(array $params = [])
    {
        $idLang = Context::getContext()->language->id;

        $countryByLang = [
            1 => 6,
            2 => 17,
            3 => 8,
            4 => 15,
            5 => 1,
            6 => 10,
        ];

        if (!isset($params['id_country']) && isset($countryByLang[$idLang])) {
            $params['id_country'] = $countryByLang[$idLang];
        }

        if (isset($params['id_country']) && $params['id_country'] != $this->formatter->getCountry()->id) {
            $this->formatter->setCountry(new Country(
                $params['id_country'],
                $idLang
            ));

        }

        return parent::fillWith($params);
    }
}