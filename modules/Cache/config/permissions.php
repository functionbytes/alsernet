<?php

return [
    'permissions' => [
        [
            'name' => 'Cache Settings Management',
            'flag' => 'Cache.index',
            'parent_flag' => 'core.index',
            'is_feature' => true,
        ],
        [
            'name' => 'View Cache Settings',
            'flag' => 'Cache.settings.index',
            'parent_flag' => 'Cache.index',
        ],
        [
            'name' => 'Update Cache Settings',
            'flag' => 'Cache.settings.update',
            'parent_flag' => 'Cache.index',
        ],
    ],
];
