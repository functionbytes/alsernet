<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Engagement\Models\AbTest;
use Modules\Engagement\Models\AutomationFlow;
use Modules\Engagement\Models\ConversionGoal;
use Modules\Engagement\Models\EmailCampaign;
use Modules\Engagement\Models\PersonalizationRule;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Engagement\Models\Segment;
use Modules\Engagement\Models\TriggerRule;
use Modules\Engagement\Models\WebChannel;
use Modules\Helpdesk\Models\Inbox;

class SettingsIndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:engagement.triggers.view')->only('index');
    }

    public function index(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        $stats = [
            'triggers' => [
                'label' => 'Reglas de activación',
                'route' => 'settings.engagement.triggers.page',
                'permission' => 'engagement.triggers.view',
                'count' => TriggerRule::query()->active()->count(),
                'icon' => 'fa-bolt',
                'color' => 'warning',
            ],
            'personalizations' => [
                'label' => 'Personalización DOM',
                'route' => 'settings.engagement.personalizations.page',
                'permission' => 'engagement.personalizations.view',
                'count' => PersonalizationRule::query()->active()->count(),
                'icon' => 'fa-code',
                'color' => 'info',
            ],
            'platforms' => [
                'label' => 'Integraciones',
                'route' => 'settings.engagement.platforms.page',
                'permission' => 'engagement.platforms.view',
                'count' => PlatformIntegration::query()->active()->count(),
                'icon' => 'fa-plug',
                'color' => 'success',
            ],
            'automation' => [
                'label' => 'Automation',
                'route' => 'settings.engagement.automation.page',
                'permission' => 'engagement.automation.view',
                'count' => AutomationFlow::query()->active()->count(),
                'icon' => 'fa-robot',
                'color' => 'primary',
            ],
            'goals' => [
                'label' => 'Objetivos',
                'route' => 'settings.engagement.goals.page',
                'permission' => 'engagement.goals.view',
                'count' => ConversionGoal::query()->active()->count(),
                'icon' => 'fa-bullseye',
                'color' => 'danger',
            ],
            'email_campaigns' => [
                'label' => 'Campañas email',
                'route' => 'settings.engagement.email-campaigns.page',
                'permission' => 'engagement.email_campaigns.view',
                'count' => EmailCampaign::query()->whereIn('status', ['draft', 'scheduled'])->count(),
                'icon' => 'fa-envelope',
                'color' => 'primary',
            ],
            'segments' => [
                'label' => 'Segmentos',
                'route' => 'settings.engagement.segments.page',
                'permission' => 'engagement.segments.view',
                'count' => Segment::query()->active()->count(),
                'icon' => 'fa-users',
                'color' => 'info',
            ],
            'ab_tests' => [
                'label' => 'AB Tests',
                'route' => 'settings.engagement.ab-tests.page',
                'permission' => 'engagement.ab_tests.view',
                'count' => AbTest::query()->whereIn('status', [AbTest::STATUS_RUNNING, AbTest::STATUS_DRAFT])->count(),
                'icon' => 'fa-flask',
                'color' => 'success',
            ],
            'web_channels' => [
                'label' => 'Canales web',
                'route' => 'settings.engagement.web-channels.page',
                'permission' => 'engagement.web_channels.view',
                'count' => WebChannel::query()->active()->count(),
                'icon' => 'fa-globe',
                'color' => 'primary',
            ],
        ];

        return view('engagement::settings.engagement.index', compact('inboxes', 'stats'));
    }
}
