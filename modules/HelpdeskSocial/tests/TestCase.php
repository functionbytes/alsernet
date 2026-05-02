<?php

namespace Modules\HelpdeskSocial\Tests;

use Spatie\Permission\Models\Permission;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'helpdesksocial.view',
            'helpdesksocial.manage-accounts',
            'helpdesksocial.manage-rules',
            'helpdesksocial.manage-templates',
            'helpdesksocial.view-analytics',
            'helpdesksocial.manage',
            'helpdesksocial.approver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
