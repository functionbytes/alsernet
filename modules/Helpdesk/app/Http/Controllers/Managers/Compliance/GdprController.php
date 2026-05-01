<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Compliance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Compliance\GdprDeletionService;
use Modules\Helpdesk\Services\Compliance\GdprExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GdprController extends Controller
{
    public function __construct(
        private readonly GdprExportService $exportService,
        private readonly GdprDeletionService $deletionService,
    ) {}

    /**
     * Stream a ZIP export of all data for the given customer.
     */
    public function export(Customer $customer): BinaryFileResponse
    {
        $this->authorize('manage', Customer::class);

        $zipPath = $this->exportService->exportToZip($customer);

        return response()
            ->download($zipPath, 'gdpr_export_customer_'.$customer->id.'.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * Anonymise or hard-delete the customer on GDPR request.
     */
    public function delete(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('manage', Customer::class);

        $request->validate([
            'confirmation' => ['required', 'string', 'in:CONFIRMAR'],
            'hard' => ['sometimes', 'boolean'],
        ]);

        $hard = (bool) $request->boolean('hard', false);

        $result = $this->deletionService->deleteCustomer($customer, $hard);

        return response()->json([
            'success' => true,
            'message' => $hard
                ? 'Cliente eliminado permanentemente según solicitud GDPR.'
                : 'PII del cliente anonimizada. Los datos serán eliminados definitivamente en 90 días.',
            'data' => $result,
        ]);
    }
}
