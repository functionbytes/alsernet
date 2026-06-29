<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Group;

class LookupController extends Controller
{
    public function agents(): JsonResponse
    {
        $agents = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['support', 'manager', 'admin', 'callcenter']))
            ->select('id', 'firstname', 'lastname', 'email')
            ->orderBy('firstname')
            ->limit(200)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->firstname.' '.$u->lastname) ?: $u->email,
                'email' => $u->email,
            ]);

        return response()->json(['data' => $agents]);
    }

    public function groups(): JsonResponse
    {
        $groups = Group::query()
            ->where('is_active', true)
            ->select('id', 'name', 'key')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $groups]);
    }

    public function tags(): JsonResponse
    {
        $tags = ConversationTag::query()
            ->where('is_active', true)
            ->select('id', 'name', 'slug', 'color')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $tags]);
    }
}
