<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Tests\TestCase;

class FormShortcodesTest extends TestCase
{
    public function test_form_shortcode_is_registered_with_typed_metadata(): void
    {
        $this->assertTrue(app()->bound('shortcode'), 'Shortcode compiler no está bound.');

        $meta = app('shortcode')->getRegistered();
        $form = collect($meta)->firstWhere('name', 'form');

        $this->assertNotNull($form, 'Shortcode [form] no está registrado.');
        $this->assertArrayHasKey('display', $form['attributes']);
        $this->assertIsArray($form['attributes']['display']);
        $this->assertSame('select', $form['attributes']['display']['type']);
        $this->assertArrayHasKey('inline', $form['attributes']['display']['options']);
    }

    public function test_form_link_shortcode_is_registered(): void
    {
        $meta = app('shortcode')->getRegistered();
        $link = collect($meta)->firstWhere('name', 'form-link');

        $this->assertNotNull($link);
        $this->assertSame('formularios', $link['category'] ?? null);
    }

    public function test_form_shortcode_returns_html_for_active_form(): void
    {
        $form = $this->createActiveForm(['name' => 'Contacto', 'slug' => 'contacto-test']);
        $this->createField($form, ['label' => 'Nombre', 'key' => 'nombre', 'type' => 'text', 'is_visible' => true]);

        $html = app('shortcode')->compile('[form id="'.$form->id.'" /]');

        $this->assertStringContainsString('forms-form', $html);
    }

    public function test_form_shortcode_resolves_by_slug(): void
    {
        $form = $this->createActiveForm(['slug' => 'mi-slug']);

        $html = app('shortcode')->compile('[form slug="mi-slug" /]');

        $this->assertNotEmpty($html);
    }

    public function test_form_shortcode_returns_empty_when_form_not_found(): void
    {
        $html = app('shortcode')->compile('[form id="99999" /]');

        $this->assertSame('', trim($html));
    }
}
