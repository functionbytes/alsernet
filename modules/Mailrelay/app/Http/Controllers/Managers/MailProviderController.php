<?php

namespace Modules\Mailrelay\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Services\ProviderManager;

/**
 * Controlador para gestión de Mail Providers
 * Unifica la configuración de todos los providers de email (Mailrelay, Mailtrap, etc.)
 */
class MailProviderController extends Controller
{
    protected ProviderManager $providerManager;

    public function __construct(ProviderManager $providerManager)
    {
        $this->providerManager = $providerManager;
    }

    /**
     * Listar todos los providers
     */
    public function index()
    {
        $providers = MailProvider::orderBy('is_default', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();

        $availableDrivers = $this->providerManager->availableDrivers();

        return view('mailrelay::providers.index', [
            'providers' => $providers,
            'availableDrivers' => $availableDrivers,
            'pageTitle' => 'Providers de Email',
            'breadcrumb' => 'Configuración / Email / Providers',
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $availableDrivers = collect($this->providerManager->availableDrivers())
            ->map(function ($driver) {
                return [
                    'value' => $driver,
                    'label' => $this->providerManager->getDriverInfo($driver)['name'],
                    'info' => $this->providerManager->getDriverInfo($driver),
                ];
            });

        return view('mailrelay::providers.create', [
            'availableDrivers' => $availableDrivers,
            'pageTitle' => 'Nuevo Provider de Email',
            'breadcrumb' => 'Configuración / Email / Providers / Nuevo',
        ]);
    }

    /**
     * Guardar nuevo provider
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:mailrelay,mailtrap',
            'credentials' => 'required|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        try {
            // Validar credenciales antes de guardar
            $validationResult = $this->providerManager->validateCredentials(
                $validated['driver'],
                $validated['credentials']
            );

            $provider = MailProvider::create([
                'name' => $validated['name'],
                'driver' => $validated['driver'],
                'credentials' => $validated['credentials'],
                'is_active' => $validated['is_active'] ?? true,
                'is_default' => $validated['is_default'] ?? false,
                'priority' => $validated['priority'] ?? 0,
                'connection_ok' => $validationResult['valid'],
                'last_tested_at' => now(),
            ]);

            return redirect()
                ->route('managers.mailrelay.providers.index')
                ->with('success', "Provider '{$provider->name}' creado exitosamente");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear provider: '.$e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(int $id)
    {
        $provider = MailProvider::findOrFail($id);

        $driverInfo = $this->providerManager->getDriverInfo($provider->driver);

        return view('mailrelay::providers.edit', [
            'provider' => $provider,
            'driverInfo' => $driverInfo,
            'pageTitle' => 'Editar Provider',
            'breadcrumb' => 'Configuración / Email / Providers / Editar',
        ]);
    }

    /**
     * Actualizar provider
     */
    public function update(Request $request, int $id)
    {
        $provider = MailProvider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'credentials' => 'required|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        try {
            $provider->update([
                'name' => $validated['name'],
                'credentials' => $validated['credentials'],
                'is_active' => $validated['is_active'] ?? $provider->is_active,
                'is_default' => $validated['is_default'] ?? $provider->is_default,
                'priority' => $validated['priority'] ?? $provider->priority,
            ]);

            return redirect()
                ->route('managers.mailrelay.providers.index')
                ->with('success', "Provider '{$provider->name}' actualizado exitosamente");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar provider: '.$e->getMessage());
        }
    }

    /**
     * Eliminar provider
     */
    public function destroy(int $id)
    {
        $provider = MailProvider::findOrFail($id);

        // Verificar que no sea el provider por defecto
        if ($provider->is_default) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar el provider por defecto');
        }

        // Verificar que no tenga campaigns asociadas
        if ($provider->campaigns()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar un provider con campaigns asociadas');
        }

        $name = $provider->name;
        $provider->delete();

        return redirect()
            ->route('managers.mailrelay.providers.index')
            ->with('success', "Provider '{$name}' eliminado exitosamente");
    }

    /**
     * Test de conexión del provider
     */
    public function test(int $id)
    {
        try {
            $result = $this->providerManager->testProvider($id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar provider como default
     */
    public function setDefault(int $id)
    {
        $provider = MailProvider::findOrFail($id);

        if (! $provider->is_active) {
            return redirect()
                ->back()
                ->with('error', 'No se puede marcar como default un provider inactivo');
        }

        $provider->update(['is_default' => true]);

        return redirect()
            ->route('managers.mailrelay.providers.index')
            ->with('success', "Provider '{$provider->name}' marcado como default");
    }

    /**
     * Toggle estado activo/inactivo
     */
    public function toggleActive(int $id)
    {
        $provider = MailProvider::findOrFail($id);

        // No permitir desactivar el provider por defecto
        if ($provider->is_default && $provider->is_active) {
            return redirect()
                ->back()
                ->with('error', 'No se puede desactivar el provider por defecto. Selecciona otro provider como default primero.');
        }

        $provider->update(['is_active' => ! $provider->is_active]);

        $status = $provider->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->route('managers.mailrelay.providers.index')
            ->with('success', "Provider '{$provider->name}' {$status}");
    }
}
