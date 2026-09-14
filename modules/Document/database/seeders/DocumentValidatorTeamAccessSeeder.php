<?php

namespace Modules\Document\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Document\Entities\DocumentValidatorGroup;

/**
 * Acceso real (no de prueba) a los grupos validadores de Documentos.
 * Sigue el mismo patrón que HelpdeskAccessSeeder (modules/Helpdesk):
 * una lista declarativa de gente real por email + los grupos a los que
 * pertenece, para poder sumar/quitar gente editando un array en vez de
 * a mano por tinker/UI.
 */
class DocumentValidatorTeamAccessSeeder extends Seeder
{
    /**
     * email => [group_key, ...] — mismos grupos que tiene hoy Helena
     * (helena@a-alvarez.com), como 'primary' en ambos.
     */
    private const MEMBERS = [
        'helena@a-alvarez.com' => ['documentation_team', 'licenses_team'],
        'victor@a-alvarez.com' => ['documentation_team', 'licenses_team'],
        'miguel@a-alvarez.com' => ['documentation_team', 'licenses_team'],
        'contenidosweb@a-alvarez.com' => ['documentation_team', 'licenses_team'],
        'gorka@a-alvarez.com' => ['documentation_team', 'licenses_team'],
        'clientes@a-alvarez.com' => ['documentation_team', 'licenses_team'],
    ];

    private const PRIORITY = 'primary';

    public function run(): void
    {
        $groups = DocumentValidatorGroup::whereIn(
            'key',
            collect(self::MEMBERS)->flatten()->unique()->values()
        )->get()->keyBy('key');

        foreach (self::MEMBERS as $email => $groupKeys) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->command?->warn("⚠️  Usuario no encontrado: {$email}");

                continue;
            }

            foreach ($groupKeys as $groupKey) {
                $group = $groups->get($groupKey);

                if (! $group) {
                    $this->command?->warn("⚠️  Grupo validador no encontrado: {$groupKey}");

                    continue;
                }

                $this->attachUserToGroup($group, $user);
            }
        }
    }

    private function attachUserToGroup(DocumentValidatorGroup $group, User $user): void
    {
        $exists = DB::table('document_validator_group_user')
            ->where('validator_group_id', $group->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $exists) {
            DB::table('document_validator_group_user')->insert([
                'validator_group_id' => $group->id,
                'user_id' => $user->id,
                'priority' => self::PRIORITY,
                'created_at' => now(),
            ]);

            $this->command?->line("  ✓ {$user->email} añadido a '{$group->name}' como ".self::PRIORITY);
        }
    }
}
