<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskBirthday\Services\BirthdayAudienceService;
use Tests\TestCase;

/**
 * De dónde saca la campaña los cumpleañeros.
 *
 * Importa a QUIÉN se le pregunta: la API de clientes de este panel y la del
 * manager externo exponen la misma ruta, pero solo la de aquí tiene el filtro
 * `birthday`. Preguntar a la equivocada no da error — da clientes cualesquiera,
 * que recibirían una felicitación que no les toca.
 */
class BirthdayAudienceSourceTest extends TestCase
{
    /**
     * Setting::get() cachea 10 minutos en el store REAL — y cachea también el
     * valor por defecto cuando la clave no existe en la tabla. Sin este forget,
     * el 'gestion' que fija un test de aquí se queda pegado y la aplicación
     * entera consulta la fuente equivocada hasta que caduque. Comprobado: pasó.
     */
    protected function tearDown(): void
    {
        Cache::forget('setting_helpdeskbirthday.audience_source');

        parent::tearDown();
    }

    private function respuesta(array $clientes = []): array
    {
        return [
            'success' => true,
            'data' => $clientes,
            'pagination' => ['limit' => 100, 'offset' => 0, 'count' => count($clientes), 'hasMore' => false],
        ];
    }

    public function test_por_defecto_pregunta_a_la_api_de_clientes_de_este_panel(): void
    {
        config(['helpdeskbirthday.customers_api_url' => 'http://nginx']);
        config(['helpdeskErp.manager_url' => 'http://manager-externo.test']);

        Http::fake(['*' => Http::response($this->respuesta())]);

        app(BirthdayAudienceService::class)->fetchForDate(CarbonImmutable::parse('2026-09-04'));

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'http://nginx/api/erp/customer'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'manager-externo'));
    }

    public function test_manda_el_dia_en_formato_mm_dd_y_las_exclusiones(): void
    {
        config(['helpdeskbirthday.customers_api_url' => 'http://nginx']);

        Http::fake(['*' => Http::response($this->respuesta())]);

        app(BirthdayAudienceService::class)->fetchForDate(CarbonImmutable::parse('2026-09-04'), [
            'has_email' => true,
        ]);

        Http::assertSent(function ($request) {
            $url = urldecode($request->url());

            return str_contains($url, 'birthday=09-04') && str_contains($url, 'has_email=1');
        });
    }

    public function test_se_puede_usar_la_api_de_gestion_como_alternativa(): void
    {
        config(['helpdeskbirthday.audience_source' => 'gestion']);

        // Gestión se consulta con un cliente propio (Guzzle), que Http::fake no
        // intercepta: si se hubiese ido por la API de clientes, este fake habría
        // registrado la llamada.
        Http::fake(['*' => Http::response($this->respuesta())]);

        try {
            app(BirthdayAudienceService::class)->fetchForDate(CarbonImmutable::parse('2026-09-04'));
        } catch (\Throwable) {
            // Sin ERP alcanzable desde el test da igual: lo que se comprueba es
            // que NO se llamó a la API de clientes.
        }

        Http::assertNothingSent();
    }
}
