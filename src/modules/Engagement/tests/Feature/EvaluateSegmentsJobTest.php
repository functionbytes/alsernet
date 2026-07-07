<?php

namespace Modules\Engagement\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Engagement\Jobs\EvaluateSegmentsJob;
use Modules\Engagement\Models\Segment;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\SegmentEvaluator;
use Tests\TestCase;

/**
 * Regresión: la query de scores solo seleccionaba session_token/inbox_id, así
 * que $score->customer_id (usado al upsertar el match) siempre era null y el
 * ?? 0 se activaba siempre — todo match de segmento se guardaba con
 * customer_id=0 en vez del cliente real.
 */
class EvaluateSegmentsJobTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_it_stores_the_real_customer_id_on_segment_match(): void
    {
        $segment = Segment::factory()->create(['inbox_id' => 1, 'is_active' => true]);

        VisitorScore::factory()->create([
            'inbox_id' => 1,
            'customer_id' => 42,
        ]);

        $this->mock(SegmentEvaluator::class, function ($mock) {
            $mock->shouldReceive('matches')->andReturn(true);
        });

        (new EvaluateSegmentsJob(1))->handle(app(SegmentEvaluator::class));

        $row = DB::connection('helpdesk')->table('engagement_segment_customers')
            ->where('segment_id', $segment->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(42, (int) $row->customer_id);
    }
}
