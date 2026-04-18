<?php

namespace Modules\Attention\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;
use Modules\Attention\Tests\TestCase;

class AttentionModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_generates_uid_automatically()
    {
        // Act
        $attention = $this->createAttention();

        // Assert
        $this->assertNotNull($attention->uid);
        $this->assertTrue(Str::isUuid($attention->uid));
    }

    /** @test */
    public function test_generates_radicado()
    {
        // Act
        $radicado = Attention::generateRadicado();

        // Assert
        $this->assertStringStartsWith('peticiones-', $radicado);
        $this->assertStringContainsString(date('Y'), $radicado);

        // Format: peticiones-YYYY-NNNNNN
        $pattern = '/^peticiones-\d{4}-\d{6}$/';
        $this->assertMatchesRegularExpression($pattern, $radicado);
    }

    /** @test */
    public function test_generates_sequential_radicados()
    {
        // Act
        $radicado1 = Attention::generateRadicado();
        $this->createAttention(['radicado' => $radicado1]);

        $radicado2 = Attention::generateRadicado();
        $this->createAttention(['radicado' => $radicado2]);

        // Assert
        $this->assertNotEquals($radicado1, $radicado2);

        // Extraer números
        preg_match('/-(\d+)$/', $radicado1, $matches1);
        preg_match('/-(\d+)$/', $radicado2, $matches2);

        $number1 = (int) $matches1[1];
        $number2 = (int) $matches2[1];

        $this->assertEquals($number1 + 1, $number2);
    }

    /** @test */
    public function test_can_add_document()
    {
        // Arrange
        $attention = $this->createAttention();
        $file = $this->createTestFile('test.pdf');

        // Act
        $media = $attention->addDocument($file, 'Test Document');

        // Assert
        $this->assertNotNull($media);
        $this->assertEquals('Test Document', $media->name);
        $this->assertTrue($attention->hasDocuments());
    }

    /** @test */
    public function test_can_remove_document()
    {
        // Arrange
        $attention = $this->createAttention();
        $media = $attention->addDocument($this->createTestFile('test.pdf'));

        // Act
        $result = $attention->removeDocument($media->id);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse($attention->hasDocuments());
    }

    /** @test */
    public function test_can_log_action()
    {
        // Arrange
        $attention = $this->createAttention();
        $user = $this->createUser();

        // Act
        $action = $attention->logAction('test_action', 'Test description', $user->id);

        // Assert
        $this->assertNotNull($action);
        $this->assertEquals('test_action', $action->action);
        $this->assertEquals('Test description', $action->description);
        $this->assertEquals($user->id, $action->user_id);

        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'test_action',
        ]);
    }

    /** @test */
    public function test_can_add_note()
    {
        // Arrange
        $attention = $this->createAttention();
        $user = $this->createUser();

        // Act
        $note = $attention->addNote('This is a test note', $user->id);

        // Assert
        $this->assertNotNull($note);
        $this->assertEquals('This is a test note', $note->content);
        $this->assertEquals($user->id, $note->user_id);

        $this->assertDatabaseHas('attention_notes', [
            'attention_id' => $attention->id,
            'content' => 'This is a test note',
        ]);
    }

    /** @test */
    public function test_can_change_status()
    {
        // Arrange
        $attention = $this->createAttention(['status' => AttentionStatus::RECEIVED]);

        // Act
        $attention->changeStatus(AttentionStatus::IN_PROCESS, 'Starting to work on this');

        // Assert
        $attention->refresh();
        $this->assertEquals(AttentionStatus::IN_PROCESS, $attention->status);

        // Verificar que se registró la acción
        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'status_changed',
        ]);
    }

    /** @test */
    public function test_can_assign_to_user()
    {
        // Arrange
        $attention = $this->createAttention();
        $user = $this->createUser();

        // Act
        $attention->assignTo($user->id, 'Assigned to specialist');

        // Assert
        $attention->refresh();
        $this->assertEquals($user->id, $attention->assigned_user_id);

        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'assigned',
        ]);
    }

    /** @test */
    public function test_can_assign_to_department()
    {
        // Arrange
        $attention = $this->createAttention();
        $department = $this->createDepartment();

        // Act
        $attention->assignToDepartment($department->id, 'Routing to department');

        // Assert
        $attention->refresh();
        $this->assertEquals($department->id, $attention->department_id);

        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'department_assigned',
        ]);
    }

    /** @test */
    public function test_can_resolve_attention()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $attention->resolve('Issue has been resolved', ResponseType::EMAIL);

        // Assert
        $attention->refresh();
        $this->assertEquals(AttentionStatus::RESOLVED, $attention->status);
        $this->assertEquals('Issue has been resolved', $attention->resolution);
        $this->assertEquals(ResponseType::EMAIL, $attention->response_type);
        $this->assertNotNull($attention->resolved_at);

        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'resolved',
        ]);
    }

    /** @test */
    public function test_can_close_attention()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $attention->close('Closing after resolution');

        // Assert
        $attention->refresh();
        $this->assertEquals(AttentionStatus::CLOSED, $attention->status);
        $this->assertNotNull($attention->closed_at);

        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'closed',
        ]);
    }

    /** @test */
    public function test_scope_recent_orders_by_date()
    {
        // Arrange
        $old = $this->createAttention(['customer_email' => 'old@example.com']);
        $old->created_at = now()->subDays(2);
        $old->save();

        $new = $this->createAttention(['customer_email' => 'new@example.com']);

        // Act
        $attentions = Attention::recent()->get();

        // Assert
        $this->assertEquals($new->id, $attentions->first()->id);
        $this->assertEquals($old->id, $attentions->last()->id);
    }

    /** @test */
    public function test_scope_by_radicado()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $result = Attention::byRadicado($attention->radicado)->first();

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($attention->id, $result->id);
    }

    /** @test */
    public function test_scope_by_status()
    {
        // Arrange
        $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);

        // Act
        $received = Attention::byStatus(AttentionStatus::RECEIVED)->get();

        // Assert
        $this->assertCount(2, $received);
    }

    /** @test */
    public function test_scope_pending()
    {
        // Arrange
        $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);

        // Act
        $pending = Attention::pending()->get();

        // Assert
        $this->assertCount(1, $pending);
    }

    /** @test */
    public function test_scope_in_process()
    {
        // Arrange
        $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);
        $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);

        // Act
        $inProcess = Attention::inProcess()->get();

        // Assert
        $this->assertCount(2, $inProcess);
    }

    /** @test */
    public function test_scope_resolved()
    {
        // Arrange
        $this->createAttention(['status' => AttentionStatus::RESOLVED]);
        $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);

        // Act
        $resolved = Attention::resolved()->get();

        // Assert
        $this->assertCount(1, $resolved);
    }

    /** @test */
    public function test_scope_closed()
    {
        // Arrange
        $this->createAttention(['status' => AttentionStatus::CLOSED]);
        $this->createAttention(['status' => AttentionStatus::CLOSED]);
        $this->createAttention(['status' => AttentionStatus::RESOLVED]);

        // Act
        $closed = Attention::closed()->get();

        // Assert
        $this->assertCount(2, $closed);
    }

    /** @test */
    public function test_scope_assigned_to()
    {
        // Arrange
        $user = $this->createUser();
        $this->createAttention(['assigned_user_id' => $user->id]);
        $this->createAttention(['assigned_user_id' => $user->id]);
        $this->createAttention();

        // Act
        $assigned = Attention::assignedTo($user->id)->get();

        // Assert
        $this->assertCount(2, $assigned);
    }

    /** @test */
    public function test_scope_by_department()
    {
        // Arrange
        $department = $this->createDepartment();
        $this->createAttention(['department_id' => $department->id]);
        $this->createAttention();

        // Act
        $byDept = Attention::byDepartment($department->id)->get();

        // Assert
        $this->assertCount(1, $byDept);
    }

    /** @test */
    public function test_scope_search()
    {
        // Arrange
        $this->createAttention(['subject' => 'Important request']);
        $this->createAttention(['description' => 'Something important']);
        $this->createAttention(['subject' => 'Unrelated topic']);

        // Act
        $results = Attention::search('important')->get();

        // Assert
        $this->assertCount(2, $results);
    }

    /** @test */
    public function test_can_check_if_can_be_edited()
    {
        // Arrange
        $received = $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $inProcess = $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);
        $resolved = $this->createAttention(['status' => AttentionStatus::RESOLVED]);
        $closed = $this->createAttention(['status' => AttentionStatus::CLOSED]);

        // Assert
        $this->assertTrue($received->canBeEdited());
        $this->assertTrue($inProcess->canBeEdited());
        $this->assertFalse($resolved->canBeEdited());
        $this->assertFalse($closed->canBeEdited());
    }

    /** @test */
    public function test_can_check_if_is_resolved()
    {
        // Arrange
        $received = $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $resolved = $this->createAttention(['status' => AttentionStatus::RESOLVED]);

        // Assert
        $this->assertFalse($received->isResolved());
        $this->assertTrue($resolved->isResolved());
    }

    /** @test */
    public function test_can_check_if_is_closed()
    {
        // Arrange
        $received = $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $closed = $this->createAttention(['status' => AttentionStatus::CLOSED]);

        // Assert
        $this->assertFalse($received->isClosed());
        $this->assertTrue($closed->isClosed());
    }

    /** @test */
    public function test_full_name_accessor_for_regular_user()
    {
        // Arrange
        $attention = $this->createAttention([
            'customer_firstname' => 'John',
            'customer_lastname' => 'Doe',
            'is_anonymous' => false,
        ]);

        // Assert
        $this->assertEquals('JOHN DOE', $attention->full_name);
    }

    /** @test */
    public function test_full_name_accessor_for_anonymous_user()
    {
        // Arrange
        $attention = $this->createAnonymousAttention();

        // Assert
        $this->assertEquals('ANÓNIMO', $attention->full_name);
    }

    /** @test */
    public function test_status_label_accessor()
    {
        // Arrange
        $attention = $this->createAttention(['status' => AttentionStatus::RECEIVED]);

        // Assert
        $this->assertEquals('Recibido', $attention->status_label);
    }

    /** @test */
    public function test_names_are_converted_to_uppercase()
    {
        // Arrange & Act
        $attention = $this->createAttention([
            'customer_firstname' => 'maría',
            'customer_lastname' => 'garcía',
        ]);

        // Assert
        $this->assertEquals('MARÍA', $attention->customer_firstname);
        $this->assertEquals('GARCÍA', $attention->customer_lastname);
    }

    /** @test */
    public function test_relationships_are_loaded_correctly()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act & Assert
        $this->assertInstanceOf(AttentionType::class, $attention->type);
        $this->assertInstanceOf(AttentionCategory::class, $attention->category);
        $this->assertInstanceOf(AttentionSede::class, $attention->sede);
    }
}
