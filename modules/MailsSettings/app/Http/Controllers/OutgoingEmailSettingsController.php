<?php

namespace Modules\MailsSettings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;

class OutgoingEmailSettingsController extends Controller
{
    /**
     * Display outgoing email backups
     */
    public function index()
    {
        $settings = Setting::getEmailSettings();
        $pageTitle = 'Configuración de Correo Saliente';
        $breadcrumb = 'Configuración / Email / Saliente';

        return view('mails-backups::settings.outgoing', compact('settings', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Show email edit form
     */
    public function edit()
    {
        $settings = Setting::getEmailSettings();
        $rules = Setting::getEmailRules();
        $pageTitle = 'Editar Correo Saliente';
        $breadcrumb = 'Configuración / Email / Saliente / Editar';

        return view('mails-backups::settings.outgoing-edit', compact('settings', 'rules', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Update outgoing email backups
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate(Setting::getEmailRules());

            Setting::setEmailSettings($validated);

            return redirect()->route('settings.outgoing.index')
                ->with('success', 'Configuración de correo saliente actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la configuración: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Test SMTP connection
     */
    public function testConnection()
    {
        try {
            $settings = Setting::getEmailSettings();

            // Test SMTP connection
            $host = $settings['mail_host'];
            $port = (int) $settings['mail_port'];
            $timeout = 10;

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
                'message' => 'Error en la conexión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request)
    {
        try {
            $validated = $request->validate([
                'test_email' => 'required|email',
            ]);

            $settings = Setting::getEmailSettings();

            // Configure mail backups temporarily
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
                    'local_domain' => env('MAIL_EHLO_DOMAIN'),
                ],
                'mail.from' => [
                    'address' => $settings['mail_from_address'],
                    'name' => $settings['mail_from_name'],
                ],
            ]);

            // Send test email with HTML content
            Mail::send([], [], function ($message) use ($validated, $settings) {
                $message->to($validated['test_email'])
                    ->subject('🧪 Correo de Prueba - Alsernet')
                    ->html($this->getTestEmailContent($validated['test_email'], $settings));
            });

            Log::info('Test email sent successfully', [
                'recipient' => $validated['test_email'],
                'smtp_host' => $settings['mail_host'],
                'smtp_port' => $settings['mail_port'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de prueba enviado exitosamente a '.$validated['test_email'],
            ]);
        } catch (\Swift_TransportException $e) {
            Log::error('SMTP Transport error when sending test email', [
                'error' => $e->getMessage(),
                'recipient' => $request->test_email ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de transporte SMTP: '.$e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error sending test email', [
                'error' => $e->getMessage(),
                'recipient' => $request->test_email ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar correo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate HTML content for test email
     */
    private function getTestEmailContent(string $recipient, array $settings): string
    {
        $date = now()->format('d/m/Y H:i:s');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #90bb13 0%, #7a9f11 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">✅ Prueba Exitosa</h1>
            </div>

            <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0;">
                <h2 style="color: #90bb13; margin-top: 0;">¡El sistema de correo funciona correctamente!</h2>

                <p>Este es un correo de prueba enviado desde <strong>Alsernet</strong> para verificar que la configuración SMTP está funcionando correctamente.</p>

                <div style="background: white; padding: 20px; border-left: 4px solid #90bb13; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #555;">📋 Detalles de la Prueba</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 5px 0;"><strong>Destinatario:</strong> {$recipient}</li>
                        <li style="padding: 5px 0;"><strong>Servidor SMTP:</strong> {$settings['mail_host']}</li>
                        <li style="padding: 5px 0;"><strong>Puerto:</strong> {$settings['mail_port']}</li>
                        <li style="padding: 5px 0;"><strong>Encriptación:</strong> {$settings['mail_encryption']}</li>
                        <li style="padding: 5px 0;"><strong>Fecha y Hora:</strong> {$date}</li>
                    </ul>
                </div>

                <p style="color: #666; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <strong>Nota:</strong> Si recibiste este correo, significa que tu configuración de email está funcionando perfectamente.
                    No necesitas responder a este mensaje.
                </p>

                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #90bb13;">
                    <p style="color: #999; font-size: 12px; margin: 0;">
                        Este correo fue generado automáticamente por el sistema de configuración de Alsernet
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
