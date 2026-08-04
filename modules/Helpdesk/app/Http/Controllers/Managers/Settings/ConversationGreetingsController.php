<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\Concerns\AutoReplySettingsController;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreConversationGreetingRequest;
use Modules\Helpdesk\Models\ConversationGreeting;

/**
 * CRUD de los mensajes de bienvenida que dispara
 * SendGreetingOnConversationCreated. Lógica compartida con Fuera de horario
 * y Despedida en AutoReplySettingsController.
 */
class ConversationGreetingsController extends AutoReplySettingsController
{
    protected function modelClass(): string
    {
        return ConversationGreeting::class;
    }

    protected function viewName(): string
    {
        return 'helpdesk::settings.business.greeting';
    }

    protected function routeName(): string
    {
        return 'settings.helpdesk.business.greeting';
    }

    protected function itemLabel(): string
    {
        return 'de bienvenida';
    }

    public function index(): View
    {
        return $this->renderIndex();
    }

    public function store(StoreConversationGreetingRequest $request): RedirectResponse
    {
        return $this->handleStore($request);
    }

    public function update(StoreConversationGreetingRequest $request, ConversationGreeting $conversationGreeting): RedirectResponse
    {
        return $this->handleUpdate($request, $conversationGreeting);
    }

    public function destroy(ConversationGreeting $conversationGreeting): RedirectResponse
    {
        return $this->handleDestroy($conversationGreeting);
    }
}
