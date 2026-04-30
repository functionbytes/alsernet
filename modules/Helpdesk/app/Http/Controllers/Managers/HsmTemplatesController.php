<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HsmTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.conversations.update');
    }

    /**
     * Return the list of approved HSM templates.
     * In v1 this returns a hardcoded set; later can be fetched from WhatsApp Business API.
     */
    public function index(): JsonResponse
    {
        $templates = [
            [
                'id' => 1,
                'name' => 'confirmacion_pedido',
                'label' => 'Confirmación de pedido',
                'language' => 'es',
                'category' => 'Utilidad',
                'status' => 'APPROVED',
                'body' => 'Hola {{1}}, tu pedido #{{2}} ha sido confirmado. Total: {{3}}. Gracias por tu compra.',
                'vars' => ['Nombre del cliente', 'Número de pedido', 'Total del pedido'],
            ],
            [
                'id' => 2,
                'name' => 'envio_en_camino',
                'label' => 'Envío en camino',
                'language' => 'es',
                'category' => 'Utilidad',
                'status' => 'APPROVED',
                'body' => 'Tu pedido #{{1}} está en camino. Seguimiento: {{2}}. Llegará aprox. el {{3}}.',
                'vars' => ['Número de pedido', 'Código de tracking', 'Fecha estimada'],
            ],
            [
                'id' => 3,
                'name' => 'recordatorio_cita',
                'label' => 'Recordatorio de cita',
                'language' => 'es',
                'category' => 'Utilidad',
                'status' => 'APPROVED',
                'body' => 'Hola {{1}}, te recordamos tu cita el {{2}} a las {{3}}. Responde CONFIRMAR o CANCELAR.',
                'vars' => ['Nombre del cliente', 'Fecha de cita', 'Hora de cita'],
            ],
            [
                'id' => 4,
                'name' => 'encuesta_csat',
                'label' => 'Encuesta CSAT',
                'language' => 'es',
                'category' => 'Marketing',
                'status' => 'APPROVED',
                'body' => '¡Gracias {{1}}! ¿Cómo valorarías tu experiencia del 1 al 5?',
                'vars' => ['Nombre del cliente'],
            ],
            [
                'id' => 5,
                'name' => 'codigo_verificacion',
                'label' => 'Código de verificación',
                'language' => 'es',
                'category' => 'Autenticación',
                'status' => 'APPROVED',
                'body' => 'Tu código de verificación es {{1}}. Válido durante {{2}} minutos.',
                'vars' => ['Código OTP', 'Minutos de validez'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }
}
