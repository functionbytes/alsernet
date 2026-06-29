<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Ecommerce\Models\Shipment;

class PrintShippingLabelController extends Controller
{
    public function __invoke(Shipment $shipment): Response
    {
        $shipment->load(['order.customer', 'order.items']);

        $pdf = Pdf::loadView('ecommerce::shipments.label', compact('shipment'))
            ->setPaper([0, 0, 283.46, 425.20], 'portrait'); // 10x15cm

        return $pdf->stream('etiqueta-envio-'.($shipment->shipment_id ?? $shipment->id).'.pdf');
    }
}
