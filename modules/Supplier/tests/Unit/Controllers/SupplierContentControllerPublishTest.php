<?php

namespace Modules\Supplier\Tests\Unit\Controllers;

use Tests\TestCase;

/**
 * SupplierContentController::publish() ya no delega en un servicio de
 * PrestaShop: el antiguo SupplierSyncService (modules/Prestashop) estaba
 * muerto y roto — importaba `Modules\Supplier\Entities\*`, un namespace que
 * ya no existe en el módulo Supplier tras su reestructuración a
 * `Models\Ai\AiContent` — y el bridge (alsernetbridge) no expone ningún
 * endpoint de escritura de productos con el que reemplazarlo. El endpoint
 * queda expuesto pero responde siempre "no disponible" hasta que exista un
 * mecanismo real de publicación.
 */
class SupplierContentControllerPublishTest extends TestCase
{
    public function test_controller_does_not_reference_prestashop_module(): void
    {
        $source = file_get_contents(
            __DIR__.'/../../../app/Http/Controllers/Settings/Suppliers/SupplierContentController.php'
        );

        $this->assertStringNotContainsString(
            'Modules\\Prestashop',
            $source,
            'The controller must not reference the Prestashop module — there is no working sync mechanism to call.'
        );
    }

    public function test_controller_does_not_expose_dead_sync_resolver(): void
    {
        $source = file_get_contents(
            __DIR__.'/../../../app/Http/Controllers/Settings/Suppliers/SupplierContentController.php'
        );

        $this->assertStringNotContainsString(
            'prestashopSyncService',
            $source,
            'The dead lazy-resolver method should not come back without a real, working sync mechanism behind it.'
        );
    }
}
