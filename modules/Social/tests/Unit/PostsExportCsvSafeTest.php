<?php

namespace Modules\Social\Tests\Unit;

use Illuminate\Support\Carbon;
use Modules\Social\Exports\PostsExport;
use PHPUnit\Framework\TestCase;

/**
 * Regresión de seguridad: los campos de texto editables del post (contenido,
 * nombre de cuenta/creador) deben salir escapados en el CSV para que Excel/
 * Sheets no ejecute como fórmula un valor que empieza por =,+,-,@.
 */
class PostsExportCsvSafeTest extends TestCase
{
    public function test_map_prefixes_formula_like_values_with_a_quote(): void
    {
        $row = (new PostsExport(1))->map($this->fakePost(
            content: '=SUM(A1)',
            accountName: '=cmd|/c calc',
            creatorName: 'Nombre Normal',
        ));

        // content (índice 4) y nombre de cuenta (índice 1) empiezan por '=' → prefijados con comilla.
        $this->assertSame("'=SUM(A1)...", $row[4]);
        $this->assertStringStartsWith("'", $row[1]);
        // Un valor normal no se altera.
        $this->assertSame('Nombre Normal', $row[11]);
    }

    private function fakePost(string $content, string $accountName, string $creatorName): object
    {
        return new class($content, $accountName, $creatorName)
        {
            public int $id = 1;

            public object $socialAccount;

            public ?object $campaign = null;

            public object $status;

            public string $content;

            public ?Carbon $scheduled_at = null;

            public ?Carbon $published_at = null;

            public int $likes_count = 0;

            public int $comments_count = 0;

            public int $shares_count = 0;

            public object $creator;

            public Carbon $created_at;

            public function __construct(string $content, string $accountName, string $creatorName)
            {
                $this->content = $content;
                $this->socialAccount = (object) ['name' => $accountName];
                $this->creator = (object) ['name' => $creatorName];
                $this->status = (object) ['value' => 'draft'];
                $this->created_at = Carbon::parse('2026-01-01 00:00:00');
            }

            public function getTotalEngagement(): int
            {
                return 0;
            }
        };
    }
}
