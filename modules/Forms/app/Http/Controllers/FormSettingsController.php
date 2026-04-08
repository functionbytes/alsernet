<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

    public function update(Request $request, Form $form): RedirectResponse
    {
        $this->authorize('Forms.settings.manage');

        $data = $request->validate([
            'success_message' => ['nullable', 'string'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'submit_button_text' => ['nullable', 'string', 'max:100'],
            'allow_multiple' => ['boolean'],
            'max_submissions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_multi_step' => ['boolean'],
            'floating_label' => ['boolean'],
        ]);

        $form->update($data);

        session()->flash('success', 'Configuración general guardada.');

        return redirect()->route('settings.forms.settings.edit', $form);
    }

    public function emails(Form $form): View
    {
        $this->authorize('Forms.settings.manage');

        $form->load(['followUps', 'conditionalEmails']);

        $mailerTemplates = MailerTemplate::query()->orderBy('name')->get(['id', 'name']);

        return view('forms::settings.emails', compact('form', 'mailerTemplates'));
    }

    public function updateEmails(Request $request, Form $form): RedirectResponse
    {
        $this->authorize('Forms.settings.manage');

        $data = $request->validate([
            'admin_notification_email' => ['nullable', 'string', 'max:500'],
            'send_confirmation' => ['boolean'],
            'email_field_key' => ['nullable', 'string', 'max:100'],
            'confirmation_subject' => ['nullable', 'string', 'max:255'],
            'confirmation_message' => ['nullable', 'string'],
            'honeypot_enabled' => ['boolean'],
            'captcha_enabled' => ['boolean'],
            'admin_template_id' => ['nullable', 'integer'],
            'confirmation_template_id' => ['nullable', 'integer'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'follow_up' => ['nullable', 'array'],
            'follow_up.name' => ['required_with:follow_up', 'string', 'max:100'],
            'follow_up.send_after_days' => ['required_with:follow_up', 'integer', 'min:1'],
            'follow_up.email_template_id' => ['required_with:follow_up', 'integer'],
            'follow_up.recipient_type' => ['required_with:follow_up', 'in:submitter,admin'],
            'follow_up.is_active' => ['boolean'],
            'conditional_email' => ['nullable', 'array'],
            'conditional_email.condition_field_key' => ['required_with:conditional_email', 'string', 'max:100'],
            'conditional_email.condition_operator' => ['required_with:conditional_email', 'in:equals,not_equals,contains,starts_with,ends_with'],
            'conditional_email.condition_value' => ['required_with:conditional_email', 'string'],
            'conditional_email.admin_template_id' => ['nullable', 'integer'],
            'conditional_email.client_template_id' => ['nullable', 'integer'],
            'conditional_email.is_active' => ['boolean'],
        ]);

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

    public function updateAccess(Request $request, Form $form): RedirectResponse
    {
        $this->authorize('Forms.settings.manage');

        $data = $request->validate([
            'access_control' => ['required', 'in:public,authenticated,roles'],
            'allowed_roles' => ['nullable', 'array'],
            'allowed_roles.*' => ['string'],
            'is_password_protected' => ['boolean'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'limit_per_user' => ['boolean'],
            'prevent_duplicate_email' => ['boolean'],
        ]);

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

    public function updateGdpr(Request $request, Form $form): RedirectResponse
    {
        $this->authorize('Forms.settings.manage');

        $data = $request->validate([
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $form->update($data);

        if ($request->boolean('anonymize_old')) {
            $cutoff = now()->subDays($data['retention_days'] ?? 365);

            $form->submissions()
                ->where('created_at', '<', $cutoff)
                ->chunk(100, function ($submissions) {
                    $submissions->each(fn ($s) => $s->update(['is_anonymized' => true]));
                });
        }

        session()->flash('success', 'Configuración GDPR guardada.');

        return redirect()->route('settings.forms.settings.gdpr', $form);
    }
}
