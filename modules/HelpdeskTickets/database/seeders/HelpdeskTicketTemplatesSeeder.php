<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketTemplate;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

/**
 * Plantillas de ticket de ejemplo (/panel/helpdesk/ticket-templates).
 *
 * Ojo con no confundirlas con las respuestas rápidas de Ajustes → Tickets:
 * una plantilla es el ESQUELETO DE UN TICKET NUEVO (asunto + descripción +
 * categoría + prioridad) y la aplica el selector "Usar plantilla" del alta;
 * una respuesta rápida es un TROZO DE TEXTO que se inserta en el cuadro de
 * respuesta de un ticket que ya existe, y se invoca por su short_code.
 *
 * Por eso aquí sí hay asunto, categoría y prioridad, y en las respuestas
 * rápidas no: allí solo hay título y cuerpo.
 *
 * created_by = null → plantilla general (la ve y usa cualquiera).
 * created_by = id   → plantilla personal de ese agente (scopeVisibleTo).
 *
 * Las variables van con doble llave y salen de TicketVariableInterpolator; se
 * dejan SIN interpolar en la plantilla, que es lo que se guarda.
 *
 *   php artisan db:seed --class="Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketTemplatesSeeder"
 */
class HelpdeskTicketTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $owner = CatalogCacheService::agents()->first()?->id;

        foreach ($this->definitions($owner) as $definition) {
            // Clave por nombre + dueño: una plantilla personal y una general
            // pueden llamarse igual sin pisarse.
            TicketTemplate::updateOrCreate(
                ['name' => $definition['name'], 'created_by' => $definition['created_by']],
                $definition,
            );
        }

        $this->command?->info('Sembradas '.count($this->definitions($owner)).' plantillas de ticket.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(?int $owner): array
    {
        $general = [
            [
                'name' => 'Incidencia con un pedido',
                'description' => 'Alta rápida cuando el cliente llama por un pedido que no ha llegado, llegó incompleto o llegó dañado.',
                'subject' => 'Incidencia con el pedido de {{customer_name}}',
                'body' => "Cliente: {{customer_name}} ({{customer_email}})\n"
                    ."Último pedido en el ERP: {{erp_ultimo_pedido_numero}} del {{erp_ultimo_pedido_fecha}}\n\n"
                    ."Qué ha pasado:\n- \n\n"
                    ."Qué espera el cliente:\n- \n\n"
                    .'Comprobado en almacén: no / sí →',
                'category' => 'Estado del Pedido',
                'priority' => 'high',
            ],
            [
                'name' => 'Devolución o cambio',
                'description' => 'Para tramitar una devolución, un cambio de talla o un producto defectuoso.',
                'subject' => 'Devolución solicitada por {{customer_name}}',
                'body' => "Pedido: {{erp_ultimo_pedido_numero}}\n"
                    ."Artículo a devolver:\n"
                    ."Motivo (defectuoso / no es lo que esperaba / talla / arrepentimiento):\n"
                    ."¿Dentro de plazo?: sí / no\n\n"
                    .'Acción: recogida a domicilio / el cliente lo envía / abono directo',
                'category' => 'Estado del Pedido',
                'priority' => 'normal',
            ],
            [
                'name' => 'Consulta de facturación',
                'description' => 'Cobros duplicados, facturas que no llegan o rectificativas.',
                'subject' => 'Consulta de facturación — {{customer_name}}',
                'body' => "NIF: {{erp_nif}}\n"
                    ."Saldo pendiente: {{erp_saldo_pendiente}}\n"
                    ."Límite de crédito: {{erp_limite_credito}}\n\n"
                    ."Documento afectado (nº de factura):\n"
                    ."Qué reclama:\n\n"
                    .'Requiere rectificativa: sí / no',
                'category' => 'Consulta de Facturación',
                'priority' => 'normal',
            ],
            [
                'name' => 'Avería o fallo técnico',
                'description' => 'Plantilla con los pasos mínimos que necesita soporte técnico para no tener que volver a preguntar.',
                'subject' => 'Fallo técnico reportado por {{customer_name}}',
                'body' => "Producto / referencia:\n"
                    ."Nº de serie:\n"
                    ."Desde cuándo ocurre:\n"
                    ."Pasos para reproducirlo:\n1. \n2. \n3. \n\n"
                    ."Qué debería pasar:\n"
                    ."Qué pasa en realidad:\n\n"
                    .'Fotos o vídeo adjuntos: sí / no',
                'category' => 'Soporte Técnico',
                'priority' => 'high',
            ],
            [
                'name' => 'Alta o cambio de datos de cuenta',
                'description' => 'Cambios de titular, de dirección de facturación o de datos de contacto.',
                'subject' => 'Cambio de datos de cuenta — {{customer_name}}',
                'body' => "Cliente en ERP: {{erp_id_cliente}} ({{erp_nif}})\n"
                    ."Ciudad actual: {{erp_ciudad}}\n\n"
                    ."Dato a cambiar:\n"
                    ."Valor nuevo:\n\n"
                    ."Identidad verificada: sí / no\n"
                    .'Documento aportado:',
                'category' => 'Gestión de Cuenta',
                'priority' => 'normal',
            ],
            [
                'name' => 'Escalado urgente a nivel 2',
                'description' => 'Solo para lo que bloquea al cliente y no puede esperar al siguiente turno.',
                'subject' => 'URGENTE: {{ticket_subject}}',
                'body' => "Escalado por: {{agent_name}} el {{fecha}}\n\n"
                    ."Impacto para el cliente (qué no puede hacer ahora mismo):\n\n"
                    ."Qué se ha probado ya:\n- \n\n"
                    ."Qué se necesita de nivel 2:\n\n"
                    .'Contacto directo del cliente: {{customer_phone}}',
                'category' => 'Soporte Técnico',
                'priority' => 'urgent',
            ],
        ];

        $personal = [
            [
                'name' => 'Mi checklist de recogidas',
                'description' => 'Plantilla personal: solo la ve quien la creó. Sirve para comprobar el filtro de generales frente a personales.',
                'subject' => 'Recogida pendiente — {{customer_name}}',
                'body' => "Dirección de recogida:\n"
                    ."Franja horaria acordada:\n"
                    ."Nº de bultos:\n"
                    .'Agencia avisada: sí / no',
                'category' => 'Estado del Pedido',
                'priority' => 'normal',
            ],
        ];

        $definitions = [];

        foreach ($general as $item) {
            $definitions[] = $this->build($item, createdBy: null);
        }

        // Sin agentes en el entorno no se siembra la personal: created_by
        // apuntando a un usuario inexistente dejaría una plantilla que no ve
        // nadie (scopeVisibleTo nunca la devolvería).
        if ($owner !== null) {
            foreach ($personal as $item) {
                $definitions[] = $this->build($item, createdBy: $owner);
            }
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function build(array $item, ?int $createdBy): array
    {
        return [
            'name' => $item['name'],
            'description' => $item['description'],
            'subject' => $item['subject'],
            'body' => $item['body'],
            // Por nombre y no por id: los ids de categoría cambian entre
            // entornos y una plantilla apuntando a una que no existe se
            // aplicaría dejando el selector de categoría en blanco.
            'category_id' => $this->categoryId($item['category']),
            'priority' => $item['priority'],
            'is_active' => true,
            'created_by' => $createdBy,
        ];
    }

    private function categoryId(string $name): ?int
    {
        return TicketCategory::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');
    }
}
