<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Support\HmacSigner;
use Tests\TestCase;

/**
 * Entrada de opiniones desde la tienda.
 */
class ReviewEventReceiverTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql', 'helpdesk'];

    private const SECRET = 'secreto-de-pruebas-para-opiniones';

    protected function setUp(): void
    {
        parent::setUp();

        config(['reviews.secret' => self::SECRET]);
    }

    private function envia(array $payload, string $event = 'review.created', ?int $timestamp = null, ?string $signature = null)
    {
        $timestamp ??= time();
        $body = json_encode(['event' => $event, 'data' => $payload, 'timestamp' => $timestamp]);

        return $this->call(
            'POST',
            '/api/reviews/webhooks/event',
            [],
            [],
            [],
            [
                'HTTP_X-Alsernet-Event' => $event,
                'HTTP_X-Alsernet-Timestamp' => (string) $timestamp,
                'HTTP_X-Alsernet-Signature' => $signature ?? HmacSigner::sign(self::SECRET, $timestamp, $body),
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'id_productcomment' => 999000001,
            'id_source_comment' => null,
            'id_product' => 30481,
            'id_customer' => 4242,
            'id_lang' => 1,
            'lang_iso' => 'es',
            'stars' => 8,
            'nick' => 'Cliente de prueba',
            'title' => 'Buen producto',
            'comment' => 'Funciona como esperaba.',
            'answer' => '',
            'active' => 0,
            'date' => '2026-09-01 10:00:00',
            'date_upd' => '2026-09-01 10:00:00',
            'product' => ['name' => 'Trípode Primos', 'reference' => 'REF123'],
            'customer' => ['email' => 'cliente@ejemplo.test'],
            'order' => ['id_order' => 5555, 'reference' => 'ABCDEF'],
        ], $extra);
    }

    public function test_rechaza_una_peticion_sin_firma_valida(): void
    {
        $this->envia($this->payload(), 'review.created', time(), 'firma-inventada')
            ->assertStatus(401);

        $this->assertDatabaseMissing('product_reviews', ['ps_comment_id' => 999000001], 'helpdesk');
    }

    public function test_rechaza_una_firma_caducada(): void
    {
        $viejo = time() - 3600;
        $body = json_encode(['event' => 'review.created', 'data' => $this->payload(), 'timestamp' => $viejo]);

        $this->envia($this->payload(), 'review.created', $viejo, HmacSigner::sign(self::SECRET, $viejo, $body))
            ->assertStatus(401);
    }

    public function test_registra_la_opinion_como_pendiente(): void
    {
        $this->envia($this->payload())->assertOk();

        $review = Review::where('ps_comment_id', 999000001)->first();

        $this->assertNotNull($review);
        $this->assertSame(Review::STATUS_PENDING, $review->status);
        $this->assertSame(8, $review->stars);
        $this->assertSame(4.0, $review->rating);
        $this->assertSame('Trípode Primos', $review->product_name);
        $this->assertSame('ABCDEF', $review->order_reference);
        $this->assertFalse($review->ps_active);
        $this->assertSame('received', $review->events()->first()->event);
    }

    public function test_una_opinion_que_llega_publicada_nace_aprobada(): void
    {
        $this->envia($this->payload(['active' => 1]))->assertOk();

        $review = Review::where('ps_comment_id', 999000001)->first();

        $this->assertSame(Review::STATUS_APPROVED, $review->status);
        $this->assertTrue($review->ps_active);
    }

    public function test_recibirla_dos_veces_no_la_duplica(): void
    {
        $this->envia($this->payload())->assertOk();
        $this->envia($this->payload(), 'review.updated')->assertOk();

        $this->assertSame(1, Review::where('ps_comment_id', 999000001)->count());
    }

    public function test_una_traduccion_no_se_registra_como_opinion(): void
    {
        $this->envia($this->payload([
            'id_productcomment' => 999000002,
            'id_source_comment' => 999000001,
            'id_lang' => 3,
            'lang_iso' => 'fr',
        ]))->assertOk();

        $this->assertDatabaseMissing('product_reviews', ['ps_comment_id' => 999000002], 'helpdesk');
    }

    public function test_detecta_que_la_tienda_y_el_panel_discrepan(): void
    {
        $this->envia($this->payload())->assertOk();

        $review = Review::where('ps_comment_id', 999000001)->first();
        $review->update(['status' => Review::STATUS_APPROVED]);

        // La opinión sigue oculta en la tienda: alguien la tumbó por el otro lado.
        $this->assertTrue($review->fresh()->hasConflict());

        // Y cuando la tienda la publica, deja de haber discrepancia.
        $this->envia($this->payload(['active' => 1]), 'review.updated')->assertOk();

        $this->assertFalse($review->fresh()->hasConflict());
    }
}
