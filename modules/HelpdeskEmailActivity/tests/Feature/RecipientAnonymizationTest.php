<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\RecipientAnonymizerService;
use Tests\TestCase;

/**
 * Anonimización de un destinatario (RGPD art. 17) — acción IRREVERSIBLE.
 *
 * Los tests cubren las dos mitades que importan en un borrado sin vuelta
 * atrás: que borra de verdad lo que dice (contenido, direcciones, índice de
 * búsqueda) y, sobre todo, que NO toca nada más — ni los envíos de otras
 * personas, ni las métricas del propio envío.
 */
class RecipientAnonymizationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const TARGET = 'sujeto.gdpr@ejemplo-test.invalid';

    private const OTHER = 'ajeno.gdpr@ejemplo-test.invalid';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
    }

    private function manager(): User
    {
        return tap(User::factory()->create())->givePermissionTo([
            'helpdeskemailactivity.view',
            'helpdeskemailactivity.manage',
        ]);
    }

    private function logFor(string $email): EmailLog
    {
        return EmailLog::factory()->create([
            'to_addresses' => [$email],
            'body_html' => '<p>Contenido personal</p>',
            'body_text' => 'Contenido personal',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function anonymise(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->manager())->postJson(
            route('helpdeskemailactivity.gdpr.anonymize'),
            array_merge([
                'email' => self::TARGET,
                'confirmation' => self::TARGET,
                'purge_body' => 1,
                'replace_address' => 1,
                'suppress' => 0,
            ], $overrides),
        );
    }

    public function test_it_purges_content_and_replaces_the_address(): void
    {
        $target = $this->logFor(self::TARGET);

        $this->anonymise()->assertOk()->assertJson(['success' => true]);

        $target->refresh();

        $this->assertNull($target->body_html);
        $this->assertNull($target->body_text);
        $this->assertNotContains(self::TARGET, $target->to_addresses ?? []);
        $this->assertStringContainsString('anonimizado.invalid', $target->to_addresses[0]);
    }

    public function test_the_search_index_no_longer_contains_the_address(): void
    {
        // recipients_index solo se recalcula al CREAR (ver EmailLog::booted()),
        // así que si el servicio no lo rehace a mano, el buscador seguiría
        // encontrando a esta persona por su dirección original.
        $target = $this->logFor(self::TARGET);

        $this->anonymise()->assertOk();

        $this->assertStringNotContainsString(self::TARGET, (string) $target->refresh()->recipients_index);
    }

    public function test_it_does_not_touch_other_recipients(): void
    {
        $other = $this->logFor(self::OTHER);

        $this->anonymise()->assertOk();

        $other->refresh();

        $this->assertSame('Contenido personal', $other->body_text);
        $this->assertSame([self::OTHER], $other->to_addresses);
    }

    public function test_metrics_survive_the_anonymisation(): void
    {
        // El envío sigue existiendo con su estado y su fecha: borrar la fila
        // falsearía los totales y las tasas del histórico.
        $target = $this->logFor(self::TARGET);
        $status = $target->status;
        $createdAt = $target->created_at;

        $this->anonymise()->assertOk();

        $target->refresh();

        $this->assertNotNull($target->id);
        $this->assertSame($status, $target->status);
        $this->assertEquals($createdAt, $target->created_at);
    }

    public function test_a_mismatched_confirmation_changes_nothing(): void
    {
        $target = $this->logFor(self::TARGET);

        $this->anonymise(['confirmation' => 'otra.cosa@ejemplo-test.invalid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirmation');

        $this->assertSame('Contenido personal', $target->refresh()->body_text);
    }

    public function test_it_refuses_when_no_action_is_selected(): void
    {
        $target = $this->logFor(self::TARGET);

        $this->anonymise(['purge_body' => 0, 'replace_address' => 0, 'suppress' => 0])
            ->assertStatus(422);

        $this->assertSame('Contenido personal', $target->refresh()->body_text);
    }

    public function test_a_viewer_cannot_anonymise(): void
    {
        $target = $this->logFor(self::TARGET);
        $viewer = tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.view');

        $this->actingAs($viewer)->postJson(route('helpdeskemailactivity.gdpr.anonymize'), [
            'email' => self::TARGET,
            'confirmation' => self::TARGET,
            'purge_body' => 1,
        ])->assertForbidden();

        $this->assertSame('Contenido personal', $target->refresh()->body_text);
    }

    public function test_the_pseudonym_is_stable_for_the_same_address(): void
    {
        // Estable = los envíos de una misma persona siguen agrupados tras
        // anonimizar, que es lo que permite que las métricas por destinatario
        // sigan cuadrando.
        $service = app(RecipientAnonymizerService::class);

        $this->assertSame(
            $service->pseudonymFor(self::TARGET),
            $service->pseudonymFor(' '.mb_strtoupper(self::TARGET).' '),
        );
        $this->assertNotSame(
            $service->pseudonymFor(self::TARGET),
            $service->pseudonymFor(self::OTHER),
        );
    }

    public function test_the_search_index_follows_any_address_change_not_just_anonymisation(): void
    {
        // El arreglo vive en EmailLog::booting() ('saving', no 'creating'), así
        // que cubre CUALQUIER cambio de direcciones, no solo el RGPD: antes,
        // editar to_addresses por otra vía dejaba el índice — y por tanto el
        // buscador — apuntando a la dirección vieja.
        $log = $this->logFor(self::TARGET);

        $log->forceFill(['to_addresses' => ['nueva.direccion@ejemplo-test.invalid']])->save();

        $index = (string) $log->refresh()->recipients_index;

        $this->assertStringContainsString('nueva.direccion@ejemplo-test.invalid', $index);
        $this->assertStringNotContainsString(self::TARGET, $index);
    }

    public function test_preview_reports_the_scope_without_changing_anything(): void
    {
        $target = $this->logFor(self::TARGET);
        $this->logFor(self::OTHER);

        $this->actingAs($this->manager())
            ->postJson(route('helpdeskemailactivity.gdpr.preview'), ['email' => self::TARGET])
            ->assertOk()
            ->assertJsonPath('preview.emails', 1)
            ->assertJsonPath('preview.with_body', 1);

        $this->assertSame('Contenido personal', $target->refresh()->body_text);
    }
}
