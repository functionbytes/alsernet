<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormAccessToken;

class FormAccessTokenController extends Controller
{
    public function index(Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $tokens = FormAccessToken::query()
            ->where('form_id', $form->id)
            ->with('creator')
            ->latest()
            ->get()
            ->map(function (FormAccessToken $token) {
                return [
                    'token' => $token,
                    'status' => $this->resolveStatus($token),
                ];
            });

        return response()->json(['data' => $tokens]);
    }

    public function store(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $validated = $request->validate([
            'email' => 'nullable|email',
            'max_uses' => 'integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $accessToken = $form->accessTokens()->create([
            'token' => Str::random(48),
            'email' => $validated['email'] ?? null,
            'max_uses' => $validated['max_uses'] ?? 1,
            'expires_at' => $validated['expires_at'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'data' => $accessToken,
            'access_url' => route('forms.public.access', $accessToken->token),
        ], 201);
    }

    public function destroy(Form $form, FormAccessToken $token): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $token->delete();

        return response()->json(['message' => 'Token eliminado correctamente.']);
    }

    private function resolveStatus(FormAccessToken $token): string
    {
        if ($token->times_used >= $token->max_uses) {
            return 'agotado';
        }

        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return 'expirado';
        }

        return 'válido';
    }
}
