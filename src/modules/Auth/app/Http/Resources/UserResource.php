<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullName' => trim(($this->firstname ?? '').' '.($this->lastname ?? '')),
            'email' => $this->email,
            'identification' => $this->identification,
            'cellphone' => $this->cellphone,
            'avatarUrl' => $this->avatar_url ?? null,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'available' => (bool) $this->available,
            'verified' => (bool) $this->verified,
            'mustChangePassword' => (bool) $this->must_change_password,
            'twoFactorEnabled' => $this->hasTwoFactorEnabled(),
            'lastLoginAt' => $this->last_login_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->when(
                $request->user()?->id === $this->id,
                fn () => $this->getAllPermissions()->pluck('name'),
            ),
        ];
    }
}
