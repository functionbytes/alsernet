<?php
namespace App\Http\Controllers\Callcenters\Returns;

use App\Http\Controllers\Controller;
use App\Library\Log;
use App\Models\Customer;
use App\Models\Return\ReturnOrder;
use App\Models\Return\ReturnOrderProduct;
use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnReason;
use App\Models\Return\ReturnRequestProduct;
use App\Models\Return\ReturnType;
use App\Services\ErpService;
use App\Services\Return\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReturnsController extends Controller
{
    protected $erpService;

    public function __construct(ErpService $erpService)
    {
        $this->erpService = $erpService;
    }

    public function index(Request $request)
    {
        $returns = ReturnRequest::with(['status.state', 'returnType', 'returnReason'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('callcenters.views.returns.index', compact('returns'));
    }


    public function genserate($uid)
    {
        $validation = [];
        $erpService = new ErpService();
        $orderData = $erpService->retrieveOrderById($uid);

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
        $customer = $this->syncErpClientToCustomer($erpOrder['resource']['cliente'], $erpService);
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

        Log::info('Return request created from ERP order', [
            'return_id' => $returnRequest->id_return_request,
            'order_id' => $order->id,
            'erp_order_id' => $order->erp_order_id,
            'created_by' => auth()->id()
        ]);

        // Validar elegibilidad
        //$validation = $returnRequest->validateOrderEligibility();

        //if (!$validation['can_proceed']) {
        //    return back()
        //        ->with('error', 'No se puede crear la devolución.')
        //        ->with('validation_errors', $validation['errors']);
        //}

        // Mostrar advertencias si las hay
        //if (!empty($validation['warnings'])) {
        //    session()->flash('warnings', $validation['warnings']);
        //}

        return view('callcenters.views.returns.create')->with([
            'return' => $returnRequest,
            'customer' => $customer,
            'order' => $order,
            'validation' => $validation,
            'products' => ReturnOrderProduct::getReturnableByOrder($order->id),
            'returnableProducts' => ReturnOrderProduct::getReturnableByOrder($order->id),
        ]);
    }

    public function generate($uid)
    {
         $validation = [];
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

            return view('callcenters.views.returns.generate')->with([
                'return' => $returnRequest,
                'customer' => $customer,
                'order' => $order,
                'validation' => $validation,
                'returnableProducts' => $returnableProducts,
                'returnReasons' => $this->getReturnReasons(),
                'returnConditions' => $this->getReturnConditions()
            ]);

    }

    public function createss($uid)
    {
        $erpService = new ErpService();
        $orderData = $erpService->retrieveOrderById($uid);

        if (!$orderData || empty($orderData['resource'])) {
            return back()->with('error', 'No se encontró el pedido en ERP.');
        }

        // Verificar que tiene cliente
        if (empty($orderData['resource']['cliente'])) {
            return back()->with('error', 'El pedido no tiene información de cliente.');
        }

        // Buscar o crear la orden en nuestra base de datos
        $order = $this->findOrCreateOrder($orderData);

        // Verificar si la orden permite devoluciones
        //if (!$order->canCreateReturns()) {
         //   return back()->with('error', 'Esta orden no permite crear devoluciones en este momento.');
        //}

        // Sincronizar cliente si es necesario
        $customer = $this->syncErpClientToCustomer($orderData['resource']['cliente'], $erpService);


        // Crear la solicitud de devolución base
        $returnRequest = ReturnRequest::createFromOrder($order, [
            'customer_id' => $customer?->id,
            'type_id' => 1, // Por defecto reembolso
            'description' => 'Devolución creada desde call center',
            'created_by' => auth()->id()
        ]);

        Log::info('Return request created from ERP order', [
            'return_id' => $returnRequest->id_return_request,
            'order_id' => $order->id,
            'erp_order_id' => $order->erp_order_id,
            'created_by' => auth()->id()
        ]);

        $returnRequest = ReturnRequest::createFromOrder($uid);

        // Validar elegibilidad
        $validation = $returnRequest->validateOrderEligibility();

        if (!$validation['can_proceed']) {
            return back()
                ->with('error', 'No se puede crear la devolución')
                ->with('validation_errors', $validation['errors']);
        }

        // Mostrar advertencias si las hay
        if (!empty($validation['warnings'])) {
            session()->flash('warnings', $validation['warnings']);
        }

        return view('callcenters.views.returns.validate')->with([
            'return' => $returnRequest,
            'order' => $order,
            'validation' => $validation,
            'returnableProducts' => ReturnOrderProduct::getReturnableByOrder($order->id)
        ]);
    }

    public function createsss($uid)
    {
        try {
            // Obtener datos del ERP
            $erpService = new ErpService();
            $orderData = $erpService->retrieveOrderById($uid);

            if (!$orderData || empty($orderData['resource'])) {
                return back()->with('error', 'No se encontró el pedido en ERP.');
            }

            // Verificar que tiene cliente
            if (empty($orderData['resource']['cliente'])) {
                return back()->with('error', 'El pedido no tiene información de cliente.');
            }

            // Buscar o crear la orden en nuestra base de datos
            $order = $this->findOrCreateOrder($orderData);

            // Verificar si la orden permite devoluciones
            if (!$order->canCreateReturn()) {
                return back()->with('error', 'Esta orden no permite crear devoluciones en este momento.');
            }

            dd($order);

            // Sincronizar cliente si es necesario
            $customer = $this->syncErpClientToCustomer($orderData['resource']['cliente'], $erpService);

            // Crear la solicitud de devolución base
            $returnRequest = ReturnRequest::createFromOrder($order, [
                'customer_id' => $customer?->id,
                'type_id' => 1, // Por defecto reembolso
                'description' => 'Devolución creada desde call center',
                'created_by' => auth()->id()
            ]);

            Log::info('Return request created from ERP order', [
                'return_id' => $returnRequest->id_return_request,
                'order_id' => $order->id,
                'erp_order_id' => $order->erp_order_id,
                'created_by' => auth()->id()
            ]);

            return view('callcenters.views.returns.validate')->with([
                'return' => $returnRequest,
                'order' => $order,
                'returnableProducts' => ReturnOrderProduct::getReturnableByOrder($order->id),
                'customer' => $customer
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating return from ERP', [
                'erp_order_id' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al procesar el pedido: ' . $e->getMessage());
        }
    }


    private function findOrCreateOrder(array $orderData): ReturnOrder
    {
        $erpOrderId = $orderData['resource']['idpedidocli'];

        // Buscar orden existente
        $order = ReturnOrder::byErpId($erpOrderId)->first();

        if ($order) {
            // Actualizar datos si es necesario
            $order->updateFromErpData($orderData);
            Log::info('Order updated from ERP', ['order_id' => $order->id, 'erp_id' => $erpOrderId]);
        } else {
            // Crear nueva orden
            $order = ReturnOrder::createFromErpData($orderData);

            // Crear productos de la orden
            if (!empty($orderData['resource']['lineas_pedido_cliente']['resource'])) {
                ReturnOrderProduct::createFromErpLines(
                    $order->id,
                    $orderData['resource']['lineas_pedido_cliente']['resource']
                );
            }

            Log::info('New order created from ERP', ['order_id' => $order->id, 'erp_id' => $erpOrderId]);
        }

        return $order;
    }

    /**
     * Sincronizar cliente del ERP
     */
    private function syncErpClientToCustomer(array $clienteData, ErpService $erpService): ?Customer
    {
        try {

            $erpClientId = $clienteData['idcliente'];

            // Buscar cliente existente
            $customer = Customer::where('erp_client_id', $erpClientId)->first();

            if (!$customer) {
                // Obtener datos completos del cliente desde ERP
                $customerData = $erpService->retrieveErpClientId($erpClientId);

                if ($customerData && !empty($customerData['resource'])) {
                    $customer = Customer::createFromErpData($customerData['resource'], $clienteData);
                    Log::info('Customer created from ERP', ['customer_id' => $customer->id, 'erp_id' => $erpClientId]);
                }
            }

            return $customer;
        } catch (\Exception $e) {
            Log::warning('Failed to sync ERP client', [
                'erp_client_id' => $clienteData['idcliente'] ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Procesar selección de productos para devolución
     */
    public function processProductSelection(Request $request, $returnId)
    {
        $request->validate([
            'selected_products' => 'required|array|min:1',
            'selected_products.*.product_id' => 'required|exists:return_order_products,id',
            'selected_products.*.quantity' => 'required|numeric|min:0.01',
            'selected_products.*.reason_id' => 'required|exists:return_reasons,reason_id',
            'selected_products.*.condition' => 'required|in:new,good,fair,poor,damaged',
            'selected_products.*.notes' => 'nullable|string|max:500',
            'selected_products.*.replacement_requested' => 'boolean'
        ]);

        try {
            DB::transaction(function () use ($request, $returnId) {
                $returnRequest = ReturnRequest::findOrFail($returnId);

                // Eliminar productos previamente seleccionados
                $returnRequest->products()->delete();

                // Crear nuevos productos seleccionados
                ReturnRequestProduct::createFromSelection($returnId, $request->selected_products);

                // Actualizar totales de la devolución
                $returnRequest->updateTotals();

                Log::info('Products selected for return', [
                    'return_id' => $returnId,
                    'products_count' => count($request->selected_products),
                    'total_amount' => $returnRequest->fresh()->total_amount
                ]);
            });

            return redirect()
                ->route('callcenters.returns.review', $returnId)
                ->with('success', 'Productos seleccionados correctamente');

        } catch (\Exception $e) {
            Log::error('Error processing product selection', [
                'return_id' => $returnId,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al procesar la selección: ' . $e->getMessage());
        }
    }

    /**
     * Revisar devolución antes de confirmar
     */
    public function review($returnId)
    {
        $returnRequest = ReturnRequest::with([
            'order',
            'products.orderProduct',
            'products.returnReason',
            'status',
            'returnType'
        ])->findOrFail($returnId);

        if ($returnRequest->products->isEmpty()) {
            return redirect()
                ->route('callcenters.returns.validate', $returnId)
                ->with('warning', 'Debe seleccionar al menos un producto para devolver');
        }

        return view('callcenters.views.returns.review')->with([
            'return' => $returnRequest,
            'totalProducts' => $returnRequest->getTotalProductsQuantity(),
            'totalAmount' => $returnRequest->total_amount
        ]);
    }

    /**
     * Confirmar y finalizar devolución
     */
    public function confirm(Request $request, $returnId)
    {
        $request->validate([
            'final_notes' => 'nullable|string|max:1000',
            'logistics_mode' => 'required|in:customer_transport,home_pickup,store_delivery,inpost',
            'return_address' => 'required_if:logistics_mode,home_pickup|nullable|string|max:500'
        ]);

        try {
            DB::transaction(function () use ($request, $returnId) {
                $returnRequest = ReturnRequest::findOrFail($returnId);

                // Actualizar información final
                $returnRequest->update([
                    'description' => $request->final_notes ?? $returnRequest->description,
                    'logistics_mode' => $request->logistics_mode,
                    'return_address' => $request->return_address,
                    'status_id' => config('returns.default_status_id', 1)
                ]);

                // Disparar evento de creación (esto activará PDF, emails, etc.)
                $this->returnService->triggerReturnCreatedEvent($returnRequest);

                Log::info('Return request confirmed', [
                    'return_id' => $returnId,
                    'total_products' => $returnRequest->getTotalProductsQuantity(),
                    'total_amount' => $returnRequest->total_amount,
                    'logistics_mode' => $request->logistics_mode
                ]);
            });

            return redirect()
                ->route('callcenters.returns.success', $returnId)
                ->with('success', 'Devolución creada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error confirming return request', [
                'return_id' => $returnId,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al confirmar la devolución: ' . $e->getMessage());
        }
    }

    /**
     * Página de éxito
     */
    public function success($returnId)
    {
        $returnRequest = ReturnRequest::with(['order', 'products', 'status'])
            ->findOrFail($returnId);

        return view('callcenters.views.returns.success')->with([
            'return' => $returnRequest
        ]);
    }

    /**
     * Obtener productos disponibles para devolución via AJAX
     */
    public function getAvailableProducts($orderId)
    {
        try {
            $products = ReturnOrderProduct::getReturnableByOrder($orderId);

            return response()->json([
                'success' => true,
                'products' => $products->map(function($product) {
                    return $product->getDisplayInfo();
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($uid)
    {
        $return = ReturnRequest::where('uid', $uid)->firstOrFail();
        $return->delete();

        return redirect()->route('callcenters.views.returns')->with('success', 'Devolución eliminada correctamente.');
    }

    public function show($uid)
    {
        $return = ReturnRequest::where('uid', $uid)
            ->with(['status.state', 'returnType', 'returnReason', 'discussions', 'history'])
            ->firstOrFail();

        return view('callcenters.views.returns.show', compact('return'));
    }









    /**
     * Obtener productos que se pueden devolver
     */
    private function getReturnableProducts($order)
    {
        $products = [];

        foreach ($order->products as $orderProduct) {
            $alreadyReturned = ReturnOrderProduct::getTotalReturnedQuantity($order->id, $orderProduct->product_id);
            $availableToReturn = $orderProduct->quantity - $alreadyReturned;

            if ($availableToReturn > 0) {
                $products[] = [
                    'product_id' => $orderProduct->product_id,
                    'product' => $orderProduct->product,
                    'name' => $orderProduct->product_name,
                    'description' => $orderProduct->product_description,
                    'ordered_quantity' => $orderProduct->quantity,
                    'already_returned' => $alreadyReturned,
                    'available_to_return' => $availableToReturn,
                    'unit_price' => $orderProduct->unit_price,
                    'total_price' => $orderProduct->total_price
                ];
            }
        }

        return $products;
    }

    /**
     * Validar productos seleccionados para devolución (AJAX)
     */
    public function validateProducts(Request $request)
    {
        $request->validate([
            'return_id' => 'required|exists:return_requests,id_return_request',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.reason' => 'required|string',
            'products.*.condition' => 'required|string'
        ]);

        $returnRequest = ReturnRequest::findOrFail($request->return_id);
        $order = $returnRequest->order;
        $errors = [];
        $validProducts = [];

        foreach ($request->products as $productData) {
            // Validar que el producto pertenece a la orden
            $orderProduct = $order->orderProducts()
                ->where('product_id', $productData['product_id'])
                ->first();

            if (!$orderProduct) {
                $errors[] = [
                    'product_id' => $productData['product_id'],
                    'message' => 'El producto no pertenece a esta orden'
                ];
                continue;
            }

            // Validar cantidad disponible
            $alreadyReturned = ReturnRequestProduct::getTotalReturnedQuantity($order->id, $productData['product_id']);
            $availableToReturn = $orderProduct->quantity - $alreadyReturned;

            if ($productData['quantity'] > $availableToReturn) {
                $errors[] = [
                    'product_id' => $productData['product_id'],
                    'message' => "Cantidad excede lo disponible. Máximo: {$availableToReturn}"
                ];
                continue;
            }

            $validProducts[] = [
                'product_id' => $productData['product_id'],
                'product_name' => $orderProduct->product->name,
                'quantity' => $productData['quantity'],
                'unit_price' => $orderProduct->unit_price,
                'total' => $productData['quantity'] * $orderProduct->unit_price,
                'reason' => $productData['reason'],
                'condition' => $productData['condition']
            ];
        }

        return response()->json([
            'success' => empty($errors),
            'errors' => $errors,
            'valid_products' => $validProducts,
            'total_refund' => collect($validProducts)->sum('total')
        ]);
    }

    /**
     * Guardar solicitud de devolución
     */
    public function store(Request $request)
    {
        $request->validate([
            'return_id' => 'required|exists:return_requests,id_return_request',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.reason' => 'required|string',
            'products.*.condition' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $returnRequest = ReturnRequest::findOrFail($request->return_id);

            // Limpiar items existentes si los hay
            $returnRequest->returnItems()->delete();

            // Crear nuevos items
            foreach ($request->products as $productData) {
                $orderProduct = $returnRequest->order->orderProducts()
                    ->where('product_id', $productData['product_id'])
                    ->first();

                ReturnItem::create([
                    'return_request_id' => $returnRequest->id_return_request,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $orderProduct->unit_price,
                    'reason' => $productData['reason'],
                    'condition' => $productData['condition'],
                    'notes' => $productData['notes'] ?? null
                ]);
            }

            // Actualizar descripción si hay notas
            if ($request->filled('notes')) {
                $returnRequest->update(['description' => $request->notes]);
            }

            // Calcular total
            $returnRequest->calculateTotal();

            // Validar productos una vez más
            $validationErrors = $returnRequest->validateReturnedProducts();

            if (!empty($validationErrors)) {
                throw new \Exception('Errores de validación: ' . implode(', ', $validationErrors));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de devolución creada exitosamente',
                'return_id' => $returnRequest->id_return_request,
                'total_amount' => $returnRequest->total_amount
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
     * Editar solicitud de devolución existente
     */
    public function edit($id)
    {
        $returnRequest = ReturnRequest::with(['order', 'customer', 'returnItems.product'])
            ->findOrFail($id);

        // Verificar que se pueda editar
        if (!in_array($returnRequest->status, ['pending', 'draft'])) {
            return back()->with('error', 'Esta solicitud ya no se puede editar');
        }

        $validation = $returnRequest->validateOrderEligibility();
        $returnableProducts = $this->getReturnableProducts($returnRequest->order);

        // Preparar items actuales para edición
        $currentItems = $returnRequest->returnItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'reason' => $item->reason,
                'condition' => $item->condition,
                'notes' => $item->notes
            ];
        })->toArray();

        return view('callcenters.views.returns.edit')->with([
            'return' => $returnRequest,
            'customer' => $returnRequest->customer,
            'order' => $returnRequest->order,
            'validation' => $validation,
            'returnableProducts' => $returnableProducts,
            'currentItems' => $currentItems,
            'returnReasons' => $this->getReturnReasons(),
            'returnConditions' => $this->getReturnConditions()
        ]);
    }

    /**
     * Actualizar solicitud de devolución
     */
    public function update(Request $request, $id)
    {
        // Similar a store() pero actualizando la solicitud existente
        $returnRequest = ReturnRequest::findOrFail($id);

        if (!in_array($returnRequest->status, ['pending', 'draft'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud ya no se puede editar'
            ], 403);
        }

        // El resto es similar al método store()
        // ... (implementar lógica de actualización)
    }

    /**
     * Obtener razones de devolución
     */
    private function getReturnReasons()
    {
        return [
            'defective' => 'Producto defectuoso',
            'wrong_product' => 'Producto incorrecto',
            'damaged' => 'Producto dañado',
            'not_as_described' => 'No coincide con la descripción',
            'changed_mind' => 'Cambio de opinión',
            'other' => 'Otra razón'
        ];
    }

    /**
     * Obtener condiciones del producto
     */
    private function getReturnConditions()
    {
        return [
            'unopened' => 'Sin abrir',
            'opened_unused' => 'Abierto pero sin usar',
            'used' => 'Usado',
            'damaged' => 'Dañado'
        ];
    }

}
