<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailLog\Database\Seeders\HelpdeskEmailLogPermissionsSeeder;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Jobs\ResendEmailLogJob;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Tests\TestCase;

class EmailLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailLogPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemaillog.view');
    }

    private function manager(): User
    {
        return tap(User::factory()->create())->givePermissionTo(['helpdeskemaillog.view', 'helpdeskemaillog.manage']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('helpdeskemaillog.index'))->assertRedirect();
    }

    public function test_index_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('helpdeskemaillog.index'))
            ->assertForbidden();
    }

    public function test_index_renders_with_logs_and_stats(): void
    {
        EmailLog::factory()->count(3)->create();
        EmailLog::factory()->failed()->create();

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index'))
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::emails.index')
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 4 && $stats['failed'] === 1);
    }

    public function test_index_filters_by_status_and_module(): void
    {
        EmailLog::factory()->forModule('Auth')->create(['subject' => 'Reset link']);
        EmailLog::factory()->forModule('Newsletter')->failed()->create(['subject' => 'Weekly digest']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('Weekly digest')
            ->assertDontSee('Reset link');

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['module' => 'Auth']))
            ->assertOk()
            ->assertSee('Reset link')
            ->assertDontSee('Weekly digest');
    }

    public function test_show_displays_the_email_preview(): void
    {
        $log = EmailLog::factory()->create(['subject' => 'Order confirmation']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::emails.preview')
            ->assertSee('Order confirmation');
    }

    public function test_show_returns_404_for_unknown_uid(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', Str::orderedUuid()))
            ->assertNotFound();
    }

    public function test_destroy_requires_manage_permission(): void
    {
        $log = EmailLog::factory()->create();

        $this->actingAs($this->viewer())
            ->delete(route('helpdeskemaillog.destroy', $log->uid))
            ->assertForbidden();

        $this->assertDatabaseHas('email_logs', ['id' => $log->id]);
    }

    public function test_manager_can_delete_a_log(): void
    {
        $log = EmailLog::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.destroy', $log->uid))
            ->assertRedirect(route('helpdeskemaillog.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('email_logs', ['id' => $log->id]);
    }

    public function test_manager_can_bulk_delete_logs(): void
    {
        $logs = EmailLog::factory()->count(3)->create();
        $keep = EmailLog::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.bulk-destroy'), ['uids' => $logs->pluck('uid')->all()])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('email_logs', 1);
        $this->assertDatabaseHas('email_logs', ['id' => $keep->id]);
    }

    public function test_bulk_delete_validates_input(): void
    {
        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.bulk-destroy'), ['uids' => []])
            ->assertSessionHasErrors('uids');
    }

    public function test_export_returns_a_csv_download(): void
    {
        EmailLog::factory()->create(['subject' => 'Exported subject']);

        $response = $this->actingAs($this->viewer())->get(route('helpdeskemaillog.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('Exported subject', $response->streamedContent());
    }

    public function test_resend_dispatches_job_for_manager(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create([
            'to_addresses' => ['client@example.test'],
            'subject' => 'Please resend me',
            'status' => EmailStatus::Sent,
        ]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(ResendEmailLogJob::class, fn (ResendEmailLogJob $job) => $job->emailLogId === $log->id);
    }

    public function test_resend_requires_manage_permission(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create(['to_addresses' => ['client@example.test']]);

        $this->actingAs($this->viewer())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_resend_returns_error_when_no_recipients(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create(['to_addresses' => []]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    public function test_bulk_destroy_rejects_non_uuid_values(): void
    {
        EmailLog::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.bulk-destroy'), ['uids' => ['not-a-uuid']])
            ->assertSessionHasErrors('uids.0');
    }

    public function test_export_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('helpdeskemaillog.export'))
            ->assertForbidden();
    }

    public function test_index_search_finds_by_recipient(): void
    {
        EmailLog::factory()->create([
            'subject' => 'Welcome aboard',
            'to_addresses' => ['needle@example.test'],
        ]);
        EmailLog::factory()->create([
            'subject' => 'Other message',
            'to_addresses' => ['other@example.test'],
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['search' => 'needle@example.test']))
            ->assertOk()
            ->assertSee('Welcome aboard')
            ->assertDontSee('Other message');
    }
}
