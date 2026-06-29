<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Exports\NewsletterSubscriberExport;
use Modules\Ecommerce\Models\NewsletterSubscriber;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        $subscribers = NewsletterSubscriber::query()
            ->when($request->input('search'), fn ($q, $s) => $q->where('email', 'like', "%{$s}%")
                ->orWhere('name', 'like', "%{$s}%"))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return view('ecommerce::newsletter.index', compact('subscribers'));
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $this->authorize('delete', $subscriber);

        $subscriber->delete();

        return redirect()->route('ecommerce.newsletter.index')
            ->with('success', 'Suscriptor eliminado exitosamente.');
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        return Excel::download(
            new NewsletterSubscriberExport,
            'newsletter-suscriptores-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        $request->validate([
            'action' => ['required', 'string', 'in:unsubscribe,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:ecommerce_newsletter_subscribers,id'],
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        match ($action) {
            'unsubscribe' => NewsletterSubscriber::query()->whereIn('id', $ids)->update([
                'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ]),
            'delete' => NewsletterSubscriber::query()->whereIn('id', $ids)->delete(),
        };

        return response()->json([
            'success' => true,
            'message' => 'Acción aplicada a '.count($ids).' suscriptor(es).',
        ]);
    }
}
