<?php

namespace Modules\Reviews\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Reviews\Models\UserNotificationPreference;

trait HasNotificationPreferences
{
    public function reviewNotificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }
}
