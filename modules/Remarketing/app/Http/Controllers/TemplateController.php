<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Modules\Mailer\Models\MailerTemplateVersion;
use Modules\Mailer\Models\MailerVariable;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Remarketing\Http\Requests\Web\StoreTemplateRequest;
use Modules\Remarketing\Http\Requests\Web\UpdateTemplateRequest;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Template;

class TemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Template::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $templates = Template::query()
            ->with('store')
            ->where(function ($q) use ($storeIds, $user) {
                $q->whereIn('store_id', $storeIds)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('visibility', 'user')
                            ->whereHas('store', fn ($s) => $s->where('user_id', $user->id));
                    })
                    ->orWhere('visibility', 'global');
            })
            ->latest()
            ->paginate(20);

        return view('remarketing::templates.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('create', Template::class);

        $stores = $this->getUserStores();
        $layouts = $this->getMailerLayouts();
        $mailerVariables = $this->getMailerVariables();
        $langs = $this->getAvailableLangs();

        return view('remarketing::templates.form', compact('stores', 'layouts', 'mailerVariables', 'langs'));
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        $template = Template::query()->create($data);

        $this->syncToMailer($template, $translations);

        return redirect()->route('remarketing.templates.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function edit(Template $template): View
    {
        $this->authorize('update', $template);

        $stores = $this->getUserStores();
        $layouts = $this->getMailerLayouts();
        $mailerVariables = $this->getMailerVariables();
        $langs = $this->getAvailableLangs();
        $templateTranslations = $this->getTemplateTranslations($template);
        $templateVersions = $this->getTemplateVersions($template);

        return view('remarketing::templates.form', compact(
            'template', 'stores', 'layouts', 'mailerVariables',
            'langs', 'templateTranslations', 'templateVersions'
        ));
    }

    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $data = $request->validated();
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        $template->update($data);

        $this->syncToMailer($template, $translations);

        return redirect()->route('remarketing.templates.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('remarketing.templates.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }

    public function preview(Template $template): View
    {
        $this->authorize('view', $template);

        $langId = request()->input('lang_id');
        $html = $template->html_content ?? '';

        if ($template->mailer_template_id) {
            try {
                $html = MailerTemplateRendererService::renderEmailTemplate(
                    $template->mailerTemplate,
                    $this->preparePreviewVariables(),
                    $langId ? (int) $langId : null
                );
            } catch (\Throwable $e) {
                Log::warning('Template preview render failed', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('remarketing::templates.preview', compact('template', 'html'));
    }

    private function preparePreviewVariables(): array
    {
        return [
            'CUSTOMER_NAME' => 'Juan',
            'CUSTOMER_LASTNAME' => 'García',
            'CUSTOMER_EMAIL' => 'juan@example.com',
            'UNSUBSCRIBE_URL' => url('/r/unsubscribe/demo'),
            'STORE_NAME' => 'Tienda Demo',
            'STORE_DOMAIN' => 'demo.example.com',
            '{{firstName}}' => 'Juan',
            '{{lastName}}' => 'García',
            '{{email}}' => 'juan@example.com',
            '{{unsubscribeUrl}}' => url('/r/unsubscribe/demo'),
            '{{UNSUBSCRIBE_URL}}' => url('/r/unsubscribe/demo'),
        ];
    }

    private function getUserStores(): Collection
    {
        $user = auth()->user();

        return Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->get(['id', 'name']);
    }

    private function getMailerLayouts(): Collection
    {
        return MailerLayout::query()
            ->where('is_enabled', true)
            ->orderBy('alias')
            ->get(['id', 'alias', 'group_name']);
    }

    private function getMailerVariables(): Collection
    {
        return MailerVariable::query()
            ->where('is_enabled', true)
            ->where(function ($q) {
                $q->where('module', 'remarketing')
                    ->orWhere('module', 'core');
            })
            ->orderBy('category')
            ->orderBy('name')
            ->get(['name', 'description', 'category']);
    }

    /**
     * Sincronizar el template de Remarketing con Mailer.
     * Crea o actualiza el MailerTemplate, sus traducciones y versiones.
     */
    private function syncToMailer(Template $template, array $extraTranslations = []): void
    {
        $defaultLangId = MailerLang::resolveDefaultId();

        $mailerTemplate = MailerTemplate::updateOrCreate(
            ['id' => $template->mailer_template_id],
            [
                'key' => 'remarketing.'.$template->id,
                'name' => $template->name,
                'module' => 'remarketing',
                'is_enabled' => true,
                'layout_id' => $template->layout_id,
            ]
        );

        $syncedLangIds = [];

        // Default translation from the template itself
        $defaultTranslation = MailerTemplateLang::updateOrCreate(
            [
                'mailer_template_id' => $mailerTemplate->id,
                'lang_id' => $defaultLangId,
            ],
            [
                'subject' => $template->subject,
                'preheader' => $template->preheader,
                'content' => $template->html_content,
            ]
        );
        $syncedLangIds[] = $defaultLangId;
        $this->createVersionIfChanged($mailerTemplate, $defaultTranslation, $defaultLangId);

        // Extra translations from the form
        foreach ($extraTranslations as $t) {
            $langId = (int) $t['lang_id'];
            if ($langId === $defaultLangId) {
                continue;
            }

            $translation = MailerTemplateLang::updateOrCreate(
                [
                    'mailer_template_id' => $mailerTemplate->id,
                    'lang_id' => $langId,
                ],
                [
                    'subject' => $t['subject'] ?? null,
                    'preheader' => $t['preheader'] ?? null,
                    'content' => $t['content'] ?? null,
                ]
            );
            $syncedLangIds[] = $langId;
            $this->createVersionIfChanged($mailerTemplate, $translation, $langId);
        }

        if ($template->mailer_template_id !== $mailerTemplate->id) {
            $template->update(['mailer_template_id' => $mailerTemplate->id]);
        }
    }

    /**
     * Create a version entry if the translation content changed.
     */
    private function createVersionIfChanged(MailerTemplate $mailerTemplate, MailerTemplateLang $translation, int $langId): void
    {
        if (! $translation->wasChanged(['subject', 'preheader', 'content'])) {
            return;
        }

        try {
            MailerTemplateVersion::create([
                'mailer_template_id' => $mailerTemplate->id,
                'lang_id' => $langId,
                'created_by' => auth()->id(),
                'subject' => $translation->subject,
                'content' => $translation->content,
                'change_note' => 'Actualizado desde Remarketing',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create template version', [
                'mailer_template_id' => $mailerTemplate->id,
                'lang_id' => $langId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getAvailableLangs(): Collection
    {
        $defaultId = MailerLang::resolveDefaultId();

        return MailerLang::query()
            ->where('id', '!=', $defaultId)
            ->where('available', 1)
            ->orderBy('title')
            ->get(['id', 'title', 'iso_code']);
    }

    private function getTemplateTranslations(Template $template): Collection
    {
        if (! $template->mailer_template_id) {
            return collect();
        }

        return MailerTemplateLang::query()
            ->where('mailer_template_id', $template->mailer_template_id)
            ->where('lang_id', '!=', MailerLang::resolveDefaultId())
            ->get();
    }

    private function getTemplateVersions(Template $template): Collection
    {
        if (! $template->mailer_template_id) {
            return collect();
        }

        return MailerTemplateVersion::query()
            ->where('mailer_template_id', $template->mailer_template_id)
            ->with('lang')
            ->latest()
            ->limit(20)
            ->get();
    }
}
