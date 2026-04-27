<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Notifications\OrderRecoveryNotification;

class IncompleteOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->whereNotNull('token')
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->with('customer')
            ->when($request->input('search'), fn ($q, $s) => $q->where('code', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return view('ecommerce::incomplete-orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'customer', 'shippingAddress', 'referral']);

        return view('ecommerce::incomplete-orders.show', compact('order'));
    }

    public function markComplete(Order $order): RedirectResponse
    {
        $order->update(['payment_status' => 'paid']);

        return redirect()->route('ecommerce.incomplete-orders.index')
            ->with('success', 'Orden marcada como completada.');
    }

    public function saveNote(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update(['admin_note' => $request->admin_note]);

        return back()->with('success', 'Nota guardada.');
    }

    public function sendRecoveryEmail(Order $order): RedirectResponse
    {
        if (! $order->token) {
            return back()->with('error', 'Esta orden no tiene enlace de recupero.');
        }

        $email = $order->customer?->email ?? $order->shippingAddress?->email;

        if (! $email) {
            return back()->with('error', 'No se encontró un correo electrónico para este cliente.');
        }

        $order->load('items');

        $name = $order->customer?->name ?? $order->shippingAddress?->name ?? 'Cliente';

        Notification::route('mail', [$email => $name])
            ->notify(new OrderRecoveryNotification($order));

        return back()->with('success', "Correo de recupero enviado a {$email}.");
    }
}
