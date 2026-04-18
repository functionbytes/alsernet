<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Captcha\Facades\Captcha;
use Modules\Core\Models\Setting;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Services\NewsletterEmailService;

class PublicUnsubscribeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $rules = ['email' => ['required', 'email']];

        if (Setting::get('newsletter.recaptcha_enabled') === '1' && Captcha::isEnabled()) {
            $rules['g-recaptcha-response'] = ['required'];
        }

        $request->validate($rules);

        if (Setting::get('newsletter.recaptcha_enabled') === '1' && Captcha::isEnabled()) {
            if (! Captcha::verify($request->input('g-recaptcha-response'), $request->ip())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe completar la verificación de captcha.',
                ], 422);
            }
        }

        $email = $request->input('email');

        $subscriber = Subscriber::query()
            ->where('email', $email)
            ->subscribed()
            ->first();

        if (! $subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'No encontramos una suscripción activa con ese correo electrónico.',
            ], 422);
        }

        $subscriber->unsubscribe();

        NewsletterEmailService::sendUnsubscriptionEmail($subscriber->email, $subscriber->name);

        return response()->json([
            'success' => true,
            'message' => 'Tu suscripción ha sido cancelada correctamente.',
        ]);
    }
}
