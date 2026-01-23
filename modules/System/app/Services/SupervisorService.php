<?php

namespace Modules\System\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Symfony\Component\Process\Process;

class SupervisorService
{
    private static $configPath = '/etc/supervisor/conf.d';

    private static $supervisorctlCommand = 'supervisorctl';

    /**
     * Get status of all Supervisor processes
     */
    public static function getStatus()
    {
        try {
            $process = self::executeSupervisorCtl(['status']);

            if (! $process['success']) {
                Log::warning('Supervisor status failed', ['error' => $process['error'] ?? 'Unknown error']);

                return ['error' => 'Failed to get status: '.($process['error'] ?? 'Unknown error')];
            }

            $output = $process['output'];
            $lines = array_filter(explode("\n", $output));

            $processes = [];
            foreach ($lines as $line) {
                $parsed = self::parseStatusLine($line);
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
     * Execute supervisorctl command with sudo
     * Uses sudo -n (non-interactive) to avoid password prompts
     * Requires passwordless sudo to be configured in /etc/sudoers
     */
    private static function executeSupervisorCtl($args = [])
    {
        try {
            // Use sudo -n for non-interactive execution (requires passwordless sudo)
            $command = array_merge(['sudo', '-n', self::$supervisorctlCommand], $args);
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
     * Parse a single supervisor status line
     */
    private static function parseStatusLine($line)
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
     * Get status of a specific process
     */
    public static function getProcessStatus($processName)
    {
        try {
            $result = self::executeSupervisorCtl(['status', $processName]);

            if (! $result['success']) {
                return ['error' => $result['error']];
            }

            $output = trim($result['output']);
            $parsed = self::parseStatusLine($output);

            return ['success' => true, 'process' => $parsed];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Start a process
     */
    public static function startProcess($processName)
    {
        try {
            $result = self::executeSupervisorCtl(['start', $processName]);

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
     * Stop a process
     */
    public static function stopProcess($processName)
    {
        try {
            $result = self::executeSupervisorCtl(['stop', $processName]);

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
     * Restart a process
     */
    public static function restartProcess($processName)
    {
        try {
            $result = self::executeSupervisorCtl(['restart', $processName]);

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
     * Get process logs
     */
    public static function getProcessLogs($processName, $lines = 50)
    {
        try {
            $result = self::executeSupervisorCtl(['tail', '-f', $processName, (string) $lines]);

            if (! $result['success']) {
                return ['error' => $result['error']];
            }

            return ['success' => true, 'logs' => $result['output']];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Reload supervisor configuration
     */
    public static function reload()
    {
        try {
            // First reread
            $reread = self::executeSupervisorCtl(['reread']);
            if (! $reread['success']) {
                return ['error' => 'Failed to reread configuration: '.$reread['error']];
            }

            // Then update
            $update = self::executeSupervisorCtl(['update']);

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
     * Restart supervisor service
     * Uses sudo -n (non-interactive) to avoid password prompts
     */
    public static function restartSupervisor()
    {
        try {
            // Use sudo -n for non-interactive execution
            $process = new Process(['sudo', '-n', 'systemctl', 'restart', 'supervisor']);
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
     * Get PID of a process
     */
    public static function getPid($processName)
    {
        $status = self::getProcessStatus($processName);

        if (isset($status['process']['details']) && preg_match('/pid (\d+)/', $status['process']['details'], $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get uptime of a process
     */
    public static function getUptime($processName)
    {
        $status = self::getProcessStatus($processName);

        if (isset($status['process']['details']) && preg_match('/uptime ([\d:]+)/', $status['process']['details'], $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get all Alsernet processes
     */
    public static function getAlsernetProcesses()
    {
        $status = self::getStatus();

        if (! isset($status['processes'])) {
            return [];
        }

        return array_filter($status['processes'], function ($p) {
            return $p && strpos($p['name'], 'Alsernet') !== false;
        });
    }

    /**
     * Create a backup of supervisor configurations
     */
    public static function createBackup($name, $description = null, $environment = 'dev')
    {
        try {
            $configFiles = self::readConfigFiles();
            $supervisorStatus = self::getStatus();

            $backup = Setting\Backup\SupervisorBackup::create([
                'name' => $name,
                'description' => $description,
                'environment' => $environment,
                'config_files' => $configFiles,
                'supervisor_status' => $supervisorStatus['processes'] ?? [],
                'backup_size' => self::calculateConfigSize($configFiles),
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
     * Restore a backup of supervisor configurations
     */
    public static function restoreBackup($backupId, $userId = null)
    {
        try {
            $backup = Setting\Backup\SupervisorBackup::findOrFail($backupId);

            // Write config files back
            if ($backup->config_files) {
                foreach ($backup->config_files as $filePath => $content) {
                    self::writeConfigFile($filePath, $content);
                }
            }

            // Update restore info
            $backup->update([
                'restored_at' => now(),
                'restored_by' => $userId ?? auth()->id(),
            ]);

            // Reload supervisor
            self::reload();

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
     * Delete a backup
     */
    public static function deleteBackup($backupId)
    {
        try {
            $backup = Setting\Backup\SupervisorBackup::findOrFail($backupId);
            $backup->delete();

            Log::info('Supervisor backup deleted', ['backup_id' => $backupId]);

            return ['success' => true, 'message' => 'Backup eliminado'];
        } catch (Exception $e) {
            Log::error('Supervisor backup delete failed', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Read all supervisor config files
     */
    private static function readConfigFiles()
    {
        $configFiles = [];

        try {
            // Read from default config path
            $configDirs = [
                '/etc/supervisor/conf.d',
                '/etc/supervisor/supervisord.conf',
            ];

            foreach ($configDirs as $dir) {
                if (file_exists($dir)) {
                    if (is_file($dir)) {
                        // Single file
                        $configFiles[$dir] = file_get_contents($dir);
                    } elseif (is_dir($dir)) {
                        // Directory with .conf files
                        $files = glob($dir.'/*.conf');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                $configFiles[$file] = file_get_contents($file);
                            }
                        }
                    }
                }
            }

            // Also get the local project config
            $projectConfig = base_path('config/supervisor');
            if (is_dir($projectConfig)) {
                $files = glob($projectConfig.'/*.conf');
                foreach ($files as $file) {
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
     * Write a config file
     */
    private static function writeConfigFile($filePath, $content)
    {
        try {
            // Only write to /etc/supervisor/conf.d or project config
            $allowedPaths = [
                '/etc/supervisor/conf.d',
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

            // Create directory if needed
            $dir = dirname($filePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($filePath, $content);
            Log::info('Config file written', ['file' => $filePath]);

            return true;
        } catch (Exception $e) {
            Log::error('Error writing config file', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate total size of config files
     */
    private static function calculateConfigSize($configFiles)
    {
        $size = 0;
        foreach ($configFiles as $content) {
            $size += strlen($content);
        }

        return $size;
    }

    /**
     * Get all backups
     */
    public static function getBackups($environment = null, $limit = 50)
    {
        $query = Setting\Backup\SupervisorBackup::orderBy('backed_up_at', 'desc');

        if ($environment) {
            $query->where('environment', $environment);
        }

        return $query->take($limit)->get();
    }

    /**
     * Get configuration file content for editing
     */
    public static function getConfigFile($filePath)
    {
        try {
            // Security check - only allow reading from allowed paths
            $allowedPaths = [
                '/etc/supervisor/conf.d',
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
     * Update a configuration file
     */
    public static function updateConfigFile($filePath, $content)
    {
        try {
            // Create backup before updating
            if (file_exists($filePath)) {
                $backup = Setting\Backup\SupervisorBackup::create([
                    'name' => 'Auto backup before edit: '.basename($filePath),
                    'environment' => app()->environment() === 'production' ? 'prod' : 'dev',
                    'config_files' => [$filePath => file_get_contents($filePath)],
                    'is_auto' => true,
                    'backed_up_at' => now(),
                ]);
            }

            // Write new content
            self::writeConfigFile($filePath, $content);

            Log::info('Config file updated', ['file' => $filePath]);

            return [
                'success' => true,
                'message' => 'Archivo actualizado exitosamente',
                'backup_id' => $backup->id ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Error updating config file', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }
}
