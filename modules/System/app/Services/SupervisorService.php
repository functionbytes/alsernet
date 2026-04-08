<?php

namespace Modules\System\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Backup\Models\SupervisorBackup;
use Symfony\Component\Process\Process;

class SupervisorService
{
    /**
     * Resolve the supervisorctl binary path based on the current OS.
     * On macOS (Homebrew) the binary is owned by the current user — no sudo needed.
     */
    private function supervisorctlBin(): string
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            foreach (['/opt/homebrew/bin/supervisorctl', '/usr/local/bin/supervisorctl'] as $path) {
                if (is_executable($path)) {
                    return $path;
                }
            }
        }

        return 'supervisorctl';
    }

    /**
     * Resolve the supervisor conf.d directory based on the current OS.
     */
    private function confDir(): string
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            foreach (['/opt/homebrew/etc/supervisor.d', '/usr/local/etc/supervisor.d'] as $path) {
                if (is_dir($path)) {
                    return $path;
                }
            }

            return '/opt/homebrew/etc/supervisor.d';
        }

        return '/etc/supervisor/conf.d';
    }

    /**
     * Get status of all Supervisor processes
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        try {
            $process = $this->executeSupervisorCtl(['status']);

            if (! $process['success']) {
                Log::warning('Supervisor status failed', ['error' => $process['error'] ?? 'Unknown error']);

                return ['error' => 'Failed to get status: '.($process['error'] ?? 'Unknown error')];
            }

            $lines = array_filter(explode("\n", $process['output']));
            $processes = [];

            foreach ($lines as $line) {
                $parsed = $this->parseStatusLine($line);
                if ($parsed) {
                    $processes[] = $parsed;
                }
            }

            return ['success' => true, 'processes' => $processes];
        } catch (Exception $e) {
            Log::error('Supervisor getStatus exception', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Execute a supervisorctl command.
     * On macOS (Homebrew) runs the binary directly (no sudo needed).
     * On Linux uses sudo -n (requires passwordless sudo in /etc/sudoers).
     *
     * @param  array<int, string>  $args
     * @return array<string, mixed>
     */
    private function executeSupervisorCtl(array $args = []): array
    {
        try {
            $bin = $this->supervisorctlBin();

            $command = PHP_OS_FAMILY === 'Darwin'
                ? array_merge([$bin], $args)
                : array_merge(['sudo', '-n', $bin], $args);

            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                $error = $process->getErrorOutput() ?: $process->getOutput();
                Log::warning('Supervisorctl command failed', [
                    'command' => implode(' ', $command),
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'error' => trim($error),
                    'output' => $process->getOutput(),
                ];
            }

            return [
                'success' => true,
                'output' => $process->getOutput(),
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('Supervisorctl execution exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'output' => '',
            ];
        }
    }

    /**
     * Parse a single supervisor status line.
     *
     * @return array<string, string>|null
     */
    private function parseStatusLine(string $line): ?array
    {
        // Format: program_name:process_name STATE [pid xxx, uptime x:xx:xx] [exitstatus xx]
        if (preg_match('/^(\S+)\s+(\S+)\s+(.+)$/', trim($line), $matches)) {
            return [
                'name' => $matches[1],
                'state' => $matches[2],
                'details' => $matches[3],
            ];
        }

        return null;
    }

    /**
     * Validate that a process name is safe to pass to supervisorctl.
     *
     * Rejects empty strings, the literal "all" (which would match every process),
     * and any string containing characters outside [a-zA-Z0-9_\-:.].
     *
     * @throws \InvalidArgumentException
     */
    public function validateProcessName(string $processName): void
    {
        if ($processName === '') {
            throw new \InvalidArgumentException('Process name cannot be empty.');
        }

        if (strtolower($processName) === 'all') {
            throw new \InvalidArgumentException('The process name "all" is not allowed.');
        }

        if (! preg_match('/^[a-zA-Z0-9_\-:.]+$/', $processName)) {
            throw new \InvalidArgumentException(
                'Process name contains invalid characters. Only alphanumerics, underscores, hyphens, colons and dots are allowed.'
            );
        }
    }

    /**
     * Get status of a specific process.
     *
     * @return array<string, mixed>
     */
    public function getProcessStatus(string $processName): array
    {
        $this->validateProcessName($processName);

        try {
            $result = $this->executeSupervisorCtl(['status', $processName]);

            if (! $result['success']) {
                return ['error' => $result['error']];
            }

            $parsed = $this->parseStatusLine(trim($result['output']));

            return ['success' => true, 'process' => $parsed];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Start a process.
     *
     * @return array<string, mixed>
     */
    public function startProcess(string $processName): array
    {
        $this->validateProcessName($processName);

        try {
            $result = $this->executeSupervisorCtl(['start', $processName]);

            return [
                'success' => $result['success'],
                'output' => $result['output'],
                'error' => $result['error'],
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Stop a process.
     *
     * @return array<string, mixed>
     */
    public function stopProcess(string $processName): array
    {
        $this->validateProcessName($processName);

        try {
            $result = $this->executeSupervisorCtl(['stop', $processName]);

            return [
                'success' => $result['success'],
                'output' => $result['output'],
                'error' => $result['error'],
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Restart a process.
     *
     * @return array<string, mixed>
     */
    public function restartProcess(string $processName): array
    {
        $this->validateProcessName($processName);

        try {
            $result = $this->executeSupervisorCtl(['restart', $processName]);

            return [
                'success' => $result['success'],
                'output' => $result['output'],
                'error' => $result['error'],
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Restart all Supervisor processes.
     *
     * @return array<string, mixed>
     */
    public function restartAllProcesses(): array
    {
        try {
            $result = $this->executeSupervisorCtl(['restart', 'all']);

            return [
                'success' => $result['success'],
                'output' => $result['output'],
                'error' => $result['error'],
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get process logs.
     *
     * @return array<string, mixed>
     */
    public function getProcessLogs(string $processName, int $lines = 50): array
    {
        $this->validateProcessName($processName);

        try {
            $result = $this->executeSupervisorCtl(['tail', '-f', $processName, (string) $lines]);

            if (! $result['success']) {
                return ['error' => $result['error']];
            }

            return ['success' => true, 'logs' => $result['output']];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Reload supervisor configuration.
     *
     * @return array<string, mixed>
     */
    public function reload(): array
    {
        try {
            $reread = $this->executeSupervisorCtl(['reread']);

            if (! $reread['success']) {
                return ['error' => 'Failed to reread configuration: '.$reread['error']];
            }

            $update = $this->executeSupervisorCtl(['update']);

            return [
                'success' => $update['success'],
                'output' => $update['output'],
                'error' => $update['error'],
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Restart supervisor service.
     * On macOS uses `brew services restart supervisor`.
     * On Linux uses `sudo -n systemctl restart supervisor`.
     *
     * @return array<string, mixed>
     */
    public function restartSupervisor(): array
    {
        try {
            if (PHP_OS_FAMILY === 'Darwin') {
                $brew = is_executable('/opt/homebrew/bin/brew') ? '/opt/homebrew/bin/brew' : '/usr/local/bin/brew';
                $command = [$brew, 'services', 'restart', 'supervisor'];
            } else {
                $command = ['sudo', '-n', 'systemctl', 'restart', 'supervisor'];
            }

            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            Log::info('Supervisor restart executed', [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ]);

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ];
        } catch (Exception $e) {
            Log::error('Supervisor restart failed', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get PID of a process.
     */
    public function getPid(string $processName): ?string
    {
        $status = $this->getProcessStatus($processName);

        if (isset($status['process']['details']) && preg_match('/pid (\d+)/', $status['process']['details'], $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get uptime of a process.
     */
    public function getUptime(string $processName): ?string
    {
        $status = $this->getProcessStatus($processName);

        if (isset($status['process']['details']) && preg_match('/uptime ([\d:]+)/', $status['process']['details'], $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get all Alsernet processes.
     *
     * @return array<int, array<string, string>>
     */
    public function getAlsernetProcesses(): array
    {
        $status = $this->getStatus();

        if (! isset($status['processes'])) {
            return [];
        }

        return array_values(array_filter($status['processes'], function ($p) {
            return $p && strpos($p['name'], 'Alsernet') !== false;
        }));
    }

    /**
     * Create a backup of supervisor configurations.
     *
     * @return array<string, mixed>
     */
    public function createBackup(string $name, ?string $description = null, string $environment = 'dev'): array
    {
        try {
            $configFiles = $this->readConfigFiles();
            $supervisorStatus = $this->getStatus();

            $backup = SupervisorBackup::create([
                'name' => $name,
                'description' => $description,
                'environment' => $environment,
                'config_files' => $configFiles,
                'supervisor_status' => $supervisorStatus['processes'] ?? [],
                'backup_size' => $this->calculateConfigSize($configFiles),
                'backed_up_at' => now(),
            ]);

            Log::info('Supervisor backup created', [
                'backup_id' => $backup->id,
                'name' => $name,
                'environment' => $environment,
            ]);

            return [
                'success' => true,
                'backup_id' => $backup->id,
                'message' => 'Backup creado exitosamente',
            ];
        } catch (Exception $e) {
            Log::error('Supervisor backup failed', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Restore a backup of supervisor configurations.
     *
     * @return array<string, mixed>
     */
    public function restoreBackup(int|string $backupId, ?int $userId = null): array
    {
        try {
            $backup = SupervisorBackup::findOrFail($backupId);

            if ($backup->config_files) {
                foreach ($backup->config_files as $filePath => $content) {
                    $this->writeConfigFile($filePath, $content);
                }
            }

            $backup->update([
                'restored_at' => now(),
                'restored_by' => $userId ?? auth()->id(),
            ]);

            $this->reload();

            Log::info('Supervisor backup restored', [
                'backup_id' => $backupId,
                'restored_by' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'Configuración restaurada exitosamente',
            ];
        } catch (Exception $e) {
            Log::error('Supervisor restore failed', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete a backup.
     *
     * @return array<string, mixed>
     */
    public function deleteBackup(int|string $backupId): array
    {
        try {
            $backup = SupervisorBackup::findOrFail($backupId);
            $backup->delete();

            Log::info('Supervisor backup deleted', ['backup_id' => $backupId]);

            return ['success' => true, 'message' => 'Backup eliminado'];
        } catch (Exception $e) {
            Log::error('Supervisor backup delete failed', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * List all config file paths in the supervisor conf.d directory.
     *
     * @return array<int, string>
     */
    public function listConfDirFiles(): array
    {
        $confDir = $this->confDir();
        $glob = PHP_OS_FAMILY === 'Darwin' ? '*.ini' : '*.conf';

        if (! is_dir($confDir)) {
            return [];
        }

        return array_values(array_filter(glob($confDir.'/'.$glob), 'is_file'));
    }

    /**
     * Read all supervisor config files.
     *
     * @return array<string, string>
     */
    private function readConfigFiles(): array
    {
        $configFiles = [];

        try {
            $confDir = $this->confDir();
            $glob = PHP_OS_FAMILY === 'Darwin' ? '*.ini' : '*.conf';

            $candidates = PHP_OS_FAMILY === 'Darwin'
                ? [$confDir]
                : [$confDir, '/etc/supervisor/supervisord.conf'];

            foreach ($candidates as $dir) {
                if (! file_exists($dir)) {
                    continue;
                }

                if (is_file($dir)) {
                    $configFiles[$dir] = file_get_contents($dir);
                } elseif (is_dir($dir)) {
                    foreach (glob($dir.'/'.$glob) as $file) {
                        if (is_file($file)) {
                            $configFiles[$file] = file_get_contents($file);
                        }
                    }
                }
            }

            $projectConfig = base_path('config/supervisor');
            if (is_dir($projectConfig)) {
                foreach (glob($projectConfig.'/*.conf') as $file) {
                    if (is_file($file)) {
                        $configFiles[$file] = file_get_contents($file);
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('Error reading supervisor config files', ['error' => $e->getMessage()]);
        }

        return $configFiles;
    }

    /**
     * Write a config file to an allowed path.
     *
     * @throws Exception
     */
    private function writeConfigFile(string $filePath, string $content): bool
    {
        $allowedPaths = [
            '/etc/supervisor/conf.d',
            '/opt/homebrew/etc/supervisor.d',
            '/usr/local/etc/supervisor.d',
            base_path('config/supervisor'),
        ];

        $isAllowed = false;
        foreach ($allowedPaths as $allowed) {
            if (strpos($filePath, $allowed) === 0) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            throw new Exception('Path not allowed: '.$filePath);
        }

        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $content);
        Log::info('Config file written', ['file' => $filePath]);

        return true;
    }

    /**
     * Calculate total size of config files.
     *
     * @param  array<string, string>  $configFiles
     */
    private function calculateConfigSize(array $configFiles): int
    {
        $size = 0;
        foreach ($configFiles as $content) {
            $size += strlen($content);
        }

        return $size;
    }

    /**
     * Get all backups.
     *
     * @return Collection<int, SupervisorBackup>
     */
    public function getBackups(?string $environment = null, int $limit = 50): Collection
    {
        $query = SupervisorBackup::orderBy('backed_up_at', 'desc');

        if ($environment) {
            $query->where('environment', $environment);
        }

        return $query->take($limit)->get();
    }

    /**
     * Get configuration file content for editing.
     *
     * @return array<string, mixed>
     */
    public function getConfigFile(string $filePath): array
    {
        try {
            $allowedPaths = [
                '/etc/supervisor/conf.d',
                '/opt/homebrew/etc/supervisor.d',
                '/usr/local/etc/supervisor.d',
                base_path('config/supervisor'),
            ];

            $isAllowed = false;
            foreach ($allowedPaths as $allowed) {
                if (strpos($filePath, $allowed) === 0) {
                    $isAllowed = true;
                    break;
                }
            }

            if (! $isAllowed || ! file_exists($filePath)) {
                return ['error' => 'File not found or not allowed'];
            }

            return [
                'success' => true,
                'content' => file_get_contents($filePath),
                'file' => $filePath,
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update a configuration file.
     *
     * @return array<string, mixed>
     */
    public function updateConfigFile(string $filePath, string $content): array
    {
        try {
            $backup = null;

            if (file_exists($filePath)) {
                $backup = SupervisorBackup::create([
                    'name' => 'Auto backup before edit: '.basename($filePath),
                    'environment' => app()->environment() === 'production' ? 'prod' : 'dev',
                    'config_files' => [$filePath => file_get_contents($filePath)],
                    'is_auto' => true,
                    'backed_up_at' => now(),
                ]);
            }

            $this->writeConfigFile($filePath, $content);

            Log::info('Config file updated', ['file' => $filePath]);

            return [
                'success' => true,
                'message' => 'Archivo actualizado exitosamente',
                'backup_id' => $backup?->id,
            ];
        } catch (Exception $e) {
            Log::error('Error updating config file', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }
}
