<?php

namespace Modules\Erp\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Genera un token Sanctum para que el panel Alsernet (proyecto Helpdesk)
 * pueda consultar la API ERP en nombre del usuario sistema.
 *
 *   php artisan erp:issue-bridge-token --user=system@bridge --label=alsernet-helpdesk
 *
 * El token se imprime UNA sola vez. Cópialo y pégalo en la integración
 * "ERP" del panel Alsernet (Settings → Integraciones → Nueva → ERP).
 */
class IssueBridgeTokenCommand extends Command
{
    protected $signature = 'erp:issue-bridge-token
                            {--user=system@bridge : Email del usuario sistema (se crea si no existe)}
                            {--label=alsernet-helpdesk : Etiqueta del token (visible en personal_access_tokens)}
                            {--abilities=* : Habilidades opcionales para limitar el token}';

    protected $description = 'Emite un token Sanctum para el bridge Alsernet → ERP API';

    public function handle(): int
    {
        $email = (string) $this->option('user');
        $label = (string) $this->option('label');
        $abilities = (array) $this->option('abilities') ?: ['*'];

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Bridge System User',
                'password' => bcrypt(Str::random(40)),
            ],
        );

        if ($user->wasRecentlyCreated) {
            $this->info("Usuario sistema creado: {$email}");
        }

        $token = $user->createToken($label, $abilities);
        $plainText = $token->plainTextToken;

        $this->newLine();
        $this->line('<fg=green;options=bold>Token generado correctamente.</> Copia este valor y pégalo en el panel Alsernet:');
        $this->newLine();
        $this->line("  <fg=yellow>{$plainText}</>");
        $this->newLine();
        $this->line('<fg=red>⚠ Este token NO se vuelve a mostrar.</> Si lo pierdes, revoca el actual y emite otro.');
        $this->newLine();
        $this->line('Para revocar: <fg=cyan>php artisan tinker</> y luego:');
        $this->line("  Laravel\\Sanctum\\PersonalAccessToken::find({$token->accessToken->id})->delete();");

        return self::SUCCESS;
    }
}
