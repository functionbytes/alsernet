<?php

return [
    /*
     * Permissions for the Cookie Consent module
     */
    'permissions' => [
        [
            'name' => 'Cookie Consent Management',
            'flag' => 'Cookie.index',
            'parent_flag' => 'core.index',
            'is_feature' => true,
        ],
        [
            'name' => 'View Cookie Consent Settings',
            'flag' => 'Cookie.settings.index',
            'parent_flag' => 'Cookie.index',
        ],
        [
            'name' => 'Update Cookie Consent Settings',
            'flag' => 'Cookie.settings.update',
            'parent_flag' => 'Cookie.index',
        ],
    ],
];
