<?php

namespace Modules\Chat\Http\Controllers\Settings\Channels;

use App\Models\OAuthSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Services\Channels\Facebook\ApiClient;

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

        return view('Chat::settings.channels.facebook-pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new Facebook page.
     */
    public function create()
    {
        // Generate OAuth URL
        $oauthUrl = $this->facebookApi->getOAuthUrl();

        return view('Chat::settings.channels.facebook-pages.create', compact('oauthUrl'));
    }

    /**
     * Handle OAuth callback from Facebook.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()
                ->route('settings.chat.channels.facebook-pages.index')
                ->with('error', 'Facebook authorization was cancelled or failed.');
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()
                ->route('settings.chat.channels.facebook-pages.index')
                ->with('error', 'No authorization code received from Facebook.');
        }

        try {
            // Exchange code for access token
            $accessToken = $this->facebookApi->getAccessTokenFromCode($code);

            // Get user's pages
            $pages = $this->facebookApi->getUserPages($accessToken);

            // Store OAuth session data securely in database
            $sessionToken = OAuthSession::store('facebook', [
                'pages' => $pages,
                'user_token' => $accessToken,
            ], auth()->id());

            return redirect()->route('settings.chat.channels.facebook-pages.select', ['token' => $sessionToken]);
        } catch (\Exception $e) {
            return redirect()
                ->route('settings.chat.channels.facebook-pages.index')
                ->with('error', 'Failed to connect to Facebook: '.$e->getMessage());
        }
    }

    /**
     * Show page selection form.
     */
    public function select(Request $request)
    {
        // Retrieve OAuth session from database using token
        $token = $request->query('token');
        if (! $token) {
            return redirect()
                ->route('settings.chat.channels.facebook-pages.create')
                ->with('error', 'Invalid or expired session. Please authorize again.');
        }

        $sessionData = OAuthSession::retrieve($token);
        if (! $sessionData) {
            return redirect()
                ->route('settings.chat.channels.facebook-pages.create')
                ->with('error', 'Session expired. Please authorize again.');
        }

        $pages = $sessionData['pages'] ?? [];
        if (empty($pages)) {
            return redirect()
                ->route('settings.chat.channels.facebook-pages.create')
                ->with('error', 'No Facebook pages found. Please authorize again.');
        }

        // Store token in session temporarily for store() method
        session(['_oauth_token' => $token]);

        return view('Chat::settings.channels.facebook-pages.select', compact('pages'));
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

        // Retrieve OAuth session data
        $token = session('_oauth_token');
        if (! $token) {
            return back()->with('error', 'Session expired. Please authorize again.');
        }

        // Consume the token (retrieve + delete) now that we're actually storing the channel
        $sessionData = OAuthSession::consume($token);
        if (! $sessionData) {
            return back()->with('error', 'Session expired. Please authorize again.');
        }

        $pages = $sessionData['pages'] ?? [];
        $userToken = $sessionData['user_token'] ?? null;

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
            $facebookPage = Facebook::create([
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
                'channel_type' => Facebook::class,
                'name' => $validated['inbox_name'],
                'timezone' => config('app.timezone'),
            ]);

            // Subscribe to page webhooks
            $this->facebookApi->subscribePageToApp($pageAccessToken);

            DB::commit();

            // Clear temporary session token (OAuth session auto-deleted by retrieve())
            session()->forget('_oauth_token');

            return redirect()
                ->route('settings.chat.channels.facebook-pages.index')
                ->with('success', 'Facebook Page connected successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Facebook Store Exception Caught', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()
                ->with('error', 'Failed to create Facebook Page channel: '.$e->getMessage());
        }
    }

    /**
     * Display the specified Facebook page.
     */
    public function show(Facebook $facebookPage)
    {
        $facebookPage->load('inbox');

        return view('Chat::settings.channels.facebook-pages.show', compact('facebookPage'));
    }

    /**
     * Show the form for editing the Facebook page.
     */
    public function edit(Facebook $facebookPage)
    {
        $facebookPage->load('inbox');

        return view('Chat::settings.channels.facebook-pages.edit', compact('facebookPage'));
    }

    /**
     * Update the specified Facebook page.
     */
    public function update(Request $request, Facebook $facebookPage)
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
                ->route('settings.chat.channels.facebook-pages.show', $facebookPage)
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
    public function destroy(Facebook $facebookPage)
    {
        try {
            $facebookPage->inbox?->delete();
            $facebookPage->delete();

            return redirect()
                ->route('settings.chat.channels.facebook-pages.index')
                ->with('success', 'Facebook Page disconnected successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to disconnect Facebook Page: '.$e->getMessage());
        }
    }

    /**
     * Reauthorize the Facebook page.
     */
    public function reauthorize(Facebook $facebookPage)
    {
        // Store page ID in session for reauthorization
        session(['reauthorize_facebook_page_id' => $facebookPage->id]);

        // Redirect to OAuth
        $oauthUrl = $this->facebookApi->getOAuthUrl();

        return redirect($oauthUrl);
    }
}
