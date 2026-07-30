<?php

namespace Modules\Helpdesk\Broadcasting;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Modules\Helpdesk\Support\ChannelMetrics;

/**
 * Decorador que hace best-effort la emisión a websockets.
 *
 * Los eventos de chat implementan ShouldBroadcastNow, así que la llamada HTTP al
 * servidor de websockets (Reverb/Pusher) ocurre DENTRO de la petición. Si ese
 * servidor está caído, la excepción sube y tumba la petición completa: el
 * mensaje ya se persistió, pero el visitante ve un error al enviar y los
 * webhooks de WhatsApp/Facebook/Instagram devuelven 500, con lo que el proveedor
 * reintenta y duplica mensajes.
 *
 * El tiempo real es una mejora sobre el canal de entrega, no el canal en sí: el
 * widget y el panel también leen por HTTP. Por eso un fallo del transporte se
 * registra (report) pero nunca interrumpe el flujo de negocio.
 *
 * Sólo se envuelve broadcast(); auth() debe seguir fallando de forma visible,
 * porque ahí un error sí es un problema de autorización real.
 */
class ResilientBroadcaster implements Broadcaster
{
    /** Pseudo-canal bajo el que se contabilizan los fallos de transporte. */
    public const FAILURE_CHANNEL = 'realtime';

    public const FAILURE_METRIC = 'broadcast_failed';

    public function __construct(private Broadcaster $inner) {}

    public function auth($request)
    {
        return $this->inner->auth($request);
    }

    public function validAuthenticationResponse($request, $result)
    {
        return $this->inner->validAuthenticationResponse($request, $result);
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        try {
            $this->inner->broadcast($channels, $event, $payload);
        } catch (\Throwable $e) {
            report($e);

            // Tragarse el fallo sin dejar rastro convertiría una caída del
            // websocket en un chat "lento" sin causa aparente. El contador lo
            // hace visible desde `helpdesk:channel-metrics`. Nunca puede
            // propagar: ese es justamente el punto de este decorador.
            try {
                ChannelMetrics::increment(self::FAILURE_METRIC, self::FAILURE_CHANNEL);
            } catch (\Throwable) {
                // El backend de caché también está caído; ya se reportó lo importante.
            }
        }
    }

    /**
     * El resto de la superficie del broadcaster no está en el contrato pero sí se
     * usa: Broadcast::channel() y demás llegan aquí vía BroadcastManager::__call.
     * Se delegan tal cual — sólo broadcast() cambia de comportamiento.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->inner->{$method}(...$parameters);
    }
}
