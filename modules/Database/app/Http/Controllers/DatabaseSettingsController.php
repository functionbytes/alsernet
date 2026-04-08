<?php

namespace Modules\Database\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Database\Services\DatabaseConnectionTester;

class DatabaseSettingsController extends Controller
{
    public function __construct(
        private readonly DatabaseConnectionTester $tester
    ) {}

    /**
     * Display database backups form
     */
    public function index(): View
    {
        $settings = Setting::getDatabaseSettings();
        $pageTitle = 'Configuración de base de datos';
        $breadcrumb = 'Configuración / Base de datos';

        return view('database::settings.index', compact('settings', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Show database edit form
     */
    public function edit(): View
    {
        $settings = Setting::getDatabaseSettings();
        $rules = Setting::getDatabaseRules();
        $pageTitle = 'Editar base de datos';
        $breadcrumb = 'Configuración / Base de datos';

        return view('database::settings.edit', compact('settings', 'rules', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Update database backups
     */
    public function update(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate(Setting::getDatabaseRules());
            $validated['cleanup_enabled'] = $request->boolean('cleanup_enabled');

            Setting::setDatabaseSettings($validated);

            return redirect()->route('settings.database.index')
                ->with('success', 'Configuración de base de datos actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Database settings update failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Error al actualizar la configuración. Por favor, inténtalo de nuevo.')
                ->withInput();
        }
    }

    /**
     * Test database connection
     */
    public function checkConnection(): JsonResponse
    {
        try {
            $settings = Setting::getDatabaseSettings();
            $driver = $settings['db_connection'] ?? 'mysql';

            $conn = match ($driver) {
                'mysql' => $this->tester->testMySQL($settings),
                'pgsql' => $this->tester->testPostgreSQL($settings),
                'sqlite' => $this->tester->testSQLite($settings),
                default => throw new \Exception("Driver de base de datos no soportado: {$driver}"),
            };

            return response()->json([
                'success' => true,
                'status' => 'connected',
                'message' => 'Conexión a base de datos exitosa',
                'version' => $conn['version'] ?? 'Desconocida',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error en la conexión. Por favor, inténtalo de nuevo.',
            ], 422);
        }
    }
}
