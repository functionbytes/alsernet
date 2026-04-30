<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

class SocialIntegrationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only(['index', 'testWhatsapp', 'testFacebook', 'testInstagram']);
        $this->middleware('can:helpdesk.settings.update')->only(['update', 'connect', 'disconnect']);
    }

    public function index(): View
    {
        return view('helpdesk::settings.social-integrations.index', [
            'whatsappEnabled' => config('helpdesk.integrations.whatsapp.enabled', false),
            'facebookEnabled' => config('helpdesk.integrations.facebook.enabled', false),
            'instagramEnabled' => config('helpdesk.integrations.instagram.enabled', false),
            'webhookBaseUrl' => url('webhooks/helpdesk'),
        ]);
    }

    public function testWhatsapp(WhatsAppBusinessService $service): JsonResponse
    {
        if (! $service->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'WhatsApp no está configurado o habilitado.']);
        }

        return response()->json(['success' => true, 'message' => 'WhatsApp Business API configurado correctamente.']);
    }

    public function testFacebook(FacebookMessengerService $service): JsonResponse
    {
        if (! $service->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'Facebook Messenger no está configurado o habilitado.']);
        }

        return response()->json(['success' => true, 'message' => 'Facebook Messenger configurado correctamente.']);
    }

    public function testInstagram(InstagramService $service): JsonResponse
    {
        if (! $service->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'Instagram no está configurado o habilitado.']);
        }

        return response()->json(['success' => true, 'message' => 'Instagram API configurado correctamente.']);
    }
}
