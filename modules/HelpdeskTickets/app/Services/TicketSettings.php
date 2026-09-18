<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\Helpdesk\Models\Setting;

/**
 * Runtime access to settings that belong to the Tickets module.
 *
 * Keeping this lookup in one place means scheduled commands use the same
 * values that the Settings screen writes, instead of silently falling back to
 * environment values or hard-coded command defaults.
 */
class TicketSettings
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get('tickets.'.ltrim($key, '.'), $default);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function integer(string $key, int $default, int $minimum = 1): int
    {
        return max($minimum, (int) $this->get($key, $default));
    }
}
