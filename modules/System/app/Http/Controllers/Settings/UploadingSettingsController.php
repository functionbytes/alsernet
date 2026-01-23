<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class UploadingSettingsController extends Controller
{
    /**
     * Display uploading backups page
     */
    public function index()
    {
        $pageTitle = 'Configuración de carga de archivos';
        $breadcrumb = 'Configuración / Carga de archivos';

        // Get uploading backups from database
        $settings = Setting::getUploadingSettings();

        // Get current PHP upload limits
        $phpLimits = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'memory_limit' => ini_get('memory_limit'),
        ];

        return view('system::uploading.index', compact(
            'pageTitle',
            'breadcrumb',
            'settings',
            'phpLimits'
        ));
    }

    /**
     * Update uploading backups
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'max_file_size' => 'required|integer|min:1|max:102400',
            'allowed_file_types' => 'required|array|min:1',
            'allowed_image_types' => 'required|array|min:1',
            'allowed_document_types' => 'required|array|min:1',
            'max_files_per_upload' => 'required|integer|min:1|max:50',
            'enable_virus_scan' => 'nullable|boolean',
            'storage_driver' => 'required|string|in:local,s3,spaces,ftp',
            's3_bucket' => 'nullable|string',
            's3_region' => 'nullable|string',
        ]);

        try {
            Setting::setUploadingSettings($validated);

            return redirect()->route('settings.system.uploading.index')
                ->with('success', 'Configuración de carga de archivos actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la configuración: '.$e->getMessage())
                ->withInput();
        }
    }
}
