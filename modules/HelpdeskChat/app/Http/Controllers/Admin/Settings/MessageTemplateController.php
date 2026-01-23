<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Templates\MessageTemplate;
use Modules\HelpdeskChat\Services\Messages\TemplateRenderer;

class MessageTemplateController extends Controller
{
    public function __construct(
        protected TemplateRenderer $renderer
    ) {}

    /**
     * Display a listing of message templates.
     */
    public function index(Request $request): View
    {
        $query = MessageTemplate::forAccount(auth()->user()->account_id)
            ->forUser(auth()->id())
            ->with('user');

        // Apply filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Order
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'usage') {
            $query->mostUsed();
        } elseif ($sortBy === 'recent') {
            $query->recentlyUsed();
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $templates = $query->paginate(20);
        $categories = MessageTemplate::getCategories();

        return view('helpdeskchat::admin.settings.message-templates.index', compact('templates', 'categories'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        $categories = MessageTemplate::getCategories();
        $availableVariables = MessageTemplate::getAvailableVariables();

        return view('helpdeskchat::admin.settings.message-templates.create', compact('categories', 'availableVariables'));
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'shortcut' => 'nullable|string|max:50|unique:message_templates,shortcut,NULL,id,account_id,'.auth()->user()->account_id,
            'category' => 'nullable|string|max:100',
            'is_public' => 'boolean',
        ]);

        // Validate template syntax
        $errors = $this->renderer->validate($validated['content']);
        if (! empty($errors)) {
            return back()->withErrors(['content' => $errors])->withInput();
        }

        $template = MessageTemplate::create([
            ...$validated,
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.helpdesk.message-templates.index')
            ->with('success', 'Template created successfully.');
    }

    /**
     * Display the specified template.
     */
    public function show(MessageTemplate $messageTemplate): View
    {
        $this->authorize('view', $messageTemplate);

        $template = $messageTemplate;
        $usedVariables = collect($this->renderer->getUsedVariables($messageTemplate));
        $categories = MessageTemplate::getCategories();

        return view('helpdeskchat::admin.settings.message-templates.show', compact('template', 'usedVariables', 'categories'));
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(MessageTemplate $messageTemplate): View
    {
        $this->authorize('update', $messageTemplate);

        $template = $messageTemplate;
        $categories = MessageTemplate::getCategories();
        $availableVariables = MessageTemplate::getAvailableVariables();

        return view('helpdeskchat::admin.settings.message-templates.edit', compact('template', 'categories', 'availableVariables'));
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $this->authorize('update', $messageTemplate);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'shortcut' => 'nullable|string|max:50|unique:message_templates,shortcut,'.$messageTemplate->id.',id,account_id,'.auth()->user()->account_id,
            'category' => 'nullable|string|max:100',
            'is_public' => 'boolean',
        ]);

        // Validate template syntax
        $errors = $this->renderer->validate($validated['content']);
        if (! empty($errors)) {
            return back()->withErrors(['content' => $errors])->withInput();
        }

        $messageTemplate->update($validated);

        return redirect()
            ->route('admin.helpdesk.message-templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified template.
     */
    public function destroy(MessageTemplate $messageTemplate)
    {
        $this->authorize('delete', $messageTemplate);

        $messageTemplate->delete();

        return redirect()
            ->route('admin.helpdesk.message-templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Search templates (AJAX).
     */
    public function search(Request $request): JsonResponse
    {
        $query = MessageTemplate::forAccount(auth()->user()->account_id)
            ->forUser(auth()->id());

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $templates = $query->mostUsed()->limit(10)->get();

        return response()->json([
            'success' => true,
            'templates' => $templates->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'content' => $t->content,
                'shortcut' => $t->shortcut,
                'category' => $t->category,
                'usage_count' => $t->usage_count,
            ]),
        ]);
    }

    /**
     * Preview template with variables (AJAX).
     */
    public function preview(Request $request, MessageTemplate $messageTemplate): JsonResponse
    {
        $this->authorize('view', $messageTemplate);

        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = Conversation::find($request->conversation_id);
        }

        $preview = $this->renderer->preview(
            $messageTemplate,
            $conversation,
            $conversation?->contact,
            auth()->user(),
            auth()->user()->account
        );

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'used_variables' => $this->renderer->getUsedVariables($messageTemplate),
        ]);
    }

    /**
     * Render template for use (AJAX).
     */
    public function render(Request $request, MessageTemplate $messageTemplate): JsonResponse
    {
        $this->authorize('view', $messageTemplate);

        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = Conversation::find($request->conversation_id);
        }

        $rendered = $this->renderer->render(
            $messageTemplate,
            $conversation,
            $conversation?->contact,
            auth()->user(),
            auth()->user()->account
        );

        return response()->json([
            'success' => true,
            'content' => $rendered,
            'template' => [
                'id' => $messageTemplate->id,
                'name' => $messageTemplate->name,
            ],
        ]);
    }
}
