<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Social\Http\Requests\StoreCampaignRequest;
use Modules\Social\Http\Requests\UpdateCampaignRequest;
use Modules\Social\Models\Campaign;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::where('account_id', auth()->user()->account_id)
            ->withCount('posts')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('social::campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('social::campaigns.create');
    }

    public function store(StoreCampaignRequest $request)
    {
        $this->authorize('create', Campaign::class);

        $validated = $request->validated();

        Campaign::create([
            'account_id' => auth()->user()->account_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.social.campaigns.index')
            ->with('success', 'Campaña creada exitosamente');
    }

    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load([
            'posts' => function ($query) {
                $query->with(['socialAccount', 'labels'])
                    ->orderBy('created_at', 'desc');
            },
        ])
            ->loadCount([
                'posts as published_posts_count' => fn ($query) => $query->where('status', 'published'),
                'posts as scheduled_posts_count' => fn ($query) => $query->where('status', 'scheduled'),
                'posts as draft_posts_count' => fn ($query) => $query->where('status', 'draft'),
                'posts as failed_posts_count' => fn ($query) => $query->where('status', 'failed'),
            ]);

        $campaign->total_engagement = $campaign->posts->sum(function ($post) {
            return $post->likes_count + $post->comments_count + $post->shares_count;
        });

        return view('social::campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        return view('social::campaigns.edit', compact('campaign'));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return redirect()
            ->route('admin.social.campaigns.index')
            ->with('success', 'Campaña actualizada exitosamente');
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if ($campaign->posts()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar una campaña con posts asociados');
        }

        $campaign->delete();

        return redirect()
            ->route('admin.social.campaigns.index')
            ->with('success', 'Campaña eliminada exitosamente');
    }
}
