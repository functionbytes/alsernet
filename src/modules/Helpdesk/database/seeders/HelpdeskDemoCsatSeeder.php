<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;

class HelpdeskDemoCsatSeeder extends Seeder
{
    public function run(): void
    {
        $conversations = Conversation::query()->limit(20)->get();

        if ($conversations->isEmpty()) {
            $this->command->warn('No hay conversaciones — saltando CSAT seed');

            return;
        }

        $comments = [
            5 => ['Excelente atención, muy rápida.', 'Resolvió mi problema en minutos.', 'Profesionalismo total.', 'Muy satisfecho.', null, null],
            4 => ['Bien atendido, podría mejorar el tiempo de respuesta.', 'Buen servicio.', null, 'Todo correcto.'],
            3 => ['Resolvió pero tardó.', 'Esperaba más rapidez.', null],
            2 => ['No me convenció la solución.', 'Tuvieron que escalar.'],
            1 => ['Pésima atención.', 'Nadie me ayudó.'],
        ];

        $created = 0;
        foreach ($conversations as $i => $c) {
            $rating = match (true) {
                $i < 12 => 5,
                $i < 16 => 4,
                $i < 18 => 3,
                $i < 19 => 2,
                default => 1,
            };

            $commentPool = $comments[$rating];
            $comment = $commentPool[array_rand($commentPool)];

            DB::connection('helpdesk')->table('helpdesk_csat_ratings')->updateOrInsert(
                ['conversation_id' => $c->id],
                [
                    'rating' => $rating,
                    'comment' => $comment,
                    'customer_id' => $c->customer_id,
                    'agent_id' => $c->assignee_id,
                    'survey_token' => Str::random(40),
                    'expires_at' => now()->addDays(30),
                    'answered_at' => now()->subDays(rand(0, 30)),
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now(),
                ]
            );
            $created++;
        }

        $this->command->info("CSAT ratings demo creadas ({$created})");
    }
}
