<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreInboxRequest;
use Modules\Helpdesk\Models\Channels\Email;
use Modules\Helpdesk\Models\Channels\Facebook;
use Modules\Helpdesk\Models\Channels\Instagram;
use Modules\Helpdesk\Models\Channels\Whatsapp;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class InboxesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only(['index', 'create', 'edit']);
        $this->middleware('can:helpdesk.settings.update')->only(['store', 'update', 'destroy', 'toggle']);
    }

    public function index(): View
    {
        $inboxes = Inbox::query()
            ->with(['defaultAssignee:id,firstname,lastname', 'defaultGroup:id,name'])
            ->withCount('conversations')
            ->orderBy('channel_type')
            ->orderBy('name')
            ->get();

        $channelLabels = Inbox::channelLabels();
        $channelIcons = Inbox::channelIcons();

        return view('helpdesk::settings.inboxes.index', compact(
            'inboxes',
            'channelLabels',
            'channelIcons',
        ));
    }

    public function create(Request $request): View
    {
        $channel = $request->input('channel', Inbox::CHANNEL_WHATSAPP);
        if (! in_array($channel, Inbox::availableChannelTypes(), true)) {
            $channel = Inbox::CHANNEL_WHATSAPP;
        }

        $inbox = new Inbox(['channel_type' => $channel, 'is_active' => true]);

        return $this->renderForm($inbox);
    }

    public function store(StoreInboxRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['greeting_enabled'] = $request->boolean('greeting_enabled');
        $data['working_hours_enabled'] = $request->boolean('working_hours_enabled');
        $data['working_hours'] = $request->boolean('working_hours_enabled')
            ? $this->buildWorkingHours($request->input('working_hours', []))
            : null;

        $inbox = Inbox::create($data);

        $this->syncChannelRecord($inbox, $request);

        return redirect()
            ->route('settings.helpdesk.inboxes.edit', $inbox)
            ->with('success', 'Inbox creado correctamente.');
    }

    public function edit(Inbox $inbox): View
    {
        return $this->renderForm($inbox);
    }

    public function update(StoreInboxRequest $request, Inbox $inbox): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['greeting_enabled'] = $request->boolean('greeting_enabled');
        $data['working_hours_enabled'] = $request->boolean('working_hours_enabled');
        $data['working_hours'] = $request->boolean('working_hours_enabled')
            ? $this->buildWorkingHours($request->input('working_hours', []))
            : null;

        // Si el form no envió credentials (no se editaron), conservamos las existentes.
        if (! $request->has('credentials')) {
            unset($data['credentials']);
        }

        $inbox->update($data);

        $this->syncChannelRecord($inbox, $request);

        return redirect()
            ->route('settings.helpdesk.inboxes.edit', $inbox)
            ->with('success', 'Inbox actualizado.');
    }

    public function destroy(Inbox $inbox): RedirectResponse
    {
        $inbox->delete();

        return redirect()
            ->route('settings.helpdesk.inboxes.index')
            ->with('success', 'Inbox eliminado.');
    }

    public function toggle(Inbox $inbox): RedirectResponse
    {
        $inbox->update(['is_active' => ! $inbox->is_active]);

        return back()->with('success', $inbox->is_active ? 'Inbox activado.' : 'Inbox desactivado.');
    }

    public function test(Request $request, Inbox $inbox): JsonResponse
    {
        try {
            if ($inbox->channel_type === Inbox::CHANNEL_WHATSAPP) {
                $creds = $inbox->credentials;
                $token = $creds['access_token'] ?? null;
                $phoneNumberId = $creds['phone_number_id'] ?? null;

                if (! $token || ! $phoneNumberId) {
                    return response()->json(['ok' => false, 'message' => 'Faltan credenciales (access_token o phone_number_id).']);
                }

                $response = Http::timeout(8)
                    ->withToken($token)
                    ->get("https://graph.facebook.com/v19.0/{$phoneNumberId}");

                $ok = $response->successful();
                $message = $ok
                    ? 'Conexión exitosa con WhatsApp Cloud API.'
                    : 'Error al conectar: '.$response->json('error.message', 'Respuesta inesperada.');

                return response()->json(['ok' => $ok, 'message' => $message]);
            }

            if ($inbox->channel_type === Inbox::CHANNEL_WEB) {
                return response()->json(['ok' => true, 'message' => 'No hay conexión externa que verificar.']);
            }

            return response()->json(['ok' => true, 'message' => 'Verificación no disponible para este canal.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Error al verificar: '.$e->getMessage()]);
        }
    }

    private function renderForm(Inbox $inbox): View
    {
        $agents = User::query()
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->orderBy('firstname')
            ->limit(200)
            ->get();

        $groups = Group::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $credentialFields = Inbox::credentialFields($inbox->channel_type);
        $channelLabels = Inbox::channelLabels();
        $channelIcons = Inbox::channelIcons();
        $channel = $inbox->channel;

        return view('helpdesk::settings.inboxes.form', compact(
            'inbox',
            'agents',
            'groups',
            'credentialFields',
            'channelLabels',
            'channelIcons',
            'channel',
        ));
    }

    private function syncChannelRecord(Inbox $inbox, Request $request): void
    {
        $creds = $request->input('credentials', []);

        match ($inbox->channel_type) {
            Inbox::CHANNEL_WEB => $this->syncWebChannel($inbox, $creds),
            Inbox::CHANNEL_WHATSAPP => $this->syncWhatsappChannel($inbox, $creds, $request->input('wa_provider', 'whatsapp_cloud')),
            Inbox::CHANNEL_FACEBOOK => $this->syncFacebookChannel($inbox, $creds),
            Inbox::CHANNEL_INSTAGRAM => $this->syncInstagramChannel($inbox, $creds),
            Inbox::CHANNEL_EMAIL => $this->syncEmailChannel($inbox, $creds),
            default => null,
        };
    }

    private function syncWebChannel(Inbox $inbox, array $creds): void
    {
        $isNew = ! $inbox->channel_id;
        $channel = $isNew
            ? new Web
            : Web::findOrFail($inbox->channel_id);

        $channel->fill([
            'account_id' => $inbox->account_id,
            'website_url' => $creds['site_url'] ?? null,
            'widget_color' => $creds['primary_color'] ?? $inbox->color ?? '#b10100',
        ]);
        $channel->save();

        if ($isNew) {
            $inbox->updateQuietly(['channel_id' => $channel->id]);
        }
    }

    private function syncWhatsappChannel(Inbox $inbox, array $creds, string $provider): void
    {
        $isNew = ! $inbox->channel_id;
        $channel = $isNew
            ? new Whatsapp
            : Whatsapp::findOrFail($inbox->channel_id);

        $providerConfig = match ($provider) {
            '360dialog' => ['api_key' => $creds['api_key'] ?? null],
            'evolution_api' => [
                'instance_name' => $creds['instance_name'] ?? null,
                'api_url' => $creds['api_url'] ?? null,
                'api_key' => $creds['api_key'] ?? null,
            ],
            default => [
                'access_token' => $creds['access_token'] ?? null,
                'phone_number_id' => $creds['phone_number_id'] ?? null,
                'business_account_id' => $creds['business_account_id'] ?? null,
            ],
        };

        $channel->fill([
            'account_id' => $inbox->account_id,
            'phone_number' => $creds['phone_number'] ?? null,
            'provider' => $provider,
            'provider_config' => $providerConfig,
        ]);
        $channel->save();

        if ($isNew) {
            $inbox->updateQuietly(['channel_id' => $channel->id]);
        }
    }

    private function syncFacebookChannel(Inbox $inbox, array $creds): void
    {
        $isNew = ! $inbox->channel_id;
        $channel = $isNew
            ? new Facebook
            : Facebook::findOrFail($inbox->channel_id);

        $channel->fill([
            'account_id' => $inbox->account_id,
            'page_id' => $creds['page_id'] ?? null,
            'page_access_token' => $creds['page_access_token'] ?? null,
        ]);
        $channel->save();

        if ($isNew) {
            $inbox->updateQuietly(['channel_id' => $channel->id]);
        }
    }

    private function syncInstagramChannel(Inbox $inbox, array $creds): void
    {
        $isNew = ! $inbox->channel_id;
        $channel = $isNew
            ? new Instagram
            : Instagram::findOrFail($inbox->channel_id);

        $channel->fill([
            'account_id' => $inbox->account_id,
            'business_account_id' => $creds['business_account_id'] ?? null,
        ]);
        $channel->save();

        if ($isNew) {
            $inbox->updateQuietly(['channel_id' => $channel->id]);
        }
    }

    private function syncEmailChannel(Inbox $inbox, array $creds): void
    {
        $isNew = ! $inbox->channel_id;
        $channel = $isNew
            ? new Email
            : Email::findOrFail($inbox->channel_id);

        $channel->fill([
            'account_id' => $inbox->account_id,
            'email' => $creds['inbound_address'] ?? null,
            'provider' => $creds['provider'] ?? 'generic',
            'imap_enabled' => ! empty($creds['imap_address']),
            'imap_address' => $creds['imap_address'] ?? null,
            'imap_port' => (int) ($creds['imap_port'] ?? 993),
            'imap_login' => $creds['imap_login'] ?? null,
            'imap_password' => $creds['imap_password'] ?? null,
            'imap_enable_ssl' => true,
            'smtp_enabled' => ! empty($creds['smtp_address']),
            'smtp_address' => $creds['smtp_address'] ?? null,
            'smtp_port' => (int) ($creds['smtp_port'] ?? 587),
            'smtp_login' => $creds['smtp_login'] ?? null,
            'smtp_password' => $creds['smtp_password'] ?? null,
        ]);
        $channel->save();

        if ($isNew) {
            $inbox->updateQuietly(['channel_id' => $channel->id]);
        }
    }

    private function buildWorkingHours(array $input): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];

        foreach ($days as $day) {
            $dayData = $input[$day] ?? [];
            $hours[$day] = [
                'enabled' => (bool) ($dayData['enabled'] ?? false),
                'start' => $dayData['start'] ?? '09:00',
                'end' => $dayData['end'] ?? '18:00',
            ];
        }

        return $hours;
    }
}
