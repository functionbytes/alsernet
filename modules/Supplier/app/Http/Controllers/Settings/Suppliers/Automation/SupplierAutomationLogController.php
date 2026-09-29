<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Supplier\Listeners\MirrorSupplierLogsListener;

class SupplierAutomationLogController extends Controller
{
    /** Días de ficheros diarios del canal propio que se muestran/descargan. */
    private const DAYS = 3;

    /**
     * 29-sep-2026: el visor lee SOLO el log propio del módulo
     * (MirrorSupplierLogsListener), nunca storage/logs/laravel.log, y exige el
     * permiso de gestión de automatizaciones además del de verlas.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('can:suppliers.automation.manage');
    }

    /**
     * Show the logs page
     */
    public function index(): View
    {
        return view('supplier::settings.views.automation.logs');
    }

    /**
     * Contenido de los últimos ficheros diarios del canal del módulo, del más
     * antiguo al más reciente. Cadena vacía si no hay ninguno.
     */
    private function moduleLogContent(): string
    {
        $path = (string) config(
            'logging.channels.'.MirrorSupplierLogsListener::CHANNEL.'.path',
            storage_path('logs/supplier-automation.log')
        );
        $pattern = preg_replace('/\.log$/', '', $path).'-*.log';

        $files = glob($pattern) ?: [];
        sort($files);
        $files = array_slice($files, -self::DAYS);

        $content = '';
        foreach ($files as $file) {
            if (is_file($file) && is_readable($file)) {
                $content .= file_get_contents($file)."\n";
            }
        }

        return $content;
    }

    /**
     * Get system logs (AJAX)
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $type = (string) $request->input('type', 'error');
            $limit = max(1, min(1000, (int) $request->input('limit', 100)));
            $logContent = $this->moduleLogContent();

            if (trim($logContent) === '') {
                return response()->json([
                    'success' => true,
                    'logs' => [],
                    'message' => 'No hay logs disponibles',
                ]);
            }

            $logs = $this->parseLogContent($logContent);
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
    public function download(Request $request): RedirectResponse|Response
    {
        try {
            $type = (string) $request->input('type', 'all');
            $logContent = $this->moduleLogContent();

            if (trim($logContent) === '') {
                return back()->with('error', 'No hay logs disponibles para descargar');
            }

            $filename = 'automation-logs-'.date('Y-m-d-His').'.log';

            $lines = explode("\n", $logContent);

            if ($type !== 'all') {
                $lines = array_filter(
                    $lines,
                    fn ($line) => stripos($line, '.'.$type.':') !== false || empty(trim($line))
                );
            }

            return response(implode("\n", $lines), 200)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\Exception $e) {
            Log::error('Error downloading logs: '.$e->getMessage());

            return back()->with('error', 'Error al descargar los logs.');
        }
    }

    /**
     * @return array<int, array{timestamp: string, level: string, message: string}>
     */
    private function parseLogContent(string $content): array
    {
        $lines = explode("\n", $content);
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
