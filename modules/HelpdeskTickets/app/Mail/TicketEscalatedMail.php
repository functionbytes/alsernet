<?php

namespace Modules\HelpdeskTickets\Mail;

/**
 * Aviso de escalado de un ticket (plantilla helpdesk_tickets.ticket_escalated).
 *
 * Todo el comportamiento esta en TicketMailable: asunto y cuerpo ya renderizados
 * por TicketMailRenderer, cola 'emails' y registro en el log de envios.
 */
class TicketEscalatedMail extends TicketMailable {}
