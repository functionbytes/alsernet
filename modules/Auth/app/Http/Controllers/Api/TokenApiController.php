<?php

namespace Modules\Auth\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'abilities' => $t->abilities,
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
                'is_current' => $request->user()->currentAccessToken()?->id === $t->id,
            ]);

        return response()->json(['success' => true, 'data' => $tokens]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:100'],
        ]);

        $token = $request->user()->createToken(
            $request->input('name'),
            $request->input('abilities', ['*']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Token generado.',
            'data' => [
                'id' => $token->accessToken->id,
                'token' => $token->plainTextToken,
                'name' => $token->accessToken->name,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => 'Token no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Token revocado.']);
    }
}
