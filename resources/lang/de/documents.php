<?php

return [
    'types' => [
        'corta' => [
            'label' => 'Kurzwaffen',
            'description' => 'Erforderliche Unterlagen für Kurzwaffen und olympisches Schießen',
            'instructions' => 'Bitte laden Sie die folgenden Dokumente hoch, um Ihre Kurzwaffenbestellung abzuschließen.',
        ],
        'rifle' => [
            'label' => 'Langwaffen (Gewehre)',
            'description' => 'Erforderliche Unterlagen für Gewehre und Langwaffen',
            'instructions' => 'Bitte laden Sie die folgenden Dokumente hoch, um Ihre Gewehrbestellung abzuschließen.',
        ],
        'escopeta' => [
            'label' => 'Schrotflinten',
            'description' => 'Erforderliche Unterlagen für Jagd- und Sportschrotflinten',
            'instructions' => 'Bitte laden Sie die folgenden Dokumente hoch, um Ihre Schrotflintenbestellung abzuschließen.',
        ],
        'dni' => [
            'label' => 'Ausweisdokumentation',
            'description' => 'Identitätsprüfung und Dokumentation',
            'instructions' => 'Bitte laden Sie die folgenden Ausweisdokumente hoch.',
        ],
        'general' => [
            'label' => 'Allgemeine Dokumentation',
            'description' => 'Allgemeine Bestelldokumentation',
            'instructions' => 'Bitte laden Sie die folgenden Dokumente hoch.',
        ],
    ],
    'requirements' => [
        'dni_frontal' => [
            'name' => 'Ausweis - Vorderseite',
            'help_text' => 'Foto oder Scan der Vorderseite des Ausweises, muss klar und lesbar sein',
        ],
        'dni_trasera' => [
            'name' => 'Ausweis - Rückseite',
            'help_text' => 'Foto oder Scan der Rückseite des Ausweises',
        ],
        'licencia' => [
            'name' => 'Waffenschein',
            'help_text' => 'Gültige, von zuständiger Behörde ausgestellte Lizenz',
        ],
        'licencia_corta' => [
            'name' => 'Kurzwaffenschein (Typ B) oder olympischer Schießschein (Typ F)',
            'help_text' => 'Gültige, von zuständiger Behörde ausgestellte Lizenz',
        ],
        'licencia_rifle' => [
            'name' => 'Langwaffenschein (Typ D)',
            'help_text' => 'Gültige Lizenz',
        ],
        'licencia_escopeta' => [
            'name' => 'Schrotflintenschein (Typ E)',
            'help_text' => 'Gültige Lizenz',
        ],
        'documento' => [
            'name' => 'Ausweisdokument',
            'help_text' => 'Gültiges Ausweisdokument',
        ],
    ],
];
