<?php

declare(strict_types=1);

namespace Modules\Captcha\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CaptchaSettingPolicy
{
    use HandlesAuthorization;

    public function update(User $user): bool
    {
        return $user->can('captcha.settings.update')
            || $user->hasRole(['admin', 'super-admin']);
    }
}
