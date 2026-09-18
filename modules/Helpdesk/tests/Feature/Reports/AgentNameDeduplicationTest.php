<?php

namespace Modules\Helpdesk\Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Concerns\FormatsAgentNames;
use Modules\Helpdesk\Http\Controllers\Managers\AgentPerformanceController;
use Modules\Helpdesk\Http\Controllers\Managers\CsatReportController;
use Modules\Helpdesk\Http\Requests\Managers\CsatReportDataRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;
use Tests\TestCase;

/**
 * Varios agentes reales de esta base tienen el mismo valor en firstname y
 * lastname ("Ángeles Ángeles", "Helena Helena"): concatenar a ciegas
 * duplicaba el nombre en pantalla. Ya se arregló una vez en
 * SlaBreachesReportController; este test cubre los otros dos sitios donde
 * el mismo código se había copiado sin el arreglo (CsatReportController,
 * dos veces, y AgentPerformanceController) para que un futuro informe que
 * copie el patrón no reintroduzca el defecto sin que salte un test.
 *
 * @see FormatsAgentNames
 */
class AgentNameDeduplicationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    private function duplicateNameAgent(): User
    {
        return User::factory()->create(['firstname' => 'Casimira', 'lastname' => 'Casimira']);
    }

    public function test_csat_by_agent_does_not_duplicate_the_name(): void
    {
        $this->actingAs(User::orderBy('id')->firstOrFail());

        $agent = $this->duplicateNameAgent();
        $conversation = Conversation::factory()->create();

        CsatRating::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'agent_id' => $agent->id,
            'rating' => 5,
            'survey_token' => 'test-'.uniqid(),
            'sent_at' => now()->subDay(),
            'answered_at' => now()->subHours(2),
        ]);

        $from = now()->subDays(30)->startOfDay();
        $to = now()->endOfDay();
        Cache::forget(sprintf('helpdesk:reports:csat:%s:%s', $from->toDateString(), $to->toDateString()));

        $request = CsatReportDataRequest::create('/panel/helpdesk/reports/csat/data', 'GET');
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->setUserResolver(fn () => auth()->user());
        $request->validateResolved();

        $payload = app(CsatReportController::class)->data($request)->getData(true);

        $row = collect($payload['by_agent'])->firstWhere('agent_id', $agent->id);

        $this->assertNotNull($row);
        $this->assertSame('Casimira', $row['agent_name']);
        $this->assertNotSame('Casimira Casimira', $row['agent_name']);
    }

    public function test_csat_low_ratings_does_not_duplicate_the_name(): void
    {
        $this->actingAs(User::orderBy('id')->firstOrFail());

        $agent = $this->duplicateNameAgent();
        $conversation = Conversation::factory()->create();

        CsatRating::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'agent_id' => $agent->id,
            'rating' => 1,
            'comment' => 'Nada bien, tardaron mucho.',
            'survey_token' => 'test-'.uniqid(),
            'sent_at' => now()->subDay(),
            'answered_at' => now()->subHours(1),
        ]);

        $from = now()->subDays(30)->startOfDay();
        $to = now()->endOfDay();
        Cache::forget(sprintf('helpdesk:reports:csat:%s:%s', $from->toDateString(), $to->toDateString()));

        $request = CsatReportDataRequest::create('/panel/helpdesk/reports/csat/data', 'GET');
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->setUserResolver(fn () => auth()->user());
        $request->validateResolved();

        $payload = app(CsatReportController::class)->data($request)->getData(true);

        $row = collect($payload['low_ratings'])->firstWhere('agent_name', 'Casimira');

        $this->assertNotNull($row);
    }

    public function test_agent_performance_report_does_not_duplicate_the_name(): void
    {
        $this->actingAs(User::orderBy('id')->firstOrFail());

        $agent = $this->duplicateNameAgent();

        Conversation::factory()->create([
            'assignee_id' => $agent->id,
            'closed_at' => now(),
            'first_response_at' => now()->subMinutes(10),
        ]);

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        Cache::forget("helpdesk:reports:agents:{$from->toDateString()}:{$to->toDateString()}");

        $view = app(AgentPerformanceController::class)->index();
        $agents = $view->getData()['agents'];

        $this->assertTrue(
            $agents->contains('name', 'Casimira'),
            'El informe de rendimiento debería mostrar "Casimira", no "Casimira Casimira".'
        );
    }
}
