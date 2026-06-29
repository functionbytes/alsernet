<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Str;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormVersion;

/**
 * Lógica de negocio del Form que antes vivía en el controller.
 * Mantener el controller thin y testear aquí.
 */
class FormService
{
    public function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Form::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public function createInitialVersion(Form $form): FormVersion
    {
        $lastVersion = FormVersion::query()
            ->where('form_id', $form->id)
            ->max('version_number') ?? 0;

        return FormVersion::query()->create([
            'form_id' => $form->id,
            'version_number' => $lastVersion + 1,
            'form_snapshot' => $form->toArray(),
            'fields_snapshot' => $form->fields()->ordered()->get()->toArray(),
            'created_by' => auth()->id(),
        ]);
    }

    public function cloneForm(Form $source): Form
    {
        return \DB::transaction(function () use ($source): Form {
            $newForm = $source->replicate(['slug']);
            $newForm->name = 'Copia de '.$source->name;
            $newForm->slug = $this->generateUniqueSlug($newForm->name);
            $newForm->save();

            $source->fields()->ordered()->get()->each(function (FormField $field) use ($newForm) {
                $clonedField = $field->replicate(['form_id']);
                $clonedField->form_id = $newForm->id;
                $clonedField->save();
            });

            $this->createInitialVersion($newForm);

            return $newForm;
        });
    }

    /**
     * Limpia el HTML de una vista estructural para mostrar en el editor de código.
     */
    public function cleanStructureHtml(string $html): string
    {
        $html = preg_replace_callback('/<[^>]+>/s', function ($m) {
            $tag = preg_replace('/\s+/', ' ', $m[0]);

            return str_replace(' >', '>', $tag);
        }, $html);

        $html = preg_replace('/\s+id="[^"]*"/', '', $html);
        $html = preg_replace('/\s+for="[^"]*"/', '', $html);
        $html = preg_replace('/\s+value=""/', '', $html);
        $html = preg_replace('/\s+placeholder=""/', '', $html);
        $html = preg_replace('/\s+data-auto-populate="[^"]*"/', '', $html);
        $html = preg_replace('/\s+data-conditions="[^"]*"/', '', $html);

        $html = preg_replace('/<div class="invalid-feedback"><\/div>\s*/', '', $html);
        $html = preg_replace('/(\s*\n){3,}/', "\n\n", $html);

        return trim($html);
    }
}
