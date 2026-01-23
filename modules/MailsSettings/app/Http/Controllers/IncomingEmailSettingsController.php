<?php

namespace Modules\MailsSettings\Http\Controllers;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;

class IncomingEmailSettingsController extends Controller
{
    /**
     * Display incoming email backups
     */
    public function index()
    {
        $settings = Setting::getIncomingEmailSettings();
        $pageTitle = 'Configuración de Correo Entrante';
        $breadcrumb = 'Configuración / Email / Entrante';

        return view('mails-backups::settings.incoming', compact('settings', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Update Pipe handler backups
     */
    public function updatePipe(Request $request)
    {
        try {
            $validated = $request->validate([
                'pipe_enabled' => 'nullable|boolean',
                'pipe_email_address' => 'nullable|email',
            ]);

            Setting::setIncomingEmailSettings($validated);

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Configuración Pipe actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating Pipe backups', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al actualizar Pipe: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update REST API handler backups
     */
    public function updateApi(Request $request)
    {
        try {
            $validated = $request->validate([
                'api_enabled' => 'nullable|boolean',
                'api_key' => 'nullable|string|min:32',
            ]);

            Setting::setIncomingEmailSettings($validated);

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Configuración REST API actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating API backups', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al actualizar REST API: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Generate new API key for REST API handler
     */
    public function generateApiKey()
    {
        try {
            $apiKey = bin2hex(random_bytes(32)); // Generate 64-char hex string

            Setting::setIncomingEmailSettings(['api_key' => $apiKey]);

            return response()->json([
                'success' => true,
                'api_key' => $apiKey,
                'message' => 'Nueva API Key generada correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating API key', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar API Key: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Mailgun handler backups
     */
    public function updateMailgun(Request $request)
    {
        try {
            $validated = $request->validate([
                'mailgun_enabled' => 'nullable|boolean',
                'mailgun_api_key' => 'nullable|string',
                'mailgun_domain' => 'nullable|string',
            ]);

            Setting::setIncomingEmailSettings($validated);

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Configuración Mailgun actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating Mailgun backups', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al actualizar Mailgun: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Store IMAP connection
     */
    public function storeImapConnection(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'host' => 'required|string',
                'port' => 'required|integer|min:1|max:65535',
                'username' => 'required|string',
                'password' => 'required|string',
                'folder' => 'nullable|string',
                'encryption' => 'nullable|string|in:tls,ssl',
                'create_tickets' => 'nullable|boolean',
                'create_replies' => 'nullable|boolean',
            ]);

            // Generate unique ID for connection
            $validated['id'] = uniqid('imap_', true);
            $validated['created_at'] = now()->toISOString();

            // Get existing connections
            $settings = Setting::getIncomingEmailSettings();
            $connections = $settings['imap']['connections'] ?? [];

            // Add new connection
            $connections[] = $validated;

            // Save
            Setting::set('incoming_email', json_encode([
                'imap' => [
                    'connections' => $connections,
                ],
                'pipe' => $settings['pipe'] ?? ['enabled' => false],
                'api' => $settings['api'] ?? ['enabled' => false],
            ]));

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Conexión IMAP agregada correctamente');
        } catch (\Exception $e) {
            Log::error('Error storing IMAP connection', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al guardar la conexión IMAP: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete IMAP connection
     */
    public function deleteImapConnection(string $id)
    {
        try {
            $settings = Setting::getIncomingEmailSettings();
            $connections = $settings['imap']['connections'] ?? [];

            // Filter out the connection with the given ID
            $connections = array_filter($connections, fn ($conn) => $conn['id'] !== $id);

            // Reindex array
            $connections = array_values($connections);

            // Save
            Setting::set('incoming_email', json_encode([
                'imap' => [
                    'connections' => $connections,
                ],
                'pipe' => $settings['pipe'] ?? ['enabled' => false],
                'api' => $settings['api'] ?? ['enabled' => false],
            ]));

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Conexión IMAP eliminada correctamente');
        } catch (\Exception $e) {
            Log::error('Error deleting IMAP connection', [
                'error' => $e->getMessage(),
                'connection_id' => $id,
            ]);

            return redirect()->back()
                ->with('error', 'Error al eliminar la conexión IMAP: '.$e->getMessage());
        }
    }

    /**
     * Test IMAP connection
     */
    public function testImapConnection(Request $request)
    {
        try {
            $validated = $request->validate([
                'host' => 'required|string',
                'port' => 'required|integer',
                'username' => 'required|string',
                'password' => 'required|string',
                'folder' => 'nullable|string',
            ]);

            // Test IMAP connection using fsockopen first
            $host = $validated['host'];
            $port = (int) $validated['port'];
            $timeout = 10;

            $startTime = microtime(true);
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if (! $connection) {
                return response()->json([
                    'success' => false,
                    'message' => "No se pudo conectar al servidor IMAP: {$errstr} (Código: {$errno})",
                ], 400);
            }

            fclose($connection);

            Log::info('IMAP connection test successful', [
                'host' => $host,
                'port' => $port,
                'response_time_ms' => $responseTime,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Servidor IMAP {$host}:{$port} responde correctamente ({$responseTime}ms)",
                'response_time_ms' => $responseTime,
            ]);
        } catch (\Exception $e) {
            Log::error('IMAP connection test exception', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al probar la conexión IMAP: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display REST API documentation
     */
    public function apiDocumentation()
    {
        $pageTitle = 'Documentación REST API - Incoming Email';
        $breadcrumb = 'Configuración / Email / Entrante / Documentación API';

        return view('mails-backups::settings.api-documentation', compact('pageTitle', 'breadcrumb'));
    }

    /**
     * Update Gmail API handler backups
     */
    public function updateGmail(Request $request)
    {
        try {
            $validated = $request->validate([
                'gmail_enabled' => 'nullable|boolean',
                'gmail_client_id' => 'nullable|string',
                'gmail_client_secret' => 'nullable|string',
                'gmail_redirect_uri' => 'nullable|string',
            ]);

            Setting::setIncomingEmailSettings($validated);

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Configuración Gmail actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating Gmail backups', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al actualizar Gmail: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Initiate Gmail OAuth2 authorization flow
     */
    public function gmailAuthorize(Request $request)
    {
        try {
            $settings = Setting::getIncomingEmailSettings();
            $gmailSettings = $settings['gmail'] ?? [];

            if (empty($gmailSettings['client_id']) || empty($gmailSettings['client_secret'])) {
                return redirect()->route('settings.incoming.index')
                    ->with('error', 'Configure primero las credenciales de Gmail (Client ID y Client Secret)');
            }

            $client = new GoogleClient;
            $client->setClientId($gmailSettings['client_id']);
            $client->setClientSecret($gmailSettings['client_secret']);
            $client->setRedirectUri($gmailSettings['redirect_uri'] ?? route('settings.incoming.gmail.callback'));
            $client->addScope(Gmail::GMAIL_READONLY);
            $client->addScope(Gmail::GMAIL_MODIFY);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            $authUrl = $client->createAuthUrl();

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Error initiating Gmail OAuth2', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('settings.incoming.index')
                ->with('error', 'Error al iniciar autorización de Gmail: '.$e->getMessage());
        }
    }

    /**
     * Handle Gmail OAuth2 callback
     */
    public function gmailCallback(Request $request)
    {
        try {
            $code = $request->input('code');

            if (! $code) {
                return redirect()->route('settings.incoming.index')
                    ->with('error', 'No se recibió el código de autorización de Google');
            }

            $settings = Setting::getIncomingEmailSettings();
            $gmailSettings = $settings['gmail'] ?? [];

            $client = new GoogleClient;
            $client->setClientId($gmailSettings['client_id']);
            $client->setClientSecret($gmailSettings['client_secret']);
            $client->setRedirectUri($gmailSettings['redirect_uri'] ?? route('settings.incoming.gmail.callback'));

            // Exchange authorization code for access token
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? $token['error']);
            }

            // Get user email
            $client->setAccessToken($token);
            $gmail = new Gmail($client);
            $profile = $gmail->users->getProfile('me');

            // Store connection
            $connection = [
                'id' => uniqid('gmail_', true),
                'email' => $profile->emailAddress,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => isset($token['expires_in']) ? now()->addSeconds($token['expires_in'])->toISOString() : null,
                'created_at' => now()->toISOString(),
            ];

            $connections = $gmailSettings['connections'] ?? [];
            $connections[] = $connection;

            // Save
            Setting::set('incoming_email', json_encode([
                'gmail' => array_merge($gmailSettings, ['connections' => $connections]),
                'imap' => $settings['imap'] ?? ['connections' => []],
                'pipe' => $settings['pipe'] ?? ['enabled' => false],
                'api' => $settings['api'] ?? ['enabled' => false],
                'mailgun' => $settings['mailgun'] ?? ['enabled' => false],
            ]));

            return redirect()->route('settings.incoming.index')
                ->with('success', "Cuenta de Gmail {$profile->emailAddress} conectada correctamente");
        } catch (\Exception $e) {
            Log::error('Error handling Gmail OAuth2 callback', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('settings.incoming.index')
                ->with('error', 'Error al conectar cuenta de Gmail: '.$e->getMessage());
        }
    }

    /**
     * Delete Gmail connection
     */
    public function deleteGmailConnection(string $id)
    {
        try {
            $settings = Setting::getIncomingEmailSettings();
            $connections = $settings['gmail']['connections'] ?? [];

            // Filter out the connection with the given ID
            $connections = array_filter($connections, fn ($conn) => $conn['id'] !== $id);

            // Reindex array
            $connections = array_values($connections);

            // Save
            $gmailSettings = $settings['gmail'] ?? [];
            Setting::set('incoming_email', json_encode([
                'gmail' => array_merge($gmailSettings, ['connections' => $connections]),
                'imap' => $settings['imap'] ?? ['connections' => []],
                'pipe' => $settings['pipe'] ?? ['enabled' => false],
                'api' => $settings['api'] ?? ['enabled' => false],
                'mailgun' => $settings['mailgun'] ?? ['enabled' => false],
            ]));

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Conexión Gmail eliminada correctamente');
        } catch (\Exception $e) {
            Log::error('Error deleting Gmail connection', [
                'error' => $e->getMessage(),
                'connection_id' => $id,
            ]);

            return redirect()->back()
                ->with('error', 'Error al eliminar la conexión Gmail: '.$e->getMessage());
        }
    }

    /**
     * Update phpList handler backups
     */
    public function updatePhplist(Request $request)
    {
        try {
            $validated = $request->validate([
                'phplist_enabled' => 'nullable|boolean',
                'phplist_api_url' => 'nullable|string|url',
                'phplist_api_key' => 'nullable|string',
                'phplist_default_list' => 'nullable|integer',
            ]);

            Setting::setIncomingEmailSettings($validated);

            return redirect()->route('settings.incoming.index')
                ->with('success', 'Configuración phpList actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating phpList backups', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error al actualizar phpList: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Test phpList API connection
     */
    public function testPhplistConnection(Request $request)
    {
        try {
            $validated = $request->validate([
                'api_url' => 'required|string|url',
                'api_key' => 'required|string',
            ]);

            $client = new GuzzleClient;

            // Test connection by getting lists
            $response = $client->get($validated['api_url'].'/lists', [
                'headers' => [
                    'Authorization' => 'Bearer '.$validated['api_key'],
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $data = json_decode($response->getBody(), true);
                $listCount = is_array($data) ? count($data) : 0;

                return response()->json([
                    'success' => true,
                    'message' => "Conexión exitosa con phpList. Se encontraron {$listCount} listas disponibles.",
                    'list_count' => $listCount,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con phpList. Código de estado: '.$statusCode,
            ], 400);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación. Verifique su API Key: '.$e->getMessage(),
            ], 401);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con phpList: '.$e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error testing phpList connection', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al probar la conexión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get phpList mailing lists
     */
    public function getPhplistLists(Request $request)
    {
        try {
            $settings = Setting::getIncomingEmailSettings();
            $phplistSettings = $settings['phplist'] ?? [];

            if (empty($phplistSettings['api_url']) || empty($phplistSettings['api_key'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configure primero la URL y API Key de phpList',
                ], 400);
            }

            $client = new GuzzleClient;

            $response = $client->get($phplistSettings['api_url'].'/lists', [
                'headers' => [
                    'Authorization' => 'Bearer '.$phplistSettings['api_key'],
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $lists = json_decode($response->getBody(), true);

            return response()->json([
                'success' => true,
                'lists' => $lists,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching phpList lists', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las listas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subscribe email to phpList
     */
    public function phplistSubscribe(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'list_id' => 'required|integer',
                'attributes' => 'nullable|array',
            ]);

            $settings = Setting::getIncomingEmailSettings();
            $phplistSettings = $settings['phplist'] ?? [];

            if (empty($phplistSettings['api_url']) || empty($phplistSettings['api_key'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configure primero la URL y API Key de phpList',
                ], 400);
            }

            $client = new GuzzleClient;

            $response = $client->post($phplistSettings['api_url'].'/subscribers', [
                'headers' => [
                    'Authorization' => 'Bearer '.$phplistSettings['api_key'],
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $validated['email'],
                    'lists' => [$validated['list_id']],
                    'attributes' => $validated['attributes'] ?? [],
                ],
                'timeout' => 10,
            ]);

            $result = json_decode($response->getBody(), true);

            return response()->json([
                'success' => true,
                'message' => "Email {$validated['email']} suscrito correctamente a la lista",
                'data' => $result,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody(), true);

            return response()->json([
                'success' => false,
                'message' => $body['message'] ?? 'Error al suscribir el email',
            ], $response->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Error subscribing to phpList', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al suscribir: '.$e->getMessage(),
            ], 500);
        }
    }
}
