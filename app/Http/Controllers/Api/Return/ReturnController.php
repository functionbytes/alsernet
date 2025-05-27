<?php

namespace App\Http\Controllers\Api\Return;

use App\Http\Controllers\Controller;
use App\Models\Return\ReturnReason;
use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnType;
use App\Services\ReturnPDFService;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReturnController extends Controller
{
    protected $returnService;
    protected $pdfService;

    public function __construct(ReturnService $returnService, ReturnPDFService $pdfService)
    {
        $this->returnService = $returnService;
        $this->pdfService = $pdfService;
    }

    /**
     * Listar devoluciones del cliente autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = auth()->user()->id_customer ?? null;
        $email = auth()->user()->email;

        $query = ReturnRequest::query();

        // Filtros de seguridad por cliente
        if ($customerId) {
            $query->byCustomer($customerId);
        } elseif ($email) {
            $query->byEmail($email);
        } else {
            return response()->json(['error' => 'Cliente no identificado'], 403);
        }

        // Filtros adicionales
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('return_type')) {
            $query->where('id_return_type', $request->return_type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $returns = $query->with(['status.state', 'returnType', 'returnReason'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Formatear respuesta
        $returns->getCollection()->transform(function ($return) {
            return [
                'id_return_request' => $return->id_return_request,
                'id_order' => $return->id_order,
                'status' => $return->getStatusName(),
                'status_color' => $return->status->color,
                'return_type' => $return->getReturnTypeName(),
                'return_reason' => $return->getReturnReasonName(),
                'logistics_mode' => $return->getLogisticsModeLabel(),
                'product_quantity' => $return->product_quantity,
                'is_refunded' => $return->is_refunded,
                'created_at' => $return->created_at,
                'can_download_pdf' => !empty($return->pdf_path),
                'can_be_modified' => $return->canBeModified()
            ];
        });

        return response()->json($returns);
    }

    /**
     * Crear nueva solicitud de devolución
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_order' => 'required|integer',
            'id_order_detail' => 'required|integer',
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'id_return_type' => 'required|integer|exists:return_types,id_return_type',
            'id_return_reason' => 'required|integer|exists:return_reasons,id_return_reason',
            'logistics_mode' => 'required|in:customer_transport,home_pickup,store_delivery,inpost',
            'description' => 'required|string|min:10',
            'product_quantity' => 'required|integer|min:1',
            'return_address' => 'nullable|string',
            'iban' => 'nullable|string|max:34|regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/',
            'id_customer' => 'nullable|integer',
            'id_address' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();
            $data['created_by'] = 'web';

            $return = $this->returnService->createReturnRequest($data);

            return response()->json([
                'message' => 'Solicitud de devolución creada exitosamente',
                'data' => $return,
                'return_id' => $return->id_return_request
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar solicitud específica
     */
    public function show($id): JsonResponse
    {
        $customerId = auth()->user()->id_customer ?? null;
        $email = auth()->user()->email;

        $query = ReturnRequest::where('id_return_request', $id);

        if ($customerId) {
            $query->byCustomer($customerId);
        } elseif ($email) {
            $query->byEmail($email);
        } else {
            return response()->json(['error' => 'Cliente no identificado'], 403);
        }

        $return = $query->with([
            'status.state',
            'returnType',
            'returnReason',
            'discussions' => function($q) {
                $q->where('private', false)->orderBy('created_at', 'desc');
            },
            'history' => function($q) {
                $q->where('shown_to_customer', true)->orderBy('created_at', 'desc');
            }
        ])->firstOrFail();

        return response()->json([
            'id_return_request' => $return->id_return_request,
            'id_order' => $return->id_order,
            'customer_name' => $return->customer_name,
            'email' => $return->email,
            'phone' => $return->phone,
            'status' => $return->getStatusName(),
            'status_color' => $return->status->color,
            'return_type' => $return->getReturnTypeName(),
            'return_reason' => $return->getReturnReasonName(),
            'logistics_mode' => $return->getLogisticsModeLabel(),
            'description' => $return->description,
            'return_address' => $return->return_address,
            'product_quantity' => $return->product_quantity,
            'is_refunded' => $return->is_refunded,
            'received_date' => $return->received_date,
            'pickup_date' => $return->pickup_date,
            'created_at' => $return->created_at,
            'can_be_modified' => $return->canBeModified(),
            'discussions' => $return->discussions,
            'history' => $return->history->map(function($h) {
                return [
                    'date' => $h->created_at,
                    'status' => $h->status->getTranslation()->name ?? 'Desconocido',
                    'description' => $h->description
                ];
            })
        ]);
    }

    /**
     * Descargar PDF de la devolución
     */
    public function downloadPDF($id)
    {
        $customerId = auth()->user()->id_customer ?? null;
        $email = auth()->user()->email;

        $query = ReturnRequest::where('id_return_request', $id);

        if ($customerId) {
            $query->byCustomer($customerId);
        } elseif ($email) {
            $query->byEmail($email);
        } else {
            return response()->json(['error' => 'Cliente no identificado'], 403);
        }

        $return = $query->with(['status.state', 'returnType', 'returnReason'])->firstOrFail();

        try {
            $pdf = $this->pdfService->generateReturnPDF($return);

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="devolucion-' . $return->id_return_request . '.pdf"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener datos para formularios
     */
    public function getFormData(): JsonResponse
    {
        $returnTypes = ReturnType::with('translations')->get();
        $returnReasons = ReturnReason::with('translations')->where('active', true)->get();

        return response()->json([
            'return_types' => $returnTypes->map(function($type) {
                $translation = $type->getTranslation();
                return [
                    'id_return_type' => $type->id_return_type,
                    'name' => $translation->name ?? 'Desconocido',
                    'day' => $translation->day ?? 30,
                    'color' => $translation->return_color ?? '#000000'
                ];
            }),
            'return_reasons' => $returnReasons->map(function($reason) {
                $translation = $reason->getTranslation();
                return [
                    'id_return_reason' => $reason->id_return_reason,
                    'name' => $translation->name ?? 'Desconocido',
                    'return_type' => $reason->return_type
                ];
            }),
            'logistics_modes' => [
                'customer_transport' => 'Agencia de transporte (cuenta del cliente)',
                'home_pickup' => 'Recogida a domicilio',
                'store_delivery' => 'Entrega en tienda',
                'inpost' => 'InPost'
            ]
        ]);
    }
}
