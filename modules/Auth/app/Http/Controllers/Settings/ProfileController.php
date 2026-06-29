<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Auth\Http\Requests\UpdateProfileInfoRequest;

class ProfileController extends Controller
{
    /**
     * Display user profile with tabs.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $activeTab = $request->get('tab', 'account');

        return view('auth::settings.profile.index', [
            'user' => $user,
            'activeTab' => $activeTab,
        ]);
    }

    public function sessions(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'sessions']));
    }

    public function twoFactor(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'two-factor']));
    }

    public function password(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'password']));
    }

    public function devices(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'devices']));
    }

    public function apiTokens(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'api-tokens']));
    }

    /**
     * Update the authenticated user's personal information.
     */
    public function updateInfo(UpdateProfileInfoRequest $request): RedirectResponse
    {
        auth()->user()->update($request->validated());

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Upload and store a new avatar for the authenticated user.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'avatar.required' => 'Selecciona una imagen.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.max' => 'La imagen no puede superar los 2 MB.',
            'avatar.mimes' => 'Solo se permiten imágenes JPG, PNG o WebP.',
        ]);

        $user = auth()->user();

        if ($user->user_img && Storage::disk('public')->exists($user->user_img)) {
            Storage::disk('public')->delete($user->user_img);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['user_img' => $path]);

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function deleteAvatar(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->user_img && Storage::disk('public')->exists($user->user_img)) {
            Storage::disk('public')->delete($user->user_img);
        }

        $user->update(['user_img' => null]);

        return back()->with('success', 'Foto de perfil eliminada.');
    }
}
