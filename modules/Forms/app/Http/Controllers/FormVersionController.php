<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormVersion;

class FormVersionController extends Controller
{
    public function index(Form $form): View
    {
        $this->authorize('Forms.forms.edit');

        $versions = FormVersion::query()
            ->where('form_id', $form->id)
            ->with('creator')
            ->orderByDesc('version_number')
            ->get();

        return view('forms::settings.forms.versions.index', compact('form', 'versions'));
    }

    public function restore(Form $form, FormVersion $version): RedirectResponse
    {
        $this->authorize('Forms.forms.edit');

        DB::transaction(function () use ($form, $version) {
            // Save current state as a new version before restoring
            $lastNumber = FormVersion::query()
                ->where('form_id', $form->id)
                ->max('version_number') ?? 0;

            FormVersion::query()->create([
                'form_id' => $form->id,
                'version_number' => $lastNumber + 1,
                'form_snapshot' => $form->toArray(),
                'fields_snapshot' => $form->fields()->ordered()->get()->toArray(),
                'created_by' => auth()->id(),
            ]);

            // Delete all current fields and recreate from snapshot
            $form->fields()->delete();

            foreach ($version->fields_snapshot ?? [] as $fieldData) {
                $form->fields()->create(
                    collect($fieldData)->except(['id', 'form_id', 'created_at', 'updated_at'])->all()
                );
            }
        });

        session()->flash('success', "Formulario restaurado a la versión {$version->version_number} correctamente.");

        return redirect()->route('settings.forms.edit', $form);
    }
}
