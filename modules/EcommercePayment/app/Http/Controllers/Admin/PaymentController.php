<?php

namespace Modules\EcommercePayment\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Exports\PaymentsExport;
use Modules\EcommercePayment\Models\Payment;
use Modules\EcommercePayment\Services\PaymentGatewayManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    private const MANUAL_CHANNELS = ['cod', 'bank_transfer'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query()->with('order');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_channel')) {
            $query->where('payment_channel', $request->input('payment_channel'));
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
        $channels = Payment::query()->distinct()->pluck('payment_channel')->filter()->values()->toArray();

        return view('ecommerce-payment::admin.payments.index', compact('payments', 'statuses', 'channels'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['order.items', 'logs']);

        return view('ecommerce-payment::admin.payments.show', compact('payment'));
    }

    public function confirmPayment(Payment $payment): RedirectResponse
    {
        $this->authorize('confirm', $payment);

        if (! in_array($payment->payment_channel, self::MANUAL_CHANNELS, true)) {
            return redirect()->route('ecommerce-payment.payments.show', $payment)
                ->with('error', 'Solo se pueden confirmar pagos manuales (COD o transferencia).');
        }

        if ($payment->status !== PaymentStatus::PENDING) {
            return redirect()->route('ecommerce-payment.payments.show', $payment)
                ->with('error', 'Solo se pueden confirmar pagos en estado pendiente.');
        }

        $payment->update(['status' => PaymentStatus::COMPLETED]);

        if ($payment->order) {
            $payment->order->update(['payment_status' => PaymentStatus::COMPLETED->value]);
        }

        return redirect()->route('ecommerce-payment.payments.show', $payment)
            ->with('success', 'Pago confirmado exitosamente.');
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('refund', $payment);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $manager = app(PaymentGatewayManager::class);
        $channel = $payment->payment_channel;

        if (! $manager->has($channel)) {
            return redirect()->route('ecommerce-payment.payments.show', $payment)
                ->with('error', "El gateway '{$channel}' no está registrado.");
        }

        $gateway = $manager->get($channel);
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

        $filters = $request->only(['status', 'search', 'date_from', 'date_to', 'payment_channel']);
        $fileName = 'pagos_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new PaymentsExport($filters), $fileName);
    }
}
