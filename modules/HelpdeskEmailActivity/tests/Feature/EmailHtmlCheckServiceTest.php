<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Modules\HelpdeskEmailActivity\Services\EmailHtmlCheckService;
use Tests\TestCase;

/**
 * El motor de compatibilidad no toca base de datos: analiza una cadena HTML
 * contra el dataset de caniemail que viaja en el repo, así que estos tests son
 * unitarios puros (sin DatabaseTransactions, como el resto de tests de soporte
 * del módulo).
 */
class EmailHtmlCheckServiceTest extends TestCase
{
    private function service(): EmailHtmlCheckService
    {
        return app(EmailHtmlCheckService::class);
    }

    private function warning(array $result, string $slug): ?array
    {
        foreach ($result['Warnings'] as $warning) {
            if ($warning['Slug'] === $slug) {
                return $warning;
            }
        }

        return null;
    }

    public function test_detects_css_properties_declared_inline(): void
    {
        $result = $this->service()->run('<div style="margin:0; border-radius:8px;">Hola</div>');

        $this->assertNotNull($this->warning($result, 'css-margin'));
        $this->assertNotNull($this->warning($result, 'css-border-radius'));
    }

    public function test_counts_one_occurrence_per_node_using_the_property(): void
    {
        $html = '<div style="padding:4px"><span style="padding:8px">a</span><b style="padding:2px">b</b></div>';

        $this->assertSame(3, $this->warning($this->service()->run($html), 'css-padding')['Score']['Found']);
    }

    public function test_merges_style_blocks_into_nodes_before_testing(): void
    {
        // Sin fundir el <style> en los nodos, un correo que declare todo su
        // estilo en la cabecera no daría ni un solo aviso de CSS.
        $html = '<html><head><style>p { line-height: 1.5; }</style></head><body><p>Hola</p></body></html>';

        $this->assertNotNull($this->warning($this->service()->run($html), 'css-line-height'));
    }

    public function test_detects_at_rules_that_only_exist_in_style_blocks(): void
    {
        $html = '<html><head><style>@media (max-width: 600px) { .a { color: red; } }</style></head><body><p>a</p></body></html>';

        $this->assertNotNull($this->warning($this->service()->run($html), 'css-at-media'));
    }

    public function test_flags_script_as_unsupported_everywhere(): void
    {
        $warning = $this->warning($this->service()->run('<div><script>alert(1)</script></div>'), 'html-script');

        $this->assertNotNull($warning);
        $this->assertSame(100.0, $warning['Score']['Unsupported']);
        $this->assertSame(0.0, $warning['Score']['Supported']);
    }

    public function test_ignores_structured_data_scripts(): void
    {
        $html = '<div><script type="application/ld+json">{"@type":"Order"}</script></div>';

        $this->assertNull($this->warning($this->service()->run($html), 'html-script'));
    }

    public function test_detects_image_formats_by_extension(): void
    {
        $result = $this->service()->run('<img src="https://example.com/a.webp"><img src="https://example.com/b.gif">');

        $this->assertNotNull($this->warning($result, 'image-webp'));
        $this->assertNotNull($this->warning($result, 'image-gif'));
    }

    public function test_counts_implicit_tbody_nodes_like_a_real_email_client(): void
    {
        // Un cliente de correo parsea con reglas HTML5 e inserta un <tbody> por
        // tabla con filas sueltas; sin contarlos, el denominador de la
        // ponderación queda corto y todo aviso sale más grave de lo que es.
        $html = '<table><tr><td style="margin:0">a</td></tr></table>';

        // table + tr + td + tbody implícito.
        $this->assertSame(4, $this->service()->run($html)['Total']['Nodes']);
    }

    public function test_score_is_the_worst_weighted_case_not_an_average(): void
    {
        $result = $this->service()->run('<div style="margin:0"><p style="padding:0">a</p></div>');
        $total = $result['Total'];

        $worstPartial = 0.0;

        foreach ($result['Warnings'] as $warning) {
            $worstPartial = max($worstPartial, $warning['Score']['Partial'] * $warning['Score']['Found'] / $total['Nodes']);
        }

        $this->assertEqualsWithDelta($worstPartial, $total['Partial'], 0.001);
        $this->assertEqualsWithDelta(100 - $total['Partial'] - $total['Unsupported'], $total['Supported'], 0.001);
    }

    public function test_warnings_are_sorted_worst_first(): void
    {
        $result = $this->service()->run(file_get_contents(__DIR__.'/../Fixtures/sample-email.html'));
        $nodes = $result['Total']['Nodes'];

        $weights = array_map(
            fn (array $w) => ($w['Score']['Unsupported'] + $w['Score']['Partial']) * $w['Score']['Found'] / $nodes,
            $result['Warnings'],
        );

        $sorted = $weights;
        rsort($sorted);

        $this->assertSame($sorted, $weights);
    }

    public function test_limiting_platforms_changes_the_score(): void
    {
        $html = '<div style="margin:0">a</div>';

        $all = $this->warning($this->service()->run($html), 'css-margin');
        $outlookOnly = $this->warning($this->service()->run($html, ['windows']), 'css-margin');

        $this->assertNotSame($all['Score']['Supported'], $outlookOnly['Score']['Supported']);

        foreach ($outlookOnly['Results'] as $result) {
            $this->assertSame('windows', $result['Platform']);
        }
    }

    public function test_partial_support_keeps_its_note_number(): void
    {
        $warning = $this->warning($this->service()->run('<div style="margin:0">a</div>'), 'css-margin');

        $withNotes = array_filter($warning['Results'], fn (array $r) => $r['NoteNumber'] !== '');

        $this->assertNotEmpty($withNotes);

        foreach ($withNotes as $result) {
            $this->assertSame('partial', $result['Support']);
            $this->assertArrayHasKey($result['NoteNumber'], $warning['NotesByNumber']);
        }
    }

    public function test_descriptions_escape_html_from_the_dataset(): void
    {
        $warning = $this->warning($this->service()->run('<div style="margin:0">a</div>'), 'css-margin');

        // El texto del dataset viene en Markdown ligero: los `backticks` pasan a
        // <code>, y cualquier otra marca queda escapada — este HTML se pinta sin
        // escapar en la vista.
        $this->assertStringContainsString('<code>margin</code>', $warning['Description']);
        $this->assertStringNotContainsString('<script', $warning['Description']);
    }

    public function test_reports_platforms_with_their_display_names(): void
    {
        $result = $this->service()->run('<div style="margin:0">a</div>');

        $this->assertArrayHasKey('ios', $result['Platforms']);
        $this->assertSame('iOS', $result['PlatformNames']['ios']);
        $this->assertSame('Outlook.com', $result['PlatformNames']['outlook-com']);
    }

    public function test_broken_html_does_not_throw(): void
    {
        $result = $this->service()->run('<div style="margin:0"><p>sin cerrar<table><tr><td>x');

        $this->assertIsArray($result['Warnings']);
        $this->assertGreaterThan(0, $result['Total']['Nodes']);
    }

    public function test_empty_html_yields_no_warnings(): void
    {
        $result = $this->service()->run('');

        $this->assertSame([], $result['Warnings']);
        $this->assertSame(100.0, round($result['Total']['Supported'], 5));
    }
}
