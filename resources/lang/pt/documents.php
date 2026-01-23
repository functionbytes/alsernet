<?php

return [
    'types' => [
        'corta' => [
            'label' => 'Armas Curtas',
            'description' => 'Documentação necessária para armas curtas e tiro olímpico',
            'instructions' => 'Por favor, carregue os seguintes documentos para completar seu pedido de arma curta.',
        ],
        'rifle' => [
            'label' => 'Armas Longas (Rifles)',
            'description' => 'Documentação para rifles e armas longas',
            'instructions' => 'Por favor, carregue os seguintes documentos para completar seu pedido de rifle.',
        ],
        'escopeta' => [
            'label' => 'Espingardas',
            'description' => 'Documentação para espingardas de caça e esportivas',
            'instructions' => 'Por favor, carregue os seguintes documentos para completar seu pedido de espingarda.',
        ],
        'dni' => [
            'label' => 'Documentação de Identidade',
            'description' => 'Verificação de identidade e documentação',
            'instructions' => 'Por favor, carregue os seguintes documentos de identificação.',
        ],
        'general' => [
            'label' => 'Documentação Geral',
            'description' => 'Documentação geral do pedido',
            'instructions' => 'Por favor, carregue os seguintes documentos.',
        ],
    ],
    'requirements' => [
        'dni_frontal' => [
            'name' => 'Documento de Identidade - Frente',
            'help_text' => 'Foto ou digitalização da frente do documento, deve estar clara e legível',
        ],
        'dni_trasera' => [
            'name' => 'Documento de Identidade - Verso',
            'help_text' => 'Foto ou digitalização do verso do documento',
        ],
        'licencia' => [
            'name' => 'Licença de armas',
            'help_text' => 'Licença válida emitida por autoridade competente',
        ],
        'licencia_corta' => [
            'name' => 'Licença de armas curtas (tipo B) ou licença de tiro olímpico (tipo F)',
            'help_text' => 'Licença válida emitida por autoridade competente',
        ],
        'licencia_rifle' => [
            'name' => 'Licença de armas longas (tipo D)',
            'help_text' => 'Licença válida',
        ],
        'licencia_escopeta' => [
            'name' => 'Licença de espingardas (tipo E)',
            'help_text' => 'Licença válida',
        ],
        'documento' => [
            'name' => 'Documento de identidade',
            'help_text' => 'Documento de identificação válido',
        ],
    ],
];
