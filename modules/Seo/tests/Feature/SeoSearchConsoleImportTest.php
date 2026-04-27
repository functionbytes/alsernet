<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Tests\TestCase;

class SeoSearchConsoleImportTest extends TestCase
{
    public function test_shows_import_page(): void
    {
        $user = $this->createUser(['Seo.dashboard.view', 'Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('settings.seo.search-console.import'))
            ->assertOk();
    }

    public function test_imports_gsc_csv_and_updates_meta(): void
    {
        $meta = SeoMeta::factory()->create(['canonical_url' => 'https://example.com/test-page']);

        $csvContent = "Page,Clicks,Impressions,CTR,Position\nhttps://example.com/test-page,150,3000,5.0%,8.5\n";
        $file = UploadedFile::fake()->createWithContent('gsc.csv', $csvContent);

        $user = $this->createUser(['Seo.dashboard.view', 'Seo.metas.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.search-console.import.store'), ['csv_file' => $file])
            ->assertRedirect(route('settings.seo.report.index'))
            ->assertSessionHas('success');

        $this->assertEquals(150, $meta->fresh()->gsc_clicks);
        $this->assertEquals(3000, $meta->fresh()->gsc_impressions);
        $this->assertEquals(8.5, $meta->fresh()->gsc_position);
    }

    public function test_import_skips_rows_without_matching_meta(): void
    {
        $csvContent = "Page,Clicks,Impressions,CTR,Position\nhttps://example.com/nonexistent,50,1000,5.0%,10\n";
        $file = UploadedFile::fake()->createWithContent('gsc.csv', $csvContent);

        $user = $this->createUser(['Seo.dashboard.view', 'Seo.metas.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.search-console.import.store'), ['csv_file' => $file])
            ->assertSessionHas('success');
    }

    public function test_rejects_non_csv_file(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100);

        $user = $this->createUser(['Seo.dashboard.view', 'Seo.metas.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.search-console.import.store'), ['csv_file' => $file])
            ->assertSessionHasErrors('csv_file');
    }
}
