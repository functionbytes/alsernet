<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Campaign\Jobs\ExportSubscribersJob;
use Modules\Campaign\Jobs\ImportSubscribersJob;
use Modules\Campaign\Models\CampaignField;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\CampaignSendingServers\Models\SendingServer;

/**
 * Slim MaillistController.
 *
 * Cubre: index, create, store, show, edit, update, destroy, fields,
 * sending-servers (asociar), import-csv (queue job), export.
 */
class MaillistController extends Controller
{
    public function index(Request $request): View
    {
        $lists = CampaignMaillist::query()
            ->withCount('subscribers')
            ->search($request->query('q'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('campaign::manager.maillists.index', compact('lists'));
    }

    public function create(): View
    {
        return view('campaign::manager.maillists.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->rules($request);

        $list = CampaignMaillist::create($data);

        // Crea campos por defecto: FIRST_NAME, LAST_NAME (email es columna del modelo).
        foreach ([
            ['tag' => 'FIRST_NAME', 'label' => 'Nombre', 'type' => 'text'],
            ['tag' => 'LAST_NAME', 'label' => 'Apellido', 'type' => 'text'],
        ] as $field) {
            CampaignField::create([
                'mail_list_id' => $list->id,
                'tag' => $field['tag'],
                'label' => $field['label'],
                'type' => $field['type'],
                'visible' => true,
                'required' => false,
            ]);
        }

        return redirect()
            ->route('manager.campaigns.maillists.show', $list->uid)
            ->with('success', 'Lista creada.');
    }

    public function show(string $uid): View
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.maillists.show', [
            'list' => $list,
            'subscribed' => $list->subscribeCount(),
            'unsubscribed' => $list->unsubscribeCount(),
        ]);
    }

    public function edit(string $uid): View
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.maillists.edit', compact('list'));
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();
        $list->update($this->rules($request, true));

        return redirect()
            ->route('manager.campaigns.maillists.show', $list->uid)
            ->with('success', 'Lista actualizada.');
    }

    public function destroy(string $uid): RedirectResponse
    {
        CampaignMaillist::where('uid', $uid)->firstOrFail()->delete();

        return redirect()
            ->route('manager.campaigns.maillists.index')
            ->with('success', 'Lista eliminada.');
    }

    /**
     * Asociar sending servers a la lista (con priority).
     */
    public function sendingServers(Request $request, string $uid)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        if ($request->isMethod('POST')) {
            $data = $request->validate([
                'servers' => ['array'],
                'servers.*.id' => ['exists:campaign_sending_servers,id'],
                'servers.*.priority' => ['integer', 'min:1', 'max:100'],
            ]);

            $sync = [];
            foreach ($data['servers'] ?? [] as $row) {
                $sync[$row['id']] = ['priority' => $row['priority'] ?? 1];
            }
            $list->sendingServers()->sync($sync);

            return back()->with('success', 'Servidores actualizados.');
        }

        return view('campaign::manager.maillists.sending_servers', [
            'list' => $list,
            'allServers' => SendingServer::active()->get(),
        ]);
    }

    /**
     * Importa suscriptores desde CSV (queue).
     */
    public function importCsv(Request $request, string $uid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'mapping' => ['nullable', 'array'],   // [columna_csv => tag_field]
            'has_headers' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('file')->store('campaign/imports');

        ImportSubscribersJob::dispatch(
            $list->id,
            storage_path('app/'.$path),
            $request->input('mapping', []),
            $request->boolean('has_headers', true),
        );

        return back()->with('success', 'Importación encolada. Verás los suscriptores aparecer mientras se procesan.');
    }

    public function export(string $uid)
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        ExportSubscribersJob::dispatch($list->id);

        return back()->with('success', "Exportación encolada. Descárgalo en /campaign/exports/{$list->uid}.csv cuando termine.");
    }

    /**
     * Vista mapping de fields: el admin gestiona qué campos tiene la lista.
     * Cada CampaignField define una variable {{TAG}} usable en el builder.
     */
    public function fields(string $uid): View
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.maillists.fields', [
            'list' => $list,
            'fields' => $list->fields()->orderBy('order')->get(),
        ]);
    }

    public function fieldsStore(Request $request, string $uid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'tag' => ['required', 'string', 'regex:/^[A-Z][A-Z0-9_]*$/'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,email,number,select,date,phone,url'],
            'default_value' => ['nullable', 'string'],
            'required' => ['nullable', 'boolean'],
            'visible' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer'],
        ]);
        $data['tag'] = strtoupper($data['tag']);

        if ($list->fields()->where('tag', $data['tag'])->exists()) {
            return back()->withErrors(['tag' => 'Ya existe un field con tag '.$data['tag']]);
        }

        $list->fields()->create($data);

        return back()->with('success', "Campo {{ {$data['tag']} }} creado.");
    }

    public function fieldsUpdate(Request $request, string $uid, int $fieldId): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();
        $field = $list->fields()->findOrFail($fieldId);

        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:text,email,number,select,date,phone,url'],
            'default_value' => ['nullable', 'string'],
            'required' => ['nullable', 'boolean'],
            'visible' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer'],
        ]);

        $field->update($data);

        return back()->with('success', "Campo {$field->tag} actualizado.");
    }

    public function fieldsDestroy(string $uid, int $fieldId): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $uid)->firstOrFail();
        $field = $list->fields()->findOrFail($fieldId);

        if (in_array($field->tag, ['EMAIL', 'FIRST_NAME', 'LAST_NAME'], true)) {
            return back()->with('error', "El campo {$field->tag} es de sistema, no se puede eliminar.");
        }

        $field->delete();

        return back()->with('success', "Campo {$field->tag} eliminado.");
    }

    protected function rules(Request $request, bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'from_email' => ['nullable', 'email'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'default_subject' => ['nullable', 'string', 'max:998'],
            'subscribe_confirmation' => ['nullable', 'boolean'],
            'send_welcome_email' => ['nullable', 'boolean'],
            'unsubscribe_notification' => ['nullable', 'boolean'],
            'remind_message' => ['nullable', 'string'],
            'contact_company' => ['nullable', 'string'],
            'contact_address_1' => ['nullable', 'string'],
            'contact_city' => ['nullable', 'string'],
            'contact_state' => ['nullable', 'string'],
            'contact_zip' => ['nullable', 'string'],
            'contact_country_id' => ['nullable', 'string'],
        ]);
    }
}
