<?php

namespace Modules\Storage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Setting;
use Modules\Storage\Http\Requests\StoreDiskRequest;
use Modules\Storage\Http\Requests\UpdateDiskRequest;

class StorageController extends Controller
{
    /**
     * Display storage management page
     */
    public function index(): View
    {
        abort_unless(auth()->user()->can('storage.view'), 403);

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
     * Show create storage disk form
     */
    public function create(): View
    {
        abort_unless(auth()->user()->can('storage.create'), 403);

        return view('storage::create', [
            'driverOptions' => $this->driverOptions(),
            'pageTitle' => 'Crear disco de almacenamiento',
            'breadcrumb' => 'Configuración / Almacenamiento / Crear',
        ]);
    }

    /**
     * Store a new storage disk
     */
    public function store(StoreDiskRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $diskData = $this->buildDiskData($validated);

            if ($diskData === null) {
                return back()->withInput()->with('error', 'Tipo de almacenamiento no soportado');
            }

            if (is_array($diskData) && isset($diskData['error'])) {
                return back()->withInput()->with('error', $diskData['error']);
            }

            $existingDisks = $this->loadCustomDisks();

            if (in_array($validated['name'], array_column($existingDisks, 'name'))) {
                return back()->withInput()->with('error', 'Ya existe un disco con ese nombre');
            }

            if (array_key_exists($validated['name'], config('filesystems.disks', []))) {
                return back()->withInput()->with('error', 'Ya existe un disco del sistema con ese nombre');
            }

            $existingDisks[] = $diskData;
            $this->saveCustomDisks($existingDisks);

            activity('storage')->causedBy(auth()->user())
                ->withProperties(['disk' => $diskData['name'], 'driver' => $diskData['driver']])
                ->log('Disco de almacenamiento creado: '.$diskData['name']);

            return redirect()
                ->route('settings.storage.index')
                ->with('success', 'Disco de almacenamiento creado correctamente');
        } catch (\Exception $e) {
            Log::error('Error creating storage disk', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Error al crear el disco');
        }
    }

    /**
     * Show edit storage disk form
     */
    public function edit(string $name): View|RedirectResponse
    {
        abort_unless(auth()->user()->can('storage.update'), 403);

        $rawDisk = $this->findCustomDisk($name);
        $isFromConfig = $rawDisk === null && array_key_exists($name, config('filesystems.disks', []));

        if ($rawDisk === null && ! $isFromConfig) {
            return redirect()->route('settings.storage.index')->with('error', 'El disco solicitado no existe');
        }

        // For config disks, build a display-safe representation
        $disk = $rawDisk ?? $this->buildConfigDiskDisplay($name);

        return view('storage::edit', [
            'disk' => $disk,
            'diskName' => $name,
            'isFromConfig' => $isFromConfig,
            'driverOptions' => $this->driverOptions(),
            'pageTitle' => 'Editar disco de almacenamiento',
            'breadcrumb' => 'Configuración / Almacenamiento / Editar',
        ]);
    }

    /**
     * Update a specific storage disk
     */
    public function updateDisk(UpdateDiskRequest $request, string $name): RedirectResponse
    {
        // Load once — reuse for both the individual disk lookup and the full list update
        $existingDisks = $this->loadCustomDisks();
        $rawDisk = collect($existingDisks)->firstWhere('name', $name);
        $isFromConfig = $rawDisk === null && array_key_exists($name, config('filesystems.disks', []));

        if ($rawDisk === null && ! $isFromConfig) {
            return redirect()->route('settings.storage.index')->with('error', 'El disco solicitado no existe');
        }

        $validated = $request->validated();

        try {
            $diskData = $this->buildDiskData($validated, $rawDisk ?? []);

            if ($diskData === null) {
                return back()->withInput()->with('error', 'Tipo de almacenamiento no soportado');
            }

            if (is_array($diskData) && isset($diskData['error'])) {
                return back()->withInput()->with('error', $diskData['error']);
            }

            if ($isFromConfig) {
                $existingDisks[] = $diskData;
            } else {
                $realIndex = array_search($name, array_column($existingDisks, 'name'));
                if ($realIndex === false) {
                    return back()->withInput()->with('error', 'El disco fue eliminado por otro proceso');
                }
                $existingDisks[$realIndex] = $diskData;
            }

            $diskNames = array_column($existingDisks, 'name');
            if (count($diskNames) !== count(array_unique($diskNames))) {
                return back()->withInput()->with('error', 'No pueden existir dos discos con el mismo nombre');
            }

            $this->saveCustomDisks($existingDisks);

            activity('storage')->causedBy(auth()->user())
                ->withProperties(['disk' => $diskData['name'], 'driver' => $diskData['driver']])
                ->log('Disco de almacenamiento actualizado: '.$diskData['name']);

            return redirect()->route('settings.storage.index')->with('success', 'Disco actualizado correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating storage disk', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Error al actualizar el disco');
        }
    }

    /**
     * Delete a custom storage disk
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->can('storage.delete'), 403);

        $validated = $request->validate(['disk_name' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/']]);

        try {
            $customDisks = $this->loadCustomDisks();

            if (! collect($customDisks)->contains('name', $validated['disk_name'])) {
                return back()->with('error', 'El disco no existe o no puede ser eliminado');
            }

            $customDisks = array_values(array_filter(
                $customDisks,
                fn ($disk) => $disk['name'] !== $validated['disk_name']
            ));

            $this->saveCustomDisks($customDisks);

            activity('storage')->causedBy(auth()->user())
                ->withProperties(['disk' => $validated['disk_name']])
                ->log('Disco de almacenamiento eliminado: '.$validated['disk_name']);

            return redirect()->back()->with('success', 'Disco de almacenamiento eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error deleting storage disk', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al eliminar el disco');
        }
    }

    public function testConnection(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()->can('storage.create') || auth()->user()->can('storage.update'),
            403
        );

        $validated = $request->validate([
            'driver' => ['required', 'string', 'in:ftp,sftp,s3'],
            'host' => ['required_if:driver,ftp,sftp', 'nullable', 'string'],
            'username' => ['required_if:driver,ftp,sftp', 'nullable', 'string'],
            'password' => ['nullable', 'string'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'bucket' => ['required_if:driver,s3', 'nullable', 'string'],
            'key' => ['nullable', 'string'],
            'secret' => ['nullable', 'string'],
            'region' => ['required_if:driver,s3', 'nullable', 'string'],
        ]);

        try {
            $result = $this->verifyConnection($validated);

            if ($result['success']) {
                return response()->json(['success' => true, 'message' => 'Conexión exitosa']);
            }

            return response()->json(['success' => false, 'message' => $result['message']], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al verificar la conexión'], 422);
        }
    }

    private function verifyConnection(array $config): array
    {
        $driver = $config['driver'];
        $testDiskName = 'storage_test_'.uniqid();

        $diskConfig = match ($driver) {
            'ftp' => [
                'driver' => 'ftp',
                'host' => $config['host'],
                'username' => $config['username'],
                'password' => $config['password'] ?? '',
                'port' => (int) ($config['port'] ?? 21),
                'root' => '/',
                'passive' => true,
                'ssl' => false,
                'timeout' => 10,
                'throw' => true,
            ],
            'sftp' => [
                'driver' => 'sftp',
                'host' => $config['host'],
                'username' => $config['username'],
                'password' => $config['password'] ?? '',
                'port' => (int) ($config['port'] ?? 22),
                'root' => '/',
                'timeout' => 10,
                'throw' => true,
            ],
            's3' => [
                'driver' => 's3',
                'key' => $config['key'] ?? '',
                'secret' => $config['secret'] ?? '',
                'region' => $config['region'],
                'bucket' => $config['bucket'],
                'throw' => true,
            ],
            default => null,
        };

        if ($diskConfig === null) {
            return ['success' => false, 'message' => 'Driver no soportado para prueba de conexión'];
        }

        config(["filesystems.disks.{$testDiskName}" => $diskConfig]);

        try {
            Storage::disk($testDiskName)->exists('.connection_test');

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'No se pudo conectar: '.$e->getMessage()];
        } finally {
            $disks = config('filesystems.disks');
            unset($disks[$testDiskName]);
            config(['filesystems.disks' => $disks]);
        }
    }

    /**
     * Build disk data array from validated input.
     * Returns array with 'error' key on failure, null for unsupported driver.
     * Pass $existingRaw to preserve encrypted credentials when fields are left blank (for updates).
     */
    private function buildDiskData(array $validated, array $existingRaw = []): ?array
    {
        $driver = $validated['driver'];

        $extra = match ($driver) {
            'local' => $this->buildLocalDiskExtra($validated),
            'ftp', 'sftp' => [
                'host' => $validated['host'],
                'username' => $validated['username'],
                'password' => ! empty($validated['password'])
                    ? Crypt::encryptString($validated['password'])
                    : ($existingRaw['password'] ?? null),
                'port' => $validated['port'] ?? ($driver === 'ftp' ? 21 : 22),
            ],
            's3' => [
                'bucket' => $validated['bucket'],
                'region' => $validated['region'],
                'key' => ! empty($validated['key'])
                    ? Crypt::encryptString($validated['key'])
                    : ($existingRaw['key'] ?? null),
                'secret' => ! empty($validated['secret'])
                    ? Crypt::encryptString($validated['secret'])
                    : ($existingRaw['secret'] ?? null),
            ],
            default => null,
        };

        if ($extra === null) {
            return null;
        }

        if (isset($extra['error'])) {
            return $extra;
        }

        return array_merge(['name' => $validated['name'], 'driver' => $driver], $extra);
    }

    /**
     * Build extra config for a local disk, validating the path.
     * Returns ['error' => ...] on failure.
     */
    private function buildLocalDiskExtra(array $validated): array
    {
        $pathInfo = $this->generateStoragePath($validated['name'], $validated['storage_type']);
        if (! $pathInfo['success']) {
            return ['error' => $pathInfo['message']];
        }

        $dirCheck = $this->validateAndPrepareLocalDisk($pathInfo['root']);
        if (! $dirCheck['success']) {
            return ['error' => $dirCheck['message']];
        }

        return [
            'root' => $pathInfo['root'],
            'url' => $pathInfo['url'],
            'storage_type' => $validated['storage_type'],
        ];
    }

    /**
     * Generate storage path and URL based on disk name and storage type
     */
    private function generateStoragePath(string $diskName, string $storageType): array
    {
        if ($storageType === 'public') {
            return [
                'success' => true,
                'root' => storage_path('app/public/'.$diskName),
                'url' => '/storage/'.$diskName,
            ];
        }

        if ($storageType === 'private') {
            return ['success' => true, 'root' => storage_path($diskName), 'url' => null];
        }

        return ['success' => false, 'message' => 'Tipo de almacenamiento no válido'];
    }

    /**
     * Validate and prepare a local storage directory
     */
    private function validateAndPrepareLocalDisk(string $rootPath): array
    {
        if (empty(trim($rootPath))) {
            return ['success' => false, 'message' => 'La ruta no puede estar vacía.'];
        }

        if (str_contains($rootPath, '..')) {
            return ['success' => false, 'message' => 'La ruta no puede contener segmentos relativos (..)'];
        }

        $dangerousPaths = ['/', '/bin', '/boot', '/dev', '/etc', '/lib', '/root', '/sbin', '/sys', '/usr', '/var'];
        if (in_array($rootPath, $dangerousPaths) || in_array(rtrim($rootPath, '/'), $dangerousPaths)) {
            return [
                'success' => false,
                'message' => "La ruta '{$rootPath}' no es permitida por razones de seguridad.",
            ];
        }

        $rootPath = rtrim($rootPath, '/');

        if (! str_starts_with($rootPath, '/')) {
            return ['success' => false, 'message' => 'La ruta debe ser absoluta (debe comenzar con /).'];
        }

        $pathParts = array_values(array_filter(explode('/', $rootPath)));
        if (count($pathParts) === 1) {
            return [
                'success' => false,
                'message' => "No se permiten directorios directamente bajo raíz como /{$pathParts[0]}. Usa rutas como /mnt/storage.",
            ];
        }

        if (preg_match('/\$\{.*\}/', $rootPath) || preg_match('/`.*`/', $rootPath)) {
            return ['success' => false, 'message' => 'La ruta contiene caracteres no permitidos'];
        }

        if (! is_dir($rootPath)) {
            $parentDir = dirname($rootPath);

            if (! is_dir($parentDir)) {
                @mkdir($parentDir, 0755, true);
                if (! is_dir($parentDir)) {
                    return ['success' => false, 'message' => "No se pudo crear el directorio padre: {$parentDir}"];
                }
                Log::info('Storage parent directory created', ['path' => $parentDir]);
            }

            if (! is_writable($parentDir)) {
                return ['success' => false, 'message' => "Sin permisos de escritura en: {$parentDir}"];
            }

            @mkdir($rootPath, 0755, true);
            if (! is_dir($rootPath)) {
                return ['success' => false, 'message' => "No se pudo crear el directorio: {$rootPath}"];
            }

            Log::info('Storage directory created', ['path' => $rootPath]);
        }

        // Ensure git tracking files exist regardless of whether we just created the directory
        if (! file_exists($rootPath.'/.gitignore')) {
            if (file_put_contents($rootPath.'/.gitignore', "*\n!.gitignore\n!.gitkeep\n") === false) {
                Log::warning('Could not write .gitignore in storage directory', ['path' => $rootPath]);
            }
        }
        if (! file_exists($rootPath.'/.gitkeep')) {
            if (file_put_contents($rootPath.'/.gitkeep', '') === false) {
                Log::warning('Could not write .gitkeep in storage directory', ['path' => $rootPath]);
            }
        }

        if (! is_readable($rootPath)) {
            return ['success' => false, 'message' => "El directorio no es legible: {$rootPath}."];
        }

        if (! is_writable($rootPath)) {
            return ['success' => false, 'message' => "El directorio no es escribible: {$rootPath}."];
        }

        return ['success' => true, 'message' => 'Directorio configurado correctamente', 'path' => $rootPath];
    }

    /**
     * Load custom disks from database
     */
    private function loadCustomDisks(): array
    {
        $raw = Setting::get('system.custom_storage_disks', '[]');

        if (! is_string($raw)) {
            Log::error('Custom storage disks setting has unexpected type', ['type' => gettype($raw)]);

            return [];
        }

        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Custom storage disks JSON is malformed', ['error' => json_last_error_msg()]);

            return [];
        }

        return $decoded ?: [];
    }

    /**
     * Find a single custom disk by name (raw, with encrypted credentials)
     */
    private function findCustomDisk(string $name): ?array
    {
        return collect($this->loadCustomDisks())->firstWhere('name', $name);
    }

    /**
     * Build a display-safe representation of a config-file disk for the edit form
     */
    private function buildConfigDiskDisplay(string $name): array
    {
        return $this->buildDiskFromConfig($name, config("filesystems.disks.{$name}", []), false);
    }

    /**
     * Build a display-safe disk array from a config entry.
     * When $maskCredentials is true, credentials are replaced with placeholders (for the index table).
     * When false, credential fields are omitted (for the edit form — user must re-enter them).
     */
    private function buildDiskFromConfig(string $name, array $config, bool $maskCredentials): array
    {
        $driver = $config['driver'] ?? 'unknown';
        $disk = ['name' => $name, 'driver' => $driver, 'from_config' => true];

        if ($driver === 'local') {
            $disk['root'] = $config['root'] ?? '';
            $disk['url'] = $config['url'] ?? '';
        } elseif (in_array($driver, ['ftp', 'sftp'])) {
            $disk['host'] = $config['host'] ?? '';
            $disk['port'] = $config['port'] ?? '';
            $disk['username'] = $config['username'] ?? '';
            if ($maskCredentials) {
                $disk['password'] = '********';
            }
        } elseif ($driver === 's3') {
            $disk['bucket'] = $config['bucket'] ?? '';
            $disk['region'] = $config['region'] ?? '';
            $disk['key'] = $maskCredentials ? '••••••••' : '';
            if ($maskCredentials) {
                $disk['secret'] = '********';
            }
        }

        return $disk;
    }

    /**
     * Save custom disks to database and clear cache
     */
    private function saveCustomDisks(array $disks): void
    {
        Setting::set('system.custom_storage_disks', json_encode($disks));
        Cache::forget('storage.custom_disks');
    }

    /**
     * Get storage data from configuration
     */
    private function getStorageData(): array
    {
        $coreDisks = ['local', 'public'];
        $disksConfig = config('filesystems.disks');
        $configDisks = [];

        // DB disks are registered into config() by the ServiceProvider at boot time.
        // Exclude them here to avoid showing each DB disk twice (once as "config" and once as "DB").
        $customDisks = $this->loadCustomDisks();
        $dbDiskNames = array_column($customDisks, 'name');

        foreach ($disksConfig as $diskName => $diskConfig) {
            if (in_array($diskName, $coreDisks) || in_array($diskName, $dbDiskNames)) {
                continue;
            }

            $configDisks[] = $this->buildDiskFromConfig($diskName, $diskConfig, true);
        }

        foreach ($customDisks as &$disk) {
            $disk['from_config'] = false;

            if (! empty($disk['password'])) {
                $disk['password'] = '••••••••';
            }
            if (! empty($disk['secret'])) {
                $disk['secret'] = '••••••••';
            }
        }
        unset($disk);

        return [
            'system_disks' => $this->getSystemDisks(),
            'custom_disks' => array_merge($configDisks, $customDisks),
            'driver_options' => $this->driverOptions(),
        ];
    }

    /**
     * Get system (core) disks
     */
    private function getSystemDisks(): array
    {
        $disksConfig = config('filesystems.disks');
        $systemDisks = [];

        foreach (['local', 'public'] as $diskName) {
            if (! isset($disksConfig[$diskName])) {
                continue;
            }

            $diskConfig = $disksConfig[$diskName];
            $driver = $diskConfig['driver'] ?? 'unknown';
            $root = $diskConfig['root'] ?? 'N/A';

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
                'url' => $diskConfig['url'] ?? null,
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
        $driverCounts = ['local' => 0, 'ftp' => 0, 'sftp' => 0, 's3' => 0];

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
            'custom_db' => $customCount - $customConfigCount,
            'driver_counts' => $driverCounts,
        ];
    }

    /**
     * Available driver options for the UI
     */
    private function driverOptions(): array
    {
        return [
            'local' => 'Almacenamiento Local',
            'ftp' => 'FTP',
            'sftp' => 'SFTP',
            's3' => 'Amazon S3',
        ];
    }
}
