<?php

namespace Modules\Remarketing\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Remarketing\Models\Message;

class TrackingController extends Controller
{
    public function open(Request $request, int $messageId): Response
    {
        $message = Message::find($messageId);
        $token = $request->query('t');

        if ($message && $token && hash_equals($message->open_token, $token) && ! $message->opened_at) {
            $message->forceFill([
                'opened_at' => now(),
                'status' => in_array($message->status, ['sent', 'delivered'], true) ? 'opened' : $message->status,
            ])->save();
        }

        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function click(Request $request, int $messageId, string $linkHash): RedirectResponse
    {
        $message = Message::find($messageId);

        $encoded = $request->query('u');
        $destination = $encoded ? base64_decode($encoded, true) : null;

        if ($message && $destination) {
            $expectedHash = substr(hash_hmac('sha256', $destination, $message->click_token), 0, 16);

            if (! hash_equals($expectedHash, $linkHash)) {
                return redirect('/');
            }

            $message->forceFill([
                'clicked_at' => $message->clicked_at ?? now(),
                'opened_at' => $message->opened_at ?? now(),
                'status' => 'clicked',
            ])->save();
        }

        if (! $destination || ! filter_var($destination, FILTER_VALIDATE_URL)) {
            return redirect('/');
        }

        return redirect()->away($destination);
    }
}
