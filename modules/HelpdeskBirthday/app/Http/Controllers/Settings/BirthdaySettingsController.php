<?php

namespace Modules\HelpdeskBirthday\Http\Controllers\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskBirthday\Http\Requests\UpdateBirthdaySettingsRequest;
use Modules\HelpdeskBirthday\Services\BirthdayScheduleCalculator;
use Modules\HelpdeskBirthday\Services\BirthdayTestSendService;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;

class BirthdaySettingsController extends Controller
{
    public function __construct(
        private readonly BirthdaySettings $settings,
    ) {}

    public function index(BirthdayScheduleCalculator $calculator): View
    {
        $settings = $this->settings->all();

        // Ejemplo de ritmo con una audiencia típica, para que quien configure
        // vea el efecto de la ventana y el tope antes de guardar.
        $sample = null;

        try {
            [$start, $end] = $calculator->windowFor(
                now()->toImmutable(),
                (string) $settings['window_start'],
                (string) $settings['window_end'],
            );
            $sample = $calculator->plan(100, $start, $end, (int) $settings['throttle_per_hour']);
        } catch (\Throwable) {
            // Horas mal configuradas: el formulario ya las valida al guardar.
        }

        return view('helpdeskbirthday::settings.index', compact('settings', 'sample'));
    }

    public function update(UpdateBirthdaySettingsRequest $request): RedirectResponse
    {
        $this->settings->save($request->settings());

        return back()->with('success', __('helpdeskbirthday::messages.settings_saved'));
    }

    /**
     * Manda la felicitación a direcciones internas para verla en un cliente de
     * correo real antes del envío del día. No toca ninguna campaña.
     */
    public function testSend(Request $request, BirthdayTestSendService $tester): RedirectResponse
    {
        $data = $request->validate([
            'test_emails' => ['required', 'string', 'max:500'],
        ]);

        $emails = preg_split('/[\s,;]+/', $data['test_emails'], -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($emails) > 5) {
            return back()->with('error', __('helpdeskbirthday::messages.test_send_too_many'));
        }

        $result = $tester->send($emails);

        if ($result['sent'] === []) {
            return back()->with('error', __('helpdeskbirthday::messages.test_send_failed', [
                'errors' => implode(' · ', $result['failed']),
            ]));
        }

        return back()->with('success', __('helpdeskbirthday::messages.test_send_ok', [
            'emails' => implode(', ', $result['sent']),
        ]));
    }
}
