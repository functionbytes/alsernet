<?php

namespace Modules\Chat\Http\Controllers\Settings\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Web;
use Modules\Chat\Models\Inbox\Inbox;

class WebController extends Controller
{
    /**
     * Display a listing of web widgets.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $widgets = Web::where('account_id', $accountId)
            ->with('inbox')
            ->latest()
            ->get();

        return view('Chat::settings.channels.webs.index', compact('widgets'));
    }

    /**
     * Show the form for creating a new web widget.
     */
    public function create()
    {
        return view('Chat::settings.channels.webs.create');
    }

    /**
     * Store a newly created web widget.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'required|url|max:255',
            'widget_color' => 'nullable|string|max:7',
            'widget_position' => 'nullable|in:right,left',
            'widget_bubble_launcher_title' => 'nullable|string|max:255',
            'widget_launcher_icon' => 'nullable|string|max:100',
            'widget_bubble_color' => 'nullable|string|max:7',
            'widget_custom_styles' => 'nullable|array',
            'welcome_title' => 'nullable|string|max:255',
            'welcome_tagline' => 'nullable|string|max:500',
            'pre_chat_form_enabled' => 'boolean',
            'pre_chat_form_options' => 'nullable|array',
            'offline_message_enabled' => 'boolean',
            'offline_message' => 'nullable|string|max:1000',
            'show_availability_status' => 'boolean',
            'business_hours' => 'nullable|array',
            'reply_time_message' => 'nullable|string|max:255',
            'show_powered_by' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Create the web widget channel
            $widget = Web::create([
                'account_id' => $request->user()->account_id,
                'website_url' => $validated['website_url'],
                'widget_color' => $validated['widget_color'] ?? '#1f93ff',
                'widget_position' => $validated['widget_position'] ?? 'right',
                'widget_bubble_launcher_title' => $validated['widget_bubble_launcher_title'] ?? null,
                'widget_launcher_icon' => $validated['widget_launcher_icon'] ?? null,
                'widget_bubble_color' => $validated['widget_bubble_color'] ?? null,
                'widget_custom_styles' => $validated['widget_custom_styles'] ?? null,
                'welcome_title' => $validated['welcome_title'] ?? 'Hello!',
                'welcome_tagline' => $validated['welcome_tagline'] ?? null,
                'pre_chat_form_enabled' => $validated['pre_chat_form_enabled'] ?? false,
                'pre_chat_form_options' => $validated['pre_chat_form_options'] ?? null,
                'offline_message_enabled' => $validated['offline_message_enabled'] ?? true,
                'offline_message' => $validated['offline_message'] ?? null,
                'show_availability_status' => $validated['show_availability_status'] ?? true,
                'business_hours' => $validated['business_hours'] ?? null,
                'reply_time_message' => $validated['reply_time_message'] ?? null,
                'show_powered_by' => $validated['show_powered_by'] ?? true,
            ]);

            // Create the inbox for this channel
            $inbox = Inbox::create([
                'account_id' => $request->user()->account_id,
                'channel_id' => $widget->id,
                'channel_type' => Web::class,
                'name' => $validated['name'],
                'timezone' => config('app.timezone'),
            ]);

            DB::commit();

            return redirect()
                ->route('settings.chat.channels.webs.show', $widget)
                ->with('success', 'Web Widget created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to create Web Widget: '.$e->getMessage());
        }
    }

    /**
     * Display the specified web widget.
     */
    public function show(Web $webWidget)
    {
        $this->authorize('view', $webWidget);

        $webWidget->load('inbox');

        return view('Chat::settings.channels.webs.show', compact('webWidget'));
    }

    /**
     * Show the form for editing the web widget.
     */
    public function edit(Web $webWidget)
    {
        $this->authorize('update', $webWidget);

        $webWidget->load('inbox');

        return view('Chat::settings.channels.webs.edit', compact('webWidget'));
    }

    /**
     * Update the specified web widget.
     */
    public function update(Request $request, Web $webWidget)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'required|url|max:255',
            'widget_color' => 'nullable|string|max:7',
            'widget_position' => 'nullable|in:right,left',
            'widget_bubble_launcher_title' => 'nullable|string|max:255',
            'widget_launcher_icon' => 'nullable|string|max:100',
            'widget_bubble_color' => 'nullable|string|max:7',
            'widget_custom_styles' => 'nullable|array',
            'welcome_title' => 'nullable|string|max:255',
            'welcome_tagline' => 'nullable|string|max:500',
            'pre_chat_form_enabled' => 'boolean',
            'pre_chat_form_options' => 'nullable|array',
            'offline_message_enabled' => 'boolean',
            'offline_message' => 'nullable|string|max:1000',
            'show_availability_status' => 'boolean',
            'business_hours' => 'nullable|array',
            'reply_time_message' => 'nullable|string|max:255',
            'show_powered_by' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update the widget
            $webWidget->update([
                'website_url' => $validated['website_url'],
                'widget_color' => $validated['widget_color'] ?? '#1f93ff',
                'widget_position' => $validated['widget_position'] ?? 'right',
                'widget_bubble_launcher_title' => $validated['widget_bubble_launcher_title'] ?? null,
                'widget_launcher_icon' => $validated['widget_launcher_icon'] ?? null,
                'widget_bubble_color' => $validated['widget_bubble_color'] ?? null,
                'widget_custom_styles' => $validated['widget_custom_styles'] ?? null,
                'welcome_title' => $validated['welcome_title'] ?? 'Hello!',
                'welcome_tagline' => $validated['welcome_tagline'] ?? null,
                'pre_chat_form_enabled' => $validated['pre_chat_form_enabled'] ?? false,
                'pre_chat_form_options' => $validated['pre_chat_form_options'] ?? null,
                'offline_message_enabled' => $validated['offline_message_enabled'] ?? true,
                'offline_message' => $validated['offline_message'] ?? null,
                'show_availability_status' => $validated['show_availability_status'] ?? true,
                'business_hours' => $validated['business_hours'] ?? null,
                'reply_time_message' => $validated['reply_time_message'] ?? null,
                'show_powered_by' => $validated['show_powered_by'] ?? true,
            ]);

            // Update inbox name
            if ($webWidget->inbox) {
                $webWidget->inbox->update(['name' => $validated['name']]);
            }

            DB::commit();

            return redirect()
                ->route('settings.chat.channels.webs.show', $webWidget)
                ->with('success', 'Web Widget updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to update Web Widget: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified web widget.
     */
    public function destroy(Web $webWidget)
    {
        try {
            $webWidget->inbox?->delete();
            $webWidget->delete();

            return redirect()
                ->route('settings.chat.channels.webs.index')
                ->with('success', 'Web Widget deleted successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete Web Widget: '.$e->getMessage());
        }
    }
}
