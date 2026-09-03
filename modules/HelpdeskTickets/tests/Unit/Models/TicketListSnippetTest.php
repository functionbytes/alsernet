<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * El extracto que sale bajo el asunto en cada fila del listado.
 *
 * Los tickets de formulario guardan en `description` un volcado de todos los
 * campos ("Firstname: …\nLastname: …\nPhone: …"), así que la fila acababa
 * mostrando datos de contacto cortados a mitad de etiqueta en vez de qué
 * pide el cliente.
 */
class TicketListSnippetTest extends TestCase
{
    private function snippet(Ticket $ticket): ?string
    {
        // listSnippet() es privado a propósito (solo lo consume toListRow()).
        $method = new \ReflectionMethod(Ticket::class, 'listSnippet');
        $method->setAccessible(true);

        return $method->invoke($ticket);
    }

    public function test_prefiere_el_texto_libre_del_formulario_al_volcado_de_campos(): void
    {
        $ticket = new Ticket;
        $ticket->description = "Firstname: Test\nLastname: Golf\nPhone: 600111116";
        $ticket->custom_fields = [
            'firstname' => 'Test',
            'message' => 'Mi pedido sigue detenido y necesito saber qué documentos faltan.',
        ];

        $this->assertSame('Mi pedido sigue detenido y necesito saber qué documentos faltan.', $this->snippet($ticket));
    }

    public function test_ignora_los_campos_de_lista_demasiado_cortos_para_ser_un_mensaje(): void
    {
        // "Ninguna" es el valor de un desplegable, no lo que escribió el
        // cliente: cae al volcado en vez de mostrar una palabra suelta.
        $ticket = new Ticket;
        $ticket->description = "Firstname: Test\nLastname: Golf";
        $ticket->custom_fields = ['observations' => 'Ninguna'];

        $this->assertSame('Firstname: Test · Lastname: Golf', $this->snippet($ticket));
    }

    public function test_compacta_los_saltos_de_linea_del_volcado(): void
    {
        // Sin esto la fila cortaba en mitad de una etiqueta ("Phone secon…").
        $ticket = new Ticket;
        $ticket->description = "Firstname: Test\n\nLastname: Golf\n  Phone: 600";

        $this->assertSame('Firstname: Test · Lastname: Golf · Phone: 600', $this->snippet($ticket));
    }

    public function test_sin_descripcion_ni_campos_no_devuelve_extracto(): void
    {
        $this->assertNull($this->snippet(new Ticket));
    }

    public function test_recorta_los_extractos_largos(): void
    {
        $ticket = new Ticket;
        $ticket->description = str_repeat('a', 200);

        $this->assertLessThanOrEqual(93, mb_strlen((string) $this->snippet($ticket)));
    }
}
