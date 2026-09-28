<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Http\Requests\SubscribePushRequest;

class PushSubscriptionController extends Controller
{
    /**
     * Guarda (o actualiza) la suscripción Web Push del navegador actual del
     * usuario autenticado. Un mismo endpoint solo puede pertenecer a un
     * usuario a la vez: si el navegador ya estaba suscrito con otra cuenta
     * (equipo compartido), esta suscripción se reasigna a quien la registra
     * ahora — evita mandarle push de otro agente a quien usa el equipo después.
     */
    public function store(SubscribePushRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $endpointHash = hash('sha256', $validated['endpoint']);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
                'endpoint_hash' => $endpointHash,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => 'aes128gcm',
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Borra la suscripción de este navegador (el usuario desactivó las
     * notificaciones, o el navegador reporta la suscripción como caducada).
     */
    public function destroy(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint', '');

        if ($endpoint === '') {
            return response()->json(['success' => true]);
        }

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $endpoint))
            ->delete();

        return response()->json(['success' => true]);
    }
}
