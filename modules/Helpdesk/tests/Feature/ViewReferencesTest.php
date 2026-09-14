<?php

namespace Modules\Helpdesk\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ViewReferencesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function viewNameProvider(): array
    {
        return [
            'live dashboard' => ['helpdesk::helpdesk.dashboard.live'],
            'heatmap report' => ['helpdesk::helpdesk.reports.heatmap'],
            'trends report' => ['helpdesk::helpdesk.reports.trends'],
            'agent performance report' => ['helpdesk::helpdesk.reports.agents'],
            'leaderboard' => ['helpdesk::helpdesk.leaderboard.index'],
            'two factor setup' => ['helpdesk::helpdesk.compliance.2fa.setup'],
            'two factor challenge' => ['helpdesk::helpdesk.compliance.2fa.challenge'],
            'portal dashboard' => ['helpdesk::public.portal.dashboard'],
            'portal conversation' => ['helpdesk::public.portal.conversation'],
            'portal profile' => ['helpdesk::public.portal.profile'],
        ];
    }

    #[DataProvider('viewNameProvider')]
    public function test_view_referenced_by_controller_exists(string $view): void
    {
        $this->assertTrue(view()->exists($view), "View [{$view}] does not exist.");
    }
}
