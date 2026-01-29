<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Invoice;

class InvoiceController extends Controller
{
    public function download(Request $request)
    {
        $invoice = Invoice::findByUid($request->uid);

        return \Response::make($invoice->exportToPdf(), 200, [
            'Content-type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$invoice->uid.'.pdf"',
        ]);
    }

    public function templateEdit(Request $request)
    {
        if ($request->isMethod('post')) {
            \Modules\Mailing\Models\Setting::set('invoice.custom_template', $request->content);
        }

        return view('admin.invoices.templateEdit');
    }
}
