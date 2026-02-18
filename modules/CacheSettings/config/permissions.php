<?php

return [
    'permissions' => [
        [
            'name' => 'Cache Settings Management',
            'flag' => 'CacheSettings.index',
            'parent_flag' => 'core.index',
            'is_feature' => true,
        ],
        [
            'name' => 'View Cache Settings',
            'flag' => 'CacheSettings.settings.index',
            'parent_flag' => 'CacheSettings.index',
        ],
        [
            'name' => 'Update Cache Settings',
            'flag' => 'CacheSettings.settings.update',
            'parent_flag' => 'CacheSettings.index',
        ],
    ],
];
