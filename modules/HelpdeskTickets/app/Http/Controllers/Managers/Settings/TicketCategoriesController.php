<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Http\Requests\Settings\ReorderTicketCategoryRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketCategoryRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketCategoryRequest;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;

class TicketCategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    /**
     * Display a listing of ticket categories.
     */
    public function index(Request $request)
    {
        $query = TicketCategory::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query->with(['defaultSlaPolicy', 'ticketGroups', 'ticketCannedReplies'])
            ->ordered()
            ->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => TicketCategory::count(),
            'active' => TicketCategory::where('active', true)->count(),
            'inactive' => TicketCategory::where('active', false)->count(),
            'with_sla' => TicketCategory::whereNotNull('default_sla_policy_id')->count(),
        ];

        return view('theme.views.backups.helpdesk.ticket-categories.index', [
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        $slaPolicies = TicketSlaPolicy::all();
        $groups = TicketGroup::active()->ordered()->get();
        $cannedReplies = TicketCannedReply::active()->get();

        return view('theme.views.backups.helpdesk.ticket-categories.create', [
            'slaPolicies' => $slaPolicies,
            'groups' => $groups,
            'cannedReplies' => $cannedReplies,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreTicketCategoryRequest $request)
    {
        $validated = $request->validated();

        // Auto-generate slug if not provided
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['active'] = $request->boolean('active', true);
        $validated['custom_form_fields'] = $validated['custom_form_fields'] ?? [];

        $category = TicketCategory::create($validated);

        // Attach groups with pivot data
        if ($request->filled('groups')) {
            $groupsData = [];
            foreach ($request->groups as $index => $groupId) {
                $groupsData[$groupId] = [
                    'is_default' => $request->default_group == $groupId,
                    'priority' => $index + 1,
                ];
            }
            $category->ticketGroups()->attach($groupsData);
        }

        // Attach canned replies with order
        if ($request->filled('canned_replies')) {
            $repliesData = [];
            foreach ($request->canned_replies as $index => $replyId) {
                $repliesData[$replyId] = ['order' => $index + 1];
            }
            $category->ticketCannedReplies()->attach($repliesData);
        }

        return redirect()->route('manager.helpdesk.settings.ticket-categories.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(TicketCategory $category)
    {
        $category->load(['ticketGroups', 'ticketCannedReplies']);
        $slaPolicies = TicketSlaPolicy::all();
        $groups = TicketGroup::active()->ordered()->get();
        $cannedReplies = TicketCannedReply::active()->get();

        return view('theme.views.backups.helpdesk.ticket-categories.edit', [
            'category' => $category,
            'slaPolicies' => $slaPolicies,
            'groups' => $groups,
            'cannedReplies' => $cannedReplies,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateTicketCategoryRequest $request, TicketCategory $category)
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['active'] = $request->boolean('active');
        $validated['custom_form_fields'] = $validated['custom_form_fields'] ?? [];

        $category->update($validated);

        // Sync groups with pivot data
        if ($request->has('groups')) {
            $groupsData = [];
            if ($request->filled('groups')) {
                foreach ($request->groups as $index => $groupId) {
                    $groupsData[$groupId] = [
                        'is_default' => $request->default_group == $groupId,
                        'priority' => $index + 1,
                    ];
                }
            }
            $category->ticketGroups()->sync($groupsData);
        }

        // Sync canned replies with order
        if ($request->has('canned_replies')) {
            $repliesData = [];
            if ($request->filled('canned_replies')) {
                foreach ($request->canned_replies as $index => $replyId) {
                    $repliesData[$replyId] = ['order' => $index + 1];
                }
            }
            $category->ticketCannedReplies()->sync($repliesData);
        }

        return redirect()->route('manager.helpdesk.settings.ticket-categories.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(TicketCategory $category)
    {
        // Check if category has tickets
        if ($category->tickets()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una categoría que tiene tickets asociados.');
        }

        $category->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-categories.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }

    /**
     * Toggle the active status of a category.
     */
    public function toggle(TicketCategory $category)
    {
        $category->update(['active' => ! $category->active]);

        return back()->with('success', 'Estado de la categoría actualizado exitosamente.');
    }

    /**
     * Reorder categories via drag and drop.
     */
    public function reorder(ReorderTicketCategoryRequest $request)
    {
        $validated = $request->validated();

        TicketCategory::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }
}
