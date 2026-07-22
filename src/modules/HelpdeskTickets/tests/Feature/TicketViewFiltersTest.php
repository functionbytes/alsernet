<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * Filtros nuevos de las vistas guardadas (colas): mine / unassigned / tags /
 * rango de fechas. Se prueba applyFilters() directamente sobre la query.
 */
class TicketViewFiltersTest extends TestCase
{
    use SharesHelpdeskPdo;

    private User $agent;

    private Customer $customer;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::factory()->create();
        $this->customer = Customer::firstOrCreate(
            ['email' => 'view-filters@example.com'],
            ['name' => 'View Filters Customer']
        );
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_mine_filter_returns_only_tickets_assigned_to_the_user(): void
    {
        $mine = $this->ticket(['assignee_id' => $this->agent->id]);
        $other = $this->ticket(['assignee_id' => User::factory()->create()->id]);

        $ids = $this->makeView(['mine' => true])->applyFilters(Ticket::query(), $this->agent->id)->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_unassigned_filter_returns_only_tickets_without_assignee(): void
    {
        $unassigned = $this->ticket(['assignee_id' => null]);
        $assigned = $this->ticket(['assignee_id' => $this->agent->id]);

        $ids = $this->makeView(['unassigned' => true])->applyFilters(Ticket::query())->pluck('id');

        $this->assertTrue($ids->contains($unassigned->id));
        $this->assertFalse($ids->contains($assigned->id));
    }

    public function test_tags_filter_matches_tickets_containing_any_given_tag(): void
    {
        $vip = $this->ticket(['tags' => ['vip', 'urgente']]);
        $plain = $this->ticket(['tags' => ['normal']]);

        $ids = $this->makeView(['tags' => ['vip']])->applyFilters(Ticket::query())->pluck('id');

        $this->assertTrue($ids->contains($vip->id));
        $this->assertFalse($ids->contains($plain->id));
    }

    public function test_created_date_range_filters_by_creation_date(): void
    {
        $old = $this->ticket([]);
        $old->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
        $recent = $this->ticket([]);

        $ids = $this->makeView(['created_from' => now()->subDays(2)->toDateString()])
            ->applyFilters(Ticket::query())->pluck('id');

        $this->assertTrue($ids->contains($recent->id));
        $this->assertFalse($ids->contains($old->id));
    }

    private function makeView(array $filters): TicketView
    {
        $view = new TicketView;
        $view->filters = $filters;

        return $view;
    }

    private function ticket(array $overrides): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'View filter ticket',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
