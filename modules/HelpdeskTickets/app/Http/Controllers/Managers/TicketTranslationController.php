<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\TranslateTicketTextRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

/**
 * Traduce texto de un ticket (mensaje entrante del cliente o borrador de
 * respuesta del agente) reutilizando el traductor cacheado del ecosistema.
 * Desacoplado: si HelpdeskTranslate está deshabilitado devuelve el texto tal
 * cual con `translated=false`, sin romper.
 */
class TicketTranslationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.view');
    }

    public function translate(TranslateTicketTextRequest $request, Ticket $ticket): JsonResponse
    {
        $data = $request->validated();

        if (! helpdesk_translate_enabled() || ! class_exists(CachedTranslator::class)) {
            return response()->json([
                'success' => true,
                'translated' => false,
                'text' => $data['text'],
            ]);
        }

        $result = app(CachedTranslator::class)->translate(
            $data['text'],
            $data['target_lang'],
            $data['source_lang'] ?? null,
        );

        return response()->json([
            'success' => true,
            'translated' => $result !== null,
            'text' => $result ?? $data['text'],
        ]);
    }
}
