<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Session;

class SessionController extends Controller
{
    /**
     * Return all active sessions for the authenticated user as JSON.
     *
     * @return JsonResponse<array{sessions: array<int, array{id: string, ip_address: string|null, user_agent: string|null, last_activity: int, is_current: bool}>}>
     */
    public function index(Request $request): JsonResponse
    {
        $currentSessionId = $request->session()->getId();

        $sessions = Session::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (Session $s) => [
                'id' => $s->id,
                'ip_address' => $s->ip_address,
                'user_agent' => $s->user_agent,
                'last_activity' => $s->last_activity,
                'is_current' => $s->id === $currentSessionId,
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * Revoke a specific session by ID (cannot revoke the current session this way).
     */
    public function destroy(Request $request, string $sessionId): JsonResponse
    {
        $currentSessionId = $request->session()->getId();

        if ($sessionId === $currentSessionId) {
            return response()->json(['message' => 'No puedes revocar la sesión actual desde aquí.'], 422);
        }

        $deleted = Session::query()
            ->where('user_id', Auth::id())
            ->where('id', $sessionId)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Sesión no encontrada.'], 404);
        }

        return response()->json(['message' => 'Sesión revocada correctamente.']);
    }

    /**
     * Revoke all sessions except the current one (requires password confirmation).
     */
    public function destroyOthers(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }

        $currentSessionId = $request->session()->getId();

        Session::query()
            ->where('user_id', Auth::id())
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return response()->json(['message' => 'Todas las demás sesiones han sido cerradas.']);
    }
}
