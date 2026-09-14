<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Auth\Traits\HasUserAttributes;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Conversations\ActivityMessageService;
use Tests\TestCase;

/**
 * Varios agentes reales tienen el mismo valor en firstname y lastname
 * ("Ángeles Ángeles", "Helena Helena"). App\Models\User::fullName() (y el
 * accessor full_name que lo envuelve) es ahora el único sitio donde se
 * decide cómo mostrar un nombre — más de veinte controladores, notificaciones
 * y servicios lo llamaban por su cuenta, cada uno con su propio
 * trim(firstname.' '.lastname) sin deduplicar.
 *
 * Este test cubre ActivityMessageService en concreto porque es el texto que
 * un agente lee en la propia línea de tiempo de cada conversación ("X asignó
 * la conversación", "X añadió la etiqueta") — el sitio más visible de todos
 * los que usaban el patrón roto.
 *
 * @see HasUserAttributes::fullName()
 */
class UserFullNameDeduplicationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    private function duplicateNameAgent(): User
    {
        return User::factory()->create(['firstname' => 'Casimira', 'lastname' => 'Casimira']);
    }

    public function test_full_name_deduplicates_when_firstname_equals_lastname(): void
    {
        $agent = $this->duplicateNameAgent();

        $this->assertSame('Casimira', $agent->fullName());
        $this->assertSame('Casimira', $agent->full_name);
        $this->assertNotSame('Casimira Casimira', $agent->full_name);
    }

    public function test_full_name_keeps_both_parts_when_they_differ(): void
    {
        $agent = User::factory()->create(['firstname' => 'Casimira', 'lastname' => 'Cobo']);

        $this->assertSame('Casimira Cobo', $agent->fullName());
    }

    public function test_activity_timeline_does_not_duplicate_the_name_on_label_added(): void
    {
        $agent = $this->duplicateNameAgent();
        $conversation = Conversation::factory()->create();

        $activity = app(ActivityMessageService::class)->logLabelAdded($conversation, 'Billing', $agent);

        $this->assertSame('Casimira añadió la etiqueta "Billing"', $activity->body);
    }

    public function test_activity_timeline_does_not_duplicate_the_name_on_assignment(): void
    {
        $agent = $this->duplicateNameAgent();
        $conversation = Conversation::factory()->create();

        $activity = app(ActivityMessageService::class)->logAssigned($conversation, $agent);

        $this->assertSame('La conversación fue asignada a Casimira', $activity->body);
    }
}
