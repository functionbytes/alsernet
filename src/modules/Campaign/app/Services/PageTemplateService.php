<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\CopyPageTemplateDto;
use Modules\Campaign\Dto\PageTemplate\CreatePageTemplateDto;
use Modules\Campaign\Dto\PageTemplate\DeletePageTemplatesDto;
use Modules\Campaign\Dto\PageTemplate\RenamePageTemplateDto;
use Modules\Campaign\Dto\PageTemplate\SaveContentDto;
use Modules\Campaign\Models\Template\PageTemplate;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;

/**
 * PageTemplateService — lógica de negocio del CRUD del wrapper PageTemplate.
 * Portado de acellemail, adaptado al módulo:
 *  - tabla de categorías: campaign_template_categories
 *  - guardado de contenido: Template::updateBuilderContent($json, $content)
 *  - cleanup en cascada vía TemplateService::for() (invariante wrapper↔Template 1:1)
 */
class PageTemplateService
{
    /** Crea un PageTemplate clonando el Template de un PageTemplate base. */
    public function createFromBase(CreatePageTemplateDto $dto): ?PageTemplate
    {
        $base = PageTemplate::findByUid($dto->baseTemplateUid);
        if (! $base || ! $base->template) {
            return null;
        }

        return $this->cloneFromTemplate($dto->name, $base->template);
    }

    /** Copia un PageTemplate existente a otro con nuevo nombre. */
    public function copyFrom(CopyPageTemplateDto $dto): ?PageTemplate
    {
        $source = PageTemplate::findByUid($dto->sourceUid);
        if (! $source || ! $source->template) {
            return null;
        }

        return $this->cloneFromTemplate($dto->newName, $source->template);
    }

    /** Renombra el wrapper (el Template subyacente conserva su título interno). */
    public function rename(RenamePageTemplateDto $dto): bool
    {
        $pt = PageTemplate::findByUid($dto->uid);
        if (! $pt) {
            return false;
        }

        $pt->name = $dto->newName;
        $pt->save();

        return true;
    }

    /** Borrado masivo por UIDs con re-chequeo de autorización por item. */
    public function bulkDelete(DeletePageTemplatesDto $dto): int
    {
        if (empty($dto->uids)) {
            return 0;
        }

        $deleted = 0;
        foreach (PageTemplate::whereIn('uid', $dto->uids)->get() as $pt) {
            if (Gate::denies('delete', $pt)) {
                continue;
            }
            TemplateService::for($pt)->deleteSubjectAndTemplate();
            $deleted++;
        }

        return $deleted;
    }

    /** Guarda el contenido round-trip del builder (json + html) en el Template. */
    public function saveContent(SaveContentDto $dto): array
    {
        $pt = PageTemplate::findByUid($dto->uid);
        if (! $pt || ! $pt->template) {
            return ['ok' => false, 'errors' => ['uid' => ['Page template not found.']]];
        }

        $validator = $pt->template->updateBuilderContent($dto->json, $dto->content);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }

        return ['ok' => true, 'errors' => []];
    }

    /**
     * Crea un PageTemplate clonado de un Template existente y lo etiqueta Extended
     * (los derivados curados por admin siempre son Extended; Base se reserva a las
     * semillas de TemplateSeeder).
     */
    private function cloneFromTemplate(string $name, Template $source): PageTemplate
    {
        $new = new PageTemplate;
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

    /**
     * Factory usado por el seeder para registrar PageTemplates Base shipped.
     * El seeder aporta el Template ya creado; estas entradas se clasifican Extended
     * (las muestras de commerce son ricas, no base-tier) salvo override.
     */
    public function seedBaseTemplate(string $name, Template $template, string $categoryName = 'Extended'): PageTemplate
    {
        $pt = new PageTemplate;
        $pt->name = $name;

        $newTemplate = TemplateService::for($pt)->setTemplate($template, $name);

        $category = TemplateCategory::whereName($categoryName)->first();
        if ($category && $newTemplate) {
            if (! $newTemplate->categories()->where('campaign_template_categories.id', $category->id)->exists()) {
                $newTemplate->categories()->attach($category->id);
            }
        }

        return $pt;
    }
}
