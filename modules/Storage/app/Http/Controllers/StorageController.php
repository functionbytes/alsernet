<?php

namespace Modules\Storage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class StorageController extends Controller
{
    /**
     * Display storage management page
     */
    public function index()
    {
        $storageData = $this->getStorageData();
        $statistics = $this->getStorageStatistics($storageData);

        return view('storage::index', [
            'storageData' => $storageData,
            'statistics' => $statistics,
            'pageTitle' => 'Configuración de almacenamiento',
            'breadcrumb' => 'Configuración / Almacenamiento',
        ]);
    }

    /**
     * Update storage configuration
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'disks' => 'required|array',
            'disks.*.name' => 'required|string|max:50',
            'disks.*.driver' => 'required|string|in:local,ftp,sftp,s3',
            'disks.*.root' => 'required_if:disks.*.driver,local|string',
            'disks.*.url' => 'nullable|string',
            'disks.*.host' => 'required_if:disks.*.driver,ftp,sftp|string',
            'disks.*.username' => 'required_if:disks.*.driver,ftp,sftp|string',
            'disks.*.password' => 'nullable|string',
            'disks.*.port' => 'nullable|integer',
            'disks.*.bucket' => 'required_if:disks.*.driver,s3|string',
            'disks.*.key' => 'required_if:disks.*.driver,s3|string',
            'disks.*.secret' => 'required_if:disks.*.driver,s3|string',
            'disks.*.region' => 'required_if:disks.*.driver,s3|string',
        ]);

        try {
            // Validate disk names are unique
            $diskNames = array_column($validated['disks'], 'name');
            if (count($diskNames) !== count(array_unique($diskNames))) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No pueden existir dos discos con el mismo nombre',
                    ], 422);
                }

                return back()
                    ->withInput()
                    ->with('error', 'No pueden existir dos discos con el mismo nombre');
            }

            // Save custom storage disks configuration
            Setting::set('system.custom_storage_disks', json_encode($validated['disks']));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Configuración de almacenamiento actualizada correctamente',
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Configuración de almacenamiento actualizada correctamente');
        } catch (\Exception $e) {
            \Log::error('Error updating storage configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al actualizar la configuración: '.$e->getMessage(),
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la configuración: '.$e->getMessage());
        }
    }

    /**
     * Show create storage disk form
     */
    public function create()
    {
        $storageData = $this->getStorageData();

        return view('storage::create', [
            'driverOptions' => $storageData['driver_options'],
            'pageTitle' => 'Crear disco de almacenamiento',
            'breadcrumb' => 'Configuración / Almacenamiento / Crear',
        ]);
    }

