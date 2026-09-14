<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Settings;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * El bloque "Miembros" de crear/editar grupo.
 *
 * Se prueba el partial aislado y no la pantalla entera a propósito: la relación
 * TicketGroup::users() es un belongsToMany que cruza a la tabla `users` de la
 * conexión por defecto cualificándola con el nombre de la base. En runtime
 * funciona (misma instancia MySQL), pero bajo DatabaseTransactions la consulta
 * sale por otro PDO y no ve los usuarios recién creados, así que un test contra
 * la ruta mostraría siempre cero miembros por un motivo ajeno a esta vista.
 */
class TicketGroupMembersViewTest extends TestCase
{
    private const VIEW = 'theme.views.backups.helpdesk.ticket-groups._members';

    /** @param array<int, array{id: int, priority: string}> $members */
    private function render(array $members): string
    {
        return view(self::VIEW, [
            'users' => collect([
                (object) ['id' => 1, 'firstname' => 'Ana', 'lastname' => 'Perez'],
                (object) ['id' => 2, 'firstname' => 'Luis', 'lastname' => 'Gomez'],
            ]),
            'members' => $members,
            'errors' => new ViewErrorBag,
        ])->render();
    }

    public function test_renders_a_search_picker_instead_of_a_list_of_checkboxes(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('id="member-picker"', $html);
        $this->assertStringContainsString('Busca y elige un usuario', $html);
        // El botón "Agregar" desapareció: ahora se añade al elegir en el buscador.
        $this->assertStringNotContainsString('id="add-member"', $html);
    }

    public function test_renders_the_table_headers(): void
    {
        $html = $this->render([]);

        foreach (['Usuario', 'Prioridad', 'Accion'] as $header) {
            $this->assertStringContainsString('>'.$header.'</th>', $html);
        }
    }

    public function test_shows_an_empty_row_when_the_group_has_no_members(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('members-empty', $html);
        $this->assertStringContainsString('Todavia no hay miembros anadidos.', $html);
    }

    public function test_renders_a_row_per_member_with_its_priority_selected(): void
    {
        $html = $this->render([['id' => 2, 'priority' => 'backup']]);

        $this->assertStringContainsString('data-user-id="2"', $html);
        $this->assertStringContainsString('<input type="hidden" name="users[]" value="2">', $html);
        $this->assertStringContainsString('Luis Gomez', $html);
        $this->assertMatchesRegularExpression('/<option value="backup"\s+selected>/', $html);
        $this->assertStringNotContainsString('members-empty', $html);
    }

    public function test_a_user_already_in_the_group_is_disabled_in_the_picker(): void
    {
        // Se desactiva en el buscador en vez de dejar elegirlo y avisar después.
        $html = $this->render([['id' => 2, 'priority' => 'primary']]);

        $this->assertMatchesRegularExpression(
            '/<option value="2"[^>]*disabled>/s',
            $html,
            'El usuario ya añadido no debe poder elegirse otra vez.',
        );
        $this->assertDoesNotMatchRegularExpression('/<option value="1"[^>]*disabled>/s', $html);
    }

    public function test_member_names_are_escaped(): void
    {
        $html = view(self::VIEW, [
            'users' => collect([
                (object) ['id' => 7, 'firstname' => '<script>alert(1)</script>', 'lastname' => 'X'],
            ]),
            'members' => [['id' => 7, 'priority' => 'primary']],
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
