<?php

return [
    'labels' => [
        'customer' => 'Cliente',
        'order' => 'Pedido',
        'document' => 'Documento',
        'upload' => 'Cargar',
        'deadline' => 'Fecha límite',
        'required' => 'Requerido',
        'optional' => 'Opcional',
    ],

    'types' => [
        'corta' => [
            'label' => 'Armas Cortas',
            'description' => 'Documentación necesaria para armas cortas y de tiro olímpico',
            'instructions' => 'Por favor, suba los siguientes documentos para completar su pedido de arma corta.',
        ],
        'rifle' => [
            'label' => 'Armas Largas Rayadas (Rifles)',
            'description' => 'Documentación para rifles y armas largas rayadas',
            'instructions' => 'Por favor, suba los siguientes documentos para completar su pedido de rifle.',
        ],
        'escopeta' => [
            'label' => 'Escopetas',
            'description' => 'Documentación para escopetas de caza y deportivas',
            'instructions' => 'Por favor, suba los siguientes documentos para completar su pedido de escopeta.',
        ],
        'dni' => [
            'label' => 'Documentación de Identidad',
            'description' => 'Verificación de identidad y documentación',
            'instructions' => 'Por favor, suba los siguientes documentos de identificación.',
        ],
        'general' => [
            'label' => 'Documentación General',
            'description' => 'Documentación general del pedido',
            'instructions' => 'Por favor, suba los siguientes documentos.',
        ],
    ],
    'requirements' => [
        'dni_frontal' => [
            'name' => 'DNI - Cara frontal',
            'help_text' => 'Fotografía o escaneo del DNI por la cara delantera, debe ser legible',
        ],
        'dni_trasera' => [
            'name' => 'DNI - Cara trasera',
            'help_text' => 'Fotografía o escaneo del DNI por la cara trasera',
        ],
        'licencia' => [
            'name' => 'Licencia de armas',
            'help_text' => 'Licencia vigente expedida por autoridad competente',
        ],
        'licencia_corta' => [
            'name' => 'Licencia de armas cortas (tipo B) o licencia de tiro olímpico (tipo F)',
            'help_text' => 'Licencia vigente expedida por autoridad competente',
        ],
        'licencia_rifle' => [
            'name' => 'Licencia de armas largas rayadas (tipo D)',
            'help_text' => 'Licencia vigente',
        ],
        'licencia_escopeta' => [
            'name' => 'Licencia de escopetas (tipo E)',
            'help_text' => 'Licencia vigente',
        ],
        'documento' => [
            'name' => 'Documento de identidad',
            'help_text' => 'Documento de identificación válido',
        ],
    ],
];
