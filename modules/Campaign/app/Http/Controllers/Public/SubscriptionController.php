<?php

namespace Modules\Campaign\Http\Controllers\Public;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Campaign\Mail\AdminSubscribeNotification;
use Modules\Campaign\Mail\SubscribeConfirmationMail;
use Modules\Campaign\Mail\WelcomeMail;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\CampaignSendingServers\Models\Blacklist;

/**
 * Páginas públicas de suscripción / confirmación / gestión.
 *
 * Rutas:
 *   GET  /campaign/subscribe/{listUid}        formulario embebido / standalone
 *   POST /campaign/subscribe/{listUid}        crea pendiente + envía email confirm si lista lo exige
 *   GET  /campaign/confirm/{token}            doble opt-in
 *   GET  /campaign/manage/{subscriberUid}     centro de preferencias
 *   POST /campaign/manage/{subscriberUid}     actualizar preferencias
 */
class SubscriptionController extends Controller
{
    public function form(string $listUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        return view('campaign::public.subscribe', [
            'list' => $list,
            'fields' => $list->fields()->where('visible', true)->orderBy('order')->get(),
        ]);
    }

    public function subscribe(Request $request, string $listUid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        $data = $request->validate([
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Si está blacklisted, ignorar silenciosamente para no informar atacantes.
        if (Blacklist::isBlacklisted($data['email'])) {
            return redirect()
                ->route('campaign.subscribe.thanks', $list->uid)
                ->with('info', 'Suscripción procesada.');
        }

        $sub = CampaignSubscriber::firstOrCreate(
            ['email' => $data['email']],
            [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'subscribed_at' => now(),
                'source' => 'web',
                'ip' => $request->ip(),
            ],
        );

        // Estado por lista: confirmed o unconfirmed según política de la lista
        $needsConfirmation = (int) $list->subscribe_confirmation === 1;
        $token = $needsConfirmation ? Str::random(48) : null;

        if ($token) {
            $sub->confirmation_code = $token;
            $sub->save();
        } else {
            $sub->confirmed_at = now();
            $sub->save();
        }

        DB::table('campaign_maillists_subscribers')->updateOrInsert(
            ['mail_list_id' => $list->id, 'subscriber_id' => $sub->id],
            [
                'uid' => (string) Str::uuid(),
                'status' => $needsConfirmation ? 'unconfirmed' : 'subscribed',
                'subscribed_at' => $needsConfirmation ? null : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // Email de confirmación si aplica
        if ($needsConfirmation && $token) {
            $this->sendConfirmEmail($sub, $list, $token);
        }

        return redirect()
            ->route('campaign.subscribe.thanks', $list->uid)
            ->with('info', $needsConfirmation
                ? 'Te hemos enviado un email para confirmar tu suscripción.'
                : 'Suscripción exitosa.');
    }

    public function thanks(string $listUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        return view('campaign::public.subscribe_thanks', compact('list'));
    }

    public function confirm(string $token): View
    {
        $sub = CampaignSubscriber::where('confirmation_code', $token)->firstOrFail();
        $sub->confirmed_at = now();
        $sub->confirmation_code = null;
        $sub->save();

        // Listas que pasan de 'unconfirmed' a 'subscribed' tras confirmar.
        $newlySubscribedListIds = DB::table('campaign_maillists_subscribers')
            ->where('subscriber_id', $sub->id)
            ->where('status', 'unconfirmed')
            ->pluck('mail_list_id');

        DB::table('campaign_maillists_subscribers')
            ->where('subscriber_id', $sub->id)
            ->where('status', 'unconfirmed')
            ->update([
                'status' => 'subscribed',
                'subscribed_at' => now(),
                'updated_at' => now(),
            ]);

        // Para cada lista, dispara welcome + admin notification si aplica.
        foreach ($newlySubscribedListIds as $listId) {
            $list = CampaignMaillist::find($listId);
            if (! $list) {
                continue;
            }
            $this->afterSubscribed($sub, $list);
        }

        return view('campaign::public.confirmed', ['subscriber' => $sub]);
    }

    /**
     * Hooks tras confirmar suscripción: welcome email + admin notification.
     */
    protected function afterSubscribed(CampaignSubscriber $sub, CampaignMaillist $list): void
    {
        // Welcome email
        if ((int) $list->send_welcome_email === 1) {
            try {
                Mail::to($sub->email)->queue(new WelcomeMail($sub, $list));
            } catch (\Throwable $e) {
                \Log::warning("WelcomeMail fallo para {$sub->email}: ".$e->getMessage());
            }
        }

        // Admin notification
        if (! empty($list->mail_subscribe)) {
            $admins = array_filter(preg_split('/\s*,\s*/', $list->mail_subscribe));
            try {
                Mail::to($admins)->queue(new AdminSubscribeNotification($sub, $list, 'subscribed'));
            } catch (\Throwable $e) {
                \Log::warning('Admin notification fallo: '.$e->getMessage());
            }
        }
    }

    public function manage(string $subUid): View
    {
        $sub = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        $lists = DB::table('campaign_maillists')
            ->join('campaign_maillists_subscribers as cms', 'cms.mail_list_id', '=', 'campaign_maillists.id')
            ->where('cms.subscriber_id', $sub->id)
            ->select('campaign_maillists.uid', 'campaign_maillists.name', 'cms.status')
            ->get();

        return view('campaign::public.manage', [
            'subscriber' => $sub,
            'lists' => $lists,
        ]);
    }

    public function updatePreferences(Request $request, string $subUid): RedirectResponse
    {
        $sub = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'lists' => ['nullable', 'array'],
            'lists.*' => ['in:subscribed,unsubscribed'],
        ]);

        $sub->update(array_diff_key($data, ['lists' => true]));

        // lists viene como [listUid => 'subscribed'|'unsubscribed']
        foreach ($data['lists'] ?? [] as $listUid => $status) {
            $list = CampaignMaillist::where('uid', $listUid)->first();
            if (! $list) {
                continue;
            }
            DB::table('campaign_maillists_subscribers')
                ->where('mail_list_id', $list->id)
                ->where('subscriber_id', $sub->id)
                ->update([
                    'status' => $status,
                    'unsubscribed_at' => $status === 'unsubscribed' ? now() : null,
                    'updated_at' => now(),
                ]);

            // Notificar al admin si el cambio fue desuscripción
            if ($status === 'unsubscribed' && ! empty($list->mail_unsubscribe)) {
                $admins = array_filter(preg_split('/\s*,\s*/', $list->mail_unsubscribe));
                try {
                    Mail::to($admins)->queue(new AdminSubscribeNotification($sub, $list, 'unsubscribed'));
                } catch (\Throwable $e) {
                    \Log::warning('Admin unsubscribe notification fallo: '.$e->getMessage());
                }
            }
        }

        return back()->with('success', 'Preferencias actualizadas.');
    }

    /**
     * Envía email de confirmación con plantilla Blade `subscribe_confirmation`.
     */
    protected function sendConfirmEmail(CampaignSubscriber $sub, CampaignMaillist $list, string $token): void
    {
        try {
            Mail::to($sub->email)->send(
                new SubscribeConfirmationMail($sub, $list, $token),
            );
        } catch (\Throwable $e) {
            \Log::error('SubscriptionController: no se pudo enviar email de confirmación: '.$e->getMessage());
        }
    }
}
