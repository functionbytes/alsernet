<?php

namespace Modules\Helpdesk\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Group;
use Spatie\Permission\Models\Role;

class HelpdeskTeamMembersSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureRoles();

        $groupKeys = [
            'general_support' => Group::query()->where('key', 'general_support')->first(),
            'technical_support' => Group::query()->where('key', 'technical_support')->first(),
            'billing_support' => Group::query()->where('key', 'billing_support')->first(),
            'premium_support' => Group::query()->where('key', 'premium_support')->first(),
        ];

        $members = [
            [
                'firstname' => 'Maria',
                'lastname' => 'Lopez',
                'email' => 'maria.lopez@alsernet.local',
                'role' => 'admin',
                'groups' => ['general_support', 'technical_support', 'billing_support', 'premium_support'],
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 0,
                'auto_assign' => true,
            ],
            [
                'firstname' => 'Juan',
                'lastname' => 'Garcia',
                'email' => 'juan.garcia@alsernet.local',
                'role' => 'manager',
                'groups' => ['general_support', 'technical_support'],
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 10,
                'auto_assign' => true,
            ],
            [
                'firstname' => 'Ana',
                'lastname' => 'Martinez',
                'email' => 'ana.martinez@alsernet.local',
                'role' => 'support',
                'groups' => ['technical_support'],
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 5,
                'auto_assign' => true,
            ],
            [
                'firstname' => 'Pedro',
                'lastname' => 'Sanchez',
                'email' => 'pedro.sanchez@alsernet.local',
                'role' => 'support',
                'groups' => ['general_support', 'billing_support'],
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 5,
                'auto_assign' => true,
            ],
            [
                'firstname' => 'Lucia',
                'lastname' => 'Fernandez',
                'email' => 'lucia.fernandez@alsernet.local',
                'role' => 'callcenter',
                'groups' => ['general_support'],
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 8,
                'auto_assign' => true,
            ],
            [
                'firstname' => 'Carlos',
                'lastname' => 'Ruiz',
                'email' => 'carlos.ruiz@alsernet.local',
                'role' => 'support',
                'groups' => ['premium_support'],
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 3,
                'auto_assign' => true,
            ],
            [
                'firstname' => 'Sofia',
                'lastname' => 'Torres',
                'email' => 'sofia.torres@alsernet.local',
                'role' => 'support',
                'groups' => ['general_support'],
                'accepts_conversations' => 'working_hours',
                'max_concurrent_conversations' => 5,
                'auto_assign' => false,
            ],
            [
                'firstname' => 'Diego',
                'lastname' => 'Morales',
                'email' => 'diego.morales@alsernet.local',
                'role' => 'callcenter',
                'groups' => ['general_support', 'billing_support'],
                'accepts_conversations' => 'no',
                'max_concurrent_conversations' => 0,
                'auto_assign' => false,
            ],
        ];

        $created = 0;
        foreach ($members as $member) {
            $user = User::query()->updateOrCreate(
                ['email' => $member['email']],
                [
                    'firstname' => $member['firstname'],
                    'lastname' => $member['lastname'],
                    'password' => Hash::make('password'),
                    'mail_verified_at' => now(),
                    'verified' => 1,
                    'available' => 1,
                ]
            );

            // Assign role (Spatie)
            $user->syncRoles([$member['role']]);

            // Agent settings
            AgentSettings::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'accepts_conversations' => $member['accepts_conversations'],
                    'max_concurrent_conversations' => $member['max_concurrent_conversations'],
                    'auto_assign' => $member['auto_assign'],
                    'is_available' => $member['accepts_conversations'] === 'yes',
                ]
            );

            // Sync groups
            $groupIds = [];
            foreach ($member['groups'] as $key) {
                if (isset($groupKeys[$key]) && $groupKeys[$key]) {
                    $groupIds[] = $groupKeys[$key]->id;
                }
            }
            if (! empty($groupIds)) {
                DB::connection('helpdesk')->table('helpdesk_group_user')
                    ->where('user_id', $user->id)
                    ->delete();
                foreach ($groupIds as $groupId) {
                    DB::connection('helpdesk')->table('helpdesk_group_user')->insert([
                        'user_id' => $user->id,
                        'group_id' => $groupId,
                    ]);
                }
            }

            $created++;
        }

        $this->command->info("Team members creados/actualizados ({$created}). Password: 'password'");
    }

    private function ensureRoles(): void
    {
        foreach (['admin', 'manager', 'support', 'callcenter'] as $name) {
            Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
