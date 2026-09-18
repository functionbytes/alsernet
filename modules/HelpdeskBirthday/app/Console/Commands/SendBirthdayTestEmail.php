<?php

namespace Modules\HelpdeskBirthday\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskBirthday\Services\BirthdayTestSendService;

/**
 * Manda la felicitación a direcciones internas para revisarla en un cliente de
 * correo real. No toca la campaña ni sus destinatarios.
 */
class SendBirthdayTestEmail extends Command
{
    protected $signature = 'helpdeskbirthday:test-send
                            {emails* : Direcciones a las que mandar la prueba}';

    protected $description = 'Envía un correo de cumpleaños de prueba a las direcciones indicadas';

    public function handle(BirthdayTestSendService $tester): int
    {
        $result = $tester->send($this->argument('emails'));

        foreach ($result['sent'] as $email) {
            $this->info("Enviado a {$email}");
        }

        foreach ($result['failed'] as $email => $error) {
            $this->error("Falló {$email}: {$error}");
        }

        return $result['failed'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
