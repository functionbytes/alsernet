<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplierAutomationLogController extends Controller
{
    /**
     * Show the logs page
     */
    public function index(): View
    {
        return view('supplier::settings.views.automation.logs');
    }

    /**
     * Get system logs (AJAX)
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'error');
            $limit = $request->input('limit', 100);
            $logFile = storage_path('logs/laravel.log');

            if (! file_exists($logFile)) {
                return response()->json([
                    'success' => true,
                    'logs' => [],
                    'message' => 'No hay logs disponibles',
                ]);
            }

            $logs = $this->parseLogFile($logFile);
            $counts = $this->countByLevel($logs);

            if ($type !== 'all') {
                $logs = array_values(array_filter($logs, fn ($log) => $log['level'] === $type));
            }

            $logs = array_slice(array_reverse($logs), 0, $limit);

            return response()->json([
                'success' => true,
                'logs' => array_values($logs),
                'total' => count($logs),
                'counts' => $counts,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting logs: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs',
                'logs' => [],
            ], 500);
        }
    }

    /**
     * Download log file
     */
    public function download(Request $request): RedirectResponse|BinaryFileResponse
    {
        try {
            $type = $request->input('type', 'all');
            $logFile = storage_path('logs/laravel.log');

            if (! file_exists($logFile)) {
                return back()->with('error', 'No hay logs disponibles para descargar');
            }

            $filename = 'automation-logs-'.date('Y-m-d-His').'.log';

            if ($type === 'all') {
                return response()->download($logFile, $filename);
            }

            $logContent = file_get_contents($logFile);
            $filteredLines = array_filter(
                explode("\n", $logContent),
                fn ($line) => stripos($line, '.'.$type.':') !== false || empty(trim($line))
            );

            return response(implode("\n", $filteredLines), 200)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\Exception $e) {
            Log::error('Error downloading logs: '.$e->getMessage());

            return back()->with('error', 'Error al descargar los logs: '.$e->getMessage());
        }
    }

    /**
     * @return array<int, array{timestamp: string, level: string, message: string}>
     */
    private function parseLogFile(string $logFile): array
    {
        $lines = explode("\n", file_get_contents($logFile));
        $logs = [];
        $currentEntry = null;

        foreach ($lines as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)/', $line, $matches)) {
                if ($currentEntry) {
                    $logs[] = $currentEntry;
                }
                $currentEntry = [
                    'timestamp' => $matches[1],
                    'level' => strtolower($matches[2]),
                    'message' => $matches[3],
                ];
            } elseif ($currentEntry && trim($line)) {
                $currentEntry['message'] .= "\n".$line;
            }
        }

        if ($currentEntry) {
            $logs[] = $currentEntry;
        }

        return $logs;
    }

    /**
     * @param  array<int, array{level: string}>  $logs
     * @return array{error: int, warning: int, info: int, total: int}
     */
    private function countByLevel(array $logs): array
    {
        return [
            'error' => count(array_filter($logs, fn ($log) => $log['level'] === 'error')),
            'warning' => count(array_filter($logs, fn ($log) => $log['level'] === 'warning')),
            'info' => count(array_filter($logs, fn ($log) => $log['level'] === 'info')),
            'total' => count($logs),
        ];
    }
}
