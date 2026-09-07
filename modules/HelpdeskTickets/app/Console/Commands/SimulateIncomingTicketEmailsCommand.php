<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;

/**
 * Herramienta de desarrollo: simula correos entrantes contra el mismo pipeline
 * real que usa FetchTicketEmailsJob::processIncomingEmail() (threading,
 * lista negra, creación de Customer/Ticket/TicketMail), SIN necesitar un
 * servidor IMAP real.
 *
 * Por qué no usa IMAP de verdad: el buzón de desarrollo (Mailpit) solo habla
 * SMTP + POP3 + su propia API HTTP — no expone IMAP — y processConnection()
 * fuerza el protocolo '/imap/' en la cadena de conexión, así que Mailpit
 * nunca podría ser leído por este job aunque se apuntara la config ahí. En
 * vez de montar un servidor IMAP de prueba solo para esto, se construye
 * directamente el objeto $message con las mismas propiedades que expone
 * PhpImap\IncomingMail (from/subject/plainTextBody/...) y se invoca
 * processIncomingEmail() vía Closure::bind (el método es protected) — mismo
 * código de producción, sin el transporte IMAP de por medio.
 *
 * Toda la data de ejemplo generada está etiquetada con el dominio *-demo.test
 * y el asunto "[DEMO-BLACKLIST]" para poder localizarla y borrarla con
 * --cleanup sin arriesgar datos reales de la BD compartida.
 */
class SimulateIncomingTicketEmailsCommand extends Command
{
    protected $signature = 'helpdesk:simulate-incoming-emails
        {--cleanup : Elimina los tickets/clientes/reglas de ejemplo generados por este comando}
        {--mailpit : Además, envía una copia de cada correo por SMTP a Mailpit (config mail.mailers.smtp) para verlo en su Web UI}';

    protected $description = 'Simula correos entrantes (permitidos y en lista negra) para probar el filtro de HelpdeskTickets sin un servidor IMAP real.';

    private const SUBJECT_TAG = '[DEMO-BLACKLIST]';

    private const DEMO_DOMAIN_SUFFIX = '-demo.test';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Este comando no puede ejecutarse en producción.');

