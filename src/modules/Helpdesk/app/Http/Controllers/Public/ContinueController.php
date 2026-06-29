<?php

namespace Modules\Helpdesk\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\ConversationContinuation;

/**
 * Public endpoint that resolves a continuation token sent by email,
 * marks it as consumed, and redirects the visitor back to the widget
 * with cookies that allow the SDK to reopen the original conversation.
 */
class ContinueController extends Controller
{
    public function show(Request $request, string $token): RedirectResponse
    {
        $continuation = ConversationContinuation::query()
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->with('conversation.inbox.channel')
            ->first();

        if (! $continuation) {
            return redirect('/')->with('error', 'El enlace ha expirado o ya fue utilizado.');
        }

        $continuation->update([
            'used_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        $conversation = $continuation->conversation;

        $redirectUrl = $this->buildRedirectUrl($conversation);

        return redirect($redirectUrl)
            ->cookie('hd_continue_conversation', (string) $conversation->id, 60 * 24)
            ->cookie('hd_continue_email', $continuation->email, 60 * 24);
    }

    private function buildRedirectUrl($conversation): string
    {
        $channel = $conversation?->inbox?->channel ?? null;

        if ($channel && ! empty($channel->website_url)) {
            return $channel->website_url;
        }

        return url('/');
    }
}
