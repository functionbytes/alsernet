<?php

namespace Modules\EcommercePayment\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Exports\PaymentsExport;
use Modules\EcommercePayment\Models\Payment;
use Modules\EcommercePayment\Services\WompiGateway;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query()->with('order');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('charge_id', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('code', 'like', "%{$search}%")
                            ->orWhere('token', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $payments = $query->latest()->paginate(20);
        $statuses = PaymentStatus::cases();

        return view('ecommerce-payment::admin.payments.index', compact('payments', 'statuses'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['order.items', 'logs']);

        return view('ecommerce-payment::admin.payments.show', compact('payment'));
    }

    public function refund(Request $request, Payment $payment)
    {
        $this->authorize('refund', $payment);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $gateway = new WompiGateway;
        $result = $gateway->refund(
            $payment,
            $validated['amount'] ?? null,
            $validated['reason'] ?? ''
        );

        if ($result['success']) {
            return redirect()->route('ecommerce-payment.payments.show', $payment)
                ->with('success', $result['message']);
        }

        return redirect()->route('ecommerce-payment.payments.show', $payment)
            ->with('error', $result['message']);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->only(['status', 'search', 'date_from', 'date_to']);
        $fileName = 'pagos_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new PaymentsExport($filters), $fileName);
    }
}
