<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketStatusesController;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class TicketStatusesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketStatusesController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketStatus::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-statuses.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-statuses.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-statuses.destroy'));
    }

    /**
     * Bug real (28-sep-2026): desmarcar "predeterminado" del único estado
     * que lo tenía dejaba is_default=false en TODAS las filas del catálogo
     * — TicketObserver::creating() se quedaba sin de dónde sacar el
     * status_id de un ticket nuevo (ver la migración de datos hermana que
     * repara los tickets así en local).
     */
    public function test_update_rejects_unmarking_the_only_default_status(): void
    {
        $this->withoutMiddleware(RoleMiddleware::class);

        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.tickets.settings');

        TicketStatus::where('is_default', true)->update(['is_default' => false]);
        $onlyDefault = TicketStatus::create([
            'name' => 'Unico predeterminado',
            'slug' => 'unico-predeterminado-'.uniqid(),
            'color' => '#13C672',
            'is_open' => true,
            'is_default' => true,
            'order' => 1,
        ]);

        $this->actingAs($user)
            ->put(route('manager.helpdesk.settings.ticket-statuses.update', $onlyDefault), [
                'name' => $onlyDefault->name,
                'color' => $onlyDefault->color,
                'is_open' => true,
                'is_default' => false,
            ])
            ->assertSessionHasErrors(['is_default']);

        $this->assertTrue($onlyDefault->fresh()->is_default);
    }

    public function test_update_allows_unmarking_default_when_another_default_exists(): void
    {
        $this->withoutMiddleware(RoleMiddleware::class);

        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.tickets.settings');

        TicketStatus::where('is_default', true)->update(['is_default' => false]);
        $otherDefault = TicketStatus::create([
            'name' => 'Otro predeterminado',
            'slug' => 'otro-predeterminado-'.uniqid(),
            'color' => '#13C672',
            'is_open' => true,
            'is_default' => true,
            'order' => 1,
        ]);
        $current = TicketStatus::create([
            'name' => 'A desmarcar',
            'slug' => 'a-desmarcar-'.uniqid(),
            'color' => '#0D6EFD',
            'is_open' => true,
            'is_default' => false,
            'order' => 2,
        ]);
        // saveQuietly(): salta el hook booted()::updating que demote otros
        // is_default automáticamente, para simular los dos true a la vez que
        // el guard del controlador debe detectar.
        $current->forceFill(['is_default' => true])->saveQuietly();

        $this->actingAs($user)
            ->put(route('manager.helpdesk.settings.ticket-statuses.update', $current), [
                'name' => $current->name,
                'color' => $current->color,
                'is_open' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $this->assertFalse($current->fresh()->is_default);
        $this->assertTrue($otherDefault->fresh()->is_default);
    }
}
