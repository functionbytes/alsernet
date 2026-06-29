<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Social\Enums\AccountStatus;
use Modules\Social\Enums\SocialNetwork;
use Modules\Social\Http\Requests\StoreSocialAccountRequest;
use Modules\Social\Http\Requests\UpdateSocialAccountRequest;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Services\SocialAccountService;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = SocialAccount::with('account')
            ->where('account_id', auth()->user()->account_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('social::accounts.index', compact('accounts'));
    }

    public function create()
    {
        $networks = SocialNetwork::cases();

        return view('social::accounts.create', compact('networks'));
    }

    /**
     * Show account selection from OAuth callback
     */
    public function select()
    {
        $oauthData = session('oauth_accounts');

        if (! $oauthData) {
            return redirect()
                ->route('admin.social.accounts.index')
                ->with('error', 'No hay cuentas disponibles para conectar');
        }

        return view('social::accounts.select', [
            'network' => $oauthData['network'],
            'accounts' => $oauthData['accounts'],
        ]);
    }

    /**
     * Save selected accounts from OAuth
     */
    public function saveSelected(Request $request, SocialAccountService $service)
    {
        $oauthData = session('oauth_accounts');

        if (! $oauthData) {
            return redirect()
                ->route('admin.social.accounts.index')
                ->with('error', 'No hay cuentas disponibles para conectar');
        }

        $validated = $request->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'required|string',
        ]);

        $savedCount = $service->saveSelectedAccounts(
            $oauthData,
            $validated['selected'],
            auth()->user()->account_id
        );

        session()->forget('oauth_accounts');

        $message = $savedCount === 1
            ? 'Cuenta conectada exitosamente'
            : "{$savedCount} cuentas conectadas exitosamente";

        return redirect()
            ->route('admin.social.accounts.index')
            ->with('success', $message);
    }

    public function store(StoreSocialAccountRequest $request)
    {
        $this->authorize('create', SocialAccount::class);

        $validated = $request->validated();

        SocialAccount::create([
            'account_id' => auth()->user()->account_id,
            'network' => $validated['network'],
            'username' => $validated['username'],
            'name' => $validated['name'],
            'access_token' => $validated['access_token'] ?? null,
            'status' => AccountStatus::ACTIVE,
        ]);

        return redirect()
            ->route('admin.social.accounts.index')
            ->with('success', 'Cuenta social agregada exitosamente');
    }

    public function show(SocialAccount $account)
    {
        $this->authorize('view', $account);

        return view('social::accounts.show', compact('account'));
    }

    public function edit(SocialAccount $account)
    {
        $this->authorize('update', $account);

        $networks = SocialNetwork::cases();

        return view('social::accounts.edit', compact('account', 'networks'));
    }

    public function update(UpdateSocialAccountRequest $request, SocialAccount $account)
    {
        $this->authorize('update', $account);

        $account->update($request->validated());

        return redirect()
            ->route('admin.social.accounts.index')
            ->with('success', 'Cuenta social actualizada exitosamente');
    }

    public function destroy(SocialAccount $account)
    {
        $this->authorize('delete', $account);

        $account->delete();

        return redirect()
            ->route('admin.social.accounts.index')
            ->with('success', 'Cuenta social eliminada exitosamente');
    }

    public function reconnect(SocialAccount $account)
    {
        $this->authorize('update', $account);

        // Logic to redirect to OAuth flow for the specific network
        return redirect()->route('social.auth.'.$account->network->value);
    }

    public function sync(SocialAccount $account)
    {
        $this->authorize('update', $account);

        // Logic to sync account data from the social network
        $account->update([
            'last_sync_at' => now(),
        ]);

        return redirect()
            ->route('admin.social.accounts.index')
            ->with('success', 'Cuenta sincronizada exitosamente');
    }
}
