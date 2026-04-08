<?php

namespace Modules\Shortcode\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeController extends Controller
{
    /**
     * Display shortcode list with live examples.
     */
    public function index(): View
    {
        $this->authorize('settings.view');

        $shortcodes = Shortcode::all();

        $dbShortcodes = class_exists(\Modules\Template\Models\Shortcode::class)
            ? \Modules\Template\Models\Shortcode::query()->pluck('id', 'key')
            : collect();

        return view('shortcode::admin.index', compact('shortcodes', 'dbShortcodes'));
    }

    /**
     * Display detailed shortcode reference.
     */
    public function reference(): View
    {
        $this->authorize('settings.view');

        $shortcodes = Shortcode::all();

        return view('shortcode::admin.reference', compact('shortcodes'));
    }

    /**
     * Compile shortcode via API.
     */
    public function compile(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        $request->validate([
            'content' => 'required|string',
        ]);

        $content = $request->input('content');
        $compiled = Shortcode::compile($content);

        return response()->json([
            'success' => true,
            'original' => $content,
            'compiled' => $compiled,
        ]);
    }

    /**
     * Strip shortcodes via API.
     */
    public function strip(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        $request->validate([
            'content' => 'required|string',
        ]);

        $content = $request->input('content');
        $stripped = Shortcode::strip($content);

        return response()->json([
            'success' => true,
            'original' => $content,
            'stripped' => $stripped,
        ]);
    }

    /**
     * Get list of registered shortcodes.
     */
    public function list(): JsonResponse
    {
        $this->authorize('settings.view');

        $shortcodes = Shortcode::all();

        return response()->json([
            'success' => true,
            'shortcodes' => $shortcodes,
            'count' => count($shortcodes),
        ]);
    }

    /**
     * Check if shortcode exists.
     */
    public function check(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        $request->validate([
            'name' => 'required|string',
        ]);

        $name = $request->input('name');
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
        $this->authorize('settings.edit');

        Shortcode::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Cache de shortcodes limpiada correctamente',
        ]);
    }
}
