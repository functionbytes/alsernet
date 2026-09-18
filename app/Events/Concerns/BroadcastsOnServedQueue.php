<?php

namespace App\Events\Concerns;

/**
 * Manda el broadcast a una cola que alguien esté escuchando y que no comparta
 * sitio con trabajo lento.
 *
 * POR QUÉ HACE FALTA
 * ------------------
 * Un evento `ShouldBroadcast` no se emite en el acto: Laravel encola un
 * `BroadcastEvent` y lo emite el worker. Sin `broadcastQueue()` ese trabajo va
 * a la cola `default`, que en webadmin estuvo mucho tiempo sin ningún worker
 * (el servicio `worker` del compose estaba parado). Resultado: todo el tiempo
 * real se encolaba y no salía nunca —presencia, «está escribiendo», avisos de
 * SLA, la llegada de un correo del cliente con el ticket abierto— sin un solo
 * error en el log, y `default` acumuló 145.107 trabajos.
 *
 * POR QUÉ `helpdesk-broadcasts` Y NO `broadcasts`
 * ----------------------------------------------
 * Por el nombre parecería que `broadcasts` es la de los websockets, pero ahí
 * es donde Helpdesk encola sus ENVÍOS MASIVOS a clientes
 * (`SendBroadcastJob`/`SendBroadcastChunkJob`): «broadcast» ahí significa
 * difusión de mensajes, no WebSocket. Poner los avisos en vivo detrás de una
 * tanda de diez mil correos los dejaría llegando minutos tarde.
 *
 * `helpdesk-broadcasts` la sirve el mismo worker (`worker-helpdesk-realtime`),
 * va ANTES en el orden de prioridad y no la usa nada más. El prefijo del nombre
 * es histórico: vale para cualquier módulo, no solo para el helpdesk.
 *
 * Un evento con necesidades distintas puede sobrescribir `broadcastQueue()`.
 */
trait BroadcastsOnServedQueue
{
    public function broadcastQueue(): string
    {
        return 'helpdesk-broadcasts';
    }
}
