<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Channels\Instagram;
use Modules\HelpdeskChat\Services\Channels\Instagram\ApiClient;

class InstagramController extends Controller
{
    protected ApiClient $instagramApi;

    public function __construct(ApiClient $instagramApi)
    {
        $this->instagramApi = $instagramApi;
    }

    /**
     * Display a listing of Instagram accounts.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $instagrams = Instagram::where('account_id', $accountId)
            ->with(['inbox', 'facebookPage'])
            ->latest()
            ->get();

        return view('helpdeskchat::admin.settings.channels.instagrams.index', compact('instagrams'));
    }

    /**
     * Show the form for creating a new Instagram account.
     */
    public function create()
    {
        // Generate OAuth URL
        $oauthUrl = $this->instagramApi->getOAuthUrl();

        return view('helpdeskchat::admin.settings.channels.instagrams.create', compact('oauthUrl'));
    }

    /**
     * Handle OAuth callback from Instagram.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()
                ->route('admin.helpdesk.channels.instagrams.index')
                ->with('error', 'Instagram authorization was cancelled or failed.');
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()
                ->route('admin.helpdesk.channels.instagrams.index')
                ->with('error', 'No authorization code received from Instagram.');
        }

        try {
            // Exchange code for access token
            $accessToken = $this->instagramApi->getAccessTokenFromCode($code);

            // Get user's Instagram accounts
            $accounts = $this->instagramApi->getUserInstagramAccounts($accessToken);

            // Check if this is a reauthorization
            $reauthorizeId = session('reauthorize_instagram_id');
            if ($reauthorizeId) {
                $instagram = Instagram::find($reauthorizeId);
                if ($instagram && count($accounts) > 0) {
                    // Find the matching account
                    $matchingAccount = collect($accounts)->firstWhere('id', $instagram->instagram_id);

                    if ($matchingAccount) {
                        // Get page access token from the account's Facebook page
                        $pageAccessToken = $matchingAccount['page_access_token'] ?? null;

                        // Update tokens
                        $instagram->update([
                            'page_access_token' => $pageAccessToken,
                            'user_access_token' => $accessToken,
                        ]);

                        // Clear session
                        session()->forget('reauthorize_instagram_id');

                        \Log::info('Instagram tokens updated successfully', [
                            'instagram_id' => $instagram->id,
                            'has_page_token' => ! empty($pageAccessToken),
                            'has_user_token' => ! empty($accessToken),
                        ]);

                        return redirect()
                            ->route('admin.helpdesk.channels.instagrams.index')
                            ->with('success', 'Instagram account reauthorized successfully!');
                    }
                }

                // If we get here, reauthorization failed
                session()->forget('reauthorize_instagram_id');

                return redirect()
                    ->route('admin.helpdesk.channels.instagrams.index')
                    ->with('error', 'Failed to reauthorize Instagram account.');
            }

            // Normal flow: Store in session for selection
            session(['instagram_accounts' => $accounts, 'instagram_user_token' => $accessToken]);

            return redirect()->route('admin.helpdesk.channels.instagrams.select');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.helpdesk.channels.instagrams.index')
                ->with('error', 'Failed to connect to Instagram: '.$e->getMessage());
        }
    }

    /**
     * Show account selection form.
     */
    public function select()
    {
        $accounts = session('instagram_accounts', []);

        if (empty($accounts)) {
            return redirect()
                ->route('admin.helpdesk.channels.instagrams.create')
                ->with('error', 'No Instagram Business accounts found. Please authorize again.');
        }

        return view('helpdeskchat::admin.settings.channels.instagrams.select', compact('accounts'));
    }

