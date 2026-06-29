<?php

namespace Modules\EcommercePayment\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class BankTransferSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'status' => Setting::get('ecommerce_payment.bank_transfer.status', '0'),
            'name' => Setting::get('ecommerce_payment.bank_transfer.name', 'Transferencia bancaria'),
            'description' => Setting::get('ecommerce_payment.bank_transfer.description', 'Realiza una transferencia bancaria para completar tu pago.'),
            'bank_name' => Setting::get('ecommerce_payment.bank_transfer.bank_name', ''),
            'account_type' => Setting::get('ecommerce_payment.bank_transfer.account_type', 'Ahorros'),
            'account_number' => Setting::get('ecommerce_payment.bank_transfer.account_number', ''),
            'account_holder' => Setting::get('ecommerce_payment.bank_transfer.account_holder', ''),
            'document_type' => Setting::get('ecommerce_payment.bank_transfer.document_type', 'NIT'),
            'document_number' => Setting::get('ecommerce_payment.bank_transfer.document_number', ''),
            'instructions' => Setting::get('ecommerce_payment.bank_transfer.instructions', 'Realiza la transferencia y envíanos el comprobante por WhatsApp o email para confirmar tu pedido.'),
        ];

        return view('ecommerce-payment::settings.bank-transfer', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:0,1'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'account_type' => ['nullable', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:150'],
            'document_type' => ['nullable', 'string', 'max:20'],
            'document_number' => ['nullable', 'string', 'max:30'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set("ecommerce_payment.bank_transfer.{$key}", $value ?? '');
        }

        return redirect()->route('ecommerce-payment.bank-transfer.settings')
            ->with('success', 'Configuración de transferencia bancaria actualizada.');
    }
}
