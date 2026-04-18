<?php

namespace Modules\Attention\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionAction;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionNote;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;

class AttentionDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating demo attention data...');

        // Obtener datos base
        $types = AttentionType::all();
        $categories = AttentionCategory::all();
        $departments = AttentionDepartment::all();
        $sedes = AttentionSede::all();
        $users = User::take(5)->get();

        if ($types->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('Please run base seeders first (Types, Categories, etc.)');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please create users first.');

            return;
        }

        // Crear 50 peticiones de ejemplo
        foreach (range(1, 50) as $i) {
            $status = collect([
                AttentionStatus::RECEIVED,
                AttentionStatus::IN_PROCESS,
                AttentionStatus::RESOLVED,
                AttentionStatus::CLOSED,
            ])->random();

            $isResolved = in_array($status, [AttentionStatus::RESOLVED, AttentionStatus::CLOSED]);
            $isClosed = $status === AttentionStatus::CLOSED;

            $attention = Attention::create([
                'type_id' => $types->random()->id,
                'category_id' => $categories->random()->id,
                'sede_id' => $sedes->isNotEmpty() ? $sedes->random()->id : null,
                'customer_firstname' => fake()->firstName(),
                'customer_lastname' => fake()->lastName(),
                'customer_email' => fake()->safeEmail(),
                'customer_cellphone' => fake()->numerify('3#########'),
                'customer_dni' => fake()->numerify('##########'),
                'customer_address' => fake()->address(),
                'is_anonymous' => fake()->boolean(10), // 10% anónimos
                'subject' => fake()->sentence(),
                'description' => fake()->paragraph(3),
                'status' => $status,
                'department_id' => $departments->isNotEmpty() ? $departments->random()->id : null,
                'assigned_user_id' => $users->random()->id,
                'response_type' => $isResolved ? ResponseType::cases()[array_rand(ResponseType::cases())] : null,
                'resolution' => $isResolved ? fake()->paragraph(2) : null,
                'satisfaction_rating' => $isClosed ? fake()->numberBetween(1, 5) : null,
                'resolved_at' => $isResolved ? now()->subDays(rand(1, 10)) : null,
                'closed_at' => $isClosed ? now()->subDays(rand(0, 5)) : null,
            ]);

            // Agregar 2-5 notas
            $noteCount = rand(2, 5);
            for ($j = 0; $j < $noteCount; $j++) {
                AttentionNote::create([
                    'attention_id' => $attention->id,
                    'user_id' => $users->random()->id,
                    'content' => fake()->paragraph(2),
                ]);
            }

            // Agregar 3-8 acciones
            $actionCount = rand(3, 8);
            $actions = [
                'created',
                'status_changed',
                'assigned',
                'department_assigned',
                'note_added',
                'attachment_added',
                'email_sent',
            ];

            if ($isResolved) {
                $actions[] = 'resolved';
            }
            if ($isClosed) {
                $actions[] = 'closed';
            }

            for ($j = 0; $j < $actionCount; $j++) {
                AttentionAction::create([
                    'attention_id' => $attention->id,
                    'user_id' => $users->random()->id,
                    'action' => $actions[array_rand($actions)],
                    'description' => fake()->sentence(),
                ]);
            }

            if ($i % 10 === 0) {
                $this->command->info("Created {$i} demo attentions...");
            }
        }

        $this->command->info('Demo data created successfully!');
        $this->command->info('Total: 50 attentions with notes and action history');
    }
}
