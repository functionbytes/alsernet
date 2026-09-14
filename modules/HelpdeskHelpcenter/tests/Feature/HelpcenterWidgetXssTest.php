<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;
use Tests\TestCase;

/**
 * Regresión de seguridad (auditoría): el cuerpo del artículo que el widget
 * expone se renderiza con dangerouslySetInnerHTML en el bundle React, así que
 * DEBE ir saneado (HTMLPurifier via clean()). Sin ello, un editor podía
 * inyectar XSS almacenado en cualquier sitio que embeba el widget.
 */
class HelpcenterWidgetXssTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_widget_article_body_is_sanitized(): void
    {
        $article = HelpCenterArticle::factory()->published()->create([
            'active' => true,
            'content' => '<p>Contenido legitimo</p><script>alert(document.cookie)</script><img src=x onerror=alert(1)>',
        ]);

        $data = app(HelpcenterWidgetService::class)->getArticle($article->id);

        $this->assertNotNull($data);
        $this->assertStringNotContainsString('<script', $data['body'], 'El widget no debe exponer <script>.');
        $this->assertStringNotContainsString('onerror', $data['body'], 'El widget no debe exponer handlers onerror.');
        $this->assertStringContainsString('Contenido legitimo', $data['body'], 'El contenido legítimo debe conservarse.');
    }
}
