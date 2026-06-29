<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Http\Requests\Managers\StoreContactTicketRequest;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskContacts\Services\ContactChatsService;
use Throwable;

class ContactTabsController extends Controller
{
    public function __construct(
        private readonly ContactAggregatorService $aggregator,
        private readonly ContactChatsService $chats,
    ) {}

    public function resumen(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->resumen($customer));
    }

    public function conversaciones(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->conversaciones($customer));
    }

    public function chats(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->chats->forCustomer($customer));
    }

    public function erp(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->erp($customer));
    }

    public function prestashop(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->prestashop($customer));
    }

    public function tienda(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->tienda($customer));
    }

    public function actividad(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->actividad($customer));
    }

    public function tickets(Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->ticketsTab($customer));
    }

    /**
     * Create a ticket for this customer through the tickets bridge.
     * Gated by the route middleware (can:contacts.update).
     */
    public function createTicket(StoreContactTicketRequest $request, Customer $customer): JsonResponse
    {
        try {
            $ticket = $this->aggregator->createTicket($customer, $request->validated());
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el ticket.',
            ], 422);
        }

        if ($ticket === null) {
            return response()->json([
                'success' => false,
                'message' => 'El módulo de tickets no está disponible.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket creado correctamente.',
            'ticket' => $ticket,
        ], 201);
    }

    /**
     * Re-link external integration IDs (ERP, PrestaShop, ...) for this customer.
     * Gated by the route middleware (can:contacts.update).
     */
    public function sync(Request $request, Customer $customer): JsonResponse
    {
        return $this->tab(fn () => $this->aggregator->syncIntegrations($customer));
    }

    /**
     * Wrap a tab aggregation in the standard JSON envelope.
     * A failing tab degrades to an empty, unavailable section instead of a 500.
     *
     * @param  callable(): array<string, mixed>  $resolver
     */
    private function tab(callable $resolver): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $resolver()]);
        } catch (Throwable) {
            return response()->json(['success' => true, 'data' => ['available' => false]]);
        }
    }
}
