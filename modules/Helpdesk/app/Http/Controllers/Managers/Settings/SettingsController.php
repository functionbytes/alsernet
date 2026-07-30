<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\UpdateUploadingSettingsRequest;
use Modules\Helpdesk\Models\Setting;

class SettingsController extends Controller
{
    private const DEFAULTS = [
        'max_file_size_mb' => 25,
        'allowed_extensions' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip',
        'enable_image_compression' => true,
        'image_max_width' => 1920,
        'image_max_height' => 1080,
        'image_quality' => 85,
        'enable_virus_scan' => false,
        'enable_quarantine' => true,
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('uploadingIndex');
        $this->middleware('can:helpdesk.settings.update')->only('uploadingUpdate');
    }

    public function uploadingIndex(): View
    {
        $settings = array_merge(self::DEFAULTS, Setting::allAsFlatArray('uploading'));

        // Normalise stored extensions to array for the multi-select
        if (is_string($settings['allowed_extensions'])) {
            $settings['allowed_extensions'] = array_values(
                array_filter(array_map('trim', explode(',', $settings['allowed_extensions'])))
            );
        }

        $phpLimits = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
        ];

        return view('helpdesk::settings.uploading.index', compact('settings', 'phpLimits'));
    }

    public function uploadingUpdate(UpdateUploadingSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Normalise booleans from unchecked checkboxes / select values
        foreach (['enable_image_compression', 'enable_virus_scan', 'enable_quarantine'] as $field) {
            $validated[$field] = ! empty($validated[$field]);
        }

        // Store extensions as comma-separated string
        if (is_array($validated['allowed_extensions'])) {
            $validated['allowed_extensions'] = implode(',', $validated['allowed_extensions']);
        }

        Cache::put('helpdesk.uploading', $validated, now()->addDays(365));

        foreach ($validated as $key => $value) {
            Setting::set('uploading.'.$key, $value, 'uploading');
        }

        return back()->with('success', 'Configuración de subida de archivos actualizada correctamente.');
    }
}
