<?php

namespace Modules\Template\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Modules\Template\Models\Template;
use Modules\Template\Services\TemplateManager;

class TemplateWebController
{
    public function __construct(
        protected TemplateManager $manager,
    ) {}

    /**
     * Renderizar un template públicamente
     *
     * GET /template/{slug}
     * Renderiza el contenido del template usando su layout Blade
     */
    public function render(string $slug): View|Response
    {
        $template = Template::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $variables = [
            '{{ $title }}' => e($template->name),
            '{{ $description }}' => e($template->description ?? ''),
            '{{ $content }}' => e($template->content ?? ''),
            '{{ $keywords }}' => e($template->keywords ?? ''),
            '{{ $canonical }}' => e(url()->current()),
        ];

        $renderedContent = str_replace(array_keys($variables), array_values($variables), $template->content ?? '');

        return view('template::public.render', [
            'template' => $template,
            'title' => $template->name,
            'description' => $template->description,
            'renderedContent' => $renderedContent,
            'layout' => $template->template_path ?? "templates/{$template->slug}",
        ]);
    }
}
