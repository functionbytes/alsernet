<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\Concerns\AutoReplySettingsController;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreConversationFarewellRequest;
use Modules\Helpdesk\Models\ConversationFarewell;

/**
 * CRUD de los mensajes de despedida que dispara
 * SendFarewellOnConversationClosed. Lógica compartida con Fuera de horario y
 * Bienvenida en AutoReplySettingsController.
 */
class ConversationFarewellsController extends AutoReplySettingsController
{
    protected function modelClass(): string
    {
        return ConversationFarewell::class;
    }

    protected function viewName(): string
    {
        return 'helpdesk::settings.business.farewell';
    }

    protected function routeName(): string
    {
        return 'settings.helpdesk.business.farewell';
    }

    protected function itemLabel(): string
    {
        return 'de despedida';
    }

    public function index(): View
    {
        return $this->renderIndex();
    }

    public function store(StoreConversationFarewellRequest $request): RedirectResponse
    {
        return $this->handleStore($request);
    }

    public function update(StoreConversationFarewellRequest $request, ConversationFarewell $conversationFarewell): RedirectResponse
    {
        return $this->handleUpdate($request, $conversationFarewell);
    }

    public function destroy(ConversationFarewell $conversationFarewell): RedirectResponse
    {
        return $this->handleDestroy($conversationFarewell);
    }
}
