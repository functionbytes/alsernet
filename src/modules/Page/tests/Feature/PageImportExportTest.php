<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Page\Models\Page;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'page.create', 'guard_name' => 'web']);
    }

    private function userWithPermission(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $perm) {
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    private function makeCsvFile(string $content, string $name = 'import.csv'): UploadedFile
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'page_import_');
        file_put_contents($tmpPath, $content);

        return new UploadedFile($tmpPath, $name, 'text/csv', null, true);
    }

    private function makeJsonFile(array $data, string $name = 'import.json'): UploadedFile
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'page_import_json_');
        file_put_contents($tmpPath, json_encode($data));

        return new UploadedFile($tmpPath, $name, 'application/json', null, true);
    }

    // =========================================================================
    // Export
    // =========================================================================

    public function test_admin_can_export_pages_as_csv(): void
    {
        $user = $this->userWithPermission('page.view');
        Page::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->get(route('pages.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_export_pages_as_json(): void
    {
        $user = $this->userWithPermission('page.view');
        Page::factory()->count(2)->create();

        $response = $this->actingAs($user)
            ->get(route('pages.export', ['format' => 'json']));

        $response->assertOk();
        $response->assertJsonStructure([['id', 'title', 'slug', 'status']]);
    }

    public function test_export_json_includes_content_disposition_header(): void
    {
        $user = $this->userWithPermission('page.view');

        $response = $this->actingAs($user)
            ->get(route('pages.export', ['format' => 'json']));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_unauthorized_user_cannot_export(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pages.export'))
            ->assertForbidden();
    }

    public function test_guest_cannot_export(): void
    {
        $this->get(route('pages.export'))->assertRedirect();
    }

    // =========================================================================
    // Import
    // =========================================================================

    public function test_admin_can_import_pages_from_csv(): void
    {
        $user = $this->userWithPermission('page.create');

        $csv = "title;slug;content;status\nTest Page;test-page;Some content;draft\n";
        $file = $this->makeCsvFile($csv);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'csv',
            ])
            ->assertRedirect(route('pages.index'));
    }

    public function test_import_creates_pages_in_database(): void
    {
        $user = $this->userWithPermission('page.create');

        $csv = "title;slug;content;status\nImported Page;imported-page;Hello;draft\n";
        $file = $this->makeCsvFile($csv);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'csv',
            ]);

        $this->assertDatabaseHas('pages', ['title' => 'Imported Page']);
    }

    public function test_import_handles_duplicate_slugs(): void
    {
        $user = $this->userWithPermission('page.create');

        Page::factory()->create(['slug' => 'my-page']);

        $csv = "title;slug;content;status\nDuplicate;my-page;Content;draft\n";
        $file = $this->makeCsvFile($csv);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'csv',
            ]);

        // Original slug preserved, new one should be my-page-1
        $this->assertDatabaseHas('pages', ['slug' => 'my-page-1']);
        $this->assertDatabaseHas('pages', ['slug' => 'my-page']);
    }

    public function test_import_skips_rows_without_title(): void
    {
        $user = $this->userWithPermission('page.create');

        $csv = "title;slug;content;status\n;no-title;Content;draft\n";
        $file = $this->makeCsvFile($csv);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'csv',
            ]);

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_import_sanitizes_html_content(): void
    {
        $user = $this->userWithPermission('page.create');

        // Use a JSON import to avoid CSV delimiter conflicts with HTML angle brackets
        $data = [
            [
                'title' => 'XSS Page',
                'slug' => 'xss-page',
                'content' => '<p>Hello</p><script>alert(1)</script>',
                'status' => 'draft',
            ],
        ];
        $file = $this->makeJsonFile($data);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'json',
            ]);

        $page = Page::where('slug', 'xss-page')->first();
        $this->assertNotNull($page);
        $this->assertStringNotContainsString('<script>', $page->content);
    }

    public function test_import_accepts_json_format(): void
    {
        $user = $this->userWithPermission('page.create');

        $data = [
            ['title' => 'JSON Page', 'slug' => 'json-page', 'content' => 'Body', 'status' => 'draft'],
        ];
        $file = $this->makeJsonFile($data);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'json',
            ])
            ->assertRedirect(route('pages.index'));

        $this->assertDatabaseHas('pages', ['title' => 'JSON Page']);
    }

    public function test_import_validates_file_type(): void
    {
        $user = $this->userWithPermission('page.create');

        $tmpPath = tempnam(sys_get_temp_dir(), 'page_import_');
        file_put_contents($tmpPath, '<?php echo "evil"; ?>');
        $file = new UploadedFile($tmpPath, 'malicious.php', 'application/x-php', null, true);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'csv',
            ])
            ->assertSessionHasErrors(['file']);
    }

    public function test_import_requires_file(): void
    {
        $user = $this->userWithPermission('page.create');

        $this->actingAs($user)
            ->post(route('pages.import.process'), ['format' => 'csv'])
            ->assertSessionHasErrors(['file']);
    }

    public function test_unauthorized_user_cannot_import(): void
    {
        $user = User::factory()->create();

        $csv = "title;slug\nPage;page\n";
        $file = $this->makeCsvFile($csv);

        $this->actingAs($user)
            ->post(route('pages.import.process'), [
                'file' => $file,
                'format' => 'csv',
            ])
            ->assertForbidden();
    }

    public function test_guest_cannot_import(): void
    {
        $csv = "title;slug\nPage;page\n";
        $file = $this->makeCsvFile($csv);

        $this->post(route('pages.import.process'), [
            'file' => $file,
            'format' => 'csv',
        ])->assertRedirect();
    }
}
