<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings\Concerns;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Support\AutoReplyOptions;

/**
 * Lógica CRUD compartida por las 3 páginas de mensajes automáticos (fuera de
 * horario, bienvenida, despedida) — mismo esquema (channel, language,
 * message, is_active), mismo patrón de página propia con un solo formulario.
 * Cada subclase solo declara los datos que la diferencian (modelo, vista,
 * nombre de ruta, etiqueta para los mensajes flash) y los métodos con el
 * tipo concreto de FormRequest/Model para que la inyección y el route model
 * binding de Laravel sigan funcionando.
 */
abstract class AutoReplySettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only(['store', 'update', 'destroy', 'bulkAction']);
    }

    abstract protected function modelClass(): string;

    abstract protected function viewName(): string;

    abstract protected function routeName(): string;

    /** Texto que acompaña "Mensaje ___" en los flashes, p. ej. "fuera de horario" / "de bienvenida". */
    abstract protected function itemLabel(): string;

    protected function renderIndex(): View
    {
        $modelClass = $this->modelClass();
        $request = request();

        // Un mensaje activo sin canal ("Todos los canales", channel NULL) cubre
        // todos: COUNT(DISTINCT channel) los ignoraria y diria 0 canales
        // cubiertos justo cuando lo estan todos.
        $active = $modelClass::where('is_active', true);
        $coversAll = (clone $active)->whereNull('channel')->exists();

        $channelsCovered = $coversAll
            ? count(AutoReplyOptions::channelSlugs())
            : (clone $active)->whereNotNull('channel')->distinct()->count('channel');

        // Los contadores describen el total configurado, no la pagina que se
        // esta viendo: filtrar no debe cambiar lo que dicen las tarjetas.
        $stats = [
            'total' => $modelClass::count(),
            'active' => (clone $active)->count(),
            'inactive' => $modelClass::where('is_active', false)->count(),
            'channels' => $channelsCovered,
            'channels_total' => count(AutoReplyOptions::channelSlugs()),
        ];

        $items = $modelClass::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('message', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('channel'), function ($q) use ($request) {
                // "__none__" es la fila "Todos los canales" (channel NULL), que
                // no se puede pedir por su valor porque en la URL seria vacio.
                $channel = $request->string('channel')->toString();
                $channel === '__none__' ? $q->whereNull('channel') : $q->where('channel', $channel);
            })
            ->when($request->filled('language'), function ($q) use ($request) {
                $language = $request->string('language')->toString();
                $language === '__none__' ? $q->whereNull('language') : $q->where('language', $language);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->orderByRaw('channel IS NULL, channel')
            ->orderByRaw('language IS NULL, language')
            ->paginate(20)
            ->withQueryString();

        return view($this->viewName(), [
            'items' => $items,
            'stats' => $stats,
            'offHoursChannels' => AutoReplyOptions::channels(),
            'offHoursLanguages' => AutoReplyOptions::languages(),
        ]);
    }

    /**
     * Activar / desactivar / eliminar varios mensajes de una vez.
     */
    protected function handleBulkAction(): JsonResponse
    {
        $validated = request()->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $modelClass = $this->modelClass();
        $items = $modelClass::whereIn('id', $validated['ids'])->get();

        if ($validated['action'] === 'delete') {
            foreach ($items as $item) {
                $item->delete();
            }
        } else {
            foreach ($items as $item) {
                $item->update(['is_active' => $validated['action'] === 'activate']);
            }
        }

        $labels = ['delete' => 'eliminado(s)', 'activate' => 'activado(s)', 'deactivate' => 'desactivado(s)'];

        return response()->json([
            'message' => $items->count()." mensaje(s) {$labels[$validated['action']]}.",
            'count' => $items->count(),
        ]);
    }

    protected function handleStore(FormRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $modelClass = $this->modelClass();

        if ($this->duplicateExists($modelClass, $data['channel'] ?? null, $data['language'] ?? null)) {
            return $this->duplicateResponse();
        }

        try {
            $modelClass::create($data);
        } catch (QueryException $e) {
            return $this->duplicateOrThrow($e);
        }

        return redirect()
            ->route($this->routeName())
            ->with('success', "Mensaje {$this->itemLabel()} añadido.");
    }

    protected function handleUpdate(FormRequest $request, Model $model): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if ($this->duplicateExists($this->modelClass(), $data['channel'] ?? null, $data['language'] ?? null, $model->getKey())) {
            return $this->duplicateResponse($model->getKey());
        }

        try {
            $model->update($data);
        } catch (QueryException $e) {
            return $this->duplicateOrThrow($e, $model->getKey());
        }

        return redirect()
            ->route($this->routeName())
            ->with('success', "Mensaje {$this->itemLabel()} actualizado.");
    }

    protected function handleDestroy(Model $model): RedirectResponse
    {
        $model->delete();

        return redirect()
            ->route($this->routeName())
            ->with('success', "Mensaje {$this->itemLabel()} eliminado.");
    }

    /**
     * MySQL/MariaDB no aplican el índice único (channel, language) cuando
     * ambas columnas son NULL — NULL nunca es igual a NULL para un unique
     * index — así que la combinación "todos los canales / genérico" no
     * queda protegida por la base de datos. Se comprueba a mano antes del
     * insert/update; el catch de QueryException que sigue queda como red de
     * seguridad para el resto de combinaciones ante condiciones de carrera.
     */
    private function duplicateExists(string $modelClass, ?string $channel, ?string $language, int|string|null $ignoreId = null): bool
    {
        return $modelClass::query()
            ->when($channel === null, fn ($q) => $q->whereNull('channel'), fn ($q) => $q->where('channel', $channel))
            ->when($language === null, fn ($q) => $q->whereNull('language'), fn ($q) => $q->where('language', $language))
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * `withInput()` con lo que el usuario ya había escrito (más `_item_id`
     * cuando aplica) para que la vista pueda reabrir el modal correcto — el
     * de alta si viene de store(), o el de edición del registro concreto si
     * viene de update() — con los datos tal como los dejó, no en blanco.
     */
    private function duplicateResponse(int|string|null $itemId = null): RedirectResponse
    {
        $input = request()->input();

        if ($itemId !== null) {
            $input['_item_id'] = $itemId;
        }

        return redirect()
            ->route($this->routeName())
            ->with('error', "Ya existe un mensaje {$this->itemLabel()} configurado para esa combinación de canal e idioma.")
            ->withInput($input);
    }

    private function duplicateOrThrow(QueryException $e, int|string|null $itemId = null): RedirectResponse
    {
        if ($e->getCode() === '23000') {
            return $this->duplicateResponse($itemId);
        }

        throw $e;
    }
}
