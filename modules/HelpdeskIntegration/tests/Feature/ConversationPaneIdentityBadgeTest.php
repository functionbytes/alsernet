<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;
use Tests\TestCase;

/**
 * El panel derecho de la conversacion (core Helpdesk) resuelve el badge de
 * identidad consultando CustomerIdentityVerificationService directamente
 * (cross-modulo, decision explicita del usuario) — este test cubre esa
 * integracion en lugar de solo el servicio de forma aislada.
 *
 * Se renderiza la vista directamente (no via el endpoint /pane HTTP) para
 * no depender de la sesion del widget de LiveChat, ajena a esta feature.
 */
class ConversationPaneIdentityBadgeTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function paneHtml(Conversation $conversation): string
    {
        return view('helpdesk::helpdesk.inbox.partials.right-panel', [
            'selectedConversation' => $conversation->fresh(),
            'selectedConversationId' => $conversation->id,
        ])->render();
    }

    public function test_shows_verify_button_when_identity_is_not_verified(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $html = $this->paneHtml($conversation);

        $this->assertStringContainsString('bv-identity-verify-trigger', $html);
        $this->assertStringContainsString('Verificar identidad', $html);
        $this->assertStringNotContainsString('Verificada', $html);
    }

    public function test_shows_verified_badge_when_identity_is_verified(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        CustomerIdentityVerification::query()->create([
            'customer_id' => $customer->id,
            'channel' => 'email',
            'code_hash' => bcrypt('123456'),
            'expires_at' => now()->addMinutes(10),
            'verified_at' => now(),
            'verified_by' => User::factory()->create()->id,
        ]);

        $html = $this->paneHtml($conversation);

        $this->assertStringContainsString('Verificada', $html);
        $this->assertStringNotContainsString('bv-identity-verify-trigger', $html);
    }

    public function test_expired_verification_shows_verify_button_again(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        // TTL de sesion verificada es 30 min — una verificacion de hace 45
        // min ya no cuenta como vigente.
        CustomerIdentityVerification::query()->create([
            'customer_id' => $customer->id,
            'channel' => 'email',
            'code_hash' => bcrypt('123456'),
            'expires_at' => now()->subMinutes(35),
            'verified_at' => now()->subMinutes(45),
            'verified_by' => User::factory()->create()->id,
        ]);

        $html = $this->paneHtml($conversation);

        $this->assertStringContainsString('bv-identity-verify-trigger', $html);
        $this->assertStringNotContainsString('Verificada', $html);
    }
}
