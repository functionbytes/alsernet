<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Settings\HelpdeskSettingsRepository;

class UploadSettingsController extends Controller
{
    public function __construct(
        private readonly HelpdeskSettingsRepository $settings,
    ) {}

    public function index(): View
    {
        $settings = $this->settings->get('chat.uploading', [
            'max_file_size_mb' => 25,
            'allowed_extensions' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip',
            'enable_image_compression' => true,
            'image_max_width' => 1920,
            'image_max_height' => 1080,
            'image_quality' => 85,
            'enable_virus_scan' => false,
            'enable_quarantine' => true,
        ]);

        return view('Chat::settings.uploading', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_file_size_mb' => 'required|integer|min:1|max:1000',
            'allowed_extensions' => 'required|string',
            'enable_image_compression' => 'boolean',
            'image_max_width' => 'required|integer|min:100|max:4000',
            'image_max_height' => 'required|integer|min:100|max:4000',
            'image_quality' => 'required|integer|min:10|max:100',
            'enable_virus_scan' => 'boolean',
            'enable_quarantine' => 'boolean',
        ]);

        $this->settings->save('chat.uploading', $validated);

        return back()->with('success', 'Configuración de subida de archivos actualizada correctamente');
    }
}
