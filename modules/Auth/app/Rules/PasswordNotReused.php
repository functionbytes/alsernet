<?php

namespace Modules\Auth\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Auth\Services\PasswordHistoryService;

class PasswordNotReused implements ValidationRule
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $service = app(PasswordHistoryService::class);

        if ($service->isReused($this->user, $value)) {
            $limit = (int) config('auth.auth-policy.password.history_count', 5);
            $fail("No puedes reutilizar tus últimas {$limit} contraseñas.");
        }
    }
}
