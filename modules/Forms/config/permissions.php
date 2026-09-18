<?php

return [
    'permissions' => [
        ['name' => 'View Forms', 'flag' => 'Forms.forms.index'],
        ['name' => 'Create Forms', 'flag' => 'Forms.forms.create'],
        ['name' => 'Edit Forms', 'flag' => 'Forms.forms.edit'],
        ['name' => 'Delete Forms', 'flag' => 'Forms.forms.delete'],
        ['name' => 'View Form Submissions', 'flag' => 'Forms.submissions.index'],
        ['name' => 'Export Submissions', 'flag' => 'Forms.submissions.export'],
        ['name' => 'Delete Submissions', 'flag' => 'Forms.submissions.delete'],
        ['name' => 'Manage Form Categories', 'flag' => 'Forms.categories.manage'],
        ['name' => 'View Form Analytics', 'flag' => 'Forms.analytics.index'],
        ['name' => 'Manage Form Settings', 'flag' => 'Forms.settings.manage'],
    ],
];
