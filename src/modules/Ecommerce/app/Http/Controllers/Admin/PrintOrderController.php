<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Ecommerce\Models\Order;

class PrintOrderController extends Controller
{
    public function __invoke(Order $order): Response
    {
        $order->load(['customer', 'items', 'shipments']);

        $pdf = Pdf::loadView('ecommerce::orders.pdf', compact('order'))
            ->setPaper('a4');

        return $pdf->stream('orden-'.$order->code.'.pdf');
    }
}
