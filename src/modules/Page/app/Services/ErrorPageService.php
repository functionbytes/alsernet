<?php

namespace Modules\Page\Services;

use Illuminate\Http\Response;
use Modules\Page\Models\Page;
use Modules\Template\Services\TemplateManager;

class ErrorPageService
{
    public function __construct(private readonly TemplateManager $templateManager) {}

    /**
     * Render the CMS-configured error page for the given HTTP status code.
     * Returns null to fall back to Laravel's default error view.
     */
    public function render(int $statusCode): ?Response
    {
        $page = $this->resolvePage($statusCode);

        if (! $page) {
            return null;
        }

        $this->ensureThemeViewPathRegistered();

        $viewName = $this->resolveViewName($page);

        if (! $viewName) {
            return null;
        }

        return response()->view($viewName, $this->buildViewData($page), $statusCode);
    }

    /**
     * Resolve the Page configured for this error code.
     * Prefers the setting, falls back to the page_type marker.
     */
    private function resolvePage(int $code): ?Page
    {
        try {
            $pageId = setting("error-page-{$code}");

            return $pageId
                ? Page::find((int) $pageId)
                : Page::findByPageType("error_{$code}");
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Ensure the active theme view path is in the view finder.
     * This mirrors what RegisterTemplateViewPath middleware does,
     * so error pages rendered outside the middleware stack still
     * resolve theme views correctly.
     */
    private function ensureThemeViewPathRegistered(): void
    {
        try {
            $activeTheme = $this->templateManager->getActiveTemplateName();
            $themePath = base_path("platform/themes/{$activeTheme}/views");

            if (! is_dir($themePath)) {
                return;
            }

            $finder = app('view')->getFinder();
            $paths = $finder->getPaths();

            if (in_array($themePath, $paths, true)) {
                return;
            }

            $paths = array_filter($paths, fn ($p) => $p !== $themePath);
            array_unshift($paths, $themePath);
            $finder->setPaths($paths);
        } catch (\Throwable) {
            // Non-fatal: view will fall back to resources/views
        }
    }

    /**
     * Resolve the Blade view name using the same candidate order
     * as PublicController.
     */
    private function resolveViewName(Page $page): ?string
    {
        $template = $page->template ?: 'default';

        $candidates = [
            $this->templateManager->getThemeViewPath("templates.{$template}"),
            $this->templateManager->getThemeViewPath('templates.default'),
            $this->templateManager->getThemeViewPath('page'),
        ];

        foreach ($candidates as $candidate) {
            if (view()->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Build the view data array expected by the theme's page template.
     *
     * @return array<string, mixed>
     */
    private function buildViewData(Page $page): array
    {
        $locale = app()->getLocale();

        $content = $page->trans('content', $locale) ?? $page->content;

        if (function_exists('shortcode') && $content !== null) {
            $content = shortcode($content);
        }

        return [
            'page' => $page,
            'transTitle' => $page->trans('title', $locale) ?? $page->title,
            'transContent' => $content,
            'transDescription' => $page->trans('description', $locale) ?? $page->description,
            'transKeywords' => $page->trans('seo_keywords', $locale) ?? $page->seo_keywords,
            'canonicalUrl' => url()->current(),
            'xDefaultUrl' => null,
            'featuredImage' => null,
            'langLinks' => [],
            'detectedLocale' => $locale,
        ];
    }
}
