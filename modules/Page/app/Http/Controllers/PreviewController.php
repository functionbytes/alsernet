<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Page\Models\Page;
use Modules\Page\Models\PagePreviewToken;
use Modules\Template\Services\TemplateManager;
use Symfony\Component\HttpFoundation\Response;

class PreviewController extends Controller
{
    public function __construct(
        private readonly TemplateManager $templateManager
    ) {}

    /**
     * Show the page preview with token validation.
     *
     * Resolves the view using TemplateManager so deleted page::public.* views
     * are never referenced. Passes the same variables expected by the theme's
     * page.blade.php (same contract as PublicController::show).
     */
    public function show(string $slug, string $token): View|Response
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $previewToken = PagePreviewToken::where('page_id', $page->id)
            ->where('token', $token)
            ->first();

        if (! $previewToken) {
            abort(404, 'Token de preview no encontrado.');
        }

        if ($previewToken->isExpired()) {
            abort(403, 'Este enlace de preview ha expirado.');
        }

        $previewToken->recordView();

        $page->load('user');

        $viewName = $this->resolveView($page->template ?? 'default');

        $rawContent = $page->content ?? '';
        $transContent = function_exists('shortcode') ? shortcode($rawContent) : $rawContent;

        return view($viewName, [
            'page' => $page,
            'previewToken' => $previewToken,
            'isPreview' => true,
            'transTitle' => '[Vista previa] '.($page->seo_title ?? $page->title),
            'transDescription' => $page->seo_description ?? $page->description ?? '',
            'transKeywords' => $page->seo_keywords ?? '',
            'transContent' => $transContent,
            'featuredImage' => $page->featured_image ?? null,
            'canonicalUrl' => url()->current(),
            'xDefaultUrl' => null,
            'langLinks' => [],
            'detectedLocale' => app()->getLocale(),
        ]);
    }

    /**
     * Resolve the theme view for a given page template slug.
     *
     * Mirrors the logic in PublicController::show using TemplateManager to
     * build view names under the 'template' namespace.
     */
    private function resolveView(string $template): string
    {
        $candidates = [
            $this->templateManager->getThemeViewPath("templates.{$template}"),
            $this->templateManager->getThemeViewPath('page'),
        ];

        // Special-case homepage to use the index view
        if ($template === 'homepage') {
            array_unshift($candidates, $this->templateManager->getThemeViewPath('index'));
        }

        foreach ($candidates as $candidate) {
            if (view()->exists($candidate)) {
                return $candidate;
            }
        }

        return $this->templateManager->getThemeViewPath('page');
    }

    /**
     * Generate a new preview token for a page (settings only).
     */
    public function generate(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

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
     */
    public function revoke(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

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
     */
    public function index(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

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
