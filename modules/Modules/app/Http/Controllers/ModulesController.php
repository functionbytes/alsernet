<?php

namespace Modules\Modules\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;
use ZipArchive;

class ModulesController extends Controller
{
    /**
     * Display a listing of all modules with their status.
     */
    public function index(Request $request)
    {
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

        // Calculate stats
        $enabled = collect($modules)->filter(fn ($m) => $m['enabled'])->all();
        $disabled = collect($modules)->filter(fn ($m) => $m['disabled'])->all();

        return view('modules::index', [
            'modules' => $filteredModules,
            'enabledModules' => $enabled,
            'disabledModules' => $disabled,
            'totalModules' => count($modules),
            'enabledCount' => count($enabled),
            'disabledCount' => count($disabled),
        ]);
    }

    /**
     * Show details of a specific module.
     */
    public function show(string $moduleAlias)
    {
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
        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        // Validate input
        $validated = $request->validate([
            'priority' => 'required|integer|min:0|max:999',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            // Update module.json with new priority and description
            $configPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';

            if (file_exists($configPath)) {
                $config = json_decode(file_get_contents($configPath), true);

                $config['priority'] = (int) $validated['priority'];
                if (! empty($validated['description'])) {
                    $config['description'] = $validated['description'];
                }

                file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return redirect()->route('settings.modules.show', $module->getName())
                    ->with('success', "Module '{$module->getName()}' updated successfully.");
            } else {
                return redirect()->route('settings.modules.edit', $module->getName())
                    ->with('error', 'Module configuration file not found.');
            }
        } catch (\Exception $e) {
            return redirect()->route('settings.modules.edit', $module->getName())
                ->with('error', "Error updating module: {$e->getMessage()}");
        }
    }

    /**
     * Enable a module.
     */
    public function enable(string $moduleAlias)
    {
        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Module '{$moduleAlias}' not found.");
        }

        try {
            $module->enable();

            return redirect()->route('settings.modules.index')
                ->with('success', "Module '{$module->getName()}' has been enabled successfully.");
        } catch (\Exception $e) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Failed to enable module: {$e->getMessage()}");
        }
    }

    /**
     * Disable a module.
     */
    public function disable(string $moduleAlias)
    {
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

            return redirect()->route('settings.modules.index')
                ->with('success', "Module '{$module->getName()}' has been disabled successfully.");
        } catch (\Exception $e) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Failed to disable module: {$e->getMessage()}");
        }
    }

    /**
     * Show the upload form for installing a new module.
     */
    public function uploadForm()
    {
        return view('modules::upload');
    }

    /**
     * Install a module from an uploaded ZIP file.
     */
    public function install(Request $request)
    {
        $request->validate([
            'module_file' => 'required|file|mimes:zip',
        ], [
            'module_file.required' => 'Please select a module file to upload.',
            'module_file.mimes' => 'The module file must be a ZIP file.',
        ]);

        try {
            $file = $request->file('module_file');
            $modulesPath = base_path('Modules');
            $tempPath = storage_path('temp/modules');

            // Create temp directory if it doesn't exist
            if (! is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            // Extract ZIP file
            $zip = new ZipArchive;
            if ($zip->open($file->getPathname()) === true) {
                $zip->extractTo($tempPath);
                $zip->close();

                // Find the module directory (usually the first directory in the ZIP)
                $extracted = array_diff(scandir($tempPath), ['.', '..']);
                $moduleDir = current($extracted);

                if (! is_dir("$tempPath/$moduleDir")) {
                    // If extracted to subdirectory
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
                $moduleName = $moduleConfig['name'] ?? basename($moduleDir);

                // Check if module already exists
                if (is_dir("$modulesPath/$moduleName")) {
                    throw new \Exception("Module '$moduleName' already exists.");
                }

                // Move module to modules directory
                rename("$tempPath/$moduleDir", "$modulesPath/$moduleName");

                // Clean up temp directory
                array_map('unlink', glob("$tempPath/*.*"));
                rmdir($tempPath);

                return redirect()->route('settings.modules.index')
                    ->with('success', "Module '$moduleName' has been installed successfully.");
            } else {
                throw new \Exception('Failed to extract ZIP file.');
            }
        } catch (\Exception $e) {
            return redirect()->route('settings.modules.uploadForm')
                ->with('error', "Installation failed: {$e->getMessage()}");
        }
    }

    /**
     * Uninstall (delete) a module.
     */
    public function uninstall(string $moduleAlias)
    {
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
            // First disable the module
            if ($module->isEnabled()) {
                $module->disable();
            }

            // Delete the module directory
            $modulePath = $module->getPath();
            $this->deleteDirectory($modulePath);

            return redirect()->route('settings.modules.index')
                ->with('success', "Module '{$module->getName()}' has been uninstalled successfully.");
        } catch (\Exception $e) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Failed to uninstall module: {$e->getMessage()}");
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

    /**
     * Get module configuration from module.json.
     */
    private function getModuleConfig($module): array
    {
        try {
            $configPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';
            if (file_exists($configPath)) {
                return json_decode(file_get_contents($configPath), true) ?? [];
            }
        } catch (\Exception $e) {
            // Return empty array if config cannot be read
        }

        return [];
    }
}
