<?php

namespace Modules\Helpdesk\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * Endpoints to obtain and revoke API tokens.
 */
class AuthController extends Controller
{
    /**
     * Login
     *
     * Obtain a Sanctum API token using email and password.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Agent email address. Example: agent@example.com
     * @bodyParam password string required Agent password. Example: secret
     *
     * @response 200 {
     *   "success": true,
     *   "token": "1|abc123...",
     *   "token_type": "Bearer"
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Credenciales incorrectas."
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (! $user->can('helpdesk.api.access')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para acceder a la API de Helpdesk.',
            ], 403);
        }

        $token = $user->createToken('helpdesk-api')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout
     *
     * Revoke the current API token.
     *
     * @response 200 {"success": true, "message": "Token revocado correctamente."}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token revocado correctamente.',
        ]);
    }
}
