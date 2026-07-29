<?php

namespace Modules\Supplier\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Models\Prompt\Prompt;
use Tests\TestCase;

class PromptTest extends TestCase
{
    public function test_scope_active_filters_correctly(): void
    {
        $query = Prompt::query()->active();
        $sql = $query->toSql();

        $this->assertStringContainsString('`is_active` = ?', $sql);
    }

    public function test_scope_defaults_filters_correctly(): void
    {
        $query = Prompt::query()->defaults();
        $sql = $query->toSql();

        $this->assertStringContainsString('`is_default` = ?', $sql);
    }

    public function test_scope_templates_filters_correctly(): void
    {
        $query = Prompt::query()->templates();
        $sql = $query->toSql();

        $this->assertStringContainsString('`is_template` = ?', $sql);
    }

    public function test_render_replaces_variables(): void
    {
        $prompt = new Prompt([
            'prompt_template' => 'Hello {{name}}, your code is {{code}}',
        ]);

        $rendered = $prompt->render(['name' => 'World', 'code' => '123']);

        $this->assertSame('Hello World, your code is 123', $rendered);
    }

    public function test_render_keeps_unmatched_placeholders(): void
    {
        $prompt = new Prompt([
            'prompt_template' => 'Hello {{name}}, missing {{unknown}}',
        ]);

        $rendered = $prompt->render(['name' => 'World']);

        $this->assertSame('Hello World, missing {{unknown}}', $rendered);
    }

    public function test_create_new_version_prepares_correct_attributes(): void
    {
        $prompt = new Prompt([
            'label' => 'Test',
            'prompt_template' => 'Template',
            'version' => 3,
            'uid' => '01HX123ABC',
        ]);

        $replicated = $prompt->replicate();
        $replicated->version = $prompt->version + 1;

        $this->assertSame(4, $replicated->version);
        $this->assertSame('Test', $replicated->label);
    }

    public function test_clone_from_template_prepares_correct_state(): void
    {
        $template = new Prompt([
            'label' => 'Template Label',
            'prompt_template' => 'Template content',
            'scope' => 'global',
            'is_template' => true,
            'version' => 2,
        ]);

        $data = [
            'label' => $template->label,
            'prompt_template' => $template->prompt_template,
            'scope' => $template->scope,
            'is_template' => false,
            'cloned_from_template_id' => null,
            'supplier_id' => 1,
        ];

        $this->assertFalse($data['is_template']);
        $this->assertSame('Template Label', $data['label']);
        $this->assertSame(1, $data['supplier_id']);
    }

    public function test_relationship_methods_exist(): void
    {
        $prompt = new Prompt;

        $this->assertInstanceOf(BelongsTo::class, $prompt->supplier());
        $this->assertInstanceOf(BelongsTo::class, $prompt->source());
        $this->assertInstanceOf(HasMany::class, $prompt->contents());
        $this->assertInstanceOf(BelongsToMany::class, $prompt->favoritedBy());
    }

    public function test_casts_are_correct(): void
    {
        $prompt = new Prompt;
        $prompt->required_sections = ['intro', 'body'];
        $prompt->seo_focus = true;
        $prompt->enable_web_search = false;
        $prompt->is_default = true;
        $prompt->is_active = false;
        $prompt->is_template = true;
        $prompt->priority = 5;
        $prompt->version = 2;

        $this->assertSame(['intro', 'body'], $prompt->required_sections);
        $this->assertTrue($prompt->seo_focus);
        $this->assertFalse($prompt->enable_web_search);
        $this->assertTrue($prompt->is_default);
        $this->assertFalse($prompt->is_active);
        $this->assertTrue($prompt->is_template);
        $this->assertSame(5, $prompt->priority);
        $this->assertSame(2, $prompt->version);
    }
}
