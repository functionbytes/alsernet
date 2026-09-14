<?php

return [
    'accepted' => ':attribute debe ser aceptado.',
    'active_url' => ':attribute no es una URL valida.',
    'after' => ':attribute debe ser una fecha posterior a :date.',
    'alpha' => ':attribute solo puede contener letras.',
    'alpha_dash' => ':attribute solo puede contener letras, números y guiones.',
    'alpha_num' => ':attribute solo puede contener letras y números.',
    'array' => ':attribute debe ser un arreglo',
    'attributes' => [
    ],
    'before' => ':attribute debe ser una fecha antes del :date.',
    'between' => [
        'numeric' => ':attribute debe estar entre :min y :max.',
        'file' => ':attribute debe estar entre :min y :max kilobytes.',
        'string' => ':attribute debe contener entre :min y :max caracteres.',
        'array' => ':atributo debe tener entre :min y :max items.',
    ],
    'boolean' => ' :atributo el campo debe ser verdadero o falso.',
    'confirmed' => ':attribute de confirmacion no coincide.',
    'custom' => [
        'miss_main_field_tag' => [
            'required' => 'Falta la etiqueta de campo EMAIL',
        ],
        'conflict_field_tags' => [
            'required' => 'Las etiquetas del campo no pueden ser las mismas',
        ],
        'segment_conditions_empty' => [
            'required' => 'La lista de condiciones no puede estar vacía',
        ],
        'mysql_connection' => [
            'required' => 'No se puede conectar al servidor MySQL',
        ],
        'database_not_empty' => [
            'required' => 'La base de datos no está vacía',
        ],
        'promo_code_not_valid' => [
            'required' => 'El código de descuento no es válido',
        ],
        'smtp_valid' => [
            'required' => 'No se puede conectar al servidor SMTP',
        ],
        'yaml_parse_error' => [
            'required' => 'No se puede analizar yaml. Compruebe la sintaxis',
        ],
        'file_not_found' => [
            'required' => 'Archivo no encontrado.',
        ],
        'not_zip_archive' => [
            'required' => 'El archivo no es un paquete zip.',
        ],
        'zip_archive_unvalid' => [
            'required' => 'No se puede leer el paquete.',
        ],
        'custom_criteria_empty' => [
            'required' => 'Los criterios personalizados no pueden estar vacíos',
        ],
        'php_bin_path_invalid' => [
            'required' => 'Ejecutable PHP no válido. Por favor revise de nuevo.',
        ],
        'can_not_empty_database' => [
            'required' => 'No puede eliminar determinadas tablas, por favor, limpie su base de datos y vuelva a intentarlo.',
        ],
        'recaptcha_invalid' => [
            'required' => 'Comprobación reCAPTCHA no válida.',
        ],
        'payment_method_not_valid' => [
            'required' => 'Algo salió mal con la configuración del método de pago. Por favor revise de nuevo.',
        ],
    ],
    'date' => ':attribute no es una fecha valida.',
    'date_format' => ':attribute no coincide con el formato :format.',
    'different' => ':attribute y :other deben ser diferentes.',
    'digits' => ':attribute deben ser :digits digitos.',
    'digits_between' => ':attribute debe estar entre :min y :max digitos.',
    'distinct' => ':attribute tiene un valor de campo duplicado.',
    'email' => 'El campo debe ser una direccion de email valida.',
    'exists' => ':attribute es invalido.',
    'filled' => ':attribute es un dato requerido.',
    'image' => ':attribute debe ser una imagen.',
    'in' => ':attribute es invalido.',
    'in_array' => ':attribute archivo no existe en :other.',
    'integer' => ':attribute debe ser un entero.',
    'ip' => ':attribute debe ser una dirección de IP valida.',
    'json' => ':attribute debe ser un string JSON valido.',
    'license' => 'Licencia invalida.',
    'license_error' => ':error',
    'max' => [
        'numeric' => ':attribute no puede ser mayor que :max.',
        'file' => ':attribute no puede ser mayor que :max kilobytes.',
        'string' => ':attribute no puede ser mayor que :max caracteres.',
        'array' => ':attribute no puede contener mas de :max items.',
    ],
    'mimes' => ':attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'numeric' => ':attribute debe ser al menos :min.',
        'file' => ':attribute al menos debe contener :min kilobytes.',
        'string' => ':attribute debe contener al menos :min caracteres.',
        'array' => ':attribute debe contener al menos :min items.',
    ],
    'not_in' => ':attribute es invalido.',
    'numeric' => ':attribute debe ser un numero.',
    'present' => ':attribute debe estar presente.',
    'regex' => ':attribute el formato es invalido.',
    'required' => ':attribute requerido',
    'required_if' => ':attribute es obligatorio cuando :other es :value.',
    'required_unless' => ':attribute es obligatorio a menos que :other es en :values.',
    'required_with' => ':attribute requerido cuando :values esta presente.',
    'required_with_all' => ':attribute requerido cuando :values esta presente.',
    'required_without' => ':attribute requerido :values no presente.',
    'required_without_all' => ':attribute requerido cuando :values esta  presente.',
    'same' => ':attribute y :other deben coincidir con.',
    'size' => [
        'numeric' => ':attribute debe tener :size.',
        'file' => ':attribute debe tener :size kilobytes.',
        'string' => ':attribute debe tener :size caracteres.',
        'array' => ':attribute debe contener :size items.',
    ],
    'string' => ':attribute debe ser un string.',
    'substring' => ' :tag la etiqueta no funciona en :attribute.',
    'timezone' => ':attribute debe estar en una zona valida.',
    'unique' => 'El campo ya ha sido tomado.',
    'url' => ':attribute formato invalido.',
];
