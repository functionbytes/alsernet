<?php

namespace Modules\Modules\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Modules\Http\Requests\InstallModuleRequest;
use Modules\Modules\Http\Requests\UpdateModuleRequest;
use Modules\Modules\Services\ModuleConfigReader;
use Modules\Modules\Services\ModuleService;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Module as ModuleInstance;

class ModulesController extends Controller
{
    public function __construct(
        private readonly ModuleService $moduleService,
        private readonly ModuleConfigReader $moduleConfigReader,
    ) {}

    public function index(): View
    {
        $this->authorize('modules.manage');

        $modules = [];

        foreach (Module::all() as $module) {
            $config = $this->moduleConfigReader->read($module);
            $modules[] = $this->buildModuleData($module, $config);
        }

        $allModules = collect($modules);
        $search = request('search');
        $status = request('status');

        $filtered = $allModules
            ->when($search, function ($col) use ($search) {
                $lower = strtolower($search);

                return $col->filter(fn ($m) => str_contains(strtolower($m['name']), $lower) ||
                    str_contains(strtolower($m['alias']), $lower) ||
                    str_contains(strtolower($m['description']), $lower)
                );
            })
            ->when($status === 'enabled', fn ($col) => $col->filter(fn ($m) => $m['enabled']))
            ->when($status === 'disabled', fn ($col) => $col->filter(fn ($m) => $m['disabled']))
            ->values()
            ->all();

        return view('modules::index', [
            'modules' => $filtered,
            'totalModules' => count($modules),
            'enabledCount' => $allModules->filter(fn ($m) => $m['enabled'])->count(),
            'disabledCount' => $allModules->filter(fn ($m) => $m['disabled'])->count(),
        ]);
    }

    public function show(string $moduleAlias): View|RedirectResponse
    {
        $this->authorize('modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Módulo '{$moduleAlias}' no encontrado.");
        }

        $config = $this->moduleConfigReader->read($module);

        return view('modules::show', [
            'module' => $this->buildModuleData($module, $config, full: true),
        ]);
    }

    public function edit(string $moduleAlias): View|RedirectResponse
    {
        $this->authorize('modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Módulo '{$moduleAlias}' no encontrado.");
        }

        $config = $this->moduleConfigReader->read($module);

        return view('modules::edit', [
            'module' => $this->buildModuleData($module, $config, full: true),
        ]);
    }

    public function update(UpdateModuleRequest $request, string $moduleAlias): RedirectResponse
    {
        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Módulo '{$moduleAlias}' no encontrado.");
        }

        try {
            $validated = $request->validated();
            $this->moduleService->updateConfig($module->getPath(), (int) $validated['priority'], $validated['description'] ?? null);

            return redirect()->route('settings.modules.show', $module->getName())
                ->with('success', "Módulo '{$module->getName()}' actualizado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al actualizar módulo', ['module' => $moduleAlias, 'error' => $e->getMessage()]);

            return redirect()->route('settings.modules.edit', $module->getName())
                ->with('error', 'Error al actualizar el módulo.');
        }
    }

    public function enable(string $moduleAlias): RedirectResponse
    {
        $this->authorize('modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Módulo '{$moduleAlias}' no encontrado.");
        }

        try {
            $module->enable();
            $this->moduleService->clearCache();

            return redirect()->route('settings.modules.index')
                ->with('success', "Módulo '{$module->getName()}' habilitado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al habilitar módulo', ['module' => $moduleAlias, 'error' => $e->getMessage()]);

            return redirect()->route('settings.modules.index')
                ->with('error', 'No se pudo habilitar el módulo.');
        }
    }

    public function disable(string $moduleAlias): RedirectResponse
    {
        $this->authorize('modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Módulo '{$moduleAlias}' no encontrado.");
        }

        $coreModules = ['Role', 'Modules'];
        if (in_array($module->getName(), $coreModules)) {
            return redirect()->route('settings.modules.index')
                ->with('error', "No se puede deshabilitar el módulo principal '{$module->getName()}'.");
        }

        try {
            $module->disable();
            $this->moduleService->clearCache();

            return redirect()->route('settings.modules.index')
                ->with('success', "Módulo '{$module->getName()}' deshabilitado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al deshabilitar módulo', ['module' => $moduleAlias, 'error' => $e->getMessage()]);

            return redirect()->route('settings.modules.index')
                ->with('error', 'No se pudo deshabilitar el módulo.');
        }
    }

    public function uploadForm(): View
    {
        $this->authorize('modules.manage');

        return view('modules::upload');
    }

    public function install(InstallModuleRequest $request): RedirectResponse
    {
        try {
            $moduleName = $this->moduleService->install($request->file('module_file'));

            return redirect()->route('settings.modules.index')
                ->with('success', "Módulo '$moduleName' instalado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al instalar módulo', ['error' => $e->getMessage()]);

            return redirect()->route('settings.modules.uploadForm')
                ->with('error', 'La instalación falló: '.$e->getMessage());
        }
    }

    public function uninstall(string $moduleAlias): RedirectResponse
    {
        $this->authorize('modules.manage');

        $module = Module::find($moduleAlias);

        if (! $module) {
            return redirect()->route('settings.modules.index')
                ->with('error', "Módulo '{$moduleAlias}' no encontrado.");
        }

        $coreModules = ['Role', 'Modules'];
        if (in_array($module->getName(), $coreModules)) {
            return redirect()->route('settings.modules.index')
                ->with('error', "No se puede desinstalar el módulo principal '{$module->getName()}'.");
        }

        try {
            Artisan::call('module:migrate-rollback', ['module' => $module->getName(), '--force' => true]);

            if ($module->isEnabled()) {
                $module->disable();
            }

            $this->moduleService->deleteDirectory($module->getPath());
            $this->moduleService->clearCache();

            return redirect()->route('settings.modules.index')
                ->with('success', "Módulo '{$module->getName()}' desinstalado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al desinstalar módulo', ['module' => $moduleAlias, 'error' => $e->getMessage()]);

            return redirect()->route('settings.modules.index')
                ->with('error', 'No se pudo desinstalar el módulo.');
        }
    }

    /**
     * Build the module data array from a Module instance and its config.
     */
    private function buildModuleData(ModuleInstance $module, array $config, bool $full = false): array
    {
        $data = [
            'name' => $module->getName(),
            'alias' => $config['alias'] ?? strtolower($module->getName()),
            'description' => $config['description'] ?? '',
            'version' => $config['version'] ?? '1.0.0',
            'enabled' => $module->isEnabled(),
            'disabled' => $module->isDisabled(),
            'priority' => $config['priority'] ?? 0,
            'path' => $module->getPath(),
            'namespace' => $config['namespace'] ?? 'Modules\\'.ucfirst($module->getName()),
        ];

        if ($full) {
            $data['providers'] = $config['providers'] ?? [];
            $data['aliases'] = $config['aliases'] ?? [];
            $data['keywords'] = $config['keywords'] ?? [];
        }

        return $data;
    }
}
