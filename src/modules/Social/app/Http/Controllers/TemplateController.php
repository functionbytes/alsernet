<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Social\Http\Requests\StoreTemplateRequest;
use Modules\Social\Http\Requests\UpdateTemplateRequest;
use Modules\Social\Models\Template;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::where('account_id', auth()->user()->account_id)
            ->orderBy('usage_count', 'desc')
            ->get();

        return view('social::templates.index', compact('templates'));
    }

    public function create()
    {
        return view('social::templates.create');
    }

    public function store(StoreTemplateRequest $request)
    {
        Template::create($request->validated());

        return redirect()
            ->route('admin.social.templates.index')
            ->with('success', 'Plantilla creada exitosamente');
    }

    public function edit(Template $template)
    {
        $this->authorize('update', $template);

        return view('social::templates.edit', compact('template'));
    }

    public function update(UpdateTemplateRequest $request, Template $template)
    {
        $template->update($request->validated());

        return redirect()
            ->route('admin.social.templates.index')
            ->with('success', 'Plantilla actualizada exitosamente');
    }

    public function destroy(Template $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()
            ->route('admin.social.templates.index')
            ->with('success', 'Plantilla eliminada exitosamente');
    }

    public function apply(Request $request, Template $template)
    {
        $this->authorize('view', $template);

        $variables = $request->input('variables', []);
        $content = $template->replaceVariables($variables);

        $template->incrementUsage();

        return response()->json([
            'content' => $content,
            'media' => $template->media,
            'hashtags' => $template->hashtags,
        ]);
    }
}
