<?php

namespace Modules\Modules\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\Modules\Services\ModuleConfigReader;
use Nwidart\Modules\Facades\Module;
use ZipArchive;

class ModulesController extends Controller
{
    /**
     * Display a listing of all modules with their status.
     */
    public function index(Request $request)
    {
        $this->authorize('Modules.manage');

        $modules = [];

        foreach (Module::all() as $module) {
            $moduleConfig = $this->getModuleConfig($module);

            $modules[] = [
                'name' => $module->getName(),
                'alias' => $moduleConfig['alias'] ?? strtolower($module->getName()),
                'description' => $moduleConfig['description'] ?? '',
                'version' => $moduleConfig['version'] ?? '1.0.0',
                'enabled' => $module->isEnabled(),
                'disabled' => $module->isDisabled(),
                'priority' => $moduleConfig['priority'] ?? 0,
                'path' => $module->getPath(),
                'namespace' => $moduleConfig['namespace'] ?? 'Modules\\'.ucfirst($module->getName()),
            ];
        }

        $modulesCollection = collect($modules);

        // Apply search filter
        if ($search = $request->get('search')) {
            $modulesCollection = $modulesCollection->filter(function ($module) use ($search) {
                $searchLower = strtolower($search);

                return str_contains(strtolower($module['name']), $searchLower) ||
                       str_contains(strtolower($module['alias']), $searchLower) ||
                       str_contains(strtolower($module['description']), $searchLower);
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $modulesCollection = $modulesCollection->filter(function ($module) use ($status) {
                if ($status === 'enabled') {
                    return $module['enabled'];
                } elseif ($status === 'disabled') {
                    return $module['disabled'];
                }

                return true;
            });
        }

        $filteredModules = $modulesCollection->values()->all();

        $allModules = collect($modules);
        $enabledCount = $allModules->filter(fn ($m) => $m['enabled'])->count();
        $disabledCount = $allModules->filter(fn ($m) => $m['disabled'])->count();

        return view('modules::index', [
            'modules' => $filteredModules,
            'totalModules' => count($modules),
            'enabledCount' => $enabledCount,
            'disabledCount' => $disabledCount,
        ]);
    }

    /**
     * Show details of a specific module.
     */
    public function show(string $moduleAlias)
    {
        $this->authorize('Modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        $moduleConfig = $this->getModuleConfig($module);

        $moduleData = [
            'name' => $module->getName(),
            'alias' => $moduleConfig['alias'] ?? strtolower($module->getName()),
            'description' => $moduleConfig['description'] ?? '',
            'version' => $moduleConfig['version'] ?? '1.0.0',
            'enabled' => $module->isEnabled(),
            'disabled' => $module->isDisabled(),
            'priority' => $moduleConfig['priority'] ?? 0,
            'path' => $module->getPath(),
            'namespace' => $moduleConfig['namespace'] ?? 'Modules\\'.ucfirst($module->getName()),
            'providers' => $moduleConfig['providers'] ?? [],
            'aliases' => $moduleConfig['aliases'] ?? [],
            'keywords' => $moduleConfig['keywords'] ?? [],
        ];

        return view('modules::show', ['module' => $moduleData]);
    }

    /**
     * Show the edit form for a module.
     */
    public function edit(string $moduleAlias)
    {
        $this->authorize('Modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        $moduleConfig = $this->getModuleConfig($module);

        $moduleData = [
            'name' => $module->getName(),
            'alias' => $moduleConfig['alias'] ?? strtolower($module->getName()),
            'description' => $moduleConfig['description'] ?? '',
            'version' => $moduleConfig['version'] ?? '1.0.0',
            'enabled' => $module->isEnabled(),
            'disabled' => $module->isDisabled(),
            'priority' => $moduleConfig['priority'] ?? 0,
            'path' => $module->getPath(),
            'namespace' => $moduleConfig['namespace'] ?? 'Modules\\'.ucfirst($module->getName()),
            'providers' => $moduleConfig['providers'] ?? [],
            'aliases' => $moduleConfig['aliases'] ?? [],
            'keywords' => $moduleConfig['keywords'] ?? [],
        ];

        return view('modules::edit', ['module' => $moduleData]);
    }

    /**
     * Update a module's configuration.
     */
    public function update(Request $request, string $moduleAlias)
    {
        $this->authorize('Modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        $validated = $request->validate([
            'priority' => 'required|integer|min:0|max:999',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $configPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';

            if (! file_exists($configPath)) {
                return redirect()->route('settings.modules.edit', $module->getName())
                    ->with('error', 'Module configuration file not found.');
            }

            $config = json_decode(file_get_contents($configPath), true);
            $config['priority'] = (int) $validated['priority'];
            $config['description'] = $validated['description'] ?? '';

            file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return redirect()->route('settings.modules.show', $module->getName())
                ->with('success', "Module '{$module->getName()}' updated successfully.");
        } catch (\Exception $e) {
            Log::error('Module update failed', ['module' => $moduleAlias, 'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.']);

            return redirect()->route('settings.modules.edit', $module->getName())
                ->with('error', 'Error al actualizar el módulo.');
        }
    }

    /**
     * Enable a module.
     */
    public function enable(string $moduleAlias)
    {
        $this->authorize('Modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        try {
            $module->enable();
            $this->clearApplicationCache();

            return redirect()->route('settings.modules.index')
                ->with('success', "Módulo '{$module->getName()}' habilitado correctamente.");
        } catch (\Exception $e) {
            Log::error('Module enable failed', ['module' => $moduleAlias, 'error' => $e->getMessage()]);

            return redirect()->route('settings.modules.index')
                ->with('error', 'No se pudo habilitar el módulo.');
        }
    }

    /**
     * Disable a module.
     */
    public function disable(string $moduleAlias)
    {
        $this->authorize('Modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        // Prevent disabling core modules
        $coreModules = ['Role', 'Modules'];
        if (in_array($module->getName(), $coreModules)) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Cannot disable core module '{$module->getName()}'.");
        }

        try {
            $module->disable();
            $this->clearApplicationCache();

            return redirect()->route('settings.modules.index')
                ->with('success', "Módulo '{$module->getName()}' deshabilitado correctamente.");
        } catch (\Exception $e) {
            Log::error('Module disable failed', ['module' => $moduleAlias, 'error' => $e->getMessage()]);

            return redirect()->route('settings.modules.index')
                ->with('error', 'No se pudo deshabilitar el módulo.');
        }
    }

    /**
     * Show the upload form for installing a new module.
     */
    public function uploadForm()
    {
        $this->authorize('Modules.manage');

        return view('modules::upload');
    }

    /**
     * Install a module from an uploaded ZIP file.
     */
    public function install(Request $request)
    {
        $this->authorize('Modules.manage');

        $request->validate([
            'module_file' => 'required|file|mimes:zip|max:51200',
        ], [
            'module_file.required' => 'Please select a module file to upload.',
            'module_file.mimes' => 'The module file must be a ZIP file.',
            'module_file.max' => 'The module file must not exceed 50 MB.',
        ]);

        $tempPath = storage_path('temp/modules');

        try {
            $file = $request->file('module_file');
            $modulesPath = base_path('Modules');

            // Create temp directory if it doesn't exist
            if (! is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            // Extract ZIP file
            $zip = new ZipArchive;
            if ($zip->open($file->getPathname()) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if (str_contains($entry, '..')) {
                        $zip->close();
                        throw new \Exception('Archivo ZIP inválido: se detectó un intento de path traversal.');
                    }
                }

                $zip->extractTo($tempPath);
                $zip->close();

                // Find the module directory (usually the first directory in the ZIP)
                $extracted = array_diff(scandir($tempPath), ['.', '..']);
                $moduleDir = current($extracted);

                if (! is_dir("$tempPath/$moduleDir")) {
                    $subDirs = array_filter(scandir("$tempPath/$moduleDir"), fn ($item) => is_dir("$tempPath/$moduleDir/$item") && ! str_starts_with($item, '.'));
                    if (! empty($subDirs)) {
                        $moduleDir = "$moduleDir/".current($subDirs);
                    }
                }

                // Check if module.json exists
                if (! file_exists("$tempPath/$moduleDir/module.json")) {
                    throw new \Exception('Invalid module: module.json not found.');
                }

                // Parse module.json to get module name
                $moduleConfig = json_decode(file_get_contents("$tempPath/$moduleDir/module.json"), true);
                $moduleName = preg_replace('/[^A-Za-z0-9_-]/', '', $moduleConfig['name'] ?? basename($moduleDir));

                if (empty($moduleName)) {
                    throw new \Exception('Nombre de módulo inválido en module.json.');
                }

                // Check if module already exists
                if (is_dir("$modulesPath/$moduleName")) {
                    throw new \Exception("Module '$moduleName' already exists.");
                }

                // Move module to modules directory
                rename("$tempPath/$moduleDir", "$modulesPath/$moduleName");

                // Clean up temp directory
                $this->deleteDirectory($tempPath);

                // Run the module's migrations
                Artisan::call('module:migrate', ['module' => $moduleName, '--force' => true]);

                $this->clearApplicationCache();

                return redirect()->route('settings.modules.index')
                    ->with('success', "Módulo '$moduleName' instalado correctamente.");
            } else {
                throw new \Exception('Failed to extract ZIP file.');
            }
        } catch (\Exception $e) {
            $this->deleteDirectory($tempPath);

            Log::error('Module install failed', ['error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.']);

            return redirect()->route('settings.modules.uploadForm')
                ->with('error', 'La instalación falló. Verifica que el archivo sea un módulo válido.');
        }
    }

    /**
     * Uninstall (delete) a module.
     */
    public function uninstall(string $moduleAlias)
    {
        $this->authorize('Modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        // Prevent uninstalling core modules
        $coreModules = ['Role', 'Modules'];
        if (in_array($module->getName(), $coreModules)) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Cannot uninstall core module '{$module->getName()}'.");
        }

        try {
            // Roll back the module's migrations before deleting files
            Artisan::call('module:migrate-rollback', ['module' => $module->getName(), '--force' => true]);

            if ($module->isEnabled()) {
                $module->disable();
            }

            $this->deleteDirectory($module->getPath());
            $this->clearApplicationCache();

            return redirect()->route('settings.modules.index')
                ->with('success', "Módulo '{$module->getName()}' desinstalado correctamente.");
        } catch (\Exception $e) {
            Log::error('Module uninstall failed', ['module' => $moduleAlias, 'error' => $e->getMessage()]);

            return redirect()->route('settings.modules.index')
                ->with('error', 'No se pudo desinstalar el módulo.');
        }
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            return @unlink($path);
        }

        foreach (scandir($path) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (! $this->deleteDirectory($path.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return @rmdir($path);
    }

    private function clearApplicationCache(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    private function getModuleConfig($module): array
    {
        return ModuleConfigReader::read($module);
    }
}
