<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings\Concerns;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
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
        $this->middleware('can:helpdesk.settings.update')->only(['store', 'update', 'destroy']);
    }

    abstract protected function modelClass(): string;

    abstract protected function viewName(): string;

    abstract protected function routeName(): string;

    /** Texto que acompaña "Mensaje ___" en los flashes, p. ej. "fuera de horario" / "de bienvenida". */
    abstract protected function itemLabel(): string;

    protected function renderIndex(): View
    {
        $modelClass = $this->modelClass();

        return view($this->viewName(), [
            'items' => $modelClass::query()
                ->orderByRaw('channel IS NULL, channel')
                ->orderByRaw('language IS NULL, language')
                ->get(),
            'offHoursChannels' => AutoReplyOptions::channels(),
            'offHoursLanguages' => AutoReplyOptions::languages(),
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
