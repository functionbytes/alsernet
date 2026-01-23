<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Channels\Facebook;
use Modules\HelpdeskChat\Services\Channels\Facebook\ApiClient;

class FacebookController extends Controller
{
    protected ApiClient $facebookApi;

    public function __construct(ApiClient $facebookApi)
    {
        $this->facebookApi = $facebookApi;
    }

    /**
     * Display a listing of Facebook pages.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $pages = Facebook::where('account_id', $accountId)
            ->with('inbox')
            ->latest()
            ->get();

        return view('helpdeskchat::admin.settings.channels.facebook-pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new Facebook page.
     */
    public function create()
    {
        // Generate OAuth URL
        $oauthUrl = $this->facebookApi->getOAuthUrl();

        return view('helpdeskchat::admin.settings.channels.facebook-pages.create', compact('oauthUrl'));
    }

    /**
     * Handle OAuth callback from Facebook.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()
                ->route('admin.helpdesk.channels.facebook-pages.index')
                ->with('error', 'Facebook authorization was cancelled or failed.');
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()
                ->route('admin.helpdesk.channels.facebook-pages.index')
                ->with('error', 'No authorization code received from Facebook.');
        }

        try {
            // Exchange code for access token
            $accessToken = $this->facebookApi->getAccessTokenFromCode($code);

            // Get user's pages
            $pages = $this->facebookApi->getUserPages($accessToken);

            // Store in session for selection
            session(['facebook_pages' => $pages, 'facebook_user_token' => $accessToken]);

            return redirect()->route('admin.helpdesk.channels.facebook-pages.select');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.helpdesk.channels.facebook-pages.index')
                ->with('error', 'Failed to connect to Facebook: '.$e->getMessage());
        }
    }

    /**
     * Show page selection form.
     */
    public function select()
    {
        $pages = session('facebook_pages', []);

        if (empty($pages)) {
            return redirect()
                ->route('admin.helpdesk.channels.facebook-pages.create')
                ->with('error', 'No Facebook pages found. Please authorize again.');
        }

        return view('helpdeskchat::admin.settings.channels.facebook-pages.select', compact('pages'));
    }

    /**
     * Store a newly created Facebook page.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|string',
            'inbox_name' => 'required|string|max:255',
        ]);

        $pages = session('facebook_pages', []);
        $userToken = session('facebook_user_token');

        $selectedPage = collect($pages)->firstWhere('id', $validated['page_id']);

        if (! $selectedPage) {
            return back()->with('error', 'Selected page not found.');
        }

        DB::beginTransaction();
        try {
            // Get long-lived page access token
            $pageAccessToken = $this->facebookApi->getLongLivedPageToken(
                $selectedPage['access_token']
            );

            // Create the Facebook page channel
            $facebookPage = FacebookPage::create([
                'account_id' => $request->user()->account_id,
                'page_id' => $selectedPage['id'],
                'page_name' => $selectedPage['name'],
                'page_access_token' => $pageAccessToken,
                'user_access_token' => $userToken,
            ]);

            // Create the inbox for this channel
            Inbox::create([
                'account_id' => $request->user()->account_id,
                'channel_id' => $facebookPage->id,
                'channel_type' => FacebookPage::class,
                'name' => $validated['inbox_name'],
                'timezone' => config('app.timezone'),
            ]);

            // Subscribe to page webhooks
            $this->facebookApi->subscribePageToApp($pageAccessToken);

            DB::commit();

            // Clear session data
            session()->forget(['facebook_pages', 'facebook_user_token']);

            return redirect()
                ->route('admin.helpdesk.channels.facebook-pages.index')
                ->with('success', 'Facebook Page connected successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Failed to create Facebook Page channel: '.$e->getMessage());
        }
    }

    /**
     * Display the specified Facebook page.
     */
    public function show(FacebookPage $facebookPage)
    {
        $facebookPage->load('inbox');

        return view('helpdeskchat::admin.settings.channels.facebook-pages.show', compact('facebookPage'));
    }

    /**
     * Show the form for editing the Facebook page.
     */
    public function edit(FacebookPage $facebookPage)
    {
        $facebookPage->load('inbox');

        return view('helpdeskchat::admin.settings.channels.facebook-pages.edit', compact('facebookPage'));
    }

    /**
     * Update the specified Facebook page.
     */
    public function update(Request $request, FacebookPage $facebookPage)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update inbox name
            if ($facebookPage->inbox) {
                $facebookPage->inbox->update(['name' => $validated['inbox_name']]);
            }

            DB::commit();

            return redirect()
                ->route('admin.helpdesk.channels.facebook-pages.show', $facebookPage)
                ->with('success', 'Facebook Page updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to update Facebook Page: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified Facebook page.
     */
    public function destroy(FacebookPage $facebookPage)
    {
        try {
            $facebookPage->delete();

            return redirect()
                ->route('admin.helpdesk.channels.facebook-pages.index')
                ->with('success', 'Facebook Page disconnected successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to disconnect Facebook Page: '.$e->getMessage());
        }
    }

    /**
     * Reauthorize the Facebook page.
     */
    public function reauthorize(FacebookPage $facebookPage)
    {
        // Store page ID in session for reauthorization
        session(['reauthorize_facebook_page_id' => $facebookPage->id]);

        // Redirect to OAuth
        $oauthUrl = $this->facebookApi->getOAuthUrl();

        return redirect($oauthUrl);
    }
}
