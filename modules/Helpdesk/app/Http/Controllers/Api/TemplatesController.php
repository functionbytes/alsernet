<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\TicketTemplate;

class TemplatesController extends Controller
{
    /**
     * List active ticket templates for portal/widget use.
     *
     * GET /api/helpdesk/templates
     */
    public function index(): JsonResponse
    {
        $templates = TicketTemplate::active()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'subject', 'body', 'category_id']);

        $data = $templates->map(fn ($template) => [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'subject' => $template->subject,
            'body' => $template->body,
            'category' => $template->category?->name,
        ]);

        return response()->json($data);
    }
}
