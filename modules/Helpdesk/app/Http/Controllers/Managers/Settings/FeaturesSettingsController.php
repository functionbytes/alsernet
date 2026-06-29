<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateFeaturesSettingsRequest;
use Modules\Helpdesk\Models\Setting;

class FeaturesSettingsController extends Controller
{
    private const GROUP = 'features';

    private const DEFAULTS = [
        // Barra de acciones del hilo
        'feature_email_enabled' => true,
        'feature_tickets_enabled' => true,
        'feature_schedule_enabled' => true,
        'feature_snooze_enabled' => true,
        'feature_assign_enabled' => true,
        'feature_tags_enabled' => true,
        'feature_search_enabled' => true,
        // Menú "Más" (dropdown)
        'feature_note_enabled' => true,
        'feature_csat_enabled' => true,
        'feature_merge_enabled' => true,
        'feature_move_team_enabled' => true,
        'feature_spam_enabled' => true,
        'feature_block_contact_enabled' => true,
        'feature_delete_conv_enabled' => true,
        'feature_forward_enabled' => true,
        'feature_preview_conv_enabled' => true,
        // Panel derecho
        'feature_rp_email_enabled' => true,
        'feature_rp_schedule_enabled' => true,
        'feature_rp_note_enabled' => true,
        'feature_rp_stats_enabled' => true,
        'feature_rp_status_enabled' => true,
        'feature_rp_tags_section_enabled' => true,
        'feature_rp_integrations_enabled' => true,
        // Pestañas del panel derecho
        'feature_tab_general_enabled' => true,
        'feature_tab_files_enabled' => true,
        'feature_tab_tickets_enabled' => true,
        'feature_tab_previous_enabled' => true,
        'feature_tab_activity_enabled' => true,
        'feature_tab_technology_enabled' => true,
        'feature_tab_orders_enabled' => true,
        'feature_tab_customer360_enabled' => true,
        // Barra de redacción (composer)
        'feature_composer_hsm_enabled' => true,
        'feature_composer_note_enabled' => true,
        'feature_composer_attach_enabled' => true,
        'feature_composer_emoji_enabled' => true,
        'feature_composer_mention_enabled' => true,
        'feature_composer_canned_enabled' => true,
        'feature_composer_record_enabled' => true,
        'feature_composer_ai_enabled' => true,
        // Pestaña panel derecho
        'feature_tab_assist_enabled' => true,
    ];

    private const BOOL_KEYS = [
        'feature_email_enabled',
        'feature_tickets_enabled',
        'feature_schedule_enabled',
        'feature_snooze_enabled',
        'feature_assign_enabled',
        'feature_tags_enabled',
        'feature_search_enabled',
        'feature_note_enabled',
        'feature_csat_enabled',
        'feature_merge_enabled',
        'feature_move_team_enabled',
        'feature_spam_enabled',
        'feature_block_contact_enabled',
        'feature_delete_conv_enabled',
        'feature_forward_enabled',
        'feature_preview_conv_enabled',
        'feature_rp_email_enabled',
        'feature_rp_schedule_enabled',
        'feature_rp_note_enabled',
        'feature_rp_stats_enabled',
        'feature_rp_status_enabled',
        'feature_rp_tags_section_enabled',
        'feature_rp_integrations_enabled',
        'feature_tab_general_enabled',
        'feature_tab_files_enabled',
        'feature_tab_tickets_enabled',
        'feature_tab_previous_enabled',
        'feature_tab_activity_enabled',
        'feature_tab_technology_enabled',
        'feature_tab_orders_enabled',
        'feature_tab_customer360_enabled',
        'feature_composer_hsm_enabled',
        'feature_composer_note_enabled',
        'feature_composer_attach_enabled',
        'feature_composer_emoji_enabled',
        'feature_composer_mention_enabled',
        'feature_composer_canned_enabled',
        'feature_composer_record_enabled',
        'feature_composer_ai_enabled',
        'feature_tab_assist_enabled',
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only('update');
    }

    public function index(): View
    {
        $prefix = self::GROUP.'.';
        $raw = Setting::allAsArray(self::GROUP);

        $dbSettings = [];
        foreach ($raw as $k => $v) {
            $bare = str_starts_with($k, $prefix) ? substr($k, strlen($prefix)) : $k;
            $dbSettings[$bare] = $v;
        }

        $settings = array_merge(self::DEFAULTS, $dbSettings);

        return view('helpdesk::settings.features.index', compact('settings'));
    }

    public function update(UpdateFeaturesSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach (self::BOOL_KEYS as $key) {
            $validated[$key] = $request->has($key);
        }

        foreach ($validated as $key => $value) {
            Setting::set(self::GROUP.'.'.$key, $value, self::GROUP);
        }

        return back()->with('success', 'Configuración de funcionalidades actualizada correctamente.');
    }
}
