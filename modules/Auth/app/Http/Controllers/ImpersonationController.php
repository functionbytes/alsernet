<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Services\ImpersonationService;
use RuntimeException;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly ImpersonationService $service,
    ) {}

    public function start(Request $request, int|User $user): JsonResponse|RedirectResponse
    {
        $target = $user instanceof User ? $user : User::findOrFail($user);

        try {
            $this->service->start(Auth::user(), $target, $request, $request->input('reason'));
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            abort(403, $e->getMessage());
        }

        $redirect = route($target->redirectRouteName());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => $redirect]);
        }

        return redirect($redirect);
    }

    public function stop(Request $request): JsonResponse|RedirectResponse
    {
        $impersonator = $this->service->stop($request);

        if (! $impersonator) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No estás impersonando.'], 422);
            }

            return redirect()->route('auth.login');
        }

        $redirect = route($impersonator->redirectRouteName());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => $redirect]);
        }

        return redirect($redirect);
    }
}
