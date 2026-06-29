<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Http\Requests\Managers\ExecuteMergeRequest;
use Modules\HelpdeskContacts\Services\ContactMergeService;
use Throwable;

class ContactsMergeController extends Controller
{
    public function __construct(
        private readonly ContactMergeService $mergeService
    ) {}

    /**
     * Search for potential duplicate contacts to merge with.
     * Excludes the current contact and returns up to 10 results.
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();
        $excludeId = $request->integer('exclude_id');

        if ($term === '') {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->search($term)
            ->when($excludeId > 0, fn ($q) => $q->where('id', '!=', $excludeId))
            ->latest('last_seen_at')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'total_conversations', 'last_seen_at']);

        return response()->json([
            'data' => $customers->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'total_conversations' => $c->total_conversations ?? 0,
                'last_seen_at' => $c->last_seen_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Preview the two contacts side-by-side before executing the merge.
     */
    public function preview(Customer $customer, Request $request): JsonResponse
    {
        $loserId = $request->integer('loser_id');

        if (! $loserId) {
            return response()->json(['success' => false, 'message' => 'El parámetro loser_id es obligatorio.'], 422);
        }

        $loser = Customer::find($loserId);

        if (! $loser) {
            return response()->json(['success' => false, 'message' => 'El contacto seleccionado no existe.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'winner' => $this->contactSummary($customer),
                'loser' => $this->contactSummary($loser),
            ],
        ]);
    }

    /**
     * Execute the merge: winner absorbs the loser.
     */
    public function execute(Customer $customer, ExecuteMergeRequest $request): JsonResponse
    {
        $loserId = $request->integer('loser_id');

        if ($loserId === $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes fusionar un contacto consigo mismo.',
            ], 422);
        }

        $loser = Customer::find($loserId);

        if (! $loser) {
            return response()->json([
                'success' => false,
                'message' => 'El contacto duplicado no existe.',
            ], 404);
        }

        try {
            $this->mergeService->merge($customer, $loser);
        } catch (Throwable $e) {
            Log::error('Error al fusionar contactos', [
                'winner_id' => $customer->id,
                'loser_id' => $loser->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron fusionar los contactos. Inténtalo de nuevo más tarde.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contactos fusionados correctamente.',
        ]);
    }

    /**
     * Build a summary array for the preview response.
     *
     * @return array<string, mixed>
     */
    private function contactSummary(Customer $customer): array
    {
        $customer->loadMissing('externalIds');

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'total_conversations' => $customer->total_conversations ?? 0,
            'integrations' => [
                'erp_customer_id' => $customer->erp_customer_id,
                'whatsapp_phone' => $customer->whatsapp_phone,
                'facebook_psid' => $customer->facebook_psid,
                'instagram_id' => $customer->instagram_id,
                'external_ids' => $customer->externalIds->map(fn ($link) => [
                    'platform' => $link->platform,
                    'external_id' => $link->external_id,
                ]),
            ],
        ];
    }
}
