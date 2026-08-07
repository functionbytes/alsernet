<?php

namespace Modules\MailsSettings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\MailsSettings\Support\MailsSettingsUrlGuard;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class OutgoingEmailSettingsController extends Controller
{
    /**
     * Display outgoing email backups
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->can('mails-settings.outgoing.view'), 403);

        $settings = Setting::getEmailSettings();
        $pageTitle = 'Configuración de Correo Saliente';
        $breadcrumb = 'Configuración / Email / Saliente';

        return view('mails-settings::settings.outgoing', compact('settings', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Show email edit form
     */
    public function edit(): View
    {
        abort_unless(auth()->user()?->can('mails-settings.outgoing.view'), 403);

        $settings = Setting::getEmailSettings();
        // El valor real nunca se manda a la vista — se renderiza en blanco con
        // placeholder "dejar en blanco para mantener la actual" (ver update()),
        // porque antes se volcaba tal cual en value="" del <input type="password">,
        // visible con "ver código fuente" para cualquiera que cargase la página.
        $settings['mail_password'] = '';
        $rules = Setting::getEmailRules();
        $pageTitle = 'Editar Correo Saliente';
        $breadcrumb = 'Configuración / Email / Saliente / Editar';

        return view('mails-settings::settings.outgoing-edit', compact('settings', 'rules', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Update outgoing email backups
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('mails-settings.outgoing.update'), 403);

        try {
            $validated = $request->validate(array_merge(Setting::getEmailRules(), [
                'mail_host' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (! MailsSettingsUrlGuard::isHostAllowed($value)) {
                        $fail('El servidor SMTP no está permitido (apunta a una IP interna/reservada no válida).');
                    }
                }],
            ]));

            // El formulario manda la contraseña en blanco cuando el usuario no
            // quiere cambiarla (ya no se precarga el valor real, ver edit()) —
            // una sumisión vacía no debe machacar la contraseña ya guardada.
            if (($validated['mail_password'] ?? '') === '') {
                unset($validated['mail_password']);
            }

            Setting::setEmailSettings($validated);

            return redirect()->route('settings.outgoing-email.index')
                ->with('success', 'Configuración de correo saliente actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Outgoing email settings update failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Error al actualizar la configuración. Por favor, inténtalo de nuevo.')
                ->withInput();
        }
    }

    /**
     * Test SMTP connection
     */
    public function testConnection(): JsonResponse
    {
        abort_unless(auth()->user()?->can('mails-settings.outgoing.test'), 403);

        try {
            $settings = Setting::getEmailSettings();

            // Test SMTP connection
            $host = $settings['mail_host'];
            $port = (int) $settings['mail_port'];
            $timeout = 10;

            // Defensa en profundidad: el host ya se valida al guardar (update()),
            // pero una fila guardada antes de ese fix podría seguir apuntando a
            // localhost/metadata cloud.
            if (! MailsSettingsUrlGuard::isHostAllowed($host)) {
                return response()->json([
                    'success' => false,
                    'status' => 'blocked',
                    'message' => 'El servidor SMTP configurado no está permitido.',
                ], 422);
            }

            $startTime = microtime(true);
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($connection) {
                fclose($connection);

                Log::info('SMTP connection test successful', [
                    'host' => $host,
                    'port' => $port,
                    'response_time_ms' => $responseTime,
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'connected',
                    'message' => "Servidor SMTP {$host}:{$port} responde correctamente ({$responseTime}ms)",
                    'response_time_ms' => $responseTime,
                    'details' => [
                        'host' => $host,
                        'port' => $port,
                        'encryption' => $settings['mail_encryption'] ?? 'none',
                    ],
                ]);
            }

            Log::warning('SMTP connection test failed', [
                'host' => $host,
                'port' => $port,
                'error_code' => $errno,
                'error_message' => $errstr,
            ]);

            return response()->json([
                'success' => false,
                'status' => 'disconnected',
                'message' => "No se pudo conectar al servidor SMTP: {$errstr} (Código: {$errno})",
            ], 400);
        } catch (\Exception $e) {
            Log::error('SMTP connection test exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error inesperado al probar la conexión SMTP.',
            ], 500);
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('mails-settings.outgoing.test'), 403);

        try {
            $validated = $request->validate([
                'test_email' => 'required|email',
            ]);

            $settings = Setting::getEmailSettings();

            // Snapshot existing config so we can restore it after sending
            $previousSmtp = config('mail.mailers.smtp');
            $previousFrom = config('mail.from');

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp' => [
                    'transport' => 'smtp',
                    'host' => $settings['mail_host'],
                    'port' => (int) $settings['mail_port'],
                    'encryption' => $settings['mail_encryption'] ?: null,
                    'username' => $settings['mail_username'],
                    'password' => $settings['mail_password'],
                    'timeout' => null,
                    'local_domain' => config('mail.mailers.smtp.local_domain'),
                ],
                'mail.from' => [
                    'address' => $settings['mail_from_address'],
                    'name' => $settings['mail_from_name'],
                ],
            ]);

            try {
                Mail::send([], [], function ($message) use ($validated, $settings) {
                    $message->to($validated['test_email'])
                        ->subject('Correo de Prueba - '.config('app.name'))
                        ->html($this->getTestEmailContent($validated['test_email'], $settings));
                });
            } finally {
                // Always restore — even if the send throws
                config([
                    'mail.mailers.smtp' => $previousSmtp,
                    'mail.from' => $previousFrom,
                ]);
            }

            Log::info('Test email sent successfully', [
                'recipient' => $validated['test_email'],
                'smtp_host' => $settings['mail_host'],
                'smtp_port' => $settings['mail_port'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de prueba enviado exitosamente a '.$validated['test_email'],
            ]);
        } catch (TransportExceptionInterface $e) {
            Log::error('SMTP Transport error when sending test email', [
                'error' => $e->getMessage(),
                'recipient' => $request->test_email ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de transporte SMTP. Verifica la configuración del servidor.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error sending test email', [
                'error' => $e->getMessage(),
                'recipient' => $request->test_email ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo de prueba. Verifica la configuración SMTP.',
            ], 500);
        }
    }

    /**
     * Generate HTML content for test email
     */
    private function getTestEmailContent(string $recipient, array $settings): string
    {
        $date = now()->format('d/m/Y H:i:s');
        $appName = config('app.name');
        $smtpHost = e($settings['mail_host']);
        $smtpPort = e($settings['mail_port']);
        $smtpEncryption = e($settings['mail_encryption']);

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #b10100 0%, #7a9f11 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">Prueba Exitosa</h1>
            </div>

            <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0;">
                <h2 style="color: #b10100; margin-top: 0;">El sistema de correo funciona correctamente</h2>

                <p>Este es un correo de prueba enviado desde <strong>{$appName}</strong> para verificar que la configuración SMTP está funcionando correctamente.</p>

                <div style="background: white; padding: 20px; border-left: 4px solid #b10100; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #555;">Detalles de la Prueba</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 5px 0;"><strong>Destinatario:</strong> {$recipient}</li>
                        <li style="padding: 5px 0;"><strong>Servidor SMTP:</strong> {$smtpHost}</li>
                        <li style="padding: 5px 0;"><strong>Puerto:</strong> {$smtpPort}</li>
                        <li style="padding: 5px 0;"><strong>Encriptación:</strong> {$smtpEncryption}</li>
                        <li style="padding: 5px 0;"><strong>Fecha y Hora:</strong> {$date}</li>
                    </ul>
                </div>

                <p style="color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <strong>Nota:</strong> Si recibiste este correo, significa que tu configuración de email está funcionando perfectamente.
                </p>

                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #b10100;">
                    <p style="color: #999; font-size: 12px; margin: 0;">
                        Este correo fue generado automáticamente por el sistema de configuración de {$appName}
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
