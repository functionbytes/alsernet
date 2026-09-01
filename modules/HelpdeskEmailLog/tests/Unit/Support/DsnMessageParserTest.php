<?php

namespace Modules\HelpdeskEmailLog\Tests\Unit\Support;

use Modules\HelpdeskEmailLog\Support\DsnMessageParser;
use PHPUnit\Framework\TestCase;

/**
 * looksLikeBounceOrComplaint() es la pieza más sensible del detector: la usa
 * FetchTicketEmailsJob para decidir si un correo entrante a un canal de
 * Tickets se desvía de la creación de ticket. Un falso positivo aquí
 * significaría descartar en silencio un correo real de un cliente — por eso
 * se prueba explícitamente contra ejemplos realistas de soporte, no solo
 * contra ejemplos sintéticos de DSN.
 */
class DsnMessageParserTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function realCustomerEmails(): array
    {
        return [
            'consulta de pedido' => ['Consulta sobre mi pedido', 'Hola, quisiera saber el estado de mi pedido 12345. Gracias.'],
            'respuesta a solicitud de documentación' => ['Re: Solicitud de documentación · pedido 829575', 'Buenos días, adjunto el documento solicitado.'],
            'urgencia real del cliente' => ['URGENTE: necesito ayuda', 'Por favor contáctenme lo antes posible, es urgente.'],
            'menciona "entrega" y "status" sin ser un DSN' => ['Problema con la entrega', 'El paquete no llegó a tiempo, necesito una actualización de status.'],
        ];
    }

    /**
     * @dataProvider realCustomerEmails
     */
    public function test_does_not_flag_real_customer_emails_as_bounce_or_complaint(string $subject, string $body): void
    {
        $this->assertFalse(DsnMessageParser::looksLikeBounceOrComplaint($subject, $body));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function realDsnSamples(): array
    {
        return [
            'asunto típico de Postfix/Exchange' => ['Mail Delivery Failed: Returning message to sender', 'Diagnostic-Code: smtp; 550 5.1.1'],
            'asunto típico Undeliverable' => ['Undeliverable: Solicitud de documentación', 'Your message could not be delivered'],
            'cabecera multipart/report estándar' => ['Algo genérico', "Content-Type: multipart/report;\r\n report-type=delivery-status;\r\n boundary=\"x\""],
            'feedback loop de queja de spam' => ['Feedback loop report', 'This message contains a complaint from a spam feedback loop.'],
        ];
    }

    /**
     * @dataProvider realDsnSamples
     */
    public function test_flags_realistic_dsn_and_complaint_samples(string $subject, string $body): void
    {
        $this->assertTrue(DsnMessageParser::looksLikeBounceOrComplaint($subject, $body));
    }

    public function test_find_original_message_id_ignores_own_dsn_id(): void
    {
        $body = "Message-ID: <own-dsn-id@mailer.test>\n\nMessage-ID: <original-id@webadmin.test>\n";

        $this->assertSame('original-id@webadmin.test', DsnMessageParser::findOriginalMessageId($body, '<own-dsn-id@mailer.test>'));
    }
}
