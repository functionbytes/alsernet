<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Reviews\Services\ReviewReportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewReportController extends Controller
{
    public function __construct(
        private readonly ReviewReportService $reportService,
    ) {}

    public function downloadMonthly(Request $request): BinaryFileResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $user = $request->user();
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        $relativePath = $this->reportService->generateMonthlyPdf($user, $year, $month);
        $absolutePath = Storage::disk('local')->path($relativePath);

        $filename = sprintf('reporte-mensual-%d-%02d.pdf', $year, $month);

        return response()->download($absolutePath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
