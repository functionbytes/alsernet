<?php

declare(strict_types=1);

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\WebChannel;
use Modules\Engagement\Traits\SoftDeletesActions;
use Modules\Helpdesk\Models\Inbox;

class WebChannelController extends Controller
{
    use RecordsAudit;
    use SoftDeletesActions;

    public function __construct()
    {
        $this->middleware('can:engagement.web_channels.view')->only('page', 'index');
        $this->middleware('can:engagement.web_channels.create')->only('store');
        $this->middleware('can:engagement.web_channels.update')->only('update');
        $this->middleware('can:engagement.web_channels.delete')->only('destroy');
        $this->middleware('can:engagement.web_channels.view')->only('trashed');
        $this->middleware('can:engagement.web_channels.update')->only('restore');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.web-channels', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $rows = WebChannel::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->where('inbox_id', (int) $id))
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'website_token' => ['required', 'string', 'max:64', 'unique:helpdesk.engagement_web_channels,website_token'],
            'domain' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $channel = WebChannel::create($validated);

        return response()->json(['success' => true, 'data' => $channel], 201);
    }

    public function update(Request $request, WebChannel $webChannel): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $webChannel->update($validated);

        return response()->json(['success' => true, 'data' => $webChannel]);
    }

    public function destroy(WebChannel $webChannel): JsonResponse
    {
        $webChannel->delete();

        return response()->json(['success' => true]);
    }

    protected function getModelClass(): string
    {
        return WebChannel::class;
    }
}
