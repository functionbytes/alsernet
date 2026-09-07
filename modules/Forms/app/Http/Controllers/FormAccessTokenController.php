<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\StoreFormAccessTokenRequest;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormAccessToken;

class FormAccessTokenController extends Controller
{
    public function index(Request $request, Form $form): View|JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $tokens = FormAccessToken::query()
            ->where('form_id', $form->id)
            ->with('creator')
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            $mapped = $tokens->map(fn ($token) => [
                'token' => $token,
                'status' => $this->resolveStatus($token),
            ]);

            return response()->json(['data' => $mapped]);
        }

        return view('forms::settings.forms.access-tokens.index', compact('form', 'tokens'));
    }

    public function store(StoreFormAccessTokenRequest $request, Form $form): JsonResponse
    {
        $validated = $request->validated();

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
