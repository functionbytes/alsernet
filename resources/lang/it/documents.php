<?php

return [
    'types' => [
        'corta' => [
            'label' => 'Armi Corte',
            'description' => 'Documentazione necessaria per armi corte e tiro olimpico',
            'instructions' => 'Si prega di caricare i seguenti documenti per completare l\'ordine di arma corta.',
        ],
        'rifle' => [
            'label' => 'Armi Lunghe (Fucili)',
            'description' => 'Documentazione per fucili e armi lunghe',
            'instructions' => 'Si prega di caricare i seguenti documenti per completare l\'ordine di fucile.',
        ],
        'escopeta' => [
            'label' => 'Fucili a Canna Liscia',
            'description' => 'Documentazione per fucili da caccia e sportivi',
            'instructions' => 'Si prega di caricare i seguenti documenti per completare l\'ordine di fucile a canna liscia.',
        ],
        'dni' => [
            'label' => 'Documentazione di Identità',
            'description' => 'Verifica dell\'identità e documentazione',
            'instructions' => 'Si prega di caricare i seguenti documenti di identificazione.',
        ],
        'general' => [
            'label' => 'Documentazione Generale',
            'description' => 'Documentazione generale dell\'ordine',
            'instructions' => 'Si prega di caricare i seguenti documenti.',
        ],
    ],
    'requirements' => [
        'dni_frontal' => [
            'name' => 'Documento di Identità - Fronte',
            'help_text' => 'Foto o scansione del lato anteriore del documento, deve essere chiara e leggibile',
        ],
        'dni_trasera' => [
            'name' => 'Documento di Identità - Retro',
            'help_text' => 'Foto o scansione del lato posteriore del documento',
        ],
        'licencia' => [
            'name' => 'Licenza per armi',
            'help_text' => 'Licenza valida rilasciata dall\'autorità competente',
        ],
        'licencia_corta' => [
            'name' => 'Licenza per armi corte (tipo B) o licenza di tiro olimpico (tipo F)',
            'help_text' => 'Licenza valida rilasciata dall\'autorità competente',
        ],
        'licencia_rifle' => [
            'name' => 'Licenza per armi lunghe (tipo D)',
            'help_text' => 'Licenza valida',
        ],
        'licencia_escopeta' => [
            'name' => 'Licenza per fucili a canna liscia (tipo E)',
            'help_text' => 'Licenza valida',
        ],
        'documento' => [
            'name' => 'Documento di identità',
            'help_text' => 'Documento di identificazione valido',
        ],
    ],
];
