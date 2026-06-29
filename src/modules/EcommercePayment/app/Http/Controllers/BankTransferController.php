<?php

namespace Modules\EcommercePayment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Order;

class BankTransferController extends Controller
{
    public function instructions(Order $order): View
    {
        if (! request()->hasValidSignature()) {
            abort(403, 'Enlace inválido o expirado.');
        }

        $bankDetails = [
            'bank_name' => Setting::get('ecommerce_payment.bank_transfer.bank_name', ''),
            'account_type' => Setting::get('ecommerce_payment.bank_transfer.account_type', 'Ahorros'),
            'account_number' => Setting::get('ecommerce_payment.bank_transfer.account_number', ''),
            'account_holder' => Setting::get('ecommerce_payment.bank_transfer.account_holder', ''),
            'document_type' => Setting::get('ecommerce_payment.bank_transfer.document_type', 'NIT'),
            'document_number' => Setting::get('ecommerce_payment.bank_transfer.document_number', ''),
            'instructions' => Setting::get('ecommerce_payment.bank_transfer.instructions', ''),
        ];

        $order->load('items');

        return view('ecommerce-payment::confirmation.bank-transfer', compact('order', 'bankDetails'));
    }
}