    /**
     * Store a newly created Instagram account.
     */
    public function store(Request $request)
    {
        \Log::info('Instagram Store Request', [
            'post_data' => $request->all(),
            'session_accounts' => session('instagram_accounts'),
            'session_token_exists' => session()->has('instagram_user_token'),
        ]);

        $validated = $request->validate([
            'instagram_id' => 'required|string',
            'inbox_name' => 'required|string|max:255',
        ]);

        $accounts = session('instagram_accounts', []);
        $userToken = session('instagram_user_token');

        \Log::info('Instagram Store Session Data', [
            'accounts_count' => count($accounts),
            'accounts' => $accounts,
            'has_token' => ! empty($userToken),
            'searching_for_id' => $validated['instagram_id'],
        ]);

        $selectedAccount = collect($accounts)->firstWhere('id', $validated['instagram_id']);

        \Log::info('After firstWhere', [
            'selected_account' => $selectedAccount,
            'is_null' => is_null($selectedAccount),
        ]);

        if (! $selectedAccount) {
            \Log::warning('Instagram Account Not Found in Session', [
                'requested_id' => $validated['instagram_id'],
                'available_accounts' => $accounts,
            ]);

            return back()->with('error', 'Selected Instagram account not found.');
        }

        \Log::info('Starting Instagram channel creation', [
            'account_id' => $request->user()->account_id,
            'instagram_id' => $selectedAccount['id'],
        ]);

        // Check if this Instagram account is already connected
        $existingInstagram = Instagram::where('instagram_id', $selectedAccount['id'])->first();
        if ($existingInstagram) {
            \Log::warning('Instagram account already exists', [
                'instagram_id' => $selectedAccount['id'],
                'existing_record_id' => $existingInstagram->id,
            ]);

            return back()
                ->withInput()
                ->with('error', 'This Instagram account is already connected. Please select a different account or disconnect the existing one first.');
        }

        DB::beginTransaction();
        try {
            // Find or create the Facebook Page record
            $facebookPageId = null;
            if (! empty($selectedAccount['facebook_page_id'])) {
                $facebookPage = \Modules\HelpdeskChat\Models\Channels\Facebook::where('page_id', $selectedAccount['facebook_page_id'])->first();
                if ($facebookPage) {
                    $facebookPageId = $facebookPage->id;
                    \Log::info('Found existing Facebook Page', ['id' => $facebookPageId]);
                }
            }

            // Create the Instagram channel
            $instagram = Instagram::create([
                'account_id' => $request->user()->account_id,
                'instagram_id' => $selectedAccount['id'],
                'username' => $selectedAccount['username'] ?? null,
                'user_access_token' => $userToken,
                'page_access_token' => $selectedAccount['page_access_token'] ?? null,
                'facebook_page_id' => $facebookPageId,
            ]);

            \Log::info('Instagram channel created', ['id' => $instagram->id]);

            // Create the inbox for this channel
            Inbox::create([
                'account_id' => $request->user()->account_id,
                'channel_id' => $instagram->id,
                'channel_type' => Instagram::class,
                'name' => $validated['inbox_name'],
                'timezone' => config('app.timezone'),
            ]);

            \Log::info('Inbox created, subscribing to webhooks');

            // Subscribe to webhooks
            $this->instagramApi->subscribeToWebhooks(
                $userToken,
                $selectedAccount['id']
            );

            \Log::info('Webhooks subscribed, committing transaction');

            DB::commit();

            // Clear session data
            session()->forget(['instagram_accounts', 'instagram_user_token']);

            return redirect()
                ->route('admin.helpdesk.channels.instagrams.index')
                ->with('success', 'Instagram account connected successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Instagram Store Exception Caught', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $errorMessage = 'This Instagram account is already connected.';

            \Log::info('Returning with error message', [
                'error_message' => $errorMessage,
                'session_before' => session()->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Display the specified Instagram account.
     */
    public function show(Instagram $instagram)
    {
        $instagram->load(['inbox', 'facebookPage']);

        return view('helpdeskchat::admin.settings.channels.instagrams.show', compact('instagram'));
    }

    /**
     * Show the form for editing the Instagram account.
     */
    public function edit(Instagram $instagram)
    {
        $instagram->load(['inbox', 'facebookPage']);

        return view('helpdeskchat::admin.settings.channels.instagrams.edit', compact('instagram'));
    }

    /**
     * Update the specified Instagram account.
     */
    public function update(Request $request, Instagram $instagram)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update inbox name
            if ($instagram->inbox) {
                $instagram->inbox->update(['name' => $validated['inbox_name']]);
            }

            DB::commit();

            return redirect()
                ->route('admin.helpdesk.channels.instagrams.show', $instagram)
                ->with('success', 'Instagram account updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to update Instagram account: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified Instagram account.
     */
    public function destroy(Instagram $instagram)
    {
        try {
            $instagram->delete();

            return redirect()
                ->route('admin.helpdesk.channels.instagrams.index')
                ->with('success', 'Instagram account disconnected successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to disconnect Instagram account: '.$e->getMessage());
        }
    }

    /**
     * Refresh the access token for the Instagram account.
     */
    public function refreshToken(Instagram $instagram)
    {
        try {
            $tokenData = $this->instagramApi->refreshAccessToken($instagram->user_access_token);

            $instagram->update([
                'user_access_token' => $tokenData['access_token'],
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 5184000), // Default 60 days
            ]);

            return back()->with('success', 'Access token refreshed successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to refresh token: '.$e->getMessage());
        }
    }

    /**
     * Reauthorize the Instagram account.
     */
    public function reauthorize(Instagram $instagram)
    {
        // Store Instagram ID in session for reauthorization
        session(['reauthorize_instagram_id' => $instagram->id]);

        // Redirect to OAuth
        $oauthUrl = $this->instagramApi->getOAuthUrl();

        return redirect($oauthUrl);
    }
}
