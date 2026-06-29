<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Social\Http\Requests\StoreRssFeedRequest;
use Modules\Social\Http\Requests\UpdateRssFeedRequest;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\RssFeed;
use Modules\Social\Models\SocialAccount;

class RssFeedController extends Controller
{
    public function index()
    {
        $rssFeeds = RssFeed::where('account_id', auth()->user()->account_id)
            ->with(['socialAccount', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('social::rss-feeds.index', compact('rssFeeds'));
    }

    public function create()
    {
        $socialAccounts = SocialAccount::where('account_id', auth()->user()->account_id)->get();
        $campaigns = Campaign::where('account_id', auth()->user()->account_id)->get();

        return view('social::rss-feeds.create', compact('socialAccounts', 'campaigns'));
    }

    public function store(StoreRssFeedRequest $request)
    {
        RssFeed::create($request->validated());

        return redirect()
            ->route('admin.social.rss-feeds.index')
            ->with('success', 'Feed RSS creado exitosamente');
    }

    public function edit(RssFeed $rssFeed)
    {
        $this->authorize('update', $rssFeed);

        $socialAccounts = SocialAccount::where('account_id', auth()->user()->account_id)->get();
        $campaigns = Campaign::where('account_id', auth()->user()->account_id)->get();

        return view('social::rss-feeds.edit', compact('rssFeed', 'socialAccounts', 'campaigns'));
    }

    public function update(UpdateRssFeedRequest $request, RssFeed $rssFeed)
    {
        $rssFeed->update($request->validated());

        return redirect()
            ->route('admin.social.rss-feeds.index')
            ->with('success', 'Feed RSS actualizado exitosamente');
    }

    public function destroy(RssFeed $rssFeed)
    {
        $this->authorize('delete', $rssFeed);

        $rssFeed->delete();

        return redirect()
            ->route('admin.social.rss-feeds.index')
            ->with('success', 'Feed RSS eliminado exitosamente');
    }

    public function toggle(RssFeed $rssFeed)
    {
        $this->authorize('update', $rssFeed);

        $rssFeed->update(['active' => ! $rssFeed->active]);

        return back()->with('success', 'Estado del feed actualizado');
    }
}
