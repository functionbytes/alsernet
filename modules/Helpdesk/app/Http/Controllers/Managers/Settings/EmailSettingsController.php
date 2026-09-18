<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\UpdateEmailSettingsRequest;
use Modules\Helpdesk\Models\Setting;

class EmailSettingsController extends Controller
{
    private const GROUP = 'email';

    private const DEFAULTS = [
        'email_from_name' => '',
        'email_from_address' => '',
        'email_reply_to' => '',
        'email_signature' => '',
        'outbound_enabled' => false,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'inbound_enabled' => false,
        'imap_host' => '',
        'imap_port' => 993,
        'imap_username' => '',
        'imap_password' => '',
        'imap_encryption' => 'ssl',
        'imap_folder' => 'INBOX',
        'auto_create_tickets' => true,
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only(['update', 'testSmtp', 'testImap']);
    }

    public function index(): View
    {
        $settings = array_merge(self::DEFAULTS, Setting::allAsFlatArray(self::GROUP));

        $hasSmtpPassword = ! empty($settings['smtp_password']);
        $hasImapPassword = ! empty($settings['imap_password']);
        $settings['smtp_password'] = '';
        $settings['imap_password'] = '';

        return view('helpdesk::settings.email.index', [
            'backups' => $settings,
            'hasSmtpPassword' => $hasSmtpPassword,
            'hasImapPassword' => $hasImapPassword,
        ]);
    }

    public function update(UpdateEmailSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        foreach (['smtp_password', 'imap_password'] as $field) {
            if (empty($validated[$field])) {
                unset($validated[$field]);
            }
        }

        foreach (['outbound_enabled', 'inbound_enabled', 'auto_create_tickets'] as $field) {
            $validated[$field] = isset($validated[$field]) && $validated[$field];
        }

        foreach ($validated as $key => $value) {
            Setting::set(self::GROUP.'.'.$key, $value, self::GROUP);
        }

        return response()->json(['status' => true, 'message' => 'Configuración de email guardada correctamente.']);
    }

    public function testSmtp(Request $request): JsonResponse
    {
        $host = trim((string) $request->input('host', ''));
        $port = (int) $request->input('port', 587);
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $encryption = (string) $request->input('encryption', 'tls');

        if (empty($password)) {
            $password = (string) (Setting::get('email.smtp_password') ?? '');
        }

        if (empty($host) || empty($username)) {
            return response()->json(['status' => false, 'message' => 'El host y el usuario SMTP son obligatorios.']);
        }

        try {
            $this->checkSmtpConnection($host, $port, $username, $password, $encryption);

            return response()->json(['status' => true, 'message' => 'Conexión SMTP exitosa. Credenciales verificadas.']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function testImap(Request $request): JsonResponse
    {
        $host = trim((string) $request->input('host', ''));
        $port = (int) $request->input('port', 993);
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $encryption = (string) $request->input('encryption', 'ssl');
        $folder = trim((string) $request->input('folder', 'INBOX')) ?: 'INBOX';

        if (empty($password)) {
            $password = (string) (Setting::get('email.imap_password') ?? '');
        }

        if (empty($host) || empty($username)) {
            return response()->json(['status' => false, 'message' => 'El host y el usuario IMAP son obligatorios.']);
        }

        try {
            $this->checkImapConnection($host, $port, $username, $password, $encryption, $folder);

            return response()->json(['status' => true, 'message' => 'Conexión IMAP exitosa. Credenciales verificadas.']);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    private function checkSmtpConnection(string $host, int $port, string $username, string $password, string $encryption): void
    {
        $timeout = 10;
        $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';

        $socket = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (! $socket) {
            throw new \RuntimeException("No se pudo conectar a {$host}:{$port} — {$errstr}");
        }

        stream_set_timeout($socket, $timeout);

        $banner = fgets($socket, 512);
        if (! str_starts_with((string) $banner, '220')) {
            fclose($socket);
            throw new \RuntimeException('Respuesta inesperada del servidor SMTP: '.trim((string) $banner));
        }

        fwrite($socket, "EHLO helpdesk.test\r\n");
        while ($line = fgets($socket, 512)) {
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($encryption === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            $stls = (string) fgets($socket, 512);
            if (! str_starts_with($stls, '220')) {
                fclose($socket);
                throw new \RuntimeException('STARTTLS rechazado: '.trim($stls));
            }
            if (! stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \RuntimeException('No se pudo negociar TLS con el servidor SMTP.');
            }
            fwrite($socket, "EHLO helpdesk.test\r\n");
            while ($line = fgets($socket, 512)) {
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        $authResp = (string) fgets($socket, 512);
        if (! str_starts_with($authResp, '334')) {
            fclose($socket);
            throw new \RuntimeException('AUTH LOGIN no es compatible con este servidor.');
        }

        fwrite($socket, base64_encode($username)."\r\n");
        fgets($socket, 512);

        fwrite($socket, base64_encode($password)."\r\n");
        $passResp = (string) fgets($socket, 512);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        if (! str_starts_with($passResp, '235')) {
            throw new \RuntimeException('Credenciales SMTP incorrectas. '.trim($passResp));
        }
    }

    private function checkImapConnection(string $host, int $port, string $username, string $password, string $encryption, string $folder): void
    {
        if (! function_exists('imap_open')) {
            throw new \RuntimeException('La extensión PHP IMAP no está disponible en este servidor.');
        }

        $flags = match ($encryption) {
            'ssl' => '/imap/ssl/novalidate-cert',
            'tls' => '/imap/tls/novalidate-cert',
            default => '/imap/novalidate-cert',
        };

        $mailbox = "{{$host}:{$port}{$flags}}{$folder}";

        $connection = @imap_open($mailbox, $username, $password, 0, 0, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);

        if (! $connection) {
            $errors = imap_errors() ?: [];
            $errorMsg = $errors ? implode('; ', $errors) : 'No se pudo conectar al servidor IMAP';
            throw new \RuntimeException('Error IMAP: '.$errorMsg);
        }

        imap_close($connection);
    }
}