    /**
     * Store a new storage disk
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'driver' => 'required|string|in:local,ftp,sftp,s3',
            'storage_type' => 'required_if:driver,local|nullable|string|in:public,private',
            'root' => 'nullable|string',
            'url' => 'nullable|string',
            'host' => 'required_if:driver,ftp,sftp|nullable|string',
            'username' => 'required_if:driver,ftp,sftp|nullable|string',
            'password' => 'nullable|string',
            'port' => 'nullable|integer',
            'bucket' => 'required_if:driver,s3|nullable|string',
            'key' => 'required_if:driver,s3|nullable|string',
            'secret' => 'required_if:driver,s3|nullable|string',
            'region' => 'required_if:driver,s3|nullable|string',
        ]);

        try {
            $diskData = [
                'name' => $validated['name'],
                'driver' => $validated['driver'],
            ];

            if ($validated['driver'] === 'local') {
                // Generate path and URL based on storage type
                $pathInfo = $this->generateStoragePath($validated['name'], $validated['storage_type']);

                if (! $pathInfo['success']) {
                    return back()
                        ->withInput()
                        ->with('error', $pathInfo['message']);
                }

                $diskData['root'] = $pathInfo['root'];
                $diskData['url'] = $pathInfo['url'];
                $diskData['storage_type'] = $validated['storage_type'];

                // Validate and prepare the disk
                $validation = $this->validateAndPrepareLocalDisk($pathInfo['root']);
                if (! $validation['success']) {
                    return back()
                        ->withInput()
                        ->with('error', $validation['message']);
                }
            } elseif ($validated['driver'] === 'ftp' || $validated['driver'] === 'sftp') {
                $diskData['host'] = $validated['host'];
                $diskData['username'] = $validated['username'];
                $diskData['password'] = $validated['password'] ?? null;
                $diskData['port'] = $validated['port'] ?? null;
            } elseif ($validated['driver'] === 's3') {
                $diskData['bucket'] = $validated['bucket'];
                $diskData['region'] = $validated['region'];
                $diskData['key'] = $validated['key'];
                $diskData['secret'] = $validated['secret'] ?? null;
            }

            $customDisksJson = Setting::get('system.custom_storage_disks', '[]');
            $existingDisks = json_decode($customDisksJson, true) ?: [];

            $diskNames = array_column($existingDisks, 'name');
            if (in_array($validated['name'], $diskNames)) {
                return back()
                    ->withInput()
                    ->with('error', 'Ya existe un disco con ese nombre');
            }

            $configDisks = config('filesystems.disks', []);
            if (isset($configDisks[$validated['name']])) {
                return back()
                    ->withInput()
                    ->with('error', 'Ya existe un disco del sistema con ese nombre');
            }

            $existingDisks[] = $diskData;
            Setting::set('system.custom_storage_disks', json_encode($existingDisks));

            return redirect()
                ->route('settings.storage')
                ->with('success', 'Disco de almacenamiento creado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error creating storage disk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al crear el disco: '.$e->getMessage());
        }
    }

    /**
     * Show edit storage disk form
     */
    public function edit(int $index)
    {
        $storageData = $this->getStorageData();

        if (! isset($storageData['custom_disks'][$index])) {
            return redirect()
                ->route('settings.storage')
                ->with('error', 'El disco solicitado no existe');
        }

        $disk = $storageData['custom_disks'][$index];
        $isFromConfig = $disk['from_config'] ?? false;

        return view('storage::edit', [
            'disk' => $disk,
            'diskIndex' => $index,
            'isFromConfig' => $isFromConfig,
            'driverOptions' => $storageData['driver_options'],
            'pageTitle' => 'Editar disco de almacenamiento',
            'breadcrumb' => 'Configuración / Almacenamiento / Editar',
        ]);
    }

