<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Sugiere artículos del centro de ayuda relevantes al asunto/descripción de un
 * ticket, para que el agente resuelva más rápido (deflexión). Desacoplado: si
 * HelpdeskHelpcenter está deshabilitado devuelve una lista vacía sin romper.
 */
class SuggestedArticlesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.view');
    }

    public function index(Ticket $ticket): JsonResponse
    {
        $articles = $this->suggest($ticket);

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }

    /**
     * @return array<int, array{id:string,title:string,url:string,excerpt:string}>
     */
    private function suggest(Ticket $ticket): array
    {
        if (! helpdesk_helpcenter_enabled() || ! class_exists(HelpcenterWidgetService::class)) {
            return [];
        }

        $query = trim($ticket->subject.' '.strip_tags((string) $ticket->description));

        if (Str::length($query) < 3) {
            return [];
        }

        try {
            $results = app(HelpcenterWidgetService::class)->searchArticles(Str::limit($query, 200, ''));
        } catch (\Throwable) {
            return [];
        }

        return array_slice(array_map(fn (array $a): array => [
            'id' => $a['id'],
            'title' => $a['title'],
            'url' => $a['url'],
            'excerpt' => $a['excerpt'] ?? '',
        ], $results), 0, 5);
    }
}
