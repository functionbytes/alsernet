<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Enums\PostType;
use Modules\Social\Http\Requests\StorePostRequest;
use Modules\Social\Http\Requests\UpdatePostRequest;
use Modules\Social\Jobs\PublishPostJob;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\HashtagGroup;
use Modules\Social\Models\Label;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Models\Template;

class PublishingController extends Controller
{
    public function index()
    {
        $posts = Post::with(['socialAccount', 'campaign', 'labels', 'creator'])
            ->where('account_id', auth()->user()->account_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => Post::where('account_id', auth()->user()->account_id)->count(),
            'draft' => Post::where('account_id', auth()->user()->account_id)->draft()->count(),
            'scheduled' => Post::where('account_id', auth()->user()->account_id)->scheduled()->count(),
            'published' => Post::where('account_id', auth()->user()->account_id)->published()->count(),
            'failed' => Post::where('account_id', auth()->user()->account_id)->failed()->count(),
        ];

        return view('social::publishing.index', compact('posts', 'stats'));
    }

    public function calendar()
    {
        $posts = Post::where('account_id', auth()->user()->account_id)
            ->whereNotNull('scheduled_at')
            ->with(['socialAccount', 'campaign'])
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => substr($post->content, 0, 50).'...',
                    'start' => $post->scheduled_at->toIso8601String(),
                    'backgroundColor' => $post->campaign?->color ?? '#5D87FF',
                    'url' => route('admin.social.publishing.edit', $post),
                ];
            });

        return view('social::publishing.calendar', compact('posts'));
    }

    public function create()
    {
        $socialAccounts = SocialAccount::where('account_id', auth()->user()->account_id)
            ->where('status', 1)
            ->get();

        $campaigns = Campaign::where('account_id', auth()->user()->account_id)
            ->active()
            ->get();

        $labels = Label::where('account_id', auth()->user()->account_id)->get();

        $templates = Template::where('account_id', auth()->user()->account_id)
            ->orderBy('usage_count', 'desc')
            ->get();

        $hashtagGroups = HashtagGroup::where('account_id', auth()->user()->account_id)
            ->orderBy('usage_count', 'desc')
            ->get();

        $postTypes = PostType::cases();

        return view('social::publishing.create', compact('socialAccounts', 'campaigns', 'labels', 'templates', 'hashtagGroups', 'postTypes'));
    }

    public function store(StorePostRequest $request)
    {
        $this->authorize('create', Post::class);

        $validated = $request->validated();

        $post = Post::create([
            'account_id' => auth()->user()->account_id,
            'social_account_id' => $validated['social_account_id'],
            'campaign_id' => $validated['campaign_id'] ?? null,
            'created_by' => auth()->id(),
            'type' => $validated['type'],
            'content' => $validated['content'],
            'media' => $validated['media'] ?? null,
            'link_url' => $validated['link_url'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => $validated['scheduled_at'] ? PostStatus::SCHEDULED : PostStatus::DRAFT,
        ]);

        if (! empty($validated['labels'])) {
            $post->labels()->attach($validated['labels']);
        }

        return redirect()
            ->route('admin.social.publishing.index')
            ->with('success', 'Post creado exitosamente');
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $socialAccounts = SocialAccount::where('account_id', auth()->user()->account_id)
            ->where('status', 1)
            ->get();

        $campaigns = Campaign::where('account_id', auth()->user()->account_id)
            ->active()
            ->get();

        $labels = Label::where('account_id', auth()->user()->account_id)->get();

        $postTypes = PostType::cases();

        return view('social::publishing.edit', compact('post', 'socialAccounts', 'campaigns', 'labels', 'postTypes'));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        if (! $post->canEdit()) {
            return redirect()
                ->back()
                ->with('error', 'Este post no puede ser editado');
        }

        $validated = $request->validated();

        $post->update([
            'social_account_id' => $validated['social_account_id'],
            'campaign_id' => $validated['campaign_id'] ?? null,
            'type' => $validated['type'],
            'content' => $validated['content'],
            'media' => $validated['media'] ?? null,
            'link_url' => $validated['link_url'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => $validated['scheduled_at'] ? PostStatus::SCHEDULED : PostStatus::DRAFT,
        ]);

        if (isset($validated['labels'])) {
            $post->labels()->sync($validated['labels']);
        }

        return redirect()
            ->route('admin.social.publishing.index')
            ->with('success', 'Post actualizado exitosamente');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()
            ->route('admin.social.publishing.index')
            ->with('success', 'Post eliminado exitosamente');
    }

    public function publish(Post $post)
    {
        $this->authorize('update', $post);

        if ($post->status !== PostStatus::DRAFT && $post->status !== PostStatus::FAILED) {
            return redirect()
                ->back()
                ->with('error', 'Este post no puede ser publicado');
        }

        // Dispatch job to publish post to social network
        PublishPostJob::dispatch($post);

        return redirect()
            ->route('admin.social.publishing.index')
            ->with('success', 'Post enviado para publicación');
    }

    public function duplicate(Post $post)
    {
        $this->authorize('create', Post::class);

        $newPost = $post->replicate();
        $newPost->status = PostStatus::DRAFT;
        $newPost->scheduled_at = null;
        $newPost->published_at = null;
        $newPost->created_by = auth()->id();
        $newPost->save();

        $newPost->labels()->attach($post->labels->pluck('id'));

        return redirect()
            ->route('admin.social.publishing.edit', $newPost)
            ->with('success', 'Post duplicado exitosamente');
    }
}
