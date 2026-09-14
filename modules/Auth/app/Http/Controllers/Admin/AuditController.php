<?php

namespace Modules\Auth\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Auth\Models\ImpersonationLog;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Services\AuthService;

/**
 * Admin-only audit view for authentication events across all users.
 * Requires `auth.audit.view` permission.
 */
class AuditController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function loginAttempts(Request $request): View
    {
        $this->authorize('viewAudit', LoginAttempt::class);

        $attempts = LoginAttempt::query()
            ->with('user:id,firstname,lastname,email')
            ->when($request->filled('email'), fn ($q) => $q->where('email', 'like', '%'.$request->input('email').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('ip'), fn ($q) => $q->where('ip_address', $request->input('ip')))
            ->orderByDesc('attempted_at')
            ->paginate((int) $request->input('per_page', 25))
            ->withQueryString();

        return view('auth::admin.audit.login-attempts', [
            'attempts' => $attempts,
            'filters' => $request->only(['email', 'status', 'ip']),
        ]);
    }

    public function impersonations(Request $request): View
    {
        $this->authorize('viewAudit', ImpersonationLog::class);

        $logs = ImpersonationLog::query()
            ->with(['impersonator:id,firstname,lastname,email', 'impersonated:id,firstname,lastname,email'])
            ->orderByDesc('started_at')
            ->paginate((int) $request->input('per_page', 25))
            ->withQueryString();

        return view('auth::admin.audit.impersonations', [
            'logs' => $logs,
        ]);
    }

    /**
     * Force-logout a user: revoke all sessions and API tokens.
     */
    public function forceLogout(Request $request, User $user): JsonResponse
    {
        $this->authorize('viewAudit', LoginAttempt::class);

        $this->auth->forceLogoutAll($user);

        return response()->json([
            'success' => true,
            'message' => "Todas las sesiones de {$user->firstname} {$user->lastname} han sido cerradas.",
        ]);
    }

    /**
     * Manually unlock a locked account.
     */
    public function unlockAccount(Request $request, User $user): JsonResponse
    {
        $this->authorize('viewAudit', LoginAttempt::class);

        $user->forceFill([
            'failed_login_count' => 0,
            'locked_until' => null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta desbloqueada.',
        ]);
    }

    /**
     * Restore a soft-deleted user account (before the hard-delete job runs).
     */
    public function restoreAccount(Request $request, int $userId): JsonResponse
    {
        $this->authorize('viewAudit', LoginAttempt::class);

        $user = User::withTrashed()->find($userId);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        if (! $user->trashed()) {
            return response()->json(['success' => false, 'message' => 'El usuario no está eliminado.'], 422);
        }

        $restoredEmail = preg_replace('/^deleted_\d+_/', '', $user->email);

        DB::transaction(function () use ($user, $restoredEmail): void {
            $user->restore();
            $user->forceFill([
                'email' => $restoredEmail,
                'available' => true,
            ])->save();
        });

        return response()->json([
            'success' => true,
            'message' => "Cuenta {$restoredEmail} restaurada.",
        ]);
    }
}
