<?php

namespace Modules\HelpdeskChatFlow\tests\Unit;

use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the context write buffer on ChatFlowSession collapses a run's many
 * setContextValue() calls into a single persisting UPDATE while keeping reads
 * correct and the final context identical to the unbuffered behaviour.
 */
class ChatFlowSessionContextBufferTest extends TestCase
{
    private function makeSession(): RecordingChatFlowSession
    {
        $session = new RecordingChatFlowSession;
        $session->context = [];

        return $session;
    }

    public function test_unbuffered_writes_persist_immediately(): void
    {
        $session = $this->makeSession();

        $session->setContextValue('a', 1);
        $session->setContextValue('b', 2);

        $this->assertCount(2, $session->persistedUpdates);
        $this->assertSame(['a' => 1, 'b' => 2], $session->context);
    }

    public function test_buffered_writes_collapse_to_single_update(): void
    {
        $session = $this->makeSession();

        $session->withBufferedContext(function () use ($session) {
            $session->setContextValue('a', 1);
            $session->setContextValue('b', 2);
            $session->setContextValues(['c' => 3, 'd' => 4]);
        });

        $this->assertCount(1, $session->persistedUpdates);
        $this->assertSame(
            ['context' => ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]],
            $session->persistedUpdates[0],
        );
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $session->context);
    }

    public function test_reads_during_buffer_see_latest_value(): void
    {
        $session = $this->makeSession();

        $session->withBufferedContext(function () use ($session) {
            $session->setContextValue('a', 1);
            $this->assertSame(1, $session->getContextValue('a'));

            $session->setContextValue('a', 99);
            $this->assertSame(99, $session->getContextValue('a'));
        });

        $this->assertSame(99, $session->getContextValue('a'));
    }

    public function test_unrelated_update_inside_buffer_piggybacks_context(): void
    {
        $session = $this->makeSession();

        $session->withBufferedContext(function () use ($session) {
            $session->setContextValue('a', 1);
            // A status/current_node_id style update mid-run must carry the buffered
            // context so the persisted row is never stale.
            $session->update(['current_node_id' => 'n2']);
        });

        // One UPDATE (the current_node_id one, with context merged); the flush
        // finds nothing left dirty and adds no extra write.
        $this->assertCount(1, $session->persistedUpdates);
        $this->assertSame(
            ['current_node_id' => 'n2', 'context' => ['a' => 1]],
            $session->persistedUpdates[0],
        );
    }

    public function test_nested_buffers_flush_once_at_outermost(): void
    {
        $session = $this->makeSession();

        $session->withBufferedContext(function () use ($session) {
            $session->setContextValue('a', 1);

            $session->withBufferedContext(function () use ($session) {
                $session->setContextValue('b', 2);
            });

            // Inner scope must not have flushed yet.
            $this->assertCount(0, $session->persistedUpdates);

            $session->setContextValue('c', 3);
        });

        $this->assertCount(1, $session->persistedUpdates);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $session->context);
    }

    public function test_buffer_flushes_even_when_callback_throws(): void
    {
        $session = $this->makeSession();

        try {
            $session->withBufferedContext(function () use ($session) {
                $session->setContextValue('a', 1);
                throw new \RuntimeException('boom');
            });
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertCount(1, $session->persistedUpdates);
        $this->assertSame(['a' => 1], $session->context);
    }
}

/**
 * DB-free ChatFlowSession that records persisted update() payloads instead of
 * hitting the database, so the buffer's write behaviour can be asserted.
 */
class RecordingChatFlowSession extends ChatFlowSession
{
    /** @var array<int, array<string, mixed>> */
    public array $persistedUpdates = [];

    public function __construct()
    {
        // Skip Eloquent's constructor: no DB connection needed for these tests.
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        // Mirror the real model's piggyback (protected $contextDirty), then record
        // the persisted payload instead of writing to the database.
        if ($this->contextDirty && ! array_key_exists('context', $attributes)) {
            $attributes['context'] = $this->context ?? [];
            $this->contextDirty = false;
        }

        if (array_key_exists('context', $attributes)) {
            $this->context = $attributes['context'];
        }

        $this->persistedUpdates[] = $attributes;

        return true;
    }
}
