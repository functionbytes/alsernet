<?php

namespace Modules\Helpdesk\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Actions\Customers\CustomerMergeAction;
use Modules\Helpdesk\Events\CustomerMerged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Regresión de la fusión de contactos (CustomerMergeAction):
 *
 * - mergeExternalIds ya no hace un UPDATE ciego: los pares (platform,
 *   external_id) que el base ya tiene se borran del mergee antes de re-asignar,
 *   para que el índice único helpdesk_cust_ext_platform_id_unique no tumbe la
 *   transacción. Nota: ese índice es GLOBAL por (platform, external_id), por lo
 *   que hoy es imposible sembrar un par exactamente duplicado entre dos
 *   customers; el escenario más cercano representable es que ambos tengan
 *   external_ids en la misma plataforma (con distinto external_id).
 *
 * - total_conversations se recalcula con un COUNT real tras mover las
 *   conversaciones, en vez de sumar contadores desnormalizados desfasados.
 */
class CustomerMergeActionTest extends HelpdeskTestCase
{
    public function test_merge_reassigns_external_ids_without_breaking_on_shared_platform(): void
    {
        Event::fake([CustomerMerged::class]);

        $base = Customer::factory()->create();
        $mergee = Customer::factory()->create();

        CustomerExternalId::create(['customer_id' => $base->id, 'platform' => 'prestashop', 'external_id' => 'PS-BASE-1']);
        CustomerExternalId::create(['customer_id' => $mergee->id, 'platform' => 'prestashop', 'external_id' => 'PS-MERGEE-1']);
        CustomerExternalId::create(['customer_id' => $mergee->id, 'platform' => 'erp', 'external_id' => 'ERP-9']);

        $result = (new CustomerMergeAction($base, $mergee))->execute();

        $this->assertSame($base->id, $result->id);

        // Los external_ids del mergee quedan re-asignados al base y no queda
        // ninguno colgando del mergee.
        $this->assertEqualsCanonicalizing(
            ['ERP-9', 'PS-BASE-1', 'PS-MERGEE-1'],
            CustomerExternalId::where('customer_id', $base->id)->pluck('external_id')->all()
        );
        $this->assertSame(0, CustomerExternalId::where('customer_id', $mergee->id)->count());

        $this->assertSoftDeleted($mergee);
        Event::assertDispatched(CustomerMerged::class);
    }

    public function test_merge_recalculates_total_conversations_with_real_count(): void
    {
        Event::fake([CustomerMerged::class]);

        // Contadores desnormalizados deliberadamente desfasados respecto a las
        // conversaciones reales (1 del base + 2 del mergee = 3).
        $base = Customer::factory()->create(['total_conversations' => 50, 'total_page_visits' => 10]);
        $mergee = Customer::factory()->create(['total_conversations' => 7, 'total_page_visits' => 5]);

        Conversation::factory()->create(['customer_id' => $base->id]);
        Conversation::factory()->count(2)->create(['customer_id' => $mergee->id]);

        $result = (new CustomerMergeAction($base, $mergee))->execute();

        // COUNT real tras el merge, no 50 + 7.
        $this->assertSame(3, (int) $result->total_conversations);

        // Las visitas no se re-asignan en el merge: se mantiene la suma.
        $this->assertSame(15, (int) $result->total_page_visits);

        $this->assertSame(
            3,
            Conversation::where('customer_id', $base->id)->count()
        );
    }
}
