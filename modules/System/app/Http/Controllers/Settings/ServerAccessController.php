<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Modules\Activity\Models\ApplicationLog;

class ServerAccessController extends Controller
{
    /**
     * Show server access logs
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 100);
        $level = $request->get('level', null);
        $source = $request->get('source', 'database'); // 'database' or 'file'

        if ($source === 'file') {
            $logs = $this->getAccessLogsFromFiles($limit);
            $total = count($this->getAccessLogsFromFiles(null));
        } else {
            $query = ApplicationLog::query()->orderBy('created_at', 'desc');

            if ($level) {
                $query->where('level', $level);
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
        ]);
    }

    /**
     * Get access logs from database
     */
    private function getAccessLogsFromDatabase($limit = 100)
    {
        return ApplicationLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get access logs from Laravel log files (organized by date)
     */
    private function getAccessLogsFromFiles($limit = 100)
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
    public function stats()
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
            'disk_total' => $this->formatBytes(disk_total_space('/')),
            'disk_free' => $this->formatBytes(disk_free_space('/')),
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
    private function getServerUptime()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A (Windows)';
        }

        $uptime = shell_exec('uptime');

        return trim($uptime);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Clear logs
     */
    public function clearLogs(Request $request)
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
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar logs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download logs
     */
    public function downloadLogs()
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            return back()->with('error', 'No hay logs disponibles');
        }

        return response()->download($logPath, 'laravel-logs-'.date('Y-m-d-His').'.log');
    }
}
