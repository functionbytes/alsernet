<?php

return [
    'types' => [
        'corta' => [
            'label' => 'Short Weapons',
            'description' => 'Required documentation for short weapons and Olympic shooting',
            'instructions' => 'Please upload the following documents to complete your short weapon order.',
        ],
        'rifle' => [
            'label' => 'Long Rifles',
            'description' => 'Required documentation for rifles and long weapons',
            'instructions' => 'Please upload the following documents to complete your rifle order.',
        ],
        'escopeta' => [
            'label' => 'Shotguns',
            'description' => 'Required documentation for hunting and sport shotguns',
            'instructions' => 'Please upload the following documents to complete your shotgun order.',
        ],
        'dni' => [
            'label' => 'Identity Documentation',
            'description' => 'Identity verification and documentation',
            'instructions' => 'Please upload the following identification documents.',
        ],
        'general' => [
            'label' => 'General Documentation',
            'description' => 'General order documentation',
            'instructions' => 'Please upload the following documents.',
        ],
    ],
    'requirements' => [
        'dni_frontal' => [
            'name' => 'ID - Front Side',
            'help_text' => 'Photo or scan of ID front side, must be clear and readable',
        ],
        'dni_trasera' => [
            'name' => 'ID - Back Side',
            'help_text' => 'Photo or scan of ID back side',
        ],
        'licencia' => [
            'name' => 'Weapons license',
            'help_text' => 'Valid license issued by competent authority',
        ],
        'licencia_corta' => [
            'name' => 'Short weapons license (type B) or Olympic shooting license (type F)',
            'help_text' => 'Valid license issued by competent authority',
        ],
        'licencia_rifle' => [
            'name' => 'Long rifle license (type D)',
            'help_text' => 'Valid license',
        ],
        'licencia_escopeta' => [
            'name' => 'Shotgun license (type E)',
            'help_text' => 'Valid license',
        ],
        'documento' => [
            'name' => 'Identity document',
            'help_text' => 'Valid identification document',
        ],
    ],
];
