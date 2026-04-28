<?php

namespace Modules\CampaignSendingServers\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\CampaignSendingServers\Models\Blacklist;

class BlacklistController extends Controller
{
    public function index(Request $request): View
    {
        $entries = Blacklist::query()
            ->when($request->query('q'), fn ($q, $kw) => $q->where('email', 'like', "%{$kw}%"))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('campaignsendingservers::manager.blacklist.index', compact('entries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:campaign_sending_server_blacklists,email'],
            'reason' => ['nullable', 'string'],
        ]);
        $data['source'] = Blacklist::SOURCE_MANUAL;

        Blacklist::create($data);

        return back()->with('success', 'Email añadido a la lista negra.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Blacklist::findOrFail($id)->delete();

        return back()->with('success', 'Email eliminado.');
    }

    /**
     * Importación masiva por CSV (un email por línea o columna `email`).
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = @fopen($path, 'r');
        if (! $handle) {
            return back()->with('error', 'No se pudo leer el archivo.');
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $email = trim((string) ($row[0] ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            Blacklist::firstOrCreate(
                ['email' => $email],
                ['source' => Blacklist::SOURCE_IMPORT, 'reason' => 'CSV import'],
            );
            $count++;
        }
        fclose($handle);

        return back()->with('success', "Se importaron {$count} emails.");
    }
}
