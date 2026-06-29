<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * MaillistProController — UI de listas y suscriptores portada de acellemail
 * (refactor). Montado en `lists-pro` para CONVIVIR con las listas existentes
 * del módulo (`manager.maillists.*`), reutilizando los mismos modelos
 * CampaignMaillist + CampaignSubscriber.
 *
 * No modifica ningún controlador, vista o ruta existente.
 */
class MaillistProController extends Controller
{
    // =========================================================================
    // LISTS
    // =========================================================================

    /**
     * Página principal: estadísticas globales + McList AJAX.
     */
    public function index(Request $request)
    {
        $totalLists = 0;
        $totalSubscribers = 0;
        $activeSubscribers = 0;
        $inactiveSubscribers = 0;

        try {
            $totalLists = CampaignMaillist::count();
        } catch (\Throwable) {
        }
        try {
            $totalSubscribers = DB::table('campaign_maillists_subscribers')->count();
        } catch (\Throwable) {
        }
        try {
            $activeSubscribers = DB::table('campaign_maillists_subscribers')
                ->where('status', 'subscribed')->count();
        } catch (\Throwable) {
        }
        try {
            $inactiveSubscribers = DB::table('campaign_maillists_subscribers')
                ->where('status', '!=', 'subscribed')->count();
        } catch (\Throwable) {
        }

        $stats = [
            'total' => $totalLists,
            'subscribers' => $totalSubscribers,
            'active' => $activeSubscribers,
            'inactive' => $inactiveSubscribers,
        ];

        return view('campaign::manager.lists-pro.index', compact('stats'));
    }

    /**
     * AJAX: listado paginado de listas con search + sort.
     */
    public function listing(Request $request)
    {
        $query = CampaignMaillist::query();

        if ($keyword = $request->keyword) {
            $query->search(trim($keyword));
        }

        $sortOrder = in_array($request->sort_order, ['name', 'created_at'], true)
            ? $request->sort_order
            : 'created_at';
        $sortDir = $request->sort_direction === 'asc' ? 'asc' : 'desc';

        $lists = $query
            ->orderBy($sortOrder, $sortDir)
            ->paginate($request->per_page ?? 25)
            ->withQueryString();

        return view('campaign::manager.lists-pro._list', compact('lists'));
    }

    /**
     * Detalle de una lista: stats + campos + segmentos.
     */
    public function overview(string $uid)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        $activeCount = 0;
        $inactiveCount = 0;
        $fields = collect();
        $segments = collect();

        try {
            $activeCount = $list->subscribeCount();
        } catch (\Throwable) {
        }
        try {
            $inactiveCount = $list->unsubscribeCount();
        } catch (\Throwable) {
        }
        try {
            $fields = $list->fields()->orderBy('order')->get();
        } catch (\Throwable) {
        }
        try {
            $segments = $list->segments()->get();
        } catch (\Throwable) {
        }

