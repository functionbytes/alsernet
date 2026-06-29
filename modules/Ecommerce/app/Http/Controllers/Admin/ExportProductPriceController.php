<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Exports\ProductPriceExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportProductPriceController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return Excel::download(
            new ProductPriceExport,
            'precios-productos-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
