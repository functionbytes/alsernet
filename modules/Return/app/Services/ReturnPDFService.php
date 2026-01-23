<?php

namespace Modules\Returns\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Modules\Returns\Models\ReturnRequest;

class ReturnPDFService
{
    /**
     * Generar PDF de devolución
     */
    public function generateReturnPDF(ReturnRequest $return): string
    {
        $returnService = app(ReturnService::class);
        $data = $returnService->getReturnDataForPDF($return->id_return_request);

        return Pdf::loadView('pdfs.return-form', $data)
            ->setPaper('a4')
            ->stream();
    }

    /**
     * Generar y guardar PDF de devolución
     */
    public function generateAndSaveReturnPDF(ReturnRequest $return): string
    {
        $pdf = $this->generateReturnPDF($return);
        $filename = 'returns/devolucion-'.$return->id_return_request.'.pdf';

        Storage::disk('public')->put($filename, $pdf);

        return $filename;
    }
}
