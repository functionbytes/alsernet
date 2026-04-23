<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\UpdateFormAccessRequest;
use Modules\Forms\Http\Requests\UpdateFormEmailsRequest;
use Modules\Forms\Http\Requests\UpdateFormGdprRequest;
use Modules\Forms\Http\Requests\UpdateFormSeoRequest;
use Modules\Forms\Http\Requests\UpdateFormSettingsRequest;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormCategory;
use Modules\Forms\Models\FormConditionalEmail;
use Modules\Forms\Models\FormFollowUp;
use Modules\Mailer\Models\MailerTemplate;
use Spatie\Permission\Models\Role;

class FormSettingsController extends Controller
{
    public function edit(Form $form): View
    {
        $this->authorize('Forms.settings.manage');

        $categories = FormCategory::query()->active()->ordered()->get();

        return view('forms::settings.general', compact('form', 'categories'));
    }

    public function update(UpdateFormSettingsRequest $request, Form $form): RedirectResponse
    {
        $form->update($request->validated());

        session()->flash('success', 'Configuración general guardada.');

        return redirect()->route('settings.forms.settings.edit', $form);
    }

    public function seo(Form $form): View
    {
        $this->authorize('Forms.settings.manage');

        $form->load('seoMeta');

        return view('forms::settings.seo', compact('form'));
    }

    public function updateSeo(UpdateFormSeoRequest $request, Form $form): RedirectResponse
    {
        $form->updateSeoMeta($request->validated());

        session()->flash('success', __('forms::messages.settings.seo_saved'));

        return redirect()->route('settings.forms.settings.seo', $form);
    }

    public function emails(Form $form): View
    {
        $this->authorize('Forms.settings.manage');

        $form->load(['followUps', 'conditionalEmails']);

        $mailerTemplates = MailerTemplate::query()->orderBy('name')->get(['id', 'name']);

        return view('forms::settings.emails', compact('form', 'mailerTemplates'));
    }

    public function updateEmails(UpdateFormEmailsRequest $request, Form $form): RedirectResponse
    {
        $data = $request->validated();

        $formData = collect($data)->only([
            'admin_notification_email', 'send_confirmation', 'email_field_key',
            'admin_template_id', 'confirmation_template_id',
            'confirmation_subject', 'confirmation_message', 'honeypot_enabled',
            'captcha_enabled', 'webhook_url', 'webhook_secret',
        ])->all();

        $form->update($formData);

        if (! empty($data['follow_up'])) {
            FormFollowUp::query()->updateOrCreate(
                ['form_id' => $form->id],
                array_merge($data['follow_up'], ['form_id' => $form->id])
            );
        }

        if (! empty($data['conditional_email'])) {
            FormConditionalEmail::query()->updateOrCreate(
                ['form_id' => $form->id],
                array_merge($data['conditional_email'], ['form_id' => $form->id])
            );
        }

        session()->flash('success', 'Configuración de emails guardada.');

        return redirect()->route('settings.forms.settings.emails', $form);
    }

    public function access(Form $form): View
    {
        $this->authorize('Forms.settings.manage');

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return view('forms::settings.access', compact('form', 'roles'));
    }

    public function updateAccess(UpdateFormAccessRequest $request, Form $form): RedirectResponse
    {
        $data = $request->validated();

        if (! $data['is_password_protected']) {
            $data['password'] = null;
        } elseif (empty($data['password'])) {
            unset($data['password']); // Mantener la contraseña existente si el campo se dejó vacío
        }

        $form->update($data);

        session()->flash('success', 'Control de acceso guardado.');

        return redirect()->route('settings.forms.settings.access', $form);
    }

    public function gdpr(Form $form): View
    {
        $this->authorize('Forms.settings.manage');

        return view('forms::settings.gdpr', compact('form'));
    }

    public function updateGdpr(UpdateFormGdprRequest $request, Form $form): RedirectResponse
    {
        $data = $request->validated();
        $form->update(['retention_days' => $data['retention_days'] ?? null]);

        if ($request->boolean('anonymize_old')) {
            $cutoff = now()->subDays($data['retention_days'] ?? 365);

            $form->submissions()
                ->where('created_at', '<', $cutoff)
                ->update(['is_anonymized' => true]);
        }

        session()->flash('success', 'Configuración GDPR guardada.');

        return redirect()->route('settings.forms.settings.gdpr', $form);
    }
}