    /**
     * Update a specific storage disk
     */
    public function updateDisk(Request $request, int $index)
    {
        $storageData = $this->getStorageData();

        if (! isset($storageData['custom_disks'][$index])) {
            return redirect()
                ->route('settings.storage')
                ->with('error', 'El disco solicitado no existe');
        }

        $disk = $storageData['custom_disks'][$index];
        $isFromConfig = $disk['from_config'] ?? false;

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'driver' => 'required|string|in:local,ftp,sftp,s3',
            'storage_type' => 'required_if:driver,local|nullable|string|in:public,private',
            'root' => 'nullable|string',
            'url' => 'nullable|string',
            'host' => 'required_if:driver,ftp,sftp|nullable|string',
            'username' => 'required_if:driver,ftp,sftp|nullable|string',
            'password' => 'nullable|string',
            'port' => 'nullable|integer',
            'bucket' => 'required_if:driver,s3|nullable|string',
            'key' => 'required_if:driver,s3|nullable|string',
            'secret' => 'nullable|string',
            'region' => 'required_if:driver,s3|nullable|string',
        ]);

        try {
            $diskData = [
                'name' => $validated['name'],
                'driver' => $validated['driver'],
            ];

            if ($validated['driver'] === 'local') {
                // Generate path and URL based on storage type
                $pathInfo = $this->generateStoragePath($validated['name'], $validated['storage_type']);

                if (! $pathInfo['success']) {
                    return back()
                        ->withInput()
                        ->with('error', $pathInfo['message']);
                }

                $diskData['root'] = $pathInfo['root'];
                $diskData['url'] = $pathInfo['url'];
                $diskData['storage_type'] = $validated['storage_type'];

                // Validate and prepare the disk
                $validation = $this->validateAndPrepareLocalDisk($pathInfo['root']);
                if (! $validation['success']) {
                    return back()
                        ->withInput()
                        ->with('error', $validation['message']);
                }
            } elseif ($validated['driver'] === 'ftp' || $validated['driver'] === 'sftp') {
                $diskData['host'] = $validated['host'];
                $diskData['username'] = $validated['username'];
                $diskData['password'] = $validated['password'] ?? $disk['password'] ?? null;
                $diskData['port'] = $validated['port'] ?? null;
            } elseif ($validated['driver'] === 's3') {
                $diskData['bucket'] = $validated['bucket'];
                $diskData['region'] = $validated['region'];
                $diskData['key'] = $validated['key'];
                $diskData['secret'] = $validated['secret'] ?? $disk['secret'] ?? null;
            }

            $customDisksJson = Setting::get('system.custom_storage_disks', '[]');
            $existingDisks = json_decode($customDisksJson, true) ?: [];

            if ($isFromConfig) {
                $existingDisks[] = $diskData;
            } else {
                $realIndex = array_search($disk['name'], array_column($existingDisks, 'name'));
                if ($realIndex !== false) {
                    $existingDisks[$realIndex] = $diskData;
                } else {
                    $existingDisks[] = $diskData;
                }
            }

            $diskNames = array_column($existingDisks, 'name');
            if (count($diskNames) !== count(array_unique($diskNames))) {
                return back()
                    ->withInput()
                    ->with('error', 'No pueden existir dos discos con el mismo nombre');
            }

            Setting::set('system.custom_storage_disks', json_encode($existingDisks));

            return redirect()
                ->route('settings.storage')
                ->with('success', 'Disco actualizado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error updating storage disk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el disco: '.$e->getMessage());
        }
    }

    /**
     * Delete custom storage disk
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'disk_name' => 'required|string',
        ]);

        try {
            $customDisksJson = Setting::get('system.custom_storage_disks', '[]');
            $customDisks = json_decode($customDisksJson, true) ?: [];

            // Filter out the disk to delete
            $customDisks = array_filter($customDisks, function ($disk) use ($validated) {
                return $disk['name'] !== $validated['disk_name'];
            });

            // Re-index array
            $customDisks = array_values($customDisks);

            // Save updated configuration
            Setting::set('system.custom_storage_disks', json_encode($customDisks));

            return redirect()
                ->back()
                ->with('success', 'Disco de almacenamiento eliminado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error deleting storage disk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Error al eliminar el disco: '.$e->getMessage());
        }
    }

    /**
     * Generate storage path and URL based on disk name and storage type
     */
    private function generateStoragePath(string $diskName, string $storageType): array
    {
        try {
            if ($storageType === 'public') {
                // Use storage/app/public directly instead of symlink
                $root = storage_path('app/public/'.$diskName);
                $url = '/storage/'.$diskName;
            } elseif ($storageType === 'private') {
                $root = storage_path($diskName);
                $url = null;
            } else {
                return [
                    'success' => false,
                    'message' => 'Tipo de almacenamiento no válido',
                ];
            }

            return [
                'success' => true,
                'root' => $root,
                'url' => $url,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al generar la ruta: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Validate and prepare local storage disk
     */
    private function validateAndPrepareLocalDisk(string $rootPath): array
    {
        // Validate that path is not empty
        if (empty(trim($rootPath))) {
            return [
                'success' => false,
                'message' => 'La ruta no puede estar vacía. Proporciona una ruta absoluta como /mnt/storage',
            ];
        }

        // Reject dangerous system paths BEFORE trimming
        $dangerousPaths = ['/', '/bin', '/boot', '/dev', '/etc', '/lib', '/root', '/sbin', '/sys', '/usr', '/var'];
        if (in_array($rootPath, $dangerousPaths) || in_array(rtrim($rootPath, '/'), $dangerousPaths)) {
            return [
                'success' => false,
                'message' => "La ruta '{$rootPath}' no es permitida por razones de seguridad. Usa directorios específicos como /mnt o subdirectorios del proyecto.",
            ];
        }

        $rootPath = rtrim($rootPath, '/');

        // Validate absolute path
        if (! str_starts_with($rootPath, '/')) {
            return [
                'success' => false,
                'message' => 'La ruta debe ser absoluta (debe comenzar con /). Ejemplo: /mnt/storage o /var/www/storage',
            ];
        }

        // Reject paths directly under root (single level like /documents, /uploads, etc)
        // Paths must be at least 2 levels deep or under specific allowed prefixes
        $pathParts = array_filter(explode('/', $rootPath));
        $allowedPrefixes = ['mnt', 'data', 'opt', 'home', 'srv', 'media', 'storage'];
        $firstPart = $pathParts[0] ?? null;

        if (count($pathParts) === 1) {
            return [
                'success' => false,
                'message' => "No se permiten directorios directamente bajo raíz como /{$firstPart}. Usa rutas como /mnt/storage, /data/uploads, /opt/storage, etc.",
            ];
        }

        // Validate that path doesn't contain suspicious patterns
        if (preg_match('/\$\{.*\}/', $rootPath) || preg_match('/`.*`/', $rootPath)) {
            return [
                'success' => false,
                'message' => 'La ruta contiene caracteres no permitidos',
            ];
        }

        if (! is_dir($rootPath)) {
            $parentDir = dirname($rootPath);

            // Create parent directory structure if needed
            if (! is_dir($parentDir)) {
                try {
                    @mkdir($parentDir, 0755, true);
                    // Verify parent was created
                    if (! is_dir($parentDir)) {
                        return [
                            'success' => false,
                            'message' => "No se pudo crear el directorio padre: {$parentDir}",
                        ];
                    }
                    \Log::info('Storage parent directory created', ['path' => $parentDir]);
                } catch (\Exception $e) {
                    return [
                        'success' => false,
                        'message' => "Error al crear directorio padre: {$e->getMessage()}",
                    ];
                }
            }

            // Verify parent directory is writable
            if (! is_writable($parentDir)) {
                return [
                    'success' => false,
                    'message' => "Sin permisos de escritura en: {$parentDir}",
                ];
            }

            // Create the storage directory
            try {
                @mkdir($rootPath, 0755, true);

                // Verify directory was created
                if (! is_dir($rootPath)) {
                    return [
                        'success' => false,
                        'message' => "No se pudo crear el directorio: {$rootPath}",
                    ];
                }

                // Create .gitignore and .gitkeep files
                file_put_contents($rootPath.'/.gitignore', "*\n!.gitignore\n!.gitkeep\n");
                file_put_contents($rootPath.'/.gitkeep', '');

                \Log::info('Storage directory created', ['path' => $rootPath]);
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => "Error al crear directorio: {$e->getMessage()}",
                ];
            }
        }

        if (! is_readable($rootPath)) {
            return [
                'success' => false,
                'message' => "El directorio no es legible: {$rootPath}. Verifica los permisos.",
            ];
        }

        if (! is_writable($rootPath)) {
            return [
                'success' => false,
                'message' => "El directorio no es escribible: {$rootPath}. Contacta al administrador del servidor para cambiar los permisos.",
            ];
        }

        return [
            'success' => true,
            'message' => 'Directorio configurado correctamente',
            'path' => $rootPath,
        ];
    }

    /**
     * Get storage data from configuration
     */
    private function getStorageData(): array
    {
        $systemDisks = $this->getSystemDisks();
        $coreDisks = ['local', 'public'];
        $configDisks = [];
        $disksConfig = config('filesystems.disks');

        foreach ($disksConfig as $diskName => $diskConfig) {
            if (in_array($diskName, $coreDisks)) {
                continue;
            }

            $driver = $diskConfig['driver'] ?? 'unknown';
            $configDisk = [
                'name' => $diskName,
                'driver' => $driver,
                'from_config' => true,
            ];

            if ($driver === 'local') {
                $configDisk['root'] = $diskConfig['root'] ?? '';
                $configDisk['url'] = $diskConfig['url'] ?? '';
            } elseif ($driver === 'ftp' || $driver === 'sftp') {
                $configDisk['host'] = $diskConfig['host'] ?? '';
                $configDisk['port'] = $diskConfig['port'] ?? '';
                $configDisk['username'] = $diskConfig['username'] ?? '';
                $configDisk['password'] = '********';
            } elseif ($driver === 's3') {
                $configDisk['bucket'] = $diskConfig['bucket'] ?? '';
                $configDisk['region'] = $diskConfig['region'] ?? '';
                $configDisk['key'] = $diskConfig['key'] ?? '';
                $configDisk['secret'] = '********';
            }

            $configDisks[] = $configDisk;
        }

        $customDisksJson = Setting::get('system.custom_storage_disks', '[]');
        $customDisks = json_decode($customDisksJson, true) ?: [];

        foreach ($customDisks as &$disk) {
            $disk['from_config'] = false;
        }

        $allCustomDisks = array_merge($configDisks, $customDisks);

        return [
            'system_disks' => $systemDisks,
            'custom_disks' => $allCustomDisks,
            'driver_options' => [
                'local' => 'Almacenamiento Local',
                'ftp' => 'FTP',
                'sftp' => 'SFTP',
                's3' => 'Amazon S3',
            ],
        ];
    }

    /**
     * Get system (core) disks
     */
    private function getSystemDisks(): array
    {
        $coreDisks = ['local', 'public'];
        $disksConfig = config('filesystems.disks');
        $systemDisks = [];

        foreach ($disksConfig as $diskName => $diskConfig) {
            if (! in_array($diskName, $coreDisks)) {
                continue;
            }

            $driver = $diskConfig['driver'] ?? 'unknown';
            $root = $diskConfig['root'] ?? 'N/A';
            $url = $diskConfig['url'] ?? null;

            $description = match ($driver) {
                'local' => 'Almacenamiento local en: '.$root,
                'ftp' => 'Servidor FTP: '.($diskConfig['host'] ?? 'No configurado'),
                'sftp' => 'Servidor SFTP: '.($diskConfig['host'] ?? 'No configurado'),
                's3' => 'Amazon S3: '.($diskConfig['bucket'] ?? 'No configurado'),
                default => 'Driver: '.$driver,
            };

            $systemDisks[] = [
                'name' => $diskName,
                'driver' => $driver,
                'root' => $root,
                'url' => $url,
                'description' => $description,
                'editable' => false,
            ];
        }

        return $systemDisks;
    }

    /**
     * Calculate storage statistics
     */
    private function getStorageStatistics(array $storageData): array
    {
        $systemCount = count($storageData['system_disks']);
        $customCount = count($storageData['custom_disks']);
        $customConfigCount = count(array_filter($storageData['custom_disks'], fn ($disk) => $disk['from_config'] ?? false));
        $customDbCount = $customCount - $customConfigCount;

        $driverCounts = [
            'local' => 0,
            'ftp' => 0,
            'sftp' => 0,
            's3' => 0,
        ];

        foreach ($storageData['custom_disks'] as $disk) {
            $driver = $disk['driver'] ?? 'unknown';
            if (isset($driverCounts[$driver])) {
                $driverCounts[$driver]++;
            }
        }

        return [
            'total_disks' => $systemCount + $customCount,
            'system_disks' => $systemCount,
            'custom_disks_total' => $customCount,
            'custom_config' => $customConfigCount,
            'custom_db' => $customDbCount,
            'driver_counts' => $driverCounts,
        ];
    }
}
