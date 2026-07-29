<?php

namespace Modules\Supplier\Tests\Unit\Controllers;

use Tests\TestCase;

/**
 * Verifies that SupplierContentController no longer hard-imports the
 * Prestashop module's class. Hard imports break the supplier module if
 * Prestashop is disabled or absent — the controller now resolves the
 * service lazily via class_exists() + app() in prestashopSyncService().
 */
class SupplierContentControllerPublishTest extends TestCase
{
    public function test_controller_does_not_hard_import_prestashop_class(): void
    {
        $source = file_get_contents(
            __DIR__.'/../../../app/Http/Controllers/Settings/Suppliers/SupplierContentController.php'
        );

        $this->assertStringNotContainsString(
            'use Modules\\Prestashop\\Services\\SupplierSyncService;',
            $source,
            'The controller must not hard-import a class from the Prestashop module; resolution must stay lazy.'
        );
    }

    public function test_controller_resolves_prestashop_service_via_class_exists(): void
    {
        $source = file_get_contents(
            __DIR__.'/../../../app/Http/Controllers/Settings/Suppliers/SupplierContentController.php'
        );

        $this->assertStringContainsString(
            'prestashopSyncService',
            $source,
            'The controller must expose a lazy resolver method.'
        );
        $this->assertStringContainsString(
            'class_exists',
            $source,
            'The controller must guard the cross-module class lookup with class_exists().'
        );
    }
}
