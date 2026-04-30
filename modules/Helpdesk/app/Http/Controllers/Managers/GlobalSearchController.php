<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->string('q'));

        if (strlen($q) < 2) {
            return response()->json(['customers' => [], 'conversations' => [], 'tags' => []]);
        }

        $customers = Customer::query()
            ->search($q)
            ->limit(5)
            ->get(['id', 'name', 'email', 'phone'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'type' => 'customer',
                'url' => route('manager.helpdesk.customers.show', $c),
            ]);

        $conversations = Conversation::query()
            ->with('customer:id,name')
            ->where(function ($q2) use ($q) {
                $q2->where('subject', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($qc) => $qc->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('items', fn ($qi) => $qi->where('body', 'like', "%{$q}%"));
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'subject', 'channel', 'customer_id'])
            ->take(5)
            ->map(fn ($c) => [
                'id' => $c->id,
                'subject' => $c->subject,
                'customer_name' => $c->customer?->name,
                'channel' => $c->channel,
                'type' => 'conversation',
                'url' => route('manager.helpdesk.conversations.index', ['selected' => $c->id]),
                'snippet' => mb_strimwidth($c->subject ?? '', 0, 80, '…'),
            ]);

        $tags = ConversationTag::query()
            ->active()
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'name', 'color'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
                'type' => 'tag',
                'url' => route('manager.helpdesk.conversations.index', ['tag' => $t->id]),
            ]);

        return response()->json([
            'customers' => $customers,
            'conversations' => $conversations,
            'tags' => $tags,
        ]);
    }
}
