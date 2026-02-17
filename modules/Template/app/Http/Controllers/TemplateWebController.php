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
    ) {
    }

    /**
     * Renderizar un template públicamente
     *
     * GET /template/{slug}
     * Renderiza el contenido del template usando su layout Blade
     */
    public function render(string $slug): View|Response
    {
        // Obtener el template
        $template = Template::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Renderizar el template
        return view('template::public.render', [
            'template' => $template,
            'title' => $template->name,
            'description' => $template->description,
            'content' => $template->content,
            'layout' => $template->template_path ?? "templates/{$template->slug}",
        ]);
    }
}
