<?php

return [
    'root' => [
        'name' => 'modules/helpdesk',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => '721ecdd9df79f2343dd7cc9713745acf80d61801',
        'type' => 'library',
        'install_path' => __DIR__.'/../../',
        'aliases' => [],
        'dev' => true,
    ],
    'versions' => [
        'league/csv' => [
            'pretty_version' => '9.28.0',
            'version' => '9.28.0.0',
            'reference' => '6582ace29ae09ba5b07049d40ea13eb19c8b5073',
            'type' => 'library',
            'install_path' => __DIR__.'/../league/csv',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'modules/helpdesk' => [
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '721ecdd9df79f2343dd7cc9713745acf80d61801',
            'type' => 'library',
            'install_path' => __DIR__.'/../../',
            'aliases' => [],
            'dev_requirement' => false,
        ],
    ],
];
