<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Slim SubscriberController.
 *
 * Suscriptores de UNA lista en concreto. Anidado bajo
 * /panel/campaign/maillists/{listUid}/subscribers/...
 *
 * Para vista global de suscriptores (sin lista) hay otro controller
 * en SubscribersController.php (root de subscribers).
 */
class SubscriberController extends Controller
{
    public function index(Request $request, string $listUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        $subscribers = $list->subscribers()
            ->when($request->query('q'), function ($q, $kw) {
                $q->where(function ($w) use ($kw): void {
                    $w->where('campaign_subscribers.email', 'like', "%{$kw}%")
                        ->orWhere('campaign_subscribers.first_name', 'like', "%{$kw}%")
                        ->orWhere('campaign_subscribers.last_name', 'like', "%{$kw}%");
                });
            })
            ->when($request->query('status'), fn ($q, $s) => $q->wherePivot('status', $s))
            ->when(
                $request->query('tag'),
                fn ($q, $tag) => $q->whereRaw('JSON_CONTAINS(campaign_maillists_subscribers.tags, ?)', [json_encode($tag)]),
            )
            ->orderBy('campaign_subscribers.id', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Tags únicas en la lista (para filtro)
        $allTags = collect();
        DB::table('campaign_maillists_subscribers')
            ->where('mail_list_id', $list->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->each(function ($json) use ($allTags): void {
                $tags = is_string($json) ? json_decode($json, true) : $json;
                if (is_array($tags)) {
                    foreach ($tags as $t) {
                        $allTags->push($t);
                    }
                }
            });

        return view('campaign::manager.subscribers.index', [
            'list' => $list,
            'subscribers' => $subscribers,
            'allTags' => $allTags->unique()->sort()->values(),
        ]);
    }

    /**
     * Asignar/quitar tags a un suscriptor de esta lista.
     */
    public function updateTags(Request $request, string $listUid, string $subUid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $sub = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        $tags = array_values(array_filter(array_map('trim', (array) $request->input('tags', []))));

        DB::table('campaign_maillists_subscribers')
            ->where('mail_list_id', $list->id)
            ->where('subscriber_id', $sub->id)
            ->update(['tags' => json_encode($tags), 'updated_at' => now()]);

        return back()->with('success', 'Tags actualizados.');
    }

    public function create(string $listUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        return view('campaign::manager.subscribers.create', [
            'list' => $list,
            'fields' => $list->fields,
        ]);
    }

    public function store(Request $request, string $listUid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        $data = $request->validate([
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'attributes' => ['nullable', 'array'],
            'status' => ['nullable', 'in:subscribed,unsubscribed,unconfirmed'],
        ]);

        $sub = CampaignSubscriber::firstOrCreate(
            ['email' => $data['email']],
            [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'attributes' => $data['attributes'] ?? null,
                'subscribed_at' => now(),
            ],
        );

        // Asociar a la lista (o reactivar si ya estaba)
        DB::table('campaign_maillists_subscribers')->updateOrInsert(
            ['mail_list_id' => $list->id, 'subscriber_id' => $sub->id],
            [
                'uid' => (string) Str::uuid(),
                'status' => $data['status'] ?? 'subscribed',
                'subscribed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return redirect()
            ->route('manager.campaigns.maillists.subscribers.index', $list->uid)
            ->with('success', "Suscriptor {$sub->email} añadido.");
    }

    public function edit(string $listUid, string $subUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $sub = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        return view('campaign::manager.subscribers.edit', [
            'list' => $list,
            'subscriber' => $sub,
            'pivot' => DB::table('campaign_maillists_subscribers')
                ->where('mail_list_id', $list->id)
                ->where('subscriber_id', $sub->id)
                ->first(),
        ]);
    }

    public function update(Request $request, string $listUid, string $subUid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $sub = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        $data = $request->validate([
            'email' => ['sometimes', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:subscribed,unsubscribed,unconfirmed'],
        ]);

        $sub->update(array_diff_key($data, ['status' => true]));

        if (! empty($data['status'])) {
            DB::table('campaign_maillists_subscribers')
                ->where('mail_list_id', $list->id)
                ->where('subscriber_id', $sub->id)
                ->update([
                    'status' => $data['status'],
                    'updated_at' => now(),
                    'unsubscribed_at' => $data['status'] === 'unsubscribed' ? now() : null,
                ]);
        }

        return redirect()
            ->route('manager.campaigns.maillists.subscribers.index', $list->uid)
            ->with('success', 'Suscriptor actualizado.');
    }

    /**
     * Eliminar la asociación con la lista (no borra el subscriber global).
     */
    public function destroy(string $listUid, string $subUid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $sub = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        DB::table('campaign_maillists_subscribers')
            ->where('mail_list_id', $list->id)
            ->where('subscriber_id', $sub->id)
            ->delete();

        return back()->with('success', 'Suscriptor desasociado de esta lista.');
    }
}
