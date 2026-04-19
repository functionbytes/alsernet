<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Template\Models\Shortcode;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shortcodes')) {
            return;
        }

        $model = $this->shortcodeModel();

        if (! $model) {
            return;
        }

        $entries = [
            [
                'key' => 'form',
                'name' => 'Formulario',
                'description' => 'Inserta un formulario publicado por ID o slug.',
                'icon' => 'fas fa-wpforms',
                'category' => 'formularios',
                'config_fields' => [
                    ['id' => 'id', 'label' => 'ID del formulario', 'type' => 'text', 'placeholder' => '1'],
                    ['id' => 'slug', 'label' => 'Slug del formulario', 'type' => 'text', 'placeholder' => 'contacto'],
                    ['id' => 'display', 'label' => 'Modo de visualización', 'type' => 'select', 'options' => ['inline' => 'Inline', 'popup' => 'Popup', 'slide_in' => 'Slide-in']],
                    ['id' => 'columns', 'label' => 'Columnas', 'type' => 'select', 'options' => ['1' => '1', '2' => '2', '3' => '3']],
                    ['id' => 'theme', 'label' => 'Tema', 'type' => 'select', 'options' => ['default' => 'Estándar', 'flat' => 'Plano', 'material' => 'Material', 'card' => 'Tarjetas', 'minimal' => 'Minimal', 'dark' => 'Oscuro', 'rounded' => 'Redondeado', 'bordered' => 'Con borde']],
                    ['id' => 'show_title', 'label' => 'Mostrar título', 'type' => 'select', 'options' => ['true' => 'Sí', 'false' => 'No']],
                    ['id' => 'lazy', 'label' => 'Lazy load', 'type' => 'select', 'options' => ['true' => 'Sí', 'false' => 'No']],
                ],
                'shortcode_template' => '[form id="{id}" display="{display}" columns="{columns}" theme="{theme}" show_title="{show_title}" lazy="{lazy}" /]',
                'render_template' => null,
                'sort_order' => 80,
                'is_active' => true,
            ],
            [
                'key' => 'form-link',
                'name' => 'Botón de formulario',
                'description' => 'Botón que abre un formulario en popup sin mostrarlo inline.',
                'icon' => 'fas fa-paper-plane',
                'category' => 'formularios',
                'config_fields' => [
                    ['id' => 'id', 'label' => 'ID del formulario', 'type' => 'text', 'placeholder' => '1'],
                    ['id' => 'text', 'label' => 'Texto del botón', 'type' => 'text', 'placeholder' => 'Contactar'],
                    ['id' => 'variant', 'label' => 'Estilo', 'type' => 'select', 'options' => ['primary' => 'Primary', 'secondary' => 'Secondary', 'success' => 'Success', 'danger' => 'Danger', 'outline-primary' => 'Outline primary', 'link' => 'Solo enlace']],
                    ['id' => 'size', 'label' => 'Tamaño', 'type' => 'select', 'options' => ['sm' => 'Pequeño', 'md' => 'Medio', 'lg' => 'Grande']],
                    ['id' => 'icon', 'label' => 'Icono (FA)', 'type' => 'text', 'placeholder' => 'fas fa-envelope'],
                ],
                'shortcode_template' => '[form-link id="{id}" text="{text}" variant="{variant}" size="{size}" icon="{icon}" /]',
                'render_template' => null,
                'sort_order' => 81,
                'is_active' => true,
            ],
        ];

        foreach ($entries as $data) {
            $model::updateOrCreate(
                ['key' => $data['key']],
                array_merge($data, [
                    'config_fields' => $data['config_fields'],
                ])
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shortcodes')) {
            return;
        }

        $model = $this->shortcodeModel();

        if (! $model) {
            return;
        }

        $model::query()->whereIn('key', ['form', 'form-link'])->delete();
    }

    private function shortcodeModel(): ?string
    {
        $class = Shortcode::class;

        return class_exists($class) ? $class : null;
    }
};
