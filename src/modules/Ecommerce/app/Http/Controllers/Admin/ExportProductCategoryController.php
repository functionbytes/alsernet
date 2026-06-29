<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Exports\ProductCategoryExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportProductCategoryController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ProductCategoryExport,
            'categorias-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
