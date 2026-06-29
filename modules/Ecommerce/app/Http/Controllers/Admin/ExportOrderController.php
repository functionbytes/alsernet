<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Exports\OrderExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportOrderController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new OrderExport($request->only(['status', 'payment_status', 'start_date', 'end_date'])),
            'orders-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
