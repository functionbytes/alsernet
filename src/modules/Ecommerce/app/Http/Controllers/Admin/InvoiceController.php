<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Invoice;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with('reference')
            ->when($request->input('search'), fn ($q, $s) => $q->where('customer_name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
            )
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        $counts = Invoice::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('ecommerce::invoices.index', compact('invoices', 'counts'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['items', 'reference']);

        return view('ecommerce::invoices.show', compact('invoice'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->items()->delete();
        $invoice->delete();

        return redirect()->route('ecommerce.invoices.index')->with('success', 'Factura eliminada.');
    }

    public function download(Invoice $invoice): Response
    {
        $invoice->load(['items', 'reference']);

        $pdf = Pdf::loadView('ecommerce::invoices.pdf', compact('invoice'));
        $pdf->setPaper('a4');

        return $pdf->stream('factura-'.($invoice->code ?? $invoice->id).'.pdf');
    }
}
