<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelpdeskCannedReplySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $replies = [
            // Saludos
            [
                'shortcut' => '/bienvenida',
                'title' => 'Saludo de bienvenida',
                'category' => 'Saludos',
                'body' => 'Hola {{nombre}}, bienvenido/a a nuestro soporte. Mi nombre es {{agente}} y estoy aquí para ayudarte. ¿En qué te puedo asistir hoy?',
                'is_global' => 1,
            ],
            [
                'shortcut' => '/gracias',
                'title' => 'Agradecimiento al cliente',
                'category' => 'Saludos',
                'body' => 'Muchas gracias por contactarnos, {{nombre}}. Es un placer atenderte. Si tienes cualquier otra consulta, no dudes en escribirnos.',
                'is_global' => 1,
            ],

            // Cierre
            [
                'shortcut' => '/cierre',
                'title' => 'Cierre de conversación',
                'category' => 'Cierre',
                'body' => 'Ha sido un placer ayudarte, {{nombre}}. Vamos a cerrar esta conversación. Si necesitas algo más en el futuro, estaremos encantados de asistirte.',
                'is_global' => 1,
            ],
            [
                'shortcut' => '/sin-respuesta',
                'title' => 'Cierre por falta de respuesta',
                'category' => 'Cierre',
                'body' => 'Hola {{nombre}}, cerramos tu conversación al no haber recibido respuesta. Si aún necesitas ayuda, puedes escribirnos en cualquier momento y con gusto te atendemos.',
                'is_global' => 1,
            ],

            // Facturación
            [
                'shortcut' => '/pago-recibido',
                'title' => 'Pago recibido con éxito',
                'category' => 'Facturación',
                'body' => 'Confirmamos que hemos recibido tu pago correctamente. En breve recibirás el comprobante en tu correo electrónico. Si tienes alguna duda adicional, aquí estamos.',
                'is_global' => 1,
            ],
            [
                'shortcut' => '/factura-spam',
                'title' => 'Factura en carpeta spam',
                'category' => 'Facturación',
                'body' => 'Disculpa el inconveniente. Te pedimos que revises tu carpeta de spam o correo no deseado. Si aún no encuentras la factura, indícanos tu número de pedido y te la reenviamos de inmediato.',
                'is_global' => 1,
            ],
            [
                'shortcut' => '/reembolso',
                'title' => 'Reembolso procesado',
                'category' => 'Facturación',
                'body' => 'Tu reembolso ha sido aprobado y procesado. Dependiendo de tu banco, puede tardar entre 5 y 10 días hábiles en reflejarse. Número de referencia: {{ref}}. Lamentamos los inconvenientes.',
                'is_global' => 1,
            ],

            // Soporte técnico
            [
                'shortcut' => '/limpia-cache',
                'title' => 'Limpiar caché del navegador',
                'category' => 'Soporte técnico',
                'body' => 'Te recomendamos limpiar la caché del navegador: 1) Presiona Ctrl+Shift+Delete (PC) o Cmd+Shift+Delete (Mac) 2) Selecciona "Todo el tiempo" como rango 3) Marca "Caché e imágenes" 4) Haz clic en Borrar. Luego intenta nuevamente.',
                'is_global' => 1,
            ],
            [
                'shortcut' => '/escalando',
                'title' => 'Escalando al equipo técnico',
                'category' => 'Soporte técnico',
                'body' => 'Gracias por tu paciencia, {{nombre}}. He escalado tu caso a nuestro equipo técnico especializado. Recibirás una respuesta en un máximo de 24 horas. Referencia del caso: {{ticket}}.',
                'is_global' => 1,
            ],

            // Envíos
            [
                'shortcut' => '/rastreo',
                'title' => 'Información de rastreo',
                'category' => 'Envíos',
                'body' => 'Tu pedido está en camino. Número de seguimiento: {{tracking}}. Puedes rastrearlo en: {{url_rastreo}}. Fecha estimada de entrega: {{fecha}}. ¡Esperamos que llegue pronto!',
                'is_global' => 1,
            ],
            [
                'shortcut' => '/retraso-envio',
                'title' => 'Retraso en envío',
                'category' => 'Envíos',
                'body' => 'Lamentamos el retraso en tu envío, {{nombre}}. Estamos coordinando con la empresa de mensajería para resolverlo. Te notificaremos en cuanto tengamos una actualización. Disculpa los inconvenientes.',
                'is_global' => 1,
            ],

            // Devoluciones
            [
                'shortcut' => '/politica-devolucion',
                'title' => 'Política de devoluciones',
                'category' => 'Devoluciones',
                'body' => 'Aceptamos devoluciones dentro de los 30 días posteriores a la compra. El producto debe estar en su estado original con el embalaje intacto. El envío de retorno corre por nuestra cuenta. Inicia tu devolución aquí: {{url_devolucion}}.',
                'is_global' => 1,
            ],
            [
                'shortcut' => '/devolucion-aprobada',
                'title' => 'Devolución aprobada',
                'category' => 'Devoluciones',
                'body' => 'Tu solicitud de devolución ha sido aprobada. En breve recibirás la etiqueta de envío en tu correo. Una vez que recibamos el producto, procesaremos tu reembolso en 3-5 días hábiles.',
                'is_global' => 1,
            ],

            // Seguimiento
            [
                'shortcut' => '/seguimiento',
                'title' => 'Seguimiento de satisfacción',
                'category' => 'Seguimiento',
                'body' => '¡Hola {{nombre}}! Esperamos que tu consulta haya quedado resuelta. ¿Puedes calificar tu experiencia con nuestro soporte? Tu opinión nos ayuda a mejorar. Gracias por confiar en nosotros.',
                'is_global' => 1,
            ],
        ];

        foreach ($replies as $reply) {
            DB::connection('helpdesk')->table('helpdesk_canned_replies')->updateOrInsert(
                ['shortcut' => $reply['shortcut']],
                array_merge($reply, [
                    'content' => $reply['body'],
                    'usage_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('✅ Respuestas rápidas creadas ('.count($replies).')');
    }
}
