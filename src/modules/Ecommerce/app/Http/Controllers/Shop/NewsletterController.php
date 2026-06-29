<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StoreNewsletterSubscriberRequest;
use Modules\Ecommerce\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    public function subscribe(StoreNewsletterSubscriberRequest $request): JsonResponse|RedirectResponse
    {
        NewsletterSubscriber::query()->create([
            'email' => $request->validated('email'),
            'name' => $request->validated('name'),
            'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'source' => 'shop_footer',
            'subscribed_at' => now(),
            'ip_address' => $request->ip(),
            'unsubscribe_token' => NewsletterSubscriber::generateUnsubscribeToken(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '¡Te has suscrito al newsletter exitosamente!',
            ]);
        }

        return redirect()->back()->with('success', '¡Te has suscrito al newsletter exitosamente!');
    }

    public function unsubscribe(Request $request, string $token): View
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        if ($subscriber->status !== NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            $subscriber->update([
                'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ]);
        }

        return view('ecommerce::shop.newsletter.unsubscribed');
    }
}
