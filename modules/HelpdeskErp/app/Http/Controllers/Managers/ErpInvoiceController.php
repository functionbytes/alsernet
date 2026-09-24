<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Http\Controllers\Concerns\ScopesErpCustomerAccess;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatCustomerResolver;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatSections;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;
use Modules\HelpdeskErp\Services\ErpInvoice\ErpInvoiceDocument;
use Modules\HelpdeskErp\Services\ErpInvoice\ErpInvoiceMonthlyService;
use Modules\HelpdeskErp\Services\ErpInvoice\ErpInvoicePdfRenderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Finanzas de Gestión en el chat (extensión "invoice") — SOLO LECTURA.
 *
 *  - pdf:     copia informativa de una factura (DomPDF) a partir de
 *             ErpChatService::invoiceDetail. La fiscal la emite Gestión.
 *             Deja rastro en activity log (quién, cliente, factura).
 *  - monthly: serie facturado vs cobrado por mes para el Balance.
 *
 * Mismas reglas que ErpChatController: helpdeskerp.view + finance, cliente
 * dentro de las bandejas del agente, integración activa y vínculo ERP.
 */
class ErpInvoiceController extends Controller
{
    use ScopesErpCustomerAccess;

    public function __construct(
        private readonly ErpChatService $service,
        private readonly ErpChatCustomerResolver $resolver,
        private readonly ErpInvoicePdfRenderer $renderer,
        private readonly ErpInvoiceMonthlyService $monthly,
    ) {}

    public function pdf(Request $request, int $customer, int $invoiceId): Response
    {
        $resolved = $this->resolve($request, $customer);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$model, $erpId] = $resolved;

        $result = $this->service->invoiceDetail($erpId, $invoiceId, $request->boolean('force'));

        if ($result['state'] === 'unavailable') {
            return response()->json([
                'success' => false,
                'state' => 'unavailable',
                'reason' => $result['reason'] ?? null,
                'message' => 'Factura no encontrada para este cliente.',
            ], 404);
        }

        if ($result['state'] === 'blocked') {
            return response()->json([
                'success' => false,
                'state' => 'blocked',
                'reason' => $result['reason'] ?? 'grant',
                'message' => 'La copia en PDF estará disponible cuando Oracle conceda el permiso de lectura de facturas.',
            ], 409);
        }

        if ($result['state'] !== 'ok' || ! is_array($result['data'] ?? null)) {
            return response()->json([
                'success' => false,
                'state' => $result['state'],
                'reason' => $result['reason'] ?? null,
                'message' => $result['message'] ?? 'Gestión no responde ahora mismo.',
            ], 503);
        }

        $user = $request->user();

        try {
            $pdf = $this->renderer->render($result['data'], $user?->name);
        } catch (\Throwable $e) {
            Log::error('HelpdeskErp: no se pudo generar la copia de factura.', [
                'erp_id' => $erpId, 'invoice_id' => $invoiceId, 'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'state' => 'down', 'message' => 'No se pudo generar el PDF de la factura.'], 500);
        }

        $this->audit($request, $model, $erpId, $invoiceId, $result['data'], $pdf['document']);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['filename'].'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function monthly(Request $request, int $customer): JsonResponse
    {
        // JSON para ErpChat.request: "sin vínculo" o "desactivada" van con 200
        // y su state (como en ErpChatController), no como error HTTP.
        $resolved = $this->resolve($request, $customer, 200);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [, $erpId] = $resolved;

        $out = $this->monthly->build($erpId, $request->boolean('force'));

        return response()->json(['success' => true] + $out + ['fetched_at' => now()->toIso8601String()]);
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    /**
     * El PDF responde 409 (JSON) si no hay vínculo o la integración está
     * apagada: el botón lo explica sin abrir una pestaña con un error.
     *
     * @return array{0: Customer, 1: int}|JsonResponse
     */
    private function resolve(Request $request, int $customerId, int $softStatus = 409): array|JsonResponse
    {
        $user = $request->user();

        if (! $user?->can(ErpChatSections::PERM_VIEW) || ! $user->can(ErpChatSections::PERM_FINANCE)) {
            return response()->json(['success' => false, 'message' => 'Sin permiso para ver las finanzas de Gestión.'], 403);
        }

        $customer = Customer::find($customerId);

        if ($customer === null) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        $this->assertErpCustomerAccess($customer);

        if (function_exists('helpdesk_erp_enabled') && ! helpdesk_erp_enabled()) {
            return response()->json([
                'success' => false,
                'state' => 'unavailable',
                'reason' => 'disabled',
                'message' => 'La integración con Gestión está desactivada.',
            ], $softStatus);
        }

        $erpId = $this->resolver->erpIdFor($customer);

        if ($erpId === null) {
            return response()->json([
                'success' => false,
                'state' => 'unlinked',
                'message' => 'Este cliente no está vinculado con Gestión.',
                'lookup_status' => $customer->erp_lookup_status,
            ], $softStatus);
        }

        return [$customer, $erpId];
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $doc
     */
    private function audit(Request $request, Customer $customer, int $erpId, int $invoiceId, array $detail, array $doc): void
    {
        if (! function_exists('activity')) {
            return;
        }

        try {
            activity('helpdeskerp')
                ->causedBy($request->user())
                ->performedOn($customer)
                ->event('invoice_pdf_downloaded')
                ->withProperties([
                    'erp_customer_id' => $erpId,
                    'invoice_id' => $invoiceId,
                    'invoice_ref' => ErpInvoiceDocument::ref($detail),
                    'invoice_date' => $detail['date'] ?? null,
                    'total' => $doc['totals']['total'] ?? null,
                    'ip' => $request->ip(),
                ])
                ->log('Descargó la copia informativa de la factura '.ErpInvoiceDocument::ref($detail));
        } catch (\Throwable $e) {
            // El registro nunca debe impedir la descarga.
            Log::warning('HelpdeskErp: no se pudo registrar la descarga de la factura.', ['error' => $e->getMessage()]);
        }
    }
}
