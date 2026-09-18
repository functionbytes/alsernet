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
 * Regresión de la interacción entre los filtros de vista y los del request.
 *
 * La vista por defecto guarda {is_open:true, is_archived:false} y se aplicaba en
 * cada carga. Dos defectos hacían que filtros explícitos se vieran vacíos:
 *  - empty() descartaba los valores false/0 (una vista "solo cerradas" con
 *    is_open=false no filtraba nada);
 *  - el is_open=true / is_archived=false de la vista por defecto enmascaraba un
 *    ?archived=1 o un estado cerrado explícito (→ 0 resultados).
 * Ahora el request tiene precedencia sobre la vista en la misma dimensión.
 */
class ConversationFilterViewTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function makeStatus(string $name, bool $isOpen): ConversationStatus
    {
        return ConversationStatus::firstOrCreate(
            ['name' => $name],
            ['color' => '#0D6EFD', 'is_open' => $isOpen, 'is_default' => false, 'order' => 99]
        );
    }

    /**
     * @param  array<string, mixed>  $viewFilters
     */
    private function filterIds(array $params, array $viewFilters): Collection
    {
        $query = Conversation::query();
        $filter = new ConversationFilter(Request::create('/', 'GET', $params));

        if ($viewFilters !== []) {
            $filter->applyViewFilters($query, $viewFilters);
        }
        $filter->apply($query);

        return $query->pluck('helpdesk_conversations.id');
    }

    private const DEFAULT_VIEW = ['is_open' => true, 'is_archived' => false];

    public function test_archived_request_overrides_default_view_and_shows_archived(): void
    {
        $customer = Customer::factory()->create();
        $archived = Conversation::factory()->create(['customer_id' => $customer->id, 'is_archived' => true]);

        $ids = $this->filterIds(['archived' => 1], self::DEFAULT_VIEW);

        $this->assertTrue($ids->contains($archived->id), 'Cerradas debe mostrar archivadas pese al is_open=true de la vista por defecto.');
    }

    public function test_default_view_hides_archived_without_archived_param(): void
    {
        $customer = Customer::factory()->create();
        $archived = Conversation::factory()->create(['customer_id' => $customer->id, 'is_archived' => true]);

        $ids = $this->filterIds([], self::DEFAULT_VIEW);

        $this->assertFalse($ids->contains($archived->id), 'Sin ?archived, la vista por defecto oculta archivadas.');
    }

    public function test_view_is_open_false_is_applied_not_skipped(): void
    {
        $customer = Customer::factory()->create();
        $closed = Conversation::factory()->create(['customer_id' => $customer->id, 'status_id' => $this->makeStatus('Cerrada v', false)->id, 'is_archived' => false]);
        $open = Conversation::factory()->create(['customer_id' => $customer->id, 'status_id' => $this->makeStatus('Abierta v', true)->id, 'is_archived' => false]);

        $ids = $this->filterIds([], ['is_open' => false, 'is_archived' => false]);

        $this->assertTrue($ids->contains($closed->id), 'Una vista is_open=false debe incluir conversaciones cerradas.');
        $this->assertFalse($ids->contains($open->id), 'Y excluir las abiertas.');
    }

    public function test_saved_view_archived_key_applies_via_view_filters(): void
    {
        $customer = Customer::factory()->create();
        $archived = Conversation::factory()->create(['customer_id' => $customer->id, 'is_archived' => true]);

        // Vista guardada por el usuario (vocabulario URL) cargada por viewId.
        $ids = $this->filterIds([], ['archived' => '1']);

        $this->assertTrue($ids->contains($archived->id), 'Una vista guardada con archived=1 debe mostrar archivadas.');
    }

    public function test_search_matches_customer_email_and_conversation_id(): void
    {
        $customer = Customer::factory()->create(['email' => 'objetivo-unico@example.com']);
        $conv = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $this->makeStatus('Abierta s', true)->id,
            'is_archived' => false,
        ]);

        $byEmail = $this->filterIds(['search' => 'objetivo-unico@'], self::DEFAULT_VIEW);
        $byId = $this->filterIds(['search' => (string) $conv->id], self::DEFAULT_VIEW);

        $this->assertTrue($byEmail->contains($conv->id), 'El buscador debe encontrar por email del cliente.');
        $this->assertTrue($byId->contains($conv->id), 'El buscador debe encontrar por id de conversación.');
    }
}
