<?php

namespace Modules\Helpdesk\Tests\Unit;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Modules\Helpdesk\Broadcasting\ResilientBroadcaster;
use Modules\Helpdesk\Support\ChannelMetrics;
use Tests\TestCase;

/**
 * Garantía central del chat: una caída del servidor de websockets no puede
 * tumbar la petición que emite el evento.
 *
 * Los eventos de conversación implementan ShouldBroadcastNow, así que la llamada
 * HTTP a Reverb ocurre dentro de la petición. Sin este decorador, con Reverb
 * caído el visitante recibía un 500 al enviar (aunque el mensaje ya estaba
 * guardado) y los webhooks de WhatsApp/Facebook/Instagram devolvían 500, con lo
 * que el proveedor reintentaba y duplicaba mensajes.
 *
 * No toca la BD.
 */
class ResilientBroadcasterTest extends TestCase
{
    private function failingInner(): Broadcaster
    {
        return new class implements Broadcaster
        {
            public bool $called = false;

            public function auth($request)
            {
                throw new BroadcastException('auth exploto');
            }

            public function validAuthenticationResponse($request, $result)
            {
                return $result;
            }

            public function broadcast(array $channels, $event, array $payload = [])
            {
                $this->called = true;
                throw new BroadcastException('Pusher error: cURL error 7: Failed to connect');
            }

            public function channel($channel, $callback, $options = [])
            {
                return 'canal-registrado';
            }
        };
    }

    public function test_swallows_transport_failures_on_broadcast(): void
    {
        $inner = $this->failingInner();
        $broadcaster = new ResilientBroadcaster($inner);

        $broadcaster->broadcast(['private-conversation.1'], 'MessageCreated', ['id' => 1]);

        $this->assertTrue($inner->called, 'El decorador debe delegar en el broadcaster real.');
    }

    public function test_counts_transport_failures_so_they_are_not_invisible(): void
    {
        $today = now()->format('Y-m-d');
        $before = ChannelMetrics::get(
            $today,
            ResilientBroadcaster::FAILURE_CHANNEL,
            ResilientBroadcaster::FAILURE_METRIC
        );

        (new ResilientBroadcaster($this->failingInner()))
            ->broadcast(['private-conversation.1'], 'MessageCreated', ['id' => 1]);

        $after = ChannelMetrics::get(
            $today,
            ResilientBroadcaster::FAILURE_CHANNEL,
            ResilientBroadcaster::FAILURE_METRIC
        );

        // Delta, no valor absoluto: el contador vive en caché compartida.
        $this->assertSame($before + 1, $after);
    }

    public function test_auth_failures_still_surface(): void
    {
        // auth() es autorización real, no transporte: ahí un fallo debe verse.
        $this->expectException(BroadcastException::class);

        (new ResilientBroadcaster($this->failingInner()))->auth(request());
    }

    public function test_forwards_methods_outside_the_contract(): void
    {
        // Broadcast::channel() llega aquí vía BroadcastManager::__call; si no se
        // delegara, el registro de canales privados reventaría al arrancar.
        $broadcaster = new ResilientBroadcaster($this->failingInner());

        $this->assertSame('canal-registrado', $broadcaster->channel('private-x', fn () => true));
    }

    public function test_reverb_connection_resolves_to_the_decorator(): void
    {
        config()->set('broadcasting.connections.reverb', [
            'driver' => 'reverb',
            'key' => 'k',
            'secret' => 's',
            'app_id' => '1',
            'options' => ['host' => '127.0.0.1', 'port' => 8090, 'scheme' => 'http', 'useTLS' => false],
        ]);

        $connection = app(BroadcastManager::class)->connection('reverb');

        $this->assertInstanceOf(ResilientBroadcaster::class, $connection);
    }
}
