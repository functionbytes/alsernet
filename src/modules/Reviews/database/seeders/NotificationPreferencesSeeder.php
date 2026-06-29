<?php

namespace Modules\Reviews\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Reviews\Services\NotificationService;

class NotificationPreferencesSeeder extends Seeder
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function run(): void
    {
        $users = User::query()
            ->whereDoesntHave('notificationPreferences')
            ->get();

        foreach ($users as $user) {
            $this->notificationService->initializeDefaultPreferences($user);
        }

        $this->command->info("Initialized notification preferences for {$users->count()} users");
    }
}
