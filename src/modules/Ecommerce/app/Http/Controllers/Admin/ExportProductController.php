<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Exports\ProductExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportProductController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ProductExport($request->only(['status', 'category_id', 'brand_id'])),
            'products-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
