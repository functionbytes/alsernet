<?php

namespace Modules\Helpdesk\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreApiTokenRequest;

class ApiTokensController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.view');
    }

    public function index(Request $request): View
    {
        $tokens = $request->user()->tokens()
            ->latest()
            ->get();

        return view('helpdesk::settings.profile.tokens', compact('tokens'));
    }

    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Read-only is the safe default. Write/manage must be explicitly
        // granted by the person creating the token.
        $abilities = array_values(array_unique($data['abilities'] ?? ['helpdesk.read']));
        $created = $request->user()->createToken($data['name'], $abilities);

        return redirect()
            ->route('settings.helpdesk.profile.tokens.index')
            ->with('success', 'Token creado correctamente. Cópialo ahora — no podrás verlo de nuevo.')
            ->with('plain_token', $created->plainTextToken);
    }

    public function destroy(Request $request, string $tokenId): RedirectResponse
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();

        if ($token) {
            $token->delete();

            return redirect()
                ->route('settings.helpdesk.profile.tokens.index')
                ->with('success', 'Token revocado.');
        }

        return redirect()
            ->route('settings.helpdesk.profile.tokens.index')
            ->with('error', 'Token no encontrado.');
    }
}
