<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Document\Notifications\DocumentStageAdvanced;
use Tests\TestCase;

/**
 * Test Suite for Real-Time Document Notifications via Broadcasting
 *
 * Based on Twilio and Codea best practices for push notifications in Laravel:
 * - Verifies events implement ShouldBroadcast
 * - Confirms broadcast messages contain required data
 * - Validates frontend integration requirements
 *
 * NOTE: These tests do NOT use RefreshDatabase or factories to avoid
 * database conflicts. They validate the notification structure only.
 */
class NotificationBroadcastTest extends TestCase
{
    /**
     * Test broadcast message contains all required fields for desktop notifications
     *
     * This validates that our notification payload matches Twilio/Codea specs
     */
    public function test_broadcast_message_contains_required_fields_for_frontend(): void
    {
        // Create a mock user (not from database)
        $mockUser = new \stdClass;
        $mockUser->id = 123;
        $mockUser->name = 'Test User';

        // Create a mock document (minimal structure)
        $mockDocument = new \stdClass;
        $mockDocument->id = 456;
        $mockDocument->uid = 'DOC-UID-001';
        $mockDocument->order_id = 789;
        $mockDocument->order_reference = 'TEST-BROADCAST-001';
        $mockDocument->total_stages = 5;
        $mockDocument->getCurrentStageLabel = fn () => 'Validation';

        // Create notification with mocked objects
        $notification = new DocumentStageAdvanced(
            document: (object) $mockDocument,
            previousStage: 1,
            currentStage: 2,
            currentValidatorGroup: 'test_group'
        );

        // Get broadcast message data
        $broadcastMessage = $notification->toBroadcast($mockUser);
        $data = $broadcastMessage->data;

        // ✅ Verify all required fields exist (Twilio/Codea requirements)
        $this->assertArrayHasKey('id', $data, '❌ Missing: notification ID (needed for deduplication)');
        $this->assertArrayHasKey('title', $data, '❌ Missing: notification title');
        $this->assertArrayHasKey('message', $data, '❌ Missing: notification message');
        $this->assertArrayHasKey('icon', $data, '❌ Missing: icon class');
        $this->assertArrayHasKey('color', $data, '❌ Missing: color class');
        $this->assertArrayHasKey('action_url', $data, '❌ Missing: action URL');
        $this->assertArrayHasKey('created_at_full', $data, '❌ Missing: ISO8601 timestamp');
        $this->assertArrayHasKey('is_read', $data, '❌ Missing: read status');
        $this->assertArrayHasKey('priority', $data, '❌ Missing: priority level');
        $this->assertArrayHasKey('badge', $data, '❌ Missing: notification badge');

        // ✅ Verify field values
        $this->assertEquals('Nuevo documento asignado', $data['title']);
        $this->assertStringContainsString('requiere validación', $data['message']);
        $this->assertFalse($data['is_read'], 'New notifications should not be marked as read');
        $this->assertEquals('high', $data['priority']);
        $this->assertNotEmpty($data['id']);
    }

    /**
     * Test broadcast message structure matches frontend expectations
     */
    public function test_broadcast_message_is_properly_formatted(): void
    {
        $mockUser = new \stdClass;
        $mockUser->id = 999;

        $mockDocument = new \stdClass;
        $mockDocument->id = 111;
        $mockDocument->uid = 'DOC-TEST';
        $mockDocument->order_id = 222;
        $mockDocument->order_reference = 'REF-001';
        $mockDocument->total_stages = 3;
        $mockDocument->getCurrentStageLabel = fn () => 'Review';

        $notification = new DocumentStageAdvanced(
            document: (object) $mockDocument,
            previousStage: 1,
            currentStage: 2,
            currentValidatorGroup: 'reviewers'
        );

        $broadcastMessage = $notification->toBroadcast($mockUser);

        // Verify broadcast message structure
        $this->assertNotNull($broadcastMessage);
        $this->assertNotEmpty($broadcastMessage->data);

        // Verify it has the correct broadcast type
        $this->assertEquals('document.stage.advanced', $notification->broadcastType());
    }

