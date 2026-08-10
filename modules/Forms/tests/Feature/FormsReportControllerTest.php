<?php

namespace Modules\Forms\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Forms\Models\Form;
use Modules\HelpdeskTickets\Database\Factories\TicketCategoryFactory;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FormsReportControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketStatus $openStatus;

    private TicketStatus $closedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#6c757d', 'is_open' => false, 'order' => 2]
        );
    }

    public function test_guest_cannot_view_the_report(): void
    {
        $this->get('/panel/forms/report')->assertRedirect();
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = $this->makeUser([]);

        $this->actingAs($user)
            ->get('/panel/forms/report')
            ->assertForbidden();
    }

    public function test_report_shows_the_form_with_zero_counts_when_it_has_no_tickets_yet(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view']);
        $form = $this->makeForm('contact', 'contacto-general');

        $response = $this->actingAs($user)->get('/panel/forms/report');

        $response->assertOk();
        $response->assertSee($form->name);
        $response->assertViewHas('rows', function ($rows) use ($form) {
            $row = $rows->firstWhere('form.id', $form->id);

            return $row !== null && $row['total'] === 0 && $row['open'] === 0;
        });
    }

    public function test_report_counts_tickets_by_category_and_open_status(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view']);
        $form = $this->makeForm('exchangesandreturns', 'devoluciones-cambios');
        $otherForm = $this->makeForm('fitting', 'cita-fitting');

        $this->createFormTicket($form->category, $this->openStatus);
        $this->createFormTicket($form->category, $this->openStatus);
        $this->createFormTicket($form->category, $this->closedStatus);
        // Ticket de la misma categoría pero de OTRO canal: no debe contar.
        $this->createFormTicket($form->category, $this->openStatus, ['source' => 'email']);
        // Ticket de formulario pero de otra categoría: no debe mezclarse.
        $this->createFormTicket($otherForm->category, $this->openStatus);

        $response = $this->actingAs($user)->get('/panel/forms/report');

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) use ($form) {
            $row = $rows->firstWhere('form.id', $form->id);

            return $row !== null && $row['total'] === 3 && $row['open'] === 2;
        });
    }

    public function test_report_shows_form_without_category_as_misconfigured(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view']);
        Form::create(['form_key' => 'orphan', 'name' => 'Huerfano', 'category_id' => null, 'active' => true]);

        $response = $this->actingAs($user)->get('/panel/forms/report');

        $response->assertOk();
        $response->assertSee('Sin categoría');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<int, string>  $permissions
     */
    private function makeUser(array $permissions): User
    {
        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function makeForm(string $formKey, string $categorySlug): Form
    {
        $category = TicketCategoryFactory::new()->create(['slug' => $categorySlug, 'active' => true]);

        return Form::create([
            'form_key' => $formKey,
            'name' => $categorySlug,
            'category_id' => $category->id,
            'active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createFormTicket(TicketCategory $category, TicketStatus $status, array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => $category->name,
            'description' => 'Test.',
            'status_id' => $status->id,
            'category_id' => $category->id,
            'priority' => 'normal',
            'source' => 'formulario',
        ], $overrides));
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
