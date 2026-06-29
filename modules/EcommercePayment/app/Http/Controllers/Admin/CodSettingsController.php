<?php

namespace Modules\EcommercePayment\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class CodSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'status' => Setting::get('ecommerce_payment.cod.status', '0'),
            'name' => Setting::get('ecommerce_payment.cod.name', 'Pago contra entrega'),
            'description' => Setting::get('ecommerce_payment.cod.description', 'Paga al momento de recibir tu pedido.'),
            'instructions' => Setting::get('ecommerce_payment.cod.instructions', 'Nuestro repartidor cobrará el valor de tu pedido al momento de la entrega.'),
            'fee_type' => Setting::get('ecommerce_payment.cod.fee_type', 'none'),
            'fee_value' => Setting::get('ecommerce_payment.cod.fee_value', '0'),
        ];

        return view('ecommerce-payment::settings.cod', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:0,1'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'fee_type' => ['required', 'in:none,fixed,percentage'],
            'fee_value' => ['required_unless:fee_type,none', 'numeric', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set("ecommerce_payment.cod.{$key}", $value ?? '');
        }

        return redirect()->route('ecommerce-payment.cod.settings')
            ->with('success', 'Configuración de pago contra entrega actualizada.');
    }
}
