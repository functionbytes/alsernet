<?php

namespace Modules\Helpdesk\Services\Templates\Drops;

use App\Models\User;

class AgentDrop extends BaseDrop
{
    public function __construct(
        private readonly User $user
    ) {}

    public function id(): int
    {
        return $this->user->id;
    }

    public function firstname(): string
    {
        return $this->user->firstname ?? $this->user->name ?? '';
    }

    public function lastname(): string
    {
        return $this->user->lastname ?? '';
    }

    public function name(): string
    {
        $first = $this->firstname();
        $last = $this->lastname();

        return trim("{$first} {$last}") ?: $this->user->name ?? '';
    }

    public function email(): string
    {
        return $this->user->email ?? '';
    }
}
