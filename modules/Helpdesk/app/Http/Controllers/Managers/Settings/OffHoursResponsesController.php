<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\Concerns\AutoReplySettingsController;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreOffHoursResponseRequest;
use Modules\Helpdesk\Models\OffHoursResponse;

/**
 * CRUD de los mensajes automáticos "fuera de horario" que dispara
 * RespondOffHoursOnConversationCreated. Lógica compartida con Bienvenida y
 * Despedida en AutoReplySettingsController — ver esa clase para el flujo
 * real de index/store/update/destroy.
 *
 * Combinación única (channel, language): null = "todos los canales" /
 * "genérico, se traduce al vuelo" respectivamente. Un mensaje con idioma
 * propio se envía tal cual, sin pasar por el traductor.
 */
class OffHoursResponsesController extends AutoReplySettingsController
{
    protected function modelClass(): string
    {
        return OffHoursResponse::class;
    }

    protected function viewName(): string
    {
        return 'helpdesk::settings.business.off-hours';
    }

    protected function routeName(): string
    {
        return 'settings.helpdesk.business.off-hours';
    }

    protected function itemLabel(): string
    {
        return 'fuera de horario';
    }

    public function index(): View
    {
        return $this->renderIndex();
    }

    public function store(StoreOffHoursResponseRequest $request): RedirectResponse
    {
        return $this->handleStore($request);
    }

    public function update(StoreOffHoursResponseRequest $request, OffHoursResponse $offHoursResponse): RedirectResponse
    {
        return $this->handleUpdate($request, $offHoursResponse);
    }

    public function destroy(OffHoursResponse $offHoursResponse): RedirectResponse
    {
        return $this->handleDestroy($offHoursResponse);
    }
}
