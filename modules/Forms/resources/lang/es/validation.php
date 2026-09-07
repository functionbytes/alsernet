<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un email válido.',
    'url' => 'El campo :attribute debe ser una URL válida.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'max' => [
        'file' => 'El archivo :attribute no debe superar :max kilobytes.',
        'string' => 'El campo :attribute no debe superar :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'numeric' => 'El campo :attribute debe ser como mínimo :min.',
    ],
    'size' => [
        'string' => 'El campo :attribute debe tener exactamente :size caracteres.',
    ],
];
