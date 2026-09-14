<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * BUG-03: emails() en ContactAggregatorService usaba MATCH AGAINST en modo
 * NATURAL LANGUAGE junto a un orWhere LIKE que anulaba el índice FULLTEXT —
 * cuando no había coincidencia exacta, la sección listaba los 20 email logs
 * GLOBALES más recientes, filtrando destinatarios de OTROS clientes.
 *
 * email_logs vive en la conexión por defecto (no 'helpdesk') y su índice
 * FULLTEXT de InnoDB solo indexa filas ya confirmadas (COMMIT): dentro de una
 * transacción sin confirmar, MATCH AGAINST no ve sus propias filas recién
 * insertadas. Por eso esta clase NO envuelve la conexión por defecto en
 * DatabaseTransactions (solo 'helpdesk', donde vive Customer) y limpia a mano
 * las filas de email_logs que crea.
 */
class ContactActivityEmailsIsolationTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['helpdesk'];

    public function test_actividad_emails_only_returns_logs_of_the_matching_customer(): void
    {
        $mine = Customer::factory()->create(['email' => 'propio@example.test']);
        Customer::factory()->create(['email' => 'ajeno@example.test']);

        $ownLog = EmailLog::factory()->create([
            'to_addresses' => ['propio@example.test'],
            'subject' => 'Correo del contacto propio',
        ]);
        $otherLog = EmailLog::factory()->create([
            'to_addresses' => ['ajeno@example.test'],
            'subject' => 'Correo de otro contacto',
        ]);

        try {
            $result = app(ContactAggregatorService::class)->actividad($mine->fresh());

            $subjects = array_column($result['emails'], 'subject');

            $this->assertContains('Correo del contacto propio', $subjects);
            $this->assertNotContains('Correo de otro contacto', $subjects);
            $this->assertCount(1, $result['emails']);
        } finally {
            $ownLog->delete();
            $otherLog->delete();
        }
    }
}
