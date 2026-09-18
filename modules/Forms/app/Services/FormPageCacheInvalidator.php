<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Facades\Log;
use Modules\Forms\Jobs\InvalidateFormPagesCacheJob;
use Modules\Forms\Jobs\WarmPageCacheJob;
use Modules\Forms\Models\Form;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;

/**
 * Busca Pages que embeben un formulario via shortcode [form id=X]
 * o [form slug=Y] e invalida su cache cuando el formulario o sus
 * campos cambian.
 *
 * Dedupe por request: si se dispara múltiples veces (p.ej. al
 * guardar 50 campos a la vez), solo invalida una vez por formulario.
 *
 * Prefiere ejecución vía queue cuando está disponible.
 */
class FormPageCacheInvalidator
{
    /**
     * @var array<int,true> form_ids ya invalidados en este request
     */
    private array $invalidatedInRequest = [];

    public function invalidate(Form $form): void
    {
        if (isset($this->invalidatedInRequest[$form->id])) {
            return;
        }

        $this->invalidatedInRequest[$form->id] = true;

        if ($this->shouldQueue()) {
            InvalidateFormPagesCacheJob::dispatch($form->id, $form->slug);

            return;
        }

        $this->invalidateByIds($form->id, $form->slug);
    }

    public function invalidateByIds(int $formId, ?string $slug): void
    {
        if (! class_exists(Page::class)) {
            return;
        }

        try {
            $pages = Page::query()
                ->select(['id', 'slug'])
                ->where(function ($q) use ($formId, $slug) {
                    $q->where('content', 'like', '%[form id="'.$formId.'"%')
                        ->orWhere('content', 'like', "%[form id='".$formId."'%");

                    if ($slug) {
                        $q->orWhere('content', 'like', '%[form slug="'.$slug.'"%')
                            ->orWhere('content', 'like', "%[form slug='".$slug."'%")
                            ->orWhere('content', 'like', '%[form-link id="'.$formId.'"%')
                            ->orWhere('content', 'like', '%[form-link slug="'.$slug.'"%');
                    }
                })
                ->with(['translations:id,page_id,slug,content'])
                ->get();

            foreach ($pages as $page) {
                $this->forgetPage($page->slug);

                foreach ($page->translations ?? [] as $translation) {
                    if ($translation->slug) {
                        $this->forgetPage($translation->slug);
                    }
                }

                // Re-warming async del cache de la Page (sólo si está habilitado)
                if ($this->shouldAutoWarm()) {
                    WarmPageCacheJob::dispatch($page->id)
                        ->delay(now()->addSeconds(2));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('FormPageCacheInvalidator failed', [
                'form_id' => $formId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldQueue(): bool
    {
        return in_array(config('queue.default'), ['redis', 'database', 'sqs', 'beanstalkd'], true);
    }

    private function shouldAutoWarm(): bool
    {
        return (bool) config('forms.auto_warm_page_cache', false)
            && $this->shouldQueue()
            && class_exists(PageCacheService::class)
            && PageCacheService::isEnabled();
    }

    private function forgetPage(string $slug): void
    {
        if (! class_exists(PageCacheService::class)) {
            return;
        }

        PageCacheService::forgetAllLocales($slug);
    }
}
