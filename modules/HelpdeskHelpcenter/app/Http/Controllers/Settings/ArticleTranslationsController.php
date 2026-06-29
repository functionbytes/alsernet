<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\HelpdeskHelpcenter\Http\Requests\Settings\StoreTranslationRequest;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleTranslation;

class ArticleTranslationsController extends Controller
{
    public function index(HelpCenterArticle $article): View
    {
        $this->authorize('translate', $article);

        $article->load('translations');

        $translations = $article->translations->keyBy('locale');

        return view('helpdeskhelpcenter::settings.translations.index', compact('article', 'translations'));
    }

    public function edit(HelpCenterArticle $article, string $locale): View
    {
        $this->authorize('translate', $article);

        $translation = $article->translations()->where('locale', $locale)->first()
            ?? new HelpCenterArticleTranslation(['locale' => $locale, 'article_id' => $article->id]);

        return view('helpdeskhelpcenter::settings.translations.edit', compact('article', 'translation', 'locale'));
    }

    public function update(StoreTranslationRequest $request, HelpCenterArticle $article, string $locale): RedirectResponse
    {
        $this->authorize('translate', $article);

        $data = $request->validated();
        $data['locale'] = $locale;
        $data['is_published'] = $request->has('is_published');

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['slug'] = Str::slug($data['slug'] ?: $data['title']);

        $article->translations()->updateOrCreate(
            ['locale' => $locale],
            $data,
        );

        return redirect()
            ->route('manager.helpcenter.articles.translations.index', $article)
            ->with('success', 'Traducción guardada correctamente.');
    }

    public function destroy(HelpCenterArticle $article, string $locale): RedirectResponse
    {
        $this->authorize('translate', $article);

        $article->translations()->where('locale', $locale)->delete();

        return redirect()
            ->route('manager.helpcenter.articles.translations.index', $article)
            ->with('success', 'Traducción eliminada.');
    }
}
