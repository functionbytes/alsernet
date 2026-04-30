<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Settings\HelpdeskSettingsRepository;

class ConfigurationController extends Controller
{
    public function __construct(
        private readonly HelpdeskSettingsRepository $settings,
    ) {}

    public function globalConfig(): View
    {
        $settings = $this->settings->get('chat.configuration', [
            'system_name' => 'Pages',
            'system_url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'language' => 'es',
            'enable_logging' => true,
            'enable_analytics' => true,
            'retention_days' => 90,
        ]);

        return view('Chat::settings.configurations.global', compact('settings'));
    }

    public function updateGlobalConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_name' => 'required|string|min:3|max:100',
            'timezone' => 'required|timezone',
            'language' => 'required|in:es,en,pt,fr',
            'enable_logging' => 'boolean',
            'enable_analytics' => 'boolean',
            'retention_days' => 'required|integer|min:1|max:365',
        ]);

        $this->settings->save('chat.configuration', $validated);

        return back()->with('success', 'Configuración global actualizada correctamente');
    }

    public function businessHours(): View
    {
        $settings = $this->settings->get('chat.business_hours', [
            'enabled' => true,
            'monday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'tuesday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'wednesday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'thursday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'friday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'saturday' => ['start' => '00:00', 'end' => '00:00', 'enabled' => false],
            'sunday' => ['start' => '00:00', 'end' => '00:00', 'enabled' => false],
        ]);

        return view('Chat::settings.business-hours', compact('settings'));
    }

    public function updateBusinessHours(Request $request): RedirectResponse
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $rules = ['enabled' => 'boolean'];
        foreach ($days as $day) {
            $rules["{$day}.start"] = 'required|date_format:H:i';
            $rules["{$day}.end"] = 'required|date_format:H:i';
            $rules["{$day}.enabled"] = 'boolean';
        }

        $validated = $request->validate($rules);

        $this->settings->save('chat.business_hours', $validated);

        return back()->with('success', 'Horarios de negocio actualizados correctamente');
    }
}
