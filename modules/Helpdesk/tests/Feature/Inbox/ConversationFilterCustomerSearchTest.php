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
 * ConversationFilter::applyCustomerSearch() usa MATCH ... AGAINST sobre
 * helpdesk_customers (name, email) desde la auditoría del 17-sep-2026.
 *
 * InnoDB solo sincroniza un índice FULLTEXT a disco en el COMMIT de la
 * transacción que escribió la fila: dentro de una transacción sin confirmar
 * (incluida la misma sesión que insertó), MATCH AGAINST devuelve 0 filas para
 * lo que se acaba de crear. Por eso esta clase NO envuelve la conexión
 * 'helpdesk' en DatabaseTransactions (solo 'mariadb') y limpia a mano cada
 * fila que crea — mismo patrón que
 * ContactActivityEmailsIsolationTest (HelpdeskContacts).
 */
class ConversationFilterCustomerSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb'];

    public function test_search_matches_customer_email_fulltext_and_conversation_id(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Abierta search-'.uniqid(),
            'color' => '#0D6EFD',
            'is_open' => true,
            'is_default' => false,
            'order' => 99,
        ]);
        $customer = Customer::factory()->create(['email' => 'objetivo-unico@example.com']);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'is_archived' => false,
        ]);

        try {
            $byEmail = $this->filterIds(['search' => 'objetivo-unico@']);
            $byId = $this->filterIds(['search' => (string) $conversation->id]);

            $this->assertTrue($byEmail->contains($conversation->id), 'El buscador debe encontrar por email del cliente vía FULLTEXT.');
            $this->assertTrue($byId->contains($conversation->id), 'El buscador debe encontrar por id de conversación.');
        } finally {
            $conversation->forceDelete();
            $customer->forceDelete();
            $status->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    private function filterIds(array $params): Collection
    {
        $query = Conversation::query();
        $filter = new ConversationFilter(Request::create('/', 'GET', $params));
        $filter->applyViewFilters($query, ['is_open' => true, 'is_archived' => false]);
        $filter->apply($query);

        return $query->pluck('helpdesk_conversations.id');
    }
}