            return self::FAILURE;
        }

        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        $this->seedBlacklistExamples();

        $job = new FetchTicketEmailsJob;
        $connection = ['name' => 'demo', 'create_tickets' => true, 'create_replies' => true];

        $rows = [];
        foreach ($this->scenarios() as $scenario) {
            if ($this->option('mailpit')) {
                $this->sendToMailpit($scenario);
            }

            $ticketsBefore = Ticket::withTrashed()->count();

            $message = $this->buildMessage($scenario);
            // processIncomingEmail() es protected: mismo método que usa el job
            // en producción, invocado aquí vía closure-binding en vez de
            // reflexión manual (más corto, mismo efecto).
            \Closure::bind(function () use ($message, $connection) {
                $this->processIncomingEmail($message, $connection);
            }, $job, FetchTicketEmailsJob::class)();

            $ticketCreated = Ticket::withTrashed()->count() > $ticketsBefore;

            $rule = TicketEmailBlacklist::query()
                ->where('type', $scenario['blacklist_type'] ?? 'email')
                ->where('value', $scenario['blacklist_lookup'] ?? '')
                ->first();

            $rows[] = [
                $scenario['label'],
                $scenario['from'],
                $ticketCreated ? 'Ticket creado' : 'Bloqueado (lista negra)',
                $rule ? "coincidencias regla: {$rule->matched_count}" : '—',
            ];
        }

        $this->table(['Escenario', 'Remitente', 'Resultado', 'Detalle'], $rows);

        $this->newLine();
        $this->info('Datos de ejemplo bajo *'.self::DEMO_DOMAIN_SUFFIX.' y asunto "'.self::SUBJECT_TAG.'".');
        $this->info('Revisa Settings → Helpdesk — Tickets → Lista negra para ver las reglas y sus coincidencias.');
        $this->info('Ejecuta con --cleanup para borrar todo lo generado por este comando.');

        return self::SUCCESS;
    }

    /**
     * Reglas de ejemplo, idempotentes (no duplica si ya existen).
     */
    private function seedBlacklistExamples(): void
    {
        TicketEmailBlacklist::firstOrCreate(
            ['type' => 'email', 'value' => 'spam@malicioso'.self::DEMO_DOMAIN_SUFFIX],
            ['reason' => 'Ejemplo: bloqueo por email exacto', 'is_active' => true]
        );

        TicketEmailBlacklist::firstOrCreate(
            ['type' => 'domain', 'value' => 'spamdomain'.self::DEMO_DOMAIN_SUFFIX],
            ['reason' => 'Ejemplo: bloqueo por dominio (incluye subdominios)', 'is_active' => true]
        );

        TicketEmailBlacklist::firstOrCreate(
            ['type' => 'email', 'value' => 'viejo-spam@inactiva'.self::DEMO_DOMAIN_SUFFIX],
            ['reason' => 'Ejemplo: regla desactivada, no debe bloquear', 'is_active' => false]
        );
    }

    /**
     * @return array<int, array{label: string, from: string, subject: string, body: string, blacklist_type?: string, blacklist_lookup?: string}>
     */
    private function scenarios(): array
    {
        $suffix = self::DEMO_DOMAIN_SUFFIX;
        $tag = self::SUBJECT_TAG;

        return [
            [
                'label' => 'Cliente legítimo (no está en la lista negra)',
                'from' => "Cliente Real <cliente.real@ejemplo-cliente{$suffix}>",
                'subject' => "{$tag} Necesito ayuda con mi pedido",
                'body' => 'Hola, tengo un problema con mi pedido reciente.',
            ],
            [
                'label' => 'Email exacto en lista negra',
                'from' => "Spammer <spam@malicioso{$suffix}>",
                'subject' => "{$tag} Oferta increíble, no te la pierdas",
                'body' => 'Compra ahora y gana un premio.',
                'blacklist_type' => 'email',
                'blacklist_lookup' => "spam@malicioso{$suffix}",
            ],
            [
                'label' => 'Dominio en lista negra (coincidencia exacta)',
                'from' => "Info <info@spamdomain{$suffix}>",
                'subject' => "{$tag} Promoción exclusiva",
                'body' => 'Contenido no deseado desde el dominio bloqueado.',
                'blacklist_type' => 'domain',
                'blacklist_lookup' => "spamdomain{$suffix}",
            ],
            [
                'label' => 'Subdominio de un dominio en lista negra',
                'from' => "Newsletter <oferta@newsletter.spamdomain{$suffix}>",
                'subject' => "{$tag} Boletín semanal",
                'body' => 'Contenido no deseado desde un subdominio del dominio bloqueado.',
                'blacklist_type' => 'domain',
                'blacklist_lookup' => "spamdomain{$suffix}",
            ],
            [
                'label' => 'Regla inactiva (no debe bloquear)',
                'from' => "Cliente Antiguo <viejo-spam@inactiva{$suffix}>",
                'subject' => "{$tag} Regla desactivada, esto sí debe crear ticket",
                'body' => 'Esta regla está desactivada, el correo debe pasar con normalidad.',
                'blacklist_type' => 'email',
                'blacklist_lookup' => "viejo-spam@inactiva{$suffix}",
            ],
        ];
    }

    /**
     * Construye un objeto con las mismas propiedades que PhpImap\IncomingMail
     * expone y que processIncomingEmail()/findOrCreateTicket() consultan.
     */
    private function buildMessage(array $scenario): object
    {
        return (object) [
            'messageId' => '<demo-'.Str::uuid().'@'.parse_url(config('app.url'), PHP_URL_HOST).'>',
            'inReplyTo' => null,
            'references' => null,
            'from' => $scenario['from'],
            'to' => 'soporte@empresa.com',
            'cc' => null,
            'bcc' => null,
            'subject' => $scenario['subject'],
            'plainTextBody' => $scenario['body'],
            'htmlBody' => null,
            'raw' => null,
            'attachments' => [],
        ];
    }

    /**
     * Copia el correo a Mailpit por SMTP, solo para inspección visual — el
     * job NO lo lee de ahí (ver nota de clase sobre por qué no hay IMAP).
     * Usa env('MAIL_HOST')/env('MAIL_PORT') directamente en vez de
     * config('mail.mailers.smtp.*'): en este proyecto ese config queda
     * sobrescrito en boot con los defaults de paquete (localhost:25) cuando
     * no hay ajustes SMTP guardados en BD, aunque el .env real (MAIL_HOST=
     * mailpit dentro de Docker) sea correcto.
     */
    private function sendToMailpit(array $scenario): void
    {
        try {
            $host = env('MAIL_HOST') ?: '127.0.0.1';
            $port = env('MAIL_PORT') ?: 1025;

            $transport = new EsmtpTransport($host, (int) $port, false);
            $mailer = new Mailer($transport);

            preg_match('/<(.+?)>/', $scenario['from'], $matches);
            $fromEmail = $matches[1] ?? $scenario['from'];
            preg_match('/^(.+?)\s*</', $scenario['from'], $nameMatches);
            $fromName = isset($nameMatches[1]) ? trim($nameMatches[1], ' "\'') : $fromEmail;

            $email = (new MimeEmail)
                ->from(new Address($fromEmail, $fromName))
                ->to('soporte@empresa.com')
                ->subject($scenario['subject'])
                ->text($scenario['body']);

            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->warn("No se pudo copiar a Mailpit ({$scenario['label']}): {$e->getMessage()}");
        }
    }

    /**
     * Borra todo lo generado por este comando: tickets/clientes bajo
     * *-demo.test y las reglas de lista negra de ejemplo.
     */
    private function cleanup(): int
    {
        $suffix = self::DEMO_DOMAIN_SUFFIX;

        $tickets = Ticket::withTrashed()->where('subject', 'like', self::SUBJECT_TAG.'%')->get();
        foreach ($tickets as $ticket) {
            $ticket->mails()->delete();
            $ticket->items()->delete();
            $ticket->forceDelete();
        }
        $this->info(\count($tickets).' ticket(s) de ejemplo eliminado(s).');

        // Customer usa SoftDeletes: un delete() normal dejaría la fila (con
        // deleted_at) y su email seguiría chocando con el unique al volver a
        // simular, así que aquí se borra en firme.
        $customers = Customer::withTrashed()->where('email', 'like', '%'.$suffix)->get();
        foreach ($customers as $customer) {
            $customer->forceDelete();
        }
        $this->info(\count($customers).' cliente(s) de ejemplo eliminado(s).');

        $rules = TicketEmailBlacklist::where('value', 'like', '%'.$suffix)->get();
        foreach ($rules as $rule) {
            $rule->delete();
        }
        $this->info(\count($rules).' regla(s) de lista negra de ejemplo eliminada(s).');

        return self::SUCCESS;
    }
}
