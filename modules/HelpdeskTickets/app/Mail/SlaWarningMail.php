<?php

namespace Modules\HelpdeskTickets\Mail;

/**
 * Aviso de que un ticket se acerca al vencimiento de su SLA.
 *
 * Todo el comportamiento esta en TicketMailable: asunto y cuerpo ya renderizados
 * por TicketMailRenderer, cola 'emails' y registro en el log de envios.
 */
class SlaWarningMail extends TicketMailable {}
