<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * QUAL-09 / item 8: transversal invariant covering every conversation-scoped
 * mutating route across the helpdesk-family modules (Helpdesk, HelpdeskDocument,
 * HelpdeskTickets, HelpdeskPrestashop, ...), not just Document.
 *
 * Same philosophy as HelpdeskDocument's DocumentActionAuthorizationTest
 * (SEC-02): the route list is DERIVED from Route::getRoutes(), never
 * hand-maintained, so a brand-new mutating route under
 * manager.helpdesk.conversations.* is covered automatically the moment it
 * ships, instead of relying on someone remembering to add it to a list.
 *
 * Scope: a user with NO helpdesk permission whatsoever (not an agent, not a
 * manager) must never get a 2xx from any of these routes, regardless of
 * which mechanism blocks it (route middleware, form-request authorize(),
 * or an in-controller ownership guard) — modules in this family were found
 * to use different mechanisms (Helpdesk: `can:helpdesk.view` middleware;
 * HelpdeskDocument: in-controller checks only, see DocumentActionAuthorizationTest;
 * HelpdeskTickets: `role:super-admin|super-settings`), so only an end-to-end
 * HTTP assertion actually verifies the invariant across all of them.
 *
 * This intentionally does not assert a specific status code (403 vs 404 vs
 * 422 vs redirect): any of those are valid "blocked" outcomes depending on
 * where the guard sits. Only a 2xx is a failure.
 */
class ConversationMutatingRoutesAuthorizationTest extends HelpdeskTestCase
{
    /**
     * @return array<int, array{0: string, 1: string, 2: string}> [route name, verb, uri]
     */
    private function mutatingConversationRoutes(): array
    {
        $mutatingVerbs = ['DELETE', 'PUT', 'PATCH', 'POST'];

        return collect(Route::getRoutes())
            ->filter(function ($route) use ($mutatingVerbs) {
                $name = $route->getName();

                if (! $name || ! str_starts_with($name, 'manager.helpdesk.conversations.')) {
                    return false;
                }

                if (! str_contains($route->uri(), '{conversation}')) {
                    return false;
                }

                return array_intersect($mutatingVerbs, $route->methods()) !== [];
            })
            ->map(function ($route) use ($mutatingVerbs) {
                $verb = strtolower(collect($mutatingVerbs)->first(fn ($m) => in_array($m, $route->methods(), true)));

                return [$route->getName(), $verb, $route->uri()];
            })
            ->values()
            ->all();
    }

    /**
     * Fill {conversation} with the real fixture and any other route
     * parameter with a harmless placeholder id: a 404 from failed model
     * binding on a secondary resource is just as valid a "blocked" outcome
     * as a 403 from a permission check, so the exact id doesn't matter.
     */
    private function urlFor(string $uri, Conversation $conversation): string
    {
        $uri = str_replace('{conversation}', (string) $conversation->id, $uri);
        $uri = preg_replace('/\{[^}]+\}/', '999999999', $uri);

        return '/'.ltrim($uri, '/');
    }

    public function test_every_mutating_conversation_route_rejects_a_user_without_any_helpdesk_permission(): void
    {
        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);
        $outsider = User::factory()->create();

        $routes = $this->mutatingConversationRoutes();

        $this->assertNotEmpty($routes, 'No mutating conversation routes were discovered — the route filter may be broken.');

        foreach ($routes as [$name, $verb, $uri]) {
            $url = $this->urlFor($uri, $conversation);

            $status = $this->actingAs($outsider)->{$verb}($url)->getStatusCode();

            $this->assertTrue(
                $status < 200 || $status >= 300,
                "La ruta '{$name}' ({$verb} {$url}) devolvió {$status} para un usuario sin ningún permiso de helpdesk."
            );
        }
    }
}
