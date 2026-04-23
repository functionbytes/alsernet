<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Services\SubscriberService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    public function __construct(
        private readonly SubscriberService $service
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Subscriber::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('status', (int) $status))
            ->latest();

        $subscribers = $query->paginate(25)->withQueryString();

        $stats = Cache::remember('newsletter:subscriber_stats', 300, function () {
            $row = Subscriber::query()
                ->selectRaw("COUNT(*) as total, SUM(status='subscribed') as subscribed, SUM(status='unsubscribed') as unsubscribed, SUM(MONTH(created_at)=? AND YEAR(created_at)=?) as new", [now()->month, now()->year])
                ->first();

            return [
                'total' => (int) $row->total,
                'subscribed' => (int) $row->subscribed,
                'unsubscribed' => (int) $row->unsubscribed,
                'new' => (int) $row->new,
            ];
        });

        return view('newsletter::subscribers.index', compact('subscribers', 'stats', 'search', 'status'));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:delete,unsubscribe,resubscribe'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = match ($data['action']) {
            'delete' => $this->service->bulkDelete($data['ids']),
            'unsubscribe' => $this->service->bulkUnsubscribe($data['ids']),
            'resubscribe' => $this->service->bulkResubscribe($data['ids']),
        };

        $messages = [
            'delete' => 'Suscriptores eliminados correctamente.',
            'unsubscribe' => 'Suscriptores desuscritos correctamente.',
            'resubscribe' => 'Suscriptores reactivados correctamente.',
        ];

        return response()->json(['message' => $messages[$data['action']], 'count' => $count]);
    }

    public function export(): StreamedResponse
    {
        return $this->service->exportCsv();
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return redirect()->back()->with('success', 'Suscriptor eliminado correctamente.');
    }
}
