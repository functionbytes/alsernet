<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Invoice;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::query()
            ->with('reference')
            ->latest()
            ->paginate(20);

        return view('ecommerce::admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        return view('ecommerce::admin.invoices.show', compact('invoice'));
    }
}