    /**
     * Test notification uses public channel (follows Twilio best practices)
     */
    public function test_notification_uses_public_channel_not_private(): void
    {
        $mockUser = new \stdClass;
        $mockUser->id = 555;

        $mockDocument = new \stdClass;
        $mockDocument->id = 666;
        $mockDocument->uid = 'DOC-CHANNEL';
        $mockDocument->order_id = 777;
        $mockDocument->order_reference = 'CH-001';
        $mockDocument->total_stages = 2;
        $mockDocument->getCurrentStageLabel = fn () => 'Check';

        $notification = new DocumentStageAdvanced(
            document: (object) $mockDocument,
            previousStage: 1,
            currentStage: 2,
            currentValidatorGroup: 'checkers'
        );

        // Set recipient for broadcastOn()
        $notification->via($mockUser);
        $channels = $notification->broadcastOn();

        // ✅ Verify uses public channel (Twilio recommendation)
        $this->assertNotEmpty($channels, 'Notification should have broadcast channels');
        $channelName = $channels[0]->name;

        // Channel should be public (no auth required for WebSocket)
        $this->assertStringStartsWith('public-notifications.', $channelName);
        $this->assertStringContainsString('555', $channelName);
    }

    /**
     * Test via() method returns both database and broadcast channels
     */
    public function test_notification_uses_database_and_broadcast_channels(): void
    {
        $mockUser = new \stdClass;
        $mockUser->id = 333;

        $mockDocument = new \stdClass;
        $mockDocument->id = 444;
        $mockDocument->uid = 'DOC-VIA';
        $mockDocument->order_id = 550;
        $mockDocument->order_reference = 'VIA-001';
        $mockDocument->total_stages = 4;
        $mockDocument->getCurrentStageLabel = fn () => 'Processing';

        $notification = new DocumentStageAdvanced(
            document: (object) $mockDocument,
            previousStage: 2,
            currentStage: 3,
            currentValidatorGroup: 'processors'
        );

        // Get notification channels
        $channels = $notification->via($mockUser);

        // ✅ Verify both storage and broadcast are enabled
        $this->assertContains('database', $channels, 'Should save to database for persistence');
        $this->assertContains('broadcast', $channels, 'Should broadcast for real-time updates');
    }

    /**
     * Test broadcast message timestamp is in correct format for JavaScript
     */
    public function test_broadcast_includes_timestamp_for_javascript(): void
    {
        $mockUser = new \stdClass;
        $mockUser->id = 777;

        $mockDocument = new \stdClass;
        $mockDocument->id = 888;
        $mockDocument->uid = 'DOC-TIME';
        $mockDocument->order_id = 999;
        $mockDocument->order_reference = 'TIME-001';
        $mockDocument->total_stages = 1;
        $mockDocument->getCurrentStageLabel = fn () => 'Final';

        $notification = new DocumentStageAdvanced(
            document: (object) $mockDocument,
            previousStage: 0,
            currentStage: 1,
            currentValidatorGroup: 'final'
        );

        $broadcastMessage = $notification->toBroadcast($mockUser);
        $data = $broadcastMessage->data;

        // ✅ Verify timestamps exist and are correctly formatted
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('created_at_full', $data);

        // created_at should be HH:MM format for display
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $data['created_at']);

        // created_at_full should be ISO8601 format for JavaScript Date parsing
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}/', $data['created_at_full']);
    }

    /**
     * Test document reference is properly included in notification
     */
    public function test_notification_includes_document_reference(): void
    {
        $mockUser = new \stdClass;
        $mockUser->id = 1111;

        $mockDocument = new \stdClass;
        $mockDocument->id = 2222;
        $mockDocument->uid = 'DOC-REF';
        $mockDocument->order_id = 3333;
        $mockDocument->order_reference = 'ORD-REF-12345';
        $mockDocument->total_stages = 5;
        $mockDocument->getCurrentStageLabel = fn () => 'Verification';

        $notification = new DocumentStageAdvanced(
            document: (object) $mockDocument,
            previousStage: 1,
            currentStage: 2,
            currentValidatorGroup: 'verifiers'
        );

        $broadcastMessage = $notification->toBroadcast($mockUser);
        $data = $broadcastMessage->data;

        // ✅ Verify document reference is in the message
        $this->assertStringContainsString('ORD-REF-12345', $data['message']);
        $this->assertEquals('ORD-REF-12345', $data['order_reference']);
        $this->assertEquals(3333, $data['order_id']);
    }
}
