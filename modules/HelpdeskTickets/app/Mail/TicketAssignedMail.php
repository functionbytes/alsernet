<?php

namespace Modules\HelpdeskTickets\Mail;

/**
 * Aviso al agente de que se le ha asignado un ticket.
 *
 * Todo el comportamiento esta en TicketMailable: asunto y cuerpo ya renderizados
 * por TicketMailRenderer, cola 'emails' y registro en el log de envios.
 */
class TicketAssignedMail extends TicketMailable {}
