<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Imports\ProductImport;

class ImportProductController extends Controller
{
    public function showForm(): View
    {
        return view('ecommerce::products.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'El archivo es obligatorio.',
            'file.mimes' => 'El archivo debe ser xlsx, xls o csv.',
            'file.max' => 'El archivo no puede superar 10MB.',
        ]);

        Excel::import(new ProductImport, $request->file('file'));

        return redirect()->route('ecommerce.products.index')->with('success', 'Productos importados exitosamente.');
    }
}
