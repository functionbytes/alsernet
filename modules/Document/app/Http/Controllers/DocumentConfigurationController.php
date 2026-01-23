<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentSlaBreach;
use Modules\Document\Entities\DocumentSlaPolicy;
use Modules\Document\Entities\DocumentStorageConfigurationHistory;
use Modules\Mailer\Models\MailerTemplate;

/**
 * DocumentConfigurationController
 * Handles all document configuration and settings:
 * - Global settings (email notifications, confirmations)
 * - Email configuration and templates
 * - SLA policies and configurations
 * - Storage configuration and management
 * - Upload settings and file management
 */
class DocumentConfigurationController extends Controller
{
    /**
     * The settings prefix for document-related settings.
     */
    private const SETTINGS_PREFIX = 'documents.';

    /**
     * Available setting groups/categories.
     *
     * @var array<string, array{label: string, description: string, icon: string}>
     */
    private const SETTING_GROUPS = [
        'general' => [
            'label' => 'General',
            'description' => 'Configuracion general del modulo de documentos',
            'icon' => 'fa-duotone fas fa-cog',
        ],
        'email' => [
            'label' => 'Notificaciones Email',
            'description' => 'Configuracion de envio de emails y recordatorios',
            'icon' => 'fa-duotone fas fa-envelope',
        ],
        'sla' => [
            'label' => 'Politicas SLA',
            'description' => 'Configuracion de politicas de nivel de servicio',
            'icon' => 'fa-duotone fas fa-clock',
        ],
        'upload' => [
            'label' => 'Carga de Archivos',
            'description' => 'Configuracion de tipos de archivo y limites',
            'icon' => 'fa-duotone fas fa-upload',
        ],
    ];

    public function index(): View
    {
        return view('documents::settings.index');
    }

    /**
     * Mostrar panel de configuración de almacenamiento
     */
    public function storageSettings()
    {
        $storageSettings = $this->getStorageSettings();

        return view('documents::settings.configurations.storage', [
            'storageSettings' => $storageSettings,
        ]);
    }

