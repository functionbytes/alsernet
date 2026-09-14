<?php

return [
    'accepted' => 'L\'attributo: :attribute deve essere accettato.',
    'active_url' => 'L\'attributo: :attribute non &egrave; un URL valido.',
    'after' => 'L\'attributo: :attribute deve essere una data successiva a :date.',
    'alpha' => 'L\'attributo: :attribute pu&ograve; contenere solo lettere.',
    'alpha_dash' => 'L\'attributo: :attribute pu&ograve; contenere solo lettere, numeri e trattini.',
    'alpha_num' => 'L\'attributo: :attribute pu&ograve; contenere solo lettere e numeri.',
    'array' => 'L\'attributo: :attribute deve essere un array.',
    'attributes' => [
        'options' => [
            'limit_value' => 'Valore limite',
            'limit_base' => 'Base limite',
            'limit_unit' => 'Unità di tempo limite',
            'api_key' => 'API key',
            'api_secret_key' => 'API secret key',
            'username' => 'username',
            'password' => 'password',
            'vendor_id' => 'ID venditore',
            'public_key' => 'key pubblica',
            'vendor_auth_code' => 'Codice di autorizzazione del venditore',
            'merchant_key' => 'Key del commerciante',
            'salt' => 'Salt',
            'payu_base_url' => 'PayU Base URL',
            'field' => 'campo elenco',
            'days_of_week' => 'giorni della settimana',
            'days_of_month' => 'giorni del mese',
        ],
        'quota_value' => 'Limite di invio',
        'quota_base' => 'Base tempo',
        'quota_unit' => 'Unit&agrave; di tempo',
        'lists_segments' => [
            0 => [
                'mail_list_uid' => 'Lista',
            ],
            1 => [
                'mail_list_uid' => 'Lista',
            ],
            2 => [
                'mail_list_uid' => 'Lista',
            ],
            3 => [
                'mail_list_uid' => 'Lista',
            ],
            4 => [
                'mail_list_uid' => 'Lista',
            ],
            5 => [
                'mail_list_uid' => 'Lista',
            ],
            6 => [
                'mail_list_uid' => 'Lista',
            ],
            7 => [
                'mail_list_uid' => 'Lista',
            ],
            8 => [
                'mail_list_uid' => 'Lista',
            ],
            9 => [
                'mail_list_uid' => 'Lista',
            ],
        ],
        'plan' => [
            'general' => [
                'name' => 'nome',
                'description' => 'descrizione',
                'currency_id' => 'valuta',
                'frequency_amount' => 'frequenza importo',
                'frequency_unit' => 'unità di frequenza',
                'price' => 'prezzo',
                'color' => 'colore',
                'vat' => 'IVA',
            ],
        ],
    ],
    'before' => 'L\'attributo: :attribute deve essere una data precedente a :date.',
    'between' => [
        'numeric' => 'L\'attributo: :attribute deve essere compreso tra :min e :max.',
        'file' => 'L\'attributo: :attribute deve essere compreso tra :min e :max kilobytes.',
        'string' => 'L\'attributo: :attribute deve essere compreso tra :min e :max caratteri.',
        'array' => 'L\'attributo: :attribute deve avere un valore compreso tra :min e :max elementi.',
    ],
    'boolean' => 'L\'attributo: :attribute il campo deve essere vero o falso.',
    'confirmed' => 'L\'attributo: :attribute la conferma non corrisponde.',
    'custom' => [
        'domain' => [
            'regex' => 'L\'attributo: :attribute deve essere un dominio o un nome host valido. Ad esempio: mydomain.com o mail.mydomain.com',
        ],
        'miss_main_field_tag' => [
            'required' => 'Tag del campo EMAIL mancante',
        ],
        'conflict_field_tags' => [
            'required' => 'I tag dei campi non possono essere gli stessi',
        ],
        'segment_conditions_empty' => [
            'required' => 'L\'elenco delle condizioni non pu&ograve; essere vuoto',
        ],
        'mysql_connection' => [
            'required' => 'Impossibile connettersi al server MySQL',
        ],
        'database_not_empty' => [
            'required' => 'Il database non &egrave; vuoto',
        ],
        'promo_code_not_valid' => [
            'required' => 'Il codice promozionale non &egrave; valido',
        ],
        'smtp_valid' => [
            'required' => 'Impossibile connettersi al server SMTP',
        ],
        'yaml_parse_error' => [
            'required' => 'Non &egrave; possibile analizzare yaml. Controllare la sintassi',
        ],
        'file_not_found' => [
            'required' => 'File non trovato.',
        ],
        'not_zip_archive' => [
            'required' => 'Il file non &egrave; un pacchetto zip.',
        ],
        'zip_archive_unvalid' => [
            'required' => 'Impossibile leggere il pacchetto.',
        ],
        'custom_criteria_empty' => [
            'required' => 'I criteri personalizzati non possono essere vuoti',
        ],
        'php_bin_path_invalid' => [
            'required' => 'Eseguibile PHP non valido. Ricontrollare.',
        ],
        'can_not_empty_database' => [
            'required' => 'Impossibile eliminare alcune tabelle, pulire manualmente il database e riprovare.',
        ],
        'recaptcha_invalid' => [
            'required' => 'Controllo reCAPTCHA non valido.',
        ],
        'captcha_invalid' => [
            'required' => 'Controllo CAPTCHA non valido.',
        ],
        'payment_method_not_valid' => [
            'required' => 'Qualcosa &egrave; andato storto nell\'impostazione del metodo di pagamento. Si prega di ricontrollare.',
        ],
        'email_already_subscribed' => [
            'required' => 'L\'e-mail &egrave; già stata registrata.',
        ],
        'mail_list_uid' => [
            'required' => 'La Mail List &egrave; necessaria.',
        ],
        'contact' => [
            'zip' => [
                'required' => 'Il codice postale &egrave; obbligatorio.',
            ],
        ],
    ],
    'date' => 'L\'attributo: :attribute non &egrave; una data valida.',
    'date_format' => 'L\'attributo: :attribute non corrisponde al formato :format.',
    'different' => 'L\'attributo: :attribute e :other devono essere diversi.',
    'digits' => 'L\'attributo: :attribute deve essere :digits cifre.',
    'digits_between' => 'L\'attributo: :attribute deve essere compreso tra :min e :max cifre.',
    'distinct' => 'L\'attributo: :attribute il campo ha un valore duplicato.',
    'email' => 'Il campo deve essere un indirizzo e-mail valido.',
    'exists' => 'L\'attributo :attribute selezionato non &egrave; valido.',
    'filled' => 'L\'attributo: :attribute il campo &egrave; obbligatorio.',
    'image' => 'L\'attributo: :attribute deve essere un\'immagine.',
    'in' => 'L\'attributo :attribute selezionato non &egrave; valido.',
    'in_array' => 'L\'attributo: :attribute non esiste in :other.',
    'integer' => 'L\'attributo: :attribute deve essere un numero intero.',
    'ip' => 'L\'attributo: :attribute deve essere un indirizzo IP valido.',
    'json' => 'L\'attributo: :attribute deve essere una stringa JSON valida.',
    'license' => 'La licenza non &egrave; valida.',
    'license_error' => ':error',
    'max' => [
        'numeric' => 'L\'attributo: :attribute non pu&ograve; essere maggiore di :max.',
        'file' => 'L\'attributo: :attribute non pu&ograve; essere maggiore di :max kilobytes.',
        'string' => 'L\'attributo: :attribute non pu&ograve; essere maggiore di :max caratteri.',
        'array' => 'L\'attributo: :attribute non pu&ograve; avere pi&ugrave; di :max elementi.',
    ],
    'mimes' => 'L\'attributo: :attribute deve essere un file di tipo: :values.',
    'min' => [
        'numeric' => 'L\'attributo: :attribute deve essere almeno :min.',
        'file' => 'L\'attributo: :attribute deve essere almeno :min kilobytes.',
        'string' => 'L\'attributo: :attribute deve essere almeno :min caratteri.',
        'array' => 'L\'attributo: :attribute deve essere almeno :min elementi.',
    ],
    'not_in' => 'L\'attributo :attribute selezionato non &egrave; valido.',
    'numeric' => 'L\'attributo: :attribute deve essere un numero.',
    'present' => 'L\'attributo: :attribute deve essere presente.',
    'regex' => 'L\'attributo: :attribute il formato non &egrave; valido.',
    'required' => 'L\'attributo: :attribute il campo &egrave; obbligatorio.',
    'required_if' => 'L\'attributo: :attribute &egrave; necessario quando :other &egrave; :value.',
    'required_unless' => 'L\'attributo: :attribute Il campo &egrave; obbligatorio a meno che :other  non sia in  :values.',
    'required_with' => 'L\'attributo: :attribute Il campo &egrave; obbligatorio quando :values &egrave; presente.',
    'required_with_all' => 'L\'attributo: :attribute Il campo &egrave; obbligatorio quando :values &egrave; presente.',
    'required_without' => 'L\'attributo: :attribute &egrave; richiesto quando :values non &egrave; presente..',
    'required_without_all' => 'L\'attributo: :attribute &egrave; richiesto quando nessuno dei  :values sono presenti.',
    'same' => 'L\'attributo: :attribute e :other devono corrispondere.',
    'size' => [
        'numeric' => 'L\'attributo: :attribute deve essere :size.',
        'file' => 'L\'attributo: :attribute deve essere :size kilobytes.',
        'string' => 'L\'attributo: :attribute deve essere :size caratteri.',
        'array' => 'L\'attributo: :attribute deve contenere :size elemrnti.',
    ],
    'string' => 'L\'attributo: :attribute deve essere una stringa.',
    'substring' => 'L\'attributo: :tag non &egrave; stato trovato in :attribute.',
    'timezone' => 'L\'attributo: :attribute deve essere una zona valida.',
    'unique' => 'L\'attributo: :attribute il campo &egrave; gi&agrave; stato occupato.',
    'url' => 'L\'attributo: :attribute il formato non &egrave; valido.',
];
