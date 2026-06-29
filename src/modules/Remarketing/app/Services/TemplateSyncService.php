<?php

namespace Modules\Remarketing\Services;

use Illuminate\Support\Str;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Modules\Remarketing\Models\Template;

class TemplateSyncService
{
    /**
     * Push a Remarketing template's HTML content to a linked Mailer template.
     * Creates the MailerTemplate if none is linked yet.
     */
    public function push(Template $template, ?int $langId = null): MailerTemplate
    {
        $mailerTemplate = $template->mailer_template_id
            ? MailerTemplate::query()->find($template->mailer_template_id)
            : null;

        if ($mailerTemplate === null) {
            $mailerTemplate = MailerTemplate::query()->create([
                'uid' => Str::uuid()->toString(),
                'key' => 'remarketing_'.$template->id,
                'name' => $template->name,
                'module' => 'remarketing',
                'is_enabled' => true,
                'is_protected' => false,
            ]);

            $template->update(['mailer_template_id' => $mailerTemplate->id]);
        }

        $resolvedLangId = $langId ?? $this->defaultLangId();

        MailerTemplateLang::query()->updateOrCreate(
            ['mailer_template_id' => $mailerTemplate->id, 'lang_id' => $resolvedLangId],
            [
                'uid' => Str::uuid()->toString(),
                'subject' => $template->subject ?? '',
                'preheader' => $template->preheader ?? '',
                'content' => $template->html_content ?? '',
            ]
        );

        return $mailerTemplate->fresh();
    }

    /**
     * Pull Mailer template content back into the Remarketing template (for the given lang).
     */
    public function pull(Template $template, ?int $langId = null): void
    {
        if (! $template->mailer_template_id) {
            return;
        }

        $mailerTemplate = MailerTemplate::query()->find($template->mailer_template_id);

        if ($mailerTemplate === null) {
            return;
        }

        $translation = $mailerTemplate->translate($langId);

        if ($translation === null) {
            return;
        }

        $template->update([
            'subject' => $translation->subject ?: $template->subject,
            'preheader' => $translation->preheader ?: $template->preheader,
            'html_content' => $translation->content ?: $template->html_content,
        ]);
    }

    private function defaultLangId(): ?int
    {
        return \DB::table('mailer_langs')->where('is_default', true)->value('id');
    }
}
