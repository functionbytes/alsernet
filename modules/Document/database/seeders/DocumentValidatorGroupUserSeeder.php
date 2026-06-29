<?php

namespace Modules\Document\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Entities\DocumentValidatorGroup;

class DocumentValidatorGroupUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds initial user assignments to validator groups with appropriate priorities.
     */
    public function run(): void
    {
        // Get groups
        $documentationTeam = DocumentValidatorGroup::firstWhere('key', 'documentation_team');
        $licensesTeam = DocumentValidatorGroup::firstWhere('key', 'licenses_team');
        $accountingTeam = DocumentValidatorGroup::firstWhere('key', 'accounting_team');
        $legalTeam = DocumentValidatorGroup::firstWhere('key', 'legal_team');

        if (! $documentationTeam || ! $licensesTeam || ! $accountingTeam) {
            $this->command->warn('⚠️  Some validator groups not found. Skipping user assignments.');

            return;
        }

        // Get or create test users for assignment
        $testUsers = [
            'administrative@alsernet.test' => 'Administrative',
            'license@alsernet.test' => 'License',
            'accounting@alsernet.test' => 'Accounting',
        ];

        $usersByEmail = [];
        foreach ($testUsers as $email => $firstname) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'firstname' => $firstname,
                    'lastname' => 'User',
                    'password' => bcrypt('secret'),
                    'available' => true,
                ]
            );
            $usersByEmail[$email] = $user;
        }

        // Assign users to documentation team
        if (isset($usersByEmail['administrative@alsernet.test'])) {
            $this->attachUserToGroup(
                $documentationTeam,
                $usersByEmail['administrative@alsernet.test'],
                'primary'
            );
        }

        // Assign users to licenses team
        if (isset($usersByEmail['license@alsernet.test'])) {
            $this->attachUserToGroup(
                $licensesTeam,
                $usersByEmail['license@alsernet.test'],
                'primary'
            );
        }

        // Assign users to accounting team
        if (isset($usersByEmail['accounting@alsernet.test'])) {
            $this->attachUserToGroup(
                $accountingTeam,
                $usersByEmail['accounting@alsernet.test'],
                'primary'
            );
        }

        // Optionally assign backup users
        if (isset($usersByEmail['administrative@alsernet.test'])) {
            $this->attachUserToGroup(
                $licensesTeam,
                $usersByEmail['administrative@alsernet.test'],
                'backup'
            );
            $this->attachUserToGroup(
                $accountingTeam,
                $usersByEmail['administrative@alsernet.test'],
                'backup'
            );
        }

        // Assign to legal team if enabled
        if ($legalTeam?->is_active && isset($usersByEmail['administrative@alsernet.test'])) {
            $this->attachUserToGroup(
                $legalTeam,
                $usersByEmail['administrative@alsernet.test'],
                'primary'
            );
        }

        $this->command->info('✅ Document validator group user assignments seeded successfully');
    }

    /**
     * Attach a user to a validator group with the given priority.
     */
    private function attachUserToGroup(DocumentValidatorGroup $group, User $user, string $priority = 'primary'): void
    {
        // Check if the attachment already exists
        $exists = DB::table('document_validator_group_user')
            ->where('validator_group_id', $group->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $exists) {
            DB::table('document_validator_group_user')->insert([
                'validator_group_id' => $group->id,
                'user_id' => $user->id,
                'priority' => $priority,
                'created_at' => now(),
            ]);

            $this->command->line("  ✓ Assigned {$user->email} to '{$group->name}' as {$priority}");
        }
    }
}
