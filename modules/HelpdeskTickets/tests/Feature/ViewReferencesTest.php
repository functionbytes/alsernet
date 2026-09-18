<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

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
            'portal login' => ['helpdesktickets::portal.login'],
            'portal account' => ['helpdesktickets::portal.account'],
            'agent dashboard' => ['helpdesktickets::agents.dashboard'],
        ];
    }

    #[DataProvider('viewNameProvider')]
    public function test_view_referenced_by_controller_exists(string $view): void
    {
        $this->assertTrue(view()->exists($view), "View [{$view}] does not exist.");
    }
}
