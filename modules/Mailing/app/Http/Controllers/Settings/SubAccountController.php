<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\SendingServer;
use Modules\Mailing\Models\SubAccount;

class SubAccountController extends Controller
{
    /**
     * Display a listing of sub-accounts.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.sub-accounts.view');

        $subAccounts = SubAccount::whereHas('sendingServer', function ($query) {
            $query->where('user_id', auth()->id());
        })
            ->with('sendingServer')
            ->orderBy('name')
            ->paginate(15);

        return view('mailing::settings.sub-accounts.index', [
            'subAccounts' => $subAccounts,
        ]);
    }

    /**
     * Show the form for creating a new sub-account.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.sub-accounts.create');

        $sendingServers = SendingServer::where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('mailing::settings.sub-accounts.create', [
            'sendingServers' => $sendingServers,
        ]);
    }

    /**
     * Store a newly created sub-account.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.sub-accounts.create');

        try {
            $validated = $request->validate([
                'sending_server_id' => 'required|exists:mailing_sending_servers,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:active,suspended,inactive',

                // Credentials
                'api_key' => 'nullable|string',
                'username' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',

                // Permissions
                'can_create_campaigns' => 'boolean',
                'can_manage_subscribers' => 'boolean',
                'can_view_reports' => 'boolean',
                'can_create_sending_servers' => 'boolean',

                // Quotas
                'email_quota_per_day' => 'nullable|integer|min:0',
                'email_quota_per_month' => 'nullable|integer|min:0',
                'subscriber_quota' => 'nullable|integer|min:0',
                'campaign_quota' => 'nullable|integer|min:0',
            ]);

            // Verify the sending server belongs to the authenticated user
            $sendingServer = SendingServer::where('user_id', auth()->id())
                ->findOrFail($validated['sending_server_id']);

            $validated['user_id'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'active';

            SubAccount::create($validated);

            return redirect()
                ->route('settings.mailing.sub-accounts.index')
                ->with('success', 'Subcuenta creada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la subcuenta: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified sub-account.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.sub-accounts.edit');

        $subAccount = SubAccount::whereHas('sendingServer', function ($query) {
            $query->where('user_id', auth()->id());
        })->findOrFail($id);

        $sendingServers = SendingServer::where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('mailing::settings.sub-accounts.edit', [
            'subAccount' => $subAccount,
            'sendingServers' => $sendingServers,
        ]);
    }

    /**
     * Update the specified sub-account.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.sub-accounts.edit');

        try {
            $subAccount = SubAccount::whereHas('sendingServer', function ($query) {
                $query->where('user_id', auth()->id());
            })->findOrFail($id);

            $validated = $request->validate([
                'sending_server_id' => 'required|exists:mailing_sending_servers,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:active,suspended,inactive',

                // Credentials
                'api_key' => 'nullable|string',
                'username' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',

                // Permissions
                'can_create_campaigns' => 'boolean',
                'can_manage_subscribers' => 'boolean',
                'can_view_reports' => 'boolean',
                'can_create_sending_servers' => 'boolean',

                // Quotas
                'email_quota_per_day' => 'nullable|integer|min:0',
                'email_quota_per_month' => 'nullable|integer|min:0',
                'subscriber_quota' => 'nullable|integer|min:0',
                'campaign_quota' => 'nullable|integer|min:0',
            ]);

            // Verify the sending server belongs to the authenticated user
            SendingServer::where('user_id', auth()->id())
                ->findOrFail($validated['sending_server_id']);

            $subAccount->update($validated);

            return redirect()
                ->route('settings.mailing.sub-accounts.index')
                ->with('success', 'Subcuenta actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la subcuenta: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified sub-account.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.sub-accounts.delete');

        try {
            $subAccount = SubAccount::whereHas('sendingServer', function ($query) {
                $query->where('user_id', auth()->id());
            })->findOrFail($id);

            $subAccount->delete();

            return redirect()
                ->route('settings.mailing.sub-accounts.index')
                ->with('success', 'Subcuenta eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la subcuenta: '.$e->getMessage());
        }
    }

    /**
     * Filter sub-accounts by sending server (AJAX endpoint).
     */
    public function filterByServer(Request $request): JsonResponse
    {
        Gate::authorize('mailing.settings.sub-accounts.view');

        try {
            $sendingServerId = $request->input('sending_server_id');

            $subAccounts = SubAccount::where('sending_server_id', $sendingServerId)
                ->whereHas('sendingServer', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->orderBy('name')
                ->get(['id', 'name', 'status', 'email']);

            return response()->json([
                'success' => true,
                'data' => $subAccounts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar subcuentas: '.$e->getMessage(),
            ], 422);
        }
    }
}
