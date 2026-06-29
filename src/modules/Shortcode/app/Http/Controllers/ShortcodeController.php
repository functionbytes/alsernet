<?php

namespace Modules\Shortcode\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Shortcode\Facades\Shortcode;
use Modules\Shortcode\Http\Requests\CheckShortcodeRequest;
use Modules\Shortcode\Http\Requests\CompileShortcodeRequest;
use Modules\Shortcode\Http\Requests\StripShortcodeRequest;
use Modules\Shortcode\Services\ShortcodeUsageStats;

class ShortcodeController extends Controller
{
    /**
     * Display shortcode list with live examples.
     */
    public function index(ShortcodeUsageStats $stats): View
    {
        $this->authorize('shortcode.view');

        $shortcodes = Shortcode::all();

        $dbShortcodes = class_exists(\Modules\Template\Models\Shortcode::class)
            ? \Modules\Template\Models\Shortcode::query()->pluck('id', 'key')
            : collect();

        $topUsage = $stats->top(10);

        return view('shortcode::admin.index', compact('shortcodes', 'dbShortcodes', 'topUsage'));
    }

    /**
     * Display detailed shortcode reference.
     */
    public function reference(): View
    {
        $this->authorize('shortcode.view');

        $shortcodes = Shortcode::all();

        return view('shortcode::admin.reference', compact('shortcodes'));
    }

    /**
     * Vista Tester: renderiza todos los shortcodes registrados con su example,
     * lado a lado (fuente + output). Útil para QA visual tras cambios de CSS.
     */
    public function tester(): View
    {
        $this->authorize('shortcode.view');

        $registered = Shortcode::getRegistered();

        return view('shortcode::admin.tester', compact('registered'));
    }

    /**
     * Compile shortcode via API.
     */
    public function compile(CompileShortcodeRequest $request): JsonResponse
    {
        $content = $request->validated('content');
        $compiled = Shortcode::compile($content);

        return response()->json([
            'success' => true,
            'original' => $content,
            'compiled' => $compiled,
        ]);
    }

    /**
     * Compila un shortcode desde el panel admin (usa sesión web, no Sanctum).
     *
     * Endpoint paralelo a compile() dedicado al preview interactivo.
     */
    public function compilePreview(CompileShortcodeRequest $request): JsonResponse
    {
        return $this->compile($request);
    }

    /**
     * Strip shortcodes via API.
     */
    public function strip(StripShortcodeRequest $request): JsonResponse
    {
        $content = $request->validated('content');
        $stripped = Shortcode::strip($content);

        return response()->json([
            'success' => true,
            'original' => $content,
            'stripped' => $stripped,
        ]);
    }

    /**
     * Get list of registered shortcode names.
     */
    public function list(): JsonResponse
    {
        $this->authorize('shortcode.view');

        $shortcodes = Shortcode::all();

        return response()->json([
            'success' => true,
            'shortcodes' => $shortcodes,
            'count' => count($shortcodes),
        ]);
    }

    /**
     * Get registered shortcodes with full metadata (for picker UI).
     */
    public function registered(): JsonResponse
    {
        $this->authorize('shortcode.view');

        return response()->json(Shortcode::getRegistered());
    }

    /**
     * Check if shortcode exists.
     */
    public function check(CheckShortcodeRequest $request): JsonResponse
    {
        $name = $request->validated('name');
        $exists = Shortcode::has($name);

        return response()->json([
            'success' => true,
            'name' => $name,
            'exists' => $exists,
        ]);
    }

    /**
     * Clear shortcode cache.
     */
    public function clearCache(): JsonResponse
    {
        $this->authorize('shortcode.manage');

        Shortcode::clearCache();

        return response()->json([
            'success' => true,
            'message' => __('shortcode::shortcode.cache_cleared'),
        ]);
    }

    /**
     * Top shortcodes por uso.
     */
    public function stats(ShortcodeUsageStats $stats): JsonResponse
    {
        $this->authorize('shortcode.view');

        return response()->json([
            'success' => true,
            'data' => $stats->top(50),
        ]);
    }

    /**
     * Resetea los contadores de uso (endpoint API con Sanctum).
     */
    public function resetStats(ShortcodeUsageStats $stats): JsonResponse
    {
        $this->authorize('shortcode.manage');

        $stats->reset();

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas reseteadas.',
        ]);
    }

    /**
     * Versión web del reset de stats (sesión web, no Sanctum).
     * Usado desde el dashboard admin.
     */
    public function resetStatsWeb(ShortcodeUsageStats $stats): JsonResponse
    {
        return $this->resetStats($stats);
    }
}
