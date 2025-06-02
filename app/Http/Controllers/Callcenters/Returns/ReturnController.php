<?php


namespace App\Http\Controllers\Callcenters\Returns;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Return\ReturnOrder;
use App\Models\Return\ReturnOrderProduct;
use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnRequestProduct;
use App\Models\Carrier;
use App\Models\StoreLocation;
use App\Services\ErpService;
use App\Services\Return\BarcodeService;
use App\Services\Return\DocumentService;
use App\Facades\Carrier as CarrierFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnController extends Controller
{
    protected $erpService;
    protected $barcodeService;
    protected $documentService;

    public function __construct(
        ErpService $erpService,
        BarcodeService $barcodeService,
        DocumentService $documentService
    ) {
        $this->erpService = $erpService;
        $this->barcodeService = $barcodeService;
        $this->documentService = $documentService;
    }

    /**
     * Generar nueva solicitud de devolución
     */
    public function generate($uid)
    {
        try {
            // Obtener orden del ERP
            $orderData = $this->erpService->retrieveOrderById($uid);

            if (!$orderData || empty($orderData['resource'])) {
                return back()->with('error', 'No se encontró el pedido en ERP.');
            }

            $erpOrder = $orderData;

            if (empty($erpOrder['resource']['cliente'])) {
                return back()->with('error', 'El pedido no tiene información de cliente.');
            }

            // Buscar o crear la orden en nuestra base de datos
            $order = $this->findOrCreateOrder($erpOrder);

            // Sincronizar cliente
            $customer = $this->syncErpClientToCustomer($erpOrder['resource']['cliente'], $this->erpService);

            if (!$customer) {
                return back()->with('error', 'No se pudo sincronizar el cliente.');
            }

            // Crear solicitud de devolución base
            $returnRequest = ReturnRequest::createFromOrder($order, [
                'customer_id' => $customer->id,
                'type_id' => 1, // Reembolso por defecto
                'description' => 'Devolución creada desde call center',
                'created_by' => auth()->id(),
            ]);

            // Validar elegibilidad
            $validation = $returnRequest->validateOrderEligibility();

            // Obtener productos devolvibles
            $returnableProducts = $this->getReturnableProducts($order);

            // Obtener métodos de recogida disponibles
            $postalCode = $order->shipping_postal_code;
            $availableCarriers = CarrierFacade::getAvailableCarriers($postalCode);

            // Obtener tiendas cercanas si hay coordenadas
            $nearbyStores = [];
            if ($customer->latitude && $customer->longitude) {
                $nearbyStores = CarrierFacade::findNearbyStores(
                    $customer->latitude,
                    $customer->longitude,
                    20 // 20km de radio
                );
            }

            return view('callcenters.views.returns.generate')->with([
                'return' => $returnRequest,
                'customer' => $customer,
                'order' => $order,
                'validation' => $validation,
                'returnableProducts' => $returnableProducts,
                'returnReasons' => $this->getReturnReasons(),
                'returnConditions' => $this->getReturnConditions(),
                'availableCarriers' => $availableCarriers,
                'nearbyStores' => $nearbyStores
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating return request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al crear la solicitud de devolución: ' . $e->getMessage());
        }
    }

    /**
     * Guardar solicitud de devolución con método de recogida
     */
    public function store(Request $request)
    {
        $request->validate([
            'return_id' => 'required|exists:return_requests,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:return_order_products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.reason' => 'required|string',
            'products.*.condition' => 'required|string',
            'products.*.other_reason_description' => 'nullable|string|required_if:products.*.reason,other',
            'notes' => 'nullable|string',

            // Validación del método de recogida
            'logistics_mode' => 'required|in:customer_transport,home_pickup,store_delivery,inpost',

            // Validaciones condicionales según método
            'carrier_id' => 'required_if:logistics_mode,home_pickup|exists:carriers,id',
            'pickup_date' => 'required_if:logistics_mode,home_pickup|date|after:today',
            'pickup_time_slot' => 'required_if:logistics_mode,home_pickup',
            'pickup_address' => 'required_if:logistics_mode,home_pickup|array',
            'contact_name' => 'required_if:logistics_mode,home_pickup',
            'contact_phone' => 'required_if:logistics_mode,home_pickup',

            'store_location_id' => 'required_if:logistics_mode,store_delivery|exists:store_locations,id',
            'expected_delivery_date' => 'required_if:logistics_mode,store_delivery|date|after:today',

            'locker_id' => 'required_if:logistics_mode,inpost'
        ]);

        DB::beginTransaction();

        try {
            $returnRequest = ReturnRequest::findOrFail($request->return_id);

            // Limpiar items existentes si los hay
            $returnRequest->products()->delete();

            // Crear nuevos items
            foreach ($request->products as $productData) {
                $orderProduct = $returnRequest->order->products()
                    ->where('id', $productData['product_id'])
                    ->first();

                ReturnRequestProduct::create([
                    'request_id' => $returnRequest->id,
                    'product_id' => $productData['product_id'],
                    'product_code' => $orderProduct->product_code,
                    'product_name' => $orderProduct->product_name,
                    'quantity' => $productData['quantity'],
                    'unit_price' => $orderProduct->unit_price,
                    'total_price' => $productData['quantity'] * $orderProduct->unit_price,
                    'reason_id' => $productData['reason'],
                    'return_condition' => $productData['condition'],
                    'notes' => $productData['other_reason_description'] ?? null
                ]);
            }

            // Actualizar información de la devolución
            $returnRequest->update([
                'description' => $request->notes,
                'logistics_mode' => $request->logistics_mode
            ]);

            // Calcular total
            $returnRequest->calculateTotal();

            // Gestionar método de recogida
            switch ($request->logistics_mode) {
                case 'home_pickup':
                    $this->handleHomePickup($returnRequest, $request);
                    break;

                case 'store_delivery':
                    $this->handleStoreDelivery($returnRequest, $request);
                    break;

                case 'inpost':
                    $this->handleInPostDelivery($returnRequest, $request);
                    break;

                case 'customer_transport':
                    // Agencia de transporte - el cliente se encarga
                    $returnRequest->update([
                        'carrier_id' => null,
                        'status_id' => 2 // Aprobada, esperando envío
                    ]);
                    break;
            }

            // Generar códigos de barras
            $barcodes = $this->barcodeService->generateForReturn($returnRequest);

            // Generar documentos
            $documents = $this->documentService->generateAllDocuments($returnRequest);

            DB::commit();

            // Enviar notificaciones
            $this->sendReturnNotifications($returnRequest);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de devolución creada exitosamente',
                'return_id' => $returnRequest->id,
                'total_amount' => $returnRequest->total_amount,
                'documents' => [
                    'labels' => route('returns.documents.download', [
                        'document' => $documents['barcode_sheet'] ?? null
                    ]),
                    'instructions' => route('returns.documents.download', [
                        'document' => $documents['return_slip'] ?? null
                    ])
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error saving return request', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestionar recogida a domicilio
     */
    protected function handleHomePickup(ReturnRequest $returnRequest, Request $request)
    {
        $pickupData = [
            'carrier_id' => $request->carrier_id,
            'pickup_date' => $request->pickup_date,
            'pickup_time_slot' => $request->pickup_time_slot,
            'pickup_address' => $request->pickup_address,
            'contact_name' => $request->contact_name,
            'contact_phone' => $request->contact_phone,
            'contact_email' => $request->contact_email ?? $returnRequest->email,
            'packages_count' => $returnRequest->products->count(),
            'total_weight' => $this->calculateTotalWeight($returnRequest)
        ];

        $pickupRequest = CarrierFacade::createPickupRequest($returnRequest, $pickupData);

        // Actualizar return request
        $returnRequest->update([
            'carrier_id' => $request->carrier_id,
            'pickup_scheduled_at' => $request->pickup_date . ' ' . explode('-', $request->pickup_time_slot)[0],
            'status_id' => 3 // Recogida programada
        ]);

        // Generar etiqueta del carrier
        CarrierFacade::generateShippingLabel($pickupRequest);
    }

    /**
     * Gestionar entrega en tienda
     */
    protected function handleStoreDelivery(ReturnRequest $returnRequest, Request $request)
    {
        $storeDelivery = CarrierFacade::scheduleStoreDelivery($returnRequest, [
            'store_location_id' => $request->store_location_id,
            'expected_delivery_date' => $request->expected_delivery_date,
            'notes' => $request->store_notes ?? null
        ]);

        $returnRequest->update([
            'status_id' => 2 // Aprobada, esperando entrega
        ]);
    }

    /**
     * Gestionar entrega en InPost
     */
    protected function handleInPostDelivery(ReturnRequest $returnRequest, Request $request)
    {
        // Obtener carrier de InPost
        $inpostCarrier = Carrier::where('code', 'INPOST')->first();

        if (!$inpostCarrier) {
            throw new \Exception('Servicio InPost no disponible');
        }

        // Crear envío en InPost
        $pickupData = [
            'carrier_id' => $inpostCarrier->id,
            'locker_id' => $request->locker_id,
            'pickup_date' => now()->addDay(), // InPost es inmediato
            'pickup_time_slot' => '00:00-23:59',
            'pickup_address' => [
                'locker_id' => $request->locker_id
            ],
            'contact_name' => $returnRequest->customer_name,
            'contact_phone' => $returnRequest->phone,
            'contact_email' => $returnRequest->email,
            'packages_count' => 1, // InPost agrupa en un paquete
            'total_weight' => $this->calculateTotalWeight($returnRequest)
        ];

        $pickupRequest = CarrierFacade::createPickupRequest($returnRequest, $pickupData);

        // Generar QR Code
        $inpostService = CarrierFacade::getCarrier('INPOST');
        if ($inpostService && method_exists($inpostService, 'generateQRCode')) {
            $qrPath = $inpostService->generateQRCode($pickupRequest->pickup_code);
        }
    }

}
