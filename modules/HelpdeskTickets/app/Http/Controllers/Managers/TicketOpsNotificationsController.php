<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateTicketNotificationPreferencesRequest;
use Modules\Notification\Models\NotificationPreference;

/**
 * Preferencias de aviso del agente para los eventos de ticket.
 *
 * El sistema de preferencias por usuario/canal/evento ya existía
 * (Modules\Notification\Models\NotificationPreference, tabla
 * notification_settings) y las notificaciones de este módulo YA lo consultan
 * en su via(). Lo que faltaba era la pantalla: se leía la preferencia al
 * notificar, pero el agente no tenía dónde cambiarla desde tickets.
 *
 * Este controlador es sólo la cara HTTP de ese sistema para el modal "Avisos"
 * del riel de operación. No toca el módulo Notification: lee y escribe con los
 * métodos públicos del modelo (isEnabled/toggle).
 *
 * Por qué el catálogo se define aquí y no se lee de NotificationTypeRegistry:
 * ese registro existe pero NADIE llama nunca a register() fuera de sus tests,
 * así que all() devuelve un array vacío. Poblarlo obligaría a tocar el módulo
 * Notification (fuera de encargo) y, sobre todo, no sabría qué canales consulta
 * de verdad cada notificación. El catálogo de abajo está derivado leyendo una a
 * una las via() de modules/HelpdeskTickets/app/Notifications/*.php.
 */
class TicketOpsNotificationsController extends Controller
{
    /**
     * Canales que el usuario puede gobernar, con la etiqueta que ve.
     *
     * Sólo dos. La tabla admite además 'email', pero ninguna notificación de
     * tickets consulta la preferencia de correo (TicketIncidentDetected manda
     * por 'mail' sin preguntar), así que una casilla "Email" sería un mando
     * desconectado.
     */
    private const CHANNELS = [
        'in_app' => [
            'key' => 'in_app',
            'label' => 'Panel',
            'description' => 'Campanita de la cabecera',
        ],
        'push' => [
            'key' => 'push',
            'label' => 'Tiempo real',
            'description' => 'Aviso emergente en el navegador',
        ],
    ];

    /**
     * Eventos de ticket que consultan la preferencia del usuario.
     *
     * `channels` lista SÓLO los canales cuya casilla hace algo de verdad: son
     * los que aparecen en un isEnabled() de la via() correspondiente. Donde el
     * canal está cableado a fuego (p. ej. TicketCreated hace
     * $channels = ['database'] antes de preguntar, así que el aviso del panel
     * llega igual) no se ofrece la casilla, para no pintar un interruptor que
     * el usuario mueve y no cambia nada.
     *
     * @var array<int, array{key: string, label: string, description: string, channels: array<int, string>, source: string}>
     */
    private const EVENTS = [
        [
            'key' => 'ticket.assigned',
            'label' => 'Me asignan un ticket',
            'description' => 'Alguien te pone como responsable de un ticket.',
            'channels' => ['in_app', 'push'],
            'source' => 'TicketAssigned',
        ],
        [
            'key' => 'ticket.status_changed',
            'label' => 'Cambia el estado de un ticket mío',
            'description' => 'Pasa a en curso, resuelto, cerrado…',
            'channels' => ['in_app', 'push'],
            'source' => 'TicketStatusChanged',
        ],
        [
            'key' => 'ticket.sla.warning',
            'label' => 'Un SLA está a punto de vencer',
            'description' => 'Queda poco para la primera respuesta o la resolución.',
            'channels' => ['in_app', 'push'],
            'source' => 'TicketSlaNearBreach',
        ],
        [
            'key' => 'ticket.sla.breached',
            'label' => 'Se ha incumplido un SLA',
            'description' => 'El ticket ya ha pasado del plazo comprometido.',
            'channels' => ['in_app', 'push'],
            'source' => 'TicketSlaBreached',
        ],
        [
            'key' => 'ticket.mention',
            'label' => 'Me mencionan en un ticket',
            'description' => 'Alguien te nombra en una nota interna.',
            // El aviso del panel es incondicional en TicketMentionNotification:
            // una mención sin rastro en la campanita se perdería.
            'channels' => ['push'],
            'source' => 'TicketMentionNotification',
        ],
        [
            'key' => 'ticket.created',
            'label' => 'Entra un ticket nuevo',
            'description' => 'Se crea un ticket en el que estás implicado.',
            // Igual que la mención: TicketCreated fija 'database' antes de
            // preguntar, así que sólo el tiempo real es gobernable.
            'channels' => ['push'],
            'source' => 'TicketCreated',
        ],
        [
            'key' => 'ticket.automation',
            'label' => 'Una automatización toca un ticket mío',
            'description' => 'Una regla cambia el ticket sin intervención tuya.',
            'channels' => ['push'],
            'source' => 'AutomationTicketNotification',
        ],
    ];

