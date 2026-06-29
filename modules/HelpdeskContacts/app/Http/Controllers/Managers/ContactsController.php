<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Http\Requests\Managers\ImportContactsRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\UpdateContactRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactsController extends Controller
{
    /**
     * Split list/detail page with a paginated, searchable customer list.
     * Authorization is handled by the route middleware (can:contacts.view).
     */
    public function index(Request $request): View
    {
        $allowedSorts = ['name', 'last_seen_at', 'total_conversations', 'created_at'];
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : 'last_seen_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $request->input('per_page'), [15, 25, 50, 100]) ? (int) $request->input('per_page') : 25;

        $customers = $this->applyFilters(Customer::query(), $request)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->appends($request->query());

        $selected = $request->filled('selected')
            ? Customer::find($request->integer('selected'))
            : null;

        return view('contacts::contacts.index', [
            'customers' => $customers,
            'selected' => $selected,
            'q' => $request->string('q')->trim()->toString(),
            'filters' => $request->only(['q', 'channel', 'last_seen', 'verified', 'banned']),
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
        ]);
    }

    /**
     * 360 tab-shell page for a single customer.
     * The customer is resolved via implicit binding on the 'helpdesk' connection.
     */
    public function show(Customer $customer): View
    {
        return view('contacts::contacts.show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update editable fields on a customer contact.
     */
    public function update(Customer $customer, UpdateContactRequest $request): JsonResponse
    {
        $customer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contacto actualizado correctamente',
        ]);
    }

    /**
     * Show the CSV import form.
     */
    public function importForm(): View
    {
        return view('contacts::contacts.import');
    }

    /**
     * Process an uploaded CSV and upsert contacts.
     * Matches on email; creates new records when no match is found.
     */
    public function importProcess(ImportContactsRequest $request): RedirectResponse
    {
        $handle = fopen($request->file('file')->getPathname(), 'r');

        // Skip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $nameCol = array_search('name', $headers) !== false ? array_search('name', $headers) : array_search('nombre', $headers);
        $emailCol = array_search('email', $headers) !== false ? array_search('email', $headers) : array_search('correo', $headers);
        $phoneCol = array_search('phone', $headers) !== false ? array_search('phone', $headers) : array_search('telefono', $headers);

        if ($nameCol === false && $emailCol === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'El CSV debe tener al menos la columna "name" o "email".']);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = ($nameCol !== false && isset($row[$nameCol])) ? trim($row[$nameCol]) : null;
            $email = ($emailCol !== false && isset($row[$emailCol])) ? trim($row[$emailCol]) : null;
            $phone = ($phoneCol !== false && isset($row[$phoneCol])) ? trim($row[$phoneCol]) : null;

            if (! $name && ! $email) {
                $skipped++;

                continue;
            }

            $existing = $email ? Customer::where('email', $email)->first() : null;

            if ($existing) {
                $existing->update(array_filter(compact('name', 'phone'), fn ($v) => $v !== null && $v !== ''));
                $updated++;
            } else {
                Customer::create(array_filter(compact('name', 'email', 'phone'), fn ($v) => $v !== null && $v !== ''));
                $created++;
            }
        }

        fclose($handle);

        return redirect()->route('contacts.index')
            ->with('success', "Importación completada: {$created} creados, {$updated} actualizados, {$skipped} omitidos.");
    }

    /**
     * Stream a CSV export of contacts matching the current filters.
     * Maximum 5 000 records.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'contactos-'.now()->format('Y-m-d').'.csv';

        $query = $this->applyFilters(Customer::query(), $request)
            ->latest('last_seen_at')
            ->limit(5000);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Nombre', 'Email', 'Teléfono', 'WhatsApp',
                'País', 'Última visita', 'Conversaciones', 'Verificado', 'Suspendido', 'Canales',
            ]);

            $query->chunk(500, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->id,
                        $customer->name,
                        $customer->email ?? '',
                        $customer->phone ?? '',
                        $customer->whatsapp_phone ?? '',
                        $customer->country ?? '',
                        $customer->last_seen_at?->toIso8601String() ?? '',
                        $customer->total_conversations ?? 0,
                        $customer->email_verified_at ? 'Sí' : 'No',
                        $customer->banned_at ? 'Sí' : 'No',
                        $this->channelList($customer),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Suspend a customer account.
     */
    public function ban(Customer $customer): JsonResponse
    {
        $customer->ban();

        return response()->json(['success' => true, 'message' => 'Contacto suspendido']);
    }

    /**
     * Reactivate a previously suspended customer.
     */
    public function unban(Customer $customer): JsonResponse
    {
        $customer->unban();

        return response()->json(['success' => true, 'message' => 'Contacto reactivado']);
    }

    /**
     * Apply a bulk action (ban, unban, delete) to a set of customer IDs.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:ban,unban,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('helpdesk_customers', 'id')],
        ], [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción debe ser ban, unban o delete.',
            'ids.required' => 'Debes seleccionar al menos un contacto.',
            'ids.min' => 'Debes seleccionar al menos un contacto.',
            'ids.*.exists' => 'Uno o más contactos seleccionados no existen.',
        ]);

        $ids = $data['ids'];

        match ($data['action']) {
            'ban' => Customer::whereIn('id', $ids)->update(['banned_at' => now()]),
            'unban' => Customer::whereIn('id', $ids)->update(['banned_at' => null]),
            'delete' => Customer::whereIn('id', $ids)->delete(),
        };

        return response()->json([
            'success' => true,
            'message' => 'Acción aplicada a '.count($ids).' contactos',
            'count' => count($ids),
        ]);
    }

    /**
     * At-risk dashboard with key contact health stats.
     */
    public function reports(): View
    {
        $atRisk = Customer::query()
            ->where(fn (Builder $q) => $q
                ->where('last_seen_at', '<', now()->subDays(30))
                ->orWhereNull('last_seen_at')
            )
            ->orderByRaw('last_seen_at IS NULL DESC, last_seen_at ASC')
            ->limit(50)
            ->get();

        $topActive = Customer::query()
            ->where('total_conversations', '>', 0)
            ->orderByDesc('total_conversations')
            ->limit(10)
            ->get();

        $stats = [
            'total' => Customer::query()->whereNull('banned_at')->count(),
            'banned' => Customer::query()->whereNotNull('banned_at')->count(),
            'inactive' => Customer::query()
                ->where(fn (Builder $q) => $q
                    ->where('last_seen_at', '<', now()->subDays(30))
                    ->orWhereNull('last_seen_at')
                )
                ->count(),
            'verified' => Customer::query()->whereNotNull('email_verified_at')->count(),
        ];

        return view('contacts::contacts.reports', compact('atRisk', 'topActive', 'stats'));
    }

    /**
     * Apply all supported query-string filters to a Customer query builder.
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        $term = $request->string('q')->trim()->toString();

        if ($term !== '') {
            $query->search($term);
        }

        match ($request->input('channel')) {
            'email' => $query->whereNotNull('email'),
            'whatsapp' => $query->whereNotNull('whatsapp_phone'),
            'facebook' => $query->whereNotNull('facebook_psid'),
            'instagram' => $query->whereNotNull('instagram_id'),
            default => null,
        };

        match ($request->input('last_seen')) {
            'today' => $query->whereDate('last_seen_at', today()),
            'week' => $query->where('last_seen_at', '>=', now()->subWeek()),
            'month' => $query->where('last_seen_at', '>=', now()->subMonth()),
            'inactive' => $query->where(
                fn (Builder $q) => $q->where('last_seen_at', '<', now()->subDays(30))
                    ->orWhereNull('last_seen_at')
            ),
            default => null,
        };

        if ($request->input('verified') === 'yes') {
            $query->whereNotNull('email_verified_at');
        } elseif ($request->input('verified') === 'no') {
            $query->whereNull('email_verified_at');
        }

        if ($request->input('banned') === 'yes') {
            $query->whereNotNull('banned_at');
        } elseif ($request->input('banned') === 'no') {
            $query->whereNull('banned_at');
        }

        return $query;
    }

    /**
     * Build a comma-separated list of active channel names for a customer.
     */
    private function channelList(Customer $customer): string
    {
        $channels = [];

        if ($customer->email) {
            $channels[] = 'email';
        }
        if ($customer->whatsapp_phone) {
            $channels[] = 'whatsapp';
        }
        if ($customer->facebook_psid) {
            $channels[] = 'facebook';
        }
        if ($customer->instagram_id) {
            $channels[] = 'instagram';
        }

        return implode(', ', $channels);
    }
}
