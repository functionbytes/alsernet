<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Page\Models\Page;
use Modules\Page\Models\PagePreviewToken;
use Symfony\Component\HttpFoundation\Response;

class PreviewController extends Controller
{
    /**
     * Show the page preview with token validation.
     *
     * Usa la plantilla activa del módulo Template si está disponible
     *
     * @return View|Response
     */
    public function show(string $slug, string $token)
    {
        // Find the page by slug (including drafts)
        $page = Page::where('slug', $slug)->firstOrFail();

        // Find and validate the preview token
        $previewToken = PagePreviewToken::where('page_id', $page->id)
            ->where('token', $token)
            ->first();

        // Validate token exists
        if (! $previewToken) {
            abort(404, 'Token de preview no encontrado.');
        }

        // Validate token is not expired
        if ($previewToken->isExpired()) {
            abort(403, 'Este enlace de preview ha expirado.');
        }

        // Record the view
        $previewToken->recordView();

        // Load relationships
        $page->load('user');

        // Obtener plantilla activa del módulo Template
        try {
            $activeTemplate = \Modules\Template\Models\Template::where('status', 'active')->first();

            if ($activeTemplate) {
                // Usar la plantilla activa del módulo Template
                return $this->renderWithTemplateModule($page, $previewToken, $activeTemplate);
            }
        } catch (\Exception $e) {
            // Si hay error, caer a layout por defecto de Page
            \Log::warning('Error loading active template: ' . $e->getMessage());
        }

        // Fallback a la vista preview estándar si Template no está disponible
        $viewPath = "page::public.templates.default";

        if (! view()->exists($viewPath)) {
            $viewPath = 'page::public.templates.default';
        }

        return view('page::public.preview', [
            'page' => $page,
            'previewToken' => $previewToken,
            'contentView' => $viewPath,
        ]);
    }

    /**
     * Renderizar página con plantilla del módulo Template
     */
    private function renderWithTemplateModule($page, $previewToken, $template)
    {
        // Renderizar la preview usando la plantilla activa del Template módulo
        return view('page::public.preview-with-template', [
            'page' => $page,
            'previewToken' => $previewToken,
            'template' => $template,
            'title' => '[Vista previa] ' . ($page->seo_title ?? $page->title),
            'description' => $page->seo_description ?? $page->description,
            'keywords' => $page->seo_keywords,
        ]);
    }

    /**
     * Generate a new preview token for a page (settings only).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request, Page $page)
    {
        $request->validate([
            'expires_in_hours' => 'sometimes|integer|min:1|max:720', // Max 30 days
        ]);

        $expiresInHours = $request->input('expires_in_hours', 24);

        $token = $page->generatePreviewToken($expiresInHours, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Token de preview generado exitosamente.',
            'data' => [
                'token' => $token->token,
                'url' => $token->getPreviewUrl(),
                'expires_at' => $token->expires_at->toIso8601String(),
                'expires_in_human' => $token->getExpiresInHuman(),
            ],
        ]);
    }

    /**
     * Revoke all preview tokens for a page (settings only).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke(Page $page)
    {
        $revokedCount = $page->revokeAllPreviewTokens();

        return response()->json([
            'success' => true,
            'message' => "Se revocaron {$revokedCount} token(s) de preview.",
            'data' => [
                'revoked_count' => $revokedCount,
            ],
        ]);
    }

    /**
     * List all preview tokens for a page (settings only).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Page $page)
    {
        $tokens = $page->previewTokens()
            ->with('creator:id,name,email')
            ->latest()
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'token' => $token->token,
                    'url' => $token->getPreviewUrl(),
                    'expires_at' => $token->expires_at->toIso8601String(),
                    'expires_in_human' => $token->getExpiresInHuman(),
                    'is_active' => $token->isActive(),
                    'viewed_count' => $token->viewed_count,
                    'last_viewed_at' => $token->last_viewed_at?->toIso8601String(),
                    'created_by' => $token->creator?->name,
                    'created_at' => $token->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }
}
