<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Filters\ConversationFilter;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

/**
 * Regresión del filtro de estado del inbox.
 *
 * El enlace lateral "En espera" navega a ?status=pending, pero applyStatus hacía
 * where('status_id', 'pending') — status_id es entero, así que MySQL convertía
 * 'pending' a 0 y la lista salía vacía, pese a que el contador (que filtra por el
 * estado con nombre "Esperando") mostraba conteo. Ahora las palabras clave no
 * numéricas se resuelven por nombre y filtro y contador coinciden.
 */
class ConversationFilterStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function makeStatus(string $name, bool $isOpen = true): ConversationStatus
    {
        return ConversationStatus::firstOrCreate(
            ['name' => $name],
            ['color' => '#0D6EFD', 'is_open' => $isOpen, 'is_default' => false, 'order' => 99]
        );
    }

    private function filtered(array $params): Collection
    {
        $query = Conversation::query();
        (new ConversationFilter(Request::create('/', 'GET', $params)))->apply($query);

        return $query->pluck('helpdesk_conversations.id');
    }

    public function test_pending_keyword_resolves_to_the_esperando_status(): void
    {
        $esperando = $this->makeStatus('Esperando');
        $customer = Customer::factory()->create();

        $waiting = Conversation::factory()->create(['customer_id' => $customer->id, 'status_id' => $esperando->id]);
        $other = Conversation::factory()->create(['customer_id' => $customer->id, 'status_id' => $this->makeStatus('Abierta')->id]);

        $ids = $this->filtered(['status' => 'pending']);

        $this->assertTrue($ids->contains($waiting->id), '"En espera" debe incluir la conversación en estado Esperando.');
        $this->assertFalse($ids->contains($other->id), 'No debe incluir conversaciones de otros estados.');
    }

    public function test_numeric_status_still_filters_by_status_id(): void
    {
        $status = $this->makeStatus('Cerrada test', false);
        $customer = Customer::factory()->create();

        $match = Conversation::factory()->create(['customer_id' => $customer->id, 'status_id' => $status->id]);
        $other = Conversation::factory()->create(['customer_id' => $customer->id, 'status_id' => $this->makeStatus('Abierta 2')->id]);

        $ids = $this->filtered(['status' => (string) $status->id]);

        $this->assertTrue($ids->contains($match->id));
        $this->assertFalse($ids->contains($other->id));
    }
}
