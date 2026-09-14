<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\Public\SimulatorContext;
use Modules\Helpdesk\Services\Public\SimulatorOutboundMessageService;
use Tests\TestCase;

/**
 * Safety coverage for the public simulator's outbound guard.
 *
 * The single most important guarantee of the feature: an agent reply to a
 * SIMULATED conversation must NEVER hit the real WhatsApp/Facebook/Instagram
 * Graph API, even though the conversation keeps its real channel slug. This is
 * enforced by SimulatorOutboundMessageService, which is bound in place of
 * OutboundMessageService whenever the simulator is enabled.
 *
 * Does not extend HelpdeskTestCase and never persists a model — supports() /
 * sendReply() only read in-memory attributes, so no DB is touched.
 */
class SimulatorOutboundGuardTest extends TestCase
{
    private function makeConversation(array $attributes): Conversation
    {
        // Unsaved model — attributes are readable without any DB query.
        return new Conversation($attributes);
    }

    public function test_simulator_decorator_is_bound_when_enabled(): void
    {
        config(['helpdesk.simulator_public_enabled' => true]);

        // Re-bind the way the service provider does, so the test is independent
        // of resolution order.
        $this->app->forgetInstance(OutboundMessageService::class);
        $this->app->singleton(OutboundMessageService::class, SimulatorOutboundMessageService::class);

        $this->assertInstanceOf(
            SimulatorOutboundMessageService::class,
            $this->app->make(OutboundMessageService::class),
        );
    }

    public function test_outbound_is_blocked_for_a_simulated_conversation(): void
    {
        $service = $this->app->make(SimulatorOutboundMessageService::class);

        $conversation = $this->makeConversation([
            'channel' => 'facebook',
            'external_sender_id' => 'PSID_sim_ABC123',
            'metadata' => ['is_simulator' => true],
        ]);

        $this->assertFalse(
            $service->supports($conversation),
            'A simulated conversation must not support outbound external sends.',
        );
        $this->assertNull(
            $service->sendReply($conversation, 'Hola'),
            'sendReply must short-circuit to null without calling the channel API.',
        );
    }

    public function test_outbound_is_allowed_for_a_real_conversation(): void
    {
        $service = $this->app->make(SimulatorOutboundMessageService::class);

        $conversation = $this->makeConversation([
            'channel' => 'facebook',
            'external_sender_id' => 'PSID_real_999',
            // no is_simulator flag → a genuine channel conversation
        ]);

        $this->assertTrue(
            $service->supports($conversation),
            'The decorator must not break outbound for real (non-simulated) conversations.',
        );
    }

    public function test_simulator_context_blocks_outbound_during_injection(): void
    {
        $service = $this->app->make(SimulatorOutboundMessageService::class);

        // A conversation NOT yet tagged as a simulator (mirrors the window
        // between processor-create and metadata-write during startSession).
        $conversation = $this->makeConversation([
            'channel' => 'whatsapp',
            'external_sender_id' => '34600111222',
            'metadata' => [],
        ]);

        $this->assertTrue($service->supports($conversation), 'Outside the simulator context, a real conversation sends normally.');

        SimulatorContext::run(function () use ($service, $conversation): void {
            $this->assertFalse(
                $service->supports($conversation),
                'While the simulator is injecting, all outbound must be blocked.',
            );
        });

        $this->assertTrue($service->supports($conversation), 'The context flag must be restored after injection.');
    }

    public function test_simulator_context_restores_flag_even_on_exception(): void
    {
        $this->assertFalse(SimulatorContext::active());

        try {
            SimulatorContext::run(function (): void {
                $this->assertTrue(SimulatorContext::active());
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertFalse(SimulatorContext::active(), 'The flag must be restored even when the callback throws.');
    }
}
