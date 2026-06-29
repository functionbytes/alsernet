<?php

namespace Modules\Remarketing\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Suppression;

class UnsubscribeController extends Controller
{
    public function handle(Request $request, string $token): View|Response
    {
        $message = Message::query()
            ->where('open_token', $token)
            ->orWhere('click_token', $token)
            ->with('customer')
            ->first();

        if (! $message || ! $message->customer) {
            if ($request->isMethod('POST')) {
                return response('OK', 200);
            }

            return view('remarketing::public.unsubscribed', ['email' => null]);
        }

        $customer = $message->customer;

        DB::transaction(function () use ($customer, $message, $request) {
            $customer->forceFill([
                'status' => 'unsubscribed',
                'consent_marketing' => 0,
                'unsubscribed_at' => now(),
            ])->save();

            Suppression::firstOrCreate(
                ['store_id' => $message->store_id, 'email' => $customer->email],
                ['reason' => 'unsubscribe', 'source_message_id' => $message->id]
            );

            ConsentEvent::create([
                'store_id' => $message->store_id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'event_type' => 'withdrawn',
                'source' => 'api',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => now(),
            ]);
        });

        if ($request->isMethod('POST')) {
            return response('OK', 200);
        }

        return view('remarketing::public.unsubscribed', ['email' => $customer->email]);
    }
}