        return view('campaign::manager.lists-pro.overview', compact(
            'list', 'activeCount', 'inactiveCount', 'fields', 'segments'
        ));
    }

    // =========================================================================
    // SUBSCRIBERS
    // =========================================================================

    /**
     * Página de suscriptores de una lista (carga McList vía AJAX).
     */
    public function subscribers(string $uid)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.lists-pro.subscribers', compact('list'));
    }

    /**
     * AJAX: tabla paginada de suscriptores de la lista.
     */
    public function subscribersListing(string $uid, Request $request)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        $subscribers = collect();

        try {
            $query = $list->subscribers();

            if ($keyword = $request->keyword) {
                $kw = trim($keyword);
                $query->where(function ($q) use ($kw): void {
                    $q->where('campaign_subscribers.email', 'like', "%{$kw}%")
                        ->orWhere('campaign_subscribers.first_name', 'like', "%{$kw}%")
                        ->orWhere('campaign_subscribers.last_name', 'like', "%{$kw}%");
                });
            }

            $subscribers = $query
                ->orderBy('campaign_subscribers.id', 'desc')
                ->paginate($request->per_page ?? 25)
                ->withQueryString();
        } catch (\Throwable) {
            $subscribers = new LengthAwarePaginator([], 0, 25);
        }

        return view('campaign::manager.lists-pro._subscribers_list', compact('list', 'subscribers'));
    }

    /**
     * Formulario para crear un nuevo suscriptor.
     */
    public function subscriberCreate(string $uid)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.lists-pro.subscriber_create', compact('list'));
    }

    /**
     * Guarda un nuevo suscriptor y lo asocia a la lista.
     */
    public function subscriberStore(string $uid, Request $request)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
        ]);

        $sub = CampaignSubscriber::firstOrCreate(
            ['email' => $data['email']],
            [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'subscribed_at' => now(),
            ],
        );

        DB::table('campaign_maillists_subscribers')->updateOrInsert(
            ['mail_list_id' => $list->id, 'subscriber_id' => $sub->id],
            [
                'uid' => (string) Str::uuid(),
                'status' => 'subscribed',
                'subscribed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => trans('campaign::lists.flash.created'),
                'redirect' => route('manager.lists_pro.subscribers', $list->uid),
            ]);
        }

        return redirect()
            ->route('manager.lists_pro.subscribers', $list->uid)
            ->with('success', trans('campaign::lists.flash.created'));
    }

    /**
     * Formulario para editar un suscriptor.
     */
    public function subscriberEdit(string $uid, string $subUid)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();
        $subscriber = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        $pivot = null;
        try {
            $pivot = DB::table('campaign_maillists_subscribers')
                ->where('mail_list_id', $list->id)
                ->where('subscriber_id', $subscriber->id)
                ->first();
        } catch (\Throwable) {
        }

        return view('campaign::manager.lists-pro.subscriber_edit', compact('list', 'subscriber', 'pivot'));
    }

    /**
     * Actualiza los datos de un suscriptor.
     */
    public function subscriberUpdate(string $uid, string $subUid, Request $request)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();
        $subscriber = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        $data = $request->validate([
            'email' => ['sometimes', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:subscribed,unsubscribed,unconfirmed'],
        ]);

        $subscriber->update(array_diff_key($data, ['status' => true]));

        if (! empty($data['status'])) {
            try {
                DB::table('campaign_maillists_subscribers')
                    ->where('mail_list_id', $list->id)
                    ->where('subscriber_id', $subscriber->id)
                    ->update([
                        'status' => $data['status'],
                        'updated_at' => now(),
                        'unsubscribed_at' => $data['status'] === 'unsubscribed' ? now() : null,
                    ]);
            } catch (\Throwable) {
            }
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => trans('campaign::lists.flash.updated'),
                'redirect' => route('manager.lists_pro.subscribers', $list->uid),
            ]);
        }

        return redirect()
            ->route('manager.lists_pro.subscribers', $list->uid)
            ->with('success', trans('campaign::lists.flash.updated'));
    }

    /**
     * Elimina la asociación de un suscriptor con la lista y devuelve JSON.
     */
    public function subscriberDelete(string $uid, string $subUid, Request $request): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();
        $subscriber = CampaignSubscriber::where('uid', $subUid)->firstOrFail();

        try {
            DB::table('campaign_maillists_subscribers')
                ->where('mail_list_id', $list->id)
                ->where('subscriber_id', $subscriber->id)
                ->delete();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => trans('campaign::lists.flash.deleted'),
        ]);
    }

    /**
     * Eliminación masiva de suscriptores de la lista (uids[]).
     */
    public function subscriberBulkDelete(string $uid, Request $request): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        $uids = (array) $request->input('uids', []);

        if (empty($uids)) {
            return response()->json(['success' => false, 'message' => 'No UIDs provided.'], 422);
        }

        try {
            $subscriberIds = CampaignSubscriber::whereIn('uid', $uids)->pluck('id');

            DB::table('campaign_maillists_subscribers')
                ->where('mail_list_id', $list->id)
                ->whereIn('subscriber_id', $subscriberIds)
                ->delete();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => trans('campaign::lists.flash.deleted'),
            'count' => count($uids),
        ]);
    }
}
