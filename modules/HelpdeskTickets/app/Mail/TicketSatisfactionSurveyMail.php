<?php

namespace Modules\HelpdeskTickets\Mail;

/**
 * Encuesta de satisfaccion (CSAT) al cerrar un ticket.
 *
 * Todo el comportamiento esta en TicketMailable: asunto y cuerpo ya renderizados
 * por TicketMailRenderer, cola 'emails' y registro en el log de envios.
 */
class TicketSatisfactionSurveyMail extends TicketMailable {}
