<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\Imports\ProductInventoryImport;

class ImportProductInventoryController extends Controller
{
    public function create(): View
    {
        return view('ecommerce::products.import-inventory');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'El archivo es obligatorio.',
            'file.mimes' => 'El archivo debe ser xlsx, xls o csv.',
            'file.max' => 'El archivo no puede superar 10MB.',
        ]);

        Excel::import(new ProductInventoryImport, $request->file('file'));

        return redirect()->route('ecommerce.inventory.index')->with('success', 'Inventario actualizado exitosamente.');
    }
}
