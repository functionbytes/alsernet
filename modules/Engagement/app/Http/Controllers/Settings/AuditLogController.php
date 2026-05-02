<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Models\AuditLog;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.manage')->only('page', 'index');
    }

    public function page(): View
    {
        return view('engagement::settings.engagement.audit-logs');
    }

    public function index(Request $request): JsonResponse
    {
        $rows = AuditLog::query()
            ->when($request->input('entity_type'), fn ($q, $t) => $q->where('entity_type', $t))
            ->when($request->input('action'), fn ($q, $a) => $q->where('action', $a))
            ->when($request->input('user_id'), fn ($q, $id) => $q->where('user_id', (int) $id))
            ->latest()
            ->limit(300)
            ->get();

        $userIds = $rows->pluck('user_id')->filter()->unique();
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'user' => $r->user_id ? [
                    'id' => $r->user_id,
                    'name' => $users->get($r->user_id)?->name ?? '—',
                    'email' => $users->get($r->user_id)?->email ?? '',
                ] : null,
                'action' => $r->action,
                'entity_type' => $r->entity_type,
                'entity_id' => $r->entity_id,
                'changes' => $r->changes,
                'ip_address' => $r->ip_address,
                'created_at' => $r->created_at->toIso8601String(),
            ])->values(),
        ]);
    }
}
