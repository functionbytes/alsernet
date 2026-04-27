<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Tests\TestCase;

class SeoSchemaOrgTest extends TestCase
{
    public function test_edit_requires_permission(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.seo.schema-org.edit', $meta))
            ->assertForbidden();
    }

    public function test_edit_renders_view(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->get(route('settings.seo.schema-org.edit', $meta))
            ->assertOk()
            ->assertViewIs('Seo::settings.schema-org.edit')
            ->assertViewHas(['meta', 'types', 'templates']);
    }

    public function test_update_saves_valid_schema(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $schema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => 'Test',
        ]);

        $this->actingAs($user)
            ->put(route('settings.seo.schema-org.update', $meta), [
                'schema_type' => 'Article',
                'schema_custom' => $schema,
            ])
            ->assertRedirect();

        $meta->refresh();
        $this->assertEquals('Article', $meta->schema_type);
        $this->assertEquals('Test', $meta->schema_custom['headline']);
    }

    public function test_update_rejects_invalid_json(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->put(route('settings.seo.schema-org.update', $meta), [
                'schema_type' => 'Article',
                'schema_custom' => '{broken json',
            ])
            ->assertSessionHasErrors('schema_custom');
    }

    public function test_update_rejects_schema_without_context(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->put(route('settings.seo.schema-org.update', $meta), [
                'schema_custom' => json_encode(['@type' => 'Article', 'headline' => 'x']),
            ])
            ->assertSessionHasErrors('schema_custom');
    }

    public function test_update_rejects_schema_without_type(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->put(route('settings.seo.schema-org.update', $meta), [
                'schema_custom' => json_encode(['@context' => 'https://schema.org', 'name' => 'x']),
            ])
            ->assertSessionHasErrors('schema_custom');
    }

    public function test_update_rejects_unsupported_schema_type(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->put(route('settings.seo.schema-org.update', $meta), [
                'schema_type' => 'Book',
                'schema_custom' => json_encode(['@context' => 'https://schema.org', '@type' => 'Book']),
            ])
            ->assertSessionHasErrors('schema_type');
    }

    public function test_template_endpoint_returns_supported_schema(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->getJson(route('settings.seo.schema-org.template', 'Article'))
            ->assertOk()
            ->assertJsonStructure(['type', 'template']);
    }

    public function test_validate_endpoint_reports_valid_schema(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $schema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => 'x',
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.seo.schema-org.validate', $meta), ['schema_custom' => $schema])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_validate_endpoint_reports_invalid_schema(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->postJson(route('settings.seo.schema-org.validate', $meta), ['schema_custom' => '{not json'])
            ->assertOk()
            ->assertJson(['valid' => false]);
    }
}
