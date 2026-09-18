<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;

/**
 * Juego de respuestas predefinidas de ejemplo, listo para probar la pantalla
 * Ajustes → Respuestas predefinidas sin tener que escribirlas a mano.
 *
 * Son las respuestas tipicas de un mostrador de soporte -bienvenida, pedir
 * mas datos, cerrar el ticket, avisar de un retraso-, no texto de relleno:
 * la idea es que se puedan insertar tal cual en un ticket real.
 *
 * Las categorias de ticket se enlazan POR NOMBRE, no por id (los ids cambian
 * entre entornos); si una categoria no esta sembrada, la respuesta se crea
 * igual mas sin ese enlace.
 *
 * Las variables {{...}} usan la MISMA sintaxis (snake_case plano, sin
 * puntos) que TicketVariableInterpolator::availableVariables() — la unica
 * fuente real que las resuelve. Hasta el 11-sep-2026 este seeder era el
 * unico que usaba notacion de punto ({{cliente.nombre}}, {{agente.nombre}},
 * {{ticket.resumen}}), copiada de un formato que nunca existio aqui: nunca
 * se reemplazaban y el agente las mandaba al cliente tal cual.
 *
 *   php artisan db:seed --class="Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketCannedReplySeeder"
 */
class HelpdeskTicketCannedReplySeeder extends Seeder
{
    public function run(): void
    {
        $categories = TicketCategory::pluck('id', 'name');

        foreach ($this->definitions() as $definition) {
            $categoryNames = $definition['ticket_categories'] ?? [];
            unset($definition['ticket_categories']);

            // updateOrCreate por short_code (columna unica): re-sembrar no
            // duplica ni pisa el usage_count de lo que ya se haya usado.
            $reply = TicketCannedReply::updateOrCreate(
                ['short_code' => $definition['short_code']],
                $definition,
            );

            $categoryIds = collect($categoryNames)
                ->map(fn (string $name) => $categories->get($name))
                ->filter()
                ->values();

            if ($categoryIds->isNotEmpty()) {
                $reply->ticketCategories()->sync(
                    $categoryIds->mapWithKeys(fn ($id, $order) => [$id => ['order' => $order + 1]])
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            [
                'title' => 'Bienvenida y primera respuesta',
                'short_code' => '/bienvenida',
                'category' => 'General',
                'tags' => ['bienvenida', 'primera-respuesta'],
                'content' => "Hola {{customer_name}},\n\nGracias por escribirnos. Hemos recibido tu consulta y ya la estamos revisando; te responderemos con una solucion lo antes posible.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Gracias por escribirnos. Hemos recibido tu consulta y ya la estamos revisando; te responderemos con una solucion lo antes posible.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 342,
                'ticket_categories' => ['Otro'],
            ],
            [
                'title' => 'Solicitar mas informacion',
                'short_code' => '/mas-info',
                'category' => 'General',
                'tags' => ['seguimiento', 'informacion'],
                'content' => "Hola {{customer_name}},\n\nPara poder ayudarte mejor, ¿podrias confirmarnos lo siguiente?\n\n- Numero de pedido o referencia\n- Capturas de pantalla del problema, si es posible\n- Pasos que seguiste antes de que ocurriera\n\nEn cuanto tengamos esos datos seguimos con el caso.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Para poder ayudarte mejor, &iquest;podrias confirmarnos lo siguiente?</p><ul><li>Numero de pedido o referencia</li><li>Capturas de pantalla del problema, si es posible</li><li>Pasos que seguiste antes de que ocurriera</li></ul><p>En cuanto tengamos esos datos seguimos con el caso.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 198,
                'ticket_categories' => ['Soporte Técnico', 'Reclamación'],
            ],
            [
                'title' => 'Pasos para reproducir un fallo tecnico',
                'short_code' => '/repro-fallo',
                'category' => 'Soporte técnico',
                'tags' => ['tecnico', 'diagnostico'],
                'content' => "Hola {{customer_name}},\n\nPara diagnosticar el problema necesitamos reproducirlo en nuestro entorno. ¿Podrias indicarnos?\n\n1. Navegador y version (o app y version, si es movil)\n2. Los pasos exactos que sigues hasta ver el error\n3. Si le ocurre solo a un usuario o a todo el equipo\n\nCon eso lo replicamos y te contamos.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Para diagnosticar el problema necesitamos reproducirlo en nuestro entorno. &iquest;Podrias indicarnos?</p><ol><li>Navegador y version (o app y version, si es movil)</li><li>Los pasos exactos que sigues hasta ver el error</li><li>Si le ocurre solo a un usuario o a todo el equipo</li></ol><p>Con eso lo replicamos y te contamos.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 87,
                'ticket_categories' => ['Soporte Técnico'],
            ],
            [
                'title' => 'Escalado a soporte de nivel 2',
                'short_code' => '/escalado',
                'category' => 'Soporte técnico',
                'tags' => ['escalado', 'tecnico'],
                'content' => "Hola {{customer_name}},\n\nTu caso requiere revision de nuestro equipo tecnico especializado, asi que lo estamos escalando. Un compañero se pondra en contacto contigo en las proximas 24-48h con mas detalle.\n\nGracias por tu paciencia.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Tu caso requiere revision de nuestro equipo tecnico especializado, asi que lo estamos escalando. Un compa&ntilde;ero se pondra en contacto contigo en las proximas 24-48h con mas detalle.</p><p>Gracias por tu paciencia.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 41,
                'ticket_categories' => ['Soporte Técnico'],
            ],
            [
                'title' => 'Consulta de facturacion resuelta',
                'short_code' => '/factura-resuelta',
                'category' => 'Facturación',
                'tags' => ['facturacion', 'pago'],
                'content' => "Hola {{customer_name}},\n\nHemos revisado tu cargo y todo esta correcto: {{ticket_subject}}\n\nTe adjuntamos la factura actualizada. Si tienes cualquier otra duda con la facturacion, escribenos de nuevo.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Hemos revisado tu cargo y todo esta correcto: {{ticket_subject}}</p><p>Te adjuntamos la factura actualizada. Si tienes cualquier otra duda con la facturacion, escribenos de nuevo.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 156,
                'ticket_categories' => ['Consulta de Facturación'],
            ],
            [
                'title' => 'Reembolso en proceso',
                'short_code' => '/reembolso-proceso',
                'category' => 'Facturación',
                'tags' => ['reembolso', 'facturacion'],
                'content' => "Hola {{customer_name}},\n\nYa hemos tramitado tu reembolso. El importe puede tardar entre 3 y 7 dias habiles en reflejarse en tu medio de pago original, segun tu banco.\n\nSi pasado ese plazo no lo ves reflejado, contactanos y lo revisamos.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Ya hemos tramitado tu reembolso. El importe puede tardar entre 3 y 7 dias habiles en reflejarse en tu medio de pago original, segun tu banco.</p><p>Si pasado ese plazo no lo ves reflejado, contactanos y lo revisamos.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 73,
                'ticket_categories' => ['Consulta de Facturación', 'Reclamación'],
            ],
            [
                'title' => 'Seguimiento de pedido retrasado',
                'short_code' => '/pedido-retraso',
                'category' => 'Pedidos',
                'tags' => ['envio', 'retraso'],
                'content' => "Hola {{customer_name}},\n\nLamentamos el retraso en tu pedido. Hemos contactado con el transportista y nos confirman una nueva fecha estimada de entrega en las proximas 48-72h.\n\nTe avisaremos en cuanto tengamos el numero de seguimiento actualizado.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Lamentamos el retraso en tu pedido. Hemos contactado con el transportista y nos confirman una nueva fecha estimada de entrega en las proximas 48-72h.</p><p>Te avisaremos en cuanto tengamos el numero de seguimiento actualizado.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 214,
                'ticket_categories' => ['Estado del Pedido'],
            ],
            [
                'title' => 'Verificacion de identidad antes de continuar',
                'short_code' => '/verificar-identidad',
                'category' => 'Cuenta',
                'tags' => ['seguridad', 'cuenta'],
                'content' => "Hola {{customer_name}},\n\nPor tratarse de datos sensibles de tu cuenta, antes de continuar necesitamos verificar tu identidad. Te hemos enviado un codigo a tu correo/telefono registrado; indicanoslo en tu proxima respuesta.\n\nGracias por tu comprension.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Por tratarse de datos sensibles de tu cuenta, antes de continuar necesitamos verificar tu identidad. Te hemos enviado un codigo a tu correo/telefono registrado; indicanoslo en tu proxima respuesta.</p><p>Gracias por tu comprension.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 29,
                'ticket_categories' => ['Gestión de Cuenta'],
            ],
            [
                'title' => 'Contraseña restablecida',
                'short_code' => '/password-ok',
                'category' => 'Cuenta',
                'tags' => ['cuenta', 'acceso'],
                'content' => "Hola {{customer_name}},\n\nYa hemos restablecido el acceso a tu cuenta. Te recomendamos cambiar la contraseña la proxima vez que inicies sesion y activar la verificacion en dos pasos si aun no la tienes.\n\n¿Necesitas algo mas?\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Ya hemos restablecido el acceso a tu cuenta. Te recomendamos cambiar la contrase&ntilde;a la proxima vez que inicies sesion y activar la verificacion en dos pasos si aun no la tienes.</p><p>&iquest;Necesitas algo mas?</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 165,
                'ticket_categories' => ['Gestión de Cuenta'],
            ],
            [
                'title' => 'Disculpas por la demora en responder',
                'short_code' => '/disculpa-demora',
                'category' => 'General',
                'tags' => ['disculpa', 'seguimiento'],
                'content' => "Hola {{customer_name}},\n\nSentimos mucho la demora en responder tu consulta; hemos tenido un volumen de solicitudes mayor de lo habitual. Seguimos con tu caso ahora mismo.\n\nGracias por tu paciencia.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Sentimos mucho la demora en responder tu consulta; hemos tenido un volumen de solicitudes mayor de lo habitual. Seguimos con tu caso ahora mismo.</p><p>Gracias por tu paciencia.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 58,
                'ticket_categories' => [],
            ],
            [
                'title' => 'Ticket resuelto y cierre',
                'short_code' => '/resuelto',
                'category' => 'General',
                'tags' => ['cierre', 'resuelto'],
                'content' => "Hola {{customer_name}},\n\nDamos por resuelta tu consulta. Si en los proximos dias detectas que el problema persiste, puedes responder a este mismo ticket y lo reabrimos sin necesidad de crear uno nuevo.\n\nGracias por contactarnos.\n\nUn saludo,\n{{agent_name}}",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Damos por resuelta tu consulta. Si en los proximos dias detectas que el problema persiste, puedes responder a este mismo ticket y lo reabrimos sin necesidad de crear uno nuevo.</p><p>Gracias por contactarnos.</p><p>Un saludo,<br>{{agent_name}}</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 421,
                'ticket_categories' => [],
            ],
            [
                'title' => 'Fuera de horario laboral',
                'short_code' => '/fuera-horario',
                'category' => 'General',
                'tags' => ['horario', 'auto-respuesta'],
                'content' => "Hola {{customer_name}},\n\nGracias por tu mensaje. Nuestro horario de atencion es de lunes a viernes de 9:00 a 18:00; lo hemos recibido fuera de ese horario y lo atenderemos en cuanto abramos.\n\nUn saludo,\nEquipo de Soporte",
                'html_body' => '<p>Hola {{customer_name}},</p><p>Gracias por tu mensaje. Nuestro horario de atencion es de lunes a viernes de 9:00 a 18:00; lo hemos recibido fuera de ese horario y lo atenderemos en cuanto abramos.</p><p>Un saludo,<br>Equipo de Soporte</p>',
                'is_global' => true,
                'is_active' => true,
                'usage_count' => 302,
                'ticket_categories' => [],
            ],
            [
                'title' => 'Plantilla antigua de bienvenida (en desuso)',
                'short_code' => '/bienvenida-v1',
                'category' => 'General',
                'tags' => ['obsoleta'],
                'content' => "Hola,\n\nGracias por contactar con nosotros. Te responderemos pronto.\n\nSaludos.",
                'html_body' => '<p>Hola,</p><p>Gracias por contactar con nosotros. Te responderemos pronto.</p><p>Saludos.</p>',
                'is_global' => true,
                'is_active' => false,
                'usage_count' => 12,
                'ticket_categories' => [],
            ],
        ];
    }
}
