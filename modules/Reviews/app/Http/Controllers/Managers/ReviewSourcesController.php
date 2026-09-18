<?php

namespace Modules\Reviews\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewSource;
use Modules\Reviews\Services\GoogleBusinessClient;
use Modules\Reviews\Services\GoogleReviewImporter;

/**
 * Fichas de las que se leen opiniones.
 *
 * Las credenciales entran pero no salen: el formulario dice si están puestas y
 * permite sustituirlas, nunca las devuelve al navegador.
 */
class ReviewSourcesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reviews.settings')->except('index');
    }

    public function index(): View
    {
        return view('reviews::sources.index', [
            'sources' => ReviewSource::withCount('reviews')->orderBy('name')->get(),
        ]);
    }

    /**
     * Una ficha con lo suyo: su estado, sus cifras y solo sus reseñas.
     *
     * Con tres tiendas y más de mil reseñas, mirarlas todas juntas no dice nada
     * de ninguna. Aquí cada establecimiento se ve por separado.
     */
    public function show(Request $request, ReviewSource $source): View
    {
        $status = (string) $request->query('status', 'all');

        $reviews = $source->reviews()
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true),
                fn ($q) => $q->where('status', $status))
            ->when($request->filled('stars'), fn ($q) => $q->where('stars', (int) $request->query('stars')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $termino = $request->query('q');
                $q->where('comment', 'like', "%{$termino}%")->orWhere('author', 'like', "%{$termino}%");
            }))
            ->latest('ps_date')
            ->paginate(25)
            ->withQueryString();

        $base = $source->reviews();

        return view('reviews::sources.show', [
            'source' => $source,
            'reviews' => $reviews,
            'status' => $status,
            'stats' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
                'negativas' => (clone $base)->where('stars', '<=', 4)->count(),
                'sin_respuesta' => (clone $base)->whereNull('answer')->count(),
                'media' => round(((float) (clone $base)->avg('stars')) / 2, 1),
            ],
            'breakdown' => (clone $base)->selectRaw('stars, COUNT(*) n')->groupBy('stars')->pluck('n', 'stars')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validated($request);

        $source = new ReviewSource([
            'name' => $datos['name'],
            'platform' => ReviewSource::PLATFORM_GOOGLE,
            'external_id' => $datos['external_id'] ?: null,
            'account_id' => $datos['account_id'] ?: null,
            'active' => $request->boolean('active'),
            'auto_approve' => $request->boolean('auto_approve'),
        ]);

        $source->credentials = $this->credentials($request, []);
        $source->save();

        return back()->with('success', 'Ficha «'.$source->name.'» registrada.');
    }

    public function update(Request $request, ReviewSource $source): RedirectResponse
    {
        $datos = $this->validated($request, $source->id);

        $source->fill([
            'name' => $datos['name'],
            'external_id' => $datos['external_id'] ?: null,
            'account_id' => $datos['account_id'] ?: null,
            'active' => $request->boolean('active'),
            'auto_approve' => $request->boolean('auto_approve'),
        ]);

        // Los campos de credencial vacíos no borran lo que ya había: el
        // formulario nunca las muestra, así que dejarlos en blanco significa
        // "no las cambies", no "bórralas".
        $source->credentials = $this->credentials($request, (array) $source->credentials);
        $source->save();

        return back()->with('success', 'Ficha actualizada.');
    }

    public function destroy(ReviewSource $source): RedirectResponse
    {
        $nombre = $source->name;
        $source->delete();

        return back()->with('success', 'Ficha «'.$nombre.'» eliminada. Las opiniones que trajo se conservan.');
    }

    public function test(ReviewSource $source, GoogleBusinessClient $google): RedirectResponse
    {
        $r = $google->testConnection($source);

        return back()->with($r['ok'] ? 'success' : 'warning', $source->name.': '.$r['message']);
    }

    public function fetch(ReviewSource $source, GoogleReviewImporter $importer): RedirectResponse
    {
        $r = $importer->import($source);

        if (! $r['ok']) {
            return back()->with('warning', $source->name.': '.$r['error']);
        }

        return back()->with('success', sprintf(
            '%s: %d reseñas nuevas y %d actualizadas.',
            $source->name, $r['nuevas'], $r['actualizadas']
        ));
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'account_id' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'refresh_token' => ['nullable', 'string', 'max:512'],
        ]);
    }

    private function credentials(Request $request, array $actuales): array
    {
        foreach (['client_id', 'client_secret', 'refresh_token'] as $clave) {
            $valor = trim((string) $request->input($clave));

            if ($valor !== '') {
                $actuales[$clave] = $valor;
            }
        }

        return $actuales;
    }
}
