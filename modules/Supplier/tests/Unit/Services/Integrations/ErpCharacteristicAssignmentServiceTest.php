<?php

namespace Modules\Supplier\Tests\Unit\Services\Integrations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Core\Models\Setting;
use Modules\Supplier\Services\Integrations\ErpCharacteristicAssignmentService;
use Tests\TestCase;

class ErpCharacteristicAssignmentServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_assign_to_model_sends_correct_payload(): void
    {
        Http::fake(['*asignar-caracteristica*' => Http::response('OK', 200)]);

        $service = $this->app->make(ErpCharacteristicAssignmentService::class);
        $result = $service->assignToModel(78, 555001);

        Http::assertSent(function ($request) {
            return $request['id_caracteristica'] === '78'
                && $request['idmodelo'] === '555001'
                && ! isset($request['id_valor'])
                && ! isset($request['idarticulo']);
        });

        $this->assertTrue($result['success']);
    }

    public function test_assign_to_article_sends_correct_payload(): void
    {
        Http::fake(['*asignar-caracteristica*' => Http::response('OK', 200)]);

        $service = $this->app->make(ErpCharacteristicAssignmentService::class);
        $result = $service->assignToArticle(78, 2293, 777001);

        Http::assertSent(function ($request) {
            return $request['id_caracteristica'] === '78'
                && $request['id_valor'] === '2293'
                && $request['idarticulo'] === '777001'
                && ! isset($request['idmodelo']);
        });

        $this->assertTrue($result['success']);
    }

    public function test_assign_to_model_returns_failure_when_erp_responds_error(): void
    {
        Http::fake(['*asignar-caracteristica*' => Http::response('Error', 500)]);

        $service = $this->app->make(ErpCharacteristicAssignmentService::class);
        $result = $service->assignToModel(78, 555001);

        $this->assertFalse($result['success']);
    }

    public function test_assign_returns_failure_without_calling_http_when_url_not_configured(): void
    {
        Setting::set('supplier.erp_caracteristica_url', '');
        Http::fake();

        $service = $this->app->make(ErpCharacteristicAssignmentService::class);
        $result = $service->assignToModel(78, 555001);

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }
}
