<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Guards the helpdesk-scoped proxy endpoints that replace the weakly-protected
 * `api.documents.*` mutating routes. Every action must require the helpdesk
 * permission AND the conversation↔document ownership (customer email match).
 */
class DocumentActionAuthorizationTest extends HelpdeskTestCase
{
    /**
     * The full set of mutating endpoints under
     * `manager.helpdesk.conversations.documents.*` that resolve a document by
     * URL binding (`{conversation}/{document}[/...]`).
     *
     * Derived from the router instead of hand-maintained: a hand-written list
     * previously omitted `link` (DocumentCreateController::link) entirely, so
     * a brand-new mutating route silently shipped without ownership coverage
     * in this test. Enumerating from Route::getRoutes() means any future
     * route matching the same URL shape is covered automatically.
     *
     * Excludes routes without both `{conversation}` and `{document}` in the
     * URI (`store`, `import-from-chat`, `import-from-device`): they resolve
     * the target document differently and don't fit this test's
     * conversation+document(+id) harness.
     *
     * @return array<int, array{0: string, 1: string, 2: bool}>
     *                                                          [route name suffix, HTTP verb, needs trailing id binding]
     */
    private function mutatingActions(): array
    {
        $mutatingVerbs = ['DELETE', 'PUT', 'PATCH', 'POST'];

        return collect(Route::getRoutes())
            ->filter(function ($route) use ($mutatingVerbs) {
                $name = $route->getName();

                if (! $name || ! str_starts_with($name, 'manager.helpdesk.conversations.documents.')) {
                    return false;
                }

                if (! str_contains($route->uri(), '{conversation}') || ! str_contains($route->uri(), '{document}')) {
                    return false;
                }

                return array_intersect($mutatingVerbs, $route->methods()) !== [];
            })
            ->map(function ($route) use ($mutatingVerbs) {
                $verb = strtolower(collect($mutatingVerbs)->first(fn ($m) => in_array($m, $route->methods(), true)));
                $action = substr($route->getName(), strlen('manager.helpdesk.conversations.documents.'));

                // More than {conversation}/{document} in the URI means a third
                // binding (noteId/mediaId/docType) the test must fill in.
                $needsId = substr_count($route->uri(), '{') > 2;

                return [$action, $verb, $needsId];
            })
            ->values()
            ->all();
    }

    /**
     * Create a conversation and a document that belong to the same customer.
     *
     * @return array{0: Conversation, 1: Document}
     */
    private function makeOwnedExpediente(string $email = 'cliente@example.com'): array
    {
        $customer = Customer::factory()->create(['email' => $email]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = Document::create([
            'customer_email' => $email,
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
        ]);

        return [$conversation, $document];
    }

    /**
     * Minimal payload that PASSES each action's Form Request validation.
     *
     * Necesario para el test de ownership: los Form Request se validan ANTES de
     * que corra el cuerpo del controller, asi que con un payload vacio las
     * acciones con campos obligatorios responderian 422 (validacion) y el test
     * nunca llegaria — ni probaria — el guard assertDocumentBelongsToConversation().
     *
     * @return array<string, mixed>
     */
    private function validPayloadFor(string $action): array
    {
        return match ($action) {
            'reject-stage' => ['reason' => 'Documento ilegible, vuelve a enviarlo.'],
            'send-custom-email' => ['subject' => 'Asunto', 'message' => 'Mensaje de prueba lo bastante largo.'],
            'notes.add' => ['content' => 'Nota de prueba'],
            'upload-attachment' => ['file' => UploadedFile::fake()->image('adjunto.jpg')],
            'update' => ['data' => ['customer_firstname' => 'Intruso']],
            default => [],
        };
    }

    private function urlFor(string $action, Conversation $conversation, Document $document, bool $needsId): string
    {
        $params = [$conversation->id, $document->id];

        if ($needsId) {
            $params[] = 1; // noteId / mediaId placeholder
        }

        return route('manager.helpdesk.conversations.documents.'.$action, $params);
    }

    public function test_guest_is_redirected_from_mutating_action(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->post($this->urlFor('assign', $conversation, $document, false), [
            'assigned_user_id' => null,
        ])->assertRedirect();
    }

    public function test_user_without_permission_cannot_perform_any_mutating_action(): void
    {
        $user = User::factory()->create();
        [$conversation, $document] = $this->makeOwnedExpediente();

        foreach ($this->mutatingActions() as [$action, $verb, $needsId]) {
            $url = $this->urlFor($action, $conversation, $document, $needsId);

            $this->actingAs($user)
                ->{$verb}($url)
                ->assertForbidden();
        }
    }

    public function test_manager_with_ownership_can_assign_validator(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('assign', $conversation, $document, false), [
                'assigned_user_id' => $this->manager->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'assigned_user_id' => $this->manager->id,
        ]);
    }

