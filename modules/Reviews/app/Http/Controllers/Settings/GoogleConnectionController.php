<?php

namespace Modules\Reviews\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Events\ConnectionRevoked;
use Modules\Reviews\Http\Requests\StoreReviewGoogleConnectionRequest;
use Modules\Reviews\Jobs\SyncGoogleLocationsJob;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Services\GoogleAccountService;
use Modules\Reviews\Services\GoogleAuthService;

class GoogleConnectionController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $authService,
        private readonly GoogleAccountService $accountService
    ) {
        $this->authorizeResource(ReviewGoogleConnection::class, 'connection');
    }

    public function index(Request $request): View
    {
        $query = ReviewGoogleConnection::query()
            ->with('user')
            ->withCount('locations')
            ->latest();

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('google_email', 'like', "%{$search}%");
            });
        }

        $connections = $query->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => ReviewGoogleConnection::count(),
            'active' => ReviewGoogleConnection::where('status', ConnectionStatus::ACTIVE)
                ->whereDate('token_expires_at', '>', now())
                ->count(),
            'expired' => ReviewGoogleConnection::whereDate('token_expires_at', '<=', now())
                ->orWhereNull('token_expires_at')
                ->count(),
            'locations' => ReviewGoogleConnection::withCount('locations')
                ->get()
                ->sum('locations_count'),
        ];

        return view('reviews::settings.connections.index', compact('connections', 'stats'));
    }

    public function create(): View
    {
        return view('reviews::settings.connections.create');
    }

    public function store(StoreReviewGoogleConnectionRequest $request): RedirectResponse
    {
        $state = Str::random(32);

        $connection = ReviewGoogleConnection::query()->create([
            'user_id' => auth()->id(),
            'name' => $request->validated('name'),
            'status' => ConnectionStatus::PENDING,
        ]);

        session(['google_oauth_state' => $state, 'google_connection_id' => $connection->id]);

        $authUrl = $this->authService->getAuthUrl($state);

        return redirect()->away($authUrl);
    }

    public function reconnect(ReviewGoogleConnection $connection): RedirectResponse
    {
        $state = Str::random(32);

        session(['google_oauth_state' => $state, 'google_connection_id' => $connection->id]);

        $authUrl = $this->authService->getAuthUrl($state);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'state' => 'required|string',
            ]);

            // SECURITY FIX 1: Rate limiting to prevent brute-force attacks on OAuth callback
            $rateLimitKey = 'oauth-callback:'.$request->ip();
            if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
                abort(429, 'Too many OAuth attempts. Please try again later.');
            }
            RateLimiter::hit($rateLimitKey, 300); // 5 minutes

            // SECURITY FIX 2: Use hash_equals() to prevent timing attacks and pull() to invalidate state after use
            $sessionState = session()->pull('google_oauth_state');
            if (! hash_equals($sessionState ?? '', $request->input('state') ?? '')) {
                abort(403, 'Invalid OAuth state parameter');
            }

            $connectionId = session('google_connection_id');
            $connection = ReviewGoogleConnection::query()->findOrFail($connectionId);

            $tokenData = $this->authService->handleCallback($request->input('code'));

            $userInfo = $this->authService->getUserInfo($tokenData['access_token']);

            $connection->update([
                'google_email' => $userInfo['email'],
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                'scopes' => explode(' ', $tokenData['scope']),
                'status' => ConnectionStatus::ACTIVE,
                'connected_at' => now(),
            ]);

            $accounts = $this->accountService->listAccounts($connection);

            if (! empty($accounts)) {
                $firstAccount = $accounts[0];
                $connection->update([
                    'google_account_id' => $firstAccount['name'],
                ]);
            }

            SyncGoogleLocationsJob::dispatch($connection);

            // Clean up session (google_oauth_state already pulled above)
            session()->forget('google_connection_id');

            activity()
                ->performedOn($connection)
                ->causedBy(auth()->user())
                ->log('Google Business Profile connected successfully');

            return redirect()
                ->route('settings.reviews.connections.index')
                ->with('success', 'Conexión establecida correctamente. Sincronizando ubicaciones...');
        } catch (\Exception $e) {
            if (isset($connection)) {
                $connection->update([
                    'status' => ConnectionStatus::ERROR,
                    'last_error' => $e->getMessage(),
                ]);
            }

            return redirect()
                ->route('settings.reviews.connections.index')
                ->with('error', 'Error al conectar: '.$e->getMessage());
        }
    }

    public function destroy(ReviewGoogleConnection $connection): RedirectResponse
    {
        try {
            $this->authService->revokeToken($connection);

            event(new ConnectionRevoked($connection));

            $connection->delete();

            return redirect()
                ->route('settings.reviews.connections.index')
                ->with('success', 'Conexión revocada correctamente');
        } catch (\Exception $e) {
            return redirect()
                ->route('settings.reviews.connections.index')
                ->with('error', 'Error al revocar conexión: '.$e->getMessage());
        }
    }

    public function bulkRevoke(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|json',
        ]);

        $ids = json_decode($request->input('ids'), true);

        if (empty($ids) || ! is_array($ids)) {
            return redirect()
                ->route('settings.reviews.connections.index')
                ->with('error', 'No se seleccionaron conexiones para revocar');
        }

        try {
            $connections = ReviewGoogleConnection::query()
                ->whereIn('id', $ids)
                ->get();

            $revokedCount = 0;

            foreach ($connections as $connection) {
                // Check authorization for each connection
                $this->authorize('delete', $connection);

                $this->authService->revokeToken($connection);
                event(new ConnectionRevoked($connection));
                $connection->delete();
                $revokedCount++;
            }

            return redirect()
                ->route('settings.reviews.connections.index')
                ->with('success', "Se revocaron {$revokedCount} conexiones correctamente");
        } catch (\Exception $e) {
            return redirect()
                ->route('settings.reviews.connections.index')
                ->with('error', 'Error al revocar conexiones: '.$e->getMessage());
        }
    }
}
