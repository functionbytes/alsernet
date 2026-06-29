<?php

namespace Modules\Campaign\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Campaign\Library\Contracts\TemplateSubjectInterface;
use Modules\Campaign\Models\Template\Template;

/**
 * TemplateService — opera de forma genérica sobre un "subject" (PageTemplate)
 * y su Template asociado 1:1, garantizando que nunca queden Templates huérfanos.
 *
 * Portado de acellemail (App\Services\TemplateService), adaptado al modelo
 * Template del módulo: las copias usan Template::createBuilderTemplate (que
 * preserva json/content/theme/source) en vez del copy() del módulo, que asume
 * columnas inexistentes (is_private) y un generateUid no disponible aquí.
 */
class TemplateService
{
    private Model $subject;

    private function __construct(TemplateSubjectInterface $subject)
    {
        if (! $subject instanceof Model) {
            throw new InvalidArgumentException('TemplateService subjects must be Eloquent models');
        }

        $relation = $subject->template();

        if (! $relation instanceof BelongsTo) {
            throw new InvalidArgumentException('TemplateService requires a belongsTo template() relation');
        }

        if ($relation->getForeignKeyName() !== 'template_id') {
            throw new InvalidArgumentException('TemplateService requires template() to use template_id as the foreign key');
        }

        $this->subject = $subject;
    }

    public static function for(TemplateSubjectInterface $subject): self
    {
        return new self($subject);
    }

    /**
     * Clona $template (preservando json/content/theme), lo asocia al subject y
     * limpia el template anterior si lo hubiera.
     */
    public function setTemplate(Template $template, string $name): Template
    {
        $this->propagateNameToSubject($name);

        $copy = Template::createBuilderTemplate(
            $template->theme ?: 'default',
            $name,
            $template->json ?: Template::DEFAULT_BUILDER_JSON,
            (string) ($template->content ?? ''),
        );

        // Copiar assets (thumbnails/imágenes) del template origen al nuevo.
        $srcDir = $template->getStoragePath();
        if (File::isDirectory($srcDir)) {
            File::copyDirectory($srcDir, $copy->getStoragePath());
        }

        return $this->replaceWithTemplate($copy);
    }

    public function replaceWithTemplate(Template $template): Template
    {
        $currentTemplate = $this->subject->template;

        $this->subject->template()->associate($template);
        $this->subject->save();
        $this->subject->refresh();

        if ($currentTemplate && ! $currentTemplate->is($template)) {
            $currentTemplate->deleteAndCleanup();
        }

        return $this->subject->template;
    }

    public function setCustomHtml(string $html, string $name): Template
    {
        $this->propagateNameToSubject($name);

        $template = Template::makeUploadedBuilderTemplate($name, $html);

        return $this->replaceWithTemplate($template);
    }

    public function removeTemplate(): void
    {
        $currentTemplate = $this->subject->template;

        if (! $currentTemplate) {
            return;
        }

        $this->subject->template()->dissociate();
        $this->subject->save();
        $this->subject->unsetRelation('template');

        $currentTemplate->deleteAndCleanup();
        $this->subject->refresh();
    }

    public function deleteSubjectAndTemplate(): void
    {
        $currentTemplate = $this->subject->template;

        if ($currentTemplate) {
            $currentTemplate->deleteAndCleanup();
        }

        if ($this->subject->getKey() && $this->subject->newQuery()->whereKey($this->subject->getKey())->exists()) {
            $this->subject->delete();
        }
    }

    /**
     * Escribe $name a la columna `name` del subject si la tiene.
     */
    private function propagateNameToSubject(string $name): void
    {
        static $cache = [];
        $class = get_class($this->subject);
        $cache[$class] ??= Schema::hasColumn($this->subject->getTable(), 'name');
        if ($cache[$class]) {
            $this->subject->setAttribute('name', $name);
        }
    }
}