    /**
     * Actualizar configuración de almacenamiento
     */
    public function updateStorageSettings(Request $request)
    {
        $validated = $request->validate([
            'default_storage_disk' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            // Validar que el disco existe en la configuración
            $availableDisks = array_keys(config('filesystems.disks'));
            if (! in_array($validated['default_storage_disk'], $availableDisks)) {
                return back()
                    ->withInput()
                    ->with('error', 'El disco seleccionado no está configurado en el sistema');
            }

            // Obtener disco anterior
            $oldDisk = Setting::get('documents.default_storage_disk', 'media');
            $newDisk = $validated['default_storage_disk'];

            // Guardar el disco seleccionado para documentos
            Setting::set('documents.default_storage_disk', $newDisk);

            // Registrar cambio en el historial solo si realmente cambió
            if ($oldDisk !== $newDisk) {
                $diskConfig = config('filesystems.disks.'.$newDisk);
                DocumentStorageConfigurationHistory::create([
                    'old_disk_name' => $oldDisk,
                    'new_disk_name' => $newDisk,
                    'changed_by' => Auth::id(),
                    'driver' => $diskConfig['driver'] ?? 'unknown',
                    'reason' => $validated['reason'] ?? null,
                    'action' => 'update',
                    'metadata' => [
                        'old_driver' => config('filesystems.disks.'.$oldDisk)['driver'] ?? 'unknown',
                        'new_driver' => $diskConfig['driver'] ?? 'unknown',
                    ],
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Disco de almacenamiento actualizado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error updating storage configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la configuración: '.$e->getMessage());
        }
    }

    /**
     * Display email configuration settings.
     *
     * @return View The email settings view
     */
    public function emailSettings(): View
    {
        $settings = $this->getSettingsGroupedByCategory();
        $documentStats = $this->calculateDocumentStatistics();

        return view('documents::settings.settings.sections.email', [
            'settings' => $settings,
            'documentStats' => $documentStats,
        ]);
    }

    /**
     * Display SLA configuration settings.
     *
     * @return View The SLA settings view
     */
    public function slaSettings(): View
    {
        $settings = $this->getSettingsGroupedByCategory();
        $slaPolicies = DocumentSlaPolicy::select('id', 'name', 'active', 'is_default')
            ->orderBy('name')
            ->get();

        return view('documents::settings.settings.sections.sla', [
            'settings' => $settings,
            'slaPolicies' => $slaPolicies,
        ]);
    }

    /**
     * Update email settings.
     *
     * @param  Request  $request  The HTTP request containing email settings
     * @return JsonResponse JSON response with success/error status
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        return $this->update($request);
    }

    /**
     * Update SLA settings.
     *
     * @param  Request  $request  The HTTP request containing SLA settings
     * @return JsonResponse JSON response with success/error status
     */
    public function updateSlaSettings(Request $request): JsonResponse
    {
        return $this->update($request);
    }

    /**
     * Mostrar panel de configuración global
     */
    public function globalSettings()
    {
        $globalSettings = $this->getGlobalSettings();

        return view('documents::settings.configurations.index', [
            'globalSettings' => $globalSettings,
        ]);
    }

    /**
     * Actualizar configuración global
     */
    public function updateGlobalSettings(Request $request)
    {
        $validated = $request->validate([
            'enable_initial_request' => 'boolean',
            'enable_reminder' => 'boolean',
            'reminder_days' => 'required|integer|min:1|max:90',
            'enable_missing_docs' => 'boolean',
            'enable_custom_email' => 'boolean',
            'enable_upload_confirmation' => 'boolean',
            'enable_approval' => 'boolean',
            'enable_rejection' => 'boolean',
            'mail_template_initial_request_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_reminder_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_missing_docs_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_custom_email_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_upload_confirmation_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_approval_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_rejection_id' => 'nullable|integer|exists:mailer_templates,id',
        ]);

        try {
            Setting::set('documents.enable_initial_request', $request->boolean('enable_initial_request') ? 'yes' : 'no');
            Setting::set('documents.enable_reminder', $request->boolean('enable_reminder') ? 'yes' : 'no');
            Setting::set('documents.reminder_days', (string) $validated['reminder_days']);
            Setting::set('documents.enable_missing_docs', $request->boolean('enable_missing_docs') ? 'yes' : 'no');
            Setting::set('documents.enable_custom_email', $request->boolean('enable_custom_email') ? 'yes' : 'no');
            Setting::set('documents.enable_upload_confirmation', $request->boolean('enable_upload_confirmation') ? 'yes' : 'no');
            Setting::set('documents.enable_approval', $request->boolean('enable_approval') ? 'yes' : 'no');
            Setting::set('documents.enable_rejection', $request->boolean('enable_rejection') ? 'yes' : 'no');

            // Save template IDs
            Setting::set('documents.mail_template_initial_request_id', (string) ($validated['mail_template_initial_request_id'] ?? ''));
            Setting::set('documents.mail_template_reminder_id', (string) ($validated['mail_template_reminder_id'] ?? ''));
            Setting::set('documents.mail_template_missing_docs_id', (string) ($validated['mail_template_missing_docs_id'] ?? ''));
            Setting::set('documents.mail_template_custom_email_id', (string) ($validated['mail_template_custom_email_id'] ?? ''));
            Setting::set('documents.mail_template_upload_confirmation_id', (string) ($validated['mail_template_upload_confirmation_id'] ?? ''));
            Setting::set('documents.mail_template_approval_id', (string) ($validated['mail_template_approval_id'] ?? ''));
            Setting::set('documents.mail_template_rejection_id', (string) ($validated['mail_template_rejection_id'] ?? ''));

            return redirect()
                ->back()
                ->with('success', 'Configuración global actualizada correctamente');
        } catch (\Exception $e) {
            \Log::error('Error updating document configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la configuración: '.$e->getMessage());
        }
    }

    /**
     * Obtener configuración global de documentos
     */
    public function getGlobalSettings(): array
    {
        // Get all available templates for Select2
        $availableTemplates = MailerTemplate::module('documents')
            ->enabled()
            ->select(['id', 'name', 'key'])
            ->with(['translations' => function ($q) {
                $q->where('lang_id', 1)->select('id', 'mailer_template_id', 'lang_id');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                $translation = $template->translations->first();

                return [
                    'id' => (int) $template->id,
                    'text' => sprintf(
                        '%s [Lang: %s]',
                        $template->name,
                        'Default'
                    ),
                    'name' => $template->name,
                    'key' => $template->key,
                ];
            })
            ->toArray();

        return [
            'enable_initial_request' => Setting::get('documents.enable_initial_request', 'yes') === 'yes',
            'enable_reminder' => Setting::get('documents.enable_reminder', 'yes') === 'yes',
            'reminder_days' => (int) Setting::get('documents.reminder_days', '7'),
            'enable_missing_docs' => Setting::get('documents.enable_missing_docs', 'yes') === 'yes',
            'enable_custom_email' => Setting::get('documents.enable_custom_email', 'no') === 'yes',
            'enable_upload_confirmation' => Setting::get('documents.enable_upload_confirmation', 'yes') === 'yes',
            'enable_approval' => Setting::get('documents.enable_approval', 'yes') === 'yes',
            'enable_rejection' => Setting::get('documents.enable_rejection', 'yes') === 'yes',
            'mail_template_initial_request_id' => Setting::get('documents.mail_template_initial_request_id'),
            'mail_template_reminder_id' => Setting::get('documents.mail_template_reminder_id'),
            'mail_template_missing_docs_id' => Setting::get('documents.mail_template_missing_docs_id'),
            'mail_template_custom_email_id' => Setting::get('documents.mail_template_custom_email_id'),
            'mail_template_upload_confirmation_id' => Setting::get('documents.mail_template_upload_confirmation_id'),
            'mail_template_approval_id' => Setting::get('documents.mail_template_approval_id'),
            'mail_template_rejection_id' => Setting::get('documents.mail_template_rejection_id'),
            'available_templates' => $availableTemplates,
        ];
    }

    /**
     * Search email templates for Select2 AJAX
     */
    public function searchTemplates(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');

            // Search templates from documents module that are enabled
            $templates = MailerTemplate::module('documents')
                ->enabled()
                ->when($query, function ($q) use ($query) {
                    return $q->search($query);
                })
                ->select(['id', 'name', 'key'])
                ->with(['translations' => function ($q) {
                    $q->where('lang_id', 1)->select('id', 'mailer_template_id', 'lang_id');
                }])
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => (int) $template->id,
                        'text' => sprintf(
                            '%s [Lang: %s]',
                            $template->name,
                            'Default'
                        ),
                        'name' => $template->name,
                        'key' => $template->key,
                    ];
                })
                ->values()
                ->toArray();

            return response()->json([
                'results' => $templates,
                'pagination' => ['more' => false],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error searching email templates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'results' => [],
                'error' => 'Error al buscar templates: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener configuración de almacenamiento
     */
    private function getStorageSettings(): array
    {
        // Obtener discos disponibles desde la configuración
        $disksConfig = config('filesystems.disks');
        $availableDisks = [];

        foreach ($disksConfig as $diskName => $diskConfig) {
            $driver = $diskConfig['driver'] ?? 'unknown';
            $root = $diskConfig['root'] ?? 'N/A';
            $url = $diskConfig['url'] ?? null;

            // Crear descripción legible
            $description = match ($driver) {
                'local' => 'Almacenamiento local en: '.$root,
                'ftp' => 'Servidor FTP: '.($diskConfig['host'] ?? 'No configurado'),
                'sftp' => 'Servidor SFTP: '.($diskConfig['host'] ?? 'No configurado'),
                's3' => 'Amazon S3: '.($diskConfig['bucket'] ?? 'No configurado'),
                default => 'Driver: '.$driver,
            };

            $availableDisks[$diskName] = [
                'name' => $diskName,
                'driver' => $driver,
                'root' => $root,
                'url' => $url,
                'description' => $description,
            ];
        }

        // Obtener discos personalizados de la base de datos
        $customDisksJson = Setting::get('system.custom_storage_disks', '[]');
        $customDisks = json_decode($customDisksJson, true) ?: [];

        // Agregar discos personalizados a la lista
        foreach ($customDisks as $customDisk) {
            $diskName = $customDisk['name'];
            $driver = $customDisk['driver'];

            $description = match ($driver) {
                'local' => 'Almacenamiento local en: '.($customDisk['root'] ?? 'N/A'),
                'ftp' => 'Servidor FTP: '.($customDisk['host'] ?? 'No configurado'),
                'sftp' => 'Servidor SFTP: '.($customDisk['host'] ?? 'No configurado'),
                's3' => 'Amazon S3: '.($customDisk['bucket'] ?? 'No configurado'),
                default => 'Driver: '.$driver,
            };

            $availableDisks[$diskName] = [
                'name' => $diskName,
                'driver' => $driver,
                'root' => $customDisk['root'] ?? 'N/A',
                'url' => $customDisk['url'] ?? null,
                'description' => $description.' (Personalizado)',
            ];
        }

        // Obtener disco seleccionado actualmente
        $currentDisk = Setting::get('documents.default_storage_disk', 'media');

        return [
            'available_disks' => $availableDisks,
            'current_disk' => $currentDisk,
        ];
    }

    /**
     * Probar conexión a un disco de almacenamiento.
     */
    public function testStorageConnection(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $diskName = $request->input('disk_name');

            if (! $diskName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre de disco requerido',
                    'response_time' => 0,
                ], 400);
            }

            // Validar que el disco existe
            $availableDisks = array_keys(config('filesystems.disks'));
            if (! in_array($diskName, $availableDisks)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disco no configurado',
                    'response_time' => round((microtime(true) - $startTime) * 1000),
                ]);
            }

            $diskConfig = config('filesystems.disks.'.$diskName);
            $driver = $diskConfig['driver'] ?? 'unknown';

            // Prueba según tipo de driver
            $result = match ($driver) {
                'local' => $this->testLocalStorage($diskConfig, $diskName),
                's3' => $this->testS3Connection($diskConfig, $diskName),
                'ftp' => $this->testFtpConnection($diskConfig, $diskName),
                'sftp' => $this->testSftpConnection($diskConfig, $diskName),
                default => ['success' => false, 'message' => "Driver '$driver' no soporta prueba de conexión"],
            };

            $responseTime = round((microtime(true) - $startTime) * 1000);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'response_time' => $responseTime,
                'metadata' => $result['metadata'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error testing storage connection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al probar conexión: '.$e->getMessage(),
                'response_time' => round((microtime(true) - $startTime) * 1000),
            ], 500);
        }
    }

    /**
     * Probar almacenamiento local.
     */
    private function testLocalStorage(array $config, string $diskName): array
    {
        $root = $config['root'] ?? null;

        if (! $root) {
            return ['success' => false, 'message' => 'Ruta raíz no configurada'];
        }

        if (! is_dir($root)) {
            return ['success' => false, 'message' => "Directorio no existe: $root"];
        }

        if (! is_readable($root)) {
            return ['success' => false, 'message' => "Directorio no es legible: $root"];
        }

        if (! is_writable($root)) {
            return ['success' => false, 'message' => "Directorio no es escribible: $root"];
        }

        return [
            'success' => true,
            'message' => 'Almacenamiento local accesible',
            'metadata' => ['path' => $root],
        ];
    }

    /**
     * Probar conexión a Amazon S3.
     */
    private function testS3Connection(array $config, string $diskName): array
    {
        try {
            $disk = Storage::disk($diskName);
            $disk->exists('.');

            return [
                'success' => true,
                'message' => 'Conexión S3 exitosa',
                'metadata' => ['bucket' => $config['bucket'] ?? 'unknown'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error en conexión S3: '.$e->getMessage()];
        }
    }

    /**
     * Probar conexión FTP.
     */
    private function testFtpConnection(array $config, string $diskName): array
    {
        try {
            $disk = Storage::disk($diskName);
            // Try to list files from the disk to verify connection
            $disk->listContents('/');

            return [
                'success' => true,
                'message' => 'Conexión FTP exitosa',
                'metadata' => ['host' => $config['host'] ?? 'unknown'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error en conexión FTP: '.$e->getMessage()];
        }
    }

    /**
     * Probar conexión SFTP.
     */
    private function testSftpConnection(array $config, string $diskName): array
    {
        try {
            $disk = Storage::disk($diskName);
            // Try to list files from the disk to verify connection
            $disk->listContents('/');

            return [
                'success' => true,
                'message' => 'Conexión SFTP exitosa',
                'metadata' => ['host' => $config['host'] ?? 'unknown'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error en conexión SFTP: '.$e->getMessage()];
        }
    }

    /**
     * Obtener estadísticas de almacenamiento de un disco.
     */
    public function getStorageStats(Request $request, string $diskName): JsonResponse
    {
        try {
            $diskConfig = config('filesystems.disks.'.$diskName);

            if (! $diskConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disco no configurado',
                ], 404);
            }

            $driver = $diskConfig['driver'] ?? 'unknown';
            $stats = null;

            // Obtener estadísticas según tipo de driver
            if ($driver === 'local') {
                $root = $diskConfig['root'] ?? null;
                if ($root && is_dir($root)) {
                    $totalSpace = disk_total_space($root);
                    $freeSpace = disk_free_space($root);
                    $usedSpace = $totalSpace - $freeSpace;

                    $stats = [
                        'total' => $this->formatBytes($totalSpace),
                        'free' => $this->formatBytes($freeSpace),
                        'used' => $this->formatBytes($usedSpace),
                        'percent' => round(($usedSpace / $totalSpace) * 100, 2),
                    ];
                }
            }

            // Para otros drivers, no disponible
            if (! $stats) {
                $stats = [
                    'total' => 'N/A',
                    'free' => 'N/A',
                    'used' => 'N/A',
                    'percent' => null,
                ];
            }

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting storage stats', [
                'disk' => $diskName,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener historial de cambios de configuración de almacenamiento.
     */
    public function getStorageConfigurationHistory(): JsonResponse
    {
        try {
            $history = DocumentStorageConfigurationHistory::with('user')
                ->recent()
                ->limit(10)
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'old_disk' => $record->old_disk_name,
                        'new_disk' => $record->new_disk_name,
                        'driver' => $record->driver,
                        'user' => $record->user?->firstname.' '.$record->user?->lastname,
                        'user_id' => $record->changed_by,
                        'action_label' => $record->action_label,
                        'reason' => $record->reason,
                        'created_at' => $record->created_at,
                        'created_at_relative' => $record->created_at?->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting storage configuration history', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formatear bytes a formato legible.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Update an individual setting.
     *
     * Validates the input based on the setting type and updates the value.
     * Logs the change to the activity log for auditing purposes.
     *
     * @param  Request  $request  The HTTP request containing key and value
     * @return JsonResponse JSON response with success/error status
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'key' => ['required', 'string', 'max:255'],
                'value' => ['present'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validacion',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $key = $request->input('key');
            $value = $request->input('value');

            // Validate key format (must start with documents prefix)
            if (! str_starts_with($key, self::SETTINGS_PREFIX)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clave de configuracion invalida',
                ], 400);
            }

            // Get setting type for validation
            $settingMeta = $this->getSettingMetadata($key);
            $validationType = $settingMeta['type'] ?? 'string';

            // Validate value based on type
            $valueValidationResult = $this->validateSettingValue($value, $validationType, $settingMeta);
            if ($valueValidationResult !== true) {
                return response()->json([
                    'success' => false,
                    'message' => $valueValidationResult,
                ], 422);
            }

            // Process value based on type
            $processedValue = $this->processSettingValue($value, $validationType);

            // Get old value for logging
            $oldValue = Setting::get($key);

            // Update the setting
            Setting::set($key, $processedValue);

            // Log the change
            $this->logSettingChange($key, $oldValue, $processedValue);

            return response()->json([
                'success' => true,
                'message' => 'Configuracion actualizada correctamente',
                'data' => [
                    'key' => $key,
                    'value' => $processedValue,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating document setting', [
                'key' => $request->input('key'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuracion: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch update multiple settings.
     *
     * Performs transactional updates ensuring all settings are updated
     * or none are (all-or-nothing approach).
     *
     * @param  Request  $request  The HTTP request containing settings array
     * @return RedirectResponse Redirect with flash message
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['present'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Error de validacion en los datos enviados');
        }

        $settings = $request->input('settings', []);

        // Pre-validate all settings before starting transaction
        $validationErrors = [];
        foreach ($settings as $index => $setting) {
            $key = $setting['key'];
            $value = $setting['value'];

            // Validate key format
            if (! str_starts_with($key, self::SETTINGS_PREFIX)) {
                $validationErrors[] = "Clave invalida en posicion {$index}: {$key}";

                continue;
            }

            // Validate value based on type
            $settingMeta = $this->getSettingMetadata($key);
            $validationType = $settingMeta['type'] ?? 'string';
            $valueValidationResult = $this->validateSettingValue($value, $validationType, $settingMeta);

            if ($valueValidationResult !== true) {
                $validationErrors[] = "Error en '{$key}': {$valueValidationResult}";
            }
        }

        if (! empty($validationErrors)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Errores de validacion: '.implode(', ', $validationErrors));
        }

        try {
            DB::beginTransaction();

            $updatedSettings = [];

            foreach ($settings as $setting) {
                $key = $setting['key'];
                $value = $setting['value'];

                $settingMeta = $this->getSettingMetadata($key);
                $validationType = $settingMeta['type'] ?? 'string';

                // Process and save the value
                $processedValue = $this->processSettingValue($value, $validationType);
                $oldValue = Setting::get($key);

                Setting::set($key, $processedValue);

                // Track updated settings for logging
                $updatedSettings[] = [
                    'key' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $processedValue,
                ];
            }

            DB::commit();

            // Log all changes after successful commit
            foreach ($updatedSettings as $change) {
                $this->logSettingChange($change['key'], $change['old_value'], $change['new_value']);
            }

            return redirect()
                ->route('settings.documents.configurations.global')
                ->with('success', 'Configuraciones actualizadas correctamente ('.count($updatedSettings).' cambios)');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in batch document settings update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al guardar las configuraciones: '.$e->getMessage());
        }
    }

    /**
     * Get settings grouped by section.
     *
     * Return settings organized by section for display in tabs/accordion.
     * Includes section descriptions and metadata.
     *
     * @param  string|null  $section  Optional section filter
     * @return JsonResponse|array Settings grouped by section
     */
    public function getSectionSettings(?string $section = null): JsonResponse|array
    {
        $groupedSettings = $this->getSettingsGroupedByCategory();

        if ($section !== null) {
            if (! isset($groupedSettings[$section])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seccion no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'section' => $section,
                    'metadata' => self::SETTING_GROUPS[$section] ?? null,
                    'settings' => $groupedSettings[$section],
                ],
            ]);
        }

        // Return all sections with their metadata
        $result = [];
        foreach ($groupedSettings as $group => $settings) {
            $result[$group] = [
                'metadata' => self::SETTING_GROUPS[$group] ?? [
                    'label' => ucfirst($group),
                    'description' => '',
                    'icon' => 'fa-duotone fas fa-cog',
                ],
                'settings' => $settings,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Reset settings to default values.
     *
     * @param  Request  $request  The HTTP request with optional group filter
     * @return JsonResponse JSON response with success/error status
     */
    public function resetToDefaults(Request $request): JsonResponse
    {
        try {
            $group = $request->input('group');

            $defaultSettings = $this->getDefaultSettings();

            if ($group !== null) {
                $defaultSettings = array_filter($defaultSettings, function ($setting) use ($group) {
                    return ($setting['group'] ?? 'general') === $group;
                });
            }

            DB::beginTransaction();

            foreach ($defaultSettings as $key => $config) {
                $fullKey = self::SETTINGS_PREFIX.$key;
                $oldValue = Setting::get($fullKey);
                Setting::set($fullKey, $config['default']);
                $this->logSettingChange($fullKey, $oldValue, $config['default'], 'reset_to_default');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuraciones restauradas a valores Por defecto',
                'count' => count($defaultSettings),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error resetting document settings to defaults', [
                'group' => $request->input('group'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar configuraciones: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retrieve all document settings grouped by category.
     *
     * @return array<string, array> Settings grouped by category
     */
    private function getSettingsGroupedByCategory(): array
    {
        // Get all document settings from database
        $settings = Setting::where('key', 'like', self::SETTINGS_PREFIX.'%')
            ->get()
            ->keyBy('key');

        // Get default settings with metadata
        $defaultSettings = $this->getDefaultSettings();

        $grouped = [];

        foreach ($defaultSettings as $key => $config) {
            $fullKey = self::SETTINGS_PREFIX.$key;
            $group = $config['group'] ?? 'general';

            // Get current value from database or use default
            $currentValue = $settings->has($fullKey)
                ? $settings[$fullKey]->value
                : $config['default'];

            $grouped[$group][$key] = [
                'key' => $fullKey,
                'value' => $currentValue,
                'default' => $config['default'],
                'type' => $config['type'],
                'label' => $config['label'],
                'description' => $config['description'] ?? '',
                'options' => $config['options'] ?? null,
                'validation' => $config['validation'] ?? null,
            ];
        }

        return $grouped;
    }

    /**
     * Get default settings configuration.
     *
     * @return array<string, array> Default settings with metadata
     */
    private function getDefaultSettings(): array
    {
        return [
            // General Settings
            'enabled' => [
                'group' => 'general',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Modulo habilitado',
                'description' => 'Habilita o deshabilita el modulo de documentos',
            ],
            'auto_detect_type' => [
                'group' => 'general',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Deteccion automatica de tipo',
                'description' => 'Detecta automaticamente el tipo de documento basandose en los productos',
            ],
            'require_all_documents' => [
                'group' => 'general',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Requerir todos los documentos',
                'description' => 'Requiere que se carguen todos los documentos antes de confirmar',
            ],
            'default_status' => [
                'group' => 'general',
                'type' => 'string',
                'default' => 'pending',
                'label' => 'Estado Por defecto',
                'description' => 'Estado inicial de los documentos nuevos',
                'options' => ['pending', 'awaiting_upload', 'in_review', 'approved'],
            ],
            'portal_url' => [
                'group' => 'general',
                'type' => 'string',
                'default' => '',
                'label' => 'URL del portal de documentos',
                'description' => 'URL base para el portal de carga de documentos (usar {uid} como placeholder)',
                'validation' => ['nullable', 'url'],
            ],

            // Email Settings
            'mail_enabled' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Envio de emails habilitado',
                'description' => 'Habilita el envio de notificaciones por email',
            ],
            'mail_upload_notification' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Notificacion de solicitud de carga',
                'description' => 'Envia email cuando se solicita carga de documentos',
            ],
            'mail_upload_confirmation' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Confirmacion de carga',
                'description' => 'Envia email de confirmacion cuando se cargan documentos',
            ],
            'mail_reminder_enabled' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Recordatorios habilitados',
                'description' => 'Envia recordatorios automaticos para documentos pendientes',
            ],
            'mail_reminder_days' => [
                'group' => 'email',
                'type' => 'integer',
                'default' => '3',
                'label' => 'Dias para recordatorio',
                'description' => 'Numero de dias antes de enviar un recordatorio',
                'validation' => ['integer', 'min:1', 'max:30'],
            ],
            'mail_max_reminders' => [
                'group' => 'email',
                'type' => 'integer',
                'default' => '3',
                'label' => 'Maximo de recordatorios',
                'description' => 'Numero maximo de recordatorios a enviar',
                'validation' => ['integer', 'min:1', 'max:10'],
            ],
            'mail_from_name' => [
                'group' => 'email',
                'type' => 'string',
                'default' => config('app.name', 'Alsernet'),
                'label' => 'Nombre del remitente',
                'description' => 'Nombre que aparece como remitente en los emails',
            ],
            'mail_from_address' => [
                'group' => 'email',
                'type' => 'string',
                'default' => config('mail.from.address', ''),
                'label' => 'Email del remitente',
                'description' => 'Direccion de email del remitente',
                'validation' => ['nullable', 'email'],
            ],
            'mail_reply_to' => [
                'group' => 'email',
                'type' => 'string',
                'default' => '',
                'label' => 'Email de respuesta',
                'description' => 'Direccion de email para respuestas',
                'validation' => ['nullable', 'email'],
            ],
            'mail_cc' => [
                'group' => 'email',
                'type' => 'text',
                'default' => '',
                'label' => 'CC (copia)',
                'description' => 'Direcciones de email para copia (separadas por coma)',
            ],
            'mail_bcc' => [
                'group' => 'email',
                'type' => 'text',
                'default' => '',
                'label' => 'BCC (copia oculta)',
                'description' => 'Direcciones de email para copia oculta (separadas por coma)',
            ],

            // SLA Settings
            'sla_enabled' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Politicas SLA habilitadas',
                'description' => 'Habilita el seguimiento de politicas SLA',
            ],
            'sla_default_policy_id' => [
                'group' => 'sla',
                'type' => 'integer',
                'default' => '0',
                'label' => 'Politica SLA Por defecto',
                'description' => 'ID de la politica SLA a aplicar Por defecto',
            ],
            'sla_track_breaches' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Registrar incumplimientos',
                'description' => 'Registra automaticamente los incumplimientos de SLA',
            ],
            'sla_auto_escalate' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Escalamiento automatico',
                'description' => 'Escala automaticamente cuando se excede el umbral',
            ],
            'sla_escalation_email' => [
                'group' => 'sla',
                'type' => 'string',
                'default' => '',
                'label' => 'Email de escalamiento',
                'description' => 'Direccion de email para notificaciones de escalamiento',
                'validation' => ['nullable', 'email'],
            ],
            'sla_business_hours_only' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Solo horario laboral',
                'description' => 'Calcula SLA solo en horario laboral',
            ],
            'sla_business_hours_start' => [
                'group' => 'sla',
                'type' => 'string',
                'default' => '09:00',
                'label' => 'Inicio horario laboral',
                'description' => 'Hora de inicio del horario laboral (HH:MM)',
            ],
            'sla_business_hours_end' => [
                'group' => 'sla',
                'type' => 'string',
                'default' => '17:00',
                'label' => 'Fin horario laboral',
                'description' => 'Hora de fin del horario laboral (HH:MM)',
            ],
            'sla_exclude_weekends' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Excluir fines de semana',
                'description' => 'No cuenta fines de semana para el calculo de SLA',
            ],

            // Upload Settings
            'upload_max_file_size' => [
                'group' => 'upload',
                'type' => 'integer',
                'default' => '10240',
                'label' => 'Tamano maximo de archivo (KB)',
                'description' => 'Tamano maximo permitido por archivo en kilobytes',
                'validation' => ['integer', 'min:1024', 'max:102400'],
            ],
            'upload_max_files' => [
                'group' => 'upload',
                'type' => 'integer',
                'default' => '10',
                'label' => 'Maximo de archivos',
                'description' => 'Numero maximo de archivos por documento',
                'validation' => ['integer', 'min:1', 'max:50'],
            ],
            'upload_allowed_types' => [
                'group' => 'upload',
                'type' => 'json',
                'default' => '["jpg","jpeg","png","pdf","doc","docx"]',
                'label' => 'Tipos de archivo permitidos',
                'description' => 'Extensiones de archivo permitidas (formato JSON)',
            ],
            'upload_image_max_width' => [
                'group' => 'upload',
                'type' => 'integer',
                'default' => '2048',
                'label' => 'Ancho maximo de imagen',
                'description' => 'Ancho maximo de imagenes en pixeles (se redimensionara)',
                'validation' => ['integer', 'min:800', 'max:4096'],
            ],
            'upload_image_max_height' => [
                'group' => 'upload',
                'type' => 'integer',
                'default' => '2048',
                'label' => 'Alto maximo de imagen',
                'description' => 'Alto maximo de imagenes en pixeles (se redimensionara)',
                'validation' => ['integer', 'min:800', 'max:4096'],
            ],
            'upload_compress_images' => [
                'group' => 'upload',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Comprimir imagenes',
                'description' => 'Comprime automaticamente las imagenes cargadas',
            ],
            'upload_image_quality' => [
                'group' => 'upload',
                'type' => 'integer',
                'default' => '85',
                'label' => 'Calidad de imagen',
                'description' => 'Calidad de compresion de imagenes (1-100)',
                'validation' => ['integer', 'min:50', 'max:100'],
            ],
        ];
    }

    /**
     * Get metadata for a specific setting.
     *
     * @param  string  $key  The setting key
     * @return array Setting metadata
     */
    private function getSettingMetadata(string $key): array
    {
        $shortKey = str_replace(self::SETTINGS_PREFIX, '', $key);
        $defaults = $this->getDefaultSettings();

        return $defaults[$shortKey] ?? [
            'type' => 'string',
            'validation' => null,
        ];
    }

    /**
     * Validate a setting value based on its type.
     *
     * @param  mixed  $value  The value to validate
     * @param  string  $type  The setting type
     * @param  array  $metadata  Additional validation metadata
     * @return bool|string True if valid, error message if invalid
     */
    private function validateSettingValue(mixed $value, string $type, array $metadata): bool|string
    {
        $customValidation = $metadata['validation'] ?? null;

        switch ($type) {
            case 'boolean':
                if (! in_array($value, ['yes', 'no', true, false, 1, 0, '1', '0'], true)) {
                    return 'El valor debe ser yes/no o true/false';
                }
                break;

            case 'integer':
                if (! is_numeric($value) || (int) $value != $value) {
                    return 'El valor debe ser un numero entero';
                }
                if ($customValidation) {
                    foreach ($customValidation as $rule) {
                        if (str_starts_with($rule, 'min:')) {
                            $min = (int) str_replace('min:', '', $rule);
                            if ((int) $value < $min) {
                                return "El valor debe ser mayor o igual a {$min}";
                            }
                        }
                        if (str_starts_with($rule, 'max:')) {
                            $max = (int) str_replace('max:', '', $rule);
                            if ((int) $value > $max) {
                                return "El valor debe ser menor o igual a {$max}";
                            }
                        }
                    }
                }
                break;

            case 'json':
                if (! empty($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return 'El valor debe ser un JSON valido';
                    }
                }
                break;

            case 'string':
            case 'text':
                if ($customValidation && in_array('email', $customValidation)) {
                    if (! empty($value) && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return 'El valor debe ser una direccion de email valida';
                    }
                }
                if ($customValidation && in_array('url', $customValidation)) {
                    if (! empty($value) && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        return 'El valor debe ser una URL valida';
                    }
                }
                break;
        }

        // Check options if defined
        if (isset($metadata['options']) && ! empty($value)) {
            if (! in_array($value, $metadata['options'])) {
                return 'El valor debe ser uno de: '.implode(', ', $metadata['options']);
            }
        }

        return true;
    }

    /**
     * Process a setting value for storage.
     *
     * @param  mixed  $value  The value to process
     * @param  string  $type  The setting type
     * @return string The processed value
     */
    private function processSettingValue(mixed $value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                if (in_array($value, [true, 1, '1', 'yes'], true)) {
                    return 'yes';
                }

                return 'no';

            case 'integer':
                return (string) (int) $value;

            case 'json':
                if (is_array($value)) {
                    return json_encode($value);
                }

                return (string) $value;

            default:
                return (string) $value;
        }
    }

    /**
     * Log a setting change to the activity log.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $oldValue  The previous value
     * @param  mixed  $newValue  The new value
     * @param  string  $action  The action type (updated, reset_to_default)
     */
    private function logSettingChange(string $key, mixed $oldValue, mixed $newValue, string $action = 'updated'): void
    {
        if ($oldValue === $newValue) {
            return;
        }

        try {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'key' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'action' => $action,
                ])
                ->log("Document setting {$action}: {$key}");
        } catch (\Exception $e) {
            Log::warning('Failed to log setting change', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate SLA compliance statistics.
     *
     * @return array SLA statistics
     */
    private function calculateSlaStatistics(): array
    {
        try {
            $totalDocuments = Document::whereNotNull('sla_policy_id')->count();
            $totalBreaches = DocumentSlaBreach::count();
            $unresolvedBreaches = DocumentSlaBreach::where('resolved', false)->count();
            $escalatedBreaches = DocumentSlaBreach::where('escalated', true)->count();

            // Calculate compliance rate
            $complianceRate = $totalDocuments > 0
                ? round((($totalDocuments - $totalBreaches) / $totalDocuments) * 100, 2)
                : 100;

            // Get breach statistics by type
            $breachByType = DocumentSlaBreach::select('breach_type', DB::raw('COUNT(*) as count'))
                ->groupBy('breach_type')
                ->pluck('count', 'breach_type')
                ->toArray();

            // Get recent breaches (last 7 days)
            $recentBreaches = DocumentSlaBreach::where('created_at', '>=', now()->subDays(7))->count();

            // Average resolution time (in minutes)
            $avgResolutionTime = DocumentSlaBreach::whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
                ->value('avg_time') ?? 0;

            return [
                'total_documents_with_sla' => $totalDocuments,
                'total_breaches' => $totalBreaches,
                'unresolved_breaches' => $unresolvedBreaches,
                'escalated_breaches' => $escalatedBreaches,
                'compliance_rate' => $complianceRate,
                'breach_by_type' => $breachByType,
                'recent_breaches' => $recentBreaches,
                'avg_resolution_time' => round($avgResolutionTime, 0),
                'active_policies' => DocumentSlaPolicy::where('active', true)->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating SLA statistics', ['error' => $e->getMessage()]);

            return [
                'total_documents_with_sla' => 0,
                'total_breaches' => 0,
                'unresolved_breaches' => 0,
                'escalated_breaches' => 0,
                'compliance_rate' => 100,
                'breach_by_type' => [],
                'recent_breaches' => 0,
                'avg_resolution_time' => 0,
                'active_policies' => 0,
            ];
        }
    }

    /**
     * Calculate document processing statistics.
     *
     * @return array Document statistics
     */
    private function calculateDocumentStatistics(): array
    {
        try {
            $totalDocuments = Document::count();
            $documentsWithMedia = Document::has('media')->count();
            $documentsConfirmed = Document::whereNotNull('confirmed_at')->count();

            // Document by type
            $documentsByType = Document::select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            // Document by source
            $documentsBySource = Document::select('source', DB::raw('COUNT(*) as count'))
                ->whereNotNull('source')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();

            // Recent documents (last 30 days)
            $recentDocuments = Document::where('created_at', '>=', now()->subDays(30))->count();

            // Document pending upload
            $pendingUpload = Document::whereNull('confirmed_at')
                ->doesntHave('media')
                ->count();

            return [
                'total_documents' => $totalDocuments,
                'documents_with_media' => $documentsWithMedia,
                'documents_confirmed' => $documentsConfirmed,
                'documents_by_type' => $documentsByType,
                'documents_by_source' => $documentsBySource,
                'recent_documents' => $recentDocuments,
                'pending_upload' => $pendingUpload,
                'completion_rate' => $totalDocuments > 0
                    ? round(($documentsWithMedia / $totalDocuments) * 100, 2)
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating document statistics', ['error' => $e->getMessage()]);

            return [
                'total_documents' => 0,
                'documents_with_media' => 0,
                'documents_confirmed' => 0,
                'documents_by_type' => [],
                'documents_by_source' => [],
                'recent_documents' => 0,
                'pending_upload' => 0,
                'completion_rate' => 0,
            ];
        }
    }
}