    /**
     * Avisos que llegan siempre y no se pueden desactivar.
     *
     * Se enseñan en el modal como texto, sin interruptor, porque sus via() no
     * consultan NotificationPreference. Es preferible decir "esto llega
     * siempre" a que el agente busque en vano el ajuste (el mockup pedía
     * "el cliente responda a un correo que envié", que es justo uno de éstos).
     *
     * @var array<int, string>
     */
    private const ALWAYS_ON = [
        'Responde el cliente o alguien deja una nota en un ticket que sigues',
        'Vence un recordatorio de seguimiento tuyo',
        'Llega un mensaje a una conversación lateral',
        'Se detecta una incidencia con varios tickets sobre lo mismo',
    ];

    /**
     * Catálogo de eventos + el valor actual del usuario para cada casilla.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        // Una sola consulta para todas las filas del usuario: isEnabled() haría
        // un SELECT por casilla (hasta 12) y aquí se pintan todas de golpe.
        $stored = NotificationPreference::query()
            ->where('user_id', $userId)
            ->whereIn('notification_type', array_column(self::EVENTS, 'key'))
            ->get()
            ->mapWithKeys(fn ($row) => [$row->notification_type.'|'.$row->channel => (bool) $row->enabled]);

        $events = [];

        foreach (self::EVENTS as $event) {
            $channels = [];

            foreach ($event['channels'] as $channel) {
                $channels[] = [
                    'key' => $channel,
                    'label' => self::CHANNELS[$channel]['label'],
                    // Sin fila guardada el sistema avisa: isEnabled() devuelve
                    // true por defecto. La casilla tiene que reflejar eso.
                    'enabled' => $stored[$event['key'].'|'.$channel] ?? true,
                ];
            }

            $events[] = [
                'key' => $event['key'],
                'label' => $event['label'],
                'description' => $event['description'],
                'channels' => $channels,
            ];
        }

        return response()->json([
            'channels' => array_values(self::CHANNELS),
            'events' => $events,
            'always_on' => self::ALWAYS_ON,
        ]);
    }

    /**
     * Guarda las casillas que el agente ha movido.
     */
    public function update(UpdateTicketNotificationPreferencesRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $saved = 0;

        foreach ($request->validated('preferences') as $preference) {
            // Descarta pares evento/canal que no existen en el catálogo: sin
            // esto se podrían sembrar filas para canales que ninguna via()
            // consulta, invisibles luego en el modal y por tanto imposibles de
            // revertir desde aquí.
            if (! $this->isGovernable($preference['notification_type'], $preference['channel'])) {
                continue;
            }

            NotificationPreference::toggle(
                $userId,
                $preference['channel'],
                $preference['notification_type'],
                (bool) $preference['enabled'],
            );

            $saved++;
        }

        return response()->json([
            'message' => 'Preferencias de aviso guardadas',
            'saved' => $saved,
        ]);
    }

    /**
     * ¿Ese par evento/canal tiene una casilla real en el catálogo?
     */
    private function isGovernable(string $type, string $channel): bool
    {
        foreach (self::EVENTS as $event) {
            if ($event['key'] === $type) {
                return in_array($channel, $event['channels'], true);
            }
        }

        return false;
    }
}