    public function test_manager_with_ownership_can_update_document_fields(): void
    {
        [$conversation, $document] = $this->makeOwnedExpediente();

        $this->actingAs($this->manager)
            ->postJson($this->urlFor('update', $conversation, $document, false), [
                'data' => ['customer_company' => 'ACME'],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'customer_company' => 'ACME',
        ]);
    }

    /**
     * El guard de ownership (assertDocumentBelongsToConversation) es el control
     * de seguridad central del modulo: impide que un agente con permiso legitimo
     * opere sobre el expediente de OTRO cliente colando su id en la URL (IDOR).
     *
     * Se recorren las 16 rutas mutadoras — no solo una — porque el guard se
     * llama a mano en cada metodo del controller (no hay middleware que lo
     * garantice): si un metodo nuevo o un refactor se lo saltara, solo un test
     * que las cubra todas lo detectaria.
     */
    public function test_manager_cannot_act_on_document_of_another_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $foreignDocument = Document::create([
            'customer_email' => 'someone-else@example.com',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);

        foreach ($this->mutatingActions() as [$action, $verb, $needsId]) {
            $response = $this->actingAs($this->manager)->{$verb}(
                $this->urlFor($action, $conversation, $foreignDocument, $needsId),
                $this->validPayloadFor($action)
            );

            $this->assertSame(
                404,
                $response->getStatusCode(),
                "La accion '{$action}' no bloqueo un expediente de otro cliente: ".
                "se esperaba 404 del guard de ownership y respondio {$response->getStatusCode()}."
            );
        }
    }

    /**
     * Complementa al test anterior: ademas de responder 404, ninguna accion
     * debe haber dejado efecto persistido sobre el expediente ajeno.
     */
    public function test_blocked_actions_leave_the_foreign_document_untouched(): void
    {
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $foreignDocument = Document::create([
            'customer_email' => 'someone-else@example.com',
            'customer_firstname' => 'Other',
            'customer_lastname' => 'Person',
        ]);

        foreach ($this->mutatingActions() as [$action, $verb, $needsId]) {
            $this->actingAs($this->manager)->{$verb}(
                $this->urlFor($action, $conversation, $foreignDocument, $needsId),
                $this->validPayloadFor($action)
            );
        }

        // 'assign' no pudo asignarse un validador, 'update' no pudo reescribir
        // el nombre del cliente y 'notes.add' no pudo dejar rastro.
        $this->assertDatabaseMissing('documents', [
            'id' => $foreignDocument->id,
            'assigned_user_id' => $this->manager->id,
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $foreignDocument->id,
            'customer_firstname' => 'Other',
            'customer_email' => 'someone-else@example.com',
        ]);

        // QUAL-09 / item 4: 'link' writes on the CONVERSATION (not the
        // document), accumulating every linked id in metadata.document_ids
        // (ConversationDocumentLinker::linkDocument()). The two assertions
        // above only cover the `documents` table, so this closes that gap:
        // the blocked 'link' call must not have appended the foreign
        // document's id to the conversation it doesn't belong to.
        $this->assertEmpty($conversation->fresh()->metadata['document_ids'] ?? []);
    }
}
