<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Exports\ProductInventoryExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportProductInventoryController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return Excel::download(
            new ProductInventoryExport,
            'inventario-productos-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
