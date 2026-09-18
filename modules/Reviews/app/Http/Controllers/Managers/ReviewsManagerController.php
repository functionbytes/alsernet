<?php

namespace Modules\Reviews\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewSource;
use Modules\Reviews\Models\ReviewTranslation;
use Modules\Reviews\Services\ReviewModerator;
use Modules\Reviews\Services\ReviewScreener;
use Modules\Reviews\Services\ReviewTranslator;

/**
 * Bandeja de moderación.
 *
 * Lo pendiente primero, con el producto, el pedido y el cliente al lado para
 * poder decidir sin abrir otra pantalla.
 */
class ReviewsManagerController extends Controller
{
    public function __construct(
        private readonly ReviewModerator $moderator,
        private readonly ReviewTranslator $translator,
    ) {
        $this->middleware('can:reviews.moderate')->only([
            'approve', 'reject', 'answer', 'bulk',
            'translate', 'approveTranslation', 'updateTranslation', 'publishTranslations', 'pullTranslations', 'screen',
        ]);
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', Review::STATUS_PENDING);
        $search = trim((string) $request->query('q', ''));

        // Con casi seis mil opiniones, poder separar producto de tienda y lo
        // propio de lo de Google deja de ser un lujo.
        $entity = $request->query('entity');
        $origin = $request->query('origin');

        $reviews = Review::query()
            ->when(in_array($entity, [Review::ENTITY_PRODUCT, Review::ENTITY_STORE], true),
                fn ($q) => $q->where('entity', $entity))
            ->when(in_array($origin, [Review::ORIGIN_CUSTOMER, Review::ORIGIN_GOOGLE], true),
                fn ($q) => $q->where('origin', $origin))
            ->when($request->filled('source'), fn ($q) => $q->where('source_id', (int) $request->query('source')))
            ->when(in_array($status, [Review::STATUS_PENDING, Review::STATUS_APPROVED, Review::STATUS_REJECTED], true),
                fn ($q) => $q->where('status', $status))
            ->when($status === 'conflict', fn ($q) => $q->inConflict())
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhere('order_reference', 'like', "%{$search}%");
            }))
            ->when($request->filled('lang'), fn ($q) => $q->where('lang_iso', $request->query('lang')))
            ->when($request->filled('stars'), fn ($q) => $q->where('stars', (int) $request->query('stars')))
            ->latest('ps_date')
            ->paginate(25)
            ->withQueryString();

        return view('reviews::manage.index', [
            'reviews' => $reviews,
            'status' => $status,
            'search' => $search,
            'entity' => $entity,
            'origin' => $origin,
            'counts' => $this->counts(),
            'byEntity' => $this->byEntity(),
            'sources' => ReviewSource::orderBy('name')->get(),
            'languages' => array_keys((array) config('reviews.languages', [])),
        ]);
    }

    /**
     * Ficha de una opinión: lo que dice, de quién viene, qué se ha decidido y
     * quién lo decidió, más las traducciones idioma por idioma.
     */
    public function show(Review $review): View
    {
        // La primera vez que se abre, se mira qué traducciones tiene ya en la
        // tienda: el 81 % venían traducidas de antes y la ficha decía "0 de 5".
        if ($review->translations_synced_at === null && $review->ps_comment_id) {
            $this->translator->pullFromShop($review);
        }

        $review->load(['translations', 'events']);

        $idiomas = (array) config('reviews.languages', []);
        $porIdioma = $review->translations->keyBy('lang_iso');

        // Una fila por idioma de la tienda, tenga traducción o no: así se ve de
        // un vistazo lo que falta, no solo lo que ya está.
        $filas = [];

        foreach ($idiomas as $iso => $psLangId) {
            if ((int) $psLangId === (int) $review->ps_lang_id) {
                continue;
            }

            $filas[] = [
                'iso' => $iso,
                'ps_lang_id' => $psLangId,
                'translation' => $porIdioma->get($iso),
            ];
        }

        return view('reviews::manage.show', [
            'review' => $review,
            'screeningEnabled' => ReviewScreener::isEnabled(),
            'screeningLabels' => ReviewScreener::LABELS,
            'rows' => $filas,
            'translatorAvailable' => $this->translator->isAvailable(),
        ]);
    }

    public function screen(Review $review, ReviewScreener $screener): RedirectResponse
    {
        $resultado = $screener->screen($review);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function pullTranslations(Review $review): RedirectResponse
    {
        $resultado = $this->translator->pullFromShop($review);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function translate(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'langs' => ['nullable', 'array'],
            'langs.*' => ['string', 'size:2'],
        ]);

        $resultado = $this->translator->translate($review, $validated['langs'] ?? null);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function updateTranslation(Request $request, Review $review, ReviewTranslation $translation): RedirectResponse
    {
        abort_unless($translation->review_id === $review->id, 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:4000'],
            'answer' => ['nullable', 'string', 'max:4000'],
        ]);

        $translation->update($validated + ['reviewed' => false]);
        $review->recordEvent('translation_edited', 'panel', ['idioma' => $translation->lang_iso]);

        return back()->with('success', 'Traducción corregida. Vuelve a aprobarla para publicarla.');
    }

    public function approveTranslation(Review $review, ReviewTranslation $translation): RedirectResponse
    {
        abort_unless($translation->review_id === $review->id, 404);

        $translation->update(['reviewed' => ! $translation->reviewed]);
        $review->recordEvent(
            $translation->reviewed ? 'translation_approved' : 'translation_unapproved',
            'panel',
            ['idioma' => $translation->lang_iso]
        );

        return back()->with('success', $translation->reviewed
            ? 'Traducción al '.strtoupper($translation->lang_iso).' aprobada.'
            : 'Aprobación retirada.');
    }

    public function publishTranslations(Review $review): RedirectResponse
    {
        $resultado = $this->translator->publish($review);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function approve(Review $review): RedirectResponse
    {
        $resultado = $this->moderator->approve($review);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function reject(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $resultado = $this->moderator->reject($review, $validated['reason'] ?? null);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function answer(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:2000'],
        ]);

        $resultado = $this->moderator->answer($review, $validated['answer']);

        return back()->with($resultado['ok'] ? 'success' : 'warning', $resultado['message']);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $reviews = Review::whereIn('id', $validated['ids'])->get();
        $hechas = 0;
        $fallidas = 0;

        foreach ($reviews as $review) {
            $resultado = $validated['action'] === 'approve'
                ? $this->moderator->approve($review)
                : $this->moderator->reject($review);

            $resultado['ok'] ? $hechas++ : $fallidas++;
        }

        $mensaje = $hechas.' '.($validated['action'] === 'approve' ? 'publicadas' : 'retiradas');

        if ($fallidas) {
            return back()->with('warning', $mensaje.'. '.$fallidas.' no llegaron a la tienda.');
        }

        return back()->with('success', $mensaje.'.');
    }

    /** Cuántas hay de cada tipo, para las pestañas de procedencia. */
    private function byEntity(): array
    {
        return [
            'product' => Review::where('entity', Review::ENTITY_PRODUCT)->count(),
            'store_customer' => Review::where('entity', Review::ENTITY_STORE)->fromCustomers()->count(),
            'store_google' => Review::fromGoogle()->count(),
        ];
    }

    private function counts(): array
    {
        return [
            'pending' => Review::pending()->count(),
            'approved' => Review::approved()->count(),
            'rejected' => Review::where('status', Review::STATUS_REJECTED)->count(),
            'conflict' => Review::inConflict()->count(),
        ];
    }
}
