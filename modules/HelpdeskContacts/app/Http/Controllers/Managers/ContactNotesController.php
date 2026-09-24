<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerNote;

/**
 * Notas internas del contacto (Contactos 360 · bloque "Notas internas").
 * Cada nota guarda autor y fecha; solo su autor puede borrarla.
 */
class ContactNotesController extends Controller
{
    public function store(Customer $customer, Request $request): JsonResponse
    {
        $this->assertVisible($customer);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $note = $customer->notes()->create([
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nota añadida',
            'data' => self::present($note->setRelation('author', $request->user())),
        ], 201);
    }

    public function destroy(Customer $customer, CustomerNote $note, Request $request): JsonResponse
    {
        $this->assertVisible($customer);

        abort_unless((int) $note->customer_id === (int) $customer->id, 404);
        abort_unless((int) $note->user_id === (int) $request->user()->id, 403, 'Solo el autor puede borrar la nota.');

        $note->delete();

        return response()->json(['success' => true, 'message' => 'Nota eliminada']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(CustomerNote $note): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'author' => $note->author?->full_name ?: 'Agente eliminado',
            'userId' => $note->user_id,
            'createdAt' => $note->created_at?->toIso8601String(),
        ];
    }

    private function assertVisible(Customer $customer): void
    {
        abort_unless(
            Customer::query()->whereKey($customer->getKey())->forAgent(request()->user())->exists(),
            403,
            'Sin autorización sobre este contacto.'
        );
    }
}
