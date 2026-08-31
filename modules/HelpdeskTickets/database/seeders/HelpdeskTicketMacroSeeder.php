<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\TicketStatus;

/**
 * Macros de ejemplo para la ficha del ticket.
 *
 * Cubren los pasos que un agente repite a diario (acusar recibo, pedir
 * documentacion, escalar, resolver, descartar spam) y sirven de plantilla
 * viva: enseñan como se encadenan acciones y como se usan las variables
 * {{...}} de TicketVariableInterpolator.
 *
 * Los estados se resuelven por slug, nunca por id: los ids cambian de una
 * instalacion a otra y una macro con un status_id inexistente dejaria el
 * ticket con un estado roto.
 *
 * No se siembran acciones assign_group / assign_user a proposito: necesitan
 * el id de un grupo o de un agente concretos, que no existen igual en cada
 * instalacion. Se añaden a mano desde el formulario.
 */
class HelpdeskTicketMacroSeeder extends Seeder
{
    public function run(): void
    {
        $status = TicketStatus::pluck('id', 'slug');

        $id = fn (string $slug) => $status[$slug] ?? null;

        $macros = [
            [
                'name' => 'Acuse de recibo',
                'description' => 'Confirma al cliente que su solicitud ha llegado y pasa el ticket a "Abierto".',
                'actions' => array_values(array_filter([
                    [
                        'type' => 'reply',
                        'body' => "Hola {{customer_name}},\n\n".
                            'Hemos recibido tu solicitud y ya la estamos revisando. '.
                            "Tu numero de referencia es {{ticket_number}}.\n\n".
                            "Te escribimos en cuanto tengamos novedades.\n\n".
                            'Un saludo,'."\n".'{{agent_name}}',
                    ],
                    $id('open') ? ['type' => 'set_status', 'value' => $id('open')] : null,
                ])),
            ],
            [
                'name' => 'Solicitar documentacion',
                'description' => 'Pide al cliente la documentacion pendiente y deja el ticket a la espera de su respuesta.',
                'actions' => array_values(array_filter([
                    [
                        'type' => 'reply',
                        'body' => "Hola {{customer_name}},\n\n".
                            'Para continuar con tu solicitud {{ticket_number}} necesitamos que nos envies '.
                            "la documentacion pendiente respondiendo a este mismo correo.\n\n".
                            "Quedamos a la espera.\n\n".
                            'Un saludo,'."\n".'{{agent_name}}',
                    ],
                    $id('waiting-customer') ? ['type' => 'set_status', 'value' => $id('waiting-customer')] : null,
                ])),
            ],
            [
                'name' => 'Escalar a nivel 2',
                'description' => 'Sube la prioridad, marca el ticket como escalado y deja constancia interna.',
                'actions' => array_values(array_filter([
                    ['type' => 'set_priority', 'value' => 'alta'],
                    $id('escalated') ? ['type' => 'set_status', 'value' => $id('escalated')] : null,
                    ['type' => 'add_tag', 'value' => 'escalado'],
                    [
                        'type' => 'internal_note',
                        'body' => 'Escalado por {{agent_name}} el {{fecha}}. '.
                            'Asunto: {{ticket_subject}} — categoria: {{ticket_category}}.',
                    ],
                ])),
            ],
            [
                'name' => 'Resolver y cerrar',
                'description' => 'Envia el mensaje de cierre, marca el ticket como resuelto y lo cierra.',
                'actions' => array_values(array_filter([
                    [
                        'type' => 'reply',
                        'body' => "Hola {{customer_name}},\n\n".
                            'Damos por resuelta tu solicitud {{ticket_number}}. '.
                            "Si necesitas cualquier otra cosa, responde a este correo y lo reabrimos.\n\n".
                            'Un saludo,'."\n".'{{agent_name}}',
                    ],
                    $id('resolved') ? ['type' => 'set_status', 'value' => $id('resolved')] : null,
                    ['type' => 'close'],
                ])),
            ],
            [
                'name' => 'Descartar como spam',
                'description' => 'Etiqueta el ticket como spam y lo cierra sin responder al remitente.',
                'actions' => array_values(array_filter([
                    ['type' => 'add_tag', 'value' => 'spam'],
                    [
                        'type' => 'internal_note',
                        'body' => 'Descartado como spam por {{agent_name}} el {{fecha}}. No se ha respondido al remitente.',
                    ],
                    $id('closed') ? ['type' => 'set_status', 'value' => $id('closed')] : null,
                    ['type' => 'close'],
                ])),
            ],
            [
                'name' => 'Estado del ultimo pedido (ERP)',
                'description' => 'Responde con los datos del ultimo pedido del cliente en el ERP. Ejemplo de uso de las variables {{erp_*}}.',
                'actions' => array_values(array_filter([
                    [
                        'type' => 'reply',
                        'body' => "Hola {{customer_name}},\n\n".
                            "Estos son los datos que nos constan de tu ultimo pedido:\n\n".
                            "Numero de pedido: {{erp_ultimo_pedido_numero}}\n".
                            "Fecha: {{erp_ultimo_pedido_fecha}}\n".
                            "Cliente: {{erp_id_cliente}} ({{erp_nif}})\n\n".
                            "Si algun dato no coincide, dinoslo respondiendo a este correo.\n\n".
                            'Un saludo,'."\n".'{{agent_name}}',
                    ],
                    $id('waiting-customer') ? ['type' => 'set_status', 'value' => $id('waiting-customer')] : null,
                ])),
            ],
        ];

        foreach ($macros as $macro) {
            // Por nombre, para que volver a sembrar refresque el contenido en
            // vez de duplicar las macros.
            Macro::updateOrCreate(
                ['name' => $macro['name']],
                $macro + ['is_shared' => true, 'is_active' => true, 'user_id' => null],
            );
        }
    }
}
