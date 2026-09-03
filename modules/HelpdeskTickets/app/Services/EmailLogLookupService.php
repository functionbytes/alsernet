<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Resuelve el EmailLog (módulo HelpdeskEmailActivity) que corresponde a un
 * TicketMail concreto, cruzando por message_id — la única correlación
 * posible entre ambas fuentes de verdad (TicketMail no trackea aperturas ni
 * clics, sería duplicar lo que EmailLog ya hace).
 *
 * TicketMail.message_id se guarda con los ángulos <...> (formato de cabecera
 * RFC 5322); EmailLog.message_id se guarda SIN ellos (ver LogEmailQueued::
 * ensureMessageId()) — sin el trim() de abajo el cruce nunca encuentra nada
 * (bug real que se coló hasta probarlo con un envío de verdad, ver
 * TicketMailsControllerTest::test_data_includes_trace_from_matching_email_log).
 *
 * Antes vivía duplicado casi al carácter en TicketDetailDataController y
 * TicketMailDetailDataController — cada uno con su propia copia del mismo
 * with(['opens','clicks'])->where('message_id', trim(...))->first(), que ya
 * había divergido una vez (uno cargaba solo 'opens', el otro también
 * 'clicks', hasta que se sincronizaron a mano). Un tercer sitio que
 * necesitara lo mismo habría sido la tercera copia a mantener sincronizada.
 *
 * TicketMailsController::openTrackingStats() NO usa este servicio a
 * propósito: agrega por SQL en bloque (JOIN sobre N TicketMail a la vez para
 * un KPI), un problema distinto de "resolver el EmailLog de UN TicketMail
 * concreto" — forzar ese caso por aquí sería una fila por vez donde hoy es
 * una sola query.
 */
class EmailLogLookupService
{
    public function forMessageId(?string $messageId): ?EmailLog
    {
        if (! $messageId) {
            return null;
        }

        return EmailLog::with(['opens', 'clicks'])
            ->where('message_id', trim($messageId, '<>'))
            ->first();
    }
}
