<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\CopyPageTemplateDto;
use Modules\Campaign\Dto\PageTemplate\CreatePageTemplateDto;
use Modules\Campaign\Dto\PageTemplate\DeletePageTemplatesDto;
use Modules\Campaign\Dto\PageTemplate\RenamePageTemplateDto;
use Modules\Campaign\Dto\PageTemplate\SaveContentDto;
use Modules\Campaign\Models\Template\SystemEmailTemplate;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;

/**
 * SystemEmailTemplateService — CRUD del wrapper SystemEmailTemplate. Espejo de
 * PageTemplateService (reutiliza los mismos DTOs y TemplateService genérico).
 */
class SystemEmailTemplateService
{
    public function createFromBase(CreatePageTemplateDto $dto): ?SystemEmailTemplate
    {
        $base = SystemEmailTemplate::findByUid($dto->baseTemplateUid);
        if (! $base || ! $base->template) {
            return null;
        }

        return $this->cloneFromTemplate($dto->name, $base->template);
    }

    public function copyFrom(CopyPageTemplateDto $dto): ?SystemEmailTemplate
    {
        $source = SystemEmailTemplate::findByUid($dto->sourceUid);
        if (! $source || ! $source->template) {
            return null;
        }

        return $this->cloneFromTemplate($dto->newName, $source->template);
    }

    public function rename(RenamePageTemplateDto $dto): bool
    {
        $t = SystemEmailTemplate::findByUid($dto->uid);
        if (! $t) {
            return false;
        }

        $t->name = $dto->newName;
        $t->save();

        return true;
    }

    public function bulkDelete(DeletePageTemplatesDto $dto): int
    {
        if (empty($dto->uids)) {
            return 0;
        }

        $deleted = 0;
        foreach (SystemEmailTemplate::whereIn('uid', $dto->uids)->get() as $t) {
            if (Gate::denies('delete', $t)) {
                continue;
            }
            TemplateService::for($t)->deleteSubjectAndTemplate();
            $deleted++;
        }

        return $deleted;
    }

    public function saveContent(SaveContentDto $dto): array
    {
        $t = SystemEmailTemplate::findByUid($dto->uid);
        if (! $t || ! $t->template) {
            return ['ok' => false, 'errors' => ['uid' => ['Email template not found.']]];
        }

        $validator = $t->template->updateBuilderContent($dto->json, $dto->content);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }

        return ['ok' => true, 'errors' => []];
    }

    private function cloneFromTemplate(string $name, Template $source): SystemEmailTemplate
    {
        $new = new SystemEmailTemplate;
        $new->name = $name;

        $newTemplate = TemplateService::for($new)->setTemplate($source, $name);

        $extended = TemplateCategory::whereName('Extended')->first();
        if ($extended && $newTemplate) {
            $base = TemplateCategory::whereName('Base')->first();
            if ($base) {
                $newTemplate->categories()->detach($base->id);
            }
            if (! $newTemplate->categories()->where('campaign_template_categories.id', $extended->id)->exists()) {
                $newTemplate->categories()->attach($extended->id);
            }
        }

        return $new;
    }

    public function seedBaseTemplate(string $name, Template $template, string $categoryName = 'Extended'): SystemEmailTemplate
    {
        $t = new SystemEmailTemplate;
        $t->name = $name;

        $newTemplate = TemplateService::for($t)->setTemplate($template, $name);

        $category = TemplateCategory::whereName($categoryName)->first();
        if ($category && $newTemplate) {
            if (! $newTemplate->categories()->where('campaign_template_categories.id', $category->id)->exists()) {
                $newTemplate->categories()->attach($category->id);
            }
        }

        return $t;
    }
}
