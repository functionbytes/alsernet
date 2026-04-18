<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $stats = [
            'total' => Subscriber::query()->count(),
            'subscribed' => Subscriber::query()->subscribed()->count(),
            'unsubscribed' => Subscriber::query()->unsubscribed()->count(),
            'new' => Subscriber::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

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
