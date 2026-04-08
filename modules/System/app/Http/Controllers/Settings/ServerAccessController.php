<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Activity\Models\ApplicationLog;
use Modules\System\Traits\FormatsBytes;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServerAccessController extends Controller
{
    use FormatsBytes;

    /**
     * Show server access logs
     */
    public function index(Request $request): View
    {
        $limit = $request->get('limit', 100);
        $level = $request->get('level', null);
        $search = $request->get('search', null);
        $source = $request->get('source', 'database'); // 'database' or 'file'

        if ($source === 'file') {
            $allLogs = $this->getAccessLogsFromFiles(null);

            if ($search) {
                $allLogs = array_filter($allLogs, fn ($log) => str_contains(strtolower($log['message'] ?? ''), strtolower($search)));
                $allLogs = array_values($allLogs);
            }

            $total = count($allLogs);
            $logs = array_slice($allLogs, 0, $limit);
        } else {
            $query = ApplicationLog::query()->orderBy('created_at', 'desc');

            if ($level) {
                $query->where('level', $level);
            }

            if ($search) {
                $query->where('message', 'like', "%{$search}%");
            }

            $total = $query->count();
            $logs = $query->limit($limit)->get()->map(function ($log) {
                return [
                    'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                    'level' => $log->level,
                    'message' => $log->message,
                    'context' => $log->context,
                    'url' => $log->url,
                    'ip_address' => $log->ip_address,
                    'user_id' => $log->user_id,
                    'id' => $log->id,
                ];
            })->toArray();
        }

        return view('system::server.access.index', [
            'logs' => $logs,
            'limit' => $limit,
            'total' => $total,
            'source' => $source,
            'level' => $level,
            'search' => $search,
        ]);
    }

    /**
     * Get access logs from database
     */
    private function getAccessLogsFromDatabase(int $limit = 100): Collection
    {
        return ApplicationLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get access logs from Laravel log files (organized by date)
     */
    private function getAccessLogsFromFiles(?int $limit = 100): array
    {
        $logsPath = storage_path('logs');
        $logs = [];

        if (! is_dir($logsPath)) {
            return [];
        }

        // Recursively read all log files
        $files = $this->getAllLogFiles($logsPath);

        // Sort files by modification time (newest first)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach ($files as $file) {
            if (! is_file($file) || ! str_contains($file, 'laravel')) {
                continue;
            }

            $content = @file_get_contents($file);
            if (! $content) {
                continue;
            }

            $lines = array_reverse(explode("\n", $content));

            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }

                // Parse Laravel log format: [YYYY-MM-DD HH:MM:SS] ENVIRONMENT.LEVEL: message
                if (preg_match('/\[(.*?)\]\s+\w+\.(\w+):\s+(.*)/', $line, $matches)) {
                    $logs[] = [
                        'timestamp' => $matches[1],
                        'level' => $matches[2],
                        'message' => $matches[3],
                        'raw' => $line,
                    ];

                    if ($limit && count($logs) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        return $logs;
    }

    /**
     * Get all log files recursively
     */
    private function getAllLogFiles($path): array
    {
        $files = [];

        if (! is_dir($path)) {
            return $files;
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path.'/'.$item;

            if (is_dir($itemPath)) {
                $files = array_merge($files, $this->getAllLogFiles($itemPath));
            } elseif (is_file($itemPath)) {
                $files[] = $itemPath;
            }
        }

        return $files;
    }

    /**
     * Get server info and statistics
     */
    public function stats(): View
    {
        $stats = [
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'N/A',
            'php_version' => phpversion(),
            'os' => php_uname(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'disk_total' => $this->formatBytes((int) disk_total_space('/')),
            'disk_free' => $this->formatBytes((int) disk_free_space('/')),
            'disk_usage_percent' => round(((disk_total_space('/') - disk_free_space('/')) / disk_total_space('/')) * 100, 2),
            'uptime' => $this->getServerUptime(),
        ];

        return view('system::server.stats.index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Get server uptime
     */
    private function getServerUptime(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A (Windows)';
        }

        $uptime = shell_exec('uptime');

        return trim($uptime);
    }

    /**
     * Clear logs
     */
    public function clearLogs(Request $request): JsonResponse
    {
        try {
            $logPath = storage_path('logs/laravel.log');

            if (file_exists($logPath)) {
                File::delete($logPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Logs limpiados correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Log file clear failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar logs.',
            ], 500);
        }
    }

    /**
     * Perform a bulk action on selected log entries.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', Rule::in(['delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = ApplicationLog::whereIn('id', $request->input('ids'))->delete();

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Download logs
     */
    public function downloadLogs(): RedirectResponse|BinaryFileResponse
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            return back()->with('error', 'No hay logs disponibles');
        }

        return response()->download($logPath, 'laravel-logs-'.date('Y-m-d-His').'.log');
    }
}
