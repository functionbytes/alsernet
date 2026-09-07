<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'url' => 'The :attribute must be a valid URL.',
    'numeric' => 'The :attribute must be a number.',
    'date' => 'The :attribute is not a valid date.',
    'file' => 'The :attribute must be a file.',
    'max' => [
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'size' => [
        'string' => 'The :attribute must be exactly :size characters.',
    ],
];
