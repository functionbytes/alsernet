<?php

namespace Modules\Erp\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Core\Models\Setting;
use Tests\TestCase;

/**
 * Cubre lo que se resuelve ANTES de tocar Oracle (auth y validación), que es
 * lo que se puede probar sin la base de datos del ERP.
 */
class CustomerApiTest extends TestCase
{
    use DatabaseTransactions;

    // mysql/mariadb/helpdesk apuntan a la MISMA BD real - nunca RefreshDatabase.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    private function setApiAuth(bool $enabled, string $guard = 'sanctum'): void
    {
        // ApiAuth lee el bundle cacheado de Setting::getErpSettings().
        $bundle = Setting::getErpSettings();
        $bundle['erp_api_auth_enabled'] = $enabled ? 'yes' : 'no';
        $bundle['erp_api_auth_guard'] = $guard;
        cache()->put('settings_erp_bundle', $bundle, now()->addMinutes(10));
    }

    public function test_auth_enabled_rejects_anonymous_reads(): void
    {
        $this->setApiAuth(true);

        $this->getJson('/api/erp/customer/1')->assertUnauthorized();
        $this->getJson('/api/erp/customer/1/personal')->assertUnauthorized();
        $this->getJson('/api/erp/customer?email=a@b.com')->assertUnauthorized();
    }

    public function test_auth_enabled_rejects_anonymous_writes(): void
    {
        $this->setApiAuth(true);

        $this->postJson('/api/erp/customer', [])->assertUnauthorized();
        $this->patchJson('/api/erp/customer/lopd', [])->assertUnauthorized();
    }

    public function test_auth_enabled_rejects_unknown_erp_token(): void
    {
        $this->setApiAuth(true, 'erp_token');

        $this->withHeader('X-Erp-Token', 'not-a-real-token')
            ->getJson('/api/erp/customer/1')
            ->assertUnauthorized();
    }

    public function test_authenticated_create_validates_before_touching_oracle(): void
    {
        $this->setApiAuth(true);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/erp/customer', ['email' => 'no-es-un-email'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed')
            ->assertJsonValidationErrors(['name', 'surnames', 'cif', 'email', 'lopd_accepted_at', 'catalogs']);
    }

    public function test_update_lopd_validation_keeps_response_shape(): void
    {
        $this->setApiAuth(false);

        $this->patchJson('/api/erp/customer/lopd', ['accepted_at' => 'mañana'])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'error', 'errors' => ['email', 'accepted_at', 'no_commercial_info', 'no_data_to_third_parties']]);
    }

    public function test_list_rejects_malformed_birth_date(): void
    {
        $this->setApiAuth(false);

        $this->getJson('/api/erp/customer?birth_date=28/09/1990')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    }

    public function test_erp_api_responses_carry_server_timing(): void
    {
        $this->setApiAuth(true);

        $this->getJson('/api/erp/customer/1')
            ->assertUnauthorized()
            ->assertHeader('Server-Timing');
    }

    public function test_rate_limit_applies_to_callers_but_not_to_the_internal_token(): void
    {
        $this->setApiAuth(false);
        config(['erp.api.throttle' => '3,1', 'erp.api.internal_token' => 'internal-test-token']);

        // Llamada normal: a la 4ª en el mismo minuto, 429 (la 422 es la
        // validación del FormRequest, que corre después del limitador).
        foreach (range(1, 3) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])->postJson('/api/erp/customer', [])->assertStatus(422);
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])->postJson('/api/erp/customer', [])->assertStatus(429);

        // Token interno (Supplier, mismo servidor): sin límite.
        foreach (range(1, 6) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.8'])
                ->withToken('internal-test-token')
                ->postJson('/api/erp/customer', [])
                ->assertStatus(422);
        }
    }
}
