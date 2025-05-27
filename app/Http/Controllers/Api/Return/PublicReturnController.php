<?php

namespace App\Http\Controllers\Api\Return;

use App\Http\Controllers\Controller;
use App\Models\Return\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class PublicReturnController extends Controller
{
    /**
     * Consultar estado público - sin autenticación
     */
    public function getStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $return = ReturnRequest::where('id_order', $request->order_id)
            ->where('email', $request->email)
            ->with(['status.state', 'returnType', 'returnReason'])
            ->first();

        if (!$return) {
            return response()->json(['message' => 'No se encontró solicitud de devolución'], 404);
        }

        return response()->json([
            'id_return_request' => $return->id_return_request,
            'id_order' => $return->id_order,
            'customer_name' => $return->customer_name,
            'status' => $return->getStatusName(),
            'status_color' => $return->status->color,
            'return_type' => $return->getReturnTypeName(),
            'return_reason' => $return->getReturnReasonName(),
            'logistics_mode' => $return->getLogisticsModeLabel(),
            'description' => $return->description,
            'product_quantity' => $return->product_quantity,
            'is_refunded' => $return->is_refunded,
            'created_at' => $return->created_at,
            'received_date' => $return->received_date,
            'pickup_date' => $return->pickup_date,
            'can_be_modified' => $return->canBeModified()
        ]);
    }

    /**
     * Crear solicitud sin autenticación (para invitados)
     */
    public function createGuestReturn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_order' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'id_return_type' => 'required|integer|exists:return_types,id_return_type',
            'id_return_reason' => 'required|integer|exists:return_reasons,id_return_reason',
            'logistics_mode' => 'required|in:customer_transport,home_pickup,store_delivery,inpost',
            'description' => 'required|string|min:10',
            'product_quantity' => 'required|integer|min:1',
            'return_address' => 'nullable|string',
            'iban' => 'nullable|string|max:34'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que el pedido no tenga ya una devolución activa
        $existingReturn = ReturnRequest::where('id_order', $request->id_order)
            ->where('email', $request->email)
            ->whereNotIn('id_return_status', [6, 7]) // No rechazadas o completadas
            ->first();

        if ($existingReturn) {
            return response()->json([
                'error' => 'Ya existe una solicitud de devolución activa para este pedido'
            ], 409);
        }

        try {
            $returnService = app(ReturnService::class);
            $data = $request->all();
            $data['created_by'] = 'guest';
            $data['id_customer'] = 0; // Cliente invitado

            $return = $returnService->createReturnRequest($data);

            return response()->json([
                'message' => 'Solicitud de devolución creada exitosamente',
                'return_id' => $return->id_return_request,
                'tracking_info' => [
                    'order_id' => $return->id_order,
                    'email' => $return->email,
                    'return_id' => $return->id_return_request
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
}
