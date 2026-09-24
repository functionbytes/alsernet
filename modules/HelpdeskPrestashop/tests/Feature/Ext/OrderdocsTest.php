<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Extensión "orderdocs": notas internas del pedido y PDF de sus documentos.
 * El bridge va simulado con Http::fake — nunca se llama a la tienda real.
 */
class OrderdocsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(string ...$extra): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['helpdesk.customers.view', 'helpdesk.customers.manage'], $extra));

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'docs-'.uniqid().'@example.com']);
    }

    public function test_notes_require_orders_view_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent())
            ->getJson(route('manager.helpdesk.ps.ext.orderdocs.notes', [$this->customer(), 816880]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_notes_are_read_with_the_customer_lookup(): void
    {
        $customer = $this->customer();

        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'order_id' => 816880,
            'notes' => [
                ['id' => 'note-0', 'source' => 'order_note', 'author' => 'María García', 'date' => '2026-05-17 10:42:00', 'text' => 'Entrega en obra, avisar 1 h antes.'],
                ['id' => 'cm-7', 'source' => 'private_message', 'author' => 'Automático', 'date' => '2026-05-16 09:00:00', 'text' => 'Pago capturado.'],
            ],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.orderdocs.notes', [$customer, 816880]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notes.0.author', 'María García')
            ->assertJsonCount(2, 'data.notes')
            // Sin orders.manage no podría guardar notas: el panel oculta el formulario.
            ->assertJsonPath('can_add', false);

        Http::assertSent(function (Request $request) use ($customer) {
            $data = $request->data();

            return ($data['action'] ?? null) === 'orderdocs.notes'
                && ($data['order_id'] ?? null) === 816880
                && ($data['lookup']['email'] ?? null) === strtolower($customer->email)
                // Lectura: sin clave de idempotencia.
                && ! $request->hasHeader('X-Alsernet-Idempotency-Key');
        });
    }

    // Pedido de otro cliente: el puente responde 404 {ok:false}; es un
    // rechazo de negocio, no una caída (antes salía 503 "no responde").
    public function test_notes_report_not_found_when_bridge_rejects_the_order(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => false, 'error' => 'unknown action or customer not found'], 404)]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.orderdocs.notes', [$this->customer(), 999]))
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_documents_list_tells_whether_the_user_can_download(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'order_id' => 816880,
            'reference' => 'BPGCSMHNY',
            'invoicing_enabled' => false,
            'invoices' => [],
            'delivery_slips' => [['id' => 152313, 'number' => '#DE050805', 'date' => '2026-02-26 15:02:27']],
            'credit_slips' => [],
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->getJson(route('manager.helpdesk.ps.ext.orderdocs.documents', [$this->customer(), 816880]))
            ->assertOk()
            ->assertJsonPath('data.delivery_slips.0.id', 152313)
            ->assertJsonPath('can_download', false);
    }

    public function test_download_requires_documents_permission(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.view'))
            ->get(route('manager.helpdesk.ps.ext.orderdocs.download', [$this->customer(), 816880, 'delivery_slip', 152313]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_download_requires_access_to_the_customer(): void
    {
        Http::fake();

        // Permiso PS sí, pero sin helpdesk.customers.view: la CustomerPolicy
        // lo corta antes de llegar al bridge.
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskprestashop.orders.documents');

        $this->actingAs($user)
            ->get(route('manager.helpdesk.ps.ext.orderdocs.download', [$this->customer(), 816880, 'delivery_slip', 152313]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_download_serves_the_pdf_as_attachment_and_logs_it(): void
    {
        $customer = $this->customer();
        $pdf = "%PDF-1.7\n% prueba\n%%EOF";

        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'order_id' => 816880, 'type' => 'delivery_slip', 'doc_id' => 152313,
            'filename' => 'DE050805.pdf', 'mime' => 'application/pdf', 'size' => strlen($pdf),
            'content_base64' => base64_encode($pdf),
        ]])]);

        $user = $this->agent('helpdeskprestashop.orders.documents');
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($user)
            ->get(route('manager.helpdesk.ps.ext.orderdocs.download', [$customer, 816880, 'delivery_slip', 152313]).'?purpose=chat&conversation_id='.$conversation->id);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('albaran-DE050805.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame($pdf, $response->getContent());

        Http::assertSent(fn (Request $request) => ($request->data()['action'] ?? null) === 'orderdocs.pdf'
            && ($request->data()['type'] ?? null) === 'delivery_slip'
            && ($request->data()['doc_id'] ?? null) === 152313);

        $activity = Activity::query()->where('description', 'ps.order_document_download')->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertSame($user->id, (int) $activity->causer_id);
        $this->assertSame('chat', $activity->properties['purpose']);
        $this->assertSame($conversation->id, (int) $activity->properties['conversation_id']);
    }

    public function test_chat_purpose_refuses_a_conversation_of_another_customer(): void
    {
        Http::fake();

        $customer = $this->customer();
        $foreign = Conversation::factory()->create(['customer_id' => $this->customer()->id]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.documents'))
            ->getJson(route('manager.helpdesk.ps.ext.orderdocs.download', [$customer, 816880, 'delivery_slip', 152313]).'?purpose=chat&conversation_id='.$foreign->id)
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // El PDF ni siquiera se pide a PrestaShop.
        Http::assertNothingSent();
        $this->assertFalse(Activity::query()
            ->where('description', 'ps.order_document_download')
            ->where('subject_id', $customer->id)
            ->exists());
    }

    public function test_chat_purpose_requires_a_conversation(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.documents'))
            ->getJson(route('manager.helpdesk.ps.ext.orderdocs.download', [$this->customer(), 816880, 'delivery_slip', 152313]).'?purpose=chat')
            ->assertStatus(422)
            ->assertJsonValidationErrors('conversation_id');

        Http::assertNothingSent();
    }

    public function test_download_rejects_a_bridge_payload_that_is_not_a_pdf(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'filename' => 'x.pdf', 'content_base64' => base64_encode('<html>no</html>'),
        ]])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.documents'))
            ->get(route('manager.helpdesk.ps.ext.orderdocs.download', [$this->customer(), 816880, 'credit_slip', 63]))
            ->assertNotFound();
    }

    public function test_download_reports_semantic_failures_as_not_found(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['ok_semantic' => false, 'error' => 'pdf_generation_failed']])]);

        $this->actingAs($this->agent('helpdeskprestashop.orders.documents'))
            ->get(route('manager.helpdesk.ps.ext.orderdocs.download', [$this->customer(), 816880, 'invoice', 1]))
            ->assertNotFound();
    }

    public function test_unknown_document_type_does_not_match_the_route(): void
    {
        Http::fake();

        $this->actingAs($this->agent('helpdeskprestashop.orders.documents'))
            ->get('/panel/helpdesk/customers/'.$this->customer()->id.'/ps/orders/816880/orderdocs/documents/passport/1')
            ->assertNotFound();

        Http::assertNothingSent();
    }
}
